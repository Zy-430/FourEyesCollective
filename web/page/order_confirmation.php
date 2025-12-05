<?php
require '../_base.php';
require '../lib/db.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/page/login.php');
}

$order_id = get('id');
$user_id = $_SESSION['user_id'];

// Get order details
$stm = $_db->prepare("
    SELECT o.*, a.*, p.status as payment_status, p.amount as payment_amount
    FROM `order` o
    LEFT JOIN address a ON o.address_id = a.address_id
    LEFT JOIN payment p ON o.order_id = p.order_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stm->execute([$order_id, $user_id]);
$order = $stm->fetch();

if (!$order) {
    temp('error', 'Order not found');
    redirect('/page/shoppage.php');
}

// Get order items
$stm = $_db->prepare("
    SELECT oi.*, p.product_name, p.product_image
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stm->execute([$order_id]);
$order_items = $stm->fetchAll();

$_title = 'Order Confirmation | Four Eyes Collective';
include '../_head.php';
?>

<h1>Order Confirmation</h1>

<div style="text-align: center; margin-bottom: 40px;">
    <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; display: inline-block; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #155724;">Thank You for Your Order!</h2>
        <p style="margin: 10px 0 0 0;">Order #<?= encode($order->order_id) ?></p>
    </div>
    
    <p style="color: #666;">A confirmation email has been sent to your email address.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
    <!-- Order Details -->
    <div>
        <h3 style="color: #2c3e50; margin-bottom: 20px;">Order Details</h3>
        
        <div style="background: #f8f9fa; border-radius: 8px; padding: 20px;">
            <div style="margin-bottom: 15px;">
                <strong>Order Date:</strong> <?= date('F j, Y', strtotime($order->order_date)) ?>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>Order Status:</strong> 
                <span style="background: <?= $order->status == 'completed' ? '#d4edda' : '#fff3cd' ?>; 
                      color: <?= $order->status == 'completed' ? '#155724' : '#856404' ?>; 
                      padding: 2px 8px; border-radius: 4px; font-size: 0.9em;">
                    <?= ucfirst($order->status) ?>
                </span>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>Payment Status:</strong> 
                <span style="background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 4px; font-size: 0.9em;">
                    <?= ucfirst($order->payment_status) ?>
                </span>
            </div>
            <div>
                <strong>Total Amount:</strong> 
                <span style="font-weight: bold; font-size: 1.2em;">RM <?= number_format($order->total_amount, 2) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Shipping Address -->
    <div>
        <h3 style="color: #2c3e50; margin-bottom: 20px;">Shipping Address</h3>
        
        <div style="background: #f8f9fa; border-radius: 8px; padding: 20px;">
            <div style="margin-bottom: 10px;">
                <?= encode($order->address_line1) ?>
                <?php if ($order->address_line2): ?>
                    <br><?= encode($order->address_line2) ?>
                <?php endif; ?>
            </div>
            <div style="margin-bottom: 10px;">
                <?= encode($order->city) ?>, <?= encode($order->state) ?> <?= encode($order->postcoed) ?>
            </div>
            <div>
                <?= encode($order->country) ?>
            </div>
        </div>
    </div>
</div>

<!-- Order Items -->
<div>
    <h3 style="color: #2c3e50; margin-bottom: 20px;">Order Items</h3>
    
    <div style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
        <div style="background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e0e0e0;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 20px; font-weight: bold;">
                <div>Product</div>
                <div>Price</div>
                <div>Quantity</div>
                <div>Subtotal</div>
            </div>
        </div>
        
        <?php foreach ($order_items as $item): ?>
            <div style="padding: 20px; border-bottom: 1px solid #f0f0f0;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 20px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px;"></div>
                        <div>
                            <div style="font-weight: bold;"><?= encode($item->product_name) ?></div>
                        </div>
                    </div>
                    <div>RM <?= number_format($item->price, 2) ?></div>
                    <div><?= $item->product_qty ?></div>
                    <div style="font-weight: bold;">RM <?= number_format($item->subtotal, 2) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div style="background: #f8f9fa; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: bold;">Total Amount</div>
                <div style="font-weight: bold; font-size: 1.2em;">RM <?= number_format($order->total_amount, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div style="text-align: center; margin-top: 40px;">
    <a href="/page/shoppage.php" class="cta-button" style="display: inline-block; margin-right: 15px;">
        Continue Shopping
    </a>
    <a href="/page/orders.php" style="display: inline-block; padding: 12px 25px; border: 2px solid #2c3e50; color: #2c3e50; text-decoration: none; border-radius: 4px; font-weight: bold;">
        View All Orders
    </a>
</div>

<?php include '../_foot.php'; ?>