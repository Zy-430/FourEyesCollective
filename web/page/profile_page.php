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

                    <?php if ($_user->role === 'Member'): ?>
                        <a href="Member/profile_address_list.php" class="btn btn-gray">My Address</a>
                    <?php endif; ?>
                </div>


            </div>

        </div>

    </div>



</section>

<?php include '../_foot.php'; ?>