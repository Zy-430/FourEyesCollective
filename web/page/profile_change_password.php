<?php
require '../_base.php';
require '../lib/db.php';

auth('Admin', 'Member');

$user_id = $_user->user_id;

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
    } elseif (!password_verify($current_password, $user->password)) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "New password must be at least 6 characters long.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $_db->prepare("UPDATE users SET password = ? WHERE user_id = ?")
            ->execute([$hashed, $user_id]);

        $_SESSION['success'] = "Password changed successfully.";
        redirect('profile_page.php');
    }
}

$_title = "Change Password | Four Eyes Collective";
$_css = ['profile.css'];
include '../_head.php';
?>

<section class="profile-section">
    <div class="profile-card narrow-card">
        <h1 class="centered-title">Change Password</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
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

            <div class="form-group">
                <button type="submit" class="cta-button full-width">Change Password</button>
            </div>
        </form>

        <div class="center-actions">
            <a href="profile_page.php" class="cta-button secondary full-width">
                Back to Profile
            </a>
        </div>


        <div class="forgot-link">
            <a href="forgot_password.php">Forgot password?</a>
        </div>
    </div>
</section>

<?php include '../_foot.php'; ?>