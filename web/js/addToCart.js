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
            fetch('/page/cart_count.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('cart-badge');
                    if (!badge) return;

                    if (data.cart_count > 0) {
                        badge.textContent = data.cart_count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                });
        } else {
            showNotification('Error adding product to cart', 'error');
        }
    };

    xhr.onerror = function () {
        showNotification('Network error. Please try again.', 'error');
    };

    xhr.send(formData);
}
