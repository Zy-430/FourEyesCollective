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
// Page Load
// ============================================================================

$(() => {

    // Initiate GET request
    $('[data-get]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.get;
        location = url || location;
    });

});


// ============================================================================
// Admin sidebar
// ============================================================================
$(function () {

    const $sidebar = $('.admin-sidebar');
    const $collapseBtn = $('.collapse-btn');
    const $icon = $collapseBtn.find('i');

    // Toggle sidebar on button click
    $collapseBtn.on('click', function () {
        $sidebar.toggleClass('collapsed');

        // Update icon
        if ($sidebar.hasClass('collapsed')) {
            $icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
        } else {
            $icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
        }

        // Persist state in localStorage
        const collapsed = $sidebar.hasClass('collapsed');
        localStorage.setItem('adminSidebarCollapsed', collapsed ? '1' : '0');

        // Send to server via AJAX to store in session
        $.ajax({
            url: '/page/ajax/sidebar_state.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ collapsed: collapsed }),
            error: function () { console.warn('Failed to save sidebar state to server'); }
        });
    });

    // Apply sidebar state on page load
    function applySidebarState() {
        // Temporarily disable transition
        $sidebar.addClass('no-transition');

        // Check localStorage first
        const collapsed = localStorage.getItem('adminSidebarCollapsed') === '1';
        if (collapsed) $sidebar.addClass('collapsed');
        else $sidebar.removeClass('collapsed');

        // Update icon
        if (collapsed) $icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
        else $icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');

        // Force reflow
        $sidebar[0].offsetHeight;

        // Remove transition block shortly after
        setTimeout(() => $sidebar.removeClass('no-transition'), 50);
    }

    applySidebarState();
});

// ============================================================================
// User photo Modal
// ============================================================================
// User photo preview
function openPhotoModal(src) {
    $('#photoModal').fadeIn();       // Show modal with fade
    $('#modalImg').attr('src', src); // Set image source
}

function closePhotoModal() {
    $('#photoModal').fadeOut();       // Hide modal with fade
}

// Close modal when clicking outside the image
$(function() {
    $('#photoModal').on('click', function(e) {
        if ($(e.target).is('#modalImg')) return; 
        $(this).fadeOut();
    });
});


// ---------------------------------------------------------------------------
// Cart badge
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


$(function() {
    // Check for cart updates from other tabs/windows
    $(window).on('storage', function(e) {
        if (e.originalEvent.key === 'cart_updated') {
            refreshCartBadge();
            if (window.location.pathname.includes('cart.php')) {
                // Reload cart page to show updated items
                window.location.reload();
            }
        }
    });
    
    // Check URL for cancellation parameter
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('checkout_cancelled')) {
        showNotification('Checkout cancelled. Items have been returned to cart.', 'info');
        // Remove parameter without page reload
        history.replaceState({}, document.title, window.location.pathname);
    }
});

// Update cart count globally
function updateGlobalCartCount() {
    // Set storage event for other tabs
    if (typeof Storage !== 'undefined') {
        localStorage.setItem('cart_updated', Date.now());
    }
    refreshCartBadge();
}

// ---------------------------------------------------------------------------
// Cart functions
// ---------------------------------------------------------------------------

$(function () {
    const $cartForm = $('#cartForm');

    function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(res => {
            const ct = res.headers.get('content-type') || '';
            if (ct.indexOf('application/json') > -1) return res.json();
            return res.text().then(text => {
                try { return JSON.parse(text); } catch (e) { return { success: false, message: 'Unexpected server response' }; }
            });
        });
    }

    function formatCurrency(amount) {
        const n = Number(amount) || 0;
        return 'RM ' + n.toFixed(2);
    }

    function safeNumber(v) { return Number(v) || 0; }

    function updateSummary() {
        const $checked = $('.item-checkbox:checked');

        // If no items are selected, show default values as requested:
        // no value for subtotal, RM 0.00 estimated total and FREE shipping
        if ($checked.length === 0) {
            $('#summarySubtotal').text('RM 0.00');
            $('#shippingCost').text('FREE');
            $('#summaryEstimated').text(formatCurrency(0));
            $('#checkoutBtn').text('Proceed to Checkout');
            // Clear any count indicators if present
            $('#summaryCount').text('');
            $('#summaryItemsPlural').text('');

            // Update selectAll state
            const total = $('.item-checkbox').length;
            $('#selectAll').prop('checked', false).prop('indeterminate', false);
            return;
        }

        let subtotal = 0;
        let totalItems = 0;

        $checked.each(function () {
            const $row = $(this).closest('.cart-item-row');
            // Prefer data-price on checkbox; fallback to parsing visible price if missing
            const price = safeNumber($(this).data('price')) || safeNumber($row.find('.cart-item-price').text().replace(/[^0-9.]/g, ''));
            const qty = safeNumber($row.find('.qty-number').text());
            subtotal += price * qty;
            totalItems += qty;
        });

        const shipping = subtotal >= 500 ? 0 : (subtotal === 0 ? 0 : 20);
        const estimated = subtotal + shipping;

        $('#summaryCount').text(totalItems);
        $('#summaryItemsPlural').text(totalItems !== 1 ? 's' : '');
        $('#summarySubtotal').text(formatCurrency(subtotal));
        $('#shippingCost').text(shipping > 0 ? formatCurrency(shipping) : 'FREE');
        $('#summaryEstimated').text(formatCurrency(estimated));

        const checkoutLabel = subtotal > 0 ? 'Proceed to Checkout — ' + formatCurrency(estimated) : 'Proceed to Checkout';
        $('#checkoutBtn').text(checkoutLabel);

        // Update selectAll state
        const total = $('.item-checkbox').length;
        const checkedCount = $checked.length;
        $('#selectAll').prop('checked', total === checkedCount).prop('indeterminate', checkedCount > 0 && checkedCount < total);
    }

    // Select All change - handle checkbox via native toggle so label clicks work
    $('#selectAll').on('change', function () {
        const $el = $(this);
        const checked = $el.prop('checked');
        // Set each item checkbox to the same state and trigger their change handlers
        $('.item-checkbox').prop('checked', checked).trigger('change');
        // Clear indeterminate visual state
        $el.prop('indeterminate', false);
        updateSummary();
    });

    // Individual checkbox
    $(document).on('change', '.item-checkbox', function () {
        const total = $('.item-checkbox').length;
        const checked = $('.item-checkbox:checked').length;
        const $selectAll = $('#selectAll');
        $selectAll.prop('checked', total === checked);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
        updateSummary();
    });

    // Quantity Increase / Decrease
    $(document).on('click', '.qty-increase, .qty-decrease', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const cartId = $btn.data('cart-id');
        const $row = $btn.closest('.cart-item-row');
        let qty = safeNumber($row.find('.qty-number').text());

        if ($btn.hasClass('qty-increase')) qty++;
        else qty = Math.max(1, qty - 1);

        const fd = new FormData();
        fd.append('action', 'update');
        fd.append('cart_item_id', cartId);
        fd.append('quantity', qty);

        postForm('/page/AJAX/cart_ajax.php', fd)
            .then(resp => {
                if (resp.success) {
                    // Update displayed quantity
                    $row.find('.qty-number').text(qty);

                    // Keep control state and data attributes in sync
                    $row.find('.qty-decrease').prop('disabled', qty <= 1);
                    $row.find('.qty-decrease, .qty-increase').attr('data-qty', qty);

                    // Update item total display
                    const price = safeNumber($row.find('.price-each').data('price'));
                    $row.find('.item-total').text(formatCurrency(price * qty));

                    showNotification(resp.message || 'Quantity updated', 'success');
                    updateSummary();
                    if (typeof refreshCartBadge === 'function') refreshCartBadge();
                } else {
                    showNotification(resp.message || 'Unable to update quantity', 'error');
                }
            })
            .catch(() => showNotification('Network error. Please try again.', 'error'));
    });

    // Remove item
    $(document).on('click', '.remove-item', function (e) {
        e.preventDefault();
        const cartId = $(this).data('cart-id');
        const $row = $(this).closest('.cart-item-row');

        if (!confirm('Remove this item from cart?')) return;

        const fd = new FormData();
        fd.append('action', 'remove');
        fd.append('cart_item_id', cartId);

        postForm('/page/AJAX/cart_ajax.php', fd)
            .then(resp => {
                if (resp.success) {
                    $row.remove();
                    showNotification(resp.message || 'Item removed', 'success');
                    updateSummary();
                    if (typeof refreshCartBadge === 'function') refreshCartBadge();
                    // If no items left, reload to show empty state properly
                    if ($('.cart-item-row').length === 0) location.reload();
                } else {
                    showNotification(resp.message || 'Unable to remove item', 'error');
                }
            })
            .catch(() => showNotification('Network error. Please try again.', 'error'));
    });

    // Checkout
    $('#checkoutBtn').on('click', function () {
        const selected = $('.item-checkbox:checked');
        if (!selected.length) {
            showNotification('Please select items to checkout', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'checkout');
        selected.each(function () { fd.append('selected_items[]', $(this).val()); });

        postForm('/page/AJAX/cart_ajax.php', fd)
            .then(resp => {
                if (resp.success) {
                    // If server requests redirect, follow it
                    if (resp.redirect) {
                        window.location.href = resp.redirect;
                        return;
                    }
                    showNotification(resp.message || 'Proceeding to checkout', 'success');
                } else {
                    showNotification(resp.message || 'Checkout failed', 'error');
                }
            })
            .catch(() => showNotification('Network error. Please try again.', 'error'));
    });

    // Initial summary
    updateSummary();
});


// ---------------------------------------------------------------------------
// Homepage
// ---------------------------------------------------------------------------
// Homepage specific JS
$(document).ready(function () {
    // Category card hover effects (no inline event attributes)
    $(document).on('mouseenter', '.category-card', function () {
        $(this).addClass('hover-scale');
    });
    $(document).on('mouseleave', '.category-card', function () {
        $(this).removeClass('hover-scale');
    });

    // Product card hover effects
    $(document).on('mouseenter', '.product-card', function () {
        $(this).addClass('feature-card');
    });
    $(document).on('mouseleave', '.product-card', function () {
        $(this).removeClass('feature-card');
    });

    // Top5 badge visibility helper
    function updateBadgeVisibility() {
        const hideBelow = 800; // px
        if ($(window).width() < hideBelow) {
            // show small mobile badges only for top 3
            $('.top5-product .mobile-badge').each(function (idx) { $(this).toggle(idx < 3); });
        } else {
            $('.top5-product .mobile-badge').hide();
        }
    }

    // Initial and on resize
    updateBadgeVisibility();
    $(window).on('resize', updateBadgeVisibility);
});


// ---------------------------------------------------------------------------
// Add to Cart
// ---------------------------------------------------------------------------
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

    xhr.open('POST', '/page/ajax/cart_ajax.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

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
$(document).on('click', '.add-to-cart', function (e) {
    e.preventDefault();
    const productId = $(this).data('product-id') || $(this).attr('data-product-id');
    if (!productId) return;
    addToCart(productId);
});

// ---------------------------------------------------------------------------
// Wishlist functions
// ---------------------------------------------------------------------------
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

// On document ready, check wishlist status for buttons on page
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


// ============================================================================
// Product Detail Carousel
// ============================================================================
(function ($) {
    function initializeProductCarousel(options = {}) {
        const images = options.images || [];
        const folder = options.folder || '';
        const $main = $(options.mainSelector || '#mainImage');
        const thumbSelector = options.thumbSelector || '.thumb';
        const prevSelector = options.prevSelector || '.carousel-prev';
        const nextSelector = options.nextSelector || '.carousel-next';
        let idx = 0;

        function showImage(i) {
            if (!images.length) return;
            idx = i % images.length;
            $main.attr('src', '/images/product/' + folder + '/' + images[idx]);
        }

        function nextImage() {
            if (!images.length) return;
            idx = (idx + 1) % images.length;
            showImage(idx);
        }

        function prevImage() {
            if (!images.length) return;
            idx = (idx - 1 + images.length) % images.length;
            showImage(idx);
        }

        $(document).on('click', prevSelector, function (e) {
            e.preventDefault();
            prevImage();
        });

        $(document).on('click', nextSelector, function (e) {
            e.preventDefault();
            nextImage();
        });

        $(document).on('click', thumbSelector, function (e) {
            e.preventDefault();
            const i = Number($(this).data('index'));
            if (!Number.isNaN(i)) showImage(i);
        });

        if (images.length > 0) {
            showImage(0);
            if (options.interval) {
                setInterval(nextImage, options.interval);
            } else {
                setInterval(nextImage, 3000);
            }
        }

        return {
            showImage,
            nextImage,
            prevImage
        };
    }

    window.initializeProductCarousel = initializeProductCarousel;

    // Auto-initialize any element that provides data-images & data-folder
    $(function () {
        $('[data-images][data-folder]').each(function () {
            try {
                const images = JSON.parse($(this).attr('data-images'));
                const folder = $(this).attr('data-folder');
                initializeProductCarousel({ images, folder });
            } catch (e) {
                // ignore invalid JSON
            }
        });
    });
})(jQuery);
