<?php
require '../_base.php';
require '../lib/db.php';

if (!$_user) {
    temp('error', 'Please login to view your cart');
    redirect('/page/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$user_id = $_user->user_id;

// Helper function to check if request is AJAX
function is_ajax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Helper function to get cart count
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
                if (is_ajax()) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Product not found',
                        'cart_count' => getCartCount($user_id, $_db)
                    ]);
                    exit;
                }
                temp('error', 'Product not found');
                break;
            }

            // Check stock
            if ($product->product_stock < $quantity) {
                if (is_ajax()) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Insufficient stock',
                        'cart_count' => getCartCount($user_id, $_db)
                    ]);
                    exit;
                }
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

                if (is_ajax()) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Quantity updated in cart",
                        'cart_count' => getCartCount($user_id, $_db)
                    ]);
                    exit;
                }
                temp('success', "Quantity updated in cart");
            } else {
                // Insert new cart item
                // Generate new cart item ID
                $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(cart_item_id, 3) AS UNSIGNED)) as max_id FROM cart_item");
                $max_id = $stm->fetch()->max_id;
                $new_id = 'CI' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);

                $stm = $_db->prepare("INSERT INTO cart_item (cart_item_id, user_id, product_id, product_qty, item_status, created_at) VALUES (?, ?, ?, ?, 'in_cart', NOW())");
                $stm->execute([$new_id, $user_id, $product_id, $quantity]);

                if (is_ajax()) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Product added to cart successfully!",
                        'cart_count' => getCartCount($user_id, $_db)
                    ]);
                    exit;
                }
                temp('success', "Product added to cart successfully!");
            }
            break;

        case 'update':
            // Update quantity
            $stm = $_db->prepare("UPDATE cart_item SET product_qty = ? WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'");
            $stm->execute([$quantity, $cart_item_id, $user_id]);

            if (is_ajax()) {
                echo json_encode([
                    'success' => true,
                    'message' => "Quantity updated",
                    'cart_count' => getCartCount($user_id, $_db)
                ]);
                exit;
            }
            temp('success', "Quantity updated");
            break;

        case 'remove':
            // Remove item (change status to abandoned)
            $stm = $_db->prepare("UPDATE cart_item SET item_status = 'abandoned', abandon_at = NOW() WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'");
            $stm->execute([$cart_item_id, $user_id]);

            if (is_ajax()) {
                echo json_encode([
                    'success' => true,
                    'message' => "Item removed from cart",
                    'cart_count' => getCartCount($user_id, $_db)
                ]);
                exit;
            }
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
                if (is_ajax()) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Items ready for checkout",
                        'redirect' => 'checkout.php'
                    ]);
                    exit;
                }
                redirect('checkout.php');
            } else {
                if (is_ajax()) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Please select items to checkout"
                    ]);
                    exit;
                }
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

                            <div style="padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: flex-start;" class="cart-item-row">
                                <div style="margin-right: 15px;">
                                    <input type="checkbox" name="selected_items[]" value="<?= $item->cart_item_id ?>"
                                        class="item-checkbox"
                                        style="margin-top: 40px;"
                                        data-price="<?= $item->product_price ?>"
                                        data-quantity="<?= $item->product_qty ?>">
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
                                    <div class="item-total" style="font-weight: bold; font-size: 18px; color: #2c3e50;">
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
                        Cart Summary
                    </h3>

                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal (<span id="summaryCount">0</span> item<span id="summaryItemsPlural">s</span>)</span>
                            <span id="summarySubtotal" style="font-weight: bold;">RM 0.00</span>
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
                            <span id="summaryEstimated">RM 0.00</span>
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
    // Initialize variables
    let itemCheckboxes = [];
    let selectAllCheckbox = null;

    // Update quantity
    function updateQuantity(cartItemId, newQuantity) {
        if (newQuantity < 1) return;

        const xhr = new XMLHttpRequest();
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('cart_item_id', cartItemId);
        formData.append('quantity', newQuantity);

        xhr.open('POST', 'cart.php');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Update cart count in header
                        if (response.cart_count !== undefined) {
                            updateCartCount(response.cart_count);
                        }
                        // Reload page to show updated cart
                        location.reload();
                    } else {
                        alert(response.message || 'Error updating quantity');
                    }
                } catch (e) {
                    location.reload();
                }
            }
        };
        xhr.send(formData);
    }

    // Remove item
    function removeItem(cartItemId) {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('cart_item_id', cartItemId);

            xhr.open('POST', 'cart.php');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Update cart count in header
                            if (response.cart_count !== undefined) {
                                updateCartCount(response.cart_count);
                            }
                            // Reload page to show updated cart
                            location.reload();
                        }
                    } catch (e) {
                        location.reload();
                    }
                }
            };
            xhr.send(formData);
        }
    }

    // Checkout selected items
    function checkoutSelected() {
        const selectedItems = [];
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            selectedItems.push(cb.value);
        });

        if (selectedItems.length === 0) {
            alert('Please select items to checkout');
            return;
        }

        if (confirm('Proceed to checkout with ' + selectedItems.length + ' item(s)?')) {
            // Mark selected items as "checkout" status and redirect to checkout page
            const formData = new FormData();
            selectedItems.forEach(itemId => {
                formData.append('selected_items[]', itemId);
            });
            formData.append('action', 'checkout');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'cart.php');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            window.location.href = 'checkout.php';
                        } else {
                            alert(response.message);
                        }
                    } catch (e) {
                        window.location.href = 'checkout.php';
                    }
                }
            };
            xhr.send(formData);
        }
    }

    // Cart count update function (for header)
    function updateCartCount(count) {
        const cartBadge = document.getElementById('cart-badge');
        const cartLink = document.getElementById('cart-link');

        if (cartBadge && cartLink) {
            if (count > 0) {
                cartBadge.textContent = count;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }
        }
    }

    // Calculate totals based on checked items
    function updateTotals() {
        let subtotal = 0;
        let count = 0;

        // Get all checked checkboxes
        const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');

        checkedBoxes.forEach(cb => {
            const price = parseFloat(cb.dataset.price);
            const quantity = parseInt(cb.dataset.quantity);

            if (!isNaN(price) && !isNaN(quantity)) {
                subtotal += price * quantity;
                count += quantity;
            }
        });

        // Update summary display
        document.getElementById('summarySubtotal').innerText = "RM " + subtotal.toFixed(2);
        document.getElementById('summaryEstimated').innerText = "RM " + subtotal.toFixed(2);
        document.getElementById('summaryCount').innerText = count;

        // Update plural text
        const pluralSpan = document.getElementById('summaryItemsPlural');
        if (pluralSpan) {
            pluralSpan.textContent = count !== 1 ? 's' : '';
        }
    }

    // Update select all checkbox state
    function updateSelectAllState() {
        if (!selectAllCheckbox) return;

        const totalItems = itemCheckboxes.length;
        const checkedItems = document.querySelectorAll('.item-checkbox:checked').length;

        if (totalItems === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.disabled = true;
        } else if (checkedItems === totalItems) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedItems > 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }

    // Handle select all checkbox
    function handleSelectAll() {
        if (!selectAllCheckbox) return;

        const isChecked = selectAllCheckbox.checked;

        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });

        updateTotals();
    }

    // Initialize cart functionality
    function initializeCart() {
        // Get all item checkboxes
        itemCheckboxes = document.querySelectorAll('.item-checkbox');
        selectAllCheckbox = document.getElementById('selectAll');

        // Add event listeners to item checkboxes
        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateTotals();
                updateSelectAllState();
            });
        });

        // Add event listener to select all checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', handleSelectAll);
        }

        // Initialize totals (should be 0 since no checkboxes are checked by default)
        updateTotals();
        updateSelectAllState();
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', initializeCart);
</script>

<?php include '../_foot.php'; ?>