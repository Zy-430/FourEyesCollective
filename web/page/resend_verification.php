<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/PHPMailer.php';

$success = false;
$message = '';
$email = req('email');

if (is_post()) {
    $email = req('email');

    // Validate email
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    }

    if (!$_err) {
        // Check if user exists and is inactive
        $stm = $_db->prepare('SELECT * FROM users WHERE email = ? AND status = "Inactive"');
        $stm->execute([$email]);
        $user = $stm->fetch();

        if ($user) {
            // Delete any existing verification tokens for this user
            $stm = $_db->prepare('DELETE FROM token WHERE user_id = ? AND type = "verification"');
            $stm->execute([$user->user_id]);

            // Generate new verification token
            $verification_token = sha1(uniqid() . rand());

            // Store new verification token
            $stm = $_db->prepare('
                INSERT INTO token (token_id, expire, user_id, type)
                VALUES(?, ADDTIME(NOW(), "24:00"), ?, "verification")
            ');
            $stm->execute([$verification_token, $user->user_id]);

            // Send verification email
            $verification_url = base("page/verify_account.php?token_id=$verification_token");

            $m = get_mail();
            $m->addAddress($email, $user->name);
            $m->isHTML(true);
            $m->Subject = 'Verify Your Account - Four Eyes Collective';

            $m->Body =  "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                    .button { display: inline-block; background: #2c3e50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Four Eyes Collective</h2>
                        <h3>Account Verification</h3>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($user->name) . ",</p>
                        
                        <p>We received a request to resend your verification email.</p>
                        
                        <p>To activate your account, please click the button below:</p>
                        
                        <p style='text-align: center;'>
                            <a href='$verification_url' class='button'>Verify Account</a>
                        </p>
                        
                        <p>Or copy and paste this link into your browser:</p>
                        <p><code>$verification_url</code></p>
                        
                        <div class='warning'>
                            <p><strong>Important:</strong></p>
                            <p>This verification link will expire in 24 hours.</p>
                            <p>If you didn't request this email, please ignore it.</p>
                        </div>
                        
                        <p>Best regards,<br>
                        <strong>The Four Eyes Collective Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message, please do not reply to this email.</p>
                        <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            try {
                if ($m->send()) {
                    $success = true;
                    $message = 'A new verification link has been sent to your email address. Please check your inbox (and spam folder).';
                } else {
                    $message = 'Failed to send verification email. Please try again later.';
                }
            } catch (Exception $e) {
                $message = "Error sending email: " . $e->getMessage();
            }
        } else {
            $success = true;
            $message = 'If your email exists and account is not verified, you will receive a verification link shortly.';
        }
    }
}

// ----------------------------------------------------------------------------
$_title = 'Resend Verification Email';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_1.png">
    <link rel="stylesheet" href="/css/user.css">
    <style>
        body.login-page {
            height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            padding: 15px;
            margin: 0;
        }

        .success-message {
            background-color: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #a7f3d0;
        }

        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
    </style>
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <div class="logo-container">
                    <img src="/images/WIS_logo_white.png" alt="Four Eyes Collective Logo" class="header-logo">
                </div>
                <div class="header-text">
                    <h1>Resend Verification Email</h1>
                </div>
            </div>
        </div>

        <form method="post" class="login-form">
            <?php if ($success && $message): ?>
                <div class="success-message">
                    <?= encode($message) ?>
                </div>
            <?php elseif ($message && !$success): ?>
                <div class="error-message">
                    <?= encode($message) ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control"
                    placeholder="your@email.com" maxlength="100"
                    value="<?= encode($email) ?>" required>
                <?= err('email') ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-black btn-block">Resend Verification Email</button>
            </div>

            <div class="back-link" style="padding-top: 15px;">
                <a href="/">Back to Home</a>
            </div>
        </form>
    </div>
</body>

</html>