/**
 * Restaurant Management System
 * Kitchen Display System (KDS) - Real-time Zero-Refresh Pipeline
 */

class KitchenDisplaySystem {
    constructor() {
        this.pollInterval = 5000; // 5 seconds
        this.knownOrderIds = new Set();
        this.init();
    }

    init() {
        this.poll();
        setInterval(() => this.poll(), this.pollInterval);
    }

    async poll() {
        try {
            const res = await fetch('../api/orders.php?action=kitchen_stream');
            const data = await res.json();

            if (data.success && data.orders) {
                this.handleOrders(data.orders);
            }
        } catch (e) {
            console.log('KDS polling error', e);
        }
    }

    handleOrders(orders) {
        // Check for new orders to trigger audio chime
        let hasNewTicket = false;
        orders.forEach(o => {
            if (!this.knownOrderIds.has(o.id) && o.status === 'pending') {
                hasNewTicket = true;
            }
            this.knownOrderIds.add(o.id);
        });

        if (hasNewTicket && window.playNotificationChime) {
            window.playNotificationChime('alert');
        }

        this.renderKanban(orders);
    }

    renderKanban(orders) {
        const pendingCol = document.getElementById('kdsColPending');
        const preparingCol = document.getElementById('kdsColPreparing');
        const readyCol = document.getElementById('kdsColReady');

        if (!pendingCol || !preparingCol || !readyCol) return;

        const pendingOrders = orders.filter(o => o.status === 'pending' || o.status === 'accepted');
        const preparingOrders = orders.filter(o => o.status === 'preparing');
        const readyOrders = orders.filter(o => o.status === 'ready');

        // Update column count badges
        const countP = document.getElementById('badgeCountPending');
        const countPr = document.getElementById('badgeCountPreparing');
        const countR = document.getElementById('badgeCountReady');

        if (countP) countP.textContent = pendingOrders.length;
        if (countPr) countPr.textContent = preparingOrders.length;
        if (countR) countR.textContent = readyOrders.length;

        pendingCol.innerHTML = pendingOrders.length === 0 ? this.emptyState('No pending tickets') : pendingOrders.map(o => this.ticketCard(o, 'accept')).join('');
        preparingCol.innerHTML = preparingOrders.length === 0 ? this.emptyState('No orders in preparation') : preparingOrders.map(o => this.ticketCard(o, 'ready')).join('');
        readyCol.innerHTML = readyOrders.length === 0 ? this.emptyState('No orders awaiting runner') : readyOrders.map(o => this.ticketCard(o, 'served')).join('');
    }

    ticketCard(o, nextAction) {
        const actionBtn = nextAction === 'accept'
            ? `<button class="btn btn-sm btn-primary-saas w-100" onclick="kds.updateStatus(${o.id}, 'preparing')"><i class="fa-solid fa-fire me-1"></i> Start Cooking</button>`
            : (nextAction === 'ready'
                ? `<button class="btn btn-sm btn-success w-100 text-white fw-bold" onclick="kds.updateStatus(${o.id}, 'ready')"><i class="fa-solid fa-bell me-1"></i> Mark Ready (Pass)</button>`
                : `<button class="btn btn-sm btn-secondary-saas w-100" onclick="kds.updateStatus(${o.id}, 'served')"><i class="fa-solid fa-check-double me-1"></i> Food Served</button>`
            );

        const itemsHtml = o.items.map(it => `
            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10 small">
                <div>
                    <span class="fw-bold text-light">${it.quantity}x</span>
                    <span class="text-light ms-1">${it.item_name}</span>
                    ${it.special_instructions ? `<div class="text-warning small fst-italic ms-3">&bull; ${it.special_instructions}</div>` : ''}
                </div>
            </div>
        `).join('');

        const timeClass = o.elapsed_minutes > 20 ? 'text-danger fw-bold' : (o.elapsed_minutes > 10 ? 'text-warning' : 'text-success');

        return `
            <div class="kds-ticket mb-3">
                <div class="kds-ticket-header">
                    <div>
                        <span class="fw-bold text-primary">${o.order_number}</span>
                        <span class="badge bg-dark border border-secondary border-opacity-25 ms-1">${o.table_number || o.order_type.toUpperCase()}</span>
                    </div>
                    <div class="kds-timer ${timeClass}">
                        <i class="fa-regular fa-clock me-1"></i> ${o.elapsed_minutes}m ago
                    </div>
                </div>

                <div class="mb-3">
                    ${itemsHtml}
                </div>

                ${o.notes ? `<div class="p-2 mb-2 rounded bg-dark small text-warning"><i class="fa-solid fa-circle-exclamation me-1"></i> ${o.notes}</div>` : ''}

                ${actionBtn}
            </div>
        `;
    }

    emptyState(msg) {
        return `<div class="text-center py-4 text-muted small opacity-50"><i class="fa-solid fa-check-circle fa-2x mb-2"></i><div>${msg}</div></div>`;
    }

    async updateStatus(orderId, newStatus) {
        try {
            const res = await fetch('../api/orders.php?action=update_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: newStatus })
            });
            const data = await res.json();
            if (data.success) {
                if (window.playNotificationChime) window.playNotificationChime('success');
                this.poll(); // Instant refresh
            } else {
                alert('Status update failed: ' + data.error);
            }
        } catch (e) {
            console.log('Update status error', e);
        }
    }
}

window.kds = new KitchenDisplaySystem();
