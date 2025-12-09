<?php
require '../_base.php';
require '../lib/db.php';

auth();
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) exit("Invalid order");


// Fetch order
$stm = $_db->prepare("
    SELECT * FROM `order` 
    WHERE order_id = ? AND user_id = ?
");
$stm->execute([$order_id, $_user->user_id]);
$order = $stm->fetch(PDO::FETCH_ASSOC);

if (!$order) exit("Order not found");


// Fetch items
$stm_items = $_db->prepare("
    SELECT oi.*, p.product_name, p.product_image, p.category_id
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stm_items->execute([$order_id]);
$items = $stm_items->fetchAll(PDO::FETCH_ASSOC);


// Fetch shipping history
$stm_hist = $_db->prepare("
    SELECT * FROM order_history
    WHERE order_id = ?
    ORDER BY changed_at ASC
");
$stm_hist->execute([$order_id]);
$history = $stm_hist->fetchAll(PDO::FETCH_ASSOC);


$_title = "Order Details | Four Eyes Collective";
include '../_head.php';
?>

<div style="max-width:900px;margin:40px auto;">

<h2>Order Details</h2>
<p><strong>Order ID:</strong> <?= encode($order['order_id']) ?></p>
<p><strong>Status:</strong> <?= ucfirst($order['status']) ?></p>
<p><strong>Order Date:</strong> <?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>

<hr>

<h3>Products</h3>
<?php foreach ($items as $item): ?>
    <div style="display:flex; gap:15px; margin-bottom:15px;">
        <img src="/images/product/<?= getFolder($item['category_id']) ?>/<?= encode($item['product_image']) ?>"
             style="width:150px;height:auto;border-radius:5px;">
        <div>
            <p><strong><?= encode($item['product_name']) ?></strong></p>
            <p>Quantity: <?= $item['product_qty'] ?></p>
            <p>Price: RM <?= number_format($item['price'], 2) ?></p>
        </div>
    </div>
<?php endforeach; ?>

<hr>

<h3>Payment Summary</h3>
<p><strong>Total Amount:</strong> RM <?= number_format($order['total_amount'], 2) ?></p>

<hr>

<h3>Shipping History</h3>
<?php if (empty($history)): ?>
    <p>No shipping updates yet.</p>
<?php else: ?>
    <?php foreach ($history as $h): ?>
        <div style="margin-bottom:10px;">
            <strong><?= date('d M Y H:i', strtotime($h['changed_at'])) ?></strong>
            <br>
            <?= encode($h['message']) ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<hr>

<?php if ($order['status'] == 'completed'): ?>
<h3>Documents</h3>

<!-- PDF download -->
<a href="/page/order_invoice.php?order_id=<?= encode($order['order_id']) ?>&type=pdf" class="cta-button"
   style="background:#D9BAFC; padding:10px 15px; color:white; border-radius:4px; text-decoration:none;">
    Download PDF
</a>

<!-- Send Email -->
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

<?php endif; ?>

</div>

<?php
// Helper function
function getFolder($cat) {
    return [
        'CA0001'=>'glasses',
        'CA0002'=>'contactlens',
        'CA0003'=>'sunglasses',
        'CA0004'=>'accessories',
    ][$cat] ?? 'other';
}
?>

<?php include '../_foot.php'; ?>
