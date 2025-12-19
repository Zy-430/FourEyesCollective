<?php
require '../../_base.php';
require '../../lib/db.php';
require '../../lib/category.php';

header('Content-Type: application/json');

function is_ajax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

if (!is_post() || !is_ajax()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!$_user) {
    echo json_encode(['success' => false, 'message' => 'Please login to view your cart', 'cart_count' => 0]);
    exit;
}

$user_id = $_user->user_id;

function getCartCount($user_id, $db)
{
    $stm = $db->prepare("SELECT SUM(product_qty) as total FROM cart_item 
                        WHERE user_id = ? AND item_status = 'in_cart'");
    $stm->execute([$user_id]);
    $result = $stm->fetch();
    return $result->total ?? 0;
}

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
            echo json_encode(['success' => false, 'message' => 'Product not found', 'cart_count' => getCartCount($user_id, $_db)]);
            exit;
        }

        // Check stock
        if ($product->product_stock < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock', 'cart_count' => getCartCount($user_id, $_db)]);
            exit;
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

            echo json_encode(['success' => true, 'message' => 'Quantity updated in cart', 'cart_count' => getCartCount($user_id, $_db)]);
            exit;
        } else {
            // Insert new cart item
            $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(cart_item_id, 3) AS UNSIGNED)) as max_id FROM cart_item");
            $max_id = $stm->fetch()->max_id;
            $new_id = 'CI' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);

            $stm = $_db->prepare("INSERT INTO cart_item (cart_item_id, user_id, product_id, product_qty, item_status, created_at) VALUES (?, ?, ?, ?, 'in_cart', NOW())");
            $stm->execute([$new_id, $user_id, $product_id, $quantity]);

            echo json_encode(['success' => true, 'message' => 'Product added to cart successfully!', 'cart_count' => getCartCount($user_id, $_db)]);
            exit;
        }
        break;

    case 'update':
        $stm = $_db->prepare("UPDATE cart_item SET product_qty = ? WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'");
        $stm->execute([$quantity, $cart_item_id, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Quantity updated', 'cart_count' => getCartCount($user_id, $_db)]);
        exit;
        break;

    case 'remove':
        $stm = $_db->prepare("UPDATE cart_item SET item_status = 'abandoned', abandon_at = NOW() WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'");
        $stm->execute([$cart_item_id, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Item removed from cart', 'cart_count' => getCartCount($user_id, $_db)]);
        exit;
        break;

    case 'checkout':
        $selected_items = post('selected_items', []);
        if (!empty($selected_items)) {
            // Mark selected items as checkout
            foreach ($selected_items as $item_id) {
                $stm = $_db->prepare("\n                        UPDATE cart_item 
                        SET item_status = 'checkout', 
                            checkout_at = NOW(),
                            order_item_id = NULL  -- Ensure it's NULL
                        WHERE cart_item_id = ? AND user_id = ? AND item_status = 'in_cart'
                    ");
                $stm->execute([$item_id, $user_id]);
            }

            echo json_encode(['success' => true, 'message' => 'Items ready for checkout', 'redirect' => 'checkout.php']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Please select items to checkout']);
            exit;
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
}
