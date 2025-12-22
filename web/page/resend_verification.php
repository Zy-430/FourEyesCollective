<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/PHPMailer.php';
require '../lib/email_action.php';


if (is_post()) {
    $email = req('email');

    if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else {
        // Check user
        $stm = $_db->prepare(
            "SELECT status FROM users WHERE email = ?"
        );
        $stm->execute([$email]);
        $user = $stm->fetch();

        if (!$user) {
            $_err['email'] = 'Email not found';
        } elseif ($user->status !== 'Inactive') {
            $_err['email'] = 'Account is already activated';
        }
    }

    if (!$_err) {
        sendEmailAction($email, 'verification');

        temp(
            'success',
            'A verification email has been sent. Please check your inbox.'
        );
        redirect('login.php');
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
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/user.css">
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
                    <p>We'll send you a link to verify your account.</p>
                </div>
            </div>
        </div>

        <form method="post" class="login-form">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" style="width:96%;"
                    placeholder="your@email.com" maxlength="100"
                    value="<?= encode($GLOBALS['email'] ?? '') ?>" required>
                <?= err('email') ?>
            </div>

            <div class="form-row button-row" style="padding-top: 20px;">
                <button type="submit" class="btn btn-black">Send Verification Link</button>
                <button type="reset" class="btn btn-white">Clear</button>
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