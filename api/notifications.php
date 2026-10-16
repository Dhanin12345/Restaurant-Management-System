<?php
/**
 * Restaurant Management System
 * REST API: Notifications & In-App Alerts
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'unread';
$userId = currentUserId();

try {
    if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            executeQuery("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)", [$id, $userId]);
        } else {
            // Mark all read
            executeQuery("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL)", [$userId]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Default: Return latest notifications & unread count
    $notifs = fetchAll(
        "SELECT * FROM notifications 
         WHERE (user_id = ? OR user_id IS NULL) 
         ORDER BY created_at DESC LIMIT 10",
        [$userId]
    );

    $unreadCnt = (int)(fetchOne(
        "SELECT COUNT(*) as cnt FROM notifications 
         WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0",
        [$userId]
    )['cnt'] ?? 0);

    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCnt,
        'notifications' => $notifs
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
