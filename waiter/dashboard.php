<?php
/**
 * Restaurant Management System
 * Waiter: Floor Station Overview
 */

declare(strict_types=1);

$pageTitle = 'Waiter Service Station';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'waiter']);

$userId = currentUserId();
$currency = getSetting('currency_symbol', '$');

// Waiter's Active Orders
$myOrders = fetchAll(
    "SELECT o.*, t.table_number, 
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
     FROM orders o
     LEFT JOIN dining_tables t ON o.table_id = t.id
     WHERE (o.waiter_id = ? OR o.waiter_id IS NULL)
       AND o.status IN ('pending', 'accepted', 'preparing', 'ready', 'served')
     ORDER BY (CASE WHEN o.status = 'ready' THEN 0 ELSE 1 END), o.created_at DESC",
    [$userId]
);

// Ready for pickup count
$readyCount = count(array_filter($myOrders, fn($o) => $o['status'] === 'ready'));

// Table overview
$tables = fetchAll("SELECT * FROM dining_tables ORDER BY floor_location ASC, table_number ASC");

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Waiter Service Station</h2>
        <p class="text-muted mb-0">Floor tables, quick dining ticket entry and food runner notifications.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="create_order.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-plus-circle me-1"></i> New POS Order
        </a>
    </div>
</div>

<!-- Alert for orders ready at pass -->
<?php if ($readyCount > 0): ?>
    <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4 shadow-sm" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3);">
        <div class="d-flex align-items-center gap-3">
            <span class="badge rounded-circle p-2 bg-success text-white"><i class="fa-solid fa-bell fa-lg"></i></span>
            <div>
                <h6 class="text-white fw-bold mb-0"><?= $readyCount ?> Order(s) Ready at Kitchen Pass!</h6>
                <small class="text-light opacity-75">Please pick up and serve to guests immediately.</small>
            </div>
        </div>
        <a href="orders.php" class="btn btn-sm btn-success text-white fw-bold">View Pickup Tickets</a>
    </div>
<?php endif; ?>

<!-- 3 Quick KPI Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card" style="--kpi-accent: #f97316;">
            <div class="kpi-label">My Active Tickets</div>
            <div class="kpi-value"><?= count($myOrders) ?></div>
            <small class="text-muted">Orders under active service</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card" style="--kpi-accent: #10b981;">
            <div class="kpi-label">Ready at Kitchen Pass</div>
            <div class="kpi-value text-success"><?= $readyCount ?></div>
            <small class="text-muted">Awaiting runner delivery</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card" style="--kpi-accent: #6366f1;">
            <div class="kpi-label">Total Floor Tables</div>
            <div class="kpi-value"><?= count($tables) ?></div>
            <small class="text-muted">Across all dining zones</small>
        </div>
    </div>
</div>

<!-- Interactive Floor Grid -->
<div class="saas-card mb-4">
    <div class="saas-card-header">
        <h5 class="saas-card-title mb-0"><i class="fa-solid fa-table-cells-large text-primary me-2"></i> Quick Table Selector</h5>
        <small class="text-muted">Click any available table to launch POS</small>
    </div>
    <div class="saas-card-body">
        <div class="floorplan-grid">
            <?php foreach ($tables as $t): ?>
                <div class="table-seat-card table-<?= htmlspecialchars($t['status']) ?>" onclick="handleTableClick(<?= $t['id'] ?>, '<?= $t['status'] ?>')">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted"><?= $t['seating_capacity'] ?> seats</small>
                        <span class="badge-status badge-<?= htmlspecialchars($t['status']) ?>" style="font-size:0.6rem;"><?= ucfirst($t['status']) ?></span>
                    </div>
                    <div class="h4 fw-bold text-white mb-0"><?= htmlspecialchars($t['table_number']) ?></div>
                    <small class="text-subtle" style="font-size:0.7rem;"><?= htmlspecialchars($t['floor_location']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function handleTableClick(tableId, status) {
    if (status === 'available') {
        window.location.href = `create_order.php?table_id=${tableId}`;
    } else if (status === 'occupied') {
        window.location.href = `orders.php`;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
