<?php
require '../../_base.php';
require '../../lib/db.php';
auth('Admin');

$sql = "
    SELECT 
        p.product_name,
        SUM(oi.product_qty) AS total_sold
    FROM order_item oi
    JOIN product p ON p.product_id = oi.product_id
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 5
";

$data = $_db->query($sql)->fetchAll();

header('Content-Type: application/json');
echo json_encode($data);