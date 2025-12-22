// Homepage specific JS - uses jQuery and project conventions
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
