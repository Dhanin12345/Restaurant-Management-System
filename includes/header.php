<?php
/**
 * Restaurant Management System
 * Shared Header Template
 */

declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/role_check.php';

$pageTitle = $pageTitle ?? 'Restaurant Pro';
$currentRole = currentUserRole() ?? 'guest';
$currentUser = currentUser();

// Fetch unread notifications count for logged in user
$unreadNotifs = 0;
if (isLoggedIn()) {
    try {
        $notifData = fetchOne("SELECT COUNT(*) as cnt FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0", [$currentUser['id']]);
        $unreadNotifs = (int)($notifData['cnt'] ?? 0);
    } catch (\Throwable $th) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Restaurant Pro SaaS</title>
    <meta name="description" content="Next-Level Enterprise Restaurant Management System with Real-Time Kitchen Workflow, Recipe Inventory Calculation & Analytics.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Chart.js for Next-Gen Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- Custom SaaS Design System -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<!-- Quick Demo Switcher Bar for Seamless Evaluation -->
<div class="role-switcher-banner">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-warning text-dark"><i class="fa-solid fa-bolt me-1"></i> Quick Role Switcher</span>
        <span class="d-none d-md-inline text-light opacity-75">Switch roles instantly to test RBAC & workflows:</span>
    </div>
    <div class="role-chips">
        <a href="../auth/login.php?switch=admin" class="role-chip <?= $currentRole === 'admin' ? 'active' : '' ?>"><i class="fa-solid fa-crown me-1"></i> Admin</a>
        <a href="../auth/login.php?switch=manager" class="role-chip <?= $currentRole === 'manager' ? 'active' : '' ?>"><i class="fa-solid fa-user-tie me-1"></i> Manager</a>
        <a href="../auth/login.php?switch=waiter" class="role-chip <?= $currentRole === 'waiter' ? 'active' : '' ?>"><i class="fa-solid fa-utensils me-1"></i> Waiter</a>
        <a href="../auth/login.php?switch=kitchen" class="role-chip <?= $currentRole === 'kitchen' ? 'active' : '' ?>"><i class="fa-solid fa-fire-burner me-1"></i> Kitchen</a>
        <a href="../auth/login.php?switch=cashier" class="role-chip <?= $currentRole === 'cashier' ? 'active' : '' ?>"><i class="fa-solid fa-cash-register me-1"></i> Cashier</a>
        <a href="../auth/login.php?switch=customer" class="role-chip <?= $currentRole === 'customer' ? 'active' : '' ?>"><i class="fa-solid fa-user me-1"></i> Customer</a>
    </div>
</div>

<div class="app-container">
