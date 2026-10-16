<?php
/**
 * Restaurant Management System
 * Admin: Reservation Management with Smart Table Allocation
 */

declare(strict_types=1);

$pageTitle = 'Reservations Management';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'waiter']);

$dateFilter = $_GET['date'] ?? date('Y-m-d');

// Status Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (validateCsrfToken()) {
        $action = $_POST['action_type'];
        $resId = (int)($_POST['reservation_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        if ($resId > 0 && !empty($newStatus)) {
            $res = fetchOne("SELECT * FROM reservations WHERE id = ?", [$resId]);
            if ($res) {
                executeQuery("UPDATE reservations SET status = ? WHERE id = ?", [$newStatus, $resId]);
                if ($newStatus === 'seated' && !empty($res['table_id'])) {
                    executeQuery("UPDATE dining_tables SET status = 'occupied' WHERE id = ?", [$res['table_id']]);
                } elseif (in_array($newStatus, ['completed', 'cancelled'], true) && !empty($res['table_id'])) {
                    executeQuery("UPDATE dining_tables SET status = 'available' WHERE id = ? AND status = 'reserved'", [$res['table_id']]);
                }
                logAudit(currentUserId(), 'UPDATE_RESERVATION_STATUS', 'reservations', $resId, ['status' => $res['status']], ['status' => $newStatus]);
                setFlash('success', "Reservation #{$res['reservation_code']} updated to " . ucfirst($newStatus));
                header("Location: reservations.php?date=$dateFilter");
                exit;
            }
        }
    }
}

$reservations = fetchAll(
    "SELECT r.*, t.table_number, t.floor_location, t.seating_capacity
     FROM reservations r
     LEFT JOIN dining_tables t ON r.table_id = t.id
     WHERE r.reservation_date = ?
     ORDER BY r.reservation_time ASC",
    [$dateFilter]
);

$availableTables = fetchAll("SELECT id, table_number, seating_capacity, floor_location FROM dining_tables WHERE status != 'maintenance' ORDER BY table_number ASC");

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold text-white mb-1">Dine-In Reservations</h2>
        <p class="text-muted mb-0">Smart capacity ranking, guest seating allocation and conflict detection.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="date" name="date" class="form-control form-control-sm form-control-saas" value="<?= htmlspecialchars($dateFilter) ?>" onchange="this.form.submit()">
        </form>
        <button class="btn btn-primary-saas" data-bs-toggle="modal" data-bs-target="#newResModal">
            <i class="fa-solid fa-calendar-plus me-1"></i> New Booking
        </button>
    </div>
</div>

<div class="saas-card">
    <div class="saas-card-body p-0">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Code & Guest</th>
                    <th>Date & Time</th>
                    <th>Guests</th>
                    <th>Allocated Table</th>
                    <th>Special Requests</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No reservations scheduled for <?= htmlspecialchars($dateFilter) ?>.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($r['reservation_code']) ?></div>
                                <div class="text-light fw-semibold small"><?= htmlspecialchars($r['customer_name']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($r['customer_phone']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-light"><i class="fa-regular fa-clock me-1 text-warning"></i> <?= date('g:i A', strtotime($r['reservation_time'])) ?></div>
                                <small class="text-muted"><?= date('D, M j', strtotime($r['reservation_date'])) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 text-light">
                                    <i class="fa-solid fa-users me-1 text-primary"></i> <?= $r['guest_count'] ?> Guests
                                </span>
                            </td>
                            <td>
                                <?php if ($r['table_number']): ?>
                                    <div class="fw-bold text-light"><?= htmlspecialchars($r['table_number']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($r['floor_location']) ?> (<?= $r['seating_capacity'] ?> seats)</small>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-25 text-warning">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($r['special_requests'] ?: 'None') ?></small>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= htmlspecialchars($r['status']) ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-primary-saas py-1 px-2" onclick="updateRes(<?= $r['id'] ?>, 'confirmed')">Confirm</button>
                                    <?php elseif ($r['status'] === 'confirmed'): ?>
                                        <button class="btn btn-sm btn-success text-white py-1 px-2" onclick="updateRes(<?= $r['id'] ?>, 'seated')">Seat</button>
                                    <?php elseif ($r['status'] === 'seated'): ?>
                                        <button class="btn btn-sm btn-secondary-saas py-1 px-2" onclick="updateRes(<?= $r['id'] ?>, 'completed')">Complete</button>
                                    <?php endif; ?>
                                    <?php if (!in_array($r['status'], ['completed', 'cancelled'])): ?>
                                        <button class="btn btn-sm btn-danger-saas py-1 px-2" onclick="updateRes(<?= $r['id'] ?>, 'cancelled')" title="Cancel">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New Reservation with Smart Table Allocation Engine -->
<div class="modal fade" id="newResModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-white fw-bold"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i> Book Reservation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="../api/reservations.php?action=create" method="POST" id="newResForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-saas">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control form-control-saas" required placeholder="e.g. Eleanor Vance">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label-saas">Email</label>
                            <input type="email" name="customer_email" class="form-control form-control-saas" required placeholder="eleanor@example.com">
                        </div>
                        <div class="col-6">
                            <label class="form-label-saas">Phone</label>
                            <input type="tel" name="customer_phone" class="form-control form-control-saas" required placeholder="+1 555-0199">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label-saas">Party Size</label>
                            <input type="number" name="guest_count" id="resGuestCount" class="form-control form-control-saas" value="4" min="1" max="20" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label-saas">Date</label>
                            <input type="date" name="reservation_date" id="resDate" class="form-control form-control-saas" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label-saas">Time</label>
                            <input type="time" name="reservation_time" id="resTime" class="form-control form-control-saas" value="19:30" required>
                        </div>
                    </div>

                    <!-- Smart Table Recommendation Widget -->
                    <div class="mb-3">
                        <label class="form-label-saas">Smart Table Allocation</label>
                        <div id="smartTableRecommendation">
                            <!-- Populated dynamically via assets/js/reservations.js -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-saas">Manual Override Table (Optional)</label>
                        <select name="table_id" id="resTableSelect" class="form-select form-control-saas">
                            <option value="">-- Let Smart Allocation Choose --</option>
                            <?php foreach ($availableTables as $at): ?>
                                <option value="<?= $at['id'] ?>"><?= htmlspecialchars($at['table_number']) ?> (<?= $at['seating_capacity'] ?> seats, <?= htmlspecialchars($at['floor_location']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-saas">Special Notes</label>
                        <textarea name="special_requests" class="form-control form-control-saas" rows="2" placeholder="Anniversary, dietary restrictions, quiet corner..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary-saas" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-saas">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="resStatusForm" action="reservations.php" method="POST" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action_type" value="update_status">
    <input type="hidden" name="reservation_id" id="resIdInput">
    <input type="hidden" name="status" id="resStatusInput">
</form>

<script src="../assets/js/reservations.js"></script>
<script>
function updateRes(resId, status) {
    document.getElementById('resIdInput').value = resId;
    document.getElementById('resStatusInput').value = status;
    document.getElementById('resStatusForm').submit();
}

document.getElementById('newResForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('../api/reservations.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert('Booking failed: ' + result.error);
        }
    } catch(err) {
        alert('Error booking reservation');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
