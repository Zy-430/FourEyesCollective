function addToCart(productId) {
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

    xhr.open('POST', 'cart.php');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification(response.message, 'success');
                    // Update cart count if returned
                    if (response.cart_count !== undefined) {
                        updateCartCount(response.cart_count);
                    }
                } else {
                    showNotification(response.message || 'Error adding product to cart', 'error');
                }
            } catch (e) {
                // If response is not JSON (legacy behavior), show generic success
                showNotification('Product added to cart successfully!', 'success');
                // Still try to update cart count
                updateCartCount();
            }
        } else {
            showNotification('Error adding product to cart', 'error');
        }
    };
    xhr.onerror = function() {
        showNotification('Network error. Please try again.', 'error');
    };
    xhr.send(formData);
}

// Function to update cart count in header
function updateCartCount(count = null) {
    const cartBadge = document.getElementById('cart-badge');
    const cartLink = document.getElementById('cart-link');
    
    if (cartBadge && cartLink) {
        if (count === null) {
            // If count not provided, fetch it
            fetchCartCount();
            return;
        }
        
        if (count > 0) {
            cartBadge.textContent = count;
            cartBadge.style.display = 'flex';
        } else {
            cartBadge.style.display = 'none';
        }
    } else if (!cartBadge && cartLink) {
        // Create badge if it doesn't exist
        createCartBadge(count);
    }
}

// Function to fetch current cart count
function fetchCartCount() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'cart_count.php');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                updateCartCount(response.cart_count);
            } catch (e) {
                console.error('Error parsing cart count:', e);
            }
        }
    };
    xhr.send();
}

// Function to create cart badge if it doesn't exist
function createCartBadge(count) {
    const cartLink = document.getElementById('cart-link');
    if (!cartLink) return;
    
    const badge = document.createElement('span');
    badge.id = 'cart-badge';
    badge.style.position = 'absolute';
    badge.style.top = '-8px';
    badge.style.right = '-8px';
    badge.style.background = 'red';
    badge.style.color = 'white';
    badge.style.borderRadius = '50%';
    badge.style.width = '20px';
    badge.style.height = '20px';
    badge.style.fontSize = '12px';
    badge.style.display = 'flex';
    badge.style.alignItems = 'center';
    badge.style.justifyContent = 'center';
    badge.style.zIndex = '1000';
    
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
    
    cartLink.style.position = 'relative';
    cartLink.appendChild(badge);
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    if (isLoggedIn) {
        // Check if badge exists, if not create it
        setTimeout(() => {
            if (!document.getElementById('cart-badge')) {
                fetchCartCount();
            }
        }, 100);
    }
});