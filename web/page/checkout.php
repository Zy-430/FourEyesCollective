<?php
require '../_base.php';
require '../lib/db.php';
require_once '../stripe-php-19.0.0/init.php';

\Stripe\Stripe::setApiKey('sk_test_51SZZzU2LpkFiPUtITtnxkZtzongU6II64ZL8YSynXO951EcqTfIfRbWAl586Hh8LOXYexaqDtwwaO6rxwdOQvygm006Vp82pdb');

auth();

$user_id = $_user->user_id;

// Get items marked as checkout AND don't have order_item_id (not yet purchased)
$stm = $_db->prepare("
    SELECT ci.*, p.*, c.category_name 
    FROM cart_item ci
    JOIN product p ON ci.product_id = p.product_id
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE ci.user_id = ? AND ci.item_status = 'checkout' AND ci.order_item_id IS NULL
    ORDER BY ci.created_at DESC
");
$stm->execute([$user_id]);
$checkout_items = $stm->fetchAll();

if (empty($checkout_items)) {
    temp('error', 'No items selected for checkout. Please select items from your cart first.');
    redirect('cart.php');
}

// Get user addresses
$stm = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC, created_at ASC");
$stm->execute([$user_id]);
$addresses = $stm->fetchAll();

// Calculate total
$total_amount = 0;
foreach ($checkout_items as $item) {
    $total_amount += $item->product_price * $item->product_qty;
}

// Process checkout submission
if (is_post()) {
    $address_id = post('address_id');
    $save_card = post('save_card', 0);
    
    // Validate address
    if (empty($address_id)) {
        echo json_encode(['success' => false, 'message' => 'Please select delivery address']);
        exit;
    }
    
    // Verify address belongs to user
    $stm = $_db->prepare("SELECT * FROM address WHERE address_id = ? AND user_id = ?");
    $stm->execute([$address_id, $user_id]);
    $address = $stm->fetch();
    
    if (!$address) {
        echo json_encode(['success' => false, 'message' => 'Invalid address']);
        exit;
    }
    
    $_db->beginTransaction();
    
    try {
        // Check stock again
        foreach ($checkout_items as $item) {
            if ($item->product_stock < $item->product_qty) {
                throw new Exception("Insufficient stock for {$item->product_name}. Available: {$item->product_stock}");
            }
        }
        
        // Generate Order ID
        $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(order_id, 3) AS UNSIGNED)) as max_id FROM `order`");
        $max_id = $stm->fetch()->max_id;
        $order_id = 'OR' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);
        
        // Create Order (with address_id) - status is pending until payment
        $stm = $_db->prepare("
            INSERT INTO `order` (order_id, user_id, address_id, order_date, total_amount, status) 
            VALUES (?, ?, ?, NOW(), ?, 'pending')
        ");
        $stm->execute([$order_id, $user_id, $address_id, $total_amount]);
        
        // Generate Order Item IDs
        $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(order_item_id, 3) AS UNSIGNED)) as max_id FROM order_item");
        $max_item_id = $stm->fetch()->max_id;
        $next_item_id = $max_item_id + 1;
        
        // Create Order Items and update stock
        foreach ($checkout_items as $item) {
            $order_item_id = 'OI' . str_pad($next_item_id, 4, '0', STR_PAD_LEFT);
            $next_item_id++;
            
            // Insert order item
            $stm = $_db->prepare("
                INSERT INTO order_item (order_item_id, order_id, product_id, product_qty, price, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stm->execute([
                $order_item_id,
                $order_id,
                $item->product_id,
                $item->product_qty,
                $item->product_price,
                $item->product_price * $item->product_qty
            ]);
            
            // Update product stock
            $stm = $_db->prepare("
                UPDATE product 
                SET product_stock = product_stock - ? 
                WHERE product_id = ?
            ");
            $stm->execute([$item->product_qty, $item->product_id]);
        }
        
        // Prepare line items for Stripe
        $lineItems = [];
        foreach ($checkout_items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => $item->product_name,
                        'metadata' => [
                            'product_id' => $item->product_id,
                            'category' => $item->category_name
                        ]
                    ],
                    'unit_amount' => intval($item->product_price * 100), // Convert to cents
                ],
                'quantity' => $item->product_qty,
            ];
        }
        
        // Add shipping address to metadata
        $shipping_info = [
            'name' => $_user->name,
            'address' => [
                'line1' => $address->address_line1,
                'line2' => $address->address_line2 ?? '',
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postcode,
                'country' => $address->country,
            ]
        ];
        
        // Get base URL
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        
        // Create Stripe checkout session
        $checkoutSession = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $baseUrl . '/page/order_success.php?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order_id,
            'cancel_url' => $baseUrl . '/page/checkout.php',
            'customer_email' => $_user->email,
            'metadata' => [
                'order_id' => $order_id,
                'user_id' => $user_id,
                'address_id' => $address_id
            ],
            'shipping_address_collection' => [
                'allowed_countries' => ['MY'],
            ],
            'phone_number_collection' => [
                'enabled' => true,
            ],
            'allow_promotion_codes' => false,
            'billing_address_collection' => 'required',
        ]);
        
        $_db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Redirecting to payment...',
            'sessionId' => $checkoutSession->id,
            'redirect' => $checkoutSession->url
        ]);
        
    } catch (Exception $e) {
        $_db->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

$_title = 'Checkout | Four Eyes Collective';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?></title>
    <link rel="stylesheet" href="/css/checkout.css">
</head>
<body>
    <div class="checkout-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Secure Checkout</h1>
            <p>Complete your purchase with confidence</p>
        </div>
        
        <!-- Main Content -->
        <div class="checkout-content">
            <!-- Left Column: Shipping & Payment -->
            <div>
                <!-- Shipping Address Section -->
                <div class="checkout-section">
                    <h2 class="section-title">Shipping Address</h2>
                    <?php if (empty($addresses)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🏠</div>
                            <p>No addresses found. Please add a shipping address.</p>
                            <a href="profile_address_add.php?return=checkout.php" class="btn btn-primary" style="width: auto; margin-top: 20px;">
                                Add New Address
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="address-options">
                            <?php foreach ($addresses as $addr): ?>
                                <label class="address-option <?= $addr->default_flag ? 'selected' : '' ?>">
                                    <input type="radio" name="address_id" value="<?= $addr->address_id ?>" required 
                                           <?= $addr->default_flag ? 'checked' : '' ?>>
                                    <div class="checkmark"></div>
                                    <div>
                                        <div class="address-label">
                                            <?php if ($addr->default_flag): ?>
                                                <span style="color: #27ae60; font-size: 0.8rem; margin-right: 10px;">✓ Default</span>
                                            <?php endif; ?>
                                            <?= encode($addr->address_line1) ?>
                                        </div>
                                        <div class="address-details">
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
                        <div class="error-message" id="addressError"></div>
                        <div style="margin-top: 20px;">
                            <a href="profile_address_add.php?return=checkout.php" class="back-link" target="_self">
                                + Add New Address
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Payment Information -->
                <div class="checkout-section">
                    <h2 class="section-title">Payment Information</h2>
                    <div class="payment-info">
                        <p style="color: #7f8c8d; margin-bottom: 15px;">
                            You will be redirected to Stripe's secure payment page to complete your purchase.
                        </p>
                        <div class="security-note">
                            <div class="security-icon">🔒</div>
                            <div>
                                <strong>Secure Payment</strong><br>
                                Your payment information is encrypted and secure. We never store your full card details.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Order Summary -->
            <div class="order-summary">
                <div class="checkout-section">
                    <h2 class="section-title">Order Summary</h2>
                    
                    <div class="order-items">
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
                                    <div class="item-meta">Qty: <?= $item->product_qty ?></div>
                                </div>
                                <div class="item-total">RM <?= number_format($item->product_price * $item->product_qty, 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
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
                    <button type="button" class="btn btn-success btn-full" id="submitBtn" onclick="processPayment()">
                        <span id="btnText">Pay RM <?= number_format($total_amount, 2) ?></span>
                        <span id="btnLoading" style="display: none;" class="loading"></span>
                    </button>
                    
                    <!-- Back to Cart Button (Cancel Checkout) -->
                    <button type="button" class="btn btn-secondary btn-full" onclick="cancelCheckout()" style="margin-top: 10px;">
                        Cancel Checkout & Return to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Cancel checkout and return to cart
        function cancelCheckout() {
            if (confirm('Are you sure you want to cancel checkout? Your selected items will return to your cart.')) {
                // Send AJAX request to restore items to cart
                const formData = new FormData();
                formData.append('action', 'cancel_checkout');
                
                fetch('cancel_checkout.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        window.location.href = 'cart.php';
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error. Please try again.');
                });
            }
        }
        
        // Process payment
        async function processPayment() {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const paymentError = document.getElementById('paymentError');
            
            // Reset messages
            if (paymentError) {
                paymentError.style.display = 'none';
            }
            
            // Validate address
            const addressSelected = document.querySelector('input[name="address_id"]:checked');
            if (!addressSelected) {
                document.getElementById('addressError').textContent = 'Please select a shipping address';
                document.getElementById('addressError').style.display = 'block';
                document.getElementById('addressError').scrollIntoView({ behavior: 'smooth' });
                return;
            }
            
            // Show loading
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            
            try {
                // Prepare form data
                const formData = new FormData();
                formData.append('address_id', addressSelected.value);
                
                const response = await fetch('checkout.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to Stripe Checkout
                    window.location.href = result.redirect;
                } else {
                    // Payment failed - show error
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'payment-error';
                    errorDiv.innerHTML = '✗ ' + result.message + '<br><small>Please try again.</small>';
                    errorDiv.style.display = 'flex';
                    
                    // Insert after payment section
                    const paymentSection = document.querySelector('.payment-info');
                    paymentSection.appendChild(errorDiv);
                    
                    // Enable button for retry
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                    
                    // Scroll to error message
                    errorDiv.scrollIntoView({ behavior: 'smooth' });
                }
                
            } catch (error) {
                // Network or server error
                const errorDiv = document.createElement('div');
                errorDiv.className = 'payment-error';
                errorDiv.innerHTML = '✗ Network error: ' + error.message + '<br><small>Please check your connection and try again.</small>';
                errorDiv.style.display = 'flex';
                
                const paymentSection = document.querySelector('.payment-info');
                paymentSection.appendChild(errorDiv);
                
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                
                // Scroll to error message
                errorDiv.scrollIntoView({ behavior: 'smooth' });
                
                console.error('Payment error:', error);
            }
        }
        
        // Address selection styling
        document.querySelectorAll('input[name="address_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.address-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.parentElement.classList.add('selected');
            });
        });
    </script>
</body>
</html>