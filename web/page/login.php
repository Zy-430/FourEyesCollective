<?php
require '../_base.php';
require '../lib/db.php';

// Login attempts (max 3 times, if exceed then lock for 5 minute)
define('MAX_ATTEMPTS', 3);
define('LOCK_MINUTES', 5);

$__temp_success = temp('success');
$__temp_error = temp('error');
$__temp_info = temp('info');

if (is_post()) {

    $email    = req('email');
    $password = req('password');

    if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    }

    if (!$_err) {
        // Get user by email
        $stm = $_db->prepare("SELECT * FROM users WHERE email = ?");
        $stm->execute([$email]);
        $user = $stm->fetch();

        if ($user) {
            /* First check if lock has expired (if yes then reset the attempt) */
            if ($user->lock_until && strtotime($user->lock_until) <= time()) {
                $_db->prepare("
                    UPDATE users 
                    SET failed_attempts = 0, lock_until = NULL 
                    WHERE user_id = ?
            ")->execute([$user->user_id]);

                // Then reset values
                $user->failed_attempts = 0;
                $user->lock_until = null;
            }

            /* Then check account is currectly lock or not (mean the lock time haven't expired) */
            if ($user->lock_until && strtotime($user->lock_until) > time()) {

                /* Calculate the remaining lock time to show to user */
                $remainingSeconds = strtotime($user->lock_until) - time();
                $remainingMinutes = ceil($remainingSeconds / 60);

                $_err['email'] =
                    'Too many failed attempts. Please try again in ' . $remainingMinutes . ' minute(s).';
            } /* Else verify to email and password match or not */ elseif (password_verify($password, $user->password)) {
                // Login successfully then reset attempts
                $_db->prepare("
                    UPDATE users 
                    SET failed_attempts = 0, lock_until = NULL 
                    WHERE user_id = ?
                ")->execute([$user->user_id]);

                // Check active status (if pending then ask to activated account)
                if ($user->status === 'Pending') {
                    $_err['email'] = 'Your account is not activated ! <br> Please check your email for the verification link or
                         <a href="resend_verification.php" style="color: #1580ebff; font-size: 13px;">Resend verification email</a>';
                } elseif ($user->status === 'Blocked') {
                    $_err['email'] = 'Your account has been blocked by administrator. <br> Please contact support at <a href="mailto:support@foureyes.com" style="color: #1580ebff; font-size: 13px;">support@foureyes.com</a> for assistance.';
                } else {
                    // Check if user needs to change password (for first login)
                    if ($user->force_password_change == 1) {
                        // Store user in session ,  redirect to forced password change page
                        $_SESSION['temp_user'] = $user;
                        temp('info', 'Welcome! Please set your new password for first login.');
                        redirect('force_password_change.php');
                    } else {
                        temp('success', 'Login successfully!');
                        // Redirect by role
                        if ($user->role === 'Member') {
                            login($user, '/page/homepage.php');
                        } elseif ($user->role === 'Admin') {
                            login($user, '/page/Admin/admin_dashboard.php');
                        } else {
                            login($user, '/homepage.php');
                        }
                    }
                }
            }/* If wrong password then the attempt will keep increasing (max attempts = 3 and will lock for 5 minutes)*/ else {
                $attempts = $user->failed_attempts + 1;

                if ($attempts >= MAX_ATTEMPTS) {

                    $lockUntil = date(
                        'Y-m-d H:i:s',
                        strtotime('+' . LOCK_MINUTES . ' minutes')
                    );

                    $_db->prepare("
                        UPDATE users 
                        SET failed_attempts = ?, lock_until = ?
                        WHERE user_id = ?
                    ")->execute([$attempts, $lockUntil, $user->user_id]);

                    $_err['password'] =
                        'Too many attempts. Account locked for 5 minutes.';
                } else {
                    $_db->prepare("
                        UPDATE users 
                        SET failed_attempts = ?
                        WHERE user_id = ?
                    ")->execute([$attempts, $user->user_id]);

                    $_err['password'] =
                        'Invalid email or password. Attempt ' . $attempts . '/3';
                }
            }
        } else {
            $_err['password'] = 'Invalid email or password.';
        }
    }
}

// ----------------------------------------------------------------------------
$_title = 'Login | Four Eyes Collective';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_1.png">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/user.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/notifications.js"></script>
</head>

<body class="login-page">
    <!-- Show notificatioon -->
    <script>
        $(function() {
            <?php if (!empty($__temp_success)): ?>
                if (typeof showNotification === 'function') showNotification("<?= addslashes($__temp_success) ?>", 'success');
            <?php endif; ?>

            <?php if (!empty($__temp_error)): ?>
                if (typeof showNotification === 'function') showNotification("<?= addslashes($__temp_error) ?>", 'error');
            <?php endif; ?>

            <?php if (!empty($__temp_info)): ?>
                if (typeof showNotification === 'function') showNotification("<?= addslashes($__temp_info) ?>", 'info');
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
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account to continue</p>
                </div>
            </div>
        </div>

        <!-- Form content -->
        <form method="post" class="login-form">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com" maxlength="100" value="<?= encode($GLOBALS['email'] ?? '') ?>" autofocus required>
                <?= err('email') ?>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" maxlength="15" required>
                <?= err('password') ?>
            </div>

            <!-- Submit Buttons -->
            <div class="button-row">
                <button type="submit" class="btn btn-black">Sign In</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>
            <div class="links-container">
                <div class="forgot-link">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                <div class="back-link">
                    <a href="/">Back to Home</a>
                </div>
                <div class="signup-link">
                    Don't have an account? <a href="member_registration.php">Sign Up</a>
                </div>
            </div>
        </form>
    </div>
</body>

</html>