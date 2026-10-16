<?php
/**
 * Restaurant Management System
 * Customer: Account Settings & Dining Profile
 */

declare(strict_types=1);

$pageTitle = 'My Dining Account';
require_once __DIR__ . '/../includes/header.php';
requireAuth();

$userId = currentUserId();
$userRecord = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $action = $_POST['action_type'];
        if ($action === 'update_profile') {
            $name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            executeQuery("UPDATE users SET full_name = ?, phone = ? WHERE id = ?", [$name, $phone, $userId]);
            $_SESSION['user_name'] = $name;
            setFlash('success', "Profile updated successfully!");
            header("Location: profile.php");
            exit;
        } elseif ($action === 'change_password') {
            $curr = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            if (password_verify($curr, $userRecord['password']) && strlen($new) >= 6) {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                executeQuery("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
                setFlash('success', "Password updated successfully!");
            } else {
                setFlash('danger', "Invalid current password or new password too short.");
            }
            header("Location: profile.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="saas-card p-4 text-center">
            <div class="user-avatar mx-auto mb-3" style="width: 72px; height: 72px; font-size: 1.8rem;">
                <?= strtoupper(substr($userRecord['full_name'] ?? 'U', 0, 1)) ?>
            </div>
            <h4 class="fw-bold text-white mb-1"><?= htmlspecialchars($userRecord['full_name']) ?></h4>
            <p class="text-muted small mb-3"><?= htmlspecialchars($userRecord['email']) ?></p>
            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-3 py-2 text-uppercase fw-bold">
                <?= htmlspecialchars($userRecord['role']) ?> Pass
            </span>
            <div class="border-top border-secondary border-opacity-25 mt-4 pt-3 text-start small text-muted">
                <div class="d-flex justify-content-between mb-1">
                    <span>Member Since:</span>
                    <span class="text-light"><?= date('M j, Y', strtotime($userRecord['created_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Account Status:</span>
                    <span class="text-success fw-bold text-uppercase"><?= htmlspecialchars($userRecord['status']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="saas-card p-4 mb-4">
            <h5 class="saas-card-title mb-3"><i class="fa-solid fa-user-pen text-primary me-2"></i> Update Contact Details</h5>
            <form action="profile.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="update_profile">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label-saas">Full Name</label>
                        <input type="text" name="full_name" class="form-control form-control-saas" value="<?= htmlspecialchars($userRecord['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-saas">Phone Number</label>
                        <input type="tel" name="phone" class="form-control form-control-saas" value="<?= htmlspecialchars($userRecord['phone'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-saas">Save Profile Changes</button>
            </form>
        </div>

        <div class="saas-card p-4">
            <h5 class="saas-card-title mb-3"><i class="fa-solid fa-lock text-warning me-2"></i> Security & Password</h5>
            <form action="profile.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="change_password">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label-saas">Current Password</label>
                        <input type="password" name="current_password" class="form-control form-control-saas" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-saas">New Password</label>
                        <input type="password" name="new_password" class="form-control form-control-saas" placeholder="Min 6 characters" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-secondary-saas">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
