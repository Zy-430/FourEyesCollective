<?php
require '../_base.php';
require '../lib/db.php';

auth();
$order_id = $_GET['order_id'] ?? null;
if (!$order_id) exit("Invalid order");

// Fetch order + payment + user + address
$stm = $_db->prepare("
    SELECT 
        o.order_id, o.order_date, o.total_amount, o.status,
        u.name AS customer_name,
        a.recipient_name, a.address_line1, a.address_line2, a.city, a.state, a.postcode, a.country,
        p.payment_method_type AS payment_brand, 
        p.transaction_date
    FROM `order` o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN address a ON a.address_id = o.address_id
    LEFT JOIN payment p ON p.order_id = o.order_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stm->execute([$order_id, $_user->user_id]);
$order = $stm->fetch(PDO::FETCH_ASSOC);

if (!$order) exit("Order not found");

// Fetch items
$stm_items = $_db->prepare("
    SELECT oi.*, p.product_name, p.product_image, p.category_id, oi.product_qty, oi.price
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stm_items->execute([$order_id]);
$items = $stm_items->fetchAll(PDO::FETCH_ASSOC);

// Fetch category folder mapping dynamically
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categories = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR); 
// ['CA0001' => 'glasses', 'CA0002' => 'sunglasses', ...]

// Fetch shipping history
$stm_hist = $_db->prepare("
    SELECT * FROM order_history
    WHERE order_id = ?
    ORDER BY changed_at ASC
");
$stm_hist->execute([$order_id]);
$history = $stm_hist->fetchAll(PDO::FETCH_ASSOC);

$_title = "Order Details | Four Eyes Collective";
$_css = ['order.css'];
include '../_head.php';
?>

<div style="max-width:900px;margin:40px auto; display:flex; flex-direction:column; gap:20px;">

    <!-- Order Info Card -->
    <div class="card" style="padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff;">
        <h2>Order Details</h2>
        <p><strong>Order ID:</strong> <?= encode($order['order_id']) ?></p>
        <p><strong>Status:</strong> <?= ucfirst($order['status']) ?></p>
        <p><strong>Order Date:</strong> <?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
    </div>

    <!-- Products Card -->
    <div class="card" style="padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff;">
        <h3>Order Item(s)</h3>

        <?php foreach ($items as $item): ?>
            <div style="
                display:flex; 
                justify-content:space-between; 
                align-items:center; 
                padding:10px 15px; 
                border-radius:8px; 
                background-color: #fcefee; 
                margin-bottom:10px;
            ">
                <!-- Product Info -->
                <div style="display:flex; align-items:center; gap:15px;">
                    <img src="/images/product/<?= encode($categories[$item['category_id']] ?? 'other') ?>/<?= encode($item['product_image']) ?>"
                        style="width:70px; height:auto; border-radius:6px;">
                    <div>
                        <p style="margin:0; font-weight:600;"><?= encode($item['product_name']) ?></p>
                        <p style="margin:0; color:#7f8c8d;">Qty: <?= $item['product_qty'] ?></p>
                    </div>
                </div>

                <!-- Price -->
                <div style="font-weight:600; color:#2c3e50;">
                    RM <?= number_format($item['price'], 2) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment Summary Card -->
    <div class="card" style="padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff;">
        <h3>Payment Details</h3>
        <p><strong>Total Amount:</strong> RM <?= number_format($order['total_amount'], 2) ?></p>
        <p><strong>Payment Method:</strong> <?= $order['payment_brand'] ?? '-' ?></p>
        <p><strong>Transaction Date:</strong> <?= $order['transaction_date'] ? date('d M Y H:i', strtotime($order['transaction_date'])) : '-' ?></p>
    </div>

    <!-- Shipping Address Card -->
    <div class="card" style="padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff;">
        <h3>📍 Shipping Address</h3>
        <p><?= encode($order['recipient_name']) ?></p>
        <p><?= encode($order['address_line1']) ?> <?= encode($order['address_line2']) ?></p>
        <p><?= encode($order['city']) ?>, <?= encode($order['state']) ?> <?= encode($order['postcode']) ?></p>
        <p><?= encode($order['country']) ?></p>
    </div>

    <!-- Shipping Timeline Card -->
    <div class="card" style="padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff;">
        <h3>Shipping Timeline</h3>
        <?php if (empty($history)): ?>
            <p>No shipping updates yet.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($history as $h): ?>
                    <div class="timeline-item status-<?= encode($h['status']) ?> <?= $h['status'] === $order['status'] ? 'active' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?= ucfirst($h['status']) ?></strong>
                                <span><?= date('d M Y H:i', strtotime($h['changed_at'])) ?></span>
                            </div>
                            <p><?= encode($h['message']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Documents Card (Completed Orders Only) -->
    <?php if ($order['status'] == 'completed'): ?>
        <div class="card" style="padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff;">
            <h3>Documents</h3>
            <a href="/page/order_invoice.php?order_id=<?= encode($order['order_id']) ?>&type=pdf" class="cta-button"
                style="background:#D9BAFC; padding:10px 15px; color:white; border-radius:4px; text-decoration:none;">
                Download PDF
            </a>
            <form method="POST" action="/page/order_invoice.php" style="display:inline;">
                <input type="hidden" name="order_id" value="<?= encode($order['order_id']) ?>">
                <input type="hidden" name="type" value="email">
                <button type="submit" class="cta-button"
                    style="background:#D9BAFC; padding:13px 15px; color:white; border-radius:4px; border:none; cursor:pointer;">
                    Send to Email
                </button>
            </form>
            <a href="/page/order_receipt.php?order_id=<?= encode($order['order_id']) ?>" class="cta-button"
                style="background:#D9BAFC; padding:10px 15px; color:white; border-radius:4px; text-decoration:none;">
                View Receipt
            </a>
        </div>
    <?php endif; ?>

</div>

<?php include '../_foot.php'; ?>