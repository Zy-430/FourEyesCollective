<?php
require '../_base.php';
require '../lib/db.php';

define('MAX_ATTEMPTS', 3);
define('LOCK_MINUTES', 15);

$temp_message = temp('success');

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

            /* Check temporary lock */
            if ($user->lock_until && strtotime($user->lock_until) > time()) {
                $remainingSeconds = strtotime($user->lock_until) - time();
                $remainingMinutes = ceil($remainingSeconds / 60);

                $_err['email'] =
                    'Too many failed attempts. Please try again in ' .
                    $remainingMinutes . ' minute(s).';
            }/* Correct password */ 
            elseif ($user->password === sha1($password)) {

                // Then will reset attempts
                $_db->prepare("
                    UPDATE users 
                    SET failed_attempts = 0, lock_until = NULL 
                    WHERE user_id = ?
                ")->execute([$user->user_id]);

                // Check active status (if inactive then ask to activated account)
                if ($user->status === 'Inactive') {
                    $_err['email'] = 'Your account is not activated!';
                } else {

                    temp('info', 'Login successfully!');

                    // Redirect by role
                    if ($user->role === 'Member') {
                        login($user, '/page/homepage.php');
                    } elseif ($user->role === 'Admin') {
                        login($user, '/page/admin_dashboard.php');
                    } else {
                        login($user, '/homepage.php');
                    }
                }
            }/* Wrong password - attempt keep increasing (max = 3 , then will lock for 15 minutes)*/ 
            else {
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
                        'Too many attempts. Account locked for 15 minutes.';
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
$_title = 'Login';
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
</head>

<body class="login-page">

    <?php if ($temp_message): ?>
        <div class="temp-message" style="position: fixed; top: 100px; left: 50%; transform: translateX(-50%); background: #11c35bff; color: white; padding: 15px 30px; border-radius: 4px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation:fadeInDrop 0.5s ease-out forwards">
            <?= encode($temp_message) ?>
        </div>

        <script>
            // Disappear after 6 seconds
            setTimeout(function() {
                var msg = document.querySelector('.temp-message');
                if (msg) msg.style.display = 'none';
            }, 6000);
        </script>
    <?php endif; ?>

    <div class="login-container">
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

        <form method="post" class="login-form">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com" maxlength="100" value="<?= encode($GLOBALS['email'] ?? '') ?>" required>
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