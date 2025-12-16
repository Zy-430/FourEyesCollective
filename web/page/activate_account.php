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
$_title = 'Account Verification';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_white.png">
    <link rel="stylesheet" href="/css/app.css">
    <style>
        body {
            background: white;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .verification-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }
        
        .header-logo {
            width: 180px;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .message {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .action-button {
            display: inline-block;
            padding: 12px 30px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .action-button:hover {
            background: #34495e;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <img src="/images/WIS_logo_2.png" alt="Logo" class="header-logo">
        <h1>Account Verification</h1>
        
        <div class="message <?= $success ? 'success' : 'error' ?>">
            <?= encode($message) ?>
        </div>
        
        <?php if ($success): ?>
            <a href="login.php" class="action-button">Go to Login</a>
        <?php else: ?>
            <a href="forgot_password.php?action=resend_verification" class="action-button">Resend Verification Email</a>
            <a href="login.php" class="action-button" style="background: #6c757d; margin-left: 10px;">Go to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>