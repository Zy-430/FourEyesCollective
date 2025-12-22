<?php
require '../../_base.php';
require '../../lib/db.php';

header('Content-Type: application/json');

if (!$_user) {
    echo json_encode(['cart_count' => 0]);
    exit;
}

$user_id = $_user->user_id;

$stm = $_db->prepare("SELECT SUM(product_qty) as total FROM cart_item 
                     WHERE user_id = ? AND item_status = 'in_cart' AND order_item_id IS NULL");
$stm->execute([$user_id]);
$result = $stm->fetch();

echo json_encode([
    'cart_count' => $result->total ?? 0
]);