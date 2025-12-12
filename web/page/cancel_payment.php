<?php
require '../_base.php';
require '../lib/db.php';

auth();

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Invalid Order | Four Eyes Collective</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Roboto', sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #e74c3c; }
            .btn { display: inline-block; padding: 12px 25px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px; margin: 10px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 style="color: #2c3e50; font-family: 'Playfair Display', serif;">❌ Invalid Order</h1>
            <div class="error">Invalid order ID provided.</div>
            <a href="cart.php" class="btn">Return to Cart</a>
            <a href="shoppage.php" class="btn">Continue Shopping</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$_db->beginTransaction();

try {
    // Verify order belongs to user and is in pending_payment status
    $stm = $_db->prepare("SELECT * FROM `order` WHERE order_id = ? AND user_id = ? AND status = 'pending_payment'");
    $stm->execute([$order_id, $_user->user_id]);
    $order = $stm->fetch(PDO::FETCH_OBJ);
    
    if (!$order) {
        throw new Exception("Order not found, already processed, or unauthorized access");
    }
    
    // Set order as cancelled
    $_db->prepare("
        UPDATE `order` SET 
            status = 'cancelled',
            cancelled_reason = 'Payment cancelled by user'
        WHERE order_id = ? AND user_id = ?
    ")->execute([$order_id, $_user->user_id]);
    
    // Get order items to restore to cart
    $stm = $_db->prepare("
        SELECT oi.order_item_id, oi.product_id, oi.product_qty
        FROM order_item oi
        WHERE oi.order_id = ?
    ");
    $stm->execute([$order_id]);
    $items = $stm->fetchAll(PDO::FETCH_OBJ);
    
    // Restore each item to cart
    $restored_items = [];
    $total_quantity = 0;
    foreach ($items as $item) {
        // Check if item already in cart
        $stm = $_db->prepare("
            SELECT * FROM cart_item 
            WHERE user_id = ? AND product_id = ? AND item_status = 'in_cart'
        ");
        $stm->execute([$_user->user_id, $item->product_id]);
        $existing = $stm->fetch();
        
        if ($existing) {
            // Update quantity
            $_db->prepare("
                UPDATE cart_item 
                SET product_qty = product_qty + ?
                WHERE cart_item_id = ?
            ")->execute([$item->product_qty, $existing->cart_item_id]);
        } else {
            // Insert new cart item
            $cart_item_id = "CI" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT);
            $_db->prepare("
                INSERT INTO cart_item (cart_item_id, user_id, product_id, product_qty, item_status, created_at)
                VALUES (?, ?, ?, ?, 'in_cart', NOW())
            ")->execute([$cart_item_id, $_user->user_id, $item->product_id, $item->product_qty]);
        }
        
        // Restore product stock
        $_db->prepare("
            UPDATE product 
            SET product_stock = product_stock + ?
            WHERE product_id = ?
        ")->execute([$item->product_qty, $item->product_id]);
        
        $restored_items[] = $item;
        $total_quantity += $item->product_qty;
    }
    
    // Update payment status if exists
    $_db->prepare("
        UPDATE payment SET status = 'cancelled' 
        WHERE order_id = ?
    ")->execute([$order_id]);
    
    // Insert order history
    $history_id = "HIS" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT);
    $_db->prepare("
        INSERT INTO order_history (history_id, order_id, status, changed_at, changed_by, message)
        VALUES (?, ?, 'cancelled', NOW(), ?, 'Payment cancelled by user, items restored to cart')
    ")->execute([$history_id, $order_id, $_user->user_id]);
    
    $_db->commit();
    
    // Display cancellation success page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Cancelled | Four Eyes Collective</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Roboto', sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            .info { background: #fff3cd; color: #856404; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 5px solid #ffc107; }
            .success { background: #d4edda; color: #155724; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 5px solid #28a745; }
            .btn { display: inline-block; padding: 12px 25px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px; margin: 15px 10px; font-weight: bold; }
            .btn-primary { background: #27ae60; }
            .btn-secondary { background: #95a5a6; }
            .cancel-icon { font-size: 4rem; color: #ffc107; margin: 20px 0; }
            ul { text-align: left; max-width: 400px; margin: 20px auto; }
            li { margin-bottom: 10px; }
            .order-summary { text-align: left; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-summary h4 { margin-top: 0; color: #2c3e50; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="cancel-icon">⚠️</div>
            <h1 style="color: #2c3e50; font-family: 'Playfair Display', serif;">Payment Cancelled</h1>
            
            <div class="info">
                <h3 style="margin-top: 0;">Checkout Process Interrupted</h3>
                <p>You have cancelled the payment process for order <strong><?= encode($order_id) ?></strong>.</p>
                <p>No charges have been made to your account.</p>
            </div>
            
            <div class="success">
                <h4 style="margin-top: 0;">Items Restored</h4>
                <p><strong><?= count($restored_items) ?> product(s)</strong> with total of <strong><?= $total_quantity ?> item(s)</strong> have been restored to your shopping cart.</p>
                <p>You can review and modify your cart before trying again.</p>
            </div>
            
            <div class="order-summary">
                <h4>What happened to your order?</h4>
                <ul>
                    <li>✅ Order status changed to "cancelled"</li>
                    <li>✅ No payment was processed</li>
                    <li>✅ All items restored to your cart</li>
                    <li>✅ Product stock has been updated</li>
                    <li>✅ You can retry checkout anytime</li>
                </ul>
            </div>
            
            <div style="margin-top: 40px;">
                <a href="cart.php" class="btn">Review Cart</a>
                <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
                <a href="order_history.php" class="btn btn-secondary">View Order History</a>
            </div>
            
            <p style="margin-top: 30px; color: #666; font-size: 0.9rem;">
                Need help? <a href="/page/contact.php" style="color: #2c3e50; text-decoration: underline;">Contact our support team</a>
            </p>
        </div>
        
        <script>
            // Update cart count after cancellation
            setTimeout(() => {
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            }, 500);
        </script>
    </body>
    </html>
    <?php
    
} catch (Exception $ex) {
    $_db->rollBack();
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error | Four Eyes Collective</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Roboto', sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #e74c3c; }
            .btn { display: inline-block; padding: 12px 25px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px; margin: 10px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 style="color: #2c3e50; font-family: 'Playfair Display', serif;">❌ Error Processing Cancellation</h1>
            <div class="error">
                <p><?= encode($ex->getMessage()) ?></p>
            </div>
            <a href="cart.php" class="btn">Return to Cart</a>
            <a href="order_history.php" class="btn">View Order History</a>
        </div>
    </body>
    </html>
    <?php
}
?>