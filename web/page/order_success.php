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

    $methodType = $paymentMethod->type;

    $brand   = null;
    $funding = null;
    $last4   = null;
    $bank    = null;

    if ($methodType === 'card') {
        $brand   = $paymentMethod->card->brand;     // visa / mastercard
        $funding = $paymentMethod->card->funding;   // credit / debit
        $last4   = $paymentMethod->card->last4;
    }

    if ($methodType === 'fpx') {
        $bank = $paymentMethod->fpx->bank;           // maybank2u, cimb
    }


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
                card_brand = ?,
                card_funding = ?,
                last4 = ?,
                bank_name = ?,
                transaction_date = NOW()
            WHERE stripe_session_id = ?
        ")->execute([
            $session->payment_intent,
            $paymentIntent->payment_method,
            $methodType,
            $brand,
            $funding,
            $last4,
            $bank,
            $session_id
        ]);

        // Update order status
        $_db->prepare("UPDATE `order` SET status = 'pending' WHERE order_id = ?")->execute([$order_id]);

        // Insert order history
        $history_id = "HIS" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT);
        $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, changed_at, changed_by, message)
            VALUES (?, ?, 'pending', NOW(), ?, 'The order has been placed.')
        ")->execute([$history_id, $order_id, $_user->user_id]);

        $_db->commit();

        // Display success page
    ?>
        <?php
        // ---------------- Payment display preparation ----------------
        $paymentLabel = '';
        $paymentExtra = '';

        if ($methodType === 'card') {
            $paymentLabel = ucfirst($brand) . ' ' . ucfirst($funding) . ' Card';
            $paymentExtra = '•••• ' . $last4;
        } elseif ($methodType === 'fpx') {
            $paymentLabel = 'FPX Online Banking';
            $paymentExtra = strtoupper($bank);
        } elseif ($methodType === 'grabpay') {
            $paymentLabel = 'GrabPay Wallet';
            $paymentExtra = 'Paid via GrabPay';
        } else {
            $paymentLabel = ucfirst($methodType);
        }
        ?>

        <!DOCTYPE html>
        <html>

        <head>
            <title>Payment Successful | Four Eyes Collective</title>
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Roboto', sans-serif;
                    background: #f8f9fa;
                    padding: 40px;
                    text-align: center;
                }

                .container {
                    max-width: 900px;
                    margin: auto;
                    background: #fff;
                    padding: 40px;
                    border-radius: 16px;
                    box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
                }

                .checkmark {
                    font-size: 4rem;
                    color: #27ae60;
                }

                h1 {
                    font-family: 'Playfair Display', serif;
                    color: #2c3e50;
                }

                .success {
                    background: #eafaf1;
                    border-left: 6px solid #27ae60;
                    padding: 25px;
                    margin: 30px 0;
                    border-radius: 8px;
                }

                .order-info {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 20px;
                    margin-top: 25px;
                }

                .info-box {
                    border: 1px solid #e0e0e0;
                    border-radius: 10px;
                    padding: 15px;
                    background: #fafafa;
                }

                .info-label {
                    font-size: .85rem;
                    color: #7f8c8d;
                }

                .info-value {
                    font-weight: bold;
                    margin-top: 5px;
                    color: #2c3e50;
                }

                .section {
                    text-align: left;
                    margin-top: 40px;
                }

                .section h3 {
                    border-bottom: 2px solid #e0e0e0;
                    padding-bottom: 10px;
                    font-family: 'Playfair Display', serif;
                }

                .order-item {
                    display: flex;
                    justify-content: space-between;
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .btn {
                    display: inline-block;
                    margin: 20px 10px;
                    padding: 12px 25px;
                    border-radius: 6px;
                    text-decoration: none;
                    color: #fff;
                    font-weight: bold;
                    background: #2c3e50;
                }

                .btn-primary {
                    background: #27ae60;
                }
            </style>
        </head>

        <body>
            <div class="container">

                <div class="checkmark">✅</div>
                <h1>Payment Successful</h1>

                <div class="success">
                    <p>Thank you <strong><?= encode($_user->name) ?></strong></p>
                    <p>Your order <strong><?= encode($order->order_id) ?></strong> has been confirmed.</p>
                    <p>A confirmation email has been sent to <strong><?= encode($_user->email) ?></strong></p>
                </div>

                <!-- Order Summary -->
                <div class="order-info">
                    <div class="info-box">
                        <div class="info-label">Order ID</div>
                        <div class="info-value"><?= encode($order->order_id) ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?= date('d M Y, H:i') ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Order Status</div>
                        <div class="info-value" style="color:#27ae60;">Paid</div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value" style="color:#27ae60;">
                            RM <?= number_format($order->total_amount, 2) ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="section">
                    <h3>Payment Details</h3>
                    <div class="order-info">
                        <div class="info-box">
                            <div class="info-label">Payment Method</div>
                            <div class="info-value"><?= encode($paymentLabel) ?></div>
                        </div>
                        <?php if ($paymentExtra): ?>
                            <div class="info-box">
                                <div class="info-label"><?= $methodType === 'card' ? 'Card Number' : 'Details' ?></div>
                                <div class="info-value"><?= encode($paymentExtra) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="info-box">
                            <div class="info-label">Transaction ID</div>
                            <div class="info-value"><?= encode($session->payment_intent) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="section">
                    <h3>Order Items</h3>
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div>
                                <strong><?= encode($item->product_name) ?></strong><br>
                                Qty: <?= $item->product_qty ?>
                            </div>
                            <div>
                                RM <?= number_format($item->subtotal, 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="order_history.php" class="btn">View Orders</a>
                <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>

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