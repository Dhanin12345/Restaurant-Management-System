<?php
/**
 * Restaurant Management System
 * Admin: Payments & Billing Ledger
 */

declare(strict_types=1);

$pageTitle = 'Payments & Revenue Ledger';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'cashier']);

$currency = getSetting('currency_symbol', '$');

$payments = fetchAll(
    "SELECT p.*, o.order_number, o.order_type, u.full_name as cashier_name
     FROM payments p
     JOIN orders o ON p.order_id = o.id
     LEFT JOIN users u ON p.processed_by = u.id
     ORDER BY p.created_at DESC LIMIT 100"
);

// Method Breakdown Summary
$methodStats = fetchAll(
    "SELECT payment_method, COUNT(*) as count, SUM(total_paid) as total_amount 
     FROM payments 
     WHERE payment_status = 'completed'
     GROUP BY payment_method"
);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Financial Ledger & Transactions</h2>
        <p class="text-muted mb-0">Reconcile cash drawers, credit card settlements, taxes, and service tips.</p>
    </div>
    <div>
        <a href="../cashier/billing.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-cash-register me-1"></i> Open Cashier Register
        </a>
    </div>
</div>

<!-- Payment Methods Breakdown Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($methodStats as $ms): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card" style="--kpi-accent: #6366f1; --kpi-bg: rgba(99, 102, 241, 0.12);">
                <div class="kpi-label text-uppercase"><?= str_replace('_', ' ', $ms['payment_method']) ?></div>
                <div class="kpi-value"><?= $currency . number_format((float)$ms['total_amount'], 2) ?></div>
                <small class="text-muted"><?= $ms['count'] ?> settled payments</small>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Order #</th>
                    <th>Method</th>
                    <th>Subtotal / Tax</th>
                    <th>Tip</th>
                    <th>Total Settled</th>
                    <th>Cashier</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No transactions recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-primary font-monospace"><?= htmlspecialchars($p['invoice_number']) ?></span>
                            </td>
                            <td>
                                <span class="text-light fw-semibold"><?= htmlspecialchars($p['order_number']) ?></span>
                                <small class="text-muted d-block text-uppercase"><?= htmlspecialchars($p['order_type']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-light text-uppercase" style="font-size:0.7rem;">
                                    <i class="fa-regular fa-credit-card me-1 text-primary"></i> <?= str_replace('_', ' ', $p['payment_method']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-light"><?= $currency . number_format((float)$p['amount'], 2) ?></div>
                                <small class="text-muted">+<?= $currency . number_format((float)$p['tax'], 2) ?> tax</small>
                            </td>
                            <td>
                                <span class="text-success fw-semibold"><?= (float)$p['tip'] > 0 ? '+' . $currency . number_format((float)$p['tip'], 2) : '—' ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-white fs-6"><?= $currency . number_format((float)$p['total_paid'], 2) ?></div>
                                <span class="badge-status badge-ready" style="font-size:0.65rem;">Paid</span>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($p['cashier_name'] ?? 'Desk') ?></small>
                            </td>
                            <td>
                                <div class="text-light small"><?= date('g:i A', strtotime($p['created_at'])) ?></div>
                                <small class="text-subtle"><?= date('M j, Y', strtotime($p['created_at'])) ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
