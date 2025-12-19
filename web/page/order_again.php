<?php
require '../_base.php';
require '../lib/db.php';

auth('Member');
header('Content-Type: application/json');

$order_id = $_POST['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['status'=>'error','message'=>'Invalid order']);
    exit;
}

// Verify ownership
$stm = $_db->prepare("SELECT user_id FROM `order` WHERE order_id=? AND user_id=?");
$stm->execute([$order_id, $_user->user_id]);
$order = $stm->fetch();

if (!$order) {
    echo json_encode(['status'=>'error','message'=>'Order not found']);
    exit;
}

// Get order items
$stm = $_db->prepare("SELECT product_id, product_qty FROM order_item WHERE order_id=?");
$stm->execute([$order_id]);
$items = $stm->fetchAll(PDO::FETCH_ASSOC);

if (!$items) {
    echo json_encode(['status'=>'error','message'=>'No items in this order']);
    exit;
}

try {
    // Insert items into cart_item table
    $stm = $_db->prepare("
        INSERT INTO cart_item (cart_item_id, user_id, product_id, product_qty, item_status, created_at) 
        VALUES (?, ?, ?, ?, 'in_cart', NOW())
    ");

    foreach ($items as $item) {
        // Generate new cart item ID
        $stm_max = $_db->query("SELECT MAX(CAST(SUBSTRING(cart_item_id, 3) AS UNSIGNED)) as max_id FROM cart_item");
        $max_id = $stm_max->fetch()->max_id ?? 0;
        $new_id = 'CI' . str_pad($max_id + 1, 4, '0', STR_PAD_LEFT);

        $stm->execute([
            $new_id,
            $_user->user_id,
            $item['product_id'],
            $item['product_qty']
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'redirect' => '/page/cart.php'
    ]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
