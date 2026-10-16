<?php
/**
 * Restaurant Management System
 * REST API: Reservations & Smart Table Allocation Engine
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

/**
 * Smart Table Allocation Algorithm
 * Finds the optimal table based on party size, capacity fit, and conflict detection
 */
function findOptimalTables(int $partySize, string $date, string $time): array {
    $minCapacity = $partySize;
    
    // 1. Fetch eligible tables with enough seats
    $candidateTables = fetchAll(
        "SELECT id, table_number, seating_capacity, floor_location, status 
         FROM dining_tables 
         WHERE seating_capacity >= ? AND status != 'maintenance'
         ORDER BY seating_capacity ASC",
        [$minCapacity]
    );

    if (empty($candidateTables)) {
        return [];
    }

    // 2. Check for time overlap conflicts (+/- 2 hour dining window)
    $availableTables = [];
    $conflictStmt = getDBConnection()->prepare(
        "SELECT id FROM reservations 
         WHERE table_id = ? 
           AND reservation_date = ? 
           AND status IN ('pending', 'confirmed', 'seated')
           AND reservation_time BETWEEN SUBTIME(?, '01:59:00') AND ADDTIME(?, '01:59:00')
         LIMIT 1"
    );

    foreach ($candidateTables as $tbl) {
        $conflictStmt->execute([$tbl['id'], $date, $time, $time]);
        $hasConflict = $conflictStmt->fetch();

        if (!$hasConflict) {
            // Calculate suitability score (smaller seat difference = higher rank)
            $seatWastage = $tbl['seating_capacity'] - $partySize;
            $tbl['fit_score'] = $seatWastage;
            $tbl['fit_label'] = ($seatWastage === 0) ? 'Perfect Fit' : "+$seatWastage seats extra";
            $availableTables[] = $tbl;
        }
    }

    // Sort by best fit score
    usort($availableTables, function($a, $b) {
        return $a['fit_score'] <=> $b['fit_score'];
    });

    return $availableTables;
}

try {
    // ----------------------------------------------------
    // 1. SMART ALLOCATION RECOMMENDATION
    // ----------------------------------------------------
    if ($action === 'recommend') {
        $partySize = max(1, (int)($_GET['party_size'] ?? 2));
        $date = $_GET['date'] ?? date('Y-m-d');
        $time = $_GET['time'] ?? '19:00:00';

        $tables = findOptimalTables($partySize, $date, $time);

        echo json_encode([
            'success' => true,
            'party_size' => $partySize,
            'date' => $date,
            'time' => $time,
            'recommended' => $tables[0] ?? null,
            'candidates' => $tables
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 2. CREATE RESERVATION
    // ----------------------------------------------------
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $name = trim($input['customer_name'] ?? '');
        $email = trim($input['customer_email'] ?? '');
        $phone = trim($input['customer_phone'] ?? '');
        $date = trim($input['reservation_date'] ?? date('Y-m-d'));
        $time = trim($input['reservation_time'] ?? '19:00:00');
        $partySize = max(1, (int)($input['guest_count'] ?? 2));
        $requests = trim($input['special_requests'] ?? '');
        $manualTableId = !empty($input['table_id']) ? (int)$input['table_id'] : null;

        if (empty($name) || empty($email) || empty($phone)) {
            echo json_encode(['success' => false, 'error' => 'Please provide full contact details.']);
            exit;
        }

        // Run smart allocation if table not manually forced
        $assignedTableId = $manualTableId;
        if (!$assignedTableId) {
            $recommendations = findOptimalTables($partySize, $date, $time);
            if (!empty($recommendations)) {
                $assignedTableId = $recommendations[0]['id'];
            }
        }

        $resCode = 'RES-' . strtoupper(substr(uniqid(), -5));
        $customerId = currentUserId();

        $sql = "INSERT INTO reservations (reservation_code, customer_id, customer_name, customer_email, customer_phone, table_id, reservation_date, reservation_time, guest_count, special_requests, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')";
        
        $resId = insertAndGetId($sql, [
            $resCode,
            $customerId,
            $name,
            $email,
            $phone,
            $assignedTableId,
            $date,
            $time,
            $partySize,
            $requests
        ]);

        // If today's reservation, mark table as reserved
        if ($assignedTableId && $date === date('Y-m-d')) {
            executeQuery("UPDATE dining_tables SET status = 'reserved' WHERE id = ? AND status = 'available'", [$assignedTableId]);
        }

        // Notify Staff
        createNotification(
            null,
            "New Reservation: $resCode",
            "$name reserved for $partySize guests on $date at $time.",
            "reservation",
            "../admin/reservations.php"
        );

        logAudit($customerId, 'CREATE_RESERVATION', 'reservations', $resId, null, [
            'code' => $resCode,
            'table_id' => $assignedTableId,
            'party_size' => $partySize,
            'date' => $date
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Reservation confirmed! Code: $resCode",
            'reservation_id' => $resId,
            'reservation_code' => $resCode,
            'table_id' => $assignedTableId
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 3. UPDATE STATUS (Confirm, Seat, Cancel)
    // ----------------------------------------------------
    if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $resId = (int)($input['reservation_id'] ?? 0);
        $newStatus = trim($input['status'] ?? '');
        $userId = currentUserId();

        $res = fetchOne("SELECT * FROM reservations WHERE id = ?", [$resId]);
        if (!$res) {
            echo json_encode(['success' => false, 'error' => 'Reservation not found']);
            exit;
        }

        executeQuery("UPDATE reservations SET status = ? WHERE id = ?", [$newStatus, $resId]);

        // If seated -> update table to occupied
        if ($newStatus === 'seated' && !empty($res['table_id'])) {
            executeQuery("UPDATE dining_tables SET status = 'occupied' WHERE id = ?", [$res['table_id']]);
        }
        // If completed or cancelled -> release table if it was marked reserved
        if (in_array($newStatus, ['completed', 'cancelled'], true) && !empty($res['table_id'])) {
            executeQuery("UPDATE dining_tables SET status = 'available' WHERE id = ? AND status = 'reserved'", [$res['table_id']]);
        }

        logAudit($userId, 'UPDATE_RESERVATION_STATUS', 'reservations', $resId, ['status' => $res['status']], ['status' => $newStatus]);

        echo json_encode(['success' => true, 'message' => "Reservation updated to " . ucfirst($newStatus)]);
        exit;
    }

    // Default: List reservations
    $dateFilter = $_GET['date'] ?? null;
    $sql = "SELECT r.*, t.table_number, t.floor_location
            FROM reservations r
            LEFT JOIN dining_tables t ON r.table_id = t.id";
    $params = [];
    if ($dateFilter) {
        $sql .= " WHERE r.reservation_date = ?";
        $params[] = $dateFilter;
    }
    $sql .= " ORDER BY r.reservation_date ASC, r.reservation_time ASC";

    $reservations = fetchAll($sql, $params);
    echo json_encode(['success' => true, 'reservations' => $reservations]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
