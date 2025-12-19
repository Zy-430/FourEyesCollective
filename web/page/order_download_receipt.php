<?php
require '../_base.php';
require '../lib/db.php';
require('../lib/fpdf/fpdf.php');

auth('Member');

$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;
$type = $_GET['type'] ?? $_POST['type'] ?? 'pdf';

if (!$order_id) exit('Invalid order');

$isAdmin = $_user->role === 'Admin';

// Check if a token is provided
$token = $_GET['token'] ?? null;

// Fetch order + user + address + payment + receipt
$sql = "
SELECT 
    o.order_id, o.user_id, o.order_date, o.total_amount,
    u.name AS customer_name,
    p.payment_method_type AS payment_brand,
    p.transaction_date,
    r.receipt_id, r.issued_at AS receipt_issued_at,
    a.recipient_name, a.address_line1, a.address_line2, a.city, a.state, a.postcode, a.country
FROM `order` o
JOIN users u ON o.user_id = u.user_id
LEFT JOIN payment p ON o.order_id = p.order_id
LEFT JOIN receipt r ON o.order_id = r.order_id
LEFT JOIN address a ON a.address_id = o.address_id
WHERE o.order_id = ?
AND (o.user_id = ? OR ? = 1)
";

$stm = $_db->prepare($sql);
$stm->execute([$order_id, $_user->user_id, $isAdmin ? 1 : 0]);
$order = $stm->fetch(PDO::FETCH_ASSOC);

if (!$order) exit('Order not found or deniew access');

// --- Token verification (for email link) ---
if ($token) {
    $stm = $_db->prepare("
        SELECT * FROM email_verification 
        WHERE token = ? AND type = 'invoice' AND user_id = ? AND expiry > NOW() AND is_used = 0
    ");
    $stm->execute([$token, $order['user_id']]);
    $tokenValid = $stm->fetch(PDO::FETCH_ASSOC);

    if (!$tokenValid) exit('Invalid or expired invoice link.');

    // Mark token as used
    $_db->prepare("UPDATE email_verification SET is_used = 1 WHERE token = ?")->execute([$token]);
} else {
    // If no token, allow owner OR admin
    if (!$isAdmin && $order['user_id'] != $_user->user_id) {
        exit('Access denied.');
    }
}

// Fetch order items
$stm_items = $_db->prepare("
SELECT oi.*, p.product_name
FROM order_item oi
JOIN product p ON oi.product_id = p.product_id
WHERE oi.order_id = ?
");
$stm_items->execute([$order_id]);
$items = $stm_items->fetchAll(PDO::FETCH_ASSOC);

// Payment display
$last4 = $order['payment_last4'] ?? '';
$payment_display = $order['payment_brand'] . ($last4 ? " **** **** **** $last4" : '');

// -------------------------
// GENERATE PDF
// -------------------------
$pdf = new FPDF();
$pdf->AddPage();

// Company Info
$pdf->Image('../images/WIS_logo_2.png', 10, 10, 20);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 5, 'Four Eyes Collective', 0, 1, 'R');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'hello@foureyescollective.com', 0, 1, 'R');
$pdf->Cell(0, 5, '+1 (555) 123-4567', 0, 1, 'R');
$pdf->Cell(0, 5, '123 Vision Street, NY 10001', 0, 1, 'R');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, "Invoice for Order {$order['order_id']}", 0, 1, 'C');

// Customer Info
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, "Customer Information", 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, "Name: {$order['customer_name']}", 0, 1);
$pdf->Cell(0, 6, "Recipient: {$order['recipient_name']}", 0, 1);
$pdf->MultiCell(0, 6, "Address: {$order['address_line1']} {$order['address_line2']}, {$order['city']}, {$order['state']} {$order['postcode']}, {$order['country']}", 0, 1);

$pdf->Ln(5);
// Order Info
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, "Order Information", 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, "Order ID: {$order['order_id']}", 0, 1);
$pdf->Cell(0, 6, "Order Date: " . date('d M Y H:i', strtotime($order['order_date'])), 0, 1);
$pdf->Cell(0, 6, "Payment Method: {$payment_display}", 0, 1);
$pdf->Cell(0, 6, "Paid At: " . ($order['transaction_date'] ? date('d M Y H:i', strtotime($order['transaction_date'])) : '-'), 0, 1);
$pdf->Cell(0, 6, "Receipt ID: " . ($order['receipt_id'] ?? '-'), 0, 1);
$pdf->Cell(0, 6, "Issued At: " . ($order['receipt_issued_at'] ? date('d M Y H:i', strtotime($order['receipt_issued_at'])) : '-'), 0, 1);

$pdf->SetAutoPageBreak(true, 20);

// Table Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(90, 8, "Product Name", 1, 0, 'C');
$pdf->Cell(25, 8, "Qty", 1, 0, 'C');
$pdf->Cell(40, 8, "Price (RM)", 1, 0, 'C');
$pdf->Cell(35, 8, "Total (RM)", 1, 1, 'C');

// Table Content
$pdf->SetFont('Arial', '', 12);
foreach ($items as $item) {
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell(90, 6, $item['product_name'], 1);
    $pdf->SetXY($x + 90, $y);
    $pdf->Cell(25, 6, $item['product_qty'], 1, 0, 'C');
    $pdf->Cell(40, 6, number_format($item['price'], 2), 1, 0, 'R');
    $pdf->Cell(35, 6, number_format($item['price'] * $item['product_qty'], 2), 1, 1, 'R');
}

// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(155, 8, "Total Amount", 1, 0, 'R');
$pdf->Cell(35, 8, number_format($order['total_amount'], 2), 1, 1, 'R');

// Output PDF
if ($type === 'pdf') {
    $pdf->Output('D', "order_{$order['order_id']}.pdf");
    exit;
}
