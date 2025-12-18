$(function () {
    const $cartForm = $('#cartForm');

    function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(res => {
            const ct = res.headers.get('content-type') || '';
            if (ct.indexOf('application/json') > -1) return res.json();
            return res.text().then(text => {
                try { return JSON.parse(text); } catch (e) { return { success: false, message: 'Unexpected server response' }; }
            });
        });
    }

    function formatCurrency(amount) {
        const n = Number(amount) || 0;
        return 'RM ' + n.toFixed(2);
    }

    function safeNumber(v) { return Number(v) || 0; }

    function updateSummary() {
        const $checked = $('.item-checkbox:checked');
        let subtotal = 0;
        let totalItems = 0;
        let rowsToConsider = $checked.length ? $checked : $('.item-checkbox');

        rowsToConsider.each(function () {
            const $row = $(this).closest('.cart-item-row');
            const price = safeNumber($row.find('.price-each').data('price'));
            const qty = safeNumber($row.find('.qty-number').text());
            subtotal += price * qty;
            totalItems += qty;
        });

        const shipping = subtotal >= 500 ? 0 : (subtotal === 0 ? 0 : 20);
        const estimated = subtotal + shipping;

        $('#summaryCount').text(totalItems);
        $('#summaryItemsPlural').text(totalItems !== 1 ? 's' : '');
        $('#summarySubtotal').text(formatCurrency(subtotal));
        $('#shippingCost').text(shipping > 0 ? formatCurrency(shipping) : (subtotal === 0 ? formatCurrency(0) : 'FREE'));
        $('#summaryEstimated').text(formatCurrency(estimated));

        const checkoutLabel = subtotal > 0 ? 'Proceed to Checkout — ' + formatCurrency(estimated) : 'Proceed to Checkout';
        $('#checkoutBtn').text(checkoutLabel);
    }

    // Select All toggle
    $('#selectAll').on('change', function () {
        $('.item-checkbox').prop('checked', this.checked);
        updateSummary();
    });

    // Individual checkbox
    $(document).on('change', '.item-checkbox', function () {
        const total = $('.item-checkbox').length;
        const checked = $('.item-checkbox:checked').length;
        $('#selectAll').prop('checked', total === checked);
        updateSummary();
    });

    // Quantity Increase / Decrease
    $(document).on('click', '.qty-increase, .qty-decrease', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const cartId = $btn.data('cart-id');
        const $row = $btn.closest('.cart-item-row');
        let qty = safeNumber($row.find('.qty-number').text());

        if ($btn.hasClass('qty-increase')) qty++;
        else qty = Math.max(1, qty - 1);

        const fd = new FormData();
        fd.append('action', 'update');
        fd.append('cart_item_id', cartId);
        fd.append('quantity', qty);

        postForm('/page/cart.php', fd)
            .then(resp => {
                if (resp.success) {
                    $row.find('.qty-number').text(qty);
                    $row.find('.price-each').data('price');
                    // Update item total display
                    const price = safeNumber($row.find('.price-each').data('price'));
                    $row.find('.item-total').text(formatCurrency(price * qty));
                    showNotification(resp.message || 'Quantity updated', 'success');
                    updateSummary();
                    if (typeof refreshCartBadge === 'function') refreshCartBadge();
                } else {
                    showNotification(resp.message || 'Unable to update quantity', 'error');
                }
            })
            .catch(() => showNotification('Network error. Please try again.', 'error'));
    });

    // Remove item
    $(document).on('click', '.remove-item', function (e) {
        e.preventDefault();
        const cartId = $(this).data('cart-id');
        const $row = $(this).closest('.cart-item-row');

        if (!confirm('Remove this item from cart?')) return;

        const fd = new FormData();
        fd.append('action', 'remove');
        fd.append('cart_item_id', cartId);

        postForm('/page/cart.php', fd)
            .then(resp => {
                if (resp.success) {
                    $row.remove();
                    showNotification(resp.message || 'Item removed', 'success');
                    updateSummary();
                    if (typeof refreshCartBadge === 'function') refreshCartBadge();
                    // If no items left, reload to show empty state properly
                    if ($('.cart-item-row').length === 0) location.reload();
                } else {
                    showNotification(resp.message || 'Unable to remove item', 'error');
                }
            })
            .catch(() => showNotification('Network error. Please try again.', 'error'));
    });

    // Checkout
    $('#checkoutBtn').on('click', function () {
        const selected = $('.item-checkbox:checked');
        if (!selected.length) {
            showNotification('Please select items to checkout', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'checkout');
        selected.each(function () { fd.append('selected_items[]', $(this).val()); });

        postForm('/page/cart.php', fd)
            .then(resp => {
                if (resp.success) {
                    // If server requests redirect, follow it
                    if (resp.redirect) {
                        window.location.href = resp.redirect;
                        return;
                    }
                    showNotification(resp.message || 'Proceeding to checkout', 'success');
                } else {
                    showNotification(resp.message || 'Checkout failed', 'error');
                }
            })
            .catch(() => showNotification('Network error. Please try again.', 'error'));
    });

    // Initial summary
    updateSummary();
});