function updateCartCount(count = null) {
    if (count === null) {
        // Fetch current count
        fetchCartCount();
        return;
    }
    
    const cartBadge = document.getElementById('cart-badge');
    const cartLink = document.getElementById('cart-link');
    
    if (cartBadge && cartLink) {
        if (count > 0) {
            cartBadge.textContent = count;
            cartBadge.style.display = 'flex';
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

// Fetch current cart count from server
function fetchCartCount() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'cart_count.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                updateCartCount(response.cart_count);
            } catch (e) {
                console.error('Error fetching cart count:', e);
            }
        }
    };
    xhr.send();
}

// Show notification message
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.custom-notification');
    existing.forEach(n => n.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.innerHTML = `
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            min-width: 300px;
            max-width: 80%;
            animation: slideIn 0.3s ease forwards;
            ${type === 'success' ? 'background: linear-gradient(135deg, #27ae60, #2ecc71);' : ''}
            ${type === 'error' ? 'background: linear-gradient(135deg, #e74c3c, #c0392b);' : ''}
            ${type === 'warning' ? 'background: linear-gradient(135deg, #f39c12, #e67e22);' : ''}
            ${type === 'info' ? 'background: linear-gradient(135deg, #3498db, #2980b9);' : ''}
        ">
            ${type === 'success' ? '✓ ' : ''}
            ${type === 'error' ? '✗ ' : ''}
            ${type === 'warning' ? '⚠ ' : ''}
            ${type === 'info' ? 'ℹ ' : ''}
            ${message}
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
    
    // Add click to dismiss
    notification.addEventListener('click', () => {
        notification.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    });
}

// Add keyframe animations if not already present
if (!document.getElementById('notification-animations')) {
    const style = document.createElement('style');
    style.id = 'notification-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Restore order items to cart
function restoreOrder(orderId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'reorder_items.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification('Items have been added to your cart!', 'success');
                    updateCartCount(response.cart_count);
                    // Redirect to cart after short delay
                    setTimeout(() => {
                        window.location.href = 'cart.php';
                    }, 1500);
                } else {
                    showNotification('Error: ' + response.message, 'error');
                }
            } catch (e) {
                showNotification('Error processing request', 'error');
            }
        }
    };
    xhr.send('order_id=' + encodeURIComponent(orderId) + '&action=reorder');
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
        // Check if badge exists, if not create it
        setTimeout(() => {
            if (!document.getElementById('cart-badge')) {
                fetchCartCount();
            }
        }, 100);
    }
});