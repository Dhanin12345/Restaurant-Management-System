/**
 * Restaurant Management System
 * Dashboard Telemetry & Real-Time Poller
 */

document.addEventListener('DOMContentLoaded', function() {
    // Poll telemetry every 15 seconds
    setInterval(updateTelemetry, 15000);

    function updateTelemetry() {
        fetch('../api/dashboard.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.metrics) {
                    const m = data.metrics;
                    // Update table occupancy if element exists
                    const tableOccEl = document.querySelector('[data-telemetry="table-utilization"]');
                    if (tableOccEl) {
                        tableOccEl.textContent = `${m.occupied_tables} / ${m.total_tables}`;
                    }
                }
            })
            .catch(err => console.log('Telemetry sync error', err));
    }
});
