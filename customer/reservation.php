<?php
/**
 * Restaurant Management System
 * Customer: Online Table Reservation with Smart Seating Engine
 */

declare(strict_types=1);

$pageTitle = 'Reserve a Table';
require_once __DIR__ . '/../includes/header.php';

$user = currentUser();
$existingBookings = [];
if (isLoggedIn()) {
    $existingBookings = fetchAll(
        "SELECT r.*, t.table_number, t.floor_location
         FROM reservations r
         LEFT JOIN dining_tables t ON r.table_id = t.id
         WHERE r.customer_id = ?
         ORDER BY r.reservation_date DESC, r.reservation_time DESC",
        [$user['id']]
    );
}

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="saas-card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge bg-primary text-white mb-2"><i class="fa-solid fa-sparkles me-1"></i> VIP Dining Seating</span>
                    <h2 class="h4 fw-bold text-white mb-1">Book Your Table Online</h2>
                    <p class="text-muted small mb-0">Smart allocation automatically pairs your party with the best available table.</p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning text-nowrap fw-bold" data-bs-toggle="modal" data-bs-target="#workflowPlayerModal">
                    <i class="fa-solid fa-play-circle me-1"></i> Play Flow
                </button>
            </div>

            <!-- Visual Step-by-Step Data Flow Pipeline -->
            <div class="step-pipeline-nav" id="resPipelineNav">
                <div class="step-pipeline-line-fill" id="resPipelineFill" style="width: 25%;"></div>
                <div class="step-pipeline-item active" id="pipeStep1">
                    <div class="step-pipeline-circle"><i class="fa-solid fa-user"></i></div>
                    <div class="step-pipeline-label">1. Contact</div>
                </div>
                <div class="step-pipeline-item" id="pipeStep2">
                    <div class="step-pipeline-circle"><i class="fa-solid fa-calendar-day"></i></div>
                    <div class="step-pipeline-label">2. Schedule</div>
                </div>
                <div class="step-pipeline-item" id="pipeStep3">
                    <div class="step-pipeline-circle"><i class="fa-solid fa-chair"></i></div>
                    <div class="step-pipeline-label">3. AI Match</div>
                </div>
                <div class="step-pipeline-item" id="pipeStep4">
                    <div class="step-pipeline-circle"><i class="fa-solid fa-check"></i></div>
                    <div class="step-pipeline-label">4. Confirmed</div>
                </div>
            </div>

            <form id="custBookingForm">
                <div class="mb-3">
                    <label class="form-label-saas"><i class="fa-regular fa-user me-1 text-primary"></i> Guest Name</label>
                    <input type="text" name="customer_name" id="resNameInput" class="form-control form-control-saas" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required placeholder="Your full name">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label-saas"><i class="fa-regular fa-envelope me-1 text-primary"></i> Email Address</label>
                        <input type="email" name="customer_email" id="resEmailInput" class="form-control form-control-saas" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required placeholder="name@example.com">
                    </div>
                    <div class="col-6">
                        <label class="form-label-saas"><i class="fa-solid fa-phone me-1 text-primary"></i> Phone Number</label>
                        <input type="tel" name="customer_phone" id="resPhoneInput" class="form-control form-control-saas" required placeholder="+1 555-0100">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <label class="form-label-saas"><i class="fa-solid fa-users me-1 text-warning"></i> Party Size</label>
                        <input type="number" name="guest_count" id="resGuestCount" class="form-control form-control-saas" value="2" min="1" max="14" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label-saas"><i class="fa-regular fa-calendar me-1 text-warning"></i> Date</label>
                        <input type="date" name="reservation_date" id="resDate" class="form-control form-control-saas" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label-saas"><i class="fa-regular fa-clock me-1 text-warning"></i> Time</label>
                        <input type="time" name="reservation_time" id="resTime" class="form-control form-control-saas" value="19:30" required>
                    </div>
                </div>

                <!-- Live Smart Allocation Preview Box -->
                <div class="mb-3">
                    <label class="form-label-saas"><i class="fa-solid fa-wand-magic-sparkles me-1 text-success"></i> AI Seating Allocation Engine</label>
                    <div id="smartTableRecommendation">
                        <!-- Filled by assets/js/reservations.js -->
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label-saas"><i class="fa-regular fa-comment-dots me-1 text-muted"></i> Occasion / Special Requests</label>
                    <textarea name="special_requests" class="form-control form-control-saas" rows="2" placeholder="Anniversary dinner, terrace seating preference, high chair needed..."></textarea>
                </div>

                <!-- Live Data Verification Card for 100% Clarity -->
                <div class="p-3 rounded-3 mb-3" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 0.68rem; letter-spacing: 0.05em;">Instant Booking Data Summary</span>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25" style="font-size:0.65rem;"><i class="fa-solid fa-shield-check me-1"></i> Live Validated</span>
                    </div>
                    <div class="row g-2 text-light" style="font-size: 0.78rem;">
                        <div class="col-6"><i class="fa-solid fa-user-check text-warning me-1"></i> Guest: <span id="summaryGuest" class="fw-semibold text-white"><?= htmlspecialchars($user['name'] ?? 'Guest') ?></span></div>
                        <div class="col-6"><i class="fa-solid fa-phone text-warning me-1"></i> Phone: <span id="summaryPhone" class="fw-semibold text-white">Enter phone</span></div>
                        <div class="col-6"><i class="fa-solid fa-users text-primary me-1"></i> Guests: <span id="summaryParty" class="fw-semibold text-white">2 Guests</span></div>
                        <div class="col-6"><i class="fa-regular fa-calendar-check text-primary me-1"></i> Slot: <span id="summarySlot" class="fw-semibold text-white"><?= date('M j') ?> @ 7:30 PM</span></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-saas w-100 py-3 fs-6 fw-bold" id="btnBookTable">
                    <i class="fa-solid fa-calendar-check me-2"></i> Confirm Instant Reservation
                </button>
            </form>
        </div>
    </div>

    <!-- Right: My Scheduled Bookings -->
    <div class="col-lg-6">
        <div class="saas-card h-100">
            <div class="saas-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="saas-card-title mb-0"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Your Reservations</h5>
                    <small class="text-muted">Past & upcoming dining reservations</small>
                </div>
                <span class="badge bg-dark border border-secondary border-opacity-25 text-muted" id="bookingCountBadge">
                    <?= count($existingBookings) ?> Bookings
                </span>
            </div>
            <div class="saas-card-body p-0" id="bookingListContainer">
                <?php if (empty($existingBookings)): ?>
                    <div class="p-5 text-center text-muted" id="emptyBookingsView">
                        <i class="fa-regular fa-calendar-xmark fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-0">No reservations booked yet.</p>
                        <small>Fill out the form on the left to reserve your table!</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" id="bookingsList">
                        <?php foreach ($existingBookings as $b): ?>
                            <div class="list-group-item bg-transparent border-secondary border-opacity-25 px-4 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-primary font-monospace"><?= htmlspecialchars($b['reservation_code']) ?></span>
                                    <span class="badge-status badge-<?= htmlspecialchars($b['status']) ?>">
                                        <?= ucfirst($b['status']) ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-light small">
                                    <span><i class="fa-regular fa-calendar me-1 text-warning"></i> <?= date('D, M j, Y', strtotime($b['reservation_date'])) ?> at <?= date('g:i A', strtotime($b['reservation_time'])) ?></span>
                                    <span><i class="fa-solid fa-users me-1 text-primary"></i> <?= $b['guest_count'] ?> Guests</span>
                                </div>
                                <?php if ($b['table_number']): ?>
                                    <div class="text-muted small mt-1">
                                        <i class="fa-solid fa-chair me-1"></i> Allocated: <?= htmlspecialchars($b['table_number']) ?> (<?= htmlspecialchars($b['floor_location']) ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/reservations.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('resNameInput');
    const phoneInput = document.getElementById('resPhoneInput');
    const guestInput = document.getElementById('resGuestCount');
    const dateInput = document.getElementById('resDate');
    const timeInput = document.getElementById('resTime');

    const summaryGuest = document.getElementById('summaryGuest');
    const summaryPhone = document.getElementById('summaryPhone');
    const summaryParty = document.getElementById('summaryParty');
    const summarySlot = document.getElementById('summarySlot');

    const pipeStep1 = document.getElementById('pipeStep1');
    const pipeStep2 = document.getElementById('pipeStep2');
    const pipeStep3 = document.getElementById('pipeStep3');
    const pipeStep4 = document.getElementById('pipeStep4');
    const pipeFill = document.getElementById('resPipelineFill');

    function updateLiveSummary() {
        if (summaryGuest && nameInput) summaryGuest.textContent = nameInput.value || 'Guest';
        if (summaryPhone && phoneInput) summaryPhone.textContent = phoneInput.value || 'Enter phone';
        if (summaryParty && guestInput) summaryParty.textContent = `${guestInput.value || 2} Guests`;
        if (summarySlot && dateInput && timeInput) {
            summarySlot.textContent = `${dateInput.value} @ ${timeInput.value}`;
        }

        // Update pipeline step states
        let filled = 25;
        if (phoneInput && phoneInput.value.length > 3) {
            pipeStep1.classList.add('completed');
            pipeStep2.classList.add('active');
            filled = 50;
        } else {
            pipeStep1.classList.remove('completed');
            pipeStep2.classList.remove('active');
        }

        if (guestInput.value && dateInput.value && timeInput.value) {
            pipeStep2.classList.add('completed');
            pipeStep3.classList.add('active');
            filled = 75;
        }

        if (pipeFill) pipeFill.style.width = `${filled}%`;
    }

    if (nameInput) nameInput.addEventListener('input', updateLiveSummary);
    if (phoneInput) phoneInput.addEventListener('input', updateLiveSummary);
    if (guestInput) guestInput.addEventListener('input', updateLiveSummary);
    if (dateInput) dateInput.addEventListener('change', updateLiveSummary);
    if (timeInput) timeInput.addEventListener('change', updateLiveSummary);

    updateLiveSummary();

    // Submission handler with step-by-step processing animation
    document.getElementById('custBookingForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnBookTable');
        btn.disabled = true;

        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> [1/3] Checking availability...';

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());

        setTimeout(async () => {
            btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles fa-spin me-2"></i> [2/3] Locking optimal table...';

            try {
                const res = await fetch('../api/reservations.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    btn.innerHTML = '<i class="fa-solid fa-check me-2"></i> [3/3] Confirmed!';
                    
                    if (pipeFill) pipeFill.style.width = '100%';
                    if (pipeStep3) pipeStep3.classList.add('completed');
                    if (pipeStep4) {
                        pipeStep4.classList.add('active', 'completed');
                    }

                    // Launch modern SaaS Modal
                    if (window.showReservationSuccessModal) {
                        window.showReservationSuccessModal({
                            code: data.reservation_code,
                            table: data.table_id ? `Table #${data.table_id}` : 'Smart Fit Reserved',
                            schedule: `${payload.reservation_date} at ${payload.reservation_time}`
                        });
                    } else if (window.showAppToast) {
                        window.showAppToast('Reservation Booked!', `Code: ${data.reservation_code}`, 'success');
                    }

                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-calendar-check me-2"></i> Confirm Instant Reservation';
                } else {
                    if (window.showAppToast) {
                        window.showAppToast('Booking Error', data.error || 'Server error', 'danger');
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-calendar-check me-2"></i> Confirm Instant Reservation';
                }
            } catch(err) {
                if (window.showAppToast) {
                    window.showAppToast('Network Error', 'Connection failed while booking', 'danger');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-calendar-check me-2"></i> Confirm Instant Reservation';
            }
        }, 500);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
