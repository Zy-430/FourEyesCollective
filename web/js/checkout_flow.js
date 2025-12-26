$(function() {
    // Address selection styling
    $('.address-option').on('click', function() {
        $('.address-option').removeClass('selected');
        $(this).addClass('selected');
        var $radio = $(this).find('input[type="radio"]');
        if ($radio.length) $radio.prop('checked', true);
        var $addrErr = $('#addressError');
        if ($addrErr.length) $addrErr.hide();
    });

    async function processPayment() {
        var $submitBtn = $('#submitBtn');
        var $btnText = $('#btnText');
        var $btnLoading = $('#btnLoading');
        var $paymentError = $('#paymentError');
        var $paymentSuccess = $('#paymentSuccess');

        // Reset messages
        $paymentError.hide().text('');
        $paymentSuccess.hide().text('');

        // Validate address
        var $addressSelected = $('input[name="address_id"]:checked');
        if (!$addressSelected.length) {
            showNotification('Please select a shipping address', 'error');
            return;
        }

        // Show processing notification
        showNotification('Payment processing...', 'info');

        // Show loading
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnLoading.show();

        try {
            var formData = new FormData();
            formData.append('address_id', $addressSelected.val());
            formData.append('action', 'checkout');

            var result = await $.ajax({
                url: 'checkout.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            });

            if (result.success) {
                $paymentSuccess.show().text('Redirecting to payment...');
                setTimeout(function() {
                    window.location.href = result.redirect;
                }, 1000);
            } else {
                $paymentError.text(result.message || 'Payment failed. Please try again.').show();
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();
            }
        } catch (error) {
            $paymentError.text('Network error. Please check your connection and try again.').show();
            $submitBtn.prop('disabled', false);
            $btnText.show();
            $btnLoading.hide();
            console.error('Payment error:', error);
        }
    }

    // Bind the payment button
    $(document).on('click', '#submitBtn', function(e){
        e.preventDefault();
        processPayment();
    });

    // Auto-scroll to error if any
    $(window).on('load', function() {
        var $error = $('.error-message:visible');
        if ($error.length) {
            $('html, body').animate({ scrollTop: $error.offset().top - 20 }, 400);
        }
    });

    // Only initialize on checkout page
    if (window.location.pathname.includes('checkout.php')) {
        initializeCheckoutCancellation();
    }
});

/*Function to handle checkout cancellation via AJAX and History API*/
function initializeCheckoutCancellation() {
    var checkoutInProgress = true;
    var paymentSubmitted = false;
    var $cancelBtn = $('#cancelCheckoutBtn');
    var currentUser = $cancelBtn.data('user') || '';
    var skipBeforeUnload = false;

    // Skip beforeunload when adding address
    $(document).on('click', 'a.add-address-link, a[href*="profile_address_add.php"]', function(e) {
        skipBeforeUnload = true;
    });

    // Handle cancel checkout button using AJAX and unified notification
    $(document).on('click', '#cancelCheckoutBtn', function(e) {
        e.preventDefault();
        var confirmMsg = $(this).data('confirm') || 'Are you sure you want to cancel checkout? Your selected items will be returned to cart.';
        if (!confirm(confirmMsg)) return;

        // Send AJAX POST to cancel_checkout.php
        $.ajax({
            url: 'cancel_checkout.php',
            method: 'POST',
            data: { source: 'user_cancel' },
            dataType: 'json'
        }).done(function(resp) {
            if (resp && resp.success) {
                var itemMsg = resp.item_count ? resp.item_count + ' item(s)' : 'items';
                // mark state to avoid duplicate actions
                checkoutInProgress = false;
                paymentSubmitted = false;
                showNotification(itemMsg + ' cancelled checkout and restored to cart.', 'info');
                refreshCartAfterCancellation();
                setTimeout(function() { window.location.href = 'cart.php'; }, 1200);
            } else {
                showNotification(resp && resp.message ? resp.message : 'Checkout cancel failed', 'error');
                setTimeout(function() { window.location.href = 'cart.php'; }, 1200);
            }
        }).fail(function() {
            showNotification('Network error cancelling checkout', 'error');
            setTimeout(function() { window.location.href = 'cart.php'; }, 1200);
        });
    });

    // Handle browser back/forward navigation with History API
    if (typeof history !== 'undefined' && history.pushState) {
        // Push a new state so pressing Back triggers popstate on this page
        history.pushState({ 
            page: 'checkout', 
            timestamp: Date.now() 
        }, '', window.location.href);
        
        // Listen for back/forward navigation
        $(window).on('popstate', function(event) {
            // Check if we're navigating away from checkout
            if (checkoutInProgress && (!event.state || event.state.page !== 'checkout')) {
                if (confirm('Are you sure you want to cancel checkout ? Your selected items will be returned to cart.')) {
                    // Cancel checkout via AJAX
                    $.ajax({
                        url: 'cancel_checkout.php',
                        method: 'POST',
                        data: { source: 'browser_back' },
                        dataType: 'json'
                    }).done(function(resp) {
                        if (resp && resp.success) {
                            var itemMsg = resp.item_count ? resp.item_count + ' item(s)' : 'items';
                            showNotification(itemMsg + ' cancelled checkout and restored to cart.', 'info');
                            refreshCartAfterCancellation();
                            setTimeout(function() { window.location.href = 'cart.php'; }, 1200);
                        } else {
                            showNotification(resp && resp.message ? resp.message : 'Checkout cancel failed', 'error');
                            setTimeout(function() { window.location.href = 'cart.php'; }, 1200);
                        }
                    }).fail(function() {
                        showNotification('Network error cancelling checkout', 'error');
                        setTimeout(function() { window.location.href = 'cart.php'; }, 1200);
                    });
                } else {
                    // Stay on page and restore state
                    history.pushState({ 
                        page: 'checkout', 
                        timestamp: Date.now() 
                    }, '', window.location.href);
                }
            }
        });
    }
    
    // Handle beforeunload to cancel checkout if navigating away
    $(window).on('beforeunload', function(e) {
        // Only trigger if checkout is in progress, payment not submitted, and not skipping
        if (checkoutInProgress && !paymentSubmitted && !skipBeforeUnload) {
            // Send beacon to cancel checkout
            var formData = new FormData();
            formData.append('cancel_reason', 'browser_navigation');
            formData.append('timestamp', Date.now());
            if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
                navigator.sendBeacon('cancel_checkout.php', formData);
            }
        }
    });
    
    var originalProcessPayment = window.processPayment;
    if (typeof originalProcessPayment === 'function') {
        window.processPayment = async function() {
            paymentSubmitted = true;
            return originalProcessPayment.apply(this, arguments);
        };
    }
    
    // Also mark as submitted when clicking submit button directly
    $(document).on('click', '#submitBtn', function() {
        paymentSubmitted = true;
    });
}

/*Helper function to refresh cart after cancellation*/
function refreshCartAfterCancellation() {
    if (typeof refreshCartBadge === 'function') {
        refreshCartBadge();
    }
    if (typeof updateCartCount === 'function') {
        updateCartCount();
    }
}