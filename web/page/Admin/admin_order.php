<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
require_once '../../lib/SimplePager.php';

auth('Admin');
$admin_id = $_user->user_id;

// --- Auto-complete delivered orders older than 3 days ---
$autoCompleteStmt = $_db->prepare("
    SELECT order_id FROM `order`
    WHERE status = 'delivered'
      AND delivered_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
");
$autoCompleteStmt->execute();
$deliveredOrders = $autoCompleteStmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($deliveredOrders as $order_id) {
    $updateStmt = $_db->prepare("UPDATE `order` SET status = 'completed' WHERE order_id = ?");
    $updateStmt->execute([$order_id]);

    $history_id = generateHistoryID($_db);
    $historyStmt = $_db->prepare("
        INSERT INTO order_history(history_id, order_id, status, changed_by, message, changed_at)
        VALUES (?, ?, 'completed', ?, 'Auto-completed after 3 days of delivery', NOW())
    ");
    $historyStmt->execute([$history_id, $order_id, $admin_id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id   = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $message    = trim($_POST['message']);

    $stm = $_db->prepare("SELECT status FROM `order` WHERE order_id = ?");
    $stm->execute([$order_id]);
    $order = $stm->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $current_status = $order['status'];
        $allowed = false;

        if ($current_status == 'pending' && in_array($new_status, ['shipped', 'cancelled'])) $allowed = true;
        if ($current_status == 'shipped' && $new_status == 'delivered') $allowed = true;
        if ($current_status == 'return_requested' && in_array($new_status, ['returned', 'return_rejected'])) $allowed = true;

        if ($allowed) {
            $_db->beginTransaction();
            try {
                // Update order status
                if ($new_status === 'delivered') {
                    $update = $_db->prepare("UPDATE `order` SET status = ?, delivered_at = NOW() WHERE order_id = ?");
                } else {
                    $update = $_db->prepare("UPDATE `order` SET status = ? WHERE order_id = ?");
                }
                $update->execute([$new_status, $order_id]);

                // Restock products if return approved
                if ($new_status === 'returned') {
                    $stm_items = $_db->prepare("SELECT product_id, product_qty FROM order_item WHERE order_id = ?");
                    $stm_items->execute([$order_id]);
                    $items = $stm_items->fetchAll(PDO::FETCH_ASSOC);

                    $stm_update_stock = $_db->prepare("UPDATE product SET product_stock = product_stock + ? WHERE product_id = ?");
                    foreach ($items as $item) {
                        $stm_update_stock->execute([$item['product_qty'], $item['product_id']]);
                    }
                }

                // Insert order history
                $history_id = generateHistoryID($_db);
                $history = $_db->prepare("
                    INSERT INTO order_history(history_id, order_id, status, changed_by, message, changed_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $history->execute([$history_id, $order_id, $new_status, $admin_id, $message]);

                $_db->commit();
                $success_msg = "Order $order_id updated successfully.";
            } catch (Exception $e) {
                $_db->rollBack();
                $error_msg = "Error updating order: " . $e->getMessage();
            }
        } else {
            $error_msg = "Invalid status change from $current_status → $new_status.";
        }
    }
}

// --- SimplePager Implementation ---
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

// Sorting
$currentSort = $_GET['sort'] ?? 'order_date';
$currentDir  = $_GET['dir'] ?? 'desc';

$allowedSorts = [
    'order_id'      => 'o.order_id',
    'customer_name' => 'u.name',
    'customer_email' => 'u.email',
    'order_date'    => 'o.order_date',
    'total_amount'  => 'o.total_amount',
    'status'        => 'o.status'
];

if (!array_key_exists($currentSort, $allowedSorts)) $currentSort = 'order_date';
if (!in_array(strtolower($currentDir), ['asc', 'desc'])) $currentDir = 'desc';

$orderBy = "ORDER BY {$allowedSorts[$currentSort]} $currentDir";

// Build base query
$where = [];
$params = [];

if ($status_filter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($search !== '') {
    $where[] = "(
        o.order_id LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.status LIKE ?
    )";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$baseSql = "
    SELECT o.*, u.name AS customer_name, u.email AS customer_email
    FROM `order` o
    JOIN users u ON o.user_id = u.user_id
    $whereSql
    $orderBy
";

// Initialize SimplePager
$page = max(1, (int)($_GET['page'] ?? 1));
$p = new SimplePager($baseSql, $params, 4, $page); // 4 orders per page
$orders = $p->result;

// Build query string for pagination links
$query_params = [];

if ($search !== '') {
    $query_params[] = "search=" . urlencode($search);
}

if ($status_filter !== 'all') {
    $query_params[] = "status=" . urlencode($status_filter);
}

$query_params[] = "sort=" . urlencode($currentSort);
$query_params[] = "dir=" . urlencode($currentDir);

$query_string = implode('&', $query_params);

$_title = "Admin Orders | Four Eyes Collective";
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Order Management </h1>

        <?php
        function sortLink($column, $label, $currentSort, $currentDir, $search, $status_filter)
        {
            $dir = 'asc';
            $class = '';

            if ($currentSort === $column) {
                if ($currentDir === 'asc') {
                    $class = 'asc';   
                    $dir = 'desc';
                } else {
                    $class = 'desc';  
                    $dir = 'asc';
                }
            }

            $url = "?page=1"
                . "&search=" . urlencode($search)
                . "&status=" . urlencode($status_filter)
                . "&sort={$column}"
                . "&dir={$dir}";

            return "<a href='{$url}' class='{$class}'>{$label}</a>";
        }

        ?>

        <?php if (!empty($success_msg)): ?>
            <div style="padding:12px; background:#2ecc71; color:white; border-radius:5px; margin-bottom:20px; text-align:center;">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="padding:12px; background:#e74c3c; color:white; border-radius:5px; margin-bottom:20px; text-align:center;">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>
        <div class="header-actions small">
            <form method="GET" style="margin-top:10px; display:flex; gap:10px; align-items:center;">
                <!-- Preserve sorting and page -->
                <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                <input type="hidden" name="page" value="1">
                
                <!-- Search input -->
                <input type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by Order ID, Name, Email, Status"
                    style="padding:8px; width:300px; border-radius:5px; border:1px solid #ccc;">

                <!-- Status filter -->
                <select name="status" style="padding:8px; border-radius:5px; border:1px solid #ccc;">
                    <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="returned" <?= $status_filter == 'returned' ? 'selected' : '' ?>>Returned</option>
                    <option value="return_requested" <?= $status_filter == 'return_requested' ? 'selected' : '' ?>>Pending Approval</option>
                </select>

                <!-- Submit button -->
                <button type="submit"
                    class="btn-default btn-add">
                    <i class="fas fa-filter"></i>Filter
                </button>

                <!-- Clear button -->
                <button type="button" class="btn-default btn-clear" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>'">
                    <i class="fas fa-eraser"></i> Clear
                </button>
            </form>
        </div>
    </div>
    <div class="table-container">

        <!-- Orders Table -->
        <div class="table-container">
            <table class="table table-small">
                <thead>
                    <tr>
                        <th><?= sortLink('order_id', 'Order ID', $currentSort, $currentDir, $search, $status_filter) ?></th>
                        <th><?= sortLink('customer_name', 'Customer', $currentSort, $currentDir, $search, $status_filter) ?></th>
                        <th><?= sortLink('customer_email', 'Email', $currentSort, $currentDir, $search, $status_filter) ?></th>
                        <th><?= sortLink('order_date', 'Date', $currentSort, $currentDir, $search, $status_filter) ?></th>
                        <th><?= sortLink('total_amount', 'Total', $currentSort, $currentDir, $search, $status_filter) ?></th>
                        <th><?= sortLink('status', 'Status', $currentSort, $currentDir, $search, $status_filter) ?></th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr style="border-bottom:1px solid #ddd; height:80px;">
                            <td style="padding:10px;"><?= $o->order_id ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($o->customer_name) ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($o->customer_email) ?></td>
                            <td style="padding:10px;"><?= date('d M Y H:i', strtotime($o->order_date)) ?></td>
                            <td style="padding:10px;">RM <?= number_format($o->total_amount, 2) ?></td>
                            <td style="padding:10px; font-weight:bold; color:#e67e22;"><?= ucfirst($o->status) ?></td>
                            <td style="padding:10px; text-align:center; display:flex; gap:5px; justify-content:center;">
                                <!-- View Details Button -->
                                <form method="GET" action="../order_details.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $o->order_id ?>">
                                    <button type="submit" style="background:none; border:none; cursor:pointer;">
                                        <img src="../../images/icons/view.png" alt="View" style="width:22px;">
                                    </button>
                                </form>

                                <?php if ($o->status === 'return_requested'): ?>
                                    <!-- Return Approval Dropdown -->
                                    <form method="POST" class="return-approval-form" style="display:flex; flex-direction:column; gap:6px; width:150px;">
                                        <input type="hidden" name="order_id" value="<?= $o->order_id ?>">
                                        <select name="new_status" class="return-status-select" style="padding:6px; font-size:13px; border-radius:5px;">
                                            <option value="">-- Select Action --</option>
                                            <option value="returned">Approve Return</option>
                                            <option value="return_rejected">Reject Return</option>
                                        </select>
                                        <input type="text" name="message" placeholder="Optional note" style="padding:6px; font-size:13px; border-radius:5px;">
                                        <button type="submit" style="padding:6px; background:#3498db; color:white; border:none; border-radius:5px; cursor:pointer;">Submit</button>
                                    </form>
                                <?php elseif (!in_array($o->status, ['completed', 'cancelled', 'delivered', 'returned', 'return_rejected'])): ?>
                                    <!-- Regular Status Change -->
                                    <form method="POST" class="status-update-form" style="display:flex; flex-direction:column; gap:6px; width:150px;">
                                        <input type="hidden" name="order_id" value="<?= $o->order_id ?>">
                                        <select name="new_status" class="status-select" style="padding:6px; font-size:13px; border-radius:5px;">
                                            <option value="<?= $o->status ?>" selected>No Change</option>
                                            <?php if ($o->status == 'pending'): ?>
                                                <option value="shipped">Shipped</option>
                                                <option value="cancelled">Cancel</option>
                                            <?php elseif ($o->status == 'shipped'): ?>
                                                <option value="delivered">Delivered</option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="text" name="message" placeholder="History note" required style="padding:6px; font-size:13px; border-radius:5px;">
                                        <button type="submit" class="status-submit-btn" style="padding:6px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer; display:none;">Submit</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Show Invoice only if status is completed -->
                                <?php if ($o->status === 'completed'): ?>
                                    <form method="GET" action="../order_invoice.php" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $o->order_id ?>">
                                        <button type="submit" style="background:none; border:none; cursor:pointer;">
                                            <img src="../../images/icons/invoice.png" alt="Invoice" style="width:22px;">
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($p->page_count > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?= (($page - 1) * 5) + 1 ?> - <?= min($page * 5, $p->item_count) ?> of <?= $p->item_count ?> orders
                </div>
                <div class="pagination">
                    <?= $p->html($query_string) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {

            // Handle regular status change forms
            $('.status-update-form').on('change', '.status-select', function() {

                var $form = $(this).closest('.status-update-form');
                var $submitBtn = $form.find('.status-submit-btn');
                var currentStatus = $form.find('option[selected]').val();
                var selectedStatus = $(this).val();

                if (selectedStatus !== currentStatus && selectedStatus !== '') {
                    $submitBtn.show();
                } else {
                    $submitBtn.hide();
                }
            });

            // Handle return approval form submission
            $('.return-approval-form').on('submit', function(e) {

                var newStatus = $(this).find('.return-status-select').val();

                if (newStatus === '') {
                    e.preventDefault();
                    alert('Please select an action');
                    return false;
                }

                if (!confirm('Are you sure you want to update this order status?')) {
                    e.preventDefault();
                    return false;
                }
            });

            // Handle regular status update form submission
            $('.status-update-form').on('submit', function(e) {

                if (!confirm('Are you sure you want to update this order status?')) {
                    e.preventDefault();
                    return false;
                }
            });

        });
    </script>

</div>
</body>

</html>