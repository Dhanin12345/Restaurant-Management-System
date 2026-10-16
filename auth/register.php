<?php
/**
 * Restaurant Management System
 * Customer Registration Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

if (isLoggedIn()) {
    header("Location: " . getRoleHomeUrl());
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || empty($email) || empty($password)) {
            $error = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirmPassword) {
            $error = "Password and confirmation do not match.";
        } else {
            // Check if email already exists
            $existing = fetchOne("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
            if ($existing) {
                $error = "An account with that email already exists. Please sign in.";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $newUserId = insertAndGetId(
                    "INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'customer', 'active')",
                    [$fullName, $email, $phone, $hashed]
                );

                logAudit($newUserId, 'CUSTOMER_REGISTER', 'users', $newUserId, null, ['email' => $email]);
                createNotification(null, 'New Customer Joined', "$fullName registered an online dining account.", 'system', 'users.php');

                // Auto login new customer
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                $_SESSION['user_avatar'] = 'default_avatar.png';

                setFlash('success', "Welcome to Restaurant Pro, $fullName! Your account has been created.");
                header("Location: ../customer/menu.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Sign Up | Restaurant Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <style>
        body {
            background: #080c14;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }
        .register-card {
            max-width: 540px;
            width: 100%;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="text-center mb-4">
        <div class="brand-icon mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-utensils"></i>
        </div>
        <h2 class="h4 fw-bold text-white mb-1">Create Customer Account</h2>
        <p class="text-muted small">Reserve VIP tables, order online and view meal receipts</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <?= csrfField() ?>

        <div class="mb-3">
            <label class="form-label-saas">Full Name</label>
            <input type="text" name="full_name" class="form-control form-control-saas" placeholder="e.g. David Sterling" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-7">
                <label class="form-label-saas">Email Address</label>
                <input type="email" name="email" class="form-control form-control-saas" placeholder="david@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label-saas">Phone Number</label>
                <input type="tel" name="phone" class="form-control form-control-saas" placeholder="+1 555-0100" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
        </div>

        <div class="row g-2 mb-4">
            <div class="col-md-6">
                <label class="form-label-saas">Password</label>
                <input type="password" name="password" class="form-control form-control-saas" placeholder="Min 6 characters" required>
            </div>
            <div class="col-md-6">
                <label class="form-label-saas">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control form-control-saas" placeholder="Re-enter password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-saas w-100 py-2">
            <i class="fa-solid fa-user-plus me-2"></i> Register Account
        </button>

        <div class="text-center mt-3">
            <span class="text-muted small">Already have an account? </span>
            <a href="login.php" class="text-primary fw-semibold small">Sign in</a>
        </div>
    </form>
</div>

</body>
</html>
