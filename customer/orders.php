<?php
/**
 * Restaurant Management System
 * Customer: Order History & Live Delivery/Dining Tracker
 */

declare(strict_types=1);

$pageTitle = 'My Dining Orders';
require_once __DIR__ . '/../includes/header.php';

$userId = currentUserId();
$currency = getSetting('currency_symbol', '$');

$orders = [];
if (isLoggedIn()) {
    $orders = fetchAll(
        "SELECT o.*, t.table_number,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
         FROM orders o
         LEFT JOIN dining_tables t ON o.table_id = t.id
         WHERE o.customer_id = ?
         ORDER BY o.created_at DESC",
        [$userId]
    );
}

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">My Orders & Live Dining Tracker</h2>
        <p class="text-muted mb-0">Follow live kitchen progress, runner delivery status and past dining receipts.</p>
    </div>
    <div>
        <a href="menu.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-plus-circle me-1"></i> Order Food
        </a>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($orders)): ?>
        <div class="col-12">
            <div class="saas-card p-5 text-center text-muted">
                <i class="fa-solid fa-bag-shopping fa-3x mb-3 text-secondary opacity-50"></i>
                <h5>No Orders Found</h5>
                <p>You have not placed any dining or takeaway orders yet.</p>
                <a href="menu.php" class="btn btn-primary-saas mt-2">Explore Delicious Menu</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $ord): 
            $items = fetchAll("SELECT oi.*, m.name as item_name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = ?", [$ord['id']]);
        ?>
            <div class="col-md-6 col-xl-4">
                <div class="saas-card h-100">
                    <div class="saas-card-header">
                        <div>
                            <span class="fw-bold text-primary font-monospace"><?= htmlspecialchars($ord['order_number']) ?></span>
                            <div class="text-muted small"><?= date('M j, Y &bull; g:i A', strtotime($ord['created_at'])) ?></div>
                        </div>
                        <span class="badge-status badge-<?= htmlspecialchars($ord['status']) ?>">
                            <?= ucfirst($ord['status']) ?>
                        </span>
                    </div>

                    <div class="saas-card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-dark border border-secondary border-opacity-25 text-light text-uppercase">
                                <i class="fa-solid fa-utensils text-primary me-1"></i> <?= htmlspecialchars($ord['table_number'] ? 'Table ' . $ord['table_number'] : $ord['order_type']) ?>
                            </span>
                            <span class="fw-bold text-white fs-5"><?= $currency . number_format((float)$ord['final_amount'], 2) ?></span>
                        </div>

                        <!-- Ordered Dishes List -->
                        <div class="p-2 rounded-3 mb-3" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                            <ul class="list-unstyled mb-0 small">
                                <?php foreach ($items as $it): ?>
                                    <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10 text-light">
                                        <span><strong><?= $it['quantity'] ?>x</strong> <?= htmlspecialchars($it['item_name']) ?></span>
                                        <span class="text-muted"><?= $currency . number_format((float)$it['subtotal'], 2) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Visual Progress Bar for active orders -->
                        <?php if ($ord['status'] !== 'completed' && $ord['status'] !== 'cancelled'): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between text-subtle small mb-1" style="font-size:0.72rem;">
                                    <span>Placed</span>
                                    <span>Cooking</span>
                                    <span>Ready</span>
                                    <span>Enjoy</span>
                                </div>
                                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.08);">
                                    <?php 
                                        $pct = 25;
                                        if ($ord['status'] === 'accepted' || $ord['status'] === 'preparing') $pct = 60;
                                        if ($ord['status'] === 'ready') $pct = 85;
                                        if ($ord['status'] === 'served') $pct = 100;
                                    ?>
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span>Tax: <?= $currency . number_format((float)$ord['tax_amount'], 2) ?></span>
                            <?php if ($ord['status'] === 'completed'): ?>
                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Receipt Settled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
