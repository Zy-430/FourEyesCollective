<?php
// order_create.php
require '../_base.php';
require '../lib/db.php';

auth('Member');

$user_id = $_user->user_id;

// Get selected cart items (already marked 'checkout')
$stm = $_db->prepare("
    SELECT ci.*, p.product_name, p.product_price 
    FROM cart_item ci
    JOIN product p ON ci.product_id = p.product_id
    WHERE ci.user_id = ? AND ci.item_status = 'checkout'
");

$stmt = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
$stm->execute([$user_id]);
$cart_items = $stm->fetchAll();

if (empty($cart_items)) {
    temp('error', 'No items selected for checkout');
    redirect('cart.php');
}

// Generate new order ID
$stm = $_db->query("SELECT MAX(CAST(SUBSTRING(order_id, 3) AS UNSIGNED)) AS max_id FROM `order`");
$max_order_id = $stm->fetch()->max_id ?? 0;
$new_order_id = 'OR' . str_pad($max_order_id + 1, 4, '0', STR_PAD_LEFT);

// Fetch default address for user, or first address if no default
$stm = $_db->prepare("
    SELECT address_id, recipient_name, address_line1, address_line2, city, state, postcode, default_flag
    FROM address
    WHERE user_id = ?
    ORDER BY default_flag DESC, created_at ASC
    LIMIT 1
");

$stm->execute([$user_id]);
$address = $stm->fetch();

if (!$address) {
    temp('error', 'No delivery address found. Please add an address first.');
    redirect('address_list.php'); // redirect to address management
}

$user_address_id = $address->address_id;

// Fetch default payment method for user (brand + last4)
$stm = $_db->prepare("
    SELECT * 
    FROM payment_method 
    WHERE user_id = ? 
    ORDER BY is_default DESC, created_at ASC 
    LIMIT 1
");
$stm->execute([$user_id]);
$payment_method = $stm->fetch();
$payment_method_name = $payment_method
    ? $payment_method->brand . ' **** **** **** ' . substr($payment_method->last4, -4)
    : 'Cash on Delivery';

// Calculate total amount
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item->product_price * $item->product_qty;
}

// Insert order
$stm = $_db->prepare("
    INSERT INTO `order` 
    (order_id, user_id, address_id, order_date, total_amount, status) 
    VALUES (?, ?, ?, NOW(), ?, 'pending')
");
$stm->execute([$new_order_id, $user_id, $user_address_id, $total_amount]);

// Insert order items and update cart items
foreach ($cart_items as $cart_item) {
    // Generate new order_item_id
    $stm2 = $_db->query("SELECT MAX(CAST(SUBSTRING(order_item_id, 3) AS UNSIGNED)) AS max_id FROM order_item");
    $max_order_item_id = $stm2->fetch()->max_id ?? 0;
    $new_order_item_id = 'OI' . str_pad($max_order_item_id + 1, 4, '0', STR_PAD_LEFT);

    // Insert into order_item
    $stm = $_db->prepare("
        INSERT INTO order_item
        (order_item_id, order_id, product_id, product_qty, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stm->execute([
        $new_order_item_id,
        $new_order_id,
        $cart_item->product_id,
        $cart_item->product_qty,
        $cart_item->product_price,
        $cart_item->product_price * $cart_item->product_qty
    ]);

    // Update cart item to link to order item
    $stm = $_db->prepare("
        UPDATE cart_item
        SET order_item_id = ?, item_status = 'ordered'
        WHERE cart_item_id = ?
    ");
    $stm->execute([$new_order_item_id, $cart_item->cart_item_id]);
}

// Display order confirmation
$_title = 'Order Confirmation | Four Eyes Collective';
include '../_head.php';
?>

<div style="max-width: 800px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
    <h1>Order Placed Successfully!</h1>
    <p>Order ID: <strong><?= $new_order_id ?></strong></p>
    <p>Total Amount: <strong>RM <?= number_format($total_amount, 2) ?></strong></p>


<h3>Delivery Address:</h3>
<p>
    <?= encode($address->recipient_name ?? $_user->name) ?><br>
    <?= encode($address->address_line1) ?><br>
    <?= $address->address_line2 ? encode($address->address_line2) . '<br>' : '' ?>
    <?= encode($address->city) ?>, <?= encode($address->state) ?> <?= encode($address->postcode) ?><br>
    <?= '+60 ' . encode($_user->phone) ?>

</p>

<h3>Payment Method:</h3>
<p><?= encode($payment_method_name) ?></p>

<h3>Items:</h3>
<ul>
    <?php foreach ($cart_items as $item): ?>
        <li><?= encode($item->product_name) ?> x <?= $item->product_qty ?> - RM <?= number_format($item->product_price * $item->product_qty, 2) ?></li>
    <?php endforeach; ?>
</ul>

<a href="shoppage.php" class="cta-button" style="padding: 10px 20px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px;">Continue Shopping</a>
<a href="order_history.php" class="cta-button" style="padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">View My Orders</a>

</div>

<?php include '../_foot.php'; ?>
