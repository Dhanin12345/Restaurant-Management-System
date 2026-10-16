/**
 * Restaurant Management System
 * Interactive Step-by-Step Data Flow Simulator Engine
 */
document.addEventListener('DOMContentLoaded', function() {
    const workflowStages = [
        {
            phase: 'Phase 1 of 5',
            title: 'Customer Reservation & AI Table Fit',
            desc: 'A guest books a party online. The AI engine searches available capacity, checks a ±2hr collision window, and locks the table.',
            pill: 'TABLES: Available ➔ Reserved',
            ui: 'Guest selects Party Size (4) and Date/Time. Live AI recommends Table T-03 (VIP Lounge, 4 seats) with 0 wasted seats.',
            sql: 'SELECT * FROM dining_tables WHERE seating_capacity >= 4 AND status != "maintenance" ... UPDATE dining_tables SET status="reserved" ...',
            metrics: [
                { label: 'Time Collision Check', value: '0 Overlaps Found', color: 'success' },
                { label: 'Seat Fit Score', value: '100% (0 Waste)', color: 'warning' },
                { label: 'Host Desk Alert', value: 'Pushed Live', color: 'info' }
            ],
            portal: '../customer/reservation.php',
            portalLabel: 'Open Reservation Portal'
        },
        {
            phase: 'Phase 2 of 5',
            title: 'POS Order Placement & Modifier Check',
            desc: 'Waiter or Customer selects dishes, enters allergy notes/coupons, and fires the electronic ticket to the POS queue.',
            pill: 'ORDERS: Draft ➔ Pending (Ticket Created)',
            ui: 'Waiter taps Truffle Pasta & Margherita Pizza. System validates stock quantities, computes 8.5% tax, and generates order reference.',
            sql: 'INSERT INTO orders (order_number, total_amount, status) VALUES ("ORD-2026", 48.50, "pending") ... INSERT INTO order_items ...',
            metrics: [
                { label: 'Ticket Generation', value: 'Instant Ticket #', color: 'primary' },
                { label: 'Tax & Subtotal Calc', value: 'Automatic PDO', color: 'success' },
                { label: 'Dining Table State', value: 'Occupied', color: 'warning' }
            ],
            portal: '../waiter/create_order.php',
            portalLabel: 'Open POS Order Builder'
        },
        {
            phase: 'Phase 3 of 5',
            title: 'Real-Time Kitchen Display (KDS)',
            desc: 'The ticket appears instantly on the Kitchen Display Brigade station with visual color-coded urgency and audio chimes.',
            pill: 'KDS: Pending ➔ Preparing ➔ Ready',
            ui: 'Chef clicks "Start Preparing". Preparation timer begins counting. Once cooked, Chef clicks "Mark Ready" to alert floor runners.',
            sql: 'UPDATE orders SET status="preparing" WHERE id = ? ... INSERT INTO order_status_history ... Web Audio Chime Triggered',
            metrics: [
                { label: 'Target Cook Time', value: '15 Minutes', color: 'warning' },
                { label: 'Brigade Routing', value: 'Hot Line Station', color: 'info' },
                { label: 'KDS Refresh Pulse', value: 'Every 5 Seconds', color: 'success' }
            ],
            portal: '../kitchen/kitchen_orders.php',
            portalLabel: 'Open Kitchen Display (KDS)'
        },
        {
            phase: 'Phase 4 of 5',
            title: 'Automated Recipe Inventory Deduction (BOM)',
            desc: 'As dishes are cooked, the Bill-of-Materials algorithm automatically subtracts grams/units of raw ingredients from inventory.',
            pill: 'STOCK: Ingredients Auto-Deducted',
            ui: 'Margherita Pizza deduction: 250g Flour, 150g Mozzarella, 80g Tomato Puree subtracted. Low-stock notifications generated if threshold breached.',
            sql: 'UPDATE ingredients SET current_stock = current_stock - ? WHERE id = ? ... INSERT INTO stock_transactions (transaction_type="consumption") ...',
            metrics: [
                { label: 'Ingredients Deducted', value: '3 Raw Items', color: 'danger' },
                { label: 'COGS Food Cost %', value: 'Calculated Live', color: 'warning' },
                { label: 'Audit Trail', value: 'Immutable Log', color: 'success' }
            ],
            portal: '../admin/inventory.php',
            portalLabel: 'Open Recipe & Inventory Desk'
        },
        {
            phase: 'Phase 5 of 5',
            title: 'Cashier Settlement & Automatic Table Reset',
            desc: 'The diner completes the meal. Cashier processes split/cash/card payment, prints itemized invoice, and frees the table.',
            pill: 'BILLING: Settled ➔ Table Available',
            ui: 'Cashier selects payment method (Card/Cash), applies optional tip/discount, and generates invoice. Dining table flips back to green (Available).',
            sql: 'INSERT INTO payments (amount, method, status="completed") ... UPDATE orders SET payment_status="paid" ... UPDATE dining_tables SET status="available"',
            metrics: [
                { label: 'Payment Status', value: 'Settled & Verified', color: 'success' },
                { label: 'Table Auto-Reset', value: 'Ready for Next Diner', color: 'info' },
                { label: 'Daily Revenue Chart', value: 'Updated Live', color: 'warning' }
            ],
            portal: '../cashier/dashboard.php',
            portalLabel: 'Open Cashier POS Desk'
        }
    ];

    let currentWorkflowIndex = 0;
    let autoPlayTimer = null;
    let autoPlayProgress = 0;

    const railContainer = document.getElementById('stageTrackerRail');
    const stageBadge = document.getElementById('stageBadge');
    const stageTitle = document.getElementById('stageTitle');
    const stageDesc = document.getElementById('stageDescription');
    const stageDbPill = document.getElementById('stageDbPill');
    const stageUiAction = document.getElementById('stageUiAction');
    const stageDbAction = document.getElementById('stageDbAction');
    const stageMetricsRow = document.getElementById('stageMetricsRow');
    const stagePortalLink = document.getElementById('stagePortalLink');
    const stageProgressBarFill = document.getElementById('stageProgressBarFill');
    const flowPrevBtn = document.getElementById('flowPrevBtn');
    const flowNextBtn = document.getElementById('flowNextBtn');
    const flowAutoPlayBtn = document.getElementById('flowAutoPlayBtn');

    if (!railContainer) return;

    // Build rail pills
    railContainer.innerHTML = '';
    workflowStages.forEach((s, idx) => {
        const pill = document.createElement('div');
        pill.className = `stage-rail-pill ${idx === 0 ? 'active' : ''}`;
        pill.id = `railPill_${idx}`;
        pill.innerHTML = `
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge ${idx === 0 ? 'bg-primary' : 'bg-dark border border-secondary border-opacity-50'} rounded-pill" style="font-size:0.65rem;">Step ${idx + 1}</span>
                <span class="text-white small fw-bold text-truncate">${s.title.split('&')[0].trim()}</span>
            </div>
            <div class="text-muted" style="font-size: 0.7rem;">${s.pill.split('➔')[1] || s.pill}</div>
        `;
        pill.addEventListener('click', () => {
            stopAutoPlay();
            renderWorkflowStage(idx);
        });
        railContainer.appendChild(pill);
    });

    function renderWorkflowStage(idx) {
        currentWorkflowIndex = idx;
        const data = workflowStages[idx];

        workflowStages.forEach((_, i) => {
            const el = document.getElementById(`railPill_${i}`);
            if (!el) return;
            el.classList.remove('active', 'completed');
            if (i === idx) el.classList.add('active');
            else if (i < idx) el.classList.add('completed');
        });

        if (stageBadge) stageBadge.textContent = data.phase;
        if (stageTitle) stageTitle.textContent = data.title;
        if (stageDesc) stageDesc.textContent = data.desc;
        if (stageDbPill) stageDbPill.innerHTML = `<i class="fa-solid fa-database text-warning"></i> <span>${data.pill}</span>`;
        if (stageUiAction) stageUiAction.textContent = data.ui;
        if (stageDbAction) stageDbAction.textContent = data.sql;

        if (stagePortalLink) {
            stagePortalLink.href = data.portal;
            stagePortalLink.innerHTML = `<i class="fa-solid fa-arrow-up-right-from-square me-1"></i> ${data.portalLabel}`;
        }

        if (stageMetricsRow) {
            stageMetricsRow.innerHTML = data.metrics.map(m => `
                <div class="col-sm-4">
                    <div class="p-2 rounded" style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255, 255, 255, 0.08);">
                        <small class="text-muted d-block" style="font-size:0.68rem;">${m.label.toUpperCase()}</small>
                        <span class="fw-bold text-${m.color} small">${m.value}</span>
                    </div>
                </div>
            `).join('');
        }

        if (flowPrevBtn) flowPrevBtn.disabled = idx === 0;
        if (flowNextBtn) {
            if (idx === workflowStages.length - 1) {
                flowNextBtn.innerHTML = '<i class="fa-solid fa-rotate-left me-1"></i> Restart Flow';
            } else {
                flowNextBtn.innerHTML = 'Next Step <i class="fa-solid fa-chevron-right ms-1"></i>';
            }
        }
    }

    if (flowPrevBtn) {
        flowPrevBtn.addEventListener('click', () => {
            stopAutoPlay();
            if (currentWorkflowIndex > 0) renderWorkflowStage(currentWorkflowIndex - 1);
        });
    }

    if (flowNextBtn) {
        flowNextBtn.addEventListener('click', () => {
            stopAutoPlay();
            if (currentWorkflowIndex < workflowStages.length - 1) {
                renderWorkflowStage(currentWorkflowIndex + 1);
            } else {
                renderWorkflowStage(0);
            }
        });
    }

    function stopAutoPlay() {
        if (autoPlayTimer) {
            clearInterval(autoPlayTimer);
            autoPlayTimer = null;
        }
        if (stageProgressBarFill) stageProgressBarFill.style.width = '0%';
        if (flowAutoPlayBtn) {
            flowAutoPlayBtn.innerHTML = '<i class="fa-solid fa-play me-1"></i> Auto-Play';
            flowAutoPlayBtn.className = 'btn btn-sm btn-primary-saas fw-bold px-3';
        }
    }

    function startAutoPlay() {
        stopAutoPlay();
        if (flowAutoPlayBtn) {
            flowAutoPlayBtn.innerHTML = '<i class="fa-solid fa-pause me-1"></i> Pause';
            flowAutoPlayBtn.className = 'btn btn-sm btn-warning-saas fw-bold px-3 text-dark';
        }

        autoPlayProgress = 0;
        const stepDuration = 3500;
        const intervalTime = 50;
        const increment = (intervalTime / stepDuration) * 100;

        autoPlayTimer = setInterval(() => {
            autoPlayProgress += increment;
            if (stageProgressBarFill) stageProgressBarFill.style.width = `${Math.min(100, autoPlayProgress)}%`;

            if (autoPlayProgress >= 100) {
                autoPlayProgress = 0;
                if (currentWorkflowIndex < workflowStages.length - 1) {
                    renderWorkflowStage(currentWorkflowIndex + 1);
                } else {
                    renderWorkflowStage(0);
                }
            }
        }, intervalTime);
    }

    if (flowAutoPlayBtn) {
        flowAutoPlayBtn.addEventListener('click', () => {
            if (autoPlayTimer) stopAutoPlay();
            else startAutoPlay();
        });
    }

    // Initialize on Stage 0
    renderWorkflowStage(0);

    // Stop auto play when modal hides
    const modalEl = document.getElementById('workflowPlayerModal');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', stopAutoPlay);
    }
});
