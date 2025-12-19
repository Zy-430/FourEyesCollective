<?php
// cart.php
require '../_base.php';
require '../lib/db.php';

if (!$_user) {
    temp('error', 'Please login to view your cart');
    redirect('/page/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user_id = $_user->user_id;


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
            // Handle checkout (just mark as checkout, actual order creation would be separate)
            $selected_items = post('selected_items', []);
            if (!empty($selected_items)) {
                foreach ($selected_items as $item_id) {
                    $stm = $_db->prepare("UPDATE cart_item SET item_status = 'checkout', checkout_at = NOW() WHERE cart_item_id = ? AND user_id = ?");
                    $stm->execute([$item_id, $user_id]);
                }
                temp('success', "Items marked for checkout. Proceed to payment.");
            } else {
                temp('error', "Please select items to checkout");
            }
            break;
    }
    
    // Refresh page to show updated cart
    redirect('cart.php');
}

// Fetch cart items with product details
$stm = $_db->prepare("
    SELECT ci.*, p.*, c.category_name 
    FROM cart_item ci
    JOIN product p ON ci.product_id = p.product_id
    LEFT JOIN category c ON p.category_id = c.category_id
    WHERE ci.user_id = ? AND ci.item_status = 'in_cart'
    ORDER BY ci.created_at DESC
");
$stm->execute([$user_id]);
$cart_items = $stm->fetchAll();

// Calculate totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item->product_price * $item->product_qty;
    $total_items += $item->product_qty;
}

$_title = 'Shopping Cart | Four Eyes Collective';
include '../_head.php';
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="margin-bottom: 30px;">Shopping Cart</h1>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 50px 20px;">
            <p style="font-size: 18px; color: #666; margin-bottom: 20px;">Your cart is empty</p>
            <a href="shoppage.php" style="padding: 12px 30px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
                Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px;">
            <!-- Cart Items -->
            <div>
                <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; overflow: hidden;">
                    <form id="cartForm" method="post">
                        <input type="hidden" name="action" value="">
                        <input type="hidden" name="cart_item_id" value="">
                        <input type="hidden" name="quantity" value="">
                        
                        <div style="padding: 20px; border-bottom: 1px solid #e0e0e0; background: #f8f9fa;">
                            <div style="display: flex; align-items: center;">
                                <input type="checkbox" id="selectAll" style="margin-right: 15px;">
                                <span style="font-weight: bold;">Select All Items</span>
                                <span style="margin-left: auto; color: #666;"><?= count($cart_items) ?> item(s)</span>
                            </div>
                        </div>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <?php
                            // Get image path
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
                            
                            <div style="padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: flex-start;">
                                <div style="margin-right: 15px;">
                                    <input type="checkbox" name="selected_items[]" value="<?= $item->cart_item_id ?>" class="item-checkbox" style="margin-top: 40px;">
                                </div>
                                
                                <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>"
                                     style="width: 120px; height: 120px; object-fit: cover; border-radius: 6px; margin-right: 20px;">
                                
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 10px 0; font-size: 16px;">
                                        <?= encode($item->product_name) ?>
                                    </h3>
                                    <p style="color: #666; margin: 0 0 10px 0; font-size: 14px;">
                                        Category: <?= encode($item->category_name) ?>
                                    </p>
                                    <p class="price-each" data-price="<?= $item->product_price ?>" 
                                    style="color: #2c3e50; font-weight: bold; font-size: 18px; margin: 0 0 15px 0;">
                                        RM <?= number_format($item->product_price, 2) ?>
                                    </p>
                                    
                                    <!-- Quantity Controls -->
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <button type="button" onclick="updateQuantity('<?= $item->cart_item_id ?>', <?= $item->product_qty - 1 ?>)"
                                                <?= $item->product_qty <= 1 ? 'disabled' : '' ?>
                                                style="width: 30px; height: 30px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">
                                            −
                                        </button>
                                        
                                        <span class="qty-number"
                                        style="min-width: 40px; text-align: center; font-weight: bold;">
                                            <?= $item->product_qty ?>
                                        </span>
                                        
                                        <button type="button" onclick="updateQuantity('<?= $item->cart_item_id ?>', <?= $item->product_qty + 1 ?>)"
                                                style="width: 30px; height: 30px; border: 1px solid #ddd; background: white; border-radius: 4px; cursor: pointer;">
                                            +
                                        </button>
                                        
                                        <button type="button" onclick="removeItem('<?= $item->cart_item_id ?>')"
                                                style="margin-left: 20px; padding: 6px 12px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; cursor: pointer; font-size: 14px;">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; min-width: 120px;">
                                    <div style="font-weight: bold; font-size: 18px; color: #2c3e50;">
                                        RM <?= number_format($item->product_price * $item->product_qty, 2) ?>
                                    </div>
                                    <div style="color: #666; font-size: 14px;">
                                        RM <?= number_format($item->product_price, 2) ?> each
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div>
                <div style="background: white; border-radius: 8px; border: 1px solid #e0e0e0; padding: 25px; position: sticky; top: 20px;">
                    <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">
                        Order Summary
                    </h3>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal (<span id="summaryCount"><?= $total_items ?></span> item<?= $total_items != 1 ? 's' : '' ?>)</span>
                            <span id="summarySubtotal" style="font-weight: bold;">RM <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                            <span>Tax</span>
                            <span>Calculated at checkout</span>
                        </div>
                    </div>
                    
                    <div style="border-top: 2px solid #e0e0e0; padding-top: 20px; margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;">
                            <span>Estimated Total</span>
                            <span id="summaryEstimated">RM <?= number_format($subtotal, 2) ?></span>
                        </div>
                    </div>
                    
                    <button type="button" onclick="checkoutSelected()"
                            style="width: 100%; padding: 15px; background: #2c3e50; color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; margin-bottom: 15px;">
                        Proceed to Checkout
                    </button>
                    
                    <a href="shoppage.php" style="display: block; text-align: center; color: #2c3e50; text-decoration: none; padding: 10px; border: 1px solid #2c3e50; border-radius: 5px;">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Update quantity
function updateQuantity(cartItemId, newQuantity) {
    if (newQuantity < 1) return;
    
    const form = document.getElementById('cartForm');
    form.querySelector('input[name="action"]').value = 'update';
    form.querySelector('input[name="cart_item_id"]').value = cartItemId;
    form.querySelector('input[name="quantity"]').value = newQuantity;
    form.submit();
}

// Remove item
function removeItem(cartItemId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        const form = document.getElementById('cartForm');
        form.querySelector('input[name="action"]').value = 'remove';
        form.querySelector('input[name="cart_item_id"]').value = cartItemId;
        form.submit();
    }
}

// Checkout selected items
function checkoutSelected() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    if (selectedItems.length === 0) {
        alert('Please select items to checkout');
        return;
    }
    
    if (confirm('Proceed to checkout with ' + selectedItems.length + ' item(s)?')) {
        const form = document.getElementById('cartForm');
        form.querySelector('input[name="action"]').value = 'checkout';
        form.submit();
    }
}
// Checkbox triggers recalculation
document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', updateTotals);
});

// Select All
document.getElementById('selectAll')?.addEventListener('change', function() {
    let all = this.checked;
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = all);
    updateTotals();
});

function updateTotals() {
    let subtotal = 0;
    let count = 0;

    document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        let row = cb.closest('div[style*="border-bottom"]');
        let price = parseFloat(row.querySelector('.price-each').dataset.price);
        let qty = parseInt(row.querySelector('.qty-number').innerText);

        subtotal += price * qty;
        count += qty;
    });

    document.getElementById('summarySubtotal').innerText = "RM " + subtotal.toFixed(2);
    document.getElementById('summaryTotal').innerText = "RM " + subtotal.toFixed(2);
    document.getElementById('summaryCount').innerText = count;
}
</script>

<?php include '../_foot.php'; ?>