<?php
/**
 * Restaurant Management System
 * REST API: Order Lifecycle & Automatic Recipe Inventory Deduction
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$pdo = getDBConnection();

try {
    // ----------------------------------------------------
    // 1. KITCHEN STREAM (Real-time KDS Polling)
    // ----------------------------------------------------
    if ($action === 'kitchen_stream') {
        $orders = fetchAll(
            "SELECT o.id, o.order_number, o.order_type, o.status, o.notes, o.created_at,
                    t.table_number, u.full_name as waiter_name,
                    TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as elapsed_minutes
             FROM orders o
             LEFT JOIN dining_tables t ON o.table_id = t.id
             LEFT JOIN users u ON o.waiter_id = u.id
             WHERE o.status IN ('pending', 'accepted', 'preparing', 'ready')
             ORDER BY o.created_at ASC"
        );

        foreach ($orders as &$ord) {
            $ord['items'] = fetchAll(
                "SELECT oi.id, oi.quantity, oi.special_instructions, oi.item_status,
                        m.name as item_name, m.preparation_time
                 FROM order_items oi
                 JOIN menu_items m ON oi.menu_item_id = m.id
                 WHERE oi.order_id = ?",
                [$ord['id']]
            );
        }

        echo json_encode(['success' => true, 'timestamp' => time(), 'orders' => $orders]);
        exit;
    }

    // ----------------------------------------------------
    // 2. ORDER DETAIL
    // ----------------------------------------------------
    if ($action === 'detail') {
        $orderId = (int)($_GET['id'] ?? 0);
        $order = fetchOne(
            "SELECT o.*, t.table_number, t.floor_location, u.full_name as waiter_name, c.full_name as customer_name
             FROM orders o
             LEFT JOIN dining_tables t ON o.table_id = t.id
             LEFT JOIN users u ON o.waiter_id = u.id
             LEFT JOIN users c ON o.customer_id = c.id
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }

        $items = fetchAll(
            "SELECT oi.*, m.name as item_name, m.image, m.price
             FROM order_items oi
             JOIN menu_items m ON oi.menu_item_id = m.id
             WHERE oi.order_id = ?",
            [$orderId]
        );

        $history = fetchAll(
            "SELECT osh.*, u.full_name as changed_by_name
             FROM order_status_history osh
             LEFT JOIN users u ON osh.changed_by = u.id
             WHERE osh.order_id = ?
             ORDER BY osh.created_at ASC",
            [$orderId]
        );

        $payment = fetchOne("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1", [$orderId]);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'payment' => $payment
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 3. CREATE ORDER & AUTO DEDUCT RECIPE INVENTORY
    // ----------------------------------------------------
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Read JSON input or POST form
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $tableId = !empty($input['table_id']) ? (int)$input['table_id'] : null;
        $orderType = $input['order_type'] ?? 'dine_in';
        $notes = trim($input['notes'] ?? '');
        $items = $input['items'] ?? [];
        $couponCode = !empty($input['coupon_code']) ? trim($input['coupon_code']) : null;
        $userId = currentUserId();

        if (empty($items) || !is_array($items)) {
            echo json_encode(['success' => false, 'error' => 'Order must contain at least one item.']);
            exit;
        }

        $pdo->beginTransaction();

        // Generate unique order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Calculate line items
        $subtotal = 0.00;
        $verifiedItems = [];

        foreach ($items as $item) {
            $itemId = (int)($item['menu_item_id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $instructions = trim($item['special_instructions'] ?? '');

            $menuItem = fetchOne("SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1", [$itemId]);
            if (!$menuItem) {
                throw new Exception("Menu item #$itemId is no longer available.");
            }

            $price = (float)$menuItem['price'];
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $verifiedItems[] = [
                'menu_item_id' => $itemId,
                'name' => $menuItem['name'],
                'quantity' => $qty,
                'unit_price' => $price,
                'subtotal' => $lineTotal,
                'instructions' => $instructions
            ];
        }

        // Apply Coupon if valid
        $discountAmount = 0.00;
        if ($couponCode) {
            $coupon = fetchOne("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (valid_to IS NULL OR valid_to >= CURDATE())", [$couponCode]);
            if ($coupon && $subtotal >= (float)$coupon['min_order_amount']) {
                if ($coupon['discount_type'] === 'percentage') {
                    $discountAmount = ($subtotal * (float)$coupon['discount_value']) / 100.0;
                    if ((float)$coupon['max_discount'] > 0) {
                        $discountAmount = min($discountAmount, (float)$coupon['max_discount']);
                    }
                } else {
                    $discountAmount = min((float)$coupon['discount_value'], $subtotal);
                }
                // Increment coupon counter
                executeQuery("UPDATE coupons SET times_used = times_used + 1 WHERE id = ?", [$coupon['id']]);
            }
        }

        $taxRate = (float)getSetting('tax_rate_percent', '8.5');
        $taxableBase = max(0, $subtotal - $discountAmount);
        $taxAmount = round(($taxableBase * $taxRate) / 100.0, 2);
        $finalAmount = $taxableBase + $taxAmount;

        // Insert Order
        $orderSql = "INSERT INTO orders (order_number, customer_id, table_id, waiter_id, order_type, status, subtotal, tax_amount, discount_amount, final_amount, coupon_code, notes, created_by)
                     VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)";
        
        $customerId = (currentUserRole() === 'customer') ? $userId : (!empty($input['customer_id']) ? (int)$input['customer_id'] : null);
        $waiterId = (currentUserRole() === 'waiter') ? $userId : null;

        $orderId = insertAndGetId($orderSql, [
            $orderNumber,
            $customerId,
            $tableId,
            $waiterId,
            $orderType,
            $subtotal,
            $taxAmount,
            $discountAmount,
            $finalAmount,
            $couponCode,
            $notes,
            $userId
        ]);

        // Insert Order Items and DEDUCT INGREDIENTS FROM RECIPE BOM
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal, special_instructions, item_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        
        $stockDeductStmt = $pdo->prepare("UPDATE ingredients SET current_stock = current_stock - ? WHERE id = ?");
        $stockTxnStmt = $pdo->prepare("INSERT INTO stock_transactions (ingredient_id, transaction_type, quantity, reference_order_id, notes, created_by) VALUES (?, 'consumption', ?, ?, ?, ?)");

        foreach ($verifiedItems as $vItem) {
            $itemStmt->execute([
                $orderId,
                $vItem['menu_item_id'],
                $vItem['quantity'],
                $vItem['unit_price'],
                $vItem['subtotal'],
                $vItem['instructions']
            ]);

            // Query Recipe Ingredients for this Menu Item
            $recipeIngredients = fetchAll(
                "SELECT ingredient_id, quantity_required FROM menu_ingredients WHERE menu_item_id = ?",
                [$vItem['menu_item_id']]
            );

            foreach ($recipeIngredients as $recipe) {
                $totalConsumed = (float)$recipe['quantity_required'] * $vItem['quantity'];
                
                // Deduct stock
                $stockDeductStmt->execute([$totalConsumed, $recipe['ingredient_id']]);

                // Record transaction
                $stockTxnStmt->execute([
                    $recipe['ingredient_id'],
                    -$totalConsumed,
                    $orderId,
                    "Consumption for Order #$orderNumber ({$vItem['quantity']}x {$vItem['name']})",
                    $userId
                ]);

                // Check if stock is now below reorder level -> trigger low-stock alert
                $ing = fetchOne("SELECT name, current_stock, reorder_level, unit FROM ingredients WHERE id = ?", [$recipe['ingredient_id']]);
                if ($ing && (float)$ing['current_stock'] <= (float)$ing['reorder_level']) {
                    createNotification(
                        null,
                        "Low Stock: " . $ing['name'],
                        "Stock level dropped to " . number_format((float)$ing['current_stock'], 1) . " {$ing['unit']} (Reorder threshold: {$ing['reorder_level']} {$ing['unit']})",
                        "low_stock",
                        "../admin/inventory.php"
                    );
                }
            }
        }

        // If dine_in table specified, update table status to 'occupied'
        if ($tableId) {
            executeQuery("UPDATE dining_tables SET status = 'occupied' WHERE id = ?", [$tableId]);
        }

        // Record Order Status History
        executeQuery("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'pending', 'Order placed', ?)", [$orderId, $userId]);

        // Create Notification for Kitchen
        createNotification(null, "New Order: $orderNumber", "Kitchen ticket generated for Table #" . ($tableId ? $tableId : 'Takeaway'), "order", "../kitchen/dashboard.php");

        // Log enterprise audit
        logAudit($userId, 'CREATE_ORDER', 'orders', $orderId, null, [
            'order_number' => $orderNumber,
            'table_id' => $tableId,
            'final_amount' => $finalAmount,
            'item_count' => count($verifiedItems)
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Order #$orderNumber created successfully!",
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'final_amount' => $finalAmount
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 4. UPDATE STATUS (Kitchen KDS & Waiter Pipeline)
    // ----------------------------------------------------
    if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $orderId = (int)($input['order_id'] ?? 0);
        $newStatus = trim($input['status'] ?? '');
        $notes = trim($input['notes'] ?? '');
        $userId = currentUserId();

        $allowedStatuses = ['pending', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid status code']);
            exit;
        }

        $currentOrder = fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$currentOrder) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }

        $oldStatus = $currentOrder['status'];

        // Update Order
        executeQuery("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $orderId]);

        // Insert Status History
        executeQuery("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, ?, ?, ?)", [
            $orderId,
            $newStatus,
            $notes ?: "Status progressed to " . ucfirst($newStatus),
            $userId
        ]);

        // If completed or cancelled, release dining table if dine_in
        if (in_array($newStatus, ['completed', 'cancelled'], true) && !empty($currentOrder['table_id'])) {
            executeQuery("UPDATE dining_tables SET status = 'available' WHERE id = ?", [$currentOrder['table_id']]);
        }

        // Notify Waiter if order is Ready
        if ($newStatus === 'ready') {
            createNotification(
                $currentOrder['waiter_id'],
                "Order Ready for Pickup!",
                "Order #{$currentOrder['order_number']} is prepared and ready at the pass.",
                "order",
                "../waiter/orders.php"
            );
        }

        logAudit($userId, 'UPDATE_ORDER_STATUS', 'orders', $orderId, ['status' => $oldStatus], ['status' => $newStatus]);

        echo json_encode([
            'success' => true,
            'message' => "Order #{$currentOrder['order_number']} marked as " . ucfirst($newStatus),
            'order_id' => $orderId,
            'status' => $newStatus
        ]);
        exit;
    }

    // Default: List orders
    $statusFilter = $_GET['status'] ?? null;
    $sql = "SELECT o.*, t.table_number, u.full_name as waiter_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o
            LEFT JOIN dining_tables t ON o.table_id = t.id
            LEFT JOIN users u ON o.waiter_id = u.id";
    $params = [];

    if ($statusFilter) {
        $sql .= " WHERE o.status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY o.created_at DESC LIMIT 50";

    $orders = fetchAll($sql, $params);
    echo json_encode(['success' => true, 'orders' => $orders]);

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
