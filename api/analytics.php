<?php
/**
 * Restaurant Management System
 * REST API: Business Intelligence & Chart Analytics
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth_check.php';

$range = $_GET['range'] ?? '7days'; // today, 7days, 30days, custom
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-6 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');

try {
    // 1. Revenue Trends Based on Range
    if ($range === 'today') {
        $revenueSql = "SELECT HOUR(created_at) as time_label, 
                              COALESCE(SUM(total_paid), 0) as revenue,
                              COUNT(*) as order_count
                       FROM payments 
                       WHERE DATE(created_at) = CURDATE() AND payment_status = 'completed'
                       GROUP BY HOUR(created_at)
                       ORDER BY time_label ASC";
        $rawTrends = fetchAll($revenueSql);
        
        $labels = [];
        $values = [];
        for ($h = 8; $h <= 23; $h++) {
            $label = ($h > 12 ? ($h - 12) . ' PM' : ($h === 12 ? '12 PM' : $h . ' AM'));
            $labels[] = $label;
            $amt = 0.00;
            foreach ($rawTrends as $t) {
                if ((int)$t['time_label'] === $h) {
                    $amt = (float)$t['revenue'];
                    break;
                }
            }
            $values[] = $amt;
        }
    } elseif ($range === '30days') {
        $revenueSql = "SELECT DATE(created_at) as time_label, 
                              COALESCE(SUM(total_paid), 0) as revenue
                       FROM payments 
                       WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) AND payment_status = 'completed'
                       GROUP BY DATE(created_at)
                       ORDER BY time_label ASC";
        $rawTrends = fetchAll($revenueSql);
        
        $labels = [];
        $values = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime("-$i days"));
            $amt = 0.00;
            foreach ($rawTrends as $t) {
                if ($t['time_label'] === $d) {
                    $amt = (float)$t['revenue'];
                    break;
                }
            }
            $values[] = $amt;
        }
    } else {
        // Default: 7 days
        $revenueSql = "SELECT DATE(created_at) as time_label, 
                              COALESCE(SUM(total_paid), 0) as revenue
                       FROM payments 
                       WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND payment_status = 'completed'
                       GROUP BY DATE(created_at)
                       ORDER BY time_label ASC";
        $rawTrends = fetchAll($revenueSql);
        
        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('D (M j)', strtotime("-$i days"));
            $amt = 0.00;
            foreach ($rawTrends as $t) {
                if ($t['time_label'] === $d) {
                    $amt = (float)$t['revenue'];
                    break;
                }
            }
            $values[] = $amt;
        }
    }

    // 2. Order Status Distribution
    $distributionSql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $distRows = fetchAll($distributionSql);
    $statusDist = [
        'pending'   => 0,
        'preparing' => 0,
        'ready'     => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
    foreach ($distRows as $r) {
        if ($r['status'] === 'accepted') {
            $statusDist['preparing'] += (int)$r['count'];
        } elseif (isset($statusDist[$r['status']])) {
            $statusDist[$r['status']] += (int)$r['count'];
        }
    }

    // 3. Popular Food Items
    $popularSql = "SELECT m.name, SUM(oi.quantity) as order_count, SUM(oi.subtotal) as total_sales
                   FROM order_items oi
                   JOIN menu_items m ON oi.menu_item_id = m.id
                   JOIN orders o ON oi.order_id = o.id
                   WHERE o.status != 'cancelled'
                   GROUP BY m.id, m.name
                   ORDER BY order_count DESC
                   LIMIT 5";
    $popularItems = fetchAll($popularSql);

    // 4. Peak Dining Hours
    $peakSql = "SELECT HOUR(created_at) as hour_num, COUNT(*) as volume
                FROM orders
                WHERE status != 'cancelled'
                GROUP BY HOUR(created_at)
                ORDER BY hour_num ASC";
    $peakRows = fetchAll($peakSql);

    $hourLabels = [];
    $hourVolumes = [];
    for ($h = 11; $h <= 23; $h++) {
        $hourLabels[] = ($h > 12 ? ($h - 12) . ' PM' : ($h === 12 ? '12 PM' : $h . ' AM'));
        $vol = 0;
        foreach ($peakRows as $pr) {
            if ((int)$pr['hour_num'] === $h) {
                $vol = (int)$pr['volume'];
                break;
            }
        }
        $hourVolumes[] = $vol;
    }

    echo json_encode([
        'success' => true,
        'range' => $range,
        'revenue_chart' => [
            'labels' => $labels,
            'values' => $values
        ],
        'order_distribution' => $statusDist,
        'popular_items' => $popularItems,
        'peak_hours' => [
            'labels' => $hourLabels,
            'volumes' => $hourVolumes
        ]
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
