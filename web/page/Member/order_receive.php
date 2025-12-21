<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');
$user_id = $_user->user_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;

    if ($order_id) {
        // --- Check if order belongs to the user ---
        $stm_check = $_db->prepare("SELECT status FROM `order` WHERE order_id = ? AND user_id = ?");
        $stm_check->execute([$order_id, $user_id]);
        $order = $stm_check->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            die('Order not found or does not belong to you.');
        }

        if ($order['status'] !== 'delivered') {
            die('This order cannot be marked as received.');
        }

        // --- Update order status to 'completed' ---
        $stm_update = $_db->prepare("UPDATE `order` SET status = 'completed' WHERE order_id = ?");
        $stm_update->execute([$order_id]);

        // --- Insert into order_history ---
        $history_id = generateHistoryID($_db);
        $stm_hist = $_db->prepare("
            INSERT INTO order_history (history_id, order_id, status, message, changed_at, changed_by)
            VALUES (?, ?, 'completed', ?, NOW(), ?)
        ");
        $stm_hist->execute([$history_id, $order_id, "Order received by user", $user_id]);

        // --- Generate Receipt ID if not exists ---
        $stm_check_receipt = $_db->prepare("SELECT receipt_id FROM receipt WHERE order_id = ?");
        $stm_check_receipt->execute([$order_id]);
        $existing_receipt = $stm_check_receipt->fetch(PDO::FETCH_ASSOC);

        if (!$existing_receipt) {
            // Generate new receipt ID (ER0001, ER0002, etc.)
            $last_num = (int)$_db->query("SELECT IFNULL(MAX(CAST(SUBSTRING(receipt_id, 3) AS UNSIGNED)), 0) FROM receipt")->fetchColumn();
            $receipt_id = 'ER' . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);

            // Insert receipt record
            $stm_insert_receipt = $_db->prepare("
                INSERT INTO receipt (receipt_id, order_id, issued_to, delivery_method, email_sent, pdf_generated)
                VALUES (?, ?, ?, 'none', 0, 0)
            ");
            $stm_insert_receipt->execute([$receipt_id, $order_id, $user_id]);
        }

        // --- Redirect with success message ---
        header("Location: /page/Member/order_history.php?msg=Order received successfully");
        exit;
    }
}

// --- Redirect if no POST or order_id ---
header("Location: /page/Member/order_history.php");
exit;
