<?php
require '../_base.php';
require '../lib/db.php';
require_once '../stripe-php-19.0.0/init.php';

// Set your Stripe secret key
\Stripe\Stripe::setApiKey('sk_test_51SZZzU2LpkFiPUtITtnxkZtzongU6II64ZL8YSynXO951EcqTfIfRbWAl586Hh8LOXYexaqDtwwaO6rxwdOQvygm006Vp82pdb');

// Must login
auth();
$user_id = $_user->user_id;

// Initialize variables
$checkout_items = [];
$total_amount = 0;
$cart_items = [];

// Load cart items currently in checkout status
$stm = $_db->prepare("
    SELECT ci.*, p.product_name, p.product_price, p.product_stock, p.product_image, 
           c.category_id, c.category_name
    FROM cart_item ci 
    JOIN product p ON ci.product_id = p.product_id
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE ci.user_id = ? AND ci.item_status = 'checkout' AND ci.order_item_id IS NULL
");
$stm->execute([$user_id]);
$cart_items = $stm->fetchAll(PDO::FETCH_OBJ);

if (!$cart_items) {
    // No items in checkout - redirect to cart
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>No Checkout Items | Four Eyes Collective</title>
        <style>
            body { font-family: 'Roboto', sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
            h2 { color: #2c3e50; margin-bottom: 20px; }
            .btn { display: inline-block; padding: 12px 25px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px; margin: 10px; font-weight: bold; }
            .btn-primary { background: #27ae60; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>🛒 No Items for Checkout</h2>
            <p>You have no items selected for checkout. Please select items from your cart first.</p>
            <a href="cart.php" class="btn">Return to Cart</a>
            <a href="shoppage.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Calculate total amount
foreach ($cart_items as $item) {
    $total_amount += $item->product_price * $item->product_qty;
    $checkout_items[] = $item;
}

// Load ALL addresses
$stm = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC");
$stm->execute([$user_id]);
$addresses = $stm->fetchAll(PDO::FETCH_OBJ);

// Handle form submission
if (is_post()) {
    $address_id = post('address_id');
    $action = post('action');

    if ($action === 'cancel') {
        // Cancel checkout - restore items to cart
        $_db->beginTransaction();
        try {
            // Update cart items back to in_cart
            $_db->prepare("
                UPDATE cart_item 
                SET item_status = 'in_cart', checkout_at = NULL 
                WHERE user_id = ? AND item_status = 'checkout' AND order_item_id IS NULL
            ")->execute([$user_id]);
            
            $_db->commit();
            
            // Redirect to cart
            echo '<script>window.location.href = "cart.php";</script>';
            exit;
        } catch (Exception $ex) {
            $_db->rollBack();
            echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
            exit;
        }
    }
    
    if (!$address_id) {
        echo json_encode(['success' => false, 'message' => 'Please select an address.']);
        exit;
    }

    // Load selected address
    $stm = $_db->prepare("SELECT * FROM address WHERE address_id = ? AND user_id = ?");
    $stm->execute([$address_id, $user_id]);
    $address = $stm->fetch(PDO::FETCH_OBJ);

    if (!$address) {
        echo json_encode(['success' => false, 'message' => 'Invalid address']);
        exit;
    }

    $_db->beginTransaction();
    try {
        // Check stock
        foreach ($cart_items as $i) {
            if ($i->product_stock < $i->product_qty) {
                throw new Exception("Insufficient stock for {$i->product_name}");
            }
        }

        // Generate order ID
        $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(order_id, 3) AS UNSIGNED)) AS maxid FROM `order`");
        $max = $stm->fetch()->maxid ?? 0;
        $order_id = "OR" . str_pad($max + 1, 4, "0", STR_PAD_LEFT);

        // Insert order with initial status 'pending_payment'
        $stm = $_db->prepare("
            INSERT INTO `order` (order_id, user_id, address_id, order_date, total_amount, status, cancelled_reason)
            VALUES (?, ?, ?, NOW(), ?, 'pending_payment', NULL)
        ");
        $stm->execute([$order_id, $user_id, $address_id, $total_amount]);

        // Insert order items
        $order_item_ids = [];
        foreach ($cart_items as $i) {
            $stm2 = $_db->query("SELECT MAX(CAST(SUBSTRING(order_item_id, 3) AS UNSIGNED)) AS maxid FROM order_item");
            $maxItem = $stm2->fetch()->maxid ?? 0;
            $order_item_id = "OI" . str_pad($maxItem + 1, 4, "0", STR_PAD_LEFT);
            $order_item_ids[] = $order_item_id;

            $stm3 = $_db->prepare("
                INSERT INTO order_item (order_item_id, order_id, product_id, product_qty, price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stm3->execute([
                $order_item_id, $order_id, $i->product_id, $i->product_qty,
                $i->product_price, $i->product_price * $i->product_qty
            ]);

            // Update cart item with order_item_id and change status
            $_db->prepare("
                UPDATE cart_item 
                SET order_item_id = ?, item_status = 'ordered'
                WHERE cart_item_id = ? AND user_id = ?
            ")->execute([$order_item_id, $i->cart_item_id, $user_id]);
            
            // Update product stock
            $_db->prepare("
                UPDATE product 
                SET product_stock = product_stock - ?
                WHERE product_id = ?
            ")->execute([$i->product_qty, $i->product_id]);
        }

        // Stripe Line Items
        $lineItems = [];
        foreach ($cart_items as $i) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => $i->product_name,
                        'metadata' => ['product_id' => $i->product_id]
                    ],
                    'unit_amount' => intval($i->product_price * 100),
                ],
                'quantity' => $i->product_qty,
            ];
        }

        $baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";

        // Create Stripe session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => "$baseURL/page/order_success.php?session_id={CHECKOUT_SESSION_ID}&order_id=$order_id",
            'cancel_url' => "$baseURL/page/cancel_payment.php?order_id=$order_id",
            'customer_email' => $_user->email,
            'metadata' => ['order_id' => $order_id, 'user_id' => $user_id]
        ]);

        // Insert payment row (no payment_method_id)
        $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(payment_id, 4) AS UNSIGNED)) AS maxid FROM payment");
        $maxPay = $stm->fetch()->maxid ?? 0;
        $payment_id = "PAY" . str_pad($maxPay + 1, 4, "0", STR_PAD_LEFT);

        $_db->prepare("
            INSERT INTO payment (payment_id, order_id, amount, status, stripe_session_id, transaction_date)
            VALUES (?, ?, ?, 'pending', ?, NOW())
        ")->execute([$payment_id, $order_id, $total_amount, $session->id]);

        // Insert order history
        $history_id = "HIS" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT);
        $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, changed_at, changed_by, message)
            VALUES (?, ?, 'pending_payment', NOW(), ?, 'Order created, awaiting payment')
        ")->execute([$history_id, $order_id, $user_id]);

        $_db->commit();

        echo json_encode(['success' => true, 'redirect' => $session->url]);
        exit;

    } catch (Exception $ex) {
        $_db->rollBack();
        echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Four Eyes Collective</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Roboto', sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .page-header h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 600;
            font-family: 'Playfair Display', serif;
        }
        
        .page-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
        }
        
        @media (max-width: 992px) {
            .checkout-content { grid-template-columns: 1fr; }
        }
        
        .checkout-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            font-family: 'Playfair Display', serif;
        }
        
        .address-option {
            border: 2px solid #eaeaea;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
        }
        
        .address-option:hover {
            border-color: #2c3e50;
            background: #f8f9fa;
        }
        
        .address-option.selected {
            border-color: #2c3e50;
            background: #f8f9fa;
        }
        
        .address-option input[type="radio"] {
            display: none;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
            background: white;
            border: 1px solid #eee;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        
        .total-amount {
            font-size: 1.5rem;
            font-weight: bold;
            border-top: 2px solid #e0e0e0;
            padding-top: 20px;
            color: #2c3e50;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            text-align: center;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #d5dbdb;
        }
        
        .error-message {
            color: #e74c3c;
            margin-top: 10px;
            padding: 10px;
            background: #fadbd8;
            border-radius: 5px;
            display: none;
        }
        
        .success-message {
            color: #27ae60;
            margin-top: 10px;
            padding: 10px;
            background: #d5f4e6;
            border-radius: 5px;
            display: none;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .security-note {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f0f7ff;
            border-radius: 8px;
            margin: 20px 0;
            color: #2c3e50;
        }
        
        .security-icon {
            font-size: 1.5rem;
        }
        
        .add-address-link {
            display: inline-block;
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            padding: 10px 15px;
            border: 2px dashed #2c3e50;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .add-address-link:hover {
            background: #2c3e50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Secure Checkout</h1>
            <p>Complete your purchase with confidence</p>
        </div>
        
        <form id="checkoutForm" method="post">
            <!-- Main Content -->
            <div class="checkout-content">
                <!-- Left Column: Shipping & Payment -->
                <div>
                    <!-- Shipping Address Section -->
                    <div class="checkout-section">
                        <h2 class="section-title">Shipping Address</h2>
                        <?php if (empty($addresses)): ?>
                            <div style="text-align: center; padding: 30px;">
                                <p style="color: #7f8c8d; margin-bottom: 20px;">No addresses found. Please add a shipping address.</p>
                                <a href="profile_address_add.php?return=checkout.php" class="add-address-link">
                                    + Add New Address
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="address-options">
                                <?php foreach ($addresses as $addr): ?>
                                    <label class="address-option <?= $addr->default_flag ? 'selected' : '' ?>">
                                        <input type="radio" name="address_id" value="<?= $addr->address_id ?>" required 
                                               <?= $addr->default_flag ? 'checked' : '' ?>>
                                        <div>
                                            <div style="font-weight: bold; color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center;">
                                                <?= encode($addr->recipient_name) ?>
                                                <?php if ($addr->default_flag): ?>
                                                    <span style="color: #27ae60; font-size: 0.8rem; margin-left: 10px; background: #d5f4e6; padding: 2px 8px; border-radius: 10px;">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="color: #666; font-size: 0.9rem; line-height: 1.5;">
                                                <?= encode($addr->address_line1) ?><br>
                                                <?php if (!empty($addr->address_line2)): ?>
                                                    <?= encode($addr->address_line2) ?><br>
                                                <?php endif; ?>
                                                <?= encode($addr->city . ', ' . $addr->state . ' ' . $addr->postcode) ?><br>
                                                <?= encode($addr->country) ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div id="addressError" class="error-message"></div>
                            <div style="margin-top: 20px;">
                                <a href="profile_address_add.php?return=checkout.php" class="add-address-link">
                                    + Add New Address
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Payment Information -->
                    <div class="checkout-section">
                        <h2 class="section-title">Payment Information</h2>
                        <div>
                            <p style="color: #7f8c8d; margin-bottom: 15px;">
                                You will be redirected to Stripe's secure payment page to complete your purchase.
                            </p>
                            <div class="security-note">
                                <div class="security-icon">🔒</div>
                                <div>
                                    <strong>Secure Payment</strong><br>
                                    Your payment information is encrypted and secure. We never store your card details.
                                </div>
                            </div>
                            <div id="paymentError" class="error-message"></div>
                            <div id="paymentSuccess" class="success-message"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Order Summary -->
                <div class="order-summary">
                    <div class="checkout-section">
                        <h2 class="section-title">Order Summary</h2>
                        
                        <div class="order-items" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                            <?php foreach ($checkout_items as $item): ?>
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
                                    <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>" class="order-item-image">
                                    <div class="item-details">
                                        <div class="item-name"><?= encode($item->product_name) ?></div>
                                        <div style="color: #666; font-size: 0.9rem;">Qty: <?= $item->product_qty ?> × RM <?= number_format($item->product_price, 2) ?></div>
                                    </div>
                                    <div style="font-weight: bold; color: #2c3e50; min-width: 100px; text-align: right;">
                                        RM <?= number_format($item->product_price * $item->product_qty, 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span>RM <?= number_format($total_amount, 2) ?></span>
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
                            <span>RM <?= number_format($total_amount, 2) ?></span>
                        </div>
                        
                        <!-- Payment Button -->
                        <button type="button" class="btn btn-success" id="submitBtn" onclick="processPayment()">
                            <span id="btnText">Pay RM <?= number_format($total_amount, 2) ?></span>
                            <span id="btnLoading" style="display: none;" class="loading"></span>
                        </button>
                        
                        <!-- Cancel Checkout Button -->
                        <button type="button" class="btn btn-secondary" onclick="cancelCheckout()">
                            Cancel Checkout
                        </button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="action" id="actionInput" value="">
        </form>
    </div>

    <script>
        // Address selection styling
        document.querySelectorAll('.address-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.address-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
                document.getElementById('addressError').style.display = 'none';
            });
        });

        // Process payment
        async function processPayment() {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const paymentError = document.getElementById('paymentError');
            const paymentSuccess = document.getElementById('paymentSuccess');
            
            // Reset messages
            paymentError.style.display = 'none';
            paymentSuccess.style.display = 'none';
            
            // Validate address
            const addressSelected = document.querySelector('input[name="address_id"]:checked');
            if (!addressSelected) {
                document.getElementById('addressError').textContent = 'Please select a shipping address';
                document.getElementById('addressError').style.display = 'block';
                return;
            }
            
            // Show loading
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            
            try {
                const formData = new FormData();
                formData.append('address_id', addressSelected.value);
                
                const response = await fetch('checkout.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Success - redirect to Stripe
                    paymentSuccess.textContent = 'Redirecting to secure payment...';
                    paymentSuccess.style.display = 'block';
                    
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                } else {
                    // Payment failed - show error
                    paymentError.textContent = result.message || 'Payment failed. Please try again.';
                    paymentError.style.display = 'block';
                    
                    // Enable button for retry
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                }
                
            } catch (error) {
                // Network or server error
                paymentError.textContent = 'Network error. Please check your connection and try again.';
                paymentError.style.display = 'block';
                
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                
                console.error('Payment error:', error);
            }
        }
        
        // Cancel checkout
        function cancelCheckout() {
            if (confirm('Are you sure you want to cancel checkout? All selected items will be returned to your cart.')) {
                const form = document.getElementById('checkoutForm');
                const actionInput = document.getElementById('actionInput');
                actionInput.value = 'cancel';
                form.submit();
            }
        }
        
        // Auto-scroll to error if any
        window.onload = function() {
            const error = document.querySelector('.error-message[style*="display: block"]');
            if (error) {
                error.scrollIntoView({ behavior: 'smooth' });
            }
        };
    </script>
</body>
</html>