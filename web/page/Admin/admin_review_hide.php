<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request');
}

$order_item_id = $_POST['order_item_id'] ?? null;

if (!$order_item_id) {
    http_response_code(400);
    exit('Invalid review');
}

try {
    // Check review exists
    $stm = $_db->prepare("
        SELECT order_item_id, review_status 
        FROM order_item
        WHERE order_item_id = ?
    ");
    $stm->execute([$order_item_id]);
    $review = $stm->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        http_response_code(404);
        exit('Review not found');
    }

    // Toggle logic (hide <-> unhide)
    $newStatus = ($review['review_status'] === 'hidden') ? 'visible' : 'hidden';

    $upd = $_db->prepare("
        UPDATE order_item
        SET review_status = ?
        WHERE order_item_id = ?
    ");
    $upd->execute([$newStatus, $order_item_id]);

    echo json_encode([
        'status' => 'success',
        'review_status' => $newStatus
    ]);
    exit;
} catch (Exception $e) {
    error_log('Admin review hide error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error'
    ]);
    exit;
}
