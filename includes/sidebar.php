<?php
/**
 * Restaurant Management System
 * Shared Sidebar Component with RBAC-Aware Navigation
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$user = currentUser();
$role = $user['role'] ?? 'customer';

function isActivePage(string $path): string {
    global $currentUri;
    return str_contains($currentUri, $path) ? 'active' : '';
}
?>

<aside class="app-sidebar" id="appSidebar">
    <!-- Brand Title -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-utensils"></i>
        </div>
        <div class="d-flex flex-column">
            <span class="brand-text">RESTAURANT <span class="brand-highlight">PRO</span></span>
            <small class="brand-subtext">ENTERPRISE SUITE</small>
        </div>
    </div>

    <!-- Navigation Items -->
    <nav class="sidebar-nav">
        
        <?php if ($role === 'admin'): ?>
            <!-- ADMIN NAVIGATION -->
            <div class="nav-section-title">Overview</div>
            <a href="../admin/dashboard.php" class="nav-link <?= isActivePage('admin/dashboard.php') ?>">
                <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
            </a>

            <div class="nav-section-title">Operations</div>
            <a href="../admin/orders.php" class="nav-link <?= isActivePage('admin/orders.php') ?>">
                <i class="fa-solid fa-receipt"></i> <span>Order Hub</span>
            </a>
            <a href="../admin/reservations.php" class="nav-link <?= isActivePage('admin/reservations.php') ?>">
                <i class="fa-solid fa-calendar-check"></i> <span>Reservations</span>
            </a>
            <a href="../admin/tables.php" class="nav-link <?= isActivePage('admin/tables.php') ?>">
                <i class="fa-solid fa-table-cells-large"></i> <span>Table Floorplan</span>
            </a>

            <div class="nav-section-title">Catalog & Kitchen</div>
            <a href="../admin/menu.php" class="nav-link <?= isActivePage('admin/menu.php') ?>">
                <i class="fa-solid fa-burger"></i> <span>Menu Items</span>
            </a>
            <a href="../admin/categories.php" class="nav-link <?= isActivePage('admin/categories.php') ?>">
                <i class="fa-solid fa-tags"></i> <span>Categories</span>
            </a>
            <a href="../admin/inventory.php" class="nav-link <?= isActivePage('admin/inventory.php') ?>">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Inventory & BOM</span>
            </a>

            <div class="nav-section-title">Finance & Intelligence</div>
            <a href="../admin/payments.php" class="nav-link <?= isActivePage('admin/payments.php') ?>">
                <i class="fa-solid fa-credit-card"></i> <span>Payments & Billing</span>
            </a>
            <a href="../admin/reports.php" class="nav-link <?= isActivePage('admin/reports.php') ?>">
                <i class="fa-solid fa-chart-line"></i> <span>Sales & Analytics</span>
            </a>
            <a href="../admin/audit_logs.php" class="nav-link <?= isActivePage('admin/audit_logs.php') ?>">
                <i class="fa-solid fa-shield-halved"></i> <span>Audit Logs</span>
            </a>
            <a href="../admin/users.php" class="nav-link <?= isActivePage('admin/users.php') ?>">
                <i class="fa-solid fa-users-gear"></i> <span>Staff & Users</span>
            </a>
            <a href="../admin/settings.php" class="nav-link <?= isActivePage('admin/settings.php') ?>">
                <i class="fa-solid fa-sliders"></i> <span>System Settings</span>
            </a>

        <?php elseif ($role === 'manager'): ?>
            <!-- MANAGER NAVIGATION -->
            <div class="nav-section-title">Management</div>
            <a href="../manager/dashboard.php" class="nav-link <?= isActivePage('manager/dashboard.php') ?>">
                <i class="fa-solid fa-chart-pie"></i> <span>Manager Dashboard</span>
            </a>
            <a href="../manager/orders.php" class="nav-link <?= isActivePage('manager/orders.php') ?>">
                <i class="fa-solid fa-receipt"></i> <span>Live Orders</span>
            </a>
            <a href="../manager/inventory.php" class="nav-link <?= isActivePage('manager/inventory.php') ?>">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Stock & Reorder</span>
            </a>
            <a href="../manager/staff.php" class="nav-link <?= isActivePage('manager/staff.php') ?>">
                <i class="fa-solid fa-id-badge"></i> <span>Staff Roster</span>
            </a>
            <a href="../manager/reports.php" class="nav-link <?= isActivePage('manager/reports.php') ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i> <span>Daily Reports</span>
            </a>

        <?php elseif ($role === 'waiter'): ?>
            <!-- WAITER NAVIGATION -->
            <div class="nav-section-title">Floor Operations</div>
            <a href="../waiter/dashboard.php" class="nav-link <?= isActivePage('waiter/dashboard.php') ?>">
                <i class="fa-solid fa-clipboard-user"></i> <span>Floor Station</span>
            </a>
            <a href="../waiter/create_order.php" class="nav-link <?= isActivePage('waiter/create_order.php') ?>">
                <i class="fa-solid fa-circle-plus"></i> <span>Create Order (POS)</span>
            </a>
            <a href="../waiter/tables.php" class="nav-link <?= isActivePage('waiter/tables.php') ?>">
                <i class="fa-solid fa-table-cells-large"></i> <span>Table Status</span>
            </a>
            <a href="../waiter/orders.php" class="nav-link <?= isActivePage('waiter/orders.php') ?>">
                <i class="fa-solid fa-bell-concierge"></i> <span>My Active Tickets</span>
            </a>

        <?php elseif ($role === 'kitchen'): ?>
            <!-- KITCHEN NAVIGATION -->
            <div class="nav-section-title">Kitchen Display</div>
            <a href="../kitchen/dashboard.php" class="nav-link <?= isActivePage('kitchen/dashboard.php') ?>">
                <i class="fa-solid fa-fire-burner"></i> <span>Kitchen Display (KDS)</span>
            </a>
            <a href="../kitchen/kitchen_orders.php" class="nav-link <?= isActivePage('kitchen/kitchen_orders.php') ?>">
                <i class="fa-solid fa-list-check"></i> <span>Orders Queue</span>
            </a>

        <?php elseif ($role === 'cashier'): ?>
            <!-- CASHIER NAVIGATION -->
            <div class="nav-section-title">Cashier Desk</div>
            <a href="../cashier/dashboard.php" class="nav-link <?= isActivePage('cashier/dashboard.php') ?>">
                <i class="fa-solid fa-cash-register"></i> <span>Terminal</span>
            </a>
            <a href="../cashier/billing.php" class="nav-link <?= isActivePage('cashier/billing.php') ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i> <span>Billing & Invoice</span>
            </a>
            <a href="../cashier/payments.php" class="nav-link <?= isActivePage('cashier/payments.php') ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Payment History</span>
            </a>

        <?php else: ?>
            <!-- CUSTOMER NAVIGATION -->
            <div class="nav-section-title">Dining Experience</div>
            <a href="../customer/menu.php" class="nav-link <?= isActivePage('customer/menu.php') ?>">
                <i class="fa-solid fa-book-open"></i> <span>Digital Menu</span>
            </a>
            <a href="../customer/reservation.php" class="nav-link <?= isActivePage('customer/reservation.php') ?>">
                <i class="fa-solid fa-calendar-plus"></i> <span>Table Reservation</span>
            </a>
            <a href="../customer/orders.php" class="nav-link <?= isActivePage('customer/orders.php') ?>">
                <i class="fa-solid fa-bag-shopping"></i> <span>My Orders</span>
            </a>
            <a href="../customer/profile.php" class="nav-link <?= isActivePage('customer/profile.php') ?>">
                <i class="fa-solid fa-user-circle"></i> <span>My Account</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title">Portals</div>
        <a href="../index.php" class="nav-link">
            <i class="fa-solid fa-store"></i> <span>Public Home</span>
        </a>
    </nav>

    <!-- Sidebar Footer with Current User Info -->
    <div class="sidebar-footer">
        <div class="user-profile-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name" title="<?= htmlspecialchars($user['name'] ?? 'Guest') ?>">
                    <?= htmlspecialchars($user['name'] ?? 'Guest') ?>
                </div>
                <span class="user-role-badge"><?= strtoupper($role) ?></span>
            </div>
            <a href="../auth/logout.php" class="btn btn-sm text-danger p-0 ms-auto" title="Log Out">
                <i class="fa-solid fa-power-off"></i>
            </a>
        </div>
    </div>
</aside>
