<?php
/**
 * Restaurant Management System
 * Customer: Table QR Code Digital Menu & Direct Ordering
 */

declare(strict_types=1);

$pageTitle = 'QR Table Menu';
require_once __DIR__ . '/../includes/header.php';

$currency = getSetting('currency_symbol', '$');
$tableParam = $_GET['table'] ?? $_GET['code'] ?? 'T-01';

// Lookup dining table
$table = fetchOne("SELECT * FROM dining_tables WHERE table_number = ? OR qr_code = ? LIMIT 1", [$tableParam, $tableParam]);
if (!$table) {
    // Default fallback to first table
    $table = fetchOne("SELECT * FROM dining_tables ORDER BY id ASC LIMIT 1");
}

$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
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
    <div class="col-lg-8">
        <!-- Table Banner -->
        <div class="p-3 rounded-4 mb-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, rgba(249, 115, 22, 0.15) 0%, rgba(99, 102, 241, 0.15) 100%); border: 1px solid rgba(249, 115, 22, 0.3);">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-icon" style="width: 48px; height: 48px; font-size: 1.4rem;">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <div>
                    <span class="badge bg-primary text-white text-uppercase">QR Dine-In Active</span>
                    <h4 class="text-white fw-bold mb-0">Seated at Table <?= htmlspecialchars($table['table_number']) ?></h4>
                    <small class="text-muted"><?= htmlspecialchars($table['floor_location']) ?> &bull; <?= $table['seating_capacity'] ?> Pax Seating</small>
                </div>
            </div>
            <div class="text-end d-none d-sm-block">
                <span class="badge bg-dark border border-secondary border-opacity-25 text-success py-2 px-3">
                    <i class="fa-solid fa-signal me-1"></i> Connected to Kitchen
                </span>
            </div>
        </div>

        <!-- Category Selector -->
        <div class="d-flex gap-2 mb-3 overflow-auto pb-2">
            <button class="btn btn-sm btn-primary-saas text-nowrap qr-cat-btn" data-cat="all">All Dishes</button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-sm btn-secondary-saas text-nowrap qr-cat-btn" data-cat="<?= $cat['id'] ?>">
                    <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?> me-1"></i> <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Food Items Grid -->
        <div class="row g-3" id="qrFoodGrid">
            <?php foreach ($items as $item): ?>
                <div class="col-sm-6 col-md-6 qr-food-card" data-cat="<?= $item['category_id'] ?>">
                    <div class="saas-card h-100 p-3 d-flex flex-column justify-content-between">
                        <div>
                            <!-- Mouth-Watering Dish Photo -->
                            <div class="food-card-img-wrap">
                                <img src="../assets/images/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="food-card-img"
                                     onerror="this.src='../assets/images/default_food.png'">
                                <span class="price-floating-badge"><?= $currency . number_format((float)$item['price'], 2) ?></span>
                                <span class="category-floating-badge">
                                    <i class="fa-solid <?= htmlspecialchars($item['category_icon']) ?>"></i> <?= htmlspecialchars($item['category_name']) ?>
                                </span>
                            </div>

                            <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                            <p class="food-card-desc mb-2"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-10">
                            <span class="text-light small fw-semibold"><i class="fa-regular fa-clock me-1 text-warning"></i> <?= $item['preparation_time'] ?>m prep</span>
                            <button class="btn btn-sm btn-primary-saas px-3" onclick="posCart.addItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['price'] ?>)">
                                <i class="fa-solid fa-plus me-1"></i> Add to Table
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right: Table Cart -->
    <div class="col-lg-4">
        <div class="saas-card position-sticky" style="top: 85px;">
            <div class="saas-card-header">
                <div>
                    <h5 class="saas-card-title mb-0"><i class="fa-solid fa-receipt text-primary me-2"></i> Table <?= htmlspecialchars($table['table_number']) ?> Check</h5>
                    <small class="text-muted">Direct to Chef Gordon</small>
                </div>
                <span class="badge bg-primary text-white" id="cartCountBadge">0</span>
            </div>
            <div class="saas-card-body p-3">
                <input type="hidden" id="orderTypeSelect" value="dine_in">
                <input type="hidden" id="orderTableSelect" value="<?= $table['id'] ?>">

                <div id="cartItemsContainer" style="max-height: 280px; overflow-y: auto;">
                    <!-- Rendered by orders.js -->
                </div>

                <div class="input-group input-group-sm my-3">
                    <input type="text" id="couponInput" class="form-control form-control-saas" placeholder="Promo code (e.g. WELCOME10)">
                    <button class="btn btn-secondary-saas" type="button" id="applyCouponBtn">Apply</button>
                </div>

                <div class="mb-3">
                    <input type="text" id="orderNotesInput" class="form-control form-control-saas form-control-sm" placeholder="Chef notes (e.g. well-done, allergy)">
                </div>

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

                <button class="btn btn-primary-saas w-100 py-3 fs-6 fw-bold" id="cartSubmitBtn" disabled>
                    <i class="fa-solid fa-fire me-2"></i> Send Ticket to Kitchen
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/orders.js"></script>
<script>
document.querySelectorAll('.qr-cat-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.qr-cat-btn').forEach(b => {
            b.classList.remove('btn-primary-saas');
            b.classList.add('btn-secondary-saas');
        });
        this.classList.remove('btn-secondary-saas');
        this.classList.add('btn-primary-saas');

        const cat = this.getAttribute('data-cat');
        document.querySelectorAll('.qr-food-card').forEach(card => {
            if (cat === 'all' || card.getAttribute('data-cat') === cat) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
