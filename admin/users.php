<?php
/**
 * Restaurant Management System
 * Admin: Staff & User Access Management
 */

declare(strict_types=1);

$pageTitle = 'Staff & User Access';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

// Handle Add User Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $action = $_POST['action_type'];
        if ($action === 'create_user') {
            $name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'waiter';
            $password = $_POST['password'] ?? '';

            if (!empty($name) && !empty($email) && !empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $newId = insertAndGetId(
                    "INSERT INTO users (full_name, email, phone, role, password, status) VALUES (?, ?, ?, ?, ?, 'active')",
                    [$name, $email, $phone, $role, $hash]
                );
                logAudit(currentUserId(), 'CREATE_STAFF_USER', 'users', $newId, null, ['name' => $name, 'role' => $role, 'email' => $email]);
                setFlash('success', "Staff user '$name' ($role) registered successfully.");
                header("Location: users.php");
                exit;
            }
        } elseif ($action === 'toggle_status') {
            $uid = (int)($_POST['user_id'] ?? 0);
            $newStatus = $_POST['status'] ?? 'active';
            if ($uid !== currentUserId()) { // Prevent self-deactivation
                executeQuery("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $uid]);
                logAudit(currentUserId(), 'TOGGLE_USER_STATUS', 'users', $uid, null, ['status' => $newStatus]);
                setFlash('info', "User status updated.");
            }
            header("Location: users.php");
            exit;
        }
    }
}

$users = fetchAll("SELECT * FROM users ORDER BY role ASC, full_name ASC");

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Staff & Access Control</h2>
        <p class="text-muted mb-0">Role assignments for Admins, Managers, Floor Waiters, Kitchen Chefs, Cashiers and Diners.</p>
    </div>
    <div>
        <button class="btn btn-primary-saas" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fa-solid fa-user-plus me-1"></i> Add Staff Member
        </button>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Assigned Role</th>
                    <th>Account Status</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <div class="fw-bold text-light"><?= htmlspecialchars($u['full_name']) ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="text-light small"><?= htmlspecialchars($u['email']) ?></span>
                        </td>
                        <td>
                            <span class="text-muted small"><?= htmlspecialchars($u['phone'] ?: '—') ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary text-white text-uppercase" style="font-size:0.68rem;">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-status <?= $u['status'] === 'active' ? 'badge-ready' : 'badge-danger' ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-muted small"><?= date('M j, Y', strtotime($u['created_at'])) ?></span>
                        </td>
                        <td class="text-end">
                            <?php if ($u['id'] !== currentUserId()): ?>
                                <form action="users.php" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action_type" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary-saas py-1 px-2" title="Toggle active status">
                                        <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban text-danger' : 'fa-check text-success' ?>"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Staff Member -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-white fw-bold">Add Staff Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="users.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="create_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-saas">Full Name</label>
                        <input type="text" name="full_name" class="form-control form-control-saas" placeholder="e.g. Liam Sterling" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">Work Email</label>
                            <input type="email" name="email" class="form-control form-control-saas" placeholder="liam@restaurant.com" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Phone</label>
                            <input type="tel" name="phone" class="form-control form-control-saas" placeholder="+1 555-0199">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">Role</label>
                            <select name="role" class="form-select form-control-saas" required>
                                <option value="waiter">Waiter</option>
                                <option value="kitchen">Kitchen Chef</option>
                                <option value="cashier">Cashier</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Initial Password</label>
                            <input type="password" name="password" class="form-control form-control-saas" value="Staff@123" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary-saas" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-saas">Register User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
