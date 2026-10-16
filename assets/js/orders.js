/**
 * Restaurant Management System
 * POS & Cart Engine
 */

class POSCart {
    constructor() {
        this.cart = [];
        this.taxRate = 8.5; // percent
        this.discountAmount = 0.00;
        this.init();
    }

    init() {
        this.bindEvents();
        this.render();
    }

    addItem(id, name, price, image = '') {
        const existing = this.cart.find(i => i.id === id);
        if (existing) {
            existing.quantity += 1;
        } else {
            this.cart.push({
                id: id,
                name: name,
                price: parseFloat(price),
                quantity: 1,
                instructions: '',
                image: image
            });
        }
        this.render();
        if (window.playNotificationChime) window.playNotificationChime('success');
    }

    updateQuantity(id, change) {
        const item = this.cart.find(i => i.id === id);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                this.cart = this.cart.filter(i => i.id !== id);
            }
        }
        this.render();
    }

    setInstructions(id, note) {
        const item = this.cart.find(i => i.id === id);
        if (item) {
            item.instructions = note;
        }
    }

    clear() {
        this.cart = [];
        this.discountAmount = 0.00;
        this.render();
    }

    getSubtotal() {
        return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    }

    getTax() {
        const base = Math.max(0, this.getSubtotal() - this.discountAmount);
        return (base * this.taxRate) / 100.0;
    }

    getTotal() {
        return Math.max(0, this.getSubtotal() - this.discountAmount) + this.getTax();
    }

    render() {
        const container = document.getElementById('cartItemsContainer');
        const countBadge = document.getElementById('cartCountBadge');
        const subtotalEl = document.getElementById('cartSubtotal');
        const taxEl = document.getElementById('cartTax');
        const totalEl = document.getElementById('cartTotal');
        const discountEl = document.getElementById('cartDiscount');
        const checkoutBtn = document.getElementById('cartSubmitBtn');

        if (!container) return;

        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        if (countBadge) countBadge.textContent = totalItems;

        if (this.cart.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-cart-shopping fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">Your order ticket is empty.</p>
                    <small>Click menu items on the left to add.</small>
                </div>
            `;
            if (checkoutBtn) checkoutBtn.disabled = true;
        } else {
            container.innerHTML = this.cart.map(item => `
                <div class="p-3 mb-2 rounded-3" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="fw-bold text-light">${item.name}</div>
                        <div class="fw-bold text-primary">$${(item.price * item.quantity).toFixed(2)}</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-secondary-saas py-0 px-2" onclick="posCart.updateQuantity(${item.id}, -1)">-</button>
                            <span class="fw-bold text-light px-2">${item.quantity}</span>
                            <button class="btn btn-sm btn-secondary-saas py-0 px-2" onclick="posCart.updateQuantity(${item.id}, 1)">+</button>
                        </div>
                        <input type="text" class="form-control form-control-sm form-control-saas py-0 px-2" 
                               style="max-width: 140px; font-size: 0.75rem;" 
                               placeholder="Special note..." 
                               value="${item.instructions || ''}" 
                               onchange="posCart.setInstructions(${item.id}, this.value)">
                    </div>
                </div>
            `).join('');
            if (checkoutBtn) checkoutBtn.disabled = false;
        }

        if (subtotalEl) subtotalEl.textContent = '$' + this.getSubtotal().toFixed(2);
        if (taxEl) taxEl.textContent = '$' + this.getTax().toFixed(2);
        if (discountEl) discountEl.textContent = '-$' + this.discountAmount.toFixed(2);
        if (totalEl) totalEl.textContent = '$' + this.getTotal().toFixed(2);
    }

    bindEvents() {
        const submitBtn = document.getElementById('cartSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitOrder());
        }

        const couponBtn = document.getElementById('applyCouponBtn');
        if (couponBtn) {
            couponBtn.addEventListener('click', () => {
                const code = document.getElementById('couponInput')?.value.trim().toUpperCase();
                if (code === 'WELCOME10') {
                    this.discountAmount = (this.getSubtotal() * 0.10);
                    if (window.showAppToast) {
                        window.showAppToast('Coupon Applied!', 'WELCOME10 applied: 10% discount subtracted.', 'success');
                    }
                    this.render();
                } else if (code === 'FLAT5OFF') {
                    this.discountAmount = 5.00;
                    if (window.showAppToast) {
                        window.showAppToast('Coupon Applied!', 'FLAT5OFF applied: $5.00 discount subtracted.', 'success');
                    }
                    this.render();
                } else if (code) {
                    if (window.showAppToast) {
                        window.showAppToast('Invalid Promo Code', 'Please enter a valid coupon (e.g. WELCOME10).', 'warning');
                    }
                }
            });
        }
    }

    async submitOrder() {
        if (this.cart.length === 0) return;

        const tableSelect = document.getElementById('orderTableSelect');
        const typeSelect = document.getElementById('orderTypeSelect');
        const notesInput = document.getElementById('orderNotesInput');
        const couponInput = document.getElementById('couponInput');
        const submitBtn = document.getElementById('cartSubmitBtn');

        const payload = {
            table_id: tableSelect ? tableSelect.value : null,
            order_type: typeSelect ? typeSelect.value : 'dine_in',
            notes: notesInput ? notesInput.value : '',
            coupon_code: couponInput ? couponInput.value : '',
            items: this.cart.map(i => ({
                menu_item_id: i.id,
                quantity: i.quantity,
                special_instructions: i.instructions
            }))
        };

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Transmitting Ticket...';

        try {
            const res = await fetch('../api/orders.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                this.clear();
                if (typeof window.showOrderSuccessModal === 'function') {
                    window.showOrderSuccessModal({
                        orderNumber: data.order_number,
                        total: data.final_amount,
                        trackUrl: '../customer/orders.php'
                    });
                } else if (window.showAppToast) {
                    window.showAppToast('Order Created!', `Order #${data.order_number} sent to kitchen.`, 'success');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-arrow-right me-2"></i> Place Online Order';
            } else {
                if (window.showAppToast) {
                    window.showAppToast('Order Error', data.error || 'Failed to place order.', 'danger');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-arrow-right me-2"></i> Place Online Order';
            }
        } catch (e) {
            if (window.showAppToast) {
                window.showAppToast('Network Error', 'Server connection failure while placing order.', 'danger');
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-arrow-right me-2"></i> Place Online Order';
        }
    }
}

window.posCart = new POSCart();
