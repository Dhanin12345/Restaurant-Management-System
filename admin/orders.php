<?php
/**
 * Restaurant Management System
 * Admin: Order Management Hub
 */

declare(strict_types=1);

$pageTitle = 'Order Management Hub';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager']);

$currency = getSetting('currency_symbol', '$');
$statusFilter = $_GET['status'] ?? 'all';

// Build query
$sql = "SELECT o.*, t.table_number, u.full_name as waiter_name, c.full_name as customer_name,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        LEFT JOIN dining_tables t ON o.table_id = t.id
        LEFT JOIN users u ON o.waiter_id = u.id
        LEFT JOIN users c ON o.customer_id = c.id";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " WHERE o.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY o.created_at DESC LIMIT 100";
$orders = fetchAll($sql, $params);

// Counts per status
$statusCounts = [
    'all' => (int)fetchOne("SELECT COUNT(*) as c FROM orders")['c'],
    'pending' => (int)fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'pending'")['c'],
    'preparing' => (int)fetchOne("SELECT COUNT(*) as c FROM orders WHERE status IN ('accepted', 'preparing')")['c'],
    'ready' => (int)fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'ready'")['c'],
    'served' => (int)fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'served'")['c'],
    'completed' => (int)fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'completed'")['c'],
];

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Order Operations Hub</h2>
        <p class="text-muted mb-0">Track live dining tickets, kitchen progress, billing status and guest checks.</p>
    </div>
    <div>
        <a href="../waiter/create_order.php" class="btn btn-primary-saas">
            <i class="fa-solid fa-plus-circle me-1"></i> New Order (POS)
        </a>
    </div>
</div>

<!-- Status Tabs -->
<div class="d-flex gap-2 mb-4 overflow-auto pb-2">
    <a href="orders.php?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
        All (<?= $statusCounts['all'] ?>)
    </a>
    <a href="orders.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
        Pending (<?= $statusCounts['pending'] ?>)
    </a>
    <a href="orders.php?status=preparing" class="btn btn-sm <?= $statusFilter === 'preparing' ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
        In Kitchen (<?= $statusCounts['preparing'] ?>)
    </a>
    <a href="orders.php?status=ready" class="btn btn-sm <?= $statusFilter === 'ready' ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
        Ready for Pickup (<?= $statusCounts['ready'] ?>)
    </a>
    <a href="orders.php?status=served" class="btn btn-sm <?= $statusFilter === 'served' ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
        Served (<?= $statusCounts['served'] ?>)
    </a>
    <a href="orders.php?status=completed" class="btn btn-sm <?= $statusFilter === 'completed' ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
        Completed & Paid (<?= $statusCounts['completed'] ?>)
    </a>
</div>

<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Table / Type</th>
                    <th>Staff / Guest</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Placed Time</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No orders match this filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td>
                                <a href="javascript:void(0)" onclick="viewOrderDetail(<?= $ord['id'] ?>)" class="fw-bold text-primary">
                                    <?= htmlspecialchars($ord['order_number']) ?>
                                </a>
                                <?php if ($ord['coupon_code']): ?>
                                    <span class="badge bg-dark border border-secondary border-opacity-25 text-warning ms-1" style="font-size:0.65rem;">
                                        <?= htmlspecialchars($ord['coupon_code']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ord['table_number']): ?>
                                    <span class="badge bg-dark border border-secondary border-opacity-25 text-light">
                                        <i class="fa-solid fa-chair text-primary me-1"></i> <?= htmlspecialchars($ord['table_number']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-25 text-light text-uppercase"><?= htmlspecialchars($ord['order_type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-light fw-semibold small"><?= htmlspecialchars($ord['customer_name'] ?? 'Walk-In Guest') ?></div>
                                <small class="text-muted">Waiter: <?= htmlspecialchars($ord['waiter_name'] ?? 'Counter') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-info">
                                    <?= $ord['item_count'] ?> items
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-light"><?= $currency . number_format((float)$ord['final_amount'], 2) ?></div>
                                <?php if ((float)$ord['discount_amount'] > 0): ?>
                                    <small class="text-success">-<?= $currency . number_format((float)$ord['discount_amount'], 2) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= htmlspecialchars($ord['status']) ?>">
                                    <?= ucfirst($ord['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-light small"><?= date('g:i A', strtotime($ord['created_at'])) ?></div>
                                <small class="text-muted"><?= date('M j', strtotime($ord['created_at'])) ?></small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-sm btn-secondary-saas py-1 px-2" onclick="viewOrderDetail(<?= $ord['id'] ?>)" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <?php if ($ord['status'] === 'served' || $ord['status'] === 'ready'): ?>
                                        <a href="../cashier/billing.php?order_id=<?= $ord['id'] ?>" class="btn btn-sm btn-success text-white py-1 px-2" title="Settle Bill">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Order Details & Recipe Ingredients Audit -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0" id="detailOrderTitle">Order #</h5>
                    <small class="text-muted" id="detailOrderMeta"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mb-4">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTable">
                        </tbody>
                    </table>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                            <h6 class="text-white fw-bold small mb-2"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Order Status Trail</h6>
                            <div id="detailTimeline" class="small text-muted"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Subtotal</span>
                                <span id="detailSubtotal" class="text-light"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Tax (8.5%)</span>
                                <span id="detailTax" class="text-light"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-success">
                                <span>Discount</span>
                                <span id="detailDiscount"></span>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top border-secondary border-opacity-25 fw-bold text-white">
                                <span>Grand Total</span>
                                <span id="detailTotal" class="text-primary h5 mb-0"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-secondary-saas" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
async function viewOrderDetail(orderId) {
    try {
        const res = await fetch(`../api/orders.php?action=detail&id=${orderId}`);
        const data = await res.json();
        if (data.success) {
            const o = data.order;
            document.getElementById('detailOrderTitle').textContent = `Order ${o.order_number}`;
            document.getElementById('detailOrderMeta').textContent = `Table: ${o.table_number || o.order_type.toUpperCase()} | Server: ${o.waiter_name || 'Desk'}`;

            document.getElementById('detailItemsTable').innerHTML = data.items.map(it => `
                <tr>
                    <td>
                        <div class="fw-bold text-light">${it.item_name}</div>
                        ${it.special_instructions ? `<small class="text-warning fst-italic">${it.special_instructions}</small>` : ''}
                    </td>
                    <td class="text-center fw-bold text-light">${it.quantity}</td>
                    <td class="text-end text-muted">$${parseFloat(it.unit_price).toFixed(2)}</td>
                    <td class="text-end fw-bold text-primary">$${parseFloat(it.subtotal).toFixed(2)}</td>
                </tr>
            `).join('');

            document.getElementById('detailSubtotal').textContent = `$${parseFloat(o.subtotal).toFixed(2)}`;
            document.getElementById('detailTax').textContent = `$${parseFloat(o.tax_amount).toFixed(2)}`;
            document.getElementById('detailDiscount').textContent = `-$${parseFloat(o.discount_amount).toFixed(2)}`;
            document.getElementById('detailTotal').textContent = `$${parseFloat(o.final_amount).toFixed(2)}`;

            document.getElementById('detailTimeline').innerHTML = data.history.map(h => `
                <div class="mb-1"><span class="badge bg-dark text-info text-uppercase">${h.status}</span> ${h.notes || ''} <span class="text-subtle">(${new Date(h.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})})</span></div>
            `).join('');

            new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
        }
    } catch (e) {
        alert('Could not load order details');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
