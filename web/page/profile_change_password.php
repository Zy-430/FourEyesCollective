<?php
require '../_base.php';
require '../lib/db.php';

auth('Admin', 'Member');

$user_id = $_user->user_id;

// Server-side strong password check
function isStrongPassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/', $password);
}

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
    } elseif (!isStrongPassword($new_password)) {
        $_SESSION['error'] = "Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.";
    } elseif (password_verify($new_password, $user->password)) {
        $_SESSION['error'] = "New password cannot be the same as your current password.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
       $_db->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE user_id = ?")
    ->execute([$hashed, $user_id]);

        $_SESSION['success'] = "Password changed successfully.";
        redirect('profile_page.php'); // redirect only on success
    }
}

$_title = "Change Password | Four Eyes Collective";
$_css = ['profile.css'];
include '../_head.php';
?>

<section class="profile-section">
    <div class="profile-card narrow-card">
        <h1 class="centered-title">Change Password</h1>

        <form method="post" id="changePasswordForm">
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

    </div>
</section>

<?php include '../_foot.php'; ?>
<script src="/js/notifications.js"></script>

<script>
$(function() {
    // Show notifications from PHP session
    <?php if (!empty($_SESSION['error'])): ?>
        showNotification("<?= addslashes($_SESSION['error']) ?>", "error");
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        showNotification("<?= addslashes($_SESSION['success']) ?>", "success");
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    // Client-side live validation for new password
    function isStrongPasswordJS(password) {
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/.test(password);
    }

    $('#changePasswordForm input[name="new_password"]').on('input', function() {
        const val = $(this).val();
        if (!isStrongPasswordJS(val)) {
            showNotification(
                "Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.",
                "error"
            );
        }
    });

    // Confirm password check
    $('#changePasswordForm input[name="confirm_password"]').on('input', function() {
        const newPass = $('#changePasswordForm input[name="new_password"]').val();
        const confirmPass = $(this).val();
        if (confirmPass !== newPass) {
            showNotification("Confirm password does not match new password.", "error");
        }
    });
});
</script>
