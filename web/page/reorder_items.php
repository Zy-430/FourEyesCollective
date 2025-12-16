<?php
require '../_base.php';
require '../lib/db.php';
require 'restore_cart.php';

auth();

$order_id = post('order_id');
$action = post('action');

if ($action !== 'reorder' || !$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Verify user owns this order
$stm = $_db->prepare("SELECT * FROM `order` WHERE order_id = ? AND user_id = ?");
$stm->execute([$order_id, $_user->user_id]);
$order = $stm->fetch(PDO::FETCH_OBJ);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Restore items to cart (creates new cart items)
$result = restoreOrderItemsToCart($_db, $_user->user_id, $order_id);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Items added to cart successfully',
        'cart_count' => $result['total_quantity'],
        'items_added' => $result['total_items']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add items to cart: ' . $result['error']
    ]);
}
?>