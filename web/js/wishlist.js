function toggleWishlist(productId, buttonElement = null) {
    // Check login state
    const isLoggedIn = document.body.dataset.loggedIn === "1";

    if (!isLoggedIn) {
        if (confirm('You need to login to manage wishlist. Go to login page?')) {
            const currentUrl = encodeURIComponent(window.location.href);
            window.location.href = '/page/login.php?redirect=' + currentUrl;
        }
        return;
    }

    // Send AJAX request to toggle wishlist
    $.post('/page/AJAX/wishlist_ajax.php', { 
        action: 'toggle', 
        product_id: productId 
    }, function(response) {
        if (response && response.success) {
            // Update heart icon
            if (buttonElement) {
                const icon = $(buttonElement).find('i');
                if (response.is_in_wishlist) {
                    icon.removeClass('far').addClass('fas').css('color', '#e74c3c');
                } else {
                    icon.removeClass('fas').addClass('far').css('color', '#333');
                }
                
                // If on wishlist page, remove the item from view when removed
                if (window.location.pathname.includes('wishlist.php') && !response.is_in_wishlist) {
                    $(buttonElement).closest('.product-card').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if wishlist is now empty
                        const remainingItems = $('.product-card').length;
                        if (remainingItems === 0) {
                            location.reload(); // Reload to show empty message
                        }
                    });
                }
            }
            
            // Show notification
            const message = response.is_in_wishlist ? 
                'Added to wishlist!' : 
                'Removed from wishlist';
            showNotification(message, 'success');
            
        } else {
            showNotification((response && response.message) || 'Error updating wishlist', 'error');
        }
    }, 'json').fail(function() {
        showNotification('Network error. Please try again.', 'error');
    });
}

// Initialize wishlist button states on page load
$(document).ready(function() {
    // If we have user, check wishlist status for each product on page
    const isLoggedIn = document.body.dataset.loggedIn === "1";
    
    if (isLoggedIn) {
        // Get all product IDs on the page
        const productIds = [];
        $('[data-product-id]').each(function() {
            productIds.push($(this).data('product-id'));
        });
        
        if (productIds.length > 0) {
            // Fetch wishlist status for all products
            $.post('/page/AJAX/wishlist_ajax.php', { 
                action: 'check_status', 
                product_ids: productIds 
            }, function(response) {
                if (response && response.success) {
                    // Update heart icons based on status
                    Object.keys(response.wishlist_status).forEach(productId => {
                        const isInWishlist = response.wishlist_status[productId];
                        const wishlistBtn = $(`.wishlist-btn[data-product-id="${productId}"]`);
                        
                        if (wishlistBtn.length) {
                            const icon = wishlistBtn.find('i');
                            if (isInWishlist) {
                                icon.removeClass('far').addClass('fas').css('color', '#e74c3c');
                            } else {
                                icon.removeClass('fas').addClass('far').css('color', '#333');
                            }
                        }
                    });
                }
            }, 'json');
        }
    }
});