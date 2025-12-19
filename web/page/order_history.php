<?php
require '../_base.php';
require '../lib/db.php';

auth();
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

<section style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 80px 0; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 3em; margin-bottom: 10px; color: #fff; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">My Orders</h1>
        <p style="font-size: 1.2em; opacity: 0.9; color: #f0f0f0; text-shadow: 1px 1px 2px rgba(0,0,0,0.4);">
            Track your orders, view history, and see details of each purchase.
        </p>
    </div>
</section>

<div style="max-width: 1000px; margin: 50px auto;">

    <!-- Tabs - Now using server-side reload -->
    <div class="order-tabs" style="display:flex; gap:15px; margin-bottom:20px;">
        <?php
        $tabs = ['all' => 'All', 'to-ship' => 'To Ship', 'to-receive' => 'To Receive', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
        foreach ($tabs as $key => $label):
            $activeClass = $key === $tabActive ? 'active' : '';
            $href = "?tab=" . urlencode($key) . "&sort=" . urlencode($sort);
        ?>
            <a href="<?= $href ?>" class="tab-button <?= $activeClass ?>" style="display:flex; flex-direction:column; align-items:center; text-decoration:none; color:<?= $activeClass ? '#fff' : '#000' ?>;">
                <?php if ($key !== 'all'): ?>
                    <img src="/images/icons/<?= $key ?><?= $activeClass ? '-active' : '' ?>.png" alt="<?= $label ?>" style="width:30px;height:30px;margin-bottom:5px;">
                <?php endif; ?>
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Sorting form - includes current tab -->
    <form method="get" style="margin-bottom:20px;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActive) ?>">
        <select name="sort" class="custom-select" style="padding:8px 12px; border-radius:6px; border:1px solid #ccc;">
            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Newest</option>
            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest</option>
            <option value="total_desc" <?= $sort === 'total_desc' ? 'selected' : '' ?>>Total: High → Low</option>
            <option value="total_asc" <?= $sort === 'total_asc' ? 'selected' : '' ?>>Total: Low → High</option>
        </select>
    </form>

    <!-- Orders container -->
    <div id="orders-container">
        <?php if (empty($orders)): ?>
            <p style="text-align:center; font-size:1.1em; color:#7f8c8d;">No orders found.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card" data-id="<?= $order['order_id'] ?>" data-status="<?= $order['status'] ?>" data-href="/page/order_details.php?order_id=<?= $order['order_id'] ?>"
                    style="border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff; display:flex; flex-direction:column; margin-bottom:20px; transition: transform 0.2s, box-shadow 0.2s;">

                    <span class="order-status <?= $order['status'] ?>" style="padding:5px 12px; font-weight:600; text-transform:capitalize;"><?= ucfirst($order['status']) ?></span>

                    <div style="background:#ecf0f1; padding:20px;">
                        <p style="font-size:27px; font-weight:600; margin-bottom:5px;">Order ID: <?= $order['order_id'] ?></p>
                        <p class="order-date"><?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
                    </div>

                    <div style="display:flex; gap:10px; padding:15px; overflow-x:auto;">
                        <?php
                        $stm_items = $_db->prepare("SELECT p.product_image, p.category_id FROM order_item oi JOIN product p ON oi.product_id = p.product_id WHERE oi.order_id=?");
                        $stm_items->execute([$order['order_id']]);
                        $items = $stm_items->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($items as $item):
                            $images = explode(',', $item['product_image']);
                        ?>
                            <img src="/images/product/<?= encode($categories[$item['category_id']] ?? 'other') ?>/<?= trim(encode($images[0])) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                        <?php endforeach; ?>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; padding:15px; border-top:1px solid #e0e0e0;">
                        <span style="font-weight:600;">Total: RM <?= number_format($order['total_amount'], 2) ?></span>
                        <div style="display:flex; gap:10px;">
                            <?php if ($order['status'] == 'delivered'): ?>
                                <button type="button" class="receive-btn cta-button large" data-id="<?= $order['order_id'] ?>"
                                    style="background:#27ae60;color:white;border:none;border-radius:18px;cursor:pointer;padding:15px 30px; transition:0.2s;">
                                    Mark as Received
                                </button>
                            <?php endif; ?>

                            <?php if ($order['status'] == 'pending'): ?>
                                <button type="button" class="cancel-btn cta-button large" data-id="<?= $order['order_id'] ?>"
                                    style="background:#e74c3c;color:white;border:none;border-radius:18px;cursor:pointer;padding:15px 30px; transition:0.2s;">
                                    Cancel
                                </button>
                            <?php endif; ?>

                            <?php if ($order['status'] == 'completed'): ?>
                                <button class="rate-btn cta-button large" data-url="/page/order_rate.php?order_id=<?= $order['order_id'] ?>"
                                    style="background:#f39c12;color:white;border:none;border-radius:18px;cursor:pointer;padding:15px 30px; transition:0.2s;">
                                    Rate
                                </button>
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
            <button id="cancelCloseBtn" style="padding:8px 15px;background:#bdc3c7;border:none;border-radius:4px;">Close</button>
            <button id="confirmCancelBtn" style="padding:8px 15px;background:#e74c3c;color:white;border:none;border-radius:4px;">Confirm Cancel</button>
        </div>
    </div>
</div>

<script>
    // Sorting form auto-submit
    const sortSelect = document.querySelector('select[name="sort"]');
    sortSelect.addEventListener('change', function() {
        this.form.submit();
    });

    // Cancel Modal and order actions - use jQuery delegated handlers
    let cancelId = null;
    const $modal = $('#cancelModal');
    const $reasonInput = $('#cancelReason');

    $(document).on('click', '.cancel-btn', function(e){
        e.stopPropagation();
        cancelId = $(this).data('id');
        $reasonInput.val('');
        $modal.css('display','flex');
    });

    $('#confirmCancelBtn').on('click', function(){
        const reason = $reasonInput.val();
        if (!reason) {
            alert('Please select a reason');
            return;
        }

        $.post('/page/order_cancel.php', { order_id: cancelId, cancelled_reason: reason })
            .done(function(){
                alert('Order cancelled');
                location.reload();
            });
    });

    function closeCancelModal() {
        $modal.css('display','none');
    }

    // Close modal button
    $(document).on('click', '#cancelCloseBtn', function(e){ e.preventDefault(); closeCancelModal(); });

    // Mark as Received
    $(document).on('click', '.receive-btn', function(e){
        e.stopPropagation();
        const id = $(this).data('id');
        $.post('/page/order_receive.php', { order_id: id }).done(function(){
            alert('Order marked as received');
            location.reload();
        });
    });

    // Card hover effects and card click navigation
    $(document).on('mouseenter', '.order-card', function(){ $(this).css('transform', 'translateY(-4px)'); });
    $(document).on('mouseleave', '.order-card', function(){ $(this).css('transform', 'translateY(0)'); });

    // Navigate to order details on card click
    $(document).on('click', '.order-card', function(e){
        const href = $(this).data('href');
        if (href) window.location = href;
    });

    // Rate button - navigate without triggering card click
    $(document).on('click', '.rate-btn', function(e){
        e.stopPropagation();
        const url = $(this).data('url');
        if (url) window.location = url;
    });
</script>

<?php include '../_foot.php'; ?>