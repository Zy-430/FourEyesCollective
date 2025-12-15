<?php
require '../_base.php';
require '../lib/db.php';

auth();

$user_id = $_user->user_id;

// Fetch data
$stmt = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch top 3 recent orders for the user
$stm_orders = $_db->prepare("
    SELECT order_id, total_amount, status, order_date
    FROM `order`
    WHERE user_id = ?
    ORDER BY order_date DESC
    LIMIT 3
");
$stm_orders->execute([$user_id]);
$recent_orders = $stm_orders->fetchAll(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit();
}

// Profile photo path
$photoPath = "../images/users/" . $user->photo;
if (empty($user->photo) || !file_exists($photoPath)) {
    $photoPath = "../images/default.jpg";
}

$_title = "My Profile | Four Eyes Collective";
$_css = ['profile.css'];
include '../_head.php';

?>

<section class="profile-page">

    <div class="profile-container">

        <h1 class="profile-title">My Profile</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="profile-success">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="profile-layout">

            <!-- Left -->
            <div class="profile-left">
                <img src="<?= $photoPath ?>" class="profile-photo">

                <a href="profile_change_photo.php" class="btn btn-photo">
                    Change Photo
                </a>
            </div>

            <!-- Right -->
            <div class="profile-right">

                <h2><?= encode($user->name) ?></h2>
                <p class="profile-email"><?= encode($user->email) ?></p>

                <div class="profile-info">
                    <div>
                        <p>Gender</p>
                        <p><?= encode($user->gender) ?></p>
                    </div>

                    <div>
                        <p>Phone</p>
                        <p>
                            <?= !empty($user->phone) ? '+60 ' . encode($user->phone) : 'Not set'; ?>
                        </p>
                    </div>

                    <div>
                        <p>Date of Birth</p>
                        <p><?= strtoupper(date('M d, Y', strtotime($user->date_of_birth))) ?></p>
                    </div>

                    <div>
                        <p>Registration Date</p>
                        <p><?= strtoupper(date('M d, Y', strtotime($user->registration_date))) ?></p>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="profile_edit.php" class="btn btn-dark">Edit Profile</a>
                    <a href="profile_change_password.php" class="btn btn-danger">Change Password</a>
                    <a href="profile_address_list.php" class="btn btn-gray">My Address</a>
                </div>

            </div>

        </div>

    </div>



</section>

<section class="profile-recent-orders">
    <h2>Recent Orders</h2>

    <?php if (empty($recent_orders)): ?>
        <p>No recent orders.</p>
    <?php else: ?>
        <div class="recent-orders-list" style="display:flex; flex-direction:column; gap:15px;">
            <?php foreach ($recent_orders as $order): ?>
                <div class="recent-order-card" 
                     onclick="window.location='/page/order_details.php?order_id=<?= $order['order_id'] ?>'"
                     style="position:relative; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.08); background:#fff; overflow:hidden; cursor:pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    
                    <!-- Status Badge Top Right -->
                    <span class="order-status <?= $order['status'] ?: 'default' ?>" 
                          style="position:absolute; top:10px; right:10px; padding:5px 10px; border-radius:4px; font-weight:600; font-size:0.85em;">
                        <?= ucfirst($order['status']) ?>
                    </span>

                    <!-- Order Info -->
                    <div style="padding:15px;">
                        <p style="margin:0; font-weight:600;">Order #<?= $order['order_id'] ?></p>
                        <p style="margin:0; color:#7f8c8d; font-size:0.9em;"><?= date('d M Y', strtotime($order['order_date'])) ?></p>
                    </div>

                    <!-- Order Summary Bar -->
                    <div style="
                        display:flex; 
                        justify-content:space-between; 
                        align-items:center; 
                        padding:15px; 
                        background:#f8f8f8; 
                        font-weight:600; 
                        font-size:0.95em; 
                        border-top:1px solid #e0e0e0;
                    ">
                        <?php
                        // Count total items in this order
                        $stm_count = $_db->prepare("SELECT SUM(product_qty) AS total_qty FROM order_item WHERE order_id = ?");
                        $stm_count->execute([$order['order_id']]);
                        $total_qty = $stm_count->fetchColumn();
                        ?>
                        <span><?= $total_qty ?> item(s)</span>
                        <span>RM <?= number_format($order['total_amount'], 2) ?></span>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <a href="/page/order_history.php" class="btn btn-dark view-orders-btn" style="margin-top:15px; display:inline-block;">View All Orders →</a>
</section>

<?php include '../_foot.php'; ?>