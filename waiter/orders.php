<?php
/**
 * Restaurant Management System
 * Waiter: Active Service Tickets
 */

declare(strict_types=1);

$pageTitle = 'My Active Service Tickets';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'waiter']);

$currency = getSetting('currency_symbol', '$');

// Handle Mark Served POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    if (validateCsrfToken()) {
        $orderId = (int)$_POST['order_id'];
        executeQuery("UPDATE orders SET status = 'served', updated_at = NOW() WHERE id = ?", [$orderId]);
        executeQuery("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'served', 'Waiter served food to table', ?)", [$orderId, currentUserId()]);
        setFlash('success', "Order marked as served to guests!");
        header("Location: orders.php");
        exit;
    }
}

$orders = fetchAll(
    "SELECT o.*, t.table_number,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
     FROM orders o
     LEFT JOIN dining_tables t ON o.table_id = t.id
     WHERE o.status IN ('pending', 'accepted', 'preparing', 'ready', 'served')
     ORDER BY (CASE WHEN o.status = 'ready' THEN 0 ELSE 1 END), o.created_at DESC"
);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Active Floor Tickets</h2>
        <p class="text-muted mb-0">Follow kitchen prep status and mark dishes served as runners bring them out.</p>
    </div>
    <div>
        <a href="create_order.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-plus-circle me-1"></i> New POS Order
        </a>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Table</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Kitchen Status</th>
                    <th>Elapsed</th>
                    <th class="text-end">Service Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No active orders on floor right now.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr class="<?= $o['status'] === 'ready' ? 'bg-success bg-opacity-10' : '' ?>">
                            <td>
                                <span class="fw-bold text-primary"><?= htmlspecialchars($o['order_number']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-light">
                                    <i class="fa-solid fa-chair text-primary me-1"></i> <?= htmlspecialchars($o['table_number'] ?? ucfirst($o['order_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-light small"><?= $o['item_count'] ?> items</span>
                            </td>
                            <td>
                                <span class="fw-bold text-light"><?= $currency . number_format((float)$o['final_amount'], 2) ?></span>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= htmlspecialchars($o['status']) ?>">
                                    <?= ucfirst($o['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?= date('g:i A', strtotime($o['created_at'])) ?></small>
                            </td>
                            <td class="text-end">
                                <?php if ($o['status'] === 'ready'): ?>
                                    <form action="orders.php" method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success text-white fw-bold py-1 px-3">
                                            <i class="fa-solid fa-check-double me-1"></i> Mark Served
                                        </button>
                                    </form>
                                <?php elseif ($o['status'] === 'served'): ?>
                                    <a href="../cashier/billing.php?order_id=<?= $o['id'] ?>" class="btn btn-sm btn-secondary-saas py-1 px-2">
                                        Bill Desk <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Cooking in Kitchen</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
