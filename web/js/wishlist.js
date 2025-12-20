/**
 * Toggle wishlist (add / remove)
 */
function toggleWishlist(productId, buttonElement) {

    // Check login state from <body data-logged-in="1">
    const isLoggedIn = document.body.dataset.loggedIn === "1";

    if (!isLoggedIn) {
        if (confirm('You need to login to manage wishlist. Go to login page?')) {
            const currentUrl = encodeURIComponent(window.location.href);
            window.location.href = '/page/login.php?redirect=' + currentUrl;
        }
        return;
    }

    $.post('/page/ajax/wishlist_ajax.php', {
        action: 'toggle',
        product_id: productId
    }, function (response) {

        if (!response || !response.success) {
            showNotification(
                (response && response.message) || 'Error updating wishlist',
                'error'
            );
            return;
        }

        // Update heart icon
        const icon = $(buttonElement).find('i');

        if (response.is_in_wishlist) {
            icon.removeClass('far').addClass('fas').css('color', '#e74c3c');
            showNotification('Added to wishlist!', 'success');
        } else {
            icon.removeClass('fas').addClass('far').css('color', '#333');
            showNotification('Removed from wishlist', 'success');
        }

        // If on wishlist page, remove card when item removed
        if (
            window.location.pathname.includes('wishlist.php') &&
            !response.is_in_wishlist
        ) {
            $(buttonElement).closest('.product-card').fadeOut(300, function () {
                $(this).remove();

                if ($('.product-card').length === 0) {
                    location.reload();
                }
            });
        }

    }, 'json').fail(function () {
        showNotification('Network error. Please try again.', 'error');
    });
}

/**
 * On page load:
 * - Check wishlist status
 * - Update heart icons
 * - Attach click handler
 */
$(document).ready(function () {

    const isLoggedIn = document.body.dataset.loggedIn === "1";

    if (isLoggedIn) {

        const productIds = [];

        $('.wishlist-btn[data-product-id]').each(function () {
            productIds.push($(this).data('product-id'));
        });

        if (productIds.length > 0) {
            $.post('/page/ajax/wishlist_ajax.php', {
                action: 'check_status',
                product_ids: productIds
            }, function (response) {

                if (!response || !response.success) return;

                Object.keys(response.wishlist_status).forEach(productId => {

                    const isInWishlist = response.wishlist_status[productId];
                    const btn = $(`.wishlist-btn[data-product-id="${productId}"]`);
                    const icon = btn.find('i');

                    if (isInWishlist) {
                        icon.removeClass('far').addClass('fas').css('color', '#e74c3c');
                    } else {
                        icon.removeClass('fas').addClass('far').css('color', '#333');
                    }
                });

            }, 'json');
        }
    }

    // Click handler (delegated)
    $(document).on('click', '.wishlist-btn', function (e) {
        e.preventDefault();
        toggleWishlist($(this).data('product-id'), this);
    });
});

/**
 * Simple toast notification (only define if not already present)
 */
if (typeof window.showNotification !== 'function') {
    window.showNotification = function (message, type = 'success') {

        let container = document.getElementById('notification-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            document.body.appendChild(container);
        }

        const note = document.createElement('div');
        note.className = 'notification' + (type === 'error' ? ' error' : '');
        note.textContent = message;

        container.appendChild(note);

        setTimeout(() => {
            note.style.opacity = '0';
            setTimeout(() => note.remove(), 300);
        }, 2500);
    };
}
