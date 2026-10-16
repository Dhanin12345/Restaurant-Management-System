<?php
/**
 * Restaurant Management System
 * REST API: Real-Time Operational Telemetry
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth_check.php';

try {
    $todaySales = (float)(fetchOne("SELECT COALESCE(SUM(total_paid), 0) as s FROM payments WHERE DATE(created_at) = CURDATE() AND payment_status = 'completed'")['s'] ?? 0);
    $todayOrders = (int)(fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = CURDATE()")['cnt'] ?? 0);
    $activeKitchen = (int)(fetchOne("SELECT COUNT(*) as cnt FROM orders WHERE status IN ('pending', 'accepted', 'preparing', 'ready')")['cnt'] ?? 0);
    $lowStock = (int)(fetchOne("SELECT COUNT(*) as cnt FROM ingredients WHERE current_stock <= reorder_level")['cnt'] ?? 0);
    
    $tables = fetchOne("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied FROM dining_tables");
    $totalTables = (int)($tables['total'] ?? 0);
    $occupiedTables = (int)($tables['occupied'] ?? 0);

    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'metrics' => [
            'today_sales' => $todaySales,
            'today_orders' => $todayOrders,
            'active_kitchen' => $activeKitchen,
            'low_stock' => $lowStock,
            'occupied_tables' => $occupiedTables,
            'total_tables' => $totalTables,
            'utilization_pct' => $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100) : 0
        ]
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
