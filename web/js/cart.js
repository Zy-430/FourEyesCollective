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

        // If no items are selected, show default values as requested:
        // no value for subtotal, RM 0.00 estimated total and FREE shipping
        if ($checked.length === 0) {
            $('#summarySubtotal').text('RM 0.00');
            $('#shippingCost').text('FREE');
            $('#summaryEstimated').text(formatCurrency(0));
            $('#checkoutBtn').text('Proceed to Checkout');
            // Clear any count indicators if present
            $('#summaryCount').text('');
            $('#summaryItemsPlural').text('');

            // Update selectAll state
            const total = $('.item-checkbox').length;
            $('#selectAll').prop('checked', false).prop('indeterminate', false);
            return;
        }

        let subtotal = 0;
        let totalItems = 0;

        $checked.each(function () {
            const $row = $(this).closest('.cart-item-row');
            // Prefer data-price on checkbox; fallback to parsing visible price if missing
            const price = safeNumber($(this).data('price')) || safeNumber($row.find('.cart-item-price').text().replace(/[^0-9.]/g, ''));
            const qty = safeNumber($row.find('.qty-number').text());
            subtotal += price * qty;
            totalItems += qty;
        });

        const shipping = subtotal >= 500 ? 0 : (subtotal === 0 ? 0 : 20);
        const estimated = subtotal + shipping;

        $('#summaryCount').text(totalItems);
        $('#summaryItemsPlural').text(totalItems !== 1 ? 's' : '');
        $('#summarySubtotal').text(formatCurrency(subtotal));
        $('#shippingCost').text(shipping > 0 ? formatCurrency(shipping) : 'FREE');
        $('#summaryEstimated').text(formatCurrency(estimated));

        const checkoutLabel = subtotal > 0 ? 'Proceed to Checkout — ' + formatCurrency(estimated) : 'Proceed to Checkout';
        $('#checkoutBtn').text(checkoutLabel);

        // Update selectAll state
        const total = $('.item-checkbox').length;
        const checkedCount = $checked.length;
        $('#selectAll').prop('checked', total === checkedCount).prop('indeterminate', checkedCount > 0 && checkedCount < total);
    }

    // Select All click - implement tri-state behaviour:
    // If selectAll is indeterminate or currently checked, clicking it will uncheck all items.
    // Otherwise it will select all items.
    $('#selectAll').on('click', function (e) {
        e.preventDefault(); // control the toggle manually
        const el = this;
        const $el = $(this);
        if (el.indeterminate || el.checked) {
            $('.item-checkbox').prop('checked', false);
            $el.prop('checked', false).prop('indeterminate', false);
        } else {
            $('.item-checkbox').prop('checked', true);
            $el.prop('checked', true).prop('indeterminate', false);
        }
        updateSummary();
    });

    // Individual checkbox
    $(document).on('change', '.item-checkbox', function () {
        const total = $('.item-checkbox').length;
        const checked = $('.item-checkbox:checked').length;
        const $selectAll = $('#selectAll');
        $selectAll.prop('checked', total === checked);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
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

        postForm('/page/AJAX/cart_ajax.php', fd)
            .then(resp => {
                if (resp.success) {
                    // Update displayed quantity
                    $row.find('.qty-number').text(qty);

                    // Keep control state and data attributes in sync
                    $row.find('.qty-decrease').prop('disabled', qty <= 1);
                    $row.find('.qty-decrease, .qty-increase').attr('data-qty', qty);

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

        postForm('/page/AJAX/cart_ajax.php', fd)
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

        postForm('/page/AJAX/cart_ajax.php', fd)
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