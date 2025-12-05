<?php
require '../_base.php';
require '../lib/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/page/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user_id = $_SESSION['user_id'];

// Get checkout items from session
$checkout_items = $_SESSION['checkout_items'] ?? [];
if (empty($checkout_items)) {
    temp('error', 'No items selected for checkout');
    redirect('/page/cart.php');
}

// Get cart items for checkout
$placeholders = str_repeat('?,', count($checkout_items) - 1) . '?';
$stm = $_db->prepare("
    SELECT ci.*, p.*, c.category_name 
    FROM cart_item ci
    JOIN product p ON ci.product_id = p.product_id
    JOIN category c ON p.category_id = c.category_id
    WHERE ci.cart_item_id IN ($placeholders) AND ci.user_id = ? AND ci.item_status = 'in_cart'
");
$params = array_merge($checkout_items, [$user_id]);
$stm->execute($params);
$checkout_items_data = $stm->fetchAll();

if (empty($checkout_items_data)) {
    temp('error', 'No valid items for checkout');
    redirect('/page/cart.php');
}

// Get user addresses
$stm = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC");
$stm->execute([$user_id]);
$addresses = $stm->fetchAll();

// Get user payment methods
$stm = $_db->prepare("SELECT * FROM payment_method WHERE user_id = ? ORDER BY is_default DESC");
$stm->execute([$user_id]);
$payment_methods = $stm->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($checkout_items_data as $item) {
    $subtotal += $item->product_price * $item->product_qty;
}
$shipping = 0;
$total = $subtotal + $shipping;

// Handle checkout submission
if (is_post()) {
    $payment_method_id = post('payment_method');
    $address_id = post('address_id');
    $use_new_card = post('use_new_card') === '1';
    $stripe_token = post('stripe_token');
    
    // Validate
    if (empty($address_id)) {
        temp('error', 'Please select a shipping address');
        redirect();
    }
    
    if (!$use_new_card && empty($payment_method_id)) {
        temp('error', 'Please select a payment method or add a new card');
        redirect();
    }
    
    if ($use_new_card && empty($stripe_token)) {
        temp('error', 'Please add a new payment method');
        redirect();
    }
    
    // Start transaction
    $_db->beginTransaction();
    
    try {
        // Create order
        $order_id = 'OR' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        $stm = $_db->prepare("
            INSERT INTO `order` (order_id, user_id, address_id, order_date, total_amount, status) 
            VALUES (?, ?, ?, NOW(), ?, 'pending')
        ");
        $stm->execute([$order_id, $user_id, $address_id, $total]);
        
        // Create order items and update cart items
        foreach ($checkout_items_data as $item) {
            $order_item_id = 'OI' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $subtotal_item = $item->product_price * $item->product_qty;
            
            // Create order item
            $stm = $_db->prepare("
                INSERT INTO order_item (order_item_id, order_id, product_id, product_qty, price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stm->execute([$order_item_id, $order_id, $item->product_id, $item->product_qty, $item->product_price, $subtotal_item]);
            
            // Update cart item
            $stm = $_db->prepare("
                UPDATE cart_item 
                SET item_status = 'checkout', checkout_at = NOW(), order_item_id = ?
                WHERE cart_item_id = ?
            ");
            $stm->execute([$order_item_id, $item->cart_item_id]);
        }
        
        // Process payment with Stripe
        if ($use_new_card) {
            // Save new payment method
            $payment_method_id = 'PM' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            
            // Note: In a real application, you would:
            // 1. Use Stripe API to create a customer/payment method
            // 2. Store the Stripe customer ID and payment method ID
            // For this example, we'll simulate it
            
            $stm = $_db->prepare("
                INSERT INTO payment_method (payment_method_id, user_id, provider, token, brand, last4, expiry_month, exiry_year, is_default)
                VALUES (?, ?, 'stripe', ?, ?, ?, ?, ?, 1)
            ");
            // You would get these from Stripe response
            $stm->execute([$payment_method_id, $user_id, $stripe_token, 'Visa', '4242', 12, 2030]);
        }
        
        // Create payment record
        $payment_id = 'PAY' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $stm = $_db->prepare("
            INSERT INTO payment (payment_id, order_id, amount, transaction_date, payment_method_id, status)
            VALUES (?, ?, ?, NOW(), ?, 'succeeded')
        ");
        $stm->execute([$payment_id, $order_id, $total, $payment_method_id]);
        
        // Update order status
        $stm = $_db->prepare("UPDATE `order` SET status = 'processing' WHERE order_id = ?");
        $stm->execute([$order_id]);
        
        // Create order history
        $history_id = 'HIS' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $stm = $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, changed_at, changed_by)
            VALUES (?, ?, 'processing', NOW(), ?)
        ");
        $stm->execute([$history_id, $order_id, $user_id]);
        
        $_db->commit();
        
        // Clear checkout session
        unset($_SESSION['checkout_items']);
        
        temp('success', 'Order placed successfully!');
        redirect('/page/order_confirmation.php?id=' . $order_id);
        
    } catch (Exception $e) {
        $_db->rollBack();
        temp('error', 'An error occurred while processing your order. Please try again.');
        redirect();
    }
}

$_title = 'Checkout | Four Eyes Collective';
include '../_head.php';
?>

<h1>Checkout</h1>

<!-- Checkout Progress -->
<div style="display: flex; justify-content: center; margin-bottom: 40px;">
    <div style="display: flex; align-items: center;">
        <div style="background: #2c3e50; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
            1
        </div>
        <div style="width: 100px; height: 2px; background: #2c3e50;"></div>
        <div style="background: #2c3e50; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
            2
        </div>
        <div style="width: 100px; height: 2px; background: #ddd;"></div>
        <div style="background: #ddd; color: #666; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
            3
        </div>
    </div>
    <div style="display: flex; justify-content: space-between; width: 400px; margin-top: 10px;">
        <span style="color: #2c3e50; font-weight: bold;">Cart</span>
        <span style="color: #2c3e50; font-weight: bold;">Checkout</span>
        <span style="color: #666;">Confirmation</span>
    </div>
</div>

<form method="post" id="checkout-form">
    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
        <!-- Left Column: Forms -->
        <div>
            <!-- Shipping Address -->
            <div style="margin-bottom: 40px;">
                <h3 style="margin-bottom: 20px; color: #2c3e50;">Shipping Address</h3>
                
                <?php if (empty($addresses)): ?>
                    <div style="background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; text-align: center;">
                        <p style="margin-bottom: 15px;">No saved addresses found</p>
                        <a href="/page/address.php?action=add&redirect=checkout" 
                           style="color: #2c3e50; text-decoration: none; font-weight: 500;">
                            Add New Address
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($addresses as $address): ?>
                            <label style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; cursor: pointer; display: block;">
                                <input type="radio" name="address_id" value="<?= $address->address_id ?>" 
                                       <?= $address->default_flag ? 'checked' : '' ?>
                                       style="margin-right: 10px;">
                                <div>
                                    <div style="font-weight: bold; margin-bottom: 5px;">
                                        <?= encode($address->address_line1) ?>
                                        <?php if ($address->address_line2): ?>
                                            , <?= encode($address->address_line2) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color: #666;">
                                        <?= encode($address->city) ?>, <?= encode($address->state) ?> <?= encode($address->postcoed) ?>
                                    </div>
                                    <div style="color: #666;">
                                        <?= encode($address->country) ?>
                                    </div>
                                    <?php if ($address->default_flag): ?>
                                        <span style="background: #2c3e50; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; margin-top: 5px; display: inline-block;">
                                            Default
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <a href="/page/address.php?action=add&redirect=checkout" 
                           style="color: #2c3e50; text-decoration: none; font-weight: 500;">
                            + Add New Address
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Method -->
            <div>
                <h3 style="margin-bottom: 20px; color: #2c3e50;">Payment Method</h3>
                
                <!-- Saved Payment Methods -->
                <?php if (!empty($payment_methods)): ?>
                    <div style="margin-bottom: 30px;">
                        <h4 style="margin-bottom: 15px; color: #555;">Saved Cards</h4>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach ($payment_methods as $pm): ?>
                                <label style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; cursor: pointer; display: block;">
                                    <input type="radio" name="payment_method" value="<?= $pm->payment_method_id ?>" 
                                           class="saved-card" 
                                           <?= $pm->is_default ? 'checked' : '' ?>
                                           style="margin-right: 10px;">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div style="width: 40px; height: 25px; background: #f0f0f0; border-radius: 3px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?= substr($pm->brand, 0, 1) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: bold;">
                                                <?= ucfirst($pm->brand) ?> •••• <?= $pm->last4 ?>
                                            </div>
                                            <div style="color: #666; font-size: 0.9em;">
                                                Expires <?= str_pad($pm->expiry_month, 2, '0', STR_PAD_LEFT) ?>/<?= $pm->exiry_year ?>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- New Card Option -->
                <div style="margin-bottom: 30px;">
                    <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <input type="radio" name="use_new_card" value="1" id="use-new-card" 
                               <?= empty($payment_methods) ? 'checked' : '' ?>
                               style="margin-right: 5px;">
                        <span style="font-weight: bold;">Add New Card</span>
                    </label>
                    
                    <!-- Stripe Card Element -->
                    <div id="card-element" style="<?= empty($payment_methods) ? '' : 'display: none;' ?>">
                        <div style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: white;">
                            <div id="stripe-card" style="min-height: 40px;"></div>
                            <div id="card-errors" role="alert" style="color: #e74c3c; margin-top: 10px; font-size: 0.9em;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Order Summary -->
        <div>
            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 25px; background: #f8f9fa; position: sticky; top: 100px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #2c3e50;">Order Summary</h3>
                
                <!-- Order Items -->
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                    <?php foreach ($checkout_items_data as $item): ?>
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                            <?php 
                            $folder = [
                                'CA0001' => 'glasses',
                                'CA0002' => 'sunglasses', 
                                'CA0003' => 'contactlens',
                                'CA0004' => 'kids'
                            ][$item->category_id] ?? 'others';
                            
                            $images = explode(',', $item->product_image);
                            $firstImage = trim($images[0]);
                            $imgPath = "/images/product/$folder/$firstImage";
                            ?>
                            
                            <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                            
                            <div style="flex: 1;">
                                <div style="font-weight: bold; font-size: 0.9em; margin-bottom: 5px;">
                                    <?= encode($item->product_name) ?>
                                </div>
                                <div style="color: #666; font-size: 0.8em;">
                                    Qty: <?= $item->product_qty ?>
                                </div>
                                <div style="font-weight: bold; color: #2c3e50; font-size: 0.9em; margin-top: 5px;">
                                    RM <?= number_format($item->product_price * $item->product_qty, 2) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Totals -->
                <div style="border-top: 1px solid #ddd; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Subtotal</span>
                        <span>RM <?= number_format($subtotal, 2) ?></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Shipping</span>
                        <span>FREE</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2em; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
                        <span>Total</span>
                        <span>RM <?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div style="margin-top: 30px;">
                    <button type="submit" id="submit-payment" 
                            style="background: #2c3e50; color: white; border: none; padding: 15px; width: 100%; border-radius: 4px; font-size: 1.1em; font-weight: bold; cursor: pointer; transition: background 0.3s ease;">
                        Place Order
                    </button>
                    
                    <p style="text-align: center; margin-top: 15px; color: #666; font-size: 0.9em;">
                        By placing your order, you agree to our <a href="#" style="color: #2c3e50;">Terms & Conditions</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <input type="hidden" name="stripe_token" id="stripe-token">
</form>

<!-- Load Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<script>
$(function() {
    // Initialize Stripe with your publishable key
    const stripe = Stripe('pk_test_51SZZzU2LpkFiPUtIjT4A3p4jku3AscftExkqT8Nc4O0uu1KspBx6jtWSMQwpQSkoS7NUB4axuVp999twSrbA9Cun00Hr2Ue6Yh');
    
    // Create Stripe elements
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#333',
                '::placeholder': {
                    color: '#aab7c4'
                }
            }
        }
    });
    
    // Mount card element
    const cardMount = document.getElementById('stripe-card');
    if (cardMount) {
        cardElement.mount('#stripe-card');
    }
    
    // Handle card errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Toggle new card form
    $('#use-new-card').on('change', function() {
        if ($(this).is(':checked')) {
            $('#card-element').show();
            $('.saved-card').prop('checked', false);
        }
    });
    
    $('.saved-card').on('change', function() {
        if ($(this).is(':checked')) {
            $('#card-element').hide();
            $('#use-new-card').prop('checked', false);
        }
    });
    
    // Form submission
    $('#checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submit-payment');
        submitBtn.prop('disabled', true).text('Processing...');
        
        // If using new card, create token
        if ($('#use-new-card').is(':checked')) {
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    $('#card-errors').text(result.error.message);
                    submitBtn.prop('disabled', false).text('Place Order');
                } else {
                    $('#stripe-token').val(result.token.id);
                    $('#checkout-form')[0].submit();
                }
            });
        } else {
            // If using saved card, submit directly
            $('#checkout-form')[0].submit();
        }
    });
});
</script>

<?php include '../_foot.php'; ?>