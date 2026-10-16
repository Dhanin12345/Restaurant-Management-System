<?php
/**
 * Restaurant Management System
 * Customer: Digital Culinary Menu & Online Ordering
 */

declare(strict_types=1);

$pageTitle = 'Digital Menu & Online Ordering';
require_once __DIR__ . '/../includes/header.php';

$currency = getSetting('currency_symbol', '$');
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
    <!-- Main Food Menu Area -->
    <div class="col-lg-8">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <h2 class="h4 fw-bold text-white mb-0">Our Digital Culinary Menu</h2>
                <small class="text-muted">Hand-crafted seasonal dining. Select dishes to build your ticket.</small>
            </div>
            <div class="input-group" style="max-width: 250px;">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="custMenuSearch" class="form-control form-control-sm form-control-saas" placeholder="Search dish...">
            </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="d-flex gap-2 mb-3 overflow-auto pb-2">
            <button class="btn btn-sm btn-primary-saas text-nowrap cust-cat-btn" data-cat="all">All Dishes</button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-sm btn-secondary-saas text-nowrap cust-cat-btn" data-cat="<?= $cat['id'] ?>">
                    <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?> me-1"></i> <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Dietary Checkboxes -->
        <div class="d-flex gap-3 mb-4 p-2 rounded-3" style="background: var(--bg-surface); border: 1px solid var(--border-color); font-size: 0.8rem;">
            <span class="text-muted fw-semibold">Dietary Preference:</span>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" id="filterVeg">
                <label class="form-check-label text-light" for="filterVeg">🌱 Vegetarian</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" id="filterVegan">
                <label class="form-check-label text-light" for="filterVegan">🌿 100% Vegan</label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" id="filterGf">
                <label class="form-check-label text-light" for="filterGf">🌾 Gluten-Free</label>
            </div>
        </div>

        <!-- Food Cards Grid -->
        <div class="row g-3" id="custFoodGrid">
            <?php foreach ($items as $item): ?>
                <div class="col-sm-6 col-md-6 food-col" 
                     data-cat="<?= $item['category_id'] ?>" 
                     data-name="<?= strtolower($item['name']) ?>"
                     data-veg="<?= $item['is_vegetarian'] ?>"
                     data-vegan="<?= $item['is_vegan'] ?>"
                     data-gf="<?= $item['is_gluten_free'] ?>">
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

                            <h3 class="h5 fw-bold text-white mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="food-card-desc mb-2"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                            
                            <div class="d-flex gap-1 mb-2">
                                <?php if ($item['is_vegetarian']): ?><span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 fw-bold">Vegetarian</span><?php endif; ?>
                                <?php if ($item['is_vegan']): ?><span class="badge bg-success text-white fw-bold">Vegan</span><?php endif; ?>
                                <?php if ($item['is_gluten_free']): ?><span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25 fw-bold">Gluten-Free</span><?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-10">
                            <span class="text-light small fw-semibold"><i class="fa-regular fa-clock me-1 text-warning"></i> <?= $item['preparation_time'] ?>m prep</span>
                            <button class="btn btn-sm btn-primary-saas px-3" onclick="posCart.addItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['price'] ?>)">
                                <i class="fa-solid fa-plus me-1"></i> Add to Order
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right: Customer Cart Drawer -->
    <div class="col-lg-4">
        <div class="saas-card position-sticky" style="top: 85px;">
            <div class="saas-card-header">
                <div>
                    <h5 class="saas-card-title mb-0"><i class="fa-solid fa-bag-shopping text-primary me-2"></i> Your Dining Bag</h5>
                    <small class="text-muted">Takeaway or Table Delivery</small>
                </div>
                <span class="badge bg-primary text-white" id="cartCountBadge">0</span>
            </div>
            <div class="saas-card-body p-3">
                <div class="mb-3">
                    <label class="form-label-saas">Order Type</label>
                    <select id="orderTypeSelect" class="form-select form-control-saas form-select-sm">
                        <option value="dine_in">Dine-In (At Restaurant)</option>
                        <option value="takeaway" selected>Takeaway / Pickup</option>
                        <option value="delivery">Doorstep Delivery</option>
                    </select>
                </div>

                <div id="cartItemsContainer" style="max-height: 250px; overflow-y: auto;">
                    <!-- Rendered via assets/js/orders.js -->
                </div>

                <div class="input-group input-group-sm my-3">
                    <input type="text" id="couponInput" class="form-control form-control-saas" placeholder="Coupon (e.g. WELCOME10)">
                    <button class="btn btn-secondary-saas" type="button" id="applyCouponBtn">Apply</button>
                </div>

                <div class="mb-3">
                    <input type="text" id="orderNotesInput" class="form-control form-control-saas form-control-sm" placeholder="Delivery instructions or allergy notes">
                </div>

                <div class="p-3 rounded-3 mb-3" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Subtotal</span>
                        <span id="cartSubtotal" class="text-light">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Tax (8.5%)</span>
                        <span id="cartTax" class="text-light">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small text-success">
                        <span>Promo Discount</span>
                        <span id="cartDiscount">-$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top border-secondary border-opacity-25 fw-bold text-white fs-5">
                        <span>Total Due</span>
                        <span id="cartTotal" class="text-primary">$0.00</span>
                    </div>
                </div>

                <button class="btn btn-primary-saas w-100 py-2 fs-6 fw-bold" id="cartSubmitBtn" disabled>
                    <i class="fa-solid fa-arrow-right me-2"></i> Place Online Order
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/orders.js"></script>
<script>
// Filter handlers
document.querySelectorAll('.cust-cat-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.cust-cat-btn').forEach(b => {
            b.classList.remove('btn-primary-saas');
            b.classList.add('btn-secondary-saas');
        });
        this.classList.remove('btn-secondary-saas');
        this.classList.add('btn-primary-saas');
        filterGrid();
    });
});

document.getElementById('custMenuSearch').addEventListener('input', filterGrid);
document.getElementById('filterVeg').addEventListener('change', filterGrid);
document.getElementById('filterVegan').addEventListener('change', filterGrid);
document.getElementById('filterGf').addEventListener('change', filterGrid);

function filterGrid() {
    const activeCat = document.querySelector('.cust-cat-btn.btn-primary-saas').getAttribute('data-cat');
    const search = document.getElementById('custMenuSearch').value.toLowerCase();
    const reqVeg = document.getElementById('filterVeg').checked;
    const reqVegan = document.getElementById('filterVegan').checked;
    const reqGf = document.getElementById('filterGf').checked;

    document.querySelectorAll('.food-col').forEach(card => {
        const cat = card.getAttribute('data-cat');
        const name = card.getAttribute('data-name');
        const isVeg = card.getAttribute('data-veg') === '1';
        const isVegan = card.getAttribute('data-vegan') === '1';
        const isGf = card.getAttribute('data-gf') === '1';

        const matchCat = (activeCat === 'all' || activeCat === cat);
        const matchSearch = name.includes(search);
        const matchVeg = !reqVeg || isVeg;
        const matchVegan = !reqVegan || isVegan;
        const matchGf = !reqGf || isGf;

        if (matchCat && matchSearch && matchVeg && matchVegan && matchGf) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
