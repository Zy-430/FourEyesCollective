<?php
require '../_base.php';
require '../lib/db.php';
require('../lib/fpdf/fpdf.php');
auth();

$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;
$type = $_GET['type'] ?? $_POST['type'] ?? 'pdf';

if (!$order_id) exit('Invalid order');

// Fetch order + user + address + payment + receipt
$stm = $_db->prepare("
SELECT 
    o.order_id, o.order_date, o.total_amount,
    u.name AS customer_name,
    pm.brand AS payment_brand,
    pm.last4 AS payment_last4,
    p.transaction_date,
    r.receipt_id, r.issued_at AS receipt_issued_at,
    a.recipient_name, a.address_line1, a.address_line2, a.city, a.state, a.postcode, a.country
FROM `order` o
JOIN users u ON o.user_id = u.user_id
LEFT JOIN payment p ON o.order_id = p.order_id
LEFT JOIN payment_method pm ON p.payment_method_id = pm.payment_method_id
LEFT JOIN receipt r ON o.order_id = r.order_id
LEFT JOIN address a ON a.address_id = o.address_id
WHERE o.order_id = ? AND o.user_id = ?
");
$stm->execute([$order_id, $_user->user_id]);
$order = $stm->fetch(PDO::FETCH_ASSOC);
if (!$order) exit('Order not found');

$last4 = $order['payment_last4'] ?? '';
$payment_display = $order['payment_brand'] . ' **** **** **** ' . $last4;

// Fetch items
$stm_items = $_db->prepare("
    SELECT oi.*, p.product_name
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stm_items->execute([$order_id]);
$items = $stm_items->fetchAll(PDO::FETCH_ASSOC);

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();

// Company Logo & Info
$pdf->Image('../images/WIS_logo_2.png', 10, 10, 20); // adjust path & size
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 5, 'Four Eyes Collective', 0, 1, 'R');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'hello@foureyescollective.com', 0, 1, 'R');
$pdf->Cell(0, 5, '+1 (555) 123-4567', 0, 1, 'R');
$pdf->Cell(0, 5, '123 Vision Street, NY 10001', 0, 1, 'R');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, "Invoice for Order {$order['order_id']}", 0, 1, 'C');

// Customer Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, "Customer Information", 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, "Name: {$order['customer_name']}", 0, 1);
$pdf->Cell(0, 6, "Recipient: {$order['recipient_name']}", 0, 1);
$pdf->MultiCell(0, 6, "Address: {$order['address_line1']} {$order['address_line2']}, {$order['city']}, {$order['state']} {$order['postcode']}, {$order['country']}", 0, 1);

$pdf->Ln(5);
// Order Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, "Order Information", 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, "Order ID: {$order['order_id']}", 0, 1);
$pdf->Cell(0, 6, "Order Date: ".date('d M Y H:i', strtotime($order['order_date'])), 0, 1);
$pdf->Cell(0, 6, "Payment Method: {$payment_display}", 0, 1);
$pdf->Cell(0, 6, "Paid At: ".($order['transaction_date'] ? date('d M Y H:i', strtotime($order['transaction_date'])) : '-'), 0, 1);
$pdf->Cell(0, 6, "Receipt ID: ".($order['receipt_id'] ?? '-'), 0, 1);
$pdf->Cell(0, 6, "Issued At: ".($order['receipt_issued_at'] ? date('d M Y H:i', strtotime($order['receipt_issued_at'])) : '-'), 0, 1);

// Set automatic page breaks
$pdf->SetAutoPageBreak(true, 20);

// Table Header
$pdf->SetFont('Arial','B',12);
$pdf->Cell(90,8,"Product Name",1,0,'C');
$pdf->Cell(25,8,"Qty",1,0,'C');
$pdf->Cell(40,8,"Price (RM)",1,0,'C');
$pdf->Cell(35,8,"Total (RM)",1,1,'C'); // sum of width = 90+25+40+35 = 190 (fits A4)

// Table Content
$pdf->SetFont('Arial','',12);
foreach($items as $item){
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Product Name with wrapping
    $pdf->MultiCell(90,6,$item['product_name'],1);

    // Move right to continue same row
    $pdf->SetXY($x + 90, $y);
    $pdf->Cell(25,6,$item['product_qty'],1,0,'C');
    $pdf->Cell(40,6,number_format($item['price'],2),1,0,'R');
    $pdf->Cell(35,6,number_format($item['price']*$item['product_qty'],2),1,1,'R');
}

// Total row
$pdf->SetFont('Arial','B',12);
$pdf->Cell(155,8,"Total Amount",1,0,'R'); // 90+25+40 = 155
$pdf->Cell(35,8,number_format($order['total_amount'],2),1,1,'R');

// Display PDF in browser
$pdf->Output('I', "receipt_{$order['order_id']}.pdf"); // 'I' = inline
exit;
?>
