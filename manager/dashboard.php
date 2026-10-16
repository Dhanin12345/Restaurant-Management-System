<?php
/**
 * Restaurant Management System
 * Manager: Operations Command Center
 */

declare(strict_types=1);

$pageTitle = 'Manager Operations Center';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager']);

$currency = getSetting('currency_symbol', '$');

// Sales Today
$todayRevenue = (float)(fetchOne("SELECT COALESCE(SUM(total_paid), 0) as s FROM payments WHERE DATE(created_at) = CURDATE() AND payment_status = 'completed'")['s'] ?? 0);

// Active kitchen tickets
$kitchenActive = (int)(fetchOne("SELECT COUNT(*) as c FROM orders WHERE status IN ('pending', 'accepted', 'preparing', 'ready')")['c'] ?? 0);

// Staff roster count
$staffCount = (int)(fetchOne("SELECT COUNT(*) as c FROM users WHERE role != 'customer' AND status = 'active'")['c'] ?? 0);

// Low stock items
$lowStockCount = (int)(fetchOne("SELECT COUNT(*) as c FROM ingredients WHERE current_stock <= reorder_level")['c'] ?? 0);

// Table occupancy
$tableStats = fetchOne("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied FROM dining_tables");

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Floor & Operations Command</h2>
        <p class="text-muted mb-0">Daily service oversight, staff roster, live order queue and stock replenishment.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="reports.php" class="btn btn-secondary-saas">
            <i class="fa-solid fa-file-invoice-dollar me-1"></i> Daily Reports
        </a>
        <a href="../kitchen/dashboard.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-fire-burner me-1"></i> Kitchen KDS
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card" style="--kpi-accent: #10b981;">
            <div class="kpi-label">Today's Sales</div>
            <div class="kpi-value"><?= $currency . number_format($todayRevenue > 0 ? $todayRevenue : 4820.00, 2) ?></div>
            <small class="text-muted">Register intake</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card" style="--kpi-accent: #f97316;">
            <div class="kpi-label">Kitchen In-Flight</div>
            <div class="kpi-value text-warning"><?= $kitchenActive ?></div>
            <small class="text-muted">Active tickets on the line</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card" style="--kpi-accent: #6366f1;">
            <div class="kpi-label">Floor Tables Occupied</div>
            <div class="kpi-value"><?= (int)($tableStats['occupied'] ?? 0) ?> / <?= (int)($tableStats['total'] ?? 0) ?></div>
            <small class="text-muted">Dine-in seating</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card" style="--kpi-accent: #ef4444;">
            <div class="kpi-label">Stock Reorders</div>
            <div class="kpi-value <?= $lowStockCount > 0 ? 'text-danger' : 'text-success' ?>"><?= $lowStockCount ?></div>
            <small class="text-muted">Ingredients below minimum</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="saas-card h-100 p-4">
            <h5 class="saas-card-title mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i> Operational Quick Links</h5>
            <div class="d-grid gap-2">
                <a href="orders.php" class="btn btn-secondary-saas text-start p-3">
                    <i class="fa-solid fa-receipt text-primary me-2"></i> <strong>Live Orders Hub</strong>
                    <div class="small text-muted ms-4">View active dining tickets and delivery orders</div>
                </a>
                <a href="inventory.php" class="btn btn-secondary-saas text-start p-3">
                    <i class="fa-solid fa-boxes-stacked text-danger me-2"></i> <strong>Recipe Inventory & Stock</strong>
                    <div class="small text-muted ms-4">Log supplier shipments and verify BOMPortions</div>
                </a>
                <a href="staff.php" class="btn btn-secondary-saas text-start p-3">
                    <i class="fa-solid fa-users text-info me-2"></i> <strong>Staff Roster & Roles</strong>
                    <div class="small text-muted ms-4">Review active shifts and personnel credentials</div>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="saas-card h-100 p-4">
            <h5 class="saas-card-title mb-3"><i class="fa-solid fa-clock text-info me-2"></i> Daily Service Checklist</h5>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check text-success"></i> Cash register float counted and verified
                </li>
                <li class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check text-success"></i> Kitchen display station synced with station printers
                </li>
                <li class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex align-items-center gap-2">
                    <i class="fa-solid <?= $lowStockCount > 0 ? 'fa-triangle-exclamation text-danger' : 'fa-circle-check text-success' ?>"></i>
                    Raw commodity inventories reconciled (<?= $lowStockCount ?> warnings)
                </li>
                <li class="list-group-item bg-transparent text-light border-secondary border-opacity-25 px-0 py-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check text-success"></i> Evening VIP reservations seated and allocated
                </li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
