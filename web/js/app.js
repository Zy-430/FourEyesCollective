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

    // Refresh cart badge on page load (if available)
    if (typeof refreshCartBadge === 'function') {
        refreshCartBadge();
    }

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



// Toggle filter dropdown
document.getElementById('filterDropdown').addEventListener('click', function (e) {
    e.stopPropagation();
    const dropdown = document.getElementById('filterForm');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

// Close filter dropdown 
document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('filterForm');
    const button = document.getElementById('filterDropdown');

    if (!button.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

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

// Generic confirm dialog for elements with data-confirm
$(document).on('click', '[data-confirm]', function(e){
    const msg = $(this).data('confirm') || 'Are you sure?';
    if (!confirm(msg)) {
        e.preventDefault();
        e.stopImmediatePropagation();
    }
});

// Auto-submit nearest form for inputs with .auto-submit
$(document).on('change', '.auto-submit', function(){
    $(this).closest('form').submit();
});

const flashMsgs = document.querySelectorAll('.flash-msg');

flashMsgs.forEach(msg => {
    setTimeout(() => {
        msg.style.transition = "opacity 0.5s";
        msg.style.opacity = 0;
        setTimeout(() => msg.remove(), 500);
    }, 4000);
});