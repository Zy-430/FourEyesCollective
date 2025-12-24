<?php
require '../_base.php';
require '../lib/db.php';

auth('Admin', 'Member');

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
// Determine which header/footer and CSS to use based on role
if ($_user->role === 'Admin') {
    include '../_admin_head.php'; // Admin header
} else {
    $_css = ['profile.css'];
    include '../_head.php'; // Member header
}
?>

<section class="profile-page">
    <div class="profile-container">
        <h1 class="profile-title">My Profile</h1>

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
                        <p><?= !empty($user->phone) ? '+60 ' . encode($user->phone) : 'Not set'; ?></p>
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

                    <?php if ($_user->role === 'Member'): ?>
                        <a href="Member/profile_address_list.php" class="btn btn-gray">My Address</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- jQuery & notification.js -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/js/notifications.js"></script>
<script>
$(function() {
    <?php if (!empty($_SESSION['success'])): ?>
        showNotification("<?= addslashes($_SESSION['success']) ?>", "success");
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        showNotification("<?= addslashes($_SESSION['error']) ?>", "error");
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
});
</script>
<?php 
// Conditionally include footer based on role
if ($_user->role === 'Admin') {
    // Admin pages don't have a footer file, just close the HTML
    echo '</body></html>';
} else {
    include '../_foot.php'; // Member footer
}
?>