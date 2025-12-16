<?php
require '../_base.php';
require '../lib/db.php';

auth();
$admin_id = $_user->user_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $message = trim($_POST['message']);

    // Get current order info
    $stm = $_db->prepare("SELECT status FROM `order` WHERE order_id = ?");
    $stm->execute([$order_id]);
    $order = $stm->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $current_status = $order['status'];

        // Only update the order table if status changed
        if ($new_status !== $current_status) {
            // Validate allowed transitions
            $allowed = false;
            if ($current_status == 'pending' && in_array($new_status, ['shipped', 'cancelled'])) $allowed = true;
            if ($current_status == 'shipped' && $new_status == 'delivered') $allowed = true;

            if (!$allowed) {
                $error_msg = "Invalid status change from $current_status → $new_status.";
                goto skip_history; // Skip updating history
            }

            // Update order status
            if ($new_status === 'delivered') {
                $update = $_db->prepare("UPDATE `order` SET status = ?, delivered_at = NOW() WHERE order_id = ?");
            } else {
                $update = $_db->prepare("UPDATE `order` SET status = ? WHERE order_id = ?");
            }
            $update->execute([$new_status, $order_id]);
        }

        // Generate new history ID
        $history_id = generateHistoryID($_db);

        // Insert into order_history
        $history = $_db->prepare("
            INSERT INTO order_history(history_id, order_id, status, changed_by, message, changed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $history->execute([
            $history_id,
            $order_id,
            $new_status,
            $admin_id,
            $message
        ]);

        $success_msg = "Order $order_id updated successfully.";

        skip_history:;
    }
}

// Fetch filter
$status_filter = $_GET['status'] ?? 'all';

// Fetch orders
$query = "SELECT * FROM `order`";
$params = [];

if ($status_filter !== 'all') {
    $query .= " WHERE status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY order_date DESC";

$stm = $_db->prepare($query);
$stm->execute($params);
$orders = $stm->fetchAll(PDO::FETCH_ASSOC);

$_title = "Admin Orders | Four Eyes Collective";
include '../_head.php';
?>

<section style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 80px 0; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 3em; margin-bottom: 10px; color: #ffffff; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Admin Order Management</h1>
        <p style="font-size: 1.2em; opacity: 0.9; color: #f0f0f0; text-shadow: 1px 1px 2px rgba(0,0,0,0.4);">
            Manage customer orders and update order statuses
        </p>
    </div>
</section>

<div style="max-width: 1100px; margin: 40px auto;">

    <?php if (!empty($success_msg)): ?>
        <div style="padding:12px; background:#2ecc71; color:white; border-radius:5px; margin-bottom:20px;">
            <?= $success_msg ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="padding:12px; background:#e74c3c; color:white; border-radius:5px; margin-bottom:20px;">
            <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <!-- Filter -->
    <form method="GET" style="margin-bottom:20px; display:flex; gap:10px;">
        <select name="status" style="padding:8px; border-radius:5px; border:1px solid #ccc;">
            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All</option>
            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
            <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        <button type="submit" style="padding:8px 15px; background:#2c3e50; color:white; border:none; border-radius:5px;">
            Filter
        </button>
    </form>

    <!-- Order Cards -->
    <?php foreach ($orders as $o): ?>
        <div style="background:white; border-radius:8px; padding:20px; margin-bottom:25px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

            <h3 style="margin-top:0;">Order ID: <?= $o['order_id'] ?></h3>
            <p><strong>Date:</strong> <?= date('d M Y H:i', strtotime($o['order_date'])) ?></p>
            <p><strong>Total:</strong> RM <?= number_format($o['total_amount'], 2) ?></p>
            <p><strong>Current Status:</strong> <span style="color:#e67e22; font-weight:bold;"><?= ucfirst($o['status']) ?></span></p>

            <hr style="margin:20px 0;">

            <!-- Status Update Form -->
            <form method="POST" style="display:flex; flex-direction:column; gap:12px;">

                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">

                <label><strong>Change Status:</strong></label>
                <select name="new_status" required style="padding:8px; border-radius:5px;">
                    <option value="<?= $o['status'] ?>" selected>No Change (<?= ucfirst($o['status']) ?>)</option>

                    <?php if ($o['status'] == 'pending'): ?>
                        <option value="shipped">Shipped</option>
                        <option value="cancelled">Cancel</option>
                    <?php elseif ($o['status'] == 'shipped'): ?>
                        <option value="delivered">Delivered</option>
                    <?php else: ?>
                        <option disabled>No further actions</option>
                    <?php endif; ?>
                </select>

                <textarea name="message" placeholder="Enter message for history..."
                    style="padding:10px; border-radius:5px; min-height:80px;"></textarea>

                <button type="submit"
                    style="padding:10px 15px; background:#27ae60; color:white; border:none; border-radius:5px; width:150px;">
                    Update
                </button>
            </form>

        </div>
    <?php endforeach; ?>

</div>

<?php include '../_foot.php'; ?>