<?php
require '../../_base.php';
require '../../lib/db.php';
require '../../lib/category.php';

auth('Member');

$user_id = $_user->user_id;
$source = get('source', 'user_cancel');

// Check if this is a beacon API request (browser navigation)
$isBeaconRequest = is_post() && post('cancel_reason') === 'browser_navigation';
// Detect AJAX requests
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$_db->beginTransaction();

try {
    // Get all checkout items for this user that are locked for checkout
    $stm = $_db->prepare("
        SELECT ci.*, p.product_name 
        FROM cart_item ci 
        JOIN product p ON ci.product_id = p.product_id
        WHERE ci.user_id = ? 
          AND ci.item_status = 'checkout' 
          AND ci.order_item_id IS NULL
    ");
    $stm->execute([$user_id]);
    $checkout_items = $stm->fetchAll(PDO::FETCH_OBJ);

    if (empty($checkout_items)) {
        // No items to cancel - redirect to cart
        if ($isBeaconRequest) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No checkout items']);
            exit;
        }
        temp('info', 'No checkout items to cancel.');
        redirect('cart.php');
    }

    $item_count = 0;
    $total_quantity = 0;
    
    foreach ($checkout_items as $item) {
        // Update existing checkout item to abandoned
        $_db->prepare("
            UPDATE cart_item 
            SET item_status = 'abandoned', 
                abandon_at = NOW(), 
                checkout_at = NULL
            WHERE cart_item_id = ?
        ")->execute([$item->cart_item_id]);

        // Generate new cart_item_id for restored item
        $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(cart_item_id, 3) AS UNSIGNED)) as max_id FROM cart_item");
        $max_id = $stm->fetch()->max_id;
        $new_cart_item_id = 'CI' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);

        // Insert restored cart item
        $_db->prepare("
            INSERT INTO cart_item (cart_item_id, user_id, product_id, product_qty, item_status, created_at)
            VALUES (?, ?, ?, ?, 'in_cart', NOW())
        ")->execute([$new_cart_item_id, $user_id, $item->product_id, $item->product_qty]);
        
        $item_count++;
        $total_quantity += $item->product_qty;
    }

    $_db->commit();

    // If this is a beacon request (from beforeunload), just return JSON
    if ($isBeaconRequest) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Checkout cancelled via browser navigation',
            'item_count' => $item_count
        ]);
        exit;
    }

    // If this is an AJAX request, return JSON so client can show unified notification
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Checkout cancelled',
            'item_count' => $item_count
        ]);
        exit;
    }

    // Regular request - show success message and redirect
    $message = "Checkout cancelled. $item_count item(s) returned to cart.";
    if ($source === 'browser_back') {
        $message = "Checkout cancelled (browser back). $item_count item(s) returned to cart.";
    }
    
    temp('success', $message);
    
    // Set session flag for cart update
    $_SESSION['cart_updated'] = time();
    
    redirect('cart.php');

} catch (Exception $ex) {
    $_db->rollBack();
    
    if ($isBeaconRequest) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error cancelling checkout: ' . $ex->getMessage()
        ]);
        exit;
    }
    
    temp('error', 'Error cancelling checkout: ' . $ex->getMessage());
    redirect('cart.php');
}