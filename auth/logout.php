<?php
/**
 * Restaurant Management System
 * Logout Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';

if (isLoggedIn()) {
    logAudit(currentUserId(), 'USER_LOGOUT', 'users', currentUserId(), null, null);
}

// Unset session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start clean session for flash message
session_start();
setFlash('info', 'You have been successfully signed out.');
header("Location: login.php");
exit;
