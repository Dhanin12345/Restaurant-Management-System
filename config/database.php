<?php
/**
 * Restaurant Management System
 * Database Configuration & High-Performance PDO Abstraction
 */

declare(strict_types=1);

// Configuration constants (Supports cloud environment variables or defaults to local XAMPP)
if ($dbUrl = (getenv('DATABASE_URL') ?: getenv('MYSQL_URL'))) {
    $parsed = parse_url($dbUrl);
    define('DB_HOST', $parsed['host'] ?? 'localhost');
    define('DB_PORT', (string)($parsed['port'] ?? '3306'));
    define('DB_USER', $parsed['user'] ?? 'root');
    define('DB_PASS', $parsed['pass'] ?? '');
    define('DB_NAME', ltrim($parsed['path'] ?? 'restaurant_db', '/'));
} else {
    define('DB_HOST', getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost'));
    define('DB_PORT', (string)(getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306')));
    define('DB_NAME', getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'restaurant_db'));
    define('DB_USER', getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root'));
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : ''));
}
define('DB_CHARSET', 'utf8mb4');

// Base URL helper
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Automatically detect script directory
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If running in subfolder or root
    $basePath = rtrim($scriptName, '/');
    // Normalize to root or subpath
    define('BASE_URL', $protocol . $host . ($basePath ? $basePath : ''));
}

/**
 * Returns a shared PDO instance
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Check if this is an API call
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Database connection failed: ' . $e->getMessage()
                ]);
                exit;
            }
            die('<div style="font-family:sans-serif;padding:30px;background:#fee2e2;border:1px solid #ef4444;border-radius:8px;max-width:600px;margin:50px auto;">
                <h2 style="color:#b91c1c;margin-top:0;">Database Connection Error</h2>
                <p>Could not connect to the database. Please verify MySQL is running in XAMPP.</p>
                <code style="background:#fff;padding:8px 12px;border-radius:4px;display:block;margin-top:10px;">' . htmlspecialchars($e->getMessage()) . '</code>
            </div>');
        }
    }

    return $pdo;
}

/**
 * Helper: Fetch a single row
 */
function fetchOne(string $sql, array $params = []): ?array {
    $stmt = getDBConnection()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Helper: Fetch all rows
 */
function fetchAll(string $sql, array $params = []): array {
    $stmt = getDBConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Helper: Execute statement (UPDATE/DELETE/INSERT)
 */
function executeQuery(string $sql, array $params = []): bool {
    $stmt = getDBConnection()->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Helper: Insert and return last inserted ID
 */
function insertAndGetId(string $sql, array $params = []): int {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$pdo->lastInsertId();
}

/**
 * Helper: Retrieve dynamic restaurant setting
 */
function getSetting(string $key, $default = null) {
    static $settingsCache = [];

    if (empty($settingsCache)) {
        try {
            $rows = fetchAll("SELECT setting_key, setting_value FROM restaurant_settings");
            foreach ($rows as $row) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Throwable $th) {
            return $default;
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Helper: Update dynamic restaurant setting
 */
function updateSetting(string $key, string $value): bool {
    $stmt = getDBConnection()->prepare("UPDATE restaurant_settings SET setting_value = ? WHERE setting_key = ?");
    return $stmt->execute([$value, $key]);
}

/**
 * Enterprise Audit Logger
 */
function logAudit(?int $userId, string $action, string $entityType, ?int $entityId = null, $oldValue = null, $newValue = null): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown', 0, 250);

        $oldJson = $oldValue !== null ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null;
        $newJson = $newValue !== null ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null;

        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        executeQuery($sql, [$userId, $action, $entityType, $entityId, $oldJson, $newJson, $ip, $userAgent]);
    } catch (\Throwable $e) {
        // Silently capture to avoid breaking critical transactional paths
        error_log("Audit log failure: " . $e->getMessage());
    }
}

/**
 * In-App Notification Dispatcher
 */
function createNotification(?int $userId, string $title, string $message, string $type = 'system', ?string $link = null): void {
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)";
        executeQuery($sql, [$userId, $title, $message, $type, $link]);
    } catch (\Throwable $e) {
        error_log("Notification dispatch failure: " . $e->getMessage());
    }
}
