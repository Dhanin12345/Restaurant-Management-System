/**
 * Restaurant Management System
 * Smart Table Allocation Frontend Helper
 */

document.addEventListener('DOMContentLoaded', function() {
    const partyInput = document.getElementById('resGuestCount');
    const dateInput = document.getElementById('resDate');
    const timeInput = document.getElementById('resTime');
    const recommendationBox = document.getElementById('smartTableRecommendation');
    const tableSelect = document.getElementById('resTableSelect');

    if (!partyInput || !dateInput || !timeInput || !recommendationBox) return;

    function fetchRecommendation() {
        const party = partyInput.value || 2;
        const date = dateInput.value;
        const time = timeInput.value;

        if (!date || !time) return;

        recommendationBox.innerHTML = `
            <div class="p-3 rounded-3 text-muted small" style="background: var(--bg-surface-elevated); border: 1px dashed var(--border-color);">
                <i class="fa-solid fa-spinner fa-spin me-2 text-warning"></i> Calculating smart table allocation...
            </div>
        `;

        fetch(`../api/reservations.php?action=recommend&party_size=${party}&date=${date}&time=${time}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.recommended) {
                    const r = data.recommended;
                    recommendationBox.innerHTML = `
                        <div class="p-3 rounded-3" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3);">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-success text-white mb-1"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Smart Recommended Table</span>
                                    <h6 class="text-white mb-0 fw-bold">${r.table_number} (${r.floor_location})</h6>
                                    <small class="text-muted">Capacity: ${r.seating_capacity} seats &bull; ${r.fit_label}</small>
                                </div>
                                <span class="badge bg-dark border border-success text-success py-2 px-3">Best Fit</span>
                            </div>
                        </div>
                    `;
                    // Automatically pre-select table in dropdown if select exists
                    if (tableSelect) {
                        tableSelect.value = r.id;
                    }
                } else {
                    recommendationBox.innerHTML = `
                        <div class="p-3 rounded-3" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
                            <span class="text-danger small"><i class="fa-solid fa-triangle-exclamation me-1"></i> No matching tables available for this time window. Please adjust party size or schedule.</span>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.log('Smart table error', err);
            });
    }

    partyInput.addEventListener('change', fetchRecommendation);
    dateInput.addEventListener('change', fetchRecommendation);
    timeInput.addEventListener('change', fetchRecommendation);

    // Initial check
    fetchRecommendation();
});
