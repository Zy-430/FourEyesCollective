<?php
require '../_base.php';
require '../lib/db.php';

$__temp_info = temp('info');

// For user created by admin (which with default password, so need to reset their password when first time login)
// Check if user is in temp session (first login after admin creation)
if (!isset($_SESSION['temp_user'])) {
    // If no temp user, check if logged in user needs password change
    if ($_user && $_user->force_password_change == 1) {
        $temp_user = $_user;
    } else {
        // If not then redirect to login
        redirect('login.php');
    }
} else {
    $temp_user = $_SESSION['temp_user'];
}

// Form submission
if (is_post()) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate passwords
    if ($new_password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match. Please try again!';
    } elseif (!is_strong_password($new_password)) {
        $_err['password'] =
            'Password must be at least 8 characters and include uppercase, lowercase, number and symbol';
    } else {
        // Hash password 
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Begin transaction
        $_db->beginTransaction();

        try {
            // Update password and reset the force_password_change flag to 0
            $stmt = $_db->prepare("
                UPDATE users 
                SET password = ?, force_password_change = 0 
                WHERE user_id = ?
            ");
            $stmt->execute([$hashed_password, $temp_user->user_id]);
            $_db->commit();

            // Get updated user data
            $stm = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
            $stm->execute([$temp_user->user_id]);
            $updated_user = $stm->fetch();

            // Clear temporary user and log the user in, then redirect based on role
            unset($_SESSION['temp_user']);

            if ($updated_user->role === 'Member') {
                temp('success', 'Login successfully!');
                login($updated_user, 'homepage.php?msg=password_changed');
            } elseif ($updated_user->role === 'Admin') {
                temp('success', 'Login successfully!');
                login($updated_user, 'Admin/admin_dashboard.php?msg=password_changed');
            } else {
                login($updated_user, '/');
            }
        } catch (Exception $e) {
            $_db->rollBack();
            temp('error', "An error occurred. Please try again.");
        }
    }
}

// ----------------------------------------------------------------------
$_title = "Set Your Password | Four Eyes Collective";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_white.png">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/user.css">
    <script src="/js/notifications.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>

<body class="login-page">
    <script>
        $(function() {
            <?php if (!empty($__temp_info)): ?>
                if (typeof showNotification === 'function') {
                    showNotification("<?= addslashes($__temp_info) ?>", 'info');
                }
            <?php endif; ?>

        });
    </script>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="header-content">
                <div class="logo-container">
                    <img src="/images/WIS_logo_white.png" alt="Four Eyes Collective Logo" class="header-logo">
                </div>
                <div class="header-text">
                    <h1>Welcome, <?= htmlspecialchars($temp_user->name ?? 'New User') ?>!</h1>
                    <p>This is your first login. For security reasons, please create your own password to continue.</p>
                </div>
            </div>
        </div>

        <!-- Form content-->
        <form method="post" class="login-form">
            <small><i>Your password should be strong and unique, including uppercase, lowercase, number, and symbol.</i></small><br><br>

            <div class="form-group">
                <label>New Password *</label>
                <input type="password" name="new_password" class="form-control" required
                    placeholder="Enter your new password" maxlength="15">
                <?= err('password') ?>
            </div>

            <div class="form-group">
                <label>Confirm New Password *</label>
                <input type="password" name="confirm_password" class="form-control" required
                    placeholder="Confirm your new password">
                <?= err('confirm_password') ?>
            </div>

            <br>
            <!-- Submit Buttons -->
            <div class="button-row">
                <button type="submit" class="btn btn-black">Set Password & Continue</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>
        </form>
    </div>
</body>

</html>
