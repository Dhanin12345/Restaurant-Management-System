<?php
/**
 * Restaurant Management System
 * Waiter: High-Speed POS Order Entry Station
 */

declare(strict_types=1);

$pageTitle = 'Point of Sale (POS) Order Entry';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'waiter']);

$currency = getSetting('currency_symbol', '$');
$preselectedTableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;

// Fetch Available Tables
$tables = fetchAll("SELECT * FROM dining_tables WHERE status != 'maintenance' ORDER BY table_number ASC");

// Fetch Categories
$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC");

// Fetch Available Menu Items
$items = fetchAll(
    "SELECT m.*, c.name as category_name, c.icon as category_icon 
     FROM menu_items m 
     JOIN categories c ON m.category_id = c.id 
     WHERE m.is_available = 1 
     ORDER BY c.display_order ASC, m.name ASC"
);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-4">
    <!-- Left: Menu Catalog Grid -->
    <div class="col-lg-8">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold text-white mb-0">POS Terminal & Order Builder</h2>
                <small class="text-muted">Tap dishes to add to the table check. Ingredients auto-deduct from stock.</small>
            </div>
            <div class="input-group" style="max-width: 250px;">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="menuSearch" class="form-control form-control-sm form-control-saas" placeholder="Search menu...">
            </div>
        </div>

        <!-- Category Selector Pills -->
        <div class="d-flex gap-2 mb-3 overflow-auto pb-2">
            <button class="btn btn-sm btn-primary-saas text-nowrap cat-filter-btn" data-cat="all">
                All Dishes
            </button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-sm btn-secondary-saas text-nowrap cat-filter-btn" data-cat="<?= $cat['id'] ?>">
                    <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?> me-1"></i> <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Food Item Grid -->
        <div class="row g-3" id="foodGrid">
            <?php foreach ($items as $item): ?>
                <div class="col-sm-6 col-md-4 food-card-col" data-cat="<?= $item['category_id'] ?>" data-name="<?= strtolower($item['name']) ?>">
                    <div class="saas-card h-100 p-3 d-flex flex-column justify-content-between" style="cursor: pointer;" 
                         onclick="posCart.addItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['price'] ?>)">
                        <div>
                            <div class="food-card-img-wrap" style="height: 140px;">
                                <img src="../assets/images/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="food-card-img"
                                     onerror="this.src='../assets/images/default_food.png'">
                                <span class="price-floating-badge" style="font-size:0.9rem; padding: 0.2rem 0.6rem;"><?= $currency . number_format((float)$item['price'], 2) ?></span>
                            </div>

                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="dish-category-badge" style="font-size:0.65rem; padding: 0.2rem 0.5rem;">
                                    <i class="fa-solid <?= htmlspecialchars($item['category_icon']) ?> me-1"></i> <?= htmlspecialchars($item['category_name']) ?>
                                </span>
                            </div>
                            <h3 class="h6 fw-bold text-white mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="food-card-desc mb-2 text-truncate"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10">
                            <span class="text-light small"><i class="fa-regular fa-clock me-1 text-warning"></i> <?= $item['preparation_time'] ?>m</span>
                            <button class="btn btn-sm btn-primary-saas py-1 px-3" style="font-size:0.78rem;">
                                <i class="fa-solid fa-plus me-1"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right: Sticky Order Ticket Drawer -->
    <div class="col-lg-4">
        <div class="saas-card position-sticky" style="top: 85px;">
            <div class="saas-card-header">
                <div>
                    <h5 class="saas-card-title mb-0"><i class="fa-solid fa-receipt text-primary me-2"></i> Active Ticket</h5>
                    <small class="text-muted">Table Check</small>
                </div>
                <span class="badge bg-primary text-white" id="cartCountBadge">0</span>
            </div>

            <div class="saas-card-body p-3">
                <!-- Dining Type & Table Selector -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label-saas">Order Type</label>
                        <select id="orderTypeSelect" class="form-select form-control-saas form-select-sm">
                            <option value="dine_in">Dine-In</option>
                            <option value="takeaway">Takeaway</option>
                            <option value="delivery">Delivery</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label-saas">Dining Table</label>
                        <select id="orderTableSelect" class="form-select form-control-saas form-select-sm">
                            <option value="">-- No Table --</option>
                            <?php foreach ($tables as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $preselectedTableId === $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['table_number']) ?> (<?= $t['status'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Cart Items Scrollable Box -->
                <div id="cartItemsContainer" style="max-height: 280px; overflow-y: auto;">
                    <!-- Rendered via assets/js/orders.js -->
                </div>

                <!-- Coupon Box -->
                <div class="input-group input-group-sm my-3">
                    <input type="text" id="couponInput" class="form-control form-control-saas" placeholder="Coupon (e.g. WELCOME10)">
                    <button class="btn btn-secondary-saas" type="button" id="applyCouponBtn">Apply</button>
                </div>

                <!-- Notes for Chef -->
                <div class="mb-3">
                    <input type="text" id="orderNotesInput" class="form-control form-control-saas form-control-sm" placeholder="Chef notes (e.g. extra crispy, allergy)">
                </div>

                <!-- Ticket Financial Totals -->
                <div class="p-3 rounded-3 mb-3" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Subtotal</span>
                        <span id="cartSubtotal" class="text-light">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Sales Tax (8.5%)</span>
                        <span id="cartTax" class="text-light">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-success">
                        <span>Discount</span>
                        <span id="cartDiscount">-$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top border-secondary border-opacity-25 fw-bold text-white fs-5">
                        <span>Total</span>
                        <span id="cartTotal" class="text-primary">$0.00</span>
                    </div>
                </div>

                <button class="btn btn-primary-saas w-100 py-2 fs-6" id="cartSubmitBtn" disabled>
                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Kitchen Ticket
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/orders.js"></script>
<script>
// Category Filter Pills & Search
document.querySelectorAll('.cat-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.cat-filter-btn').forEach(b => {
            b.classList.remove('btn-primary-saas');
            b.classList.add('btn-secondary-saas');
        });
        this.classList.remove('btn-secondary-saas');
        this.classList.add('btn-primary-saas');

        const cat = this.getAttribute('data-cat');
        document.querySelectorAll('.food-card-col').forEach(card => {
            if (cat === 'all' || card.getAttribute('data-cat') === cat) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

document.getElementById('menuSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.food-card-col').forEach(card => {
        const name = card.getAttribute('data-name');
        card.style.display = name.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
