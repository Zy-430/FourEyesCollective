<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');
$user_id = $_user->user_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $reason = $_POST['cancelled_reason'] ?? '';

    if ($order_id && $reason) {
        // Check if order belongs to the user and is still pending
        $stm_check = $_db->prepare("SELECT status FROM `order` WHERE order_id = ? AND user_id = ?");
        $stm_check->execute([$order_id, $user_id]);
        $order = $stm_check->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            die('Order not found or does not belong to you.');
        }

        if ($order['status'] !== 'pending') {
            die('This order cannot be cancelled.');
        }

        // Update order status to 'cancelled'
        $stm_update = $_db->prepare("UPDATE `order` SET status = 'cancelled' WHERE order_id = ?");
        $stm_update->execute([$order_id]);

        $history_id = generateHistoryID($_db);

        // Insert into order_history with user_id as changed_by
        $stm_hist = $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, message, changed_at, changed_by)
            VALUES (?, ?, 'cancelled', ?, NOW(), ?)
        ");
        $stm_hist->execute([$history_id, $order_id, "Order cancelled by user: $reason", $user_id]);

        header("Location: /page/Member/order_history.php?msg=Order cancelled successfully");
        exit;
    }
}
header("Location: /page/Member/order_history.php");
exit;