<?php
require '../_base.php';
require '../lib/db.php';

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
    // Verify ownership & current status
    $stm = $_db->prepare("SELECT order_id, status, user_id FROM `order` WHERE order_id = ? AND user_id = ?");
    $stm->execute([$order_id, $_user->user_id]);
    $order = $stm->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied']);
        exit;
    }

    // Only allow return requests for appropriate statuses
    $allowedStatuses = ['delivered', 'completed'];
    if (!in_array($order['status'], $allowedStatuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Return request not allowed for current order status']);
        exit;
    }

    // Check if a return request already exists for this order
    $chk = $_db->prepare("SELECT COUNT(*) FROM order_history WHERE order_id = ? AND status = 'return_requested'");
    $chk->execute([$order_id]);
    if ($chk->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'A return request has already been submitted for this order']);
        exit;
    }

    // Begin transaction
    $_db->beginTransaction();

    // Update order status to return_requested
    $upd = $_db->prepare("UPDATE `order` SET status = 'return_requested' WHERE order_id = ?");
    $upd->execute([$order_id]);

    // Insert history record
    // Assume order_history has (history_id, order_id, status, changed_by, message, changed_at)
    $history_id = bin2hex(random_bytes(8));
    $ins = $_db->prepare("
        INSERT INTO order_history (history_id, order_id, status, changed_by, message, changed_at)
        VALUES (?, ?, 'return_requested', ?, ?, NOW())
    ");
    $ins->execute([$history_id, $order_id, $_user->user_id, $reason]);

    $_db->commit();

    echo json_encode(['status' => 'success', 'message' => 'Return request submitted']);
    exit;

} catch (Exception $e) {
    if ($_db->inTransaction()) $_db->rollBack();
    http_response_code(500);
    error_log('Return request error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error while submitting return request']);
    exit;
}
