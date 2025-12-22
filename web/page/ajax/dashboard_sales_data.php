<?php
require '../../_base.php';
require '../../lib/db.php';
auth('Admin');

$sql = "
    SELECT 
        DATE(order_date) AS sale_date,
        SUM(total_amount) AS daily_total
    FROM `order`
    WHERE status = 'completed'
      AND order_date >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(order_date)
    ORDER BY sale_date ASC
";

$data = $_db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Build last 7 days with zero-fill
$result = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $amount = 0;

    foreach ($data as $row) {
        if ($row['sale_date'] === $date) {
            $amount = (float)$row['daily_total'];
            break;
        }
    }

    $result[] = [
        'date' => date('d M', strtotime($date)),
        'total' => $amount
    ];
}

header('Content-Type: application/json');
echo json_encode($result);