<?php
/**
 * Restaurant Management System
 * Cashier: Billing & Invoice Checkout Station
 */

declare(strict_types=1);

$pageTitle = 'Billing & Checkout Terminal';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'cashier']);

$currency = getSetting('currency_symbol', '$');

// Handle Payment Settle POST
$receiptData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $method = $_POST['payment_method'] ?? 'cash';
        $tip = (float)($_POST['tip_amount'] ?? 0);
        $userId = currentUserId();

        $order = fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if ($order && $order['status'] !== 'completed') {
            $subtotal = (float)$order['subtotal'];
            $tax = (float)$order['tax_amount'];
            $discount = (float)$order['discount_amount'];
            $finalBill = (float)$order['final_amount'] + $tip;

            $invNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            // Insert payment
            insertAndGetId(
                "INSERT INTO payments (invoice_number, order_id, payment_method, amount, tax, discount, tip, total_paid, payment_status, processed_by) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)",
                [$invNumber, $orderId, $method, $subtotal, $tax, $discount, $tip, $finalBill, $userId]
            );

            // Update order to completed
            executeQuery("UPDATE orders SET status = 'completed', updated_at = NOW() WHERE id = ?", [$orderId]);
            executeQuery("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'completed', 'Bill settled and receipt issued', ?)", [$orderId, $userId]);

            // Release table
            if (!empty($order['table_id'])) {
                executeQuery("UPDATE dining_tables SET status = 'available' WHERE id = ?", [$order['table_id']]);
            }

            logAudit($userId, 'SETTLE_PAYMENT', 'payments', $orderId, null, ['invoice_number' => $invNumber, 'amount' => $finalBill, 'method' => $method]);

            setFlash('success', "Invoice #$invNumber successfully settled!");

            // Prepare receipt view
            $items = fetchAll("SELECT oi.*, m.name as item_name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = ?", [$orderId]);
            $receiptData = [
                'invoice_number' => $invNumber,
                'order_number' => $order['order_number'],
                'items' => $items,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'tip' => $tip,
                'total' => $finalBill,
                'method' => $method,
                'date' => date('Y-m-d H:i:s')
            ];
        }
    }
}

// Fetch Unsettled Orders awaiting billing (status: ready, served, preparing)
$unsettledOrders = fetchAll(
    "SELECT o.*, t.table_number, u.full_name as waiter_name,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
     FROM orders o
     LEFT JOIN dining_tables t ON o.table_id = t.id
     LEFT JOIN users u ON o.waiter_id = u.id
     WHERE o.status IN ('served', 'ready', 'preparing', 'accepted', 'pending')
     ORDER BY (CASE WHEN o.status = 'served' THEN 0 ELSE 1 END), o.created_at ASC"
);

// Determine active selected order
$selectedOrderId = (int)($_GET['order_id'] ?? ($unsettledOrders[0]['id'] ?? 0));
if (isset($_GET['table_id'])) {
    $tblMatch = fetchOne("SELECT id FROM orders WHERE table_id = ? AND status != 'completed' ORDER BY id DESC LIMIT 1", [(int)$_GET['table_id']]);
    if ($tblMatch) $selectedOrderId = (int)$tblMatch['id'];
}

$activeOrder = null;
$activeItems = [];
if ($selectedOrderId > 0) {
    $activeOrder = fetchOne(
        "SELECT o.*, t.table_number, t.floor_location, u.full_name as waiter_name
         FROM orders o
         LEFT JOIN dining_tables t ON o.table_id = t.id
         LEFT JOIN users u ON o.waiter_id = u.id
         WHERE o.id = ?",
        [$selectedOrderId]
    );

    if ($activeOrder) {
        $activeItems = fetchAll(
            "SELECT oi.*, m.name as item_name, m.price 
             FROM order_items oi 
             JOIN menu_items m ON oi.menu_item_id = m.id 
             WHERE oi.order_id = ?",
            [$selectedOrderId]
        );
    }
}

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Cashier Billing & Settle Terminal</h2>
        <p class="text-muted mb-0">Select an open dining ticket, apply tips, tender customer payment and issue receipts.</p>
    </div>
    <div>
        <a href="payments.php" class="btn btn-secondary-saas">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> Payment Ledger
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Unsettled Open Orders -->
    <div class="col-lg-5">
        <div class="saas-card h-100">
            <div class="saas-card-header">
                <div>
                    <h5 class="saas-card-title mb-0"><i class="fa-solid fa-receipt text-warning me-2"></i> Unsettled Tables & Checks</h5>
                    <small class="text-muted">Awaiting final checkout</small>
                </div>
                <span class="badge bg-warning text-dark fw-bold"><?= count($unsettledOrders) ?> Open</span>
            </div>
            <div class="saas-card-body p-0" style="max-height: 550px; overflow-y: auto;">
                <?php if (empty($unsettledOrders)): ?>
                    <div class="p-4 text-center text-muted small">All dining checks have been settled!</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($unsettledOrders as $uo): ?>
                            <a href="billing.php?order_id=<?= $uo['id'] ?>" class="list-group-item list-group-item-action bg-transparent border-secondary border-opacity-25 px-3 py-3 <?= $selectedOrderId === $uo['id'] ? 'bg-dark bg-opacity-75 border-start border-4 border-primary' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-light">
                                        <span class="text-primary"><?= htmlspecialchars($uo['order_number']) ?></span>
                                        <span class="badge bg-dark border border-secondary border-opacity-25 text-light ms-1">
                                            <?= htmlspecialchars($uo['table_number'] ?? ucfirst($uo['order_type'])) ?>
                                        </span>
                                    </div>
                                    <span class="fw-bold text-white"><?= $currency . number_format((float)$uo['final_amount'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                    <span><?= $uo['item_count'] ?> items &bull; Server: <?= htmlspecialchars($uo['waiter_name'] ?? 'Counter') ?></span>
                                    <span class="badge-status badge-<?= htmlspecialchars($uo['status']) ?>"><?= ucfirst($uo['status']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Checkout & Bill Calculation -->
    <div class="col-lg-7">
        <?php if (!$activeOrder): ?>
            <div class="saas-card p-5 text-center text-muted">
                <i class="fa-solid fa-cash-register fa-3x mb-3 text-secondary opacity-50"></i>
                <h5>No Order Selected</h5>
                <p>Select an unsettled check on the left to process checkout.</p>
            </div>
        <?php else: ?>
            <div class="saas-card">
                <div class="saas-card-header">
                    <div>
                        <h5 class="saas-card-title mb-0">Check Details: <?= htmlspecialchars($activeOrder['order_number']) ?></h5>
                        <small class="text-muted">Table: <?= htmlspecialchars($activeOrder['table_number'] ?? ucfirst($activeOrder['order_type'])) ?></small>
                    </div>
                    <span class="badge-status badge-<?= htmlspecialchars($activeOrder['status']) ?>">
                        <?= ucfirst($activeOrder['status']) ?>
                    </span>
                </div>

                <div class="saas-card-body p-4">
                    <!-- Line Items Table -->
                    <div class="table-responsive mb-3">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Dish</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-light"><?= htmlspecialchars($item['item_name']) ?></div>
                                            <?php if ($item['special_instructions']): ?>
                                                <small class="text-warning fst-italic"><?= htmlspecialchars($item['special_instructions']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-light"><?= $item['quantity'] ?></td>
                                        <td class="text-end text-muted"><?= $currency . number_format((float)$item['unit_price'], 2) ?></td>
                                        <td class="text-end fw-bold text-primary"><?= $currency . number_format((float)$item['subtotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payment Tender Form -->
                    <form action="billing.php?order_id=<?= $activeOrder['id'] ?>" method="POST" id="checkoutForm">
                        <?= csrfField() ?>
                        <input type="hidden" name="action_type" value="settle_bill">
                        <input type="hidden" name="order_id" value="<?= $activeOrder['id'] ?>">

                        <!-- Tip Selector Pills -->
                        <div class="mb-3">
                            <label class="form-label-saas">Gratuity / Tip</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-secondary-saas tip-btn active" data-tip="0">No Tip</button>
                                <button type="button" class="btn btn-sm btn-secondary-saas tip-btn" data-tip="10">10%</button>
                                <button type="button" class="btn btn-sm btn-secondary-saas tip-btn" data-tip="15">15%</button>
                                <button type="button" class="btn btn-sm btn-secondary-saas tip-btn" data-tip="20">20%</button>
                                <input type="number" step="0.5" id="customTipInput" name="tip_amount" class="form-control form-control-sm form-control-saas" style="max-width: 120px;" placeholder="Custom $" value="0.00">
                            </div>
                        </div>

                        <!-- Tender Method -->
                        <div class="mb-4">
                            <label class="form-label-saas">Tender Method</label>
                            <div class="row g-2">
                                <div class="col-3">
                                    <label class="w-100">
                                        <input type="radio" name="payment_method" value="cash" class="btn-check" checked>
                                        <span class="btn btn-secondary-saas w-100 py-2 text-center d-block">
                                            <i class="fa-solid fa-money-bill-wave d-block mb-1 text-success"></i> Cash
                                        </span>
                                    </label>
                                </div>
                                <div class="col-3">
                                    <label class="w-100">
                                        <input type="radio" name="payment_method" value="credit_card" class="btn-check">
                                        <span class="btn btn-secondary-saas w-100 py-2 text-center d-block">
                                            <i class="fa-regular fa-credit-card d-block mb-1 text-primary"></i> Card
                                        </span>
                                    </label>
                                </div>
                                <div class="col-3">
                                    <label class="w-100">
                                        <input type="radio" name="payment_method" value="upi" class="btn-check">
                                        <span class="btn btn-secondary-saas w-100 py-2 text-center d-block">
                                            <i class="fa-solid fa-qrcode d-block mb-1 text-warning"></i> UPI / QR
                                        </span>
                                    </label>
                                </div>
                                <div class="col-3">
                                    <label class="w-100">
                                        <input type="radio" name="payment_method" value="online" class="btn-check">
                                        <span class="btn btn-secondary-saas w-100 py-2 text-center d-block">
                                            <i class="fa-solid fa-globe d-block mb-1 text-info"></i> Online
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Breakdown Box -->
                        <div class="p-3 rounded-3 mb-4" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Food & Beverage Subtotal</span>
                                <span class="text-light"><?= $currency . number_format((float)$activeOrder['subtotal'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Sales Tax (8.5%)</span>
                                <span class="text-light"><?= $currency . number_format((float)$activeOrder['tax_amount'], 2) ?></span>
                            </div>
                            <?php if ((float)$activeOrder['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-1 small text-success">
                                    <span>Discount (Coupon: <?= htmlspecialchars($activeOrder['coupon_code'] ?? '') ?>)</span>
                                    <span>-<?= $currency . number_format((float)$activeOrder['discount_amount'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-1 small text-warning">
                                <span>Gratuity / Tip</span>
                                <span id="billTipDisplay"><?= $currency ?>0.00</span>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top border-secondary border-opacity-25 fw-bold text-white fs-4">
                                <span>Amount Due</span>
                                <span id="billGrandTotal" class="text-primary" data-base="<?= (float)$activeOrder['final_amount'] ?>">
                                    <?= $currency . number_format((float)$activeOrder['final_amount'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-saas w-100 py-3 fs-5 fw-bold" onclick="return confirm('Complete checkout and tender payment?');">
                            <i class="fa-solid fa-check-circle me-2"></i> Settle Bill & Issue Receipt
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Printable Receipt -->
<?php if ($receiptData): ?>
    <div class="modal fade show" id="receiptModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.8);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content text-dark" style="background: #ffffff; font-family: 'JetBrains Mono', monospace;">
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <h4 class="fw-bold mb-1">GOURMET BISTRO</h4>
                        <div class="small">742 Evergreen Terrace, Downtown Food District</div>
                        <div class="small">Tel: +1 (555) 234-5678</div>
                        <div class="border-top border-bottom border-dark py-1 my-2 small">
                            <div>INVOICE: <?= htmlspecialchars($receiptData['invoice_number']) ?></div>
                            <div>ORDER: <?= htmlspecialchars($receiptData['order_number']) ?> &bull; <?= $receiptData['date'] ?></div>
                        </div>
                    </div>

                    <table class="w-100 mb-3 small">
                        <?php foreach ($receiptData['items'] as $it): ?>
                            <tr>
                                <td><?= $it['quantity'] ?>x <?= htmlspecialchars($it['item_name']) ?></td>
                                <td class="text-end">$<?= number_format((float)$it['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                    <div class="border-top border-dark pt-2 small">
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span>$<?= number_format((float)$receiptData['subtotal'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tax (8.5%):</span>
                            <span>$<?= number_format((float)$receiptData['tax'], 2) ?></span>
                        </div>
                        <?php if ($receiptData['discount'] > 0): ?>
                            <div class="d-flex justify-content-between text-success">
                                <span>Discount:</span>
                                <span>-$<?= number_format((float)$receiptData['discount'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <span>Tip:</span>
                            <span>$<?= number_format((float)$receiptData['tip'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold fs-6 border-top border-dark mt-1 pt-1">
                            <span>TOTAL PAID:</span>
                            <span>$<?= number_format((float)$receiptData['total'], 2) ?></span>
                        </div>
                        <div class="text-center mt-2 small text-uppercase">Payment: <?= $receiptData['method'] ?></div>
                    </div>

                    <div class="text-center mt-4 pt-2 border-top border-dark small">
                        <p class="mb-0">Thank you for dining with us!</p>
                        <p class="mb-0">Please visit again soon.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-secondary w-50" onclick="document.getElementById('receiptModal').remove()">Close</button>
                    <button type="button" class="btn btn-dark w-50" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Tip Calculator logic
const baseAmountEl = document.getElementById('billGrandTotal');
const tipDisplay = document.getElementById('billTipDisplay');
const customTipInput = document.getElementById('customTipInput');

if (baseAmountEl && customTipInput) {
    const base = parseFloat(baseAmountEl.getAttribute('data-base'));

    document.querySelectorAll('.tip-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tip-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const pct = parseFloat(this.getAttribute('data-tip'));
            const calculatedTip = (base * pct) / 100.0;
            customTipInput.value = calculatedTip.toFixed(2);
            updateTotals(calculatedTip);
        });
    });

    customTipInput.addEventListener('input', function() {
        const val = parseFloat(this.value) || 0;
        updateTotals(val);
    });

    function updateTotals(tip) {
        if (tipDisplay) tipDisplay.textContent = '$' + tip.toFixed(2);
        const grand = base + tip;
        baseAmountEl.textContent = '$' + grand.toFixed(2);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
