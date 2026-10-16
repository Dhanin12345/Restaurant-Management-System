<?php
/**
 * Restaurant Management System
 * Kitchen: Orders Queue Table
 */

declare(strict_types=1);

$pageTitle = 'Kitchen Orders Queue';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'kitchen']);

$orders = fetchAll(
    "SELECT o.*, t.table_number, u.full_name as waiter_name,
            TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as elapsed_minutes
     FROM orders o
     LEFT JOIN dining_tables t ON o.table_id = t.id
     LEFT JOIN users u ON o.waiter_id = u.id
     WHERE o.status IN ('pending', 'accepted', 'preparing', 'ready')
     ORDER BY o.created_at ASC"
);

foreach ($orders as &$ord) {
    $ord['items'] = fetchAll(
        "SELECT oi.*, m.name as item_name, m.preparation_time 
         FROM order_items oi 
         JOIN menu_items m ON oi.menu_item_id = m.id 
         WHERE oi.order_id = ?",
        [$ord['id']]
    );
}

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Kitchen Queue Table</h2>
        <p class="text-muted mb-0">Detailed list of active kitchen tickets with special customer prep requests.</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-table-columns me-1"></i> Open KDS Board
        </a>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Destination</th>
                    <th>Dishes to Prepare</th>
                    <th>Elapsed</th>
                    <th>State</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">All orders have been prepared and cleared!</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-primary"><?= htmlspecialchars($o['order_number']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-light">
                                    <?= htmlspecialchars($o['table_number'] ?? ucfirst($o['order_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <ul class="list-unstyled mb-0 small">
                                    <?php foreach ($o['items'] as $it): ?>
                                        <li class="mb-1 text-light">
                                            <strong><?= $it['quantity'] ?>x</strong> <?= htmlspecialchars($it['item_name']) ?>
                                            <?php if ($it['special_instructions']): ?>
                                                <span class="badge bg-warning bg-opacity-25 text-warning ms-1"><?= htmlspecialchars($it['special_instructions']) ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-warning">
                                    <?= $o['elapsed_minutes'] ?> mins
                                </span>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= htmlspecialchars($o['status']) ?>">
                                    <?= ucfirst($o['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="dashboard.php" class="btn btn-sm btn-secondary-saas py-1 px-2">
                                    Manage in KDS <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
