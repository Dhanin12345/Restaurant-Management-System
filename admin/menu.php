<?php
/**
 * Restaurant Management System
 * Admin: Menu Items Management & Recipe Linking
 */

declare(strict_types=1);

$pageTitle = 'Menu Management';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager']);

$currency = getSetting('currency_symbol', '$');
$categories = fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC");

// Handle Add/Edit Menu Item POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $actionType = $_POST['action_type'];

        if ($actionType === 'create') {
            $name = trim($_POST['name'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $price = (float)($_POST['price'] ?? 0);
            $costPrice = (float)($_POST['cost_price'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $prepTime = (int)($_POST['preparation_time'] ?? 15);
            $image = trim($_POST['image'] ?? 'default_food.png');
            if (empty($image)) $image = 'default_food.png';
            $isVeg = isset($_POST['is_vegetarian']) ? 1 : 0;
            $isVegan = isset($_POST['is_vegan']) ? 1 : 0;
            $isGlutenFree = isset($_POST['is_gluten_free']) ? 1 : 0;

            if (!empty($name) && $categoryId > 0 && $price > 0) {
                $newId = insertAndGetId(
                    "INSERT INTO menu_items (category_id, name, description, price, cost_price, image, preparation_time, is_vegetarian, is_vegan, is_gluten_free) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$categoryId, $name, $description, $price, $costPrice, $image, $prepTime, $isVeg, $isVegan, $isGlutenFree]
                );
                logAudit(currentUserId(), 'CREATE_MENU_ITEM', 'menu_items', $newId, null, ['name' => $name, 'price' => $price, 'image' => $image]);
                setFlash('success', "Menu item '$name' created successfully!");
                header("Location: menu.php");
                exit;
            }
        } elseif ($actionType === 'toggle_availability') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $newStatus = (int)($_POST['is_available'] ?? 1);
            executeQuery("UPDATE menu_items SET is_available = ? WHERE id = ?", [$newStatus, $itemId]);
            logAudit(currentUserId(), 'TOGGLE_MENU_AVAILABILITY', 'menu_items', $itemId, null, ['is_available' => $newStatus]);
            setFlash('info', "Item availability updated.");
            header("Location: menu.php");
            exit;
        } elseif ($actionType === 'delete') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            executeQuery("DELETE FROM menu_items WHERE id = ?", [$itemId]);
            logAudit(currentUserId(), 'DELETE_MENU_ITEM', 'menu_items', $itemId, null, null);
            setFlash('warning', "Menu item deleted.");
            header("Location: menu.php");
            exit;
        }
    }
}

// Filter by category
$filterCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sql = "SELECT m.*, c.name as category_name,
               (SELECT COUNT(*) FROM menu_ingredients WHERE menu_item_id = m.id) as ingredient_count
        FROM menu_items m
        JOIN categories c ON m.category_id = c.id";
$params = [];
if ($filterCat > 0) {
    $sql .= " WHERE m.category_id = ?";
    $params[] = $filterCat;
}
$sql .= " ORDER BY c.display_order ASC, m.name ASC";
$menuItems = fetchAll($sql, $params);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Culinary Menu Catalog</h2>
        <p class="text-muted mb-0">Manage dishes, recipes, dietary tags, preparation times and pricing.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary-saas" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fa-solid fa-plus-circle me-1"></i> Add Dish
        </button>
    </div>
</div>

<!-- Category Filters -->
<div class="d-flex gap-2 mb-4 overflow-auto pb-2">
    <a href="menu.php" class="btn btn-sm <?= $filterCat === 0 ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
        All Categories (<?= count($menuItems) ?>)
    </a>
    <?php foreach ($categories as $cat): ?>
        <a href="menu.php?cat=<?= $cat['id'] ?>" class="btn btn-sm <?= $filterCat === $cat['id'] ? 'btn-primary-saas' : 'btn-secondary-saas' ?> text-nowrap">
            <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?> me-1"></i> <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Menu Items Table -->
<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Dish & Recipe</th>
                    <th>Category</th>
                    <th>Price / Cost</th>
                    <th>Prep Time</th>
                    <th>Dietary</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menuItems)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No menu items found in this section.</td></tr>
                <?php else: ?>
                    <?php foreach ($menuItems as $m): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../assets/images/<?= htmlspecialchars($m['image'] ?: 'default_food.png') ?>" 
                                         alt="<?= htmlspecialchars($m['name']) ?>" 
                                         style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0;"
                                         onerror="this.src='../assets/images/default_food.png'">
                                    <div>
                                        <div class="fw-bold text-light"><?= htmlspecialchars($m['name']) ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 280px;"><?= htmlspecialchars($m['description'] ?? '') ?></small>
                                        <span class="badge bg-dark border border-secondary border-opacity-25 text-info mt-1" style="font-size:0.65rem;">
                                            <i class="fa-solid fa-cubes-stacked me-1"></i> <?= $m['ingredient_count'] ?> Recipe Ingredients
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-25 text-light"><?= htmlspecialchars($m['category_name']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?= $currency . number_format((float)$m['price'], 2) ?></div>
                                <small class="text-muted">Cost: <?= $currency . number_format((float)$m['cost_price'], 2) ?></small>
                            </td>
                            <td>
                                <span class="text-muted small"><i class="fa-regular fa-clock me-1 text-warning"></i> <?= $m['preparation_time'] ?> min</span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($m['is_vegetarian']): ?><span class="badge bg-success" title="Vegetarian">VEG</span><?php endif; ?>
                                    <?php if ($m['is_vegan']): ?><span class="badge bg-success bg-opacity-50" title="Vegan">V</span><?php endif; ?>
                                    <?php if ($m['is_gluten_free']): ?><span class="badge bg-info text-dark" title="Gluten-Free">GF</span><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <form action="menu.php" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action_type" value="toggle_availability">
                                    <input type="hidden" name="item_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="is_available" value="<?= $m['is_available'] ? 0 : 1 ?>">
                                    <button type="submit" class="badge-status border-0 <?= $m['is_available'] ? 'badge-ready' : 'badge-cancelled' ?>" style="cursor:pointer;">
                                        <?= $m['is_available'] ? 'Available' : 'Sold Out' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="menu.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this menu item?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action_type" value="delete">
                                    <input type="hidden" name="item_id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger-saas py-1 px-2" title="Delete Item">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Menu Item -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-white fw-bold"><i class="fa-solid fa-plus-circle text-primary me-2"></i> Add Menu Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="menu.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-saas">Item Name</label>
                        <input type="text" name="name" class="form-control form-control-saas" placeholder="e.g. Wagyu Ribeye Steak" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">Category</label>
                            <select name="category_id" class="form-select form-control-saas" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Prep Time (Mins)</label>
                            <input type="number" name="preparation_time" class="form-control form-control-saas" value="15" min="1">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">Retail Price (<?= $currency ?>)</label>
                            <input type="number" step="0.01" name="price" class="form-control form-control-saas" placeholder="0.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Cost Price (<?= $currency ?>)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control form-control-saas" placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-saas">Dish Photo / Asset Name</label>
                        <select name="image" class="form-select form-control-saas mb-1">
                            <option value="calamari.jpg">calamari.jpg (Fried Calamari)</option>
                            <option value="bruschetta.jpg">bruschetta.jpg (Bruschetta al Pomodoro)</option>
                            <option value="truffle_burger.jpg">truffle_burger.jpg (Black Truffle Burger)</option>
                            <option value="cheeseburger.jpg">cheeseburger.jpg (Classic Cheeseburger)</option>
                            <option value="margherita.jpg">margherita.jpg (Margherita Pizza)</option>
                            <option value="pepperoni.jpg">pepperoni.jpg (Spicy Pepperoni Pizza)</option>
                            <option value="salmon.jpg">salmon.jpg (Atlantic Salmon)</option>
                            <option value="ribeye.jpg">ribeye.jpg (Angus Ribeye Steak)</option>
                            <option value="risotto.jpg">risotto.jpg (Wild Mushroom Risotto)</option>
                            <option value="tiramisu.jpg">tiramisu.jpg (Tiramisu Classico)</option>
                            <option value="passion_spritz.jpg">passion_spritz.jpg (Passion Fruit Spritz)</option>
                            <option value="matcha.jpg">matcha.jpg (Iced Matcha Latte)</option>
                            <option value="default_food.png">default_food.png (Default Placeholder)</option>
                        </select>
                        <small class="text-muted">High-res photos from <code>assets/images/</code>.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-saas">Description</label>
                        <textarea name="description" class="form-control form-control-saas" rows="2" placeholder="Ingredients, preparation details..."></textarea>
                    </div>
                    <div class="d-flex gap-3 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_vegetarian" id="chkVeg">
                            <label class="form-check-label text-light small" for="chkVeg">Vegetarian</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_vegan" id="chkVegan">
                            <label class="form-check-label text-light small" for="chkVegan">Vegan</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_gluten_free" id="chkGf">
                            <label class="form-check-label text-light small" for="chkGf">Gluten-Free</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary-saas" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-saas">Save Dish</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
