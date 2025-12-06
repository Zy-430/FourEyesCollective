<?php
require '../_base.php';
require '../lib/db.php';

if (is_post()) {
    $email    = req('email');
    $password = req('password');

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email';
    }

    // Validate: password
    if ($password == '') {
        $_err['password'] = 'Required';
    }

    // Login user
    if (!$_err) {
        $stm = $_db->prepare('
            SELECT * FROM users
            WHERE email = ? AND password = SHA1(?)
        ');
        $stm->execute([$email, $password]);
        $u = $stm->fetch();

        if ($u) {
            // Check if user is active
            // if ($u->status === 'inactive') {
            //     $_err['email'] = 'Account not verified. Please check your email for verification link. <a href="resend_verification.php?email=' . urlencode($email) . '">Resend verification email</a>';
            // } else {
                temp('info', 'Login successfully');
                login($u);
                // Redirect based on role
                if ($u->role === 'admin' || $u->role === 'staff') {
                    redirect('/homepage.php');
                } else {
                    redirect('/homepage.php');
                }
            //}
        } else {
            $_err['password'] = 'Invalid email or password';
        }
    }}

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
    <link rel="stylesheet" href="/css/login.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>

<body class="login-page">
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
            <!-- Display error message if exists -->
            <?php if (isset($_err['email']) && strpos($_err['email'], 'Invalid email or password') !== false): ?>
                <div class="alert alert-error">
                    Invalid email or password. Please try again.
                </div>
            <!-- <?php elseif (isset($_err['email']) && strpos($_err['email'], 'Account is inactive') !== false): ?>
                <div class="alert alert-warning">
                    Account is inactive. Please contact administrator.
                </div> -->
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control"
                    placeholder="your@email.com" maxlength="100"
                    value="<?= encode($GLOBALS['email'] ?? '') ?>">
                <?= err('email') ?>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Enter your password" maxlength="100">
                <?= err('password') ?>
            </div>

            <!-- Submit Buttons -->
            <div class="button-row">
                <button type="submit" class="btn btn-primary">Sign In</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>

            <div class="links-container">
                <div class="forgot-link">
                    <a href="forgot-password.php">Forgot Password?</a>
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