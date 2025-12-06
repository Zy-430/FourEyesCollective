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

// Handle form submission
if (is_post()) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch current hashed password from DB
    $stmt = $_db->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "User not found.";
    } elseif (sha1($current_password) !== $user->password) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "New password must be at least 6 characters long.";
    } else {
        // Update password
        $hashed = sha1($new_password);
        $_db->prepare("UPDATE users SET password = ? WHERE user_id = ?")
            ->execute([$hashed, $user_id]);

        $_SESSION['success'] = "Password changed successfully.";
        redirect('profile_page.php'); // back to profile after success
    }
}

$_title = "Change Password | Four Eyes Collective";
include '../_head.php';
?>

<section style="padding:60px 0; background:#ecf0f1;">
<div style="max-width:500px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
    <h1 style="text-align:center; margin-bottom:30px;">Change Password</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div style="padding:15px; margin-bottom:20px; background:#27ae60; color:white; border-radius:4px; text-align:center;">
            <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="padding:15px; margin-bottom:20px; background:#e74c3c; color:white; border-radius:4px; text-align:center;">
            <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Current Password *</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label>New Password *</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Confirm New Password *</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <div class="form-group" style="margin-top:20px;">
            <button type="submit" class="cta-button" style="width:100%;">Change Password</button>
        </div>
    </form>

    <div style="text-align:center; margin-top:20px;">
        <a href="profile_page.php" class="cta-button" style="padding:10px 25px; width:100%; background:#34495e; color:white; border-radius:6px; text-decoration:none;">Back to Profile</a>
    </div>
</div>
</section>

<?php include '../_foot.php'; ?>
