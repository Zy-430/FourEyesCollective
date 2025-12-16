<?php
require '../_base.php';
require '../lib/db.php';
require_once '../stripe-php-19.0.0/init.php';

// Set your Stripe secret key
\Stripe\Stripe::setApiKey('sk_test_51SZZzU2LpkFiPUtITtnxkZtzongU6II64ZL8YSynXO951EcqTfIfRbWAl586Hh8LOXYexaqDtwwaO6rxwdOQvygm006Vp82pdb');

auth();

$session_id = $_GET['session_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

if (!$session_id || !$order_id) {
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Invalid Payment | Four Eyes Collective</title>
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                text-align: center;
                padding: 50px;
                background: #f8f9fa;
            }

            .container {
                max-width: 500px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }

            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 5px solid #e74c3c;
            }

            .btn {
                display: inline-block;
                padding: 12px 25px;
                background: #2c3e50;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px;
                font-weight: bold;
            }

            .btn-primary {
                background: #27ae60;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h1>❌ Invalid Payment</h1>
            <div class="error">Invalid payment session or order ID.</div>
            <a href="cart.php" class="btn">Return to Cart</a>
            <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    </body>

    </html>
<?php
    exit;
}

// Verify user owns this order
$stm = $_db->prepare("SELECT * FROM `order` WHERE order_id = ? AND user_id = ?");
$stm->execute([$order_id, $_user->user_id]);
$order = $stm->fetch(PDO::FETCH_OBJ);

if (!$order) {
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Unauthorized | Four Eyes Collective</title>
        <style>
            body {
                font-family: 'Roboto', sans-serif;
                text-align: center;
                padding: 50px;
                background: #f8f9fa;
            }

            .container {
                max-width: 500px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }

            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 5px solid #e74c3c;
            }

            .btn {
                display: inline-block;
                padding: 12px 25px;
                background: #2c3e50;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px;
                font-weight: bold;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h1>🚫 Unauthorized Access</h1>
            <div class="error">You are not authorized to view this order.</div>
            <a href="order_history.php" class="btn">View My Orders</a>
            <a href="shoppage.php" class="btn">Continue Shopping</a>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// Try to process payment
try {
    // Retrieve Stripe session
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
    $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);

    // Check if payment already processed
    $stm = $_db->prepare("SELECT * FROM payment WHERE stripe_session_id = ?");
    $stm->execute([$session_id]);
    $payment = $stm->fetch(PDO::FETCH_OBJ);

    if (!$payment) {
        throw new Exception("Payment record not found");
    }

    $_db->beginTransaction();

    if ($paymentIntent->status === 'succeeded') {
        // Payment successful
        // Update payment record
        $_db->prepare("
            UPDATE payment SET 
                status = 'succeeded',
                stripe_payment_intent = ?,
                stripe_payment_method = ?,
                payment_method_type = ?,
                last4 = ?,
                transaction_date = NOW()
            WHERE stripe_session_id = ?
        ")->execute([
            $session->payment_intent,
            $paymentIntent->payment_method,
            $session_id
        ]);

        // Update order status
        $_db->prepare("UPDATE `order` SET status = 'paid' WHERE order_id = ?")->execute([$order_id]);

        // Insert order history
        $history_id = "HIS" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT);
        $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, changed_at, changed_by, message)
            VALUES (?, ?, 'pending', NOW(), ?, 'Your order has been place.')
        ")->execute([$history_id, $order_id, $_user->user_id]);

        // Get order details
        $stm = $_db->prepare("
            SELECT o.*, a.* 
            FROM `order` o
            JOIN address a ON o.address_id = a.address_id
            WHERE o.order_id = ? AND o.user_id = ?
        ");
        $stm->execute([$order_id, $_user->user_id]);
        $order_details = $stm->fetch(PDO::FETCH_OBJ);

        // Get order items
        $stm = $_db->prepare("
            SELECT oi.*, p.product_name, p.product_image, c.category_name
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            LEFT JOIN category c ON p.category_id = c.category_id
            WHERE oi.order_id = ?
        ");
        $stm->execute([$order_id]);
        $order_items = $stm->fetchAll(PDO::FETCH_OBJ);

        $_db->commit();

        // Display success page
    ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Payment Successful | Four Eyes Collective</title>
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Roboto', sans-serif;
                    text-align: center;
                    padding: 50px;
                    background: #f8f9fa;
                }

                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                }

                .success {
                    background: #d4edda;
                    color: #155724;
                    padding: 25px;
                    border-radius: 8px;
                    margin: 30px 0;
                    border-left: 5px solid #28a745;
                }

                .btn {
                    display: inline-block;
                    padding: 12px 25px;
                    background: #2c3e50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 15px 10px;
                    font-weight: bold;
                }

                .btn-primary {
                    background: #27ae60;
                }

                .order-details {
                    text-align: left;
                    background: #f8f9fa;
                    padding: 25px;
                    border-radius: 10px;
                    margin: 30px 0;
                }

                .order-details h3 {
                    color: #2c3e50;
                    border-bottom: 2px solid #e0e0e0;
                    padding-bottom: 10px;
                    font-family: 'Playfair Display', serif;
                }

                .order-item {
                    display: flex;
                    align-items: center;
                    padding: 15px;
                    background: white;
                    border-radius: 8px;
                    margin: 10px 0;
                    border: 1px solid #e0e0e0;
                }

                .order-item img {
                    width: 60px;
                    height: 60px;
                    object-fit: cover;
                    border-radius: 6px;
                    margin-right: 15px;
                }

                .checkmark {
                    font-size: 4rem;
                    color: #27ae60;
                    margin: 20px 0;
                }

                .order-info {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin: 20px 0;
                }

                .info-box {
                    background: white;
                    padding: 15px;
                    border-radius: 8px;
                    border: 1px solid #e0e0e0;
                }

                .info-label {
                    color: #7f8c8d;
                    font-size: 0.9rem;
                }

                .info-value {
                    font-weight: bold;
                    color: #2c3e50;
                    margin-top: 5px;
                }
            </style>
        </head>

        <body>
            <div class="container">
                <div class="checkmark">✅</div>
                <h1 style="color: #2c3e50; font-family: 'Playfair Display', serif;">🎉 Payment Successful!</h1>

                <div class="success">
                    <h2 style="margin-top: 0;">Thank You for Your Order!</h2>
                    <p>Your order <strong><?= encode($order_id) ?></strong> has been confirmed.</p>
                    <p>A confirmation email has been sent to <?= encode($_user->email) ?></p>
                </div>

                <div class="order-info">
                    <div class="info-box">
                        <div class="info-label">Order ID</div>
                        <div class="info-value"><?= encode($order_id) ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?= date('F j, Y H:i', strtotime($order_details->order_date)) ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value" style="color: #27ae60;">RM <?= number_format($order_details->total_amount, 2) ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Status</div>
                        <div class="info-value" style="color: #27ae60; font-weight: bold;">Paid</div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="order-details">
                    <h3>Shipping Address</h3>
                    <p style="line-height: 1.8;">
                        <strong><?= encode($order_details->recipient_name) ?></strong><br>
                        <?= encode($order_details->address_line1) ?><br>
                        <?php if ($order_details->address_line2): ?>
                            <?= encode($order_details->address_line2) ?><br>
                        <?php endif; ?>
                        <?= encode($order_details->city . ', ' . $order_details->state . ' ' . $order_details->postcode) ?><br>
                        <?= encode($order_details->country) ?>
                    </p>

                    <h3>Order Items</h3>
                    <?php foreach ($order_items as $item): ?>
                        <?php
                        $folder = [
                            'CA0001' => 'glasses',
                            'CA0002' => 'sunglasses',
                            'CA0003' => 'contactlens',
                            'CA0004' => 'kids'
                        ][$item->category_id] ?? 'others';
                        $imgArray = explode(',', $item->product_image);
                        $firstImage = trim($imgArray[0]);
                        $imgPath = "/images/product/$folder/$firstImage";
                        ?>
                        <div class="order-item">
                            <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>">
                            <div style="flex: 1;">
                                <strong style="color: #2c3e50;"><?= encode($item->product_name) ?></strong><br>
                                <small style="color: #7f8c8d;">Quantity: <?= $item->product_qty ?> × RM <?= number_format($item->price, 2) ?></small>
                            </div>
                            <div style="font-weight: bold; color: #2c3e50;">
                                RM <?= number_format($item->subtotal, 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                        <div style="font-size: 1.2rem; font-weight: bold; color: #2c3e50;">
                            Total: RM <?= number_format($order_details->total_amount, 2) ?>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 40px;">
                    <a href="order_history.php" class="btn">View My Orders</a>
                    <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
                </div>

                <p style="margin-top: 30px; color: #666; font-size: 0.9rem;">
                    Need help? <a href="/page/contact.php" style="color: #2c3e50; text-decoration: underline;">Contact our support team</a>
                </p>
            </div>
        </body>

        </html>
    <?php

    } else {
        // Payment failed in Stripe
        throw new Exception("Payment failed. Status: " . $paymentIntent->status);
    }
} catch (Exception $e) {
    // Payment failed - handle failure
    try {
        // Mark payment as failed
        $_db->prepare("
            UPDATE payment SET status = 'failed' WHERE stripe_session_id = ?
        ")->execute([$session_id]);

        // Mark order as cancelled
        $_db->prepare("
            UPDATE `order` 
            SET status = 'cancelled', 
                cancelled_reason = ?
            WHERE order_id = ?
        ")->execute(["Payment failed: " . $e->getMessage(), $order_id]);

        // Get order items to restore to cart
        $stm = $_db->prepare("
            SELECT oi.order_item_id, oi.product_id, oi.product_qty
            FROM order_item oi
            WHERE oi.order_id = ?
        ");
        $stm->execute([$order_id]);
        $items = $stm->fetchAll(PDO::FETCH_OBJ);

        // Restore each item to cart
        $restored_count = 0;
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

            $restored_count++;
        }

        // Insert order history
        $history_id = "HIS" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT);
        $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, changed_at, changed_by, message)
            VALUES (?, ?, 'cancelled', NOW(), ?, 'Payment failed, items restored to cart')
        ")->execute([$history_id, $order_id, $_user->user_id]);

        $_db->commit();

        // Display payment failed page
    ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Payment Failed | Four Eyes Collective</title>
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Roboto', sans-serif;
                    text-align: center;
                    padding: 50px;
                    background: #f8f9fa;
                }

                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                }

                .error {
                    background: #f8d7da;
                    color: #721c24;
                    padding: 25px;
                    border-radius: 8px;
                    margin: 30px 0;
                    border-left: 5px solid #e74c3c;
                }

                .info {
                    background: #e3f2fd;
                    color: #0d47a1;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    border-left: 5px solid #2196f3;
                }

                .btn {
                    display: inline-block;
                    padding: 12px 25px;
                    background: #2c3e50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 15px 10px;
                    font-weight: bold;
                }

                .btn-primary {
                    background: #27ae60;
                }

                .btn-secondary {
                    background: #95a5a6;
                }

                .cancel-icon {
                    font-size: 4rem;
                    color: #e74c3c;
                    margin: 20px 0;
                }

                ul {
                    text-align: left;
                    max-width: 400px;
                    margin: 20px auto;
                }

                li {
                    margin-bottom: 10px;
                }
            </style>
        </head>

        <body>
            <div class="container">
                <div class="cancel-icon">❌</div>
                <h1 style="color: #2c3e50; font-family: 'Playfair Display', serif;">Payment Failed</h1>

                <div class="error">
                    <h3 style="margin-top: 0;">Payment Unsuccessful</h3>
                    <p>Your payment for order <strong><?= encode($order_id) ?></strong> failed to process.</p>
                    <p><strong>Reason:</strong> <?= encode($e->getMessage()) ?></p>
                </div>

                <div class="info">
                    <h4 style="margin-top: 0;">What happened?</h4>
                    <ul>
                        <li>Your order has been marked as cancelled</li>
                        <li>No payment was charged to your account</li>
                        <li><strong><?= $restored_count ?> item(s)</strong> have been restored to your shopping cart</li>
                        <li>Product stock has been updated accordingly</li>
                    </ul>
                </div>

                <div style="margin-top: 40px;">
                    <a href="cart.php" class="btn">Go to Cart</a>
                    <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="order_history.php" class="btn btn-secondary">View Order History</a>
                </div>

                <p style="margin-top: 30px; color: #666; font-size: 0.9rem;">
                    Need help with payment? <a href="/page/contact.php" style="color: #2c3e50; text-decoration: underline;">Contact our support team</a>
                </p>
            </div>
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
            <style>
                body {
                    font-family: 'Roboto', sans-serif;
                    text-align: center;
                    padding: 50px;
                    background: #f8f9fa;
                }

                .container {
                    max-width: 500px;
                    margin: 0 auto;
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                }

                .error {
                    background: #f8d7da;
                    color: #721c24;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    border-left: 5px solid #e74c3c;
                }

                .btn {
                    display: inline-block;
                    padding: 12px 25px;
                    background: #2c3e50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 10px;
                    font-weight: bold;
                }
            </style>
        </head>

        <body>
            <div class="container">
                <h1>⚠️ System Error</h1>
                <div class="error">
                    <p>An error occurred while processing your payment:</p>
                    <p><strong><?= encode($ex->getMessage()) ?></strong></p>
                </div>
                <a href="cart.php" class="btn">Return to Cart</a>
                <a href="/page/contact.php" class="btn">Contact Support</a>
            </div>
        </body>

        </html>
<?php
    }
}
?>