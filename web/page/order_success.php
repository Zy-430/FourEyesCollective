<?php
// order_success.php
require '../_base.php';
require '../lib/db.php';
require_once '../stripe-php-19.0.0/init.php';

\Stripe\Stripe::setApiKey('sk_test_51SZZzU2LpkFiPUtITtnxkZtzongU6II64ZL8YSynXO951EcqTfIfRbWAl586Hh8LOXYexaqDtwwaO6rxwdOQvygm006Vp82pdb');

auth();

$session_id = get('session_id');
$order_id = get('order_id');
$user_id = $_user->user_id;

// Initialize variables
$order = null;
$payment = null;
$order_items = [];
$error_message = '';
$success = false;

try {
    // Verify Stripe session if available
    if ($session_id) {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        
        // Verify this session belongs to current user
        if ($session->customer_email !== $_user->email) {
            temp('error', 'Invalid payment session');
            redirect('profile_orders.php');
        }
        
        // Update order status to 'paid' if payment was successful
        if ($session->payment_status === 'paid') {
            $_db->beginTransaction();
            
            try {
                // Get payment intent details
                $payment_intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
                $payment_method = $payment_intent->payment_method;
                
                // Get payment method details
                $pm = \Stripe\PaymentMethod::retrieve($payment_method);
                $last4 = $pm->card->last4;
                $brand = $pm->card->brand;
                $exp_month = $pm->card->exp_month;
                $exp_year = $pm->card->exp_year;
                
                // Update order status to paid
                $stm = $_db->prepare("UPDATE `order` SET status = 'paid' WHERE order_id = ? AND user_id = ?");
                $stm->execute([$order_id, $user_id]);
                
                // Check if payment record already exists
                $stm = $_db->prepare("SELECT payment_id FROM payment WHERE order_id = ?");
                $stm->execute([$order_id]);
                $existing_payment = $stm->fetch();
                
                if (!$existing_payment) {
                    // Generate payment ID
                    $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(payment_id, 4) AS UNSIGNED)) as max_id FROM payment");
                    $max_id = $stm->fetch()->max_id;
                    $payment_id = 'PAY' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);
                    
                    // Generate payment method ID
                    $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(payment_method_id, 3) AS UNSIGNED)) as max_id FROM payment_method");
                    $max_pm_id = $stm->fetch()->max_id;
                    $payment_method_id = 'PM' . str_pad($max_pm_id + 1, 4, '0', STR_PAD_LEFT);
                    
                    // Create payment method record
                    $stm = $_db->prepare("
                        INSERT INTO payment_method (payment_method_id, user_id, provider, token, brand, last4, expiry_month, expiry_year, is_default, created_at, active)
                        VALUES (?, ?, 'stripe', ?, ?, ?, ?, ?, 1, NOW(), 1)
                    ");
                    $stm->execute([
                        $payment_method_id,
                        $user_id,
                        $payment_method,
                        $brand,
                        $last4,
                        $exp_month,
                        $exp_year
                    ]);
                    
                    // Get order amount
                    $stm2 = $_db->prepare("SELECT total_amount FROM `order` WHERE order_id = ?");
                    $stm2->execute([$order_id]);
                    $order_amount = $stm2->fetch()->total_amount;
                    
                    // Create payment record
                    $stm = $_db->prepare("
                        INSERT INTO payment (payment_id, order_id, amount, transaction_date, payment_method_id, status, failed_reason)
                        VALUES (?, ?, ?, NOW(), ?, 'succeeded', NULL)
                    ");
                    $stm->execute([$payment_id, $order_id, $order_amount, $payment_method_id]);
                }
                
                $_db->commit();
                $success = true;
                
            } catch (Exception $e) {
                $_db->rollBack();
                error_log("Payment processing error: " . $e->getMessage());
                $error_message = 'Payment recorded, but there was an issue updating the order. Please contact support.';
            }
        }
    }
} catch (Exception $e) {
    error_log("Stripe verification error: " . $e->getMessage());
    // Continue to show order details even if Stripe verification fails
}

// If no session_id but order_id exists (direct access or other payment method)
if (!$session_id && $order_id) {
    // Check if order exists and belongs to user
    $stm = $_db->prepare("SELECT * FROM `order` WHERE order_id = ? AND user_id = ?");
    $stm->execute([$order_id, $user_id]);
    $order = $stm->fetch();
    
    if (!$order) {
        temp('error', 'Order not found');
        redirect('profile_orders.php');
    }
    
    // If order is pending, mark it as paid (for non-Stripe payments)
    if ($order->status === 'pending') {
        $stm = $_db->prepare("UPDATE `order` SET status = 'paid' WHERE order_id = ? AND user_id = ?");
        $stm->execute([$order_id, $user_id]);
        $order->status = 'paid';
    }
    
    $success = true;
}

// Get order details
if (!$order) {
    $stm = $_db->prepare("
        SELECT o.*, a.* 
        FROM `order` o
        LEFT JOIN address a ON o.address_id = a.address_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stm->execute([$order_id, $user_id]);
    $order = $stm->fetch();
}

if (!$order) {
    temp('error', 'Order not found');
    redirect('profile_orders.php');
}

// Get payment details
$stm = $_db->prepare("
    SELECT p.*, pm.brand, pm.last4 
    FROM payment p
    LEFT JOIN payment_method pm ON p.payment_method_id = pm.payment_method_id
    WHERE p.order_id = ?
    ORDER BY p.transaction_date DESC
    LIMIT 1
");
$stm->execute([$order_id]);
$payment = $stm->fetch();

// Get order items
$stm = $_db->prepare("
    SELECT oi.*, p.product_name, p.product_image, p.category_id, c.category_name
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE oi.order_id = ?
");
$stm->execute([$order_id]);
$order_items = $stm->fetchAll();

// Update cart items to mark as purchased
if ($success) {
    $stm = $_db->prepare("
        UPDATE cart_item 
        SET item_status = 'purchased', checkout_at = NOW()
        WHERE user_id = ? AND item_status = 'checkout' AND order_item_id IS NOT NULL
    ");
    $stm->execute([$user_id]);
}

$_title = 'Order Confirmation | Four Eyes Collective';
include '../_head.php';
?>

<div class="order-success-container" style="max-width: 1000px; margin: 40px auto; padding: 20px;">
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #f5c6cb;">
            <?= $error_message ?>
        </div>
    <?php endif; ?>
    
    <!-- Success Header -->
    <div class="success-header" style="text-align: center; margin-bottom: 50px; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
        <div class="success-icon" style="width: 100px; height: 100px; background: #27ae60; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 25px;">
            ✓
        </div>
        
        <h1 style="color: #27ae60; margin-bottom: 15px; font-size: 2.5em; font-weight: 600;">Order Confirmed!</h1>
        <p style="font-size: 1.2em; color: #666; margin-bottom: 15px;">
            Thank you for your purchase. Your order has been successfully processed.
        </p>
        <p style="color: #7f8c8d; font-size: 1.1em;">
            Order ID: <strong style="color: #2c3e50; font-family: monospace; font-size: 1.2em;"><?= $order_id ?></strong>
        </p>
    </div>

    <!-- Order Details -->
    <div class="order-details" style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 30px; color: #2c3e50; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
            Order Information
        </h2>
        
        <div class="details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Order Summary -->
            <div>
                <h3 style="margin-bottom: 20px; color: #7f8c8d; font-size: 1.1em; text-transform: uppercase; letter-spacing: 1px;">
                    Order Summary
                </h3>
                <div class="info-list" style="line-height: 1.8;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #666;">Order ID:</span>
                        <span style="font-weight: 600; color: #2c3e50;"><?= $order_id ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #666;">Order Date:</span>
                        <span style="font-weight: 600;"><?= date('F d, Y H:i', strtotime($order->order_date)) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #666;">Order Status:</span>
                        <span style="font-weight: 600; color: #27ae60;"><?= ucfirst($order->status) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #666;">Total Amount:</span>
                        <span style="font-weight: 600; color: #2c3e50; font-size: 1.2em;">RM <?= number_format($order->total_amount, 2) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div>
                <h3 style="margin-bottom: 20px; color: #7f8c8d; font-size: 1.1em; text-transform: uppercase; letter-spacing: 1px;">
                    Shipping Address
                </h3>
                <div class="address-box" style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="line-height: 1.8;">
                        <div style="font-weight: 600; margin-bottom: 8px; color: #2c3e50;"><?= encode($_user->name) ?></div>
                        <div><?= encode($order->address_line1) ?></div>
                        <?php if (!empty($order->address_line2)): ?>
                            <div><?= encode($order->address_line2) ?></div>
                        <?php endif; ?>
                        <div><?= encode($order->city . ', ' . $order->state . ' ' . $order->postcode) ?></div>
                        <div><?= encode($order->country) ?></div>
                        <div style="margin-top: 10px; color: #666;">
                            Phone: +60 <?= encode($_user->phone) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Information -->
        <div class="payment-info" style="margin-bottom: 30px; padding: 25px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin-bottom: 20px; color: #7f8c8d; font-size: 1.1em; text-transform: uppercase; letter-spacing: 1px;">
                Payment Information
            </h3>
            
            <?php if ($payment): ?>
                <div class="info-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Payment Status</div>
                        <div style="font-weight: 600; color: #27ae60; font-size: 1.1em;"><?= ucfirst($payment->status) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Payment Date</div>
                        <div style="font-weight: 600;"><?= date('F d, Y H:i', strtotime($payment->transaction_date)) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Payment Method</div>
                        <div style="font-weight: 600;">
                            <?php if ($payment->brand): ?>
                                <?= $payment->brand ?> **** **** **** <?= $payment->last4 ?>
                            <?php else: ?>
                                Stripe Payment
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Transaction ID</div>
                        <div style="font-weight: 600; font-family: monospace;"><?= $payment->payment_id ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 20px;">
                    <p style="color: #666;">Payment details will be updated shortly.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Items -->
        <div class="order-items-section">
            <h3 style="margin-bottom: 25px; color: #2c3e50; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                Order Items (<?= count($order_items) ?>)
            </h3>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #e0e0e0; color: #7f8c8d; font-weight: 600;">Product</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #e0e0e0; color: #7f8c8d; font-weight: 600;">Price</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #e0e0e0; color: #7f8c8d; font-weight: 600;">Quantity</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #e0e0e0; color: #7f8c8d; font-weight: 600;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
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
                            <tr style="border-bottom: 1px solid #eee; transition: background 0.2s ease;">
                                <td style="padding: 20px 15px;">
                                    <div style="display: flex; align-items: center;">
                                        <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>"
                                             style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px; margin-right: 20px; border: 1px solid #eee;">
                                        <div>
                                            <div style="font-weight: 600; color: #2c3e50; margin-bottom: 5px; font-size: 1.1em;">
                                                <?= encode($item->product_name) ?>
                                            </div>
                                            <div style="color: #666; font-size: 0.9em;">
                                                <?= encode($item->category_name) ?>
                                            </div>
                                            <div style="color: #95a5a6; font-size: 0.85em; margin-top: 5px;">
                                                Product ID: <?= $item->product_id ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 20px 15px; font-weight: 600; color: #2c3e50;">
                                    RM <?= number_format($item->price, 2) ?>
                                </td>
                                <td style="padding: 20px 15px;">
                                    <div style="display: inline-block; padding: 6px 12px; background: #f8f9fa; border-radius: 20px; font-weight: 600;">
                                        <?= $item->product_qty ?>
                                    </div>
                                </td>
                                <td style="padding: 20px 15px; font-weight: 700; color: #2c3e50; font-size: 1.1em;">
                                    RM <?= number_format($item->subtotal, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa;">
                            <td colspan="3" style="padding: 20px 15px; text-align: right; font-weight: 600; font-size: 1.1em;">
                                Total Amount:
                            </td>
                            <td style="padding: 20px 15px; font-weight: 700; font-size: 1.3em; color: #2c3e50;">
                                RM <?= number_format($order->total_amount, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="next-steps" style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 25px; color: #2c3e50; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
            What Happens Next?
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;">
            <div class="step" style="text-align: center; padding: 25px; border: 1px solid #eaeaea; border-radius: 10px; transition: all 0.3s ease;">
                <div class="step-icon" style="width: 60px; height: 60px; background: #e8f4fc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <span style="font-size: 28px; color: #3498db;">📧</span>
                </div>
                <h3 style="margin-bottom: 12px; color: #2c3e50; font-size: 1.2em;">Order Confirmation</h3>
                <p style="color: #666; line-height: 1.6; margin-bottom: 0;">
                    We've sent an email confirmation to <strong><?= encode($_user->email) ?></strong> with your order details.
                </p>
            </div>
            
            <div class="step" style="text-align: center; padding: 25px; border: 1px solid #eaeaea; border-radius: 10px; transition: all 0.3s ease;">
                <div class="step-icon" style="width: 60px; height: 60px; background: #e8f7ef; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <span style="font-size: 28px; color: #27ae60;">📦</span>
                </div>
                <h3 style="margin-bottom: 12px; color: #2c3e50; font-size: 1.2em;">Order Processing</h3>
                <p style="color: #666; line-height: 1.6; margin-bottom: 0;">
                    Your order is being processed and will be prepared for shipping within 1-2 business days.
                </p>
            </div>
            
            <div class="step" style="text-align: center; padding: 25px; border: 1px solid #eaeaea; border-radius: 10px; transition: all 0.3s ease;">
                <div class="step-icon" style="width: 60px; height: 60px; background: #fff4e6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <span style="font-size: 28px; color: #e67e22;">🚚</span>
                </div>
                <h3 style="margin-bottom: 12px; color: #2c3e50; font-size: 1.2em;">Shipping & Delivery</h3>
                <p style="color: #666; line-height: 1.6; margin-bottom: 0;">
                    You'll receive a shipping confirmation with tracking details once your order is dispatched.
                </p>
            </div>
        </div>
        
        <!-- Estimated Timeline -->
        <div style="margin-top: 40px; padding: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white;">
            <h3 style="margin-bottom: 20px; color: white; font-size: 1.3em;">Estimated Timeline</h3>
            <div style="display: flex; justify-content: space-between; position: relative;">
                <div style="position: absolute; top: 20px; left: 0; width: 100%; height: 2px; background: rgba(255,255,255,0.3); z-index: 1;"></div>
                
                <div class="timeline-step" style="text-align: center; position: relative; z-index: 2;">
                    <div style="width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                        <span style="color: #667eea; font-weight: bold;">1</span>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 5px;">Order Placed</div>
                    <div style="font-size: 0.9em; opacity: 0.9;">Today</div>
                </div>
                
                <div class="timeline-step" style="text-align: center; position: relative; z-index: 2;">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; border: 2px solid white;">
                        <span style="color: white; font-weight: bold;">2</span>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 5px;">Processing</div>
                    <div style="font-size: 0.9em; opacity: 0.9;">1-2 days</div>
                </div>
                
                <div class="timeline-step" style="text-align: center; position: relative; z-index: 2;">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; border: 2px solid white;">
                        <span style="color: white; font-weight: bold;">3</span>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 5px;">Shipped</div>
                    <div style="font-size: 0.9em; opacity: 0.9;">2-3 days</div>
                </div>
                
                <div class="timeline-step" style="text-align: center; position: relative; z-index: 2;">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; border: 2px solid white;">
                        <span style="color: white; font-weight: bold;">4</span>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 5px;">Delivered</div>
                    <div style="font-size: 0.9em; opacity: 0.9;">3-5 days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons" style="display: flex; gap: 15px; justify-content: center; margin-top: 40px;">
        <a href="profile_orders.php" 
           style="padding: 14px 35px; background: #2c3e50; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
            <span>📋</span> View My Orders
        </a>
        <a href="shoppage.php" 
           style="padding: 14px 35px; background: #ecf0f1; color: #2c3e50; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; border: 1px solid #ddd;">
            <span>🛒</span> Continue Shopping
        </a>
        <a href="javascript:void(0);" onclick="window.print()"
           style="padding: 14px 35px; background: #3498db; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
            <span>🖨️</span> Print Invoice
        </a>
    </div>
    
    <!-- Customer Support -->
    <div class="support-info" style="text-align: center; margin-top: 50px; padding: 30px; background: #f8f9fa; border-radius: 10px; border: 1px solid #eaeaea;">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">Need Help?</h3>
        <p style="color: #666; margin-bottom: 25px; max-width: 600px; margin-left: auto; margin-right: auto;">
            If you have any questions about your order or need assistance, our customer support team is here to help.
        </p>
        <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
            <div style="text-align: center;">
                <div style="font-size: 24px; color: #3498db; margin-bottom: 10px;">📞</div>
                <div style="font-weight: 600; color: #2c3e50;">Call Us</div>
                <div style="color: #666;">+60 3-1234 5678</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 24px; color: #3498db; margin-bottom: 10px;">✉️</div>
                <div style="font-weight: 600; color: #2c3e50;">Email Us</div>
                <div style="color: #666;">support@foureyescollective.com</div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 24px; color: #3498db; margin-bottom: 10px;">💬</div>
                <div style="font-weight: 600; color: #2c3e50;">Live Chat</div>
                <div style="color: #666;">Available 9am-6pm</div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Add to existing app.css or include here */
    .order-success-container {
        animation: fadeIn 0.5s ease-out;
    }
    
    .success-header {
        animation: slideUp 0.6s ease-out;
    }
    
    .order-details {
        animation: slideUp 0.7s ease-out;
    }
    
    .next-steps {
        animation: slideUp 0.8s ease-out;
    }
    
    .step:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-color: #3498db;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { 
            opacity: 0; 
            transform: translateY(20px); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0); 
        }
    }
    
    /* Print Styles */
    @media print {
        .action-buttons,
        .support-info,
        .next-steps .step:hover {
            display: none !important;
        }
        
        body {
            background: white !important;
        }
        
        .order-success-container {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        
        .success-header,
        .order-details,
        .next-steps {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            margin-bottom: 20px !important;
            page-break-inside: avoid;
        }
        
        .next-steps {
            page-break-before: always;
        }
    }
</style>

<script>
    // Add interactivity
    document.addEventListener('DOMContentLoaded', function() {
        // Animate timeline steps
        const timelineSteps = document.querySelectorAll('.timeline-step');
        timelineSteps.forEach((step, index) => {
            setTimeout(() => {
                step.style.opacity = '1';
                step.style.transform = 'translateY(0)';
            }, index * 200);
        });
        
        // Initialize timeline steps
        timelineSteps.forEach(step => {
            step.style.opacity = '0';
            step.style.transform = 'translateY(10px)';
            step.style.transition = 'all 0.5s ease';
        });
        
        // Print functionality
        window.printOrder = function() {
            const printContent = document.querySelector('.order-success-container').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        };
        
        // Add order to recent orders in localStorage
        if (typeof(Storage) !== "undefined") {
            let recentOrders = JSON.parse(localStorage.getItem('recent_orders') || '[]');
            
            // Check if order already exists
            const orderExists = recentOrders.some(order => order.id === '<?= $order_id ?>');
            
            if (!orderExists) {
                recentOrders.unshift({
                    id: '<?= $order_id ?>',
                    date: '<?= date('Y-m-d H:i:s') ?>',
                    amount: <?= $order->total_amount ?>
                });
                
                // Keep only last 5 orders
                if (recentOrders.length > 5) {
                    recentOrders = recentOrders.slice(0, 5);
                }
                
                localStorage.setItem('recent_orders', JSON.stringify(recentOrders));
            }
        }
        
        // Show notification
        setTimeout(() => {
            if (Notification.permission === "granted") {
                new Notification("Order Confirmed!", {
                    body: "Your order <?= $order_id ?> has been confirmed. Thank you!",
                    icon: "/images/logo.png"
                });
            }
        }, 1000);
    });
</script>

<?php include '../_foot.php'; ?>