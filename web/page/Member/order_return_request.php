<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');
header('Content-Type: application/json');

$order_id = $_POST['order_id'] ?? null;
$reason   = trim($_POST['reason'] ?? '');

if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order']);
    exit;
}

if ($reason === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a reason for return']);
    exit;
}

try {
    // Verify ownership, status & delivered date
    $stm = $_db->prepare("
        SELECT order_id, status, user_id, delivered_at
        FROM `order`
        WHERE order_id = ? AND user_id = ?
    ");
    $stm->execute([$order_id, $_user->user_id]);
    $order = $stm->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied']);
        exit;
    }

    // Only completed orders can be returned
    if ($order['status'] !== 'completed') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Only completed orders can be returned'
        ]);
        exit;
    }

    // Must have delivered_at date
    if (empty($order['delivered_at'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Return is not available for this order'
        ]);
        exit;
    }

    // 30-day return window check
    $deliveredDate = new DateTime($order['delivered_at']);
    $today = new DateTime();

    $daysPassed = $deliveredDate->diff($today)->days;

    if ($daysPassed > 30) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Return period has expired (30 days limit)'
        ]);
        exit;
    }

    // Prevent duplicate return request
    $chk = $_db->prepare("
        SELECT COUNT(*) 
        FROM order_history 
        WHERE order_id = ? AND status = 'return_requested'
    ");
    $chk->execute([$order_id]);

    if ($chk->fetchColumn() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'A return request has already been submitted for this order'
        ]);
        exit;
    }

    // Begin transaction
    $_db->beginTransaction();

    // Update order status to 'return_requested'
    // Actual return processing and restocking occurs later when admin approves
    $upd = $_db->prepare("
        UPDATE `order`
        SET status = 'return_requested'
        WHERE order_id = ?
    ");
    $upd->execute([$order_id]);

    // Insert order history
    $history_id = generateHistoryID($_db);

    $ins = $_db->prepare("
        INSERT INTO order_history 
        (history_id, order_id, status, changed_by, message, changed_at)
        VALUES (?, ?, 'return_requested', ?, ?, NOW())
    ");
    $ins->execute([
        $history_id,
        $order_id,
        $_user->user_id,
        $reason
    ]);

    $_db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Return request submitted successfully'
    ]);
    exit;
} catch (Exception $e) {
    if ($_db->inTransaction()) {
        $_db->rollBack();
    }

    error_log('Return request error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error while submitting return request'
    ]);
    exit;
}
