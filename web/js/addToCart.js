function addToCart(productId) {
    // Check login state from data attribute
    const isLoggedIn = document.body.dataset.loggedIn === "1";

    if (!isLoggedIn) {
        if (confirm('You need to login to add items to cart. Go to login page?')) {
            const currentUrl = encodeURIComponent(window.location.href);
            window.location.href = '/page/login.php?redirect=' + currentUrl;
        }
        return;
    }

    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);

    xhr.open('POST', '/page/cart.php');

    xhr.onload = function () {
        if (xhr.status === 200) {
            showNotification('Product added to cart successfully!', 'success');

            // Update cart badge immediately
            if (typeof refreshCartBadge === 'function') {
                refreshCartBadge();
            }
        } else {
            showNotification('Error adding product to cart', 'error');
        }
    };

    xhr.onerror = function () {
        showNotification('Network error. Please try again.', 'error');
    };

    xhr.send(formData);
}

// Delegate clicks on elements with .add-to-cart to the addToCart function
$(document).on('click', '.add-to-cart', function(e){
    e.preventDefault();
    const productId = $(this).data('product-id') || $(this).attr('data-product-id');
    if (!productId) return;
    addToCart(productId);
});
