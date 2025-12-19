<?php
require '../_base.php';
require '../lib/db.php';

auth('Member');
$user_id = $_user->user_id;

// Sorting and tab filtering (both require server-side reload)
$sort = $_GET['sort'] ?? 'date_desc';
$tabActive = $_GET['tab'] ?? 'all';

$orderBy = match ($sort) {
    'date_asc' => 'o.order_date ASC',
    'total_asc' => 'o.total_amount ASC',
    'total_desc' => 'o.total_amount DESC',
    default => 'o.order_date DESC'
};

$statusFilter = '';
if ($tabActive === 'to-ship') {
    $statusFilter = " AND o.status = 'pending'";
} elseif ($tabActive === 'to-receive') {
    $statusFilter = " AND o.status IN ('shipped','delivered')";
} elseif ($tabActive !== 'all') {
    $statusFilter = " AND o.status = '$tabActive'";
}

$stm = $_db->prepare("
    SELECT o.order_id, o.total_amount, o.status, o.order_date 
    FROM `order` o 
    WHERE o.user_id=? $statusFilter 
    ORDER BY $orderBy
");
$stm->execute([$user_id]);
$orders = $stm->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for images
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categories = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR);

$_title = 'My Orders | Four Eyes Collective';
$_css = ['order.css'];
include '../_head.php';
?>

<section class="hero-section">
    <div class="hero-inner">
        <h1 class="hero-title">My Orders</h1>
        <p class="hero-subtitle">Track your orders, view history, and see details of each purchase.</p>
    </div>
</section>

<div class="orders-page">
    <div class="order-tabs">
        <?php
        $tabs = ['all' => 'All', 'to-ship' => 'To Ship', 'to-receive' => 'To Receive', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'returned' => 'Returned'];
        foreach ($tabs as $key => $label):
            $activeClass = $key === $tabActive ? 'active' : '';
            $href = "?tab=" . urlencode($key) . "&sort=" . urlencode($sort);
        ?>
            <a href="<?= $href ?>" class="tab-button <?= $activeClass ?>">
                <?php if ($key !== 'all'): ?>
                    <img src="/images/icons/<?= $key ?><?= $activeClass ? '-active' : '' ?>.png" alt="<?= $label ?>" class="tab-icon">
                <?php endif; ?>
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="get" class="sort-form">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActive) ?>">
        <select name="sort" class="custom-select sort-select">
            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest</option>
            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest</option>
            <option value="total_desc" <?= $sort === 'total_desc' ? 'selected' : '' ?>>Total: High → Low</option>
            <option value="total_asc" <?= $sort === 'total_asc' ? 'selected' : '' ?>>Total: Low → High</option>
        </select>
    </form>

    <div id="orders-container" class="orders-container">
        <?php if (empty($orders)): ?>
            <p class="no-orders">No orders found.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card"
                    data-id="<?= $order['order_id'] ?>"
                    data-status="<?= $order['status'] ?>">

                    <span class="order-status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>

                    <div class="order-summary">
                        <p class="order-id">Order ID: <?= $order['order_id'] ?></p>
                        <p class="order-date"><?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
                    </div>

                    <div class="order-images">
                        <?php
                        $stm_items = $_db->prepare("SELECT p.product_image, p.category_id FROM order_item oi JOIN product p ON oi.product_id = p.product_id WHERE oi.order_id=?");
                        $stm_items->execute([$order['order_id']]);
                        $items = $stm_items->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($items as $item):
                            $images = explode(',', $item['product_image']);
                        ?>
                            <img src="/images/product/<?= encode($categories[$item['category_id']] ?? 'other') ?>/<?= trim(encode($images[0])) ?>" class="order-thumb">
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <span class="order-total">Total: RM <?= number_format($order['total_amount'], 2) ?></span>
                        <div class="order-actions">
                            <?php if ($order['status'] == 'delivered'): ?>
                                <button type="button" class="receive-btn cta-button large" data-id="<?= $order['order_id'] ?>">Mark as Received</button>
                            <?php endif; ?>

                            <?php if ($order['status'] == 'pending'): ?>
                                <button type="button" class="cancel-btn cta-button large" data-id="<?= $order['order_id'] ?>">Cancel</button>
                            <?php endif; ?>

                            <?php if ($order['status'] == 'completed'): ?>
                                <button type="button" class="rate-link cta-button large" data-id="<?= $order['order_id'] ?>">Rate</button>
                                <button type="button" class="return-btn cta-button large" data-id="<?= $order['order_id'] ?>">Return</button>
                                <button type="button" class="order-again-btn cta-button large" data-id="<?= $order['order_id'] ?>">Order Again</button>
                            <?php endif; ?>

                            <?php if (in_array($order['status'], ['returned', 'cancelled'])): ?>
                                <button type="button" class="order-again-btn cta-button large" data-id="<?= $order['order_id'] ?>">Order Again</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Cancel Modal -->
<div id="cancelModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;">
    <div style="background:white;padding:25px;border-radius:6px;width:350px;box-shadow:0 2px 10px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">Cancel Order</h3>
        <p>Please select a reason for cancellation:</p>
        <select id="cancelReason" style="width:100%;padding:8px;margin-bottom:15px;">
            <option value="">-- Select a reason --</option>
            <option value="Changed my mind">Changed my mind</option>
            <option value="Found a better price">Found a better price</option>
            <option value="Ordered by mistake">Ordered by mistake</option>
            <option value="Delivery taking too long">Delivery taking too long</option>
            <option value="Incorrect item selected">Incorrect item selected</option>
            <option value="Other">Other</option>
        </select>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button onclick="closeCancelModal()" style="padding:8px 15px;background:#bdc3c7;border:none;border-radius:4px;">Close</button>
            <button id="confirmCancelBtn" style="padding:8px 15px;background:#e74c3c;color:white;border:none;border-radius:4px;">Confirm Cancel</button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {

        // Sorting form auto-submit
        $('select[name="sort"]').on('change', function() {
            $(this).closest('form').submit();
        });

        // Cancel Modal
        var cancelId = null;
        var $modal = $('#cancelModal');
        var $reasonInput = $('#cancelReason');

        $('.cancel-btn').on('click', function(e) {
            e.stopPropagation();
            cancelId = $(this).data('id');
            $reasonInput.val('');
            $modal.css('display', 'flex');
        });

        $('#confirmCancelBtn').on('click', function() {
            var reason = $reasonInput.val();
            if (!reason) {
                if (typeof showNotification === 'function') showNotification('Please select a reason', 'error');
                return;
            }

            $.ajax({
                url: '/page/order_cancel.php',
                method: 'POST',
                data: {
                    order_id: cancelId,
                    cancelled_reason: reason
                },
                dataType: 'text'
            }).done(function() {
                if (typeof showNotification === 'function') showNotification('Order cancelled successfully', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1200);
            }).fail(function() {
                if (typeof showNotification === 'function') showNotification('Failed to cancel order', 'error');
            });
        });

        // Close cancel modal
        window.closeCancelModal = function() {
            $modal.css('display', 'none');
        };

        // Mark as Received
        $('.receive-btn').on('click', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');

            $.ajax({
                url: '/page/order_receive.php',
                method: 'POST',
                data: {
                    order_id: id
                },
                dataType: 'text'
            }).done(function() {
                if (typeof showNotification === 'function') showNotification('Order marked as received', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1200);
            }).fail(function() {
                if (typeof showNotification === 'function') showNotification('Failed to mark as received', 'error');
            });
        });

        // Order Again
        $('.order-again-btn').on('click', function(e) {
            e.stopPropagation();
            var orderId = $(this).data('id');

            $.ajax({
                url: '/page/order_again.php',
                method: 'POST',
                data: {
                    order_id: orderId
                },
                dataType: 'json'
            }).done(function(data) {
                if (data.status === 'success') {
                    if (typeof showNotification === 'function') showNotification('Items added to cart', 'success');
                    setTimeout(function() {
                        window.location.href = data.redirect;
                    }, 1200);
                } else {
                    if (typeof showNotification === 'function') showNotification(data.message || 'Failed to add items', 'error');
                }
            }).fail(function() {
                if (typeof showNotification === 'function') showNotification('Something went wrong', 'error');
            });
        });

        // Return Button
        $('.return-btn').on('click', function(e) {
            e.stopPropagation();
            var orderId = $(this).data('id');
            window.location.href = '/page/order_details.php?order_id=' + orderId + '#return';
        });

        // Rate Button
        $('.rate-link').on('click', function(e) {
            e.stopPropagation();
            var orderId = $(this).closest('.order-card').data('id');
            window.location.href = '/page/order_rate.php?order_id=' + orderId;
        });

        // Click anywhere on order card
        $('.order-card').on('click', function() {
            var orderId = $(this).data('id');
            window.location.href = '/page/order_details.php?order_id=' + orderId;
        });

        // Hover effect
        $('.order-card').hover(
            function() {
                $(this).css('transform', 'translateY(-4px)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
            }
        );

    });
</script>

<?php include '../_foot.php'; ?>