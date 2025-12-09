<?php
require '../_base.php';
require '../lib/db.php';

auth();
$user_id = $_user->user_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;

    if ($order_id) {
        // Check if order belongs to the user and is still shipped
        $stm_check = $_db->prepare("SELECT status FROM `order` WHERE order_id = ? AND user_id = ?");
        $stm_check->execute([$order_id, $user_id]);
        $order = $stm_check->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            die('Order not found or does not belong to you.');
        }

        if ($order['status'] !== 'delivered') {
            die('This order cannot be marked as received.');
        }

        // Update order status to 'delivered' and set delivered_at
        $stm_update = $_db->prepare("UPDATE `order` SET status = 'completed' WHERE order_id = ?");
        $stm_update->execute([$order_id]);

        // Generate new history ID
        $history_id = generateHistoryID($_db);

        // Insert into order_history
        $stm_hist = $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, message, changed_at, changed_by)
            VALUES (?, ?, 'delivered', ?, NOW(), ?)
        ");
        $stm_hist->execute([$history_id, $order_id, "Order received by user", $user_id]);

        header("Location: /page/order_history.php?msg=Order received successfully");
        exit;
    }
}

header("Location: /page/order_history.php");
exit;
?>
