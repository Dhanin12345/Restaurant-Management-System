<?php
/**
 * Restaurant Management System
 * Shared Footer & Scripts
 */

declare(strict_types=1);
?>
    </main>

    <!-- Global App Footer -->
    <footer class="py-3 px-4 text-center text-muted border-top border-secondary border-opacity-10" style="font-size: 0.8rem; background: var(--bg-surface);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong class="text-light">Restaurant Pro</strong> &mdash; Modern Restaurant Management System
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-dark border border-secondary border-opacity-25 text-muted">PHP 8.2 &bull; MariaDB 10.4 &bull; REST API</span>
                <a href="../README.md" class="text-muted hover-light" target="_blank"><i class="fa-solid fa-file-code me-1"></i> Documentation</a>
            </div>
        </div>
    </footer>
</div> <!-- Close .app-main -->
</div> <!-- Close .app-container -->

<!-- Order Success Modal (Premium SaaS Design) -->
<div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content modal-content-saas">
            <div class="modal-body p-4 text-center">
                <div class="success-icon-pulse">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h4 class="fw-bold text-white mb-1">Order Placed Successfully!</h4>
                <p class="text-muted small mb-4">Ticket transmitted to Kitchen brigade display and recipe stock calculated.</p>

                <div class="order-detail-box text-start mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                        <span class="text-muted small">Ticket Reference</span>
                        <span class="badge-order-number" id="modalOrderNumber">ORD-0000</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Total Charged / Due</span>
                        <span class="h5 fw-bold text-primary mb-0" id="modalOrderTotal">$0.00</span>
                    </div>
                    
                    <div class="pt-2">
                        <div class="pipeline-check-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Sent to Kitchen Display (KDS Live Queue)</span>
                        </div>
                        <div class="pipeline-check-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Recipe ingredients deducted from inventory</span>
                        </div>
                        <div class="pipeline-check-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Assigned to Waiter floor runner</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary-saas px-4" data-bs-dismiss="modal">Done</button>
                    <a href="../customer/orders.php" class="btn btn-primary-saas px-4 fw-bold" id="modalTrackBtn">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Track Order Live
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Reservation Success Modal (Modern SaaS Architecture) -->
<div class="modal fade" id="reservationSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content modal-content-saas">
            <div class="modal-body p-4 text-center">
                <div class="success-icon-pulse mb-3">
                    <i class="fa-solid fa-calendar-check text-success"></i>
                </div>
                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-1 mb-2">Instant Booking Verified</span>
                <h4 class="fw-bold text-white mb-1">Reservation Confirmed!</h4>
                <p class="text-muted small mb-4">Your dining table has been locked in our live seating allocation registry.</p>

                <div class="order-detail-box text-start mb-4" style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1.25rem;">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                        <span class="text-muted small">Confirmation Code</span>
                        <span class="badge-order-number" id="resModalCode" style="font-size: 1rem; color: #f97316; font-family: var(--font-mono); font-weight: 700;">RES-00000</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 rounded" style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255, 255, 255, 0.08);">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">TABLE ASSIGNED</small>
                                <span class="fw-bold text-white small" id="resModalTable"><i class="fa-solid fa-chair text-warning me-1"></i> Smart Fit</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded" style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255, 255, 255, 0.08);">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">SCHEDULE</small>
                                <span class="fw-bold text-white small" id="resModalSchedule"><i class="fa-regular fa-clock text-primary me-1"></i> Today</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-1">
                        <div class="pipeline-check-item d-flex align-items-center gap-2 mb-2 text-light small" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-circle-check text-success"></i>
                            <span>Time-slot dining collision checked & passed</span>
                        </div>
                        <div class="pipeline-check-item d-flex align-items-center gap-2 mb-2 text-light small" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-circle-check text-success"></i>
                            <span>Floor host station notified automatically</span>
                        </div>
                        <div class="pipeline-check-item d-flex align-items-center gap-2 text-light small" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-circle-check text-success"></i>
                            <span>Audit log recorded in compliance journal</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary-saas px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary-saas px-4 fw-bold" onclick="window.location.reload()">
                        <i class="fa-solid fa-list-check me-1"></i> View My Bookings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/workflow_player_modal.php'; ?>

<!-- Floating Toast Container -->
<div class="saas-toast-container" id="saasToastContainer"></div>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global App Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');
    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function() {
            appSidebar.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992 && 
                !appSidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) && 
                appSidebar.classList.contains('show')) {
                appSidebar.classList.remove('show');
            }
        });
    }

    // Audio Alert Synthesizer for Kitchen & Cashier notifications (Web Audio API)
    window.playNotificationChime = function(type = 'success') {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'alert') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(440, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.35);
            } else {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
                gain.gain.setValueAtTime(0.12, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.3);
            }
        } catch (e) {
            console.log('Audio chime not supported or muted');
        }
    };

    // Global Floating Toast Notification Engine
    window.showAppToast = function(title, message, type = 'info') {
        let container = document.getElementById('saasToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'saasToastContainer';
            container.className = 'saas-toast-container';
            document.body.appendChild(container);
        }
        
        const iconMap = {
            success: 'fa-circle-check text-success',
            warning: 'fa-triangle-exclamation text-warning',
            danger: 'fa-circle-xmark text-danger',
            info: 'fa-circle-info text-info'
        };
        const iconClass = iconMap[type] || iconMap.info;

        const toast = document.createElement('div');
        toast.className = `saas-toast toast-${type}`;
        toast.innerHTML = `
            <i class="fa-solid ${iconClass} fa-lg"></i>
            <div class="flex-grow-1">
                <div class="fw-bold text-white small mb-0">${title}</div>
                <div class="text-muted small" style="font-size:0.78rem;">${message}</div>
            </div>
            <button type="button" class="btn-close btn-close-white btn-sm ms-2" style="font-size:0.65rem;" onclick="this.parentElement.remove()"></button>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(15px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    // Universal Order Success Modal Launcher
    window.showOrderSuccessModal = function({ orderNumber, total, trackUrl = '../customer/orders.php' }) {
        const modalEl = document.getElementById('orderSuccessModal');
        if (!modalEl) {
            window.showAppToast('Order Placed!', `Order #${orderNumber} for $${total} sent to kitchen.`, 'success');
            return;
        }
        const orderNumEl = document.getElementById('modalOrderNumber');
        const orderTotEl = document.getElementById('modalOrderTotal');
        const trackBtn = document.getElementById('modalTrackBtn');

        if (orderNumEl) orderNumEl.textContent = orderNumber;
        if (orderTotEl) orderTotEl.textContent = '$' + parseFloat(total).toFixed(2);
        if (trackBtn) trackBtn.href = trackUrl;

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        if (window.playNotificationChime) window.playNotificationChime('success');
    };

    // Universal Reservation Success Modal Launcher
    window.showReservationSuccessModal = function({ code, table, schedule }) {
        const modalEl = document.getElementById('reservationSuccessModal');
        if (!modalEl) {
            window.showAppToast('Reservation Booked!', `Code: ${code} &bull; Table: ${table}`, 'success');
            return;
        }
        const codeEl = document.getElementById('resModalCode');
        const tableEl = document.getElementById('resModalTable');
        const schedEl = document.getElementById('resModalSchedule');

        if (codeEl) codeEl.textContent = code;
        if (tableEl) tableEl.innerHTML = `<i class="fa-solid fa-chair text-warning me-1"></i> ${table}`;
        if (schedEl) schedEl.innerHTML = `<i class="fa-regular fa-clock text-primary me-1"></i> ${schedule}`;

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        if (window.playNotificationChime) window.playNotificationChime('success');
    };
});
</script>

<!-- Interactive Step-by-Step Flow Player Engine -->
<script src="../assets/js/workflow_player.js"></script>

</body>
</html>
