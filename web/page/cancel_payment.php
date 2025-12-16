<?php
require '../_base.php';
require '../lib/db.php';

auth();

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    include_cancellation_template('Invalid Order', 'Invalid order ID provided.');
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
    
    // Set order as cancelled with payment failed reason
    $_db->prepare("
        UPDATE `order` SET 
            status = 'cancelled',
            cancelled_reason = 'Payment failed'
        WHERE order_id = ? AND user_id = ?
    ")->execute([$order_id, $_user->user_id]);
    
    // Get order items
    $stm = $_db->prepare("
        SELECT oi.order_item_id, oi.product_id, oi.product_qty
        FROM order_item oi
        WHERE oi.order_id = ?
    ");
    $stm->execute([$order_id]);
    $items = $stm->fetchAll(PDO::FETCH_OBJ);
    
    // Restore product stock
    foreach ($items as $item) {
        $_db->prepare("
            UPDATE cart_item 
            SET order_item_id = NULL, 
                item_status = 'in_cart',
                checkout_at = NULL
            WHERE order_item_id = ? AND user_id = ?
        ")->execute([$item->order_item_id, $_user->user_id]);

        // Restore product stock
        $_db->prepare("
            UPDATE product 
            SET product_stock = product_stock + ?
            WHERE product_id = ?
        ")->execute([$item->product_qty, $item->product_id]);
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
        VALUES (?, ?, 'cancelled', NOW(), ?, 'Payment failed, order cancelled')
    ")->execute([$history_id, $order_id, $_user->user_id]);
    
    $_db->commit();
    
    // Store order details for potential reorder
    $_SESSION['cancelled_order'] = [
        'order_id' => $order_id,
        'items' => $items,
        'timestamp' => time()
    ];
    
    // Display cancellation success page with reorder options
    display_cancellation_page(
        'Payment Failed',
        $order_id,
        $items,
        'payment'
    );
    
} catch (Exception $ex) {
    $_db->rollBack();
    include_cancellation_template('Error Processing Cancellation', $ex->getMessage());
}

/**
 * Display cancellation page with options
 */
function display_cancellation_page($title, $order_id, $items, $type = 'payment') {
    $item_count = count($items);
    $total_quantity = 0;
    foreach ($items as $item) {
        $total_quantity += $item->product_qty;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Failed | Four Eyes Collective</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Roboto', sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            .info { background: #fff3cd; color: #856404; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 5px solid #ffc107; }
            .error { background: #f8d7da; color: #721c24; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 5px solid #e74c3c; }
            .success { background: #d4edda; color: #155724; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 5px solid #28a745; }
            .btn { display: inline-block; padding: 12px 25px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px; margin: 15px 10px; font-weight: bold; cursor: pointer; }
            .btn-primary { background: #27ae60; }
            .btn-secondary { background: #95a5a6; }
            .btn-warning { background: #ffc107; color: #000; }
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
            <h1 style="color: #2c3e50; font-family: 'Playfair Display', serif;">Payment Failed</h1>
            
            <div class="error">
                <h3 style="margin-top: 0;">Payment Unsuccessful</h3>
                <p>Your payment for order <strong><?= encode($order_id) ?></strong> failed to process.</p>
                <p>No charges have been made to your account.</p>
            </div>
            
            <div class="info">
                <h4 style="margin-top: 0;">Order Details:</h4>
                <p><strong><?= $item_count ?> product(s)</strong> with total of <strong><?= $total_quantity ?> item(s)</strong></p>
            </div>
            
            <div class="order-summary">
                <h4>What would you like to do?</h4>
                <ul>
                    <li><strong>Order Again:</strong> Add all items from this order to your cart</li>
                    <li><strong>Shop Products:</strong> Browse our collection and add different items</li>
                    <li><strong>View Cart:</strong> Review and modify your current cart</li>
                </ul>
            </div>
            
            <div style="margin-top: 40px;">
                <a href="shoppage.php" class="btn btn-warning">Shop Other Products</a>
                <a href="cart.php" class="btn btn-secondary">View Cart</a>
            </div>
            
            <p style="margin-top: 30px; color: #666; font-size: 0.9rem;">
                Need help? <a href="/page/contact.php" style="color: #2c3e50; text-decoration: underline;">Contact our support team</a>
            </p>
        </div>
        
        <script>
            setTimeout(() => {
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            }, 500);
        </script>
    </body>
    </html>
    <?php
}


function include_cancellation_template($title, $message) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?= $title ?> | Four Eyes Collective</title>
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
            <h1 style="color: #2c3e50; font-family: 'Playfair Display', serif;">❌ <?= $title ?></h1>
            <div class="error"><?= encode($message) ?></div>
            <a href="cart.php" class="btn">Return to Cart</a>
            <a href="shoppage.php" class="btn">Continue Shopping</a>
        </div>
    </body>
    </html>
    <?php
}
?>