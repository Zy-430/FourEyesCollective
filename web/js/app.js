// ============================================================================
// General Functions
// ============================================================================
$(document).ready(function () {
    $(".user-icon").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(".user-dropdown").toggleClass("active");
    });

    // Close dropdown when clicking outside
    $(document).on("click", function () {
        $(".user-dropdown").removeClass("active");
    });
});


// ============================================================================
// Page Load (jQuery)
// ============================================================================

$(() => {

    // Initiate GET request
    $('[data-get]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.get;
        location = url || location;
    });

});

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;

    if (type === 'success') {
        notification.style.background = '#28a745';
    } else if (type === 'error') {
        notification.style.background = '#dc3545';
    } else {
        notification.style.background = '#17a2b8';
    }

    notification.textContent = message;
    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
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



// Admin sidebar 
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const collapseBtn = document.querySelector('.collapse-btn');
    const icon = collapseBtn.querySelector('i');

    sidebar.classList.toggle('collapsed');

    // Change icon
    if (sidebar.classList.contains('collapsed')) {
        icon.className = 'fas fa-chevron-right';
    } else {
        icon.className = 'fas fa-chevron-left';
    }
}

// User photo preview
function openPhotoModal(src) {
    const modal = document.getElementById('photoModal');
    const modalImg = document.getElementById('modalImg');
    modal.style.display = "block";
    modalImg.src = src;
}

function closePhotoModal() {
    document.getElementById('photoModal').style.display = "none";
}

const flashMsgs = document.querySelectorAll('.flash-msg');

flashMsgs.forEach(msg => {
    setTimeout(() => {
        msg.style.transition = "opacity 0.5s";
        msg.style.opacity = 0;
        setTimeout(() => msg.remove(), 500);
    }, 4000);
});


// ---------------------------------------------------------------------------
// Cart badge helpers
// ---------------------------------------------------------------------------
function updateCartCount(count) {
    const badge = document.getElementById('cart-badge');
    const cartLink = document.getElementById('cart-link');

    if (badge && cartLink) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

function refreshCartBadge() {
    fetch('/page/cart_count.php')
        .then(res => res.json())
        .then(data => {
            updateCartCount(data.cart_count || 0);
        }).catch(() => {});
}






