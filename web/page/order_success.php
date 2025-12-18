<?php
require '../_base.php';
require '../lib/db.php';
require_once '../stripe-php-19.0.0/init.php';
require '../lib/category.php';

\Stripe\Stripe::setApiKey('sk_test_51SZZzU2LpkFiPUtITtnxkZtzongU6II64ZL8YSynXO951EcqTfIfRbWAl586Hh8LOXYexaqDtwwaO6rxwdOQvygm006Vp82pdb');

auth();

$session_id = $_GET['session_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;

if (!$session_id || !$order_id) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="/images/WIS_logo_white.png">
        <title>Invalid Payment | Four Eyes Collective</title>
        <link rel="stylesheet" href="/css/checkout_flow.css">
        <link rel="stylesheet" href="/css/app.css">
    </head>

    <body>
        <div class="checkout-status-container status-error">
            <div class="page-header">
                <h1>❌ Invalid Payment</h1>
                <p>Invalid payment session or order ID.</p>
            </div>
            <div class="checkout-section" style="text-align: center;">
                <div class="error-message">
                    <p>Invalid payment session or order ID.</p>
                </div>
                <div class="action-buttons">
                    <a href="cart.php" class="btn btn-secondary">Return to Cart</a>
                    <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
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
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="/images/WIS_logo_white.png">
        <title>Unauthorized | Four Eyes Collective</title>
        <link rel="stylesheet" href="/css/checkout_flow.css">
        <link rel="stylesheet" href="/css/app.css">
    </head>

    <body>
        <div class="checkout-status-container status-error">
            <div class="page-header">
                <h1>🚫 Unauthorized Access</h1>
                <p>You are not authorized to view this order.</p>
            </div>
            <div class="checkout-section" style="text-align: center;">
                <div class="error-message">
                    <p>You are not authorized to view this order.</p>
                </div>
                <div class="action-buttons">
                    <a href="order_history.php" class="btn btn-secondary">View My Orders</a>
                    <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
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

    $brand = $last4 = $bank = $funding = null;
    if ($methodType === 'card') {
        $brand = $paymentMethod->card->brand;
        $funding = $paymentMethod->card->funding;
        $last4 = $paymentMethod->card->last4;
    } elseif ($methodType === 'fpx') {
        $bank = $paymentMethod->fpx->bank;
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

        // Get order items for display
        $stm = $_db->prepare("
            SELECT oi.*, p.product_name, p.product_image, c.category_id, c.category_name
            FROM order_item oi
            JOIN product p ON oi.product_id = p.product_id
            LEFT JOIN category c ON p.category_id = c.category_id
            WHERE oi.order_id = ?
        ");
        $stm->execute([$order_id]);
        $order_items = $stm->fetchAll(PDO::FETCH_OBJ);

        // Prepare payment display info
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
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="shortcut icon" href="/images/WIS_logo_white.png">
            <title>Payment Successful | Four Eyes Collective</title>
            <link rel="stylesheet" href="/css/checkout_flow.css">
            <link rel="stylesheet" href="/css/app.css">
        </head>

        <body>
            <div class="checkout-status-container status-success">
                <div class="page-header">
                    <h1>Payment Successful</h1>
                    <p>Thank you for your order!</p>
                </div>

                <div class="checkout-section">
                    <div class="status-icon">✅</div>

                    <div class="success-message">
                        <p>Thank you <strong><?= encode($_user->name) ?></strong></p>
                        <p>Your order <strong><?= encode($order->order_id) ?></strong> has been confirmed.</p>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-details-grid">
                        <div class="detail-box">
                            <div class="detail-label">Order ID</div>
                            <div class="detail-value"><?= encode($order->order_id) ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?= date('d M Y, H:i') ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">Order Status</div>
                            <div class="detail-value" style="color:#27ae60;">Paid</div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value" style="color:#27ae60;">
                                RM <?= number_format($order->total_amount, 2) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="checkout-section">
                        <h3>Payment Details</h3>
                        <div class="order-details-grid">
                            <div class="detail-box">
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value"><?= encode($paymentLabel) ?></div>
                            </div>
                            <?php if ($paymentExtra): ?>
                                <div class="detail-box">
                                    <div class="detail-label"><?= $methodType === 'card' ? 'Card Number' : 'Details' ?></div>
                                    <div class="detail-value"><?= encode($paymentExtra) ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="detail-box">
                                <div class="detail-label">Transaction ID</div>
                                <div class="detail-value"><?= encode($session->payment_intent) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="checkout-section">
                        <h3>Order Items</h3>
                        <div class="order-items" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                            <?php foreach ($order_items as $item): ?>
                                <?php
                                $folder = $categoryFolders[$item->category_id] ?? 'others';
                                $imgArray = explode(',', $item->product_image);
                                $firstImage = trim($imgArray[0]);
                                $imgPath = "/images/product/$folder/$firstImage";
                                ?>
                                <div class="order-item">
                                    <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>" class="order-item-image">
                                    <div class="item-details">
                                        <div class="item-name"><?= encode($item->product_name) ?></div>
                                        <div style="color: #666; font-size: 0.9rem;">
                                            Qty: <?= $item->product_qty ?> × RM <?= number_format($item->price, 2) ?>
                                        </div>
                                    </div>
                                    <div style="font-weight: bold; color: #2c3e50; min-width: 100px; text-align: right;">
                                        RM <?= number_format($item->subtotal, 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span>RM <?= number_format($order->total_amount, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>Shipping</span>
                                <span>FREE</span>
                            </div>
                            <div class="total-row">
                                <span>Tax</span>
                                <span>Included</span>
                            </div>
                        </div>

                        <div class="total-row total-amount">
                            <span>Total</span>
                            <span>RM <?= number_format($order->total_amount, 2) ?></span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="order_history.php" class="btn btn-secondary">View Orders</a>
                        <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </body>

        </html>
    <?php

    } else {
        throw new Exception("Payment failed. Status: " . $paymentIntent->status);
    }
} catch (Exception $e) {
    // Payment failed
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="/images/WIS_logo_white.png">
        <title>Payment Failed | Four Eyes Collective</title>
        <link rel="stylesheet" href="/css/checkout_flow.css">
        <link rel="stylesheet" href="/css/app.css">
    </head>

    <body>
        <div class="checkout-status-container status-error">
            <div class="page-header">
                <h1>Payment Failed</h1>
                <p>We encountered an issue with your payment</p>
            </div>
            <div class="checkout-section">
                <div class="status-icon">❌</div>

                <div class="error-message">
                    <h3>Payment Unsuccessful</h3>
                    <p>Your payment for order <strong><?= encode($order_id) ?></strong> failed to process.</p>
                    <p><strong>Reason:</strong> <?= encode($e->getMessage()) ?></p>
                </div>

                <div class="action-buttons">
                    <a href="cart.php" class="btn btn-secondary">Return to Cart</a>
                    <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="order_history.php" class="btn">View Order History</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
}
?>