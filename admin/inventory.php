<?php
/**
 * Restaurant Management System
 * Admin: Real-Time Inventory, Recipe BOM & Automated Reorder Alerts
 */

declare(strict_types=1);

$pageTitle = 'Inventory & Recipe BOM';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager']);

$currency = getSetting('currency_symbol', '$');

// Handle Restock Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $action = $_POST['action_type'];
        if ($action === 'restock') {
            $ingId = (int)($_POST['ingredient_id'] ?? 0);
            $qty = (float)($_POST['quantity'] ?? 0);
            $cost = (float)($_POST['unit_cost'] ?? 0);
            $notes = trim($_POST['notes'] ?? 'Supplier shipment');

            if ($ingId > 0 && $qty > 0) {
                executeQuery("UPDATE ingredients SET current_stock = current_stock + ?, unit_cost = ?, last_restocked_at = NOW() WHERE id = ?", [$qty, $cost, $ingId]);
                executeQuery("INSERT INTO stock_transactions (ingredient_id, transaction_type, quantity, notes, created_by) VALUES (?, 'purchase', ?, ?, ?)", [$ingId, $qty, $notes, currentUserId()]);
                logAudit(currentUserId(), 'RESTOCK_PURCHASE', 'ingredients', $ingId, null, ['quantity_added' => $qty, 'unit_cost' => $cost]);
                setFlash('success', "Stock replenished successfully.");
                header("Location: inventory.php");
                exit;
            }
        }
    }
}

// Fetch all ingredients
$ingredients = fetchAll(
    "SELECT *, (CASE WHEN current_stock <= reorder_level THEN 1 ELSE 0 END) as is_low_stock 
     FROM ingredients 
     ORDER BY is_low_stock DESC, name ASC"
);

// Fetch Recipe BOM data grouped by menu items
$menuRecipes = fetchAll(
    "SELECT m.id as menu_item_id, m.name as item_name, c.name as category_name,
            i.name as ingredient_name, i.unit, i.current_stock, mi.quantity_required,
            FLOOR(i.current_stock / mi.quantity_required) as possible_servings
     FROM menu_ingredients mi
     JOIN menu_items m ON mi.menu_item_id = m.id
     JOIN categories c ON m.category_id = c.id
     JOIN ingredients i ON mi.ingredient_id = i.id
     ORDER BY m.name ASC, i.name ASC"
);

$recipesByDish = [];
foreach ($menuRecipes as $mr) {
    $recipesByDish[$mr['item_name']]['category'] = $mr['category_name'];
    $recipesByDish[$mr['item_name']]['ingredients'][] = $mr;
}

// Recent stock transactions
$recentTxns = fetchAll(
    "SELECT st.*, i.name as ingredient_name, i.unit, u.full_name as user_name
     FROM stock_transactions st
     JOIN ingredients i ON st.ingredient_id = i.id
     LEFT JOIN users u ON st.created_by = u.id
     ORDER BY st.created_at DESC LIMIT 10"
);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Automated Recipe Inventory Control</h2>
        <p class="text-muted mb-0">Dynamic ingredient consumption per kitchen ticket with automated low-stock reorder thresholds.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary-saas" data-bs-toggle="modal" data-bs-target="#restockModal">
            <i class="fa-solid fa-cart-flatbed me-1"></i> Log Supplier Restock
        </button>
    </div>
</div>

<!-- Stock Health Summary Grid -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-card" style="--kpi-accent: #10b981; --kpi-bg: rgba(16, 185, 129, 0.12);">
            <div class="kpi-label">Active Stock Items</div>
            <div class="kpi-value"><?= count($ingredients) ?></div>
            <small class="text-muted">Raw kitchen commodities</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card" style="--kpi-accent: #ef4444; --kpi-bg: rgba(239, 68, 68, 0.12);">
            <div class="kpi-label">Below Reorder Level</div>
            <div class="kpi-value text-danger">
                <?= count(array_filter($ingredients, fn($i) => $i['is_low_stock'] == 1)) ?> ⚠
            </div>
            <small class="text-danger">Action required</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-card" style="--kpi-accent: #6366f1; --kpi-bg: rgba(99, 102, 241, 0.12);">
            <div class="kpi-label">Mapped Recipes (BOM)</div>
            <div class="kpi-value"><?= count($recipesByDish) ?> Dishes</div>
            <small class="text-muted">Auto-deducted on kitchen ticket</small>
        </div>
    </div>
</div>

<!-- Tab Navigation: Stock Inventory vs Recipe Explorer vs Ledger -->
<ul class="nav nav-tabs border-secondary border-opacity-25 mb-4" id="invTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active text-light fw-bold" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stockTab">
            <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i> Raw Ingredients (<?= count($ingredients) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link text-light fw-bold" id="recipes-tab" data-bs-toggle="tab" data-bs-target="#recipesTab">
            <i class="fa-solid fa-book-bookmark me-2 text-warning"></i> Recipe Bill of Materials (BOM)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link text-light fw-bold" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledgerTab">
            <i class="fa-solid fa-clock-rotate-left me-2 text-info"></i> Stock Movement Ledger
        </button>
    </li>
</ul>

<div class="tab-content" id="invTabsContent">
    <!-- Tab 1: Stock Inventory -->
    <div class="tab-pane fade show active" id="stockTab">
        <div class="saas-card">
            <div class="saas-card-body p-0">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Current Stock</th>
                            <th>Reorder Point</th>
                            <th>Unit Cost</th>
                            <th>Health State</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $ing): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-light"><?= htmlspecialchars($ing['name']) ?></div>
                                    <small class="text-muted">Unit: <code><?= htmlspecialchars($ing['unit']) ?></code></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-white fs-6">
                                        <?= number_format((float)$ing['current_stock'], 1) ?> <small class="text-muted"><?= $ing['unit'] ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted"><?= number_format((float)$ing['reorder_level'], 1) ?> <?= $ing['unit'] ?></span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-light"><?= $currency . number_format((float)$ing['unit_cost'], 2) ?></span>
                                </td>
                                <td>
                                    <?php if ($ing['is_low_stock']): ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> LOW STOCK
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">
                                            <i class="fa-solid fa-check me-1"></i> Adequate
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-primary-saas py-1 px-2" onclick="openRestockModal(<?= $ing['id'] ?>, '<?= htmlspecialchars(addslashes($ing['name'])) ?>', <?= $ing['unit_cost'] ?>)">
                                        <i class="fa-solid fa-plus me-1"></i> Restock
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Recipe BOM Explorer -->
    <div class="tab-pane fade" id="recipesTab">
        <div class="row g-4">
            <?php foreach ($recipesByDish as $dishName => $dishData): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="saas-card h-100">
                        <div class="saas-card-header">
                            <div>
                                <h5 class="saas-card-title mb-0"><?= htmlspecialchars($dishName) ?></h5>
                                <small class="text-muted"><?= htmlspecialchars($dishData['category']) ?></small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25">
                                Recipe BOM
                            </span>
                        </div>
                        <div class="saas-card-body p-3">
                            <div class="small fw-bold text-muted text-uppercase mb-2">Automated Deduction per Order:</div>
                            <ul class="list-group list-group-flush mb-3">
                                <?php 
                                $minPossible = 999999;
                                foreach ($dishData['ingredients'] as $ing): 
                                    $minPossible = min($minPossible, (int)$ing['possible_servings']);
                                ?>
                                    <li class="list-group-item bg-transparent border-secondary border-opacity-25 px-0 py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-light fw-semibold"><?= htmlspecialchars($ing['ingredient_name']) ?></span>
                                            <div class="text-muted small">Portion: <?= $ing['quantity_required'] ?> <?= $ing['unit'] ?></div>
                                        </div>
                                        <span class="badge bg-dark border border-secondary border-opacity-25 text-light">
                                            Stock: <?= number_format((float)$ing['current_stock'], 0) ?> <?= $ing['unit'] ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="p-2 rounded bg-dark border border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Ready to prepare:</span>
                                <span class="badge bg-success text-white fw-bold"><?= $minPossible ?> servings available</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tab 3: Stock Movement Ledger -->
    <div class="tab-pane fade" id="ledgerTab">
        <div class="saas-card">
            <div class="saas-card-body p-0">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Ingredient</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference / Notes</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTxns as $txn): ?>
                            <tr>
                                <td>
                                    <span class="text-light small"><?= date('M j, g:i A', strtotime($txn['created_at'])) ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-light"><?= htmlspecialchars($txn['ingredient_name']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary border-opacity-25 text-uppercase" style="font-size:0.7rem;">
                                        <?= htmlspecialchars($txn['transaction_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold <?= (float)$txn['quantity'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= (float)$txn['quantity'] > 0 ? '+' : '' ?><?= number_format((float)$txn['quantity'], 1) ?> <?= $txn['unit'] ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($txn['notes'] ?? 'Order deduction') ?></small>
                                </td>
                                <td>
                                    <small class="text-light"><?= htmlspecialchars($txn['user_name'] ?? 'System') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Restock -->
<div class="modal fade" id="restockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-white fw-bold">Replenish Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="inventory.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="restock">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-saas">Ingredient</label>
                        <select name="ingredient_id" id="modalIngSelect" class="form-select form-control-saas" required>
                            <?php foreach ($ingredients as $ing): ?>
                                <option value="<?= $ing['id'] ?>"><?= htmlspecialchars($ing['name']) ?> (Current: <?= number_format((float)$ing['current_stock'], 1) ?> <?= $ing['unit'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">Restock Quantity</label>
                            <input type="number" step="0.1" name="quantity" class="form-control form-control-saas" placeholder="e.g. 5000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Unit Cost (<?= $currency ?>)</label>
                            <input type="number" step="0.01" name="unit_cost" id="modalUnitCost" class="form-control form-control-saas" placeholder="0.02">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-saas">Supplier Invoice / PO Reference</label>
                        <input type="text" name="notes" class="form-control form-control-saas" placeholder="PO #8942 - Organic Farms Direct">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary-saas" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-saas">Add to Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRestockModal(id, name, cost) {
    document.getElementById('modalIngSelect').value = id;
    document.getElementById('modalUnitCost').value = cost;
    new bootstrap.Modal(document.getElementById('restockModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
