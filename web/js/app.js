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
    fetch('/page/Member/cart_count.php')
        .then(res => res.json())
        .then(data => {
            updateCartCount(data.cart_count || 0);
        }).catch(() => { });
}




