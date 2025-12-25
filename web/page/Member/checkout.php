<?php
require '../../_base.php';
require '../../lib/db.php';
require_once '../../stripe-php-19.0.0/init.php';
require '../../lib/category.php';

\Stripe\Stripe::setApiKey('sk_test_51SZZzU2LpkFiPUtITtnxkZtzongU6II64ZL8YSynXO951EcqTfIfRbWAl586Hh8LOXYexaqDtwwaO6rxwdOQvygm006Vp82pdb');

auth('Member');
$user_id = $_user->user_id;

// Load checkout items
$stm = $_db->prepare("
    SELECT ci.*, p.product_name, p.product_price, p.product_stock, p.product_image, 
           c.category_id, c.category_name
    FROM cart_item ci 
    JOIN product p ON ci.product_id = p.product_id
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE ci.user_id = ? 
      AND ci.item_status = 'checkout' 
      AND ci.order_item_id IS NULL 
");
$stm->execute([$user_id]);
$cart_items = $stm->fetchAll(PDO::FETCH_OBJ);

// Handle page refresh without POST data
if (empty($_POST) && !$cart_items) {
    $stm = $_db->prepare("
        SELECT order_id FROM `order` 
        WHERE user_id = ? AND status = 'pending_payment' 
        ORDER BY order_date DESC LIMIT 1
    ");
    $stm->execute([$user_id]);
    $pendingOrder = $stm->fetch(PDO::FETCH_OBJ);

    if ($pendingOrder) {
        header("Location: cancel_payment.php?order_id=" . $pendingOrder->order_id);
        exit;
    } else {
        header("Location: cart.php");
        exit;
    }
}

if (!$cart_items) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="/images/WIS_logo_white.png">
        <title>No Checkout Items | Four Eyes Collective</title>
        <link rel="stylesheet" href="/css/checkout_flow.css">
        <link rel="stylesheet" href="/css/app.css">
    </head>

    <body>
        <div class="checkout-status-container status-error">
            <div class="page-header">
                <h1>🛒 No Items for Checkout</h1>
                <p>You have no items selected for checkout.</p>
            </div>
            <div class="checkout-section" style="text-align: center;">
                <p>Please select items from your cart first.</p>
                <div class="action-buttons">
                    <a href="../cart.php" class="btn btn-secondary">Return to Cart</a>
                    <a href="../shoppage.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

// Calculate total
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item->product_price * $item->product_qty;
}

// Calculate delivery fee
$delivery_fee = ($total_amount >= 500) ? 0 : 20;
$total_with_delivery = $total_amount + $delivery_fee;

// Load addresses
$stm = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC");
$stm->execute([$user_id]);
$addresses = $stm->fetchAll(PDO::FETCH_OBJ);

// Handle form submission for payment
if (is_post()) {
    $address_id = post('address_id');
    $action = post('action');

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
        $stm->execute([$order_id, $user_id, $address_id, $total_with_delivery]);

        // Insert order items and UPDATE cart_item with order_item_id
        foreach ($cart_items as $i) {
            $stm2 = $_db->query("SELECT MAX(CAST(SUBSTRING(order_item_id, 3) AS UNSIGNED)) AS maxid FROM order_item");
            $maxItem = $stm2->fetch()->maxid ?? 0;
            $order_item_id = "OI" . str_pad($maxItem + 1, 4, "0", STR_PAD_LEFT);

            $stm3 = $_db->prepare("
                INSERT INTO order_item (order_item_id, order_id, product_id, product_qty, price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stm3->execute([
                $order_item_id,
                $order_id,
                $i->product_id,
                $i->product_qty,
                $i->product_price,
                $i->product_price * $i->product_qty
            ]);

            // UPDATE cart_item with the order_item_id
            $_db->prepare("
                UPDATE cart_item 
                SET order_item_id = ?
                WHERE cart_item_id = ? AND user_id = ? AND item_status = 'checkout'
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

        // Add delivery fee as a line item if applicable
        if (!empty($delivery_fee) && $delivery_fee > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => 'Delivery Fee',
                    ],
                    'unit_amount' => intval($delivery_fee * 100),
                ],
                'quantity' => 1,
            ];
        }

        $baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";

        // Create Stripe session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => [
                'card',
                'fpx',
                'grabpay'
            ],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => "$baseURL/page/Member/order_success.php?session_id={CHECKOUT_SESSION_ID}&order_id=$order_id",
            'cancel_url' => "$baseURL/page/Member/cancel_payment.php?order_id=$order_id",
            'customer_email' => $_user->email,
            'metadata' => ['order_id' => $order_id, 'user_id' => $user_id]
        ]);

        // Insert payment row 
        $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(payment_id, 4) AS UNSIGNED)) AS maxid FROM payment");
        $maxPay = $stm->fetch()->maxid ?? 0;
        $payment_id = "PAY" . str_pad($maxPay + 1, 4, "0", STR_PAD_LEFT);

        $_db->prepare("
            INSERT INTO payment (payment_id, order_id, amount, status, stripe_session_id, transaction_date)
            VALUES (?, ?, ?, 'pending', ?, NOW())
        ")->execute([$payment_id, $order_id, $total_with_delivery, $session->id]);

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
    <link rel="shortcut icon" href="/images/WIS_logo_white.png">
    <title>Checkout | Four Eyes Collective</title>
    <link rel="stylesheet" href="/css/checkout_flow.css">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/notifications.js"></script>
    <script src="/js/checkout_flow.js"></script>
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
                                You will be redirected to payment page to complete your purchase.
                            </p>
                            <div class="security-note">
                                <div class="security-icon">🔒</div>
                                <div>
                                    <strong>Secure Payment</strong><br>
                                    Your payment information is encrypted and secure.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="order-summary">
                    <div class="checkout-section" style="position: sticky; top: 100px;">
                        <h2 class="section-title">Order Summary</h2>

                        <div class="order-items" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                            <?php foreach ($cart_items as $item): ?>
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
                                <span><?= $delivery_fee > 0 ? 'RM ' . number_format($delivery_fee, 2) : 'FREE' ?></span>
                            </div>
                            <div class="total-row">
                                <span>Tax</span>
                                <span>Included</span>
                            </div>
                        </div>

                        <div class="total-row total-amount">
                            <span>Total</span>
                            <span>RM <?= number_format($total_with_delivery, 2) ?></span>
                        </div>

                        <!-- Payment Button -->
                        <button type="button" class="btn btn-success" id="submitBtn">
                            <span id="btnText">Pay RM <?= number_format($total_with_delivery, 2) ?></span>
                            <span id="btnLoading" style="display: none;" class="loading"></span>
                        </button>

                        <!--Cancel Button-->
                        <a href="cancel_checkout.php" class="btn btn-secondary"
                            id="cancelCheckoutBtn"
                            data-user="<?= encode($_user->email) ?>"
                            data-confirm="Are you sure you want to cancel checkout ? Your selected items will be returned to cart.">
                            Cancel Checkout
                        </a>


                        <p style="text-align: center; margin-top: 15px; color: #7f8c8d; font-size: 0.9rem;">
                            By completing your purchase, you agree to our <a href="#" style="color: #2c3e50;">Terms & Conditions</a>
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>

</body>

</html>