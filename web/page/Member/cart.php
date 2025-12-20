<?php
require '../../_base.php';
require '../../lib/db.php';
require '../../lib/category.php';

if (!$_user) {
    temp('error', 'Please login to view your cart');
    redirect('/page/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user_id = $_user->user_id;

function is_ajax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// get cart count
function getCartCount($user_id, $db)
{
    $stm = $db->prepare("SELECT SUM(product_qty) as total FROM cart_item 
                        WHERE user_id = ? AND item_status = 'in_cart'");
    $stm->execute([$user_id]);
    $result = $stm->fetch();
    return $result->total ?? 0;
}

// Handle POST actions
if (is_post()) {
    $action = post('action');
    $product_id = post('product_id');
    $cart_item_id = post('cart_item_id');
    $quantity = post('quantity', 1);

    switch ($action) {
        case 'add':
            // Check if product exists
            $stm = $_db->prepare("SELECT * FROM product WHERE product_id = ?");
            $stm->execute([$product_id]);
            $product = $stm->fetch();

            if (!$product) {
                temp('error', 'Product not found');
                break;
            }

            // Check stock
            if ($product->product_stock < $quantity) {
                temp('error', 'Insufficient stock');
                break;
            }

            // Check if product already in cart
            $stm = $_db->prepare("SELECT * FROM cart_item WHERE user_id = ? AND product_id = ? AND item_status = 'in_cart'");
            $stm->execute([$user_id, $product_id]);
            $existing_item = $stm->fetch();

            if ($existing_item) {
                // Update quantity
                $new_qty = $existing_item->product_qty + $quantity;
                $stm = $_db->prepare("UPDATE cart_item SET product_qty = ?, created_at = NOW() WHERE cart_item_id = ?");
                $stm->execute([$new_qty, $existing_item->cart_item_id]);

                temp('success', "Quantity updated in cart");
            } else {
                // Insert new cart item
                // Generate new cart item ID
                $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(cart_item_id, 3) AS UNSIGNED)) as max_id FROM cart_item");
                $max_id = $stm->fetch()->max_id;
                $new_id = 'CI' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);

                $stm = $_db->prepare("INSERT INTO cart_item (cart_item_id, user_id, product_id, product_qty, item_status, created_at) VALUES (?, ?, ?, ?, 'in_cart', NOW())");
                $stm->execute([$new_id, $user_id, $product_id, $quantity]);

                temp('success', "Product added to cart successfully!");
            }
            break;

        case 'update':
            // Update quantity
            $stm = $_db->prepare("UPDATE cart_item SET product_qty = ? WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'");
            $stm->execute([$quantity, $cart_item_id, $user_id]);

            temp('success', "Quantity updated");
            break;

        case 'remove':
            // Remove item (change status to abandoned)
            $stm = $_db->prepare("UPDATE cart_item SET item_status = 'abandoned', abandon_at = NOW() WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'");
            $stm->execute([$cart_item_id, $user_id]);

            temp('success', "Item removed from cart");
            break;

        case 'checkout':
            $selected_items = post('selected_items', []);
            if (!empty($selected_items)) {
                // Mark selected items as checkout
                foreach ($selected_items as $item_id) {
                    $stm = $_db->prepare("
                        UPDATE cart_item 
                        SET item_status = 'checkout', 
                            checkout_at = NOW(),
                            order_item_id = NULL  -- Ensure it's NULL
                        WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'
                    ");
                    $stm->execute([$item_id, $user_id]);
                }
                redirect('checkout.php');
            } else {
                temp('error', "Please select items to checkout");
            }
            break;
    }

    // Refresh page to show updated cart (only for non-AJAX requests)
    if (!is_ajax()) {
        redirect('cart.php');
    }
}

// Fetch cart items with product details
$stm = $_db->prepare("
    SELECT ci.*, p.*, c.category_name 
    FROM cart_item ci
    JOIN product p ON ci.product_id = p.product_id
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE ci.user_id = ? AND ci.item_status = 'in_cart' AND ci.order_item_id IS NULL
    ORDER BY ci.created_at DESC
");

$stm->execute([$user_id]);
$cart_items = $stm->fetchAll();

// Calculate totals for ALL items (for display only)
$subtotal_all = 0;
$total_items_all = 0;
foreach ($cart_items as $item) {
    $subtotal_all += $item->product_price * $item->product_qty;
    $total_items_all += $item->product_qty;
}

// Delivery fee for non-JS fallback: FREE when subtotal >= 500, else RM20
$delivery_fee_all = ($subtotal_all >= 500) ? 0 : 20;
$estimated_total_all = $subtotal_all + $delivery_fee_all;

$_title = 'Shopping Cart | Four Eyes Collective';
include '../../_head.php';
?>
<link rel="stylesheet" href="/css/cart.css">
<?php
?>
<div class="cart-container">
    <h1 class="cart-title">Shopping Cart</h1>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <p>Your cart is empty</p>
            <a href="shoppage.php" class="continue-shopping-btn">
                Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <!-- Use the cart-grid class for responsive layout -->
        <div class="cart-grid">
            <!-- Cart Items - Will be on top on mobile -->
            <div class="cart-items-section">
                <div class="cart-items-box">
                    <form id="cartForm" method="post">
                        <input type="hidden" name="action" value="">
                        <input type="hidden" name="cart_item_id" value="">
                        <input type="hidden" name="quantity" value="">

                        <div class="cart-header">
                            <div style="display: flex; align-items: center;">
                                <label style="display:flex; align-items:center; cursor:pointer;">
                                    <input type="checkbox" id="selectAll" style="margin-right: 15px;">
                                    <span style="font-weight: bold;">Select All Items</span>
                                </label>
                                <span style="margin-left: auto; color: #666;"><?= count($cart_items) ?> item(s)</span>
                            </div>
                        </div>

                        <?php foreach ($cart_items as $item): ?>
                            <?php
                            $folder = $categoryFolders[$item->category_id] ?? 'others';
                            $imgArray = explode(',', $item->product_image);
                            $firstImage = trim($imgArray[0]);
                            $imgPath = "/images/product/$folder/$firstImage";
                            ?>

                            <div class="cart-item-row">
                                <!-- Checkbox (Left) -->
                                <div class="cart-item-checkbox">
                                    <input type="checkbox" name="selected_items[]" value="<?= $item->cart_item_id ?>"
                                        class="item-checkbox"
                                        data-price="<?= $item->product_price ?>"
                                        data-quantity="<?= $item->product_qty ?>">
                                </div>

                                <!-- Image (30%) -->
                                <div class="cart-item-image-container">
                                    <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>" class="cart-item-image">
                                </div>

                                <!-- Product Details & Controls (70%) -->
                                <div class="cart-item-details">
                                    <h3 class="cart-item-name"><?= encode($item->product_name) ?></h3>
                                    <p class="cart-item-category">Category: <?= encode($item->category_name) ?></p>
                                    <p class="cart-item-price">RM <?= number_format((float)$item->product_price, 2) ?></p>

                                    <!-- Quantity Controls -->
                                    <div class="cart-item-controls">
                                        <button type="button" class="qty-btn qty-decrease"
                                            data-cart-id="<?= $item->cart_item_id ?>"
                                            data-qty="<?= $item->product_qty ?>"
                                            <?= $item->product_qty <= 1 ? 'disabled' : '' ?>>
                                            −
                                        </button>

                                        <span class="qty-number"><?= $item->product_qty ?></span>

                                        <button type="button" class="qty-btn qty-increase"
                                            data-cart-id="<?= $item->cart_item_id ?>"
                                            data-qty="<?= $item->product_qty ?>">
                                            +
                                        </button>

                                        <button type="button" class="remove-item" data-cart-id="<?= $item->cart_item_id ?>">
                                            Remove
                                        </button>
                                    </div>
                                </div>

                                <!-- Total Price (Right) -->
                                <div class="cart-item-total">
                                    <div class="item-total-price">
                                        RM <?= number_format((float)($item->product_price * $item->product_qty), 2) ?>
                                    </div>
                                    <div class="item-unit-price">
                                        RM <?= number_format((float)$item->product_price, 2) ?> each
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary-box">
                <h3>Cart Summary</h3>

                <div class="summary-details">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summarySubtotal">RM <?= number_format((float)$subtotal_all, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="shippingCost"><?= $delivery_fee_all > 0 ? 'RM ' . number_format((float)$delivery_fee_all, 2) : 'FREE' ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>Included</span>
                    </div>

                    <div class="summary-row total">
                        <span>Estimated Total</span>
                        <span id="summaryEstimated">RM <?= number_format((float)$estimated_total_all, 2) ?></span>
                    </div>
                </div>

                <button id="checkoutBtn" type="button" class="btn-checkout">
                    Proceed to Checkout — RM <?= number_format((float)$estimated_total_all, 2) ?>
                </button>

                <a href="../shoppage.php" class="continue-shopping-btn">
                    Continue Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="../../js/cart.js"></script>

<?php include '../../_foot.php'; ?>