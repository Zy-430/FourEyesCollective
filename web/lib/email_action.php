<?php
require_once __DIR__ . '/../_base.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/PHPMailer.php';


// For account verification(after member registration) - send verification link to email to activate account (with token type "verification")
// For reset password - send email with reset link (with token type "password_reset")
// For admin created admin - send email with default password 
// For admin created member - send email with default password and verification link (with token type "verification")
function sendEmailAction(string $email, string $type, array $data = [])
{
    global $_db;

    if (!is_email($email)) {
        return false;
    }

    $stm = $_db->prepare("SELECT * FROM users WHERE email = ?");
    $stm->execute([$email]);
    $user = $stm->fetch();

    // If user not found then return
    if (!$user) return false;

    // Verification requires user with "pending" status
    if ($type === 'verification') {
        if ($user->role !== 'Member' || $user->status !== 'Pending') return false;
    }

    // For 'staff_created_member' and 'admin_welcome' types
    if ($type === 'staff_created_member' || $type === 'admin_welcome') {
        $m = get_mail();
        $m->addAddress($user->email, $user->name);
        $m->isHTML(true);

        switch ($type) {
            case 'staff_created_member':
                $password = $data['password'] ?? '';
                $verification_url = $data['verification_url'] ?? '';

                $m->Subject = 'Verify Your Account - Four Eyes Collective';
                $m->Body = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                            .button { display: inline-block; background: #2c3e50; color: #ddd; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
                            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
                            .warning {padding-top: 10px; padding-bottom:10px; color:red; font-style:italic;}
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
                                <p>A new account has been created for you at Four Eyes Collective by our staff.</p>
                                <p>Here is your temporary login credentials:</p>
                                <ul>
                                    <li><strong>Email   :</strong> {$user->email}</li>
                                    <li><strong>Password:</strong> $password</li>
                                </ul>
                                <p>Please activate your account and set your own password using the link below:</p>
                                <p style='text-align: center;'>
                                    <a href='$verification_url' class='button' style='color:white;'>Verify Account</a>
                                </p>
                                <p>You may also use the link below:</p>
                                <p><code>$verification_url</code></p>
                                <div class='warning'>
                                    <p>* This link will expire in 24 hours.</p>
                                </div>
                                <p>Best regards,<br>
                                <strong>The Four Eyes Collective Team</strong></p>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                </html>
                ";
                break;

            case 'admin_welcome':
                $password = $data['password'] ?? '';

                $m->Subject = 'Admin Account - Four Eyes Collective';
                $m->Body = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; color:#333; }
                            .container { max-width:600px; margin:auto; }
                            .header { background:#0d1a3f; color:white; padding:20px; text-align:center; }
                            .content { background:#f9f9f9; padding:30px; }
                            .button { background:#0d1a3f; color:#ddd; padding:12px 24px; text-decoration:none; border-radius:6px; }
                            .warning { color:red; font-style:italic; }
                            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Four Eyes Collective</h2>
                                <h3>Admin Account</h3>
                            </div>
                            <div class='content'>
                                <p>Hello <strong>" . htmlspecialchars($user->name) . "</strong>,</p>
                                <p>An administrator account has been created for you.</p>
                                <p>Here is your login credentials:</p>
                                <ul>
                                    <li><strong>Email   :</strong> {$user->email}</li>
                                    <li><strong>Password:</strong> $password</li>
                                </ul>
                                <p>Your account is already <strong>active</strong>.</p>
                                <p>Please login and change your password immediately:</p>
                                <p style='text-align: center;'>
                                    <a href='" . base("page/login.php") . "' class='button' style='color:white;'>Login Now</a>
                                </p>
                                <p>You may also use the link below:</p>
                                <p><code><a href='" . base("page/login.php") . "'>" . base("page/login.php") . "</a></code></p>
                                <p>Best regards,<br>
                                <strong>Four Eyes Collective System Administration</strong></p>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                </html>
                ";
                break;
        }

        try {
            return $m->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    // For 'verification' (after member registration) and 'password_reset'types - will create token
    // Remove previous tokens
    $stm = $_db->prepare("DELETE FROM token WHERE user_id = ? AND type = ?");
    $stm->execute([$user->user_id, $type]);

    // Create token
    $token = sha1(uniqid() . rand());
    $expire = $type === 'verification' ? '24:00' : '00:05';

    $stm = $_db->prepare("
        INSERT INTO token (token_id, expire, user_id, type)
        VALUES (?, ADDTIME(NOW(), ?), ?, ?)
    ");
    $stm->execute([$token, $expire, $user->user_id, $type]);

    // URL
    $url = $type === 'verification'
        ? base("page/activate_account.php?token_id=$token")
        : base("page/reset_password.php?token_id=$token");

    $m = get_mail();
    $m->addAddress($user->email, $user->name);
    $m->isHTML(true);

    switch ($type) {
        case 'verification':

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
                    #a_verify {color: #f9f9f9;}
                    .button { display: inline-block; background: #2c3e50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; text-align: center;}
                    .warning {padding-top: 10px; padding-bottom:10px; color:red; font-style:italic;}
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
                        
                        <p>Thank you for registering with Four Eyes Collective!</p>
                        
                        <p>To activate your account, please click the button below:</p>
                        
                        <p style='text-align: center;'>
                            <a href='$url' class='button' id='a_verify'>Verify Account</a>
                        </p>
                        
                        <p>You may also use the link below:</p>
                        <p><code>$url</code></p>
                        
                        <div class='warning'>
                        <p>* This link will expire in 24 hours. If you didn't create an account with us, please ignore it.</p>
                        </div>
                        
                        <p>Best regards,<br>
                        <strong>The Four Eyes Collective Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            break;

        case 'password_reset':

            $m->Subject = 'Reset Password - Four Eyes Collective';

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
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; text-align: center; }
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
                        
                        <p>You may also use the link below:</p>
                        <p><code>$url</code></p>
                        
                        <div class='warning'>
                            <p>* This password reset link will expire in 5 minutes. If you didn't request a password reset, please ignore this email. </p>
                        </div>
                        
                        <p>Best regards,<br>
                        <strong>The Four Eyes Collective Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            break;
    }
    try {
        return $m->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
