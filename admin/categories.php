<?php
/**
 * Restaurant Management System
 * Admin: Category Management
 */

declare(strict_types=1);

$pageTitle = 'Category Management';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $action = $_POST['action_type'];
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fa-utensils');
            $order = (int)($_POST['display_order'] ?? 0);

            if (!empty($name)) {
                $id = insertAndGetId("INSERT INTO categories (name, slug, description, icon, display_order) VALUES (?, ?, ?, ?, ?)", [$name, $slug, $description, $icon, $order]);
                logAudit(currentUserId(), 'CREATE_CATEGORY', 'categories', $id, null, ['name' => $name]);
                setFlash('success', "Category '$name' created.");
                header("Location: categories.php");
                exit;
            }
        }
    }
}

$categories = fetchAll(
    "SELECT c.*, (SELECT COUNT(*) FROM menu_items WHERE category_id = c.id) as item_count 
     FROM categories c 
     ORDER BY c.display_order ASC"
);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Menu Categories</h2>
        <p class="text-muted mb-0">Organize your dishes, appetizers, beverages, and desserts into curated menus.</p>
    </div>
    <div>
        <button class="btn btn-primary-saas" data-bs-toggle="modal" data-bs-target="#addCatModal">
            <i class="fa-solid fa-plus-circle me-1"></i> New Category
        </button>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($categories as $cat): ?>
        <div class="col-md-6 col-lg-4">
            <div class="saas-card h-100">
                <div class="saas-card-body d-flex gap-3 align-items-start">
                    <div class="brand-icon" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); color: var(--primary);">
                        <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="text-white fw-bold mb-1"><?= htmlspecialchars($cat['name']) ?></h5>
                            <span class="badge bg-primary-subtle text-primary"><?= $cat['item_count'] ?> dishes</span>
                        </div>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($cat['description'] ?? 'No description') ?></p>
                        <div class="d-flex justify-content-between align-items-center text-subtle small">
                            <span>Slug: <code><?= htmlspecialchars($cat['slug']) ?></code></span>
                            <span>Order: #<?= $cat['display_order'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add Category -->
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-white fw-bold">Add Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="categories.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-saas">Category Name</label>
                        <input type="text" name="name" class="form-control form-control-saas" placeholder="e.g. Wood-Fired Pizza" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">FontAwesome Icon</label>
                            <input type="text" name="icon" class="form-control form-control-saas" value="fa-utensils">
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Display Order</label>
                            <input type="number" name="display_order" class="form-control form-control-saas" value="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-saas">Description</label>
                        <textarea name="description" class="form-control form-control-saas" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary-saas" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-saas">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
