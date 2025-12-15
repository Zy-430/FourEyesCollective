<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/PHPMailer.php';

if (is_post()) {
    $email = req('email');

    // Validate email
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else if (!is_exists($email, 'users', 'email')) {
        $_err['email'] = 'Not exists';
    }

    if (!$_err) {
        // Check if email exists in database
        $stm = $_db->prepare('SELECT * FROM users WHERE email = ?');
        $stm->execute([$email]);
        $user = $stm->fetch();

        if ($user) {
            // Generate unique token
            $token = sha1(uniqid() . rand());

            // Delete previous reset tokens
            $stm = $_db->prepare('DELETE FROM token WHERE user_id = ? AND type = "password_reset"');
            $stm->execute([$user->user_id]);

            // Insert new token
            $stm = $_db->prepare('
            INSERT INTO token (token_id, expire, user_id, type)
            VALUES(?, ADDTIME(NOW(), "00:05"), ?, "password_reset")
            ');
            $stm->execute([$token, $user->user_id]);

            $url = base("page/reset_password.php?token_id=$token");

            $m = get_mail();
            $m->addAddress($user->email, $user->name);
            if (file_exists("../photos/$user->photo")) {
                $m->addEmbeddedImage("../photos/$user->photo", 'photo');
            }
            $m->isHTML(true);
            $m->Subject = 'Reset Password';

            $m->Body =  "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                    #a_reset {color: #f9f9f9;}
                    .button { display: inline-block; background: #2c3e50; color: #ddd; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
                    .warning {padding-top: 10px; padding-bottom:10px; color:red; font-style:italic;}
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Four Eyes Collective</h2>
                        <h3>Password Reset Request</h3>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($user->name) . ",</p>
                        
                        <p>We received a request to reset your password for your Four Eyes Collective account.</p>
                        
                        <p>To reset your password, click the button below:</p>
                        
                        <p style='text-align: center;'>
                            <a href='$url' class='button' id='a_reset'>Reset Password</a>
                        </p>
                        
                        <p>Or copy and paste this link into your browser:</p>
                        <p><code>$url</code></p>
                        
                        <div class='warning'>
                            <p>* This password reset link will expire in 5 minutes. If you didn't request a password reset, please ignore this email. </p>
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

            // Send email
            try {
                if ($m->send()) {
                    temp('success', 'A password reset link has been sent to your email address. Please check your inbox (and spam folder).');
                    redirect('login.php'); 
                } else {
                    temp('error', 'Failed to send reset email. Please try again later.');
                }
            } catch (Exception $e) {
                temp('error', 'Error sending email: ' . $e->getMessage());
            }
        } else {
            temp('success', 'If your email exists in our system, you will receive a password reset link shortly.');
            redirect('login.php');
        }
    }
}

// ----------------------------------------------------------------------------
$_title = 'Forgot Password';
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
        .instructions {
            color: #666;
            font-size: 19px;
            line-height: 1.6;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-block {
            flex: 1;
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
                    <h1>Forgot Password</h1>
                </div>
            </div>
        </div>

        <form method="post" class="login-form">
            <div class="instructions">
                We'll send you a link to reset your password.
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" style="width:720px"
                    placeholder="your@email.com" maxlength="100"
                    value="<?= encode($GLOBALS['email'] ?? '') ?>" required>
                <br><?= err('email') ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-black btn-block">Send Reset Link</button>
                <button type="reset" class="btn btn-white btn-block">Clear</button>
            </div>

            <div class="links-container">
                <div class="forgot-link">
                    <a href="login.php">Login</a>
                </div>

                <div class="back-link">
                    <a href="/">Back to Home</a>
                </div>
            </div>

        </form>
    </div>
</body>

</html>