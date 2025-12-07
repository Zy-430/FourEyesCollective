<?php
require '../_base.php';
require '../lib/db.php';

auth();
$user_id = $_user->user_id;

// Fetch all orders for the user
$stm = $_db->prepare("
    SELECT o.order_id, o.total_amount, o.status, o.order_date
    FROM `order` o
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
$stm->execute([$user_id]);
$orders = $stm->fetchAll(PDO::FETCH_ASSOC);

$_title = 'My Orders | Four Eyes Collective';
include '../_head.php';
?>

<!-- Orders Hero Section -->

<section style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 80px 0; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 3em; margin-bottom: 10px; color: #ffffff; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">My Orders</h1>
        <p style="font-size: 1.2em; opacity: 0.9; color: #f0f0f0; text-shadow: 1px 1px 2px rgba(0,0,0,0.4);">
            Track your orders, view history, and see details of each purchase.
        </p>
    </div>
</section>

<!-- Tabs -->

<div style="max-width: 1000px; margin: 50px auto;">
    <div class="order-tabs" style="display:flex; gap:15px; margin-bottom:20px;">
        <button class="tab-button" data-tab="to-ship" style="display:flex; flex-direction:column; align-items:center;">
            <img src="/images/icons/to-ship.png" alt="To Ship" style="width:30px; height:30px; margin-bottom:5px;">
            <span>To Ship</span>
        </button>
        <button class="tab-button" data-tab="to-receive" style="display:flex; flex-direction:column; align-items:center;">
            <img src="/images/icons/to-receive.png" alt="To Receive" style="width:30px; height:30px; margin-bottom:5px;">
            <span>To Receive</span>
        </button>
        <button class="tab-button active" data-tab="completed" style="display:flex; flex-direction:column; align-items:center;">
            <img src="/images/icons/completed.png" alt="Completed" style="width:30px; height:30px; margin-bottom:5px;">
            <span>Completed</span>
        </button>
        <button class="tab-button" data-tab="cancelled" style="display:flex; flex-direction:column; align-items:center;">
            <img src="/images/icons/cancelled.png" alt="Cancelled" style="width:30px; height:30px; margin-bottom:5px;">
            <span>Cancelled</span>
        </button>
    </div>


    <?php
    $tab_orders = [
        'to-ship' => array_filter($orders, fn($o) => $o['status'] == 'pending'),
        'to-receive' => array_filter($orders, fn($o) => in_array($o['status'], ['shipped', 'delivered'])),
        'completed' => array_filter($orders, fn($o) => $o['status'] == 'completed'),
        'cancelled' => array_filter($orders, fn($o) => $o['status'] == 'cancelled'),
    ];
    ?>

    <?php foreach ($tab_orders as $tab => $orders_list): ?>

        <div class="tab-content <?= $tab ?>" style="display: <?= $tab == 'to-ship' ? 'flex' : 'none' ?>; flex-direction:column; gap:20px;">
            <?php if (empty($orders_list)): ?>
                <p style="text-align:center; font-size:1.1em; color:#7f8c8d;">No orders here.</p>
            <?php else: ?>
                <?php foreach ($orders_list as $order): ?>
                    <div class="order-card"
                        onclick="window.location='/page/order_details.php?order_id=<?= $order['order_id'] ?>'"
                        style="border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff; display:flex; flex-direction:column;">

                        <!-- Order Info -->
                        <div style="background:#ecf0f1; padding:20px;">
                            <p style="font-weight:600; margin-bottom:5px;">Order ID: <?= $order['order_id'] ?></p>
                            <p style="margin-bottom:5px;">Order Date: <?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
                            <p style="margin-bottom:5px;">Total: RM <?= number_format($order['total_amount'], 2) ?></p>
                            <p style="font-weight:600; color:#e74c3c;">Status: <?= ucfirst($order['status']) ?></p>
                        </div>

                        <!-- Products -->
                        <div style="padding:15px; display:flex; flex-wrap:wrap; gap:10px;">
                            <?php
                            $stm_items = $_db->prepare("
                        SELECT p.product_name, p.product_image, p.category_id
                        FROM order_item oi
                        JOIN product p ON oi.product_id = p.product_id
                        WHERE oi.order_id = ?
                    ");
                            $stm_items->execute([$order['order_id']]);
                            $items = $stm_items->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php foreach ($items as $item):
                                $folder = '';
                                if ($item['category_id'] == 'CA0001') $folder = 'glasses';
                                else if ($item['category_id'] == 'CA0002') $folder = 'contactlens';
                                else if ($item['category_id'] == 'CA0003') $folder = 'sunglasses';
                                else if ($item['category_id'] == 'CA0004') $folder = 'accessories';
                            ?>
                                <div style="width:90px; text-align:center;">
                                    <img src="/images/product/<?= $folder ?>/<?= $item['product_image'] ?>"
                                        alt="<?= $item['product_name'] ?>" style="width:100%; height:auto; border-radius:6px; margin-bottom:5px;">
                                    <span style="font-size:0.85em; display:block;"><?= $item['product_name'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Delivered button -->
                        <?php if ($order['status'] == 'delivered'): ?>
                            <form method="post" action="/page/receive_order.php" style="padding:15px;">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <button type="submit" class="cta-button" style="background:#27ae60; color:white; padding:10px 15px; border:none; border-radius:4px; cursor:pointer;">Mark as Received</button>
                            </form>
                        <?php endif; ?>

                        <!-- Cancel button -->
                        <?php if ($order['status'] == 'pending'): ?>
                            <button type="button"
                                onclick="event.stopPropagation(); openCancelModal('<?= $order['order_id'] ?>')" class="cta-button"
                                style="background:#e74c3c; color:white; padding:10px 15px; border:none; border-radius:4px; margin:15px; cursor:pointer;">
                                Cancel Order
                            </button>
                        <?php endif; ?>

                        <!-- Rate button -->
                        <?php if ($order['status'] == 'completed'): ?>
                            <button type="button"
                                onclick="window.location='/page/rate_order.php?order_id=<?= $order['order_id'] ?>'" class="cta-button"
                                style="background:#f39c12; color:white; padding:10px 15px; border:none; border-radius:4px; margin:15px; cursor:pointer;">
                                Rate Order
                            </button>
                        <?php endif; ?>


                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endforeach; ?>

    <!-- Cancel Order Modal -->

    <div id="cancelModal" style="
    position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.5); display:none; justify-content:center; align-items:center;
">
        <div style="
        background:white; padding:25px; border-radius:6px; width:350px;
        box-shadow:0 2px 10px rgba(0,0,0,0.2);
    ">
            <h3 style="margin-top:0;">Cancel Order</h3>
            <p>Please select a reason for cancellation:</p>

            <form id="cancelForm" method="POST" action="/page/cancel_order.php" onsubmit="return confirmCancel()">
                <input type="hidden" name="order_id" id="cancel_order_id">

                <select name="cancelled_reason" required style="width:100%; padding:8px; margin-bottom:15px;">
                    <option value="">-- Select a reason --</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Found a better price">Found a better price</option>
                    <option value="Ordered by mistake">Ordered by mistake</option>
                    <option value="Delivery taking too long">Delivery taking too long</option>
                    <option value="Incorrect item selected">Incorrect item selected</option>
                    <option value="Other">Other</option>
                </select>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeCancelModal()" class="cta-button"
                        style="padding:8px 15px; background:#bdc3c7; border:none; border-radius:4px;">
                        Close
                    </button>
                    <button type="submit" class="cta-button" style="padding:8px 15px; background:#e74c3c; color:white; border:none; border-radius:4px;">
                        Confirm Cancel
                    </button>
                </div>
            </form>

        </div>

    </div>

    <script>
        function openCancelModal(orderId) {
            document.getElementById('cancel_order_id').value = orderId;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }

        // Tabs switching
        const tabs = document.querySelectorAll('.tab-button');
        const contents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const selected = tab.dataset.tab;
                contents.forEach(c => c.style.display = c.classList.contains(selected) ? 'flex' : 'none');
            });
        });

        function confirmCancel() {
            const reason = document.getElementById('cancelForm').cancelled_reason.value;
            if (!reason) return false; // Prevent submit if no reason

            return confirm(`Are you sure you want to cancel this order for the reason: "${reason}"?`);
        }
    </script>

    <?php include '../_foot.php'; ?>