<?php
/**
 * Restaurant Management System
 * REST API: Inventory, Recipe BOM & Stock Management
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    // 1. Restock ingredient (Stock replenishment)
    if ($action === 'restock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        requireRole(['admin', 'manager']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $ingredientId = (int)($input['ingredient_id'] ?? 0);
        $quantity = (float)($input['quantity'] ?? 0);
        $unitCost = !empty($input['unit_cost']) ? (float)$input['unit_cost'] : null;
        $notes = trim($input['notes'] ?? 'Supplier restock');
        $userId = currentUserId();

        if ($ingredientId <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'error' => 'Valid ingredient and positive quantity required.']);
            exit;
        }

        $ing = fetchOne("SELECT * FROM ingredients WHERE id = ?", [$ingredientId]);
        if (!$ing) {
            echo json_encode(['success' => false, 'error' => 'Ingredient not found.']);
            exit;
        }

        $oldStock = (float)$ing['current_stock'];
        $newStock = $oldStock + $quantity;

        // Update ingredient
        if ($unitCost !== null) {
            executeQuery(
                "UPDATE ingredients SET current_stock = ?, unit_cost = ?, last_restocked_at = NOW() WHERE id = ?",
                [$newStock, $unitCost, $ingredientId]
            );
        } else {
            executeQuery(
                "UPDATE ingredients SET current_stock = ?, last_restocked_at = NOW() WHERE id = ?",
                [$newStock, $ingredientId]
            );
        }

        // Log transaction
        executeQuery(
            "INSERT INTO stock_transactions (ingredient_id, transaction_type, quantity, notes, created_by) VALUES (?, 'purchase', ?, ?, ?)",
            [$ingredientId, $quantity, $notes, $userId]
        );

        logAudit($userId, 'RESTOCK_INGREDIENT', 'ingredients', $ingredientId, ['current_stock' => $oldStock], ['current_stock' => $newStock, 'added' => $quantity]);

        echo json_encode([
            'success' => true,
            'message' => "Restocked {$ing['name']} with +$quantity {$ing['unit']}",
            'new_stock' => $newStock
        ]);
        exit;
    }

    // 2. Adjust or log wastage
    if ($action === 'adjust' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        requireRole(['admin', 'manager']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $ingredientId = (int)($input['ingredient_id'] ?? 0);
        $type = in_array($input['type'] ?? '', ['wastage', 'adjustment']) ? $input['type'] : 'adjustment';
        $quantity = (float)($input['quantity'] ?? 0); // negative for reduction
        $notes = trim($input['notes'] ?? 'Inventory audit adjustment');
        $userId = currentUserId();

        $ing = fetchOne("SELECT * FROM ingredients WHERE id = ?", [$ingredientId]);
        if (!$ing) {
            echo json_encode(['success' => false, 'error' => 'Ingredient not found.']);
            exit;
        }

        $oldStock = (float)$ing['current_stock'];
        $newStock = max(0, $oldStock + $quantity);

        executeQuery("UPDATE ingredients SET current_stock = ? WHERE id = ?", [$newStock, $ingredientId]);
        executeQuery(
            "INSERT INTO stock_transactions (ingredient_id, transaction_type, quantity, notes, created_by) VALUES (?, ?, ?, ?, ?)",
            [$ingredientId, $type, $quantity, $notes, $userId]
        );

        logAudit($userId, 'INVENTORY_ADJUSTMENT', 'ingredients', $ingredientId, ['current_stock' => $oldStock], ['current_stock' => $newStock]);

        echo json_encode([
            'success' => true,
            'message' => "Updated stock to $newStock {$ing['unit']}",
            'new_stock' => $newStock
        ]);
        exit;
    }

    // Default: List ingredients with status flags
    $ingredients = fetchAll(
        "SELECT id, name, unit, current_stock, reorder_level, unit_cost, last_restocked_at,
                (CASE WHEN current_stock <= reorder_level THEN 1 ELSE 0 END) as is_low_stock
         FROM ingredients
         ORDER BY is_low_stock DESC, name ASC"
    );

    echo json_encode(['success' => true, 'ingredients' => $ingredients]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
