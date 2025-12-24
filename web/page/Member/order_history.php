<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');
$user_id = $_user->user_id;

// Sorting, tab filtering, and search
$sort = $_GET['sort'] ?? 'date_desc';
$tabActive = $_GET['tab'] ?? 'all';
$search = trim($_GET['search'] ?? '');

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

// Search condition
$searchCondition = '';
$searchParams = [];
if ($search !== '') {
    $searchCondition = " AND (o.order_id LIKE ? OR o.order_id IN (
        SELECT DISTINCT oi.order_id 
        FROM order_item oi 
        JOIN product p ON oi.product_id = p.product_id 
        WHERE p.product_name LIKE ?
    ))";
    $searchTerm = "%$search%";
    $searchParams = [$searchTerm, $searchTerm];
}

// Pagination settings
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 3; // orders per page
$offset = ($page - 1) * $limit;

// Count total orders with filters
$countSql = "
    SELECT COUNT(DISTINCT o.order_id) 
    FROM `order` o 
    WHERE o.user_id=? $statusFilter $searchCondition
";
$stm_count = $_db->prepare($countSql);
$stm_count->bindValue(1, $user_id, PDO::PARAM_STR);
if ($search !== '') {
    $stm_count->bindValue(2, $searchTerm, PDO::PARAM_STR);
    $stm_count->bindValue(3, $searchTerm, PDO::PARAM_STR);
}
$stm_count->execute();
$totalItems = $stm_count->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Fetch paginated orders
$ordersSql = "
    SELECT DISTINCT o.order_id, o.total_amount, o.status, o.order_date, o.delivered_at
    FROM `order` o 
    WHERE o.user_id=? $statusFilter $searchCondition
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
";

$stm = $_db->prepare($ordersSql);
$stm->bindValue(1, $user_id, PDO::PARAM_STR);

$paramIndex = 2;
if ($search !== '') {
    $stm->bindValue($paramIndex++, $searchTerm, PDO::PARAM_STR);
    $stm->bindValue($paramIndex++, $searchTerm, PDO::PARAM_STR);
}

$stm->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stm->bindValue($paramIndex, $offset, PDO::PARAM_INT);
$stm->execute();
$orders = $stm->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for images
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categories = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR);

$_title = 'My Orders | Four Eyes Collective';
$_css = ['order.css'];
include '../../_head.php';
?>

<section class="hero-section">
    <div class="hero-inner">
        <a href="javascript:history.back()" class="floating-back-arrow">
            <i class="fas fa-arrow-left"></i> Back
        </a>

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
            $href = "?tab=" . urlencode($key) . "&sort=" . urlencode($sort) . "&search=" . urlencode($search);
        ?>
            <a href="<?= $href ?>" class="tab-button <?= $activeClass ?>">
                <?php if ($key !== 'all'): ?>
                    <img src="/images/icons/<?= $key ?><?= $activeClass ? '-active' : '' ?>.png" alt="<?= $label ?>" class="tab-icon">
                <?php endif; ?>
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Combined Search and Sort Row -->
    <div class="filter-row">
        <!-- Search Bar -->
        <div class="search-container">
            <form method="get" class="search-form">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActive) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="page" value="1">

                <div class="search-wrapper">
                    <input type="text"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search by Order ID or Product Name..."
                        class="search-input">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <?php if ($search !== ''): ?>
                    <a href="?tab=<?= urlencode($tabActive) ?>&sort=<?= urlencode($sort) ?>"
                        class="clear-search">
                        <i class="fas fa-times"></i> Clear Search
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Sort Dropdown -->
        <form method="get" class="sort-form">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActive) ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="page" value="1">
            <select name="sort" class="sort-select">
                <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Sort: Newest First</option>
                <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Sort: Oldest First</option>
                <option value="total_desc" <?= $sort === 'total_desc' ? 'selected' : '' ?>>Sort: Total High → Low</option>
                <option value="total_asc" <?= $sort === 'total_asc' ? 'selected' : '' ?>>Sort: Total Low → High</option>
            </select>
        </form>
    </div>

    <div id="orders-container" class="orders-container">
        <?php if (empty($orders)): ?>
            <div class="no-orders" style="text-align: center; padding: 40px;">
                <?php if ($search !== ''): ?>
                    <p>No orders found matching "<strong><?= htmlspecialchars($search) ?></strong>".</p>
                    <p><a href="?tab=<?= urlencode($tabActive) ?>&sort=<?= urlencode($sort) ?>" style="color: #3498db;">Clear search to see all orders</a></p>
                <?php else: ?>
                    <p>No orders found.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($search !== ''): ?>
                <div class="search-results-info" style="margin-bottom: 15px; color: #666; font-size: 14px;">
                    Found <?= $totalItems ?> order<?= $totalItems !== 1 ? 's' : '' ?> matching "<strong><?= htmlspecialchars($search) ?></strong>"
                </div>
            <?php endif; ?>

            <?php foreach ($orders as $order): ?>
                <?php
                $canReturn = false;
                $returnDaysLeft = null;

                if (
                    in_array($order['status'], ['delivered', 'completed']) &&
                    !empty($order['delivered_at'])
                ) {
                    $deliveredDate = new DateTime($order['delivered_at']);
                    $today = new DateTime();

                    if ($today >= $deliveredDate) {
                        $daysPassed = $deliveredDate->diff($today)->days;

                        if ($daysPassed <= 30) {
                            $canReturn = true;
                            $returnDaysLeft = 30 - $daysPassed;
                        }
                    }
                }

                ?>
                <div class="order-card"
                    data-id="<?= $order['order_id'] ?>"
                    data-status="<?= $order['status'] ?>">

                    <span class="order-status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>

                    <div class="order-summary">
                        <p class="order-id">Order ID: <?= $order['order_id'] ?></p>
                        <p class="order-date"><?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
                        <?php if ($canReturn): ?>
                            <p class="return-countdown">
                                Return available for <strong><?= $returnDaysLeft ?></strong> more day<?= $returnDaysLeft > 1 ? 's' : '' ?>.
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="order-images">
                        <?php
                        $stm_items = $_db->prepare("SELECT p.product_image, p.category_id, p.product_name FROM order_item oi JOIN product p ON oi.product_id = p.product_id WHERE oi.order_id=?");
                        $stm_items->execute([$order['order_id']]);
                        $items = $stm_items->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($items as $item):
                            $images = explode(',', $item['product_image']);
                        ?>
                            <div class="order-image-item" title="<?= htmlspecialchars($item['product_name']) ?>">
                                <img src="/images/product/<?= encode($categories[$item['category_id']] ?? 'other') ?>/<?= trim(encode($images[0])) ?>" class="order-thumb">
                            </div>
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
                                <?php if ($canReturn): ?>
                                    <button type="button"
                                        class="return-btn cta-button large"
                                        data-id="<?= $order['order_id'] ?>">
                                        Return (<?= $returnDaysLeft ?> day<?= $returnDaysLeft > 1 ? 's' : '' ?> left)
                                    </button>
                                <?php endif; ?>

                                <button type="button"
                                    class="order-again-btn cta-button large"
                                    data-id="<?= $order['order_id'] ?>">
                                    Order Again
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($order['status'], ['returned', 'cancelled'])): ?>
                                <button type="button" class="order-again-btn cta-button large" data-id="<?= $order['order_id'] ?>">Order Again</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($totalPages > 1): ?>
                <div style="margin-top:30px; display:flex; gap:8px; justify-content:center;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&tab=<?= urlencode($tabActive) ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>"
                            style="padding:6px 12px; border-radius:4px; text-decoration:none; font-size:14px; <?= $i == $page ? 'background:#2c3e50;color:white;' : 'background:#ecf0f1;color:#333;' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

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
            <button type="button" id="closeCancelBtn"
                style="padding:8px 15px;background:#bdc3c7;border:none;border-radius:4px;">
                Close
            </button>
            <button id="confirmCancelBtn"
                style="padding:8px 15px;background:#e74c3c;color:white;border:none;border-radius:4px;">
                Confirm Cancel
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/js/notifications.js"></script>

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
                url: '/page/Member/order_cancel.php',
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
                url: '/page/Member/order_receive.php',
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
                url: '/page/Member/order_again.php',
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
            window.location.href = '/page/Member/order_rate.php?order_id=' + orderId;
        });

        $('#closeCancelBtn').on('click', function() {
            $modal.css('display', 'none');
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

<?php include '../../_foot.php'; ?>