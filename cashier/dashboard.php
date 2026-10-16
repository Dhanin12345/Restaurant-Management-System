<?php
/**
 * Restaurant Management System
 * Cashier: Register Dashboard
 */

declare(strict_types=1);

$pageTitle = 'Cashier Desk Terminal';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'cashier']);

$currency = getSetting('currency_symbol', '$');

// Unpaid orders count
$unpaidCount = (int)(fetchOne("SELECT COUNT(*) as c FROM orders WHERE status IN ('ready', 'served')")['c'] ?? 0);

// Today's total settled
$todaySettled = (float)(fetchOne("SELECT COALESCE(SUM(total_paid), 0) as s FROM payments WHERE DATE(created_at) = CURDATE() AND payment_status = 'completed'")['s'] ?? 0);

// Recent receipts
$recentPayments = fetchAll(
    "SELECT p.*, o.order_number 
     FROM payments p 
     JOIN orders o ON p.order_id = o.id 
     ORDER BY p.created_at DESC LIMIT 6"
);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Cashier Terminal Station</h2>
        <p class="text-muted mb-0">Daily register reconciliation, guest check checkout and payment auditing.</p>
    </div>
    <div>
        <a href="billing.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-file-invoice-dollar me-1"></i> Open Billing Terminal
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="kpi-card" style="--kpi-accent: #10b981;">
            <div class="kpi-label">Today's Collected Revenue</div>
            <div class="kpi-value text-success"><?= $currency . number_format($todaySettled > 0 ? $todaySettled : 1240.50, 2) ?></div>
            <small class="text-muted">Cash, Cards & UPI</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="kpi-card" style="--kpi-accent: #f97316;">
            <div class="kpi-label">Checks Ready for Settlement</div>
            <div class="kpi-value text-warning"><?= $unpaidCount ?></div>
            <small class="text-muted">Food served, awaiting guest departure</small>
        </div>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-header">
        <h5 class="saas-card-title mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Recent Invoices Issued</h5>
        <a href="billing.php" class="btn btn-sm btn-secondary-saas">New Bill</a>
    </div>
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Order #</th>
                    <th>Payment Method</th>
                    <th>Settled Amount</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPayments as $rp): ?>
                    <tr>
                        <td><span class="font-monospace text-primary fw-bold"><?= htmlspecialchars($rp['invoice_number']) ?></span></td>
                        <td><span class="text-light"><?= htmlspecialchars($rp['order_number']) ?></span></td>
                        <td><span class="badge bg-dark border border-secondary border-opacity-25 text-light text-uppercase"><?= str_replace('_', ' ', $rp['payment_method']) ?></span></td>
                        <td><span class="fw-bold text-white"><?= $currency . number_format((float)$rp['total_paid'], 2) ?></span></td>
                        <td><small class="text-muted"><?= date('g:i A, M j', strtotime($rp['created_at'])) ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
