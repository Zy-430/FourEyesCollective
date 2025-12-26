<?php
require '../_base.php';
require '../lib/db.php';

// Initialize variables
$error = '';
$success = false;
$valid_token = false;
$id = req('token_id');

// Validate token
if ($id) {
    $stm = $_db->prepare('
        SELECT u.* 
        FROM token t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.token_id = ? 
        AND t.expire > NOW()
        AND t.type = "password_reset"
    ');
    $stm->execute([$id]);
    $user = $stm->fetch();

    if ($user) {
        $valid_token = true;

        // Process password reset if form is submitted
        if (is_post()) {
            $password = req('password');
            $confirm_password = req('confirm_password');

            // Validate new password
            $password = trim($password);
            if (!is_strong_password($password)) {
                $_err['password'] =
                    'Invalid password format';
            }

            // Validate confirm password
            if ($password !== $confirm_password) {
                $_err['confirm_password'] = 'Passwords do not match. Please try again!';
            }

            // Password hashing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if (!$_err) {
                try {
                    $_db->beginTransaction();

                    $stm = $_db->prepare('
                    UPDATE users 
                    SET password = ?
                    WHERE user_id = ?
                ');
                    $stm->execute([$hashed_password, $user->user_id]);

                    // Delete the used token
                    $stm = $_db->prepare('DELETE FROM token WHERE token_id = ?');
                    $stm->execute([$id]);

                    $_db->commit();

                    $success = true;
                    $message = 'Your password has been reset successfully!';
                } catch (Exception $e) {
                    $_db->rollBack();
                    $error = 'An error occurred while resetting your password. Please try again.';
                }
            }
        }
    } else {
        $error = 'Invalid or expired reset link. Please request a new password reset.';
    }
} else {
    $error = 'Invalid reset link.';
}

// ----------------------------------------------------------------------------
$_title = 'Reset Password | Four Eyes Collective"';
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
    <style>
        .alert-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-info {
            background-color: #e8f4fd;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>

<body class="login-page">
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="header-content">
                <div class="logo-container">
                    <img src="/images/WIS_logo_white.png" alt="Four Eyes Collective Logo" class="header-logo">
                </div>
                <div class="header-text">
                    <h1>Reset Password</h1>
                    <p>Create a new password for your account</p>
                </div>
            </div>
        </div>

        <!-- Form content -->
        <div class="login-form">
            <!-- If the token not exist or expired -->
            <?php if ($error): ?>
                <!-- Error Message -->
                <div class="alert-message alert-error">
                    <strong>Invalid Reset Link </strong><br> <?= encode($error) ?>
                </div>

                <div class="button-row" style="padding-top: 20px;">
                    <button class="btn btn-black"><a href="forgot_password.php" style="color: white; text-decoration:none">Request New Reset Link</a></button>
                    <button class="btn btn-white"><a href="login.php" style="color: #2c3e50; text-decoration:none">Go to Login</a></button>
                </div>

            <!-- Successfully reset -->
            <?php elseif ($success): ?>
                <!-- Success Message -->
                <div class="alert-message alert-success">
                    <strong>Success!!! </strong> <?= encode($message) ?>
                </div>

                <div class="back-link">
                    <a href="login.php">Back to Login</a>
                </div>

            <!-- Valid token then show pasword reset form -->
            <?php elseif ($valid_token): ?>
                <!-- Reset Password Form -->
                <div class="alert-message alert-info">
                    <strong>Valid Reset Link</strong>
                    <p style="margin: 8px 0 0 0;">Please reset your password below.</p>
                </div>

                <small><i>Your password should be strong and unique, including uppercase, lowercase, number, and symbol.</i></small><br><br>

                <!-- Form content -->
                <form method="post">
                    <div class="form-group">
                        <label for="password">New Password *</label>
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="Enter new password (8-15 characters)" maxlength="15" autofocus required
                            autocomplete="new-password">
                        <?= err('password') ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                            placeholder="Re-enter new password" maxlength="15" required
                            autocomplete="new-password">
                        <?= err('confirm_password') ?>
                    </div>

                    <div class="button-row">
                        <button type="submit" class="btn btn-black">Reset Password</button>
                        <a href="login.php" class="btn btn-white" style="text-decoration: none; text-align: center; line-height: normal;">Cancel</a>
                    </div>
                </form>

                <div class="signup-link">
                    Remember your password? <a href="login.php">Sign in here</a>
                </div>

            <!-- Invalid State -->
            <?php else: ?>
                <div class="alert-message alert-error">
                    <strong>Invalid Request</strong>
                    <p>Unable to process password reset request.</p>
                </div>

                <div class="links-container">
                    <div class="forgot-link">
                        <a href="forgot_password.php">Request New Reset Link</a>
                    </div>

                    <div class="back-link">
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
