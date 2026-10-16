<?php
/**
 * Restaurant Management System
 * Admin: Enterprise Audit Trail & Forensic Activity Log
 */

declare(strict_types=1);

$pageTitle = 'Enterprise Audit Logs';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin']);

$actionFilter = $_GET['action'] ?? '';
$sql = "SELECT a.*, u.full_name, u.email, u.role
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id";
$params = [];

if (!empty($actionFilter)) {
    $sql .= " WHERE a.action = ?";
    $params[] = $actionFilter;
}
$sql .= " ORDER BY a.created_at DESC LIMIT 150";
$logs = fetchAll($sql, $params);

// Distinct actions for dropdown
$actions = fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Enterprise Audit & Compliance Trail</h2>
        <p class="text-muted mb-0">Immutable record of price changes, stock adjustments, role modifications and system events.</p>
    </div>
    <div>
        <form method="GET" class="d-flex gap-2">
            <select name="action" class="form-select form-select-sm form-control-saas" onchange="this.form.submit()">
                <option value="">All Security Actions</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?= htmlspecialchars($act['action']) ?>" <?= $actionFilter === $act['action'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($act['action']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($actionFilter)): ?>
                <a href="audit_logs.php" class="btn btn-sm btn-secondary-saas">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Operator</th>
                    <th>Security Action</th>
                    <th>Entity Type / ID</th>
                    <th>Previous State (Old)</th>
                    <th>Committed State (New)</th>
                    <th>IP / Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No audit records match the current filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <span class="text-light small fw-mono"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-light"><?= htmlspecialchars($log['full_name'] ?? 'System / Anonymous') ?></div>
                                <span class="badge bg-primary text-white" style="font-size:0.62rem;"><?= strtoupper($log['role'] ?? 'SYSTEM') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-info fw-mono" style="font-size:0.75rem;">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted text-uppercase small"><?= htmlspecialchars($log['entity_type']) ?></span>
                                <span class="fw-bold text-light">#<?= $log['entity_id'] ?? '—' ?></span>
                            </td>
                            <td>
                                <?php if ($log['old_value']): ?>
                                    <pre class="mb-0 text-danger small" style="max-width: 200px; font-size: 0.72rem; white-space: pre-wrap;"><?= htmlspecialchars($log['old_value']) ?></pre>
                                <?php else: ?>
                                    <span class="text-subtle small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['new_value']): ?>
                                    <pre class="mb-0 text-success small" style="max-width: 220px; font-size: 0.72rem; white-space: pre-wrap;"><?= htmlspecialchars($log['new_value']) ?></pre>
                                <?php else: ?>
                                    <span class="text-subtle small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted font-monospace d-block"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></small>
                                <small class="text-subtle text-truncate d-block" style="max-width: 140px;" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>">
                                    <?= htmlspecialchars($log['user_agent'] ?? '') ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
