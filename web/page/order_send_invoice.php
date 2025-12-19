<?php
require '../_base.php';
require '../lib/db.php';

auth('Member');

$order_id = $_POST['order_id'] ?? null;
if (!$order_id) exit("Invalid order");

// Fetch order + user
$stm = $_db->prepare("
    SELECT o.user_id, u.email, u.name 
    FROM `order` o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = ?
");
$stm->execute([$order_id]);
$order = $stm->fetch(PDO::FETCH_ASSOC);

if (!$order) exit("Order not found");

// Fetch existing invoice token or create a new one
$stm = $_db->prepare("
    SELECT token, expiry, is_used 
    FROM email_verification 
    WHERE user_id = ? AND type = 'invoice' 
    ORDER BY created_at DESC LIMIT 1
");
$stm->execute([$order['user_id']]);
$tokenData = $stm->fetch(PDO::FETCH_ASSOC);

if (!$tokenData || strtotime($tokenData['expiry']) < time() || $tokenData['is_used']) {
    $token  = bin2hex(random_bytes(16));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $_db->prepare("
        INSERT INTO email_verification (user_id, token, expiry, type)
        VALUES (?, ?, ?, 'invoice')
    ")->execute([$order['user_id'], $token, $expiry]);
} else {
    $token = $tokenData['token'];
}

// Invoice link
$invoice_link = base("page/order_invoice.php?order_id={$order_id}&token={$token}");

// ======================
// SEND EMAIL (using get_mail())
// ======================
$m = get_mail(); // returns pre-configured PHPMailer instance
$m->addAddress($order['email'], $order['name']);
$m->isHTML(true);
$m->Subject = "Invoice for Order {$order_id}";

// Email body (HTML)
$m->Body = "
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
    .button { display: inline-block; background: #2c3e50; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
</style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Four Eyes Collective</h2>
            <h3>Invoice for Your Order</h3>
        </div>
        <div class='content'>
            <p>Hello " . htmlspecialchars($order['name']) . ",</p>
            <p>You can download your invoice by clicking the button below:</p>
            <p style='text-align: center;'>
                <a href='{$invoice_link}' class='button'>View Invoice</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p><code>{$invoice_link}</code></p>
            <p>This link will expire in 24 hours.</p>
            <p>Thank you for shopping with us!</p>
        </div>
        <div class='footer'>
            <p>This is an automated message, please do not reply.</p>
            <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
";

try {
    if ($m->send()) {
        // Record receipt
        $_db->prepare("
            INSERT INTO receipt (receipt_id, order_id, issued_to, delivery_method, email_sent, pdf_generated)
            VALUES (?, ?, ?, 'email', 1, 1)
        ")->execute(['ER' . substr(uniqid(), -4), $order_id, $order['user_id']]);

        // Return JSON success
        echo json_encode([
            'status' => 'success',
            'message' => 'Invoice email sent successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to send invoice email.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email failed: ' . $e->getMessage()
    ]);
}

exit; // important to stop further output

