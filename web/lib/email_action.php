<?php
require_once '../_base.php';
require '../lib/db.php';
require_once '../lib/PHPMailer.php';


// Use for account verification and password reset (with token)
// For account verification - when user is pending , it will send link to email to activate account
// For reset password - it will send link to email to let user reset their password 
function sendEmailAction(string $email, string $type)
{
    global $_db;

    if (!is_email($email)) {
        return;
    }

    $stm = $_db->prepare("SELECT * FROM users WHERE email = ?");
    $stm->execute([$email]);
    $user = $stm->fetch();

    // If user not found then return
    if (!$user) return;

    // Verification requires user with "pending" status
    if ($type === 'verification') {
        if ($user->role !== 'Member' || $user->status !== 'Pending') return;
    }

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



    if ($type === 'verification') {

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
    }

    if ($type === 'password_reset') {

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
    }

    $m->send();
}