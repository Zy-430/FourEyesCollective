<?php
require '../_base.php';
require '../lib/db.php';

if (is_post()) {
    $email = post('email');
    $password = post('password');
    
    // Demo authentication (replace with real authentication)
    if ($email === 'john@demo.com' && $password === 'demo123') {
        $_SESSION['user_id'] = 'ME0001';
        $_SESSION['user_name'] = 'John Doe';
        $_SESSION['user_email'] = 'john.doe@example.com';
        $_SESSION['user_role'] = 'member';
        
        $redirect = get('redirect', '/page/shoppage.php');
        redirect($redirect);
    } else {
        temp('error', 'Invalid credentials. For demo, use email: john@demo.com, password: demo123');
    }
}

$_title = 'Login | Four Eyes Collective';
include '../_head.php';
?>

<div style="max-width: 400px; margin: 0 auto; padding: 40px 0;">
    <h1 style="text-align: center; margin-bottom: 30px;">Login</h1>
    
    <?php if (temp('error')): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <?= encode(temp('error')) ?>
        </div>
    <?php endif; ?>
    
    <div style="background: #f8f9fa; border-radius: 8px; padding: 30px; border: 1px solid #e0e0e0;">
        <form method="post">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                    Email
                </label>
                <input type="email" name="email" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                       placeholder="john@demo.com">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                    Password
                </label>
                <input type="password" name="password" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                       placeholder="demo123">
            </div>
            
            <button type="submit" 
                    style="background: #2c3e50; color: white; border: none; padding: 12px 30px; width: 100%; border-radius: 4px; font-size: 16px; cursor: pointer; transition: background 0.3s ease;">
                Login
            </button>
        </form>
        
        <div style="margin-top: 20px; padding: 20px; background: #e8f4f8; border-radius: 4px;">
            <h4 style="margin-top: 0; color: #2c3e50;">Demo Credentials</h4>
            <p style="margin: 10px 0; color: #555;">
                <strong>Email:</strong> john@demo.com<br>
                <strong>Password:</strong> demo123
            </p>
            <p style="margin: 10px 0; color: #555; font-size: 0.9em;">
                Or click <a href="/?demo=1" style="color: #2c3e50; font-weight: bold;">here</a> to auto-login.
            </p>
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>