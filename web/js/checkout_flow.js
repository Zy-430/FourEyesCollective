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
});