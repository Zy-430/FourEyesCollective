<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');
$user_id = $_user->user_id;

// Fetch all recent orders for this user
$stm_orders = $_db->prepare("
    SELECT order_id, total_amount, status, order_date
    FROM `order`
    WHERE user_id = ?
    ORDER BY order_date DESC
");
$stm_orders->execute([$user_id]);
$recent_orders = $stm_orders->fetchAll(PDO::FETCH_ASSOC);

// Count orders by status
$statuses = ['pending' => 0, 'shipped' => 0, 'delivered' => 0];
$total_orders = count($recent_orders);

foreach ($recent_orders as $order) {
    $status = strtolower($order['status']);
    if (isset($statuses[$status])) {
        $statuses[$status]++;
    }
}

$_title = "My Recent Orders | Four Eyes Collective";
$_css = ['profile.css'];
include '../../_head.php';
?>

<section class="profile-section recent-orders-section">
    <h1>My Recent Orders</h1>

    <div class="summary-cards">
        <div class="summary-card">
            <p>Total Orders</p>
            <h2><?= $total_orders ?></h2>
        </div>
        <div class="summary-card">
            <p>Pending</p>
            <h2><?= $statuses['pending'] ?></h2>
        </div>
        <div class="summary-card">
            <p>Shipped</p>
            <h2><?= $statuses['shipped'] ?></h2>
        </div>
        <div class="summary-card">
            <p>Delivered</p>
            <h2><?= $statuses['delivered'] ?></h2>
        </div>
    </div>

    <?php if (empty($recent_orders)): ?>
        <p>No recent orders.</p>
    <?php else: ?>
        <div class="recent-orders-list">
            <?php foreach ($recent_orders as $order): ?>
                <div class="recent-order-card" data-order-id="<?= $order['order_id'] ?>">
                    <span class="status-badge <?= $order['status'] ?: 'default' ?>"><?= ucfirst($order['status']) ?></span>
                    <div class="order-info">
                        <p class="order-title">Order #<?= $order['order_id'] ?></p>
                        <p class="order-sub"><?= date('d M Y', strtotime($order['order_date'])) ?></p>
                    </div>

                    <div class="order-summary-bar">
                        <?php
                        $stm_count = $_db->prepare("SELECT SUM(product_qty) AS total_qty FROM order_item WHERE order_id = ?");
                        $stm_count->execute([$order['order_id']]);
                        $total_qty = $stm_count->fetchColumn();
                        ?>
                        <span><?= $total_qty ?> item(s)</span>
                        <span>RM <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="more-link">
            <a href="/page/Member/order_history.php" class="view-orders-btn">View Order History→</a>
        </div>
    <?php endif; ?>

</section>

<script>
    $(document).ready(function() {
        $('.recent-order-card').on('click', function() {
            var orderId = $(this).data('order-id');
            window.location.href = '/page/order_details.php?order_id=' + orderId;
        });
    });
</script>

<?php include '../../_foot.php'; ?>