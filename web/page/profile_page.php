<?php
require '../_base.php';
require '../lib/db.php';

// TEMPORARY: simulate login for testing
$_SESSION['user_id'] = 'ME0001';

// Check login
if (!isset($_SESSION['user_id'])) {
    //header("Location: login.php");
    //exit();
}

$user_id = $_SESSION['user_id'];

// Fetch data
$stmt = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

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
include '../_head.php';

?>

<!-- Profile Page Wrapper -->
<section style="
    background: #ecf0f1;
    padding: 60px 0;
    font-family: 'Poppins', sans-serif;
">
    <div style="
        max-width: 900px;
        background: white;
        margin: 0 auto;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0px 4px 20px rgba(0,0,0,0.1);
    ">
        
        <!-- Page Title -->
        <h1 style="
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2.5em;
            margin-bottom: 30px;
            color: #2c3e50;
        ">My Profile</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="padding:15px; margin-bottom:20px; background:#27ae60; color:white; border-radius:4px; text-align:center;">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); // remove after showing ?>
        <?php endif; ?>

        <!-- Profile Top Section: Photo + Basic Info -->
        <div style="
            display: flex;
            gap: 40px;
            align-items: center;
            margin-bottom: 40px;
            justify-content: center;
        ">
            <!-- Profile Photo -->
            <img src="<?= $photoPath ?>" 
                style="width:150px; height:150px; border-radius:50%; object-fit:cover; border:4px solid #ecf0f1;">

            <!-- Basic Info -->
            <div>
                <h2 style="margin:0; font-size:1.8em; color:#2c3e50;">
                    <?= encode($user->name) ?>
                </h2>
                <p style="margin-top:8px; color:#7f8c8d;">
                    <?= encode($user->email) ?>
                </p>
            </div>
        </div>

        <!-- Profile Details (Grid) -->
        <div style="
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        ">

            <div>
                <p style="font-weight:600; color:#34495e;">Gender</p>
                <p><?= encode($user->gender) ?></p>
            </div>

            <div>
                <p style="font-weight:600; color:#34495e;">Phone</p>
                <p>
                    <?php 
                        $phone = $user->phone;
                        if (!empty($phone)){
                            echo '+60 ' .encode($phone);
                        }else{
                            echo 'Not set';
                        }
                    ?>
                </p>
            </div>

            <div>
                <p style="font-weight:600; color:#34495e;">Date of Birth</p>
                <p><?= strtoupper(date('M-d-Y', strtotime($user->date_of_birth))) ?></p>
            </div>

            <div>
                <p style="font-weight:600; color:#34495e;">Registration Date</p>
                <p><?= strtoupper(date('M-d-Y', strtotime($user->registration_date))) ?></p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="text-align:center; margin-top: 20px;">
            <a href="profile_edit.php" class="cta-button"
                style="padding: 10px 25px; background:#2c3e50; color:white; text-decoration:none; border-radius:6px; margin-right:10px;">
                Edit Profile
            </a>

            <a href="profile_change_photo.php" class="cta-button"
                style="padding: 10px 25px; background:#34495e; color:white; text-decoration:none; border-radius:6px; margin-right:10px;">
                Change Photo
            </a>

            <a href="profile_change_password.php" class="cta-button"
                style="padding: 10px 25px; background:#e74c3c; color:white; text-decoration:none; border-radius:6px; margin-right:10px;">
                Change Password
            </a>

            <a href="profile_address_list.php" class="cta-button"
                style="padding: 10px 25px; background:#7f8c8d; color:white; text-decoration:none; border-radius:6px;">
                My Address
            </a>
        </div>

    </div>
</section>

<?php include '../_foot.php'; ?>
