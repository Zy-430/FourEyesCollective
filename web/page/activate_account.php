<?php
require '../_base.php';
require '../lib/db.php';

$message = '';
$success = false;
$token = req('token_id');

if ($token) {
    // Check if token exists and is a verification token)
    $stm = $_db->prepare('
        SELECT u.*, t.type 
        FROM token t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.token_id = ? 
        AND t.expire > NOW()
        AND t.type = "verification"
    ');
    $stm->execute([$token]);
    $result = $stm->fetch();

    if ($result) {
        // Check if account is already active
        if ($result->status === 'Active') {
            $message = 'Your account is already verified. You can now login.';
            $success = true;
        } else {
            // Activate the account
            $_db->beginTransaction();

            try {
                // Update user status to Active
                $stm = $_db->prepare('
                    UPDATE users 
                    SET status = "Active"
                    WHERE user_id = ?
                ');
                $stm->execute([$result->user_id]);

                // Delete the used verification token
                $stm = $_db->prepare('
                    DELETE FROM token 
                    WHERE token_id = ?
                ');
                $stm->execute([$token]);

                $_db->commit();

                $message = 'Your account has been successfully verified! You can now login.';
                $success = true;
            } catch (Exception $e) {
                $_db->rollBack();
                $message = 'An error occurred during verification. Please try again.';
            }
        }
    } else {
        $message = 'Invalid or expired verification link. Please request a new verification email.';
    }
} else {
    $message = 'Invalid verification link.';
}

// ----------------------------------------------------------------------------
$_title = 'Account Verification | Four Eyes Collective';
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
</head>

<body class="login-page">
    <div class="verification-container">
        <div class="logo-container">
            <img src="/images/WIS_logo_2.png" alt="Logo" style="width: 180px;margin-bottom: 10px !important;opacity: 0.8;" class="verification-icon">
        </div>
        <h1>Account Verification</h1>
        <div class="message <?= $success ? 'success' : 'error' ?>">
            <?= encode($message) ?>
        </div>

        <div class="button-row">
            <?php if ($success): ?>
                <button class="btn btn-black"><a href="login.php" style="color: white; text-decoration:none">Go to Login</a></button>
            <?php else: ?>
                <button class="btn btn-black"><a href="resend_verification.php" style="color: white; text-decoration:none">Resend Verification Email</a></button>
                <button class="btn btn-white"><a href="login.php" style="color: #2c3e50; text-decoration:none">Go to Login</a></button>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>