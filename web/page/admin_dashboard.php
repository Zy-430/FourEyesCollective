<?php require '../_base.php';
require '../lib/db.php';
$_title = 'Admin Dashboard';
include '../_admin_head.php';

auth('Admin');
$user_id = $_user->user_id;

// Fetch data 
$stmt = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

$user = $stmt->fetch();
if (!$user) {
    echo "User not found.";
    exit();
}

$member = $_db->query("SELECT COUNT(*) as total FROM users WHERE role = 'Member'")->fetch()->total;
$orders = $_db->query("SELECT COUNT(*) AS total FROM `order`")->fetch()->total;
$products = $_db->query("SELECT COUNT(*) as total FROM product")->fetch()->total;

?>
<!-- Show statistic cards -->
<div class="admin-content">
    <h1 class="dashboard-title">Welcome Back, <?= $user->name ?> </h1>
    <p class="dashboard-subtitle">Manage your eyewear store from here.</p>
    <div class="admin-card-container">
        <div class="admin-card">
            <div class="card-title">Total Member</div>
            <div class="card-value"><?= $member ?></div>
        </div>
        <div class="admin-card">
            <div class="card-title">Total Orders</div>
            <div class="card-value"><?= $orders ?></div>
        </div>
        <div class="admin-card">
            <div class="card-title">Total Products</div>
            <div class="card-value"><?= $products ?></div>
        </div>
    </div>
</div>
</body>

</html>