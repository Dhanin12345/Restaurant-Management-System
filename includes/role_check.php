<?php
/**
 * Restaurant Management System
 * Role-Based Access Control (RBAC) Guard
 */

declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';

/**
 * Returns default home URL for a given role
 */
function getRoleHomeUrl(?string $role = null): string {
    $role = $role ?? currentUserRole() ?? 'customer';
    
    // Relative paths work everywhere irrespective of subdirectories
    switch ($role) {
        case 'admin':
            return '../admin/dashboard.php';
        case 'manager':
            return '../manager/dashboard.php';
        case 'waiter':
            return '../waiter/dashboard.php';
        case 'kitchen':
            return '../kitchen/dashboard.php';
        case 'cashier':
            return '../cashier/dashboard.php';
        case 'customer':
            return '../customer/menu.php';
        default:
            return '../index.php';
    }
}

/**
 * Check if the currently logged-in user has one of the required roles
 */
function hasRole($roles): bool {
    if (!isLoggedIn()) {
        return false;
    }
    $currentRole = currentUserRole();
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return $currentRole === $roles;
}

/**
 * Enforce that the user has at least one of the specified roles
 */
function requireRole($allowedRoles): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access that section.');
        header("Location: ../auth/login.php");
        exit;
    }

    $rolesArray = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = currentUserRole();

    if (!in_array($userRole, $rolesArray, true)) {
        // Log unauthorized access attempt in audit log
        logAudit(
            currentUserId(),
            'UNAUTHORIZED_ACCESS_ATTEMPT',
            'security',
            null,
            ['requested_roles' => $rolesArray],
            ['user_role' => $userRole, 'uri' => $_SERVER['REQUEST_URI'] ?? '']
        );

        setFlash('danger', 'Access denied. Your role (' . ucfirst($userRole) . ') is not authorized for that page.');
        header("Location: " . getRoleHomeUrl($userRole));
        exit;
    }
}
