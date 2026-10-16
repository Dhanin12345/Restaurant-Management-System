<?php
/**
 * Restaurant Management System
 * Shared Navbar Component
 */

declare(strict_types=1);

$user = currentUser();
$flash = getFlash();

// Fetch latest 5 notifications
$recentNotifications = [];
if (isLoggedIn()) {
    try {
        $recentNotifications = fetchAll(
            "SELECT * FROM notifications 
             WHERE (user_id = ? OR user_id IS NULL) 
             ORDER BY created_at DESC LIMIT 5",
            [$user['id']]
        );
    } catch (\Throwable $th) {}
}
?>

<div class="app-main">
    <!-- Sticky Top Navbar -->
    <header class="app-header">
        <div class="header-left">
            <button class="btn btn-sm btn-secondary-saas d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="d-none d-sm-block">
                <h1 class="h5 mb-0 fw-bold text-white"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
                <small class="text-muted d-flex align-items-center gap-2">
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25" style="font-size:0.65rem;">
                        <i class="fa-solid fa-circle fa-2xs me-1"></i> Live System
                    </span>
                    <span><?= date('l, F j, Y') ?></span>
                </small>
            </div>
        </div>

        <div class="header-right">
            <!-- Step-by-Step Data Flow Simulator Launcher -->
            <button class="btn btn-sm btn-outline-warning d-flex align-items-center gap-2 py-2 px-3 fw-bold" type="button" data-bs-toggle="modal" data-bs-target="#workflowPlayerModal" title="Play Step-by-Step Data Processing">
                <i class="fa-solid fa-play-circle text-warning fa-lg"></i>
                <span class="d-none d-md-inline">Flow Player</span>
            </button>

            <!-- Notification Bell Dropdown -->
            <div class="dropdown">
                <button class="btn btn-secondary-saas position-relative py-2 px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-regular fa-bell"></i>
                    <?php if (!empty($unreadNotifs) && $unreadNotifs > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark" style="font-size: 0.65rem;">
                            <?= $unreadNotifs ?>
                        </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg p-2" style="width: 320px; background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                    <li class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom border-secondary border-opacity-25">
                        <span class="fw-bold text-light small"><i class="fa-regular fa-bell me-1 text-warning"></i> Notifications</span>
                        <span class="badge bg-primary-subtle text-primary small"><?= $unreadNotifs ?> Unread</span>
                    </li>
                    <?php if (empty($recentNotifications)): ?>
                        <li class="text-center py-3 text-muted small">No notifications found</li>
                    <?php else: ?>
                        <?php foreach ($recentNotifications as $notif): ?>
                            <li>
                                <a class="dropdown-item rounded p-2 text-wrap <?= empty($notif['is_read']) ? 'bg-dark bg-opacity-50' : '' ?>" href="<?= htmlspecialchars($notif['link'] ?? '#') ?>">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="fa-solid <?= $notif['type'] === 'low_stock' ? 'fa-triangle-exclamation text-danger' : ($notif['type'] === 'order' ? 'fa-receipt text-warning' : 'fa-circle-info text-info') ?> mt-1"></i>
                                        <div>
                                            <div class="fw-semibold text-light small"><?= htmlspecialchars($notif['title']) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($notif['message']) ?></div>
                                            <div class="text-subtle" style="font-size: 0.65rem;"><?= date('M j, g:i A', strtotime($notif['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Quick Action Button -->
            <?php if (hasRole(['admin', 'manager', 'waiter'])): ?>
                <a href="../waiter/create_order.php" class="btn btn-primary-saas py-2 px-3">
                    <i class="fa-solid fa-plus-circle"></i> <span class="d-none d-md-inline">New Order</span>
                </a>
            <?php elseif (hasRole('customer')): ?>
                <a href="../customer/reservation.php" class="btn btn-primary-saas py-2 px-3">
                    <i class="fa-regular fa-calendar-plus"></i> <span class="d-none d-md-inline">Book Table</span>
                </a>
            <?php endif; ?>

            <!-- User Menu Dropdown -->
            <div class="dropdown">
                <button class="btn btn-secondary-saas d-flex align-items-center gap-2 py-1 px-2" type="button" data-bs-toggle="dropdown">
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="d-none d-lg-inline small fw-semibold text-light"><?= htmlspecialchars($user['name'] ?? 'User') ?></span>
                    <i class="fa-solid fa-chevron-down fa-2xs text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg p-2" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                    <li class="px-2 py-1 border-bottom border-secondary border-opacity-25 mb-1">
                        <div class="text-light fw-bold small"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                        <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                        <span class="badge bg-primary text-white mt-1" style="font-size: 0.65rem;"><?= strtoupper($user['role'] ?? 'guest') ?></span>
                    </li>
                    <li><a class="dropdown-item text-light small py-2 rounded" href="../index.php"><i class="fa-solid fa-store me-2 text-warning"></i> Public Home</a></li>
                    <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                    <li><a class="dropdown-item text-danger small py-2 rounded" href="../auth/logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content Container -->
    <main class="content-wrapper">
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'] === 'error' ? 'danger' : $flash['type']) ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-success' : ($flash['type'] === 'danger' || $flash['type'] === 'error' ? 'fa-triangle-exclamation text-danger' : 'fa-circle-info text-info') ?> fa-lg"></i>
                    <div><?= htmlspecialchars($flash['message']) ?></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
