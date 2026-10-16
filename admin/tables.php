<?php
/**
 * Restaurant Management System
 * Admin: Dining Table Floorplan & Status Manager
 */

declare(strict_types=1);

$pageTitle = 'Table Management & Floorplan';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'waiter']);

// Handle Table Status Changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $action = $_POST['action_type'];

        if ($action === 'update_status') {
            $tableId = (int)($_POST['table_id'] ?? 0);
            $newStatus = $_POST['status'] ?? 'available';
            executeQuery("UPDATE dining_tables SET status = ? WHERE id = ?", [$newStatus, $tableId]);
            logAudit(currentUserId(), 'UPDATE_TABLE_STATUS', 'dining_tables', $tableId, null, ['status' => $newStatus]);
            setFlash('success', "Table status changed to " . ucfirst($newStatus));
            header("Location: tables.php");
            exit;
        } elseif ($action === 'create_table') {
            $tableNum = trim($_POST['table_number'] ?? '');
            $capacity = (int)($_POST['seating_capacity'] ?? 4);
            $location = $_POST['floor_location'] ?? 'Ground Floor Main';

            if (!empty($tableNum)) {
                $id = insertAndGetId("INSERT INTO dining_tables (table_number, seating_capacity, floor_location, status) VALUES (?, ?, ?, 'available')", [$tableNum, $capacity, $location]);
                logAudit(currentUserId(), 'CREATE_TABLE', 'dining_tables', $id, null, ['table_number' => $tableNum]);
                setFlash('success', "Table $tableNum added to floorplan.");
                header("Location: tables.php");
                exit;
            }
        }
    }
}

$tables = fetchAll("SELECT * FROM dining_tables ORDER BY floor_location ASC, table_number ASC");

// Group by floor location
$zones = [];
foreach ($tables as $t) {
    $zones[$t['floor_location']][] = $t;
}

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Interactive Table Floorplan</h2>
        <p class="text-muted mb-0">Monitor live dining room seating, occupancy states, and guest capacities.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasRole(['admin', 'manager'])): ?>
            <button class="btn btn-primary-saas" data-bs-toggle="modal" data-bs-target="#addTableModal">
                <i class="fa-solid fa-plus-circle me-1"></i> Add Dining Table
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Legend Bar -->
<div class="d-flex gap-4 p-3 rounded-3 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
    <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-circle p-2 bg-success"></span>
        <span class="small text-light">Available (Ready to seat)</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-circle p-2 bg-danger"></span>
        <span class="small text-light">Occupied (Dining in progress)</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-circle p-2 bg-warning"></span>
        <span class="small text-light">Reserved (Upcoming booking)</span>
    </div>
</div>

<!-- Render Floorplan Zones -->
<?php foreach ($zones as $zoneName => $zoneTables): ?>
    <div class="mb-5">
        <h3 class="h6 fw-bold text-uppercase text-muted mb-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-layer-group text-primary"></i> <?= htmlspecialchars($zoneName) ?> (<?= count($zoneTables) ?> Tables)
        </h3>

        <div class="floorplan-grid">
            <?php foreach ($zoneTables as $table): ?>
                <div class="table-seat-card table-<?= htmlspecialchars($table['status']) ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-dark border border-secondary border-opacity-25 text-muted" style="font-size:0.7rem;">
                            <i class="fa-solid fa-user-group me-1"></i> <?= $table['seating_capacity'] ?> Seats
                        </span>
                        <span class="badge-status badge-<?= htmlspecialchars($table['status']) ?>" style="font-size:0.65rem;">
                            <?= ucfirst($table['status']) ?>
                        </span>
                    </div>

                    <div class="display-6 fw-bold text-white my-2"><?= htmlspecialchars($table['table_number']) ?></div>

                    <div class="d-flex gap-1 justify-content-center mt-3">
                        <?php if ($table['status'] === 'available'): ?>
                            <a href="../waiter/create_order.php?table_id=<?= $table['id'] ?>" class="btn btn-sm btn-primary-saas py-1 px-2" style="font-size:0.75rem;" title="Start Dine-In Order">
                                <i class="fa-solid fa-receipt me-1"></i> Order
                            </a>
                            <button class="btn btn-sm btn-secondary-saas py-1 px-2" style="font-size:0.75rem;" onclick="setTableStatus(<?= $table['id'] ?>, 'reserved')">
                                Reserve
                            </button>
                        <?php elseif ($table['status'] === 'occupied'): ?>
                            <a href="../cashier/billing.php?table_id=<?= $table['id'] ?>" class="btn btn-sm btn-success py-1 px-2 text-white" style="font-size:0.75rem;">
                                <i class="fa-solid fa-file-invoice me-1"></i> Bill
                            </a>
                            <button class="btn btn-sm btn-secondary-saas py-1 px-2" style="font-size:0.75rem;" onclick="setTableStatus(<?= $table['id'] ?>, 'available')">
                                Release
                            </button>
                        <?php elseif ($table['status'] === 'reserved'): ?>
                            <button class="btn btn-sm btn-success py-1 px-2 text-white" style="font-size:0.75rem;" onclick="setTableStatus(<?= $table['id'] ?>, 'occupied')">
                                Seat Guests
                            </button>
                            <button class="btn btn-sm btn-secondary-saas py-1 px-2" style="font-size:0.75rem;" onclick="setTableStatus(<?= $table['id'] ?>, 'available')">
                                Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 text-center">
                        <a href="../customer/qr_menu.php?table=<?= urlencode($table['table_number']) ?>" target="_blank" class="text-subtle small text-decoration-none" style="font-size:0.7rem;">
                            <i class="fa-solid fa-qrcode me-1 text-warning"></i> Open Table QR Menu
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<!-- Hidden Status Update Form -->
<form id="statusForm" action="tables.php" method="POST" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action_type" value="update_status">
    <input type="hidden" name="table_id" id="tableIdInput">
    <input type="hidden" name="status" id="statusInput">
</form>

<!-- Modal: Add Table -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-white fw-bold">Add Dining Table</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="tables.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="create_table">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-saas">Table Identifier</label>
                        <input type="text" name="table_number" class="form-control form-control-saas" placeholder="e.g. T-15 or VIP-3" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">Guest Capacity</label>
                            <input type="number" name="seating_capacity" class="form-control form-control-saas" value="4" min="1" max="30" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Dining Zone</label>
                            <select name="floor_location" class="form-select form-control-saas">
                                <option value="Ground Floor Main">Ground Floor Main</option>
                                <option value="Outdoor Terrace">Outdoor Terrace</option>
                                <option value="VIP Lounge">VIP Lounge</option>
                                <option value="Bar & High Top">Bar & High Top</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary-saas" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-saas">Add Table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setTableStatus(tableId, status) {
    if (confirm(`Change table to ${status}?`)) {
        document.getElementById('tableIdInput').value = tableId;
        document.getElementById('statusInput').value = status;
        document.getElementById('statusForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
