<?php
/**
 * Restaurant Management System
 * Authentication Portal - Sign In
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

// Quick Role Switcher for instant evaluator/demo access
if (isset($_GET['switch'])) {
    $requestedRole = trim($_GET['switch']);
    $roleEmails = [
        'admin'    => 'admin@restaurant.com',
        'manager'  => 'manager@restaurant.com',
        'waiter'   => 'waiter@restaurant.com',
        'kitchen'  => 'kitchen@restaurant.com',
        'cashier'  => 'cashier@restaurant.com',
        'customer' => 'customer@restaurant.com'
    ];

    if (isset($roleEmails[$requestedRole])) {
        $targetEmail = $roleEmails[$requestedRole];
        $targetUser = fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$targetEmail]);
        if ($targetUser) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $targetUser['id'];
            $_SESSION['user_name'] = $targetUser['full_name'];
            $_SESSION['user_email'] = $targetUser['email'];
            $_SESSION['user_role'] = $targetUser['role'];
            $_SESSION['user_avatar'] = $targetUser['avatar'];

            logAudit($targetUser['id'], 'DEMO_ROLE_SWITCH', 'users', $targetUser['id'], null, ['role' => $targetUser['role']]);
            setFlash('success', 'Logged in as ' . htmlspecialchars($targetUser['full_name']) . ' (' . strtoupper($targetUser['role']) . ')');
            header("Location: " . getRoleHomeUrl($targetUser['role']));
            exit;
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: " . getRoleHomeUrl());
    exit;
}

$error = null;

// Handle Standard POST Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter both your email address and password.";
        } else {
            $user = fetchOne("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is marked as inactive or suspended. Please contact administrator.";
                } else {
                    // Session Fixation Protection
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_avatar'] = $user['avatar'];

                    logAudit($user['id'], 'USER_LOGIN', 'users', $user['id'], null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

                    setFlash('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
                    header("Location: " . getRoleHomeUrl($user['role']));
                    exit;
                }
            } else {
                $error = "Invalid credentials. Please verify your email and password.";
            }
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Restaurant Pro</title>
    <!-- Google Fonts -->
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
            padding: 1.5rem;
        }
        .login-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            max-width: 1020px;
            width: 100%;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }
        .login-hero {
            background: linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(10, 14, 23, 0.98) 100%),
                        radial-gradient(circle at 10% 20%, rgba(249, 115, 22, 0.15) 0%, transparent 60%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid var(--border-color);
        }
        .login-form-pane {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .demo-badge-btn {
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.45rem 0.75rem;
            font-size: 0.75rem;
            color: #cbd5e1;
            cursor: pointer;
            text-align: left;
            transition: all 0.15s ease;
        }
        .demo-badge-btn:hover {
            border-color: var(--primary);
            background: var(--bg-surface-hover);
            color: white;
            transform: translateY(-2px);
        }
        @media (max-width: 900px) {
            .login-grid {
                grid-template-columns: 1fr;
            }
            .login-hero {
                display: none;
            }
            .login-form-pane {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="login-grid">
    <!-- Left Hero Section -->
    <div class="login-hero">
        <div>
            <div class="d-flex align-items-center gap-2 mb-4">
                <div class="brand-icon" style="width: 44px; height: 44px; font-size: 1.3rem;">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div>
                    <h2 class="h5 mb-0 fw-bold text-white">RESTAURANT PRO</h2>
                    <small class="text-muted" style="letter-spacing: 0.05em;">ENTERPRISE RESTAURANT OS</small>
                </div>
            </div>
            
            <h1 class="display-6 fw-bold text-white mb-3" style="font-size: 2rem;">
                Orchestrate your kitchen, tables & billing with ease.
            </h1>
            <p class="text-muted mb-4">
                Designed for high-volume dining, real-time kitchen display pipelines, dynamic recipe inventory deduction, and actionable revenue analytics.
            </p>

            <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge rounded-circle p-2 bg-success bg-opacity-25 text-success"><i class="fa-solid fa-bolt"></i></span>
                    <span class="text-light small">Zero-refresh AJAX kitchen pipeline (KDS)</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge rounded-circle p-2 bg-warning bg-opacity-25 text-warning"><i class="fa-solid fa-cubes-stacked"></i></span>
                    <span class="text-light small">Automatic recipe BOM inventory deduction</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge rounded-circle p-2 bg-info bg-opacity-25 text-info"><i class="fa-solid fa-shield-halved"></i></span>
                    <span class="text-light small">Strict Role-Based Access Control & Audit Trails</span>
                </div>
            </div>
        </div>

        <div class="pt-4 border-top border-secondary border-opacity-25 mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">&copy; <?= date('Y') ?> Restaurant Pro SaaS</span>
                <a href="../index.php" class="text-primary small fw-semibold text-decoration-none">
                    <i class="fa-solid fa-arrow-left me-1"></i> Public Website
                </a>
            </div>
        </div>
    </div>

    <!-- Right Login Form -->
    <div class="login-form-pane">
        <div class="mb-4">
            <h2 class="h3 fw-bold text-white mb-1">Welcome Back</h2>
            <p class="text-muted small">Sign in with your credentials to access your terminal</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'] === 'error' ? 'danger' : $flash['type']) ?> py-2 px-3 small rounded-3 mb-3">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="mb-4">
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label for="emailInput" class="form-label-saas">Work Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted">
                        <i class="fa-regular fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="emailInput" class="form-control form-control-saas" placeholder="name@restaurant.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="passwordInput" class="form-label-saas">Password</label>
                    <a href="javascript:void(0)" onclick="alert('For evaluation, click one of the quick test accounts below.')" class="text-muted small" style="font-size:0.75rem;">Forgot password?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="passwordInput" class="form-control form-control-saas" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-saas w-100 py-2 mt-2">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Authenticate & Enter
            </button>
        </form>

        <!-- Instant Test / Evaluation Credentials -->
        <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.3); border: 1px dashed rgba(255,255,255,0.15);">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-light small"><i class="fa-solid fa-key me-1 text-warning"></i> 1-Click Role Accounts</span>
                <span class="badge bg-secondary" style="font-size:0.65rem;">Auto Fill</span>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <button type="button" class="demo-badge-btn w-100" onclick="fillCreds('admin@restaurant.com', 'Admin@123')">
                        <div class="fw-bold text-warning">👑 Admin</div>
                        <div class="text-muted" style="font-size:0.68rem;">admin@restaurant.com</div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="demo-badge-btn w-100" onclick="fillCreds('manager@restaurant.com', 'Manager@123')">
                        <div class="fw-bold text-info">👔 Manager</div>
                        <div class="text-muted" style="font-size:0.68rem;">manager@restaurant.com</div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="demo-badge-btn w-100" onclick="fillCreds('waiter@restaurant.com', 'Waiter@123')">
                        <div class="fw-bold text-success">🍽 Waiter (POS)</div>
                        <div class="text-muted" style="font-size:0.68rem;">waiter@restaurant.com</div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="demo-badge-btn w-100" onclick="fillCreds('kitchen@restaurant.com', 'Kitchen@123')">
                        <div class="fw-bold text-danger">🔥 Kitchen (KDS)</div>
                        <div class="text-muted" style="font-size:0.68rem;">kitchen@restaurant.com</div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="demo-badge-btn w-100" onclick="fillCreds('cashier@restaurant.com', 'Cashier@123')">
                        <div class="fw-bold text-primary">💳 Cashier</div>
                        <div class="text-muted" style="font-size:0.68rem;">cashier@restaurant.com</div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="demo-badge-btn w-100" onclick="fillCreds('customer@restaurant.com', 'Customer@123')">
                        <div class="fw-bold text-light">🧑 Customer</div>
                        <div class="text-muted" style="font-size:0.68rem;">customer@restaurant.com</div>
                    </button>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <span class="text-muted small">New diner? </span>
            <a href="register.php" class="text-primary fw-semibold small">Create customer account</a>
        </div>
    </div>
</div>

<script>
function fillCreds(email, pass) {
    document.getElementById('emailInput').value = email;
    document.getElementById('passwordInput').value = pass;
}
</script>

</body>
</html>
