<?php
require '../../_base.php';
require '../../lib/db.php';
auth('Admin');


$id = get('id');
$action = get('action');

if (!$id) {
    die("Product ID is required.");
}

// Set product status based on action
if ($action === 'delete') {
    $product_status = 0; // Inactive
    $msg = 'deleted';
} elseif ($action === 'restore') {
    $product_status = 1; // Active  
    $msg = 'restored';
} else {
    die("Invalid action.");
}

// Delete product
$stmt = $_db->prepare("UPDATE product SET product_status=? WHERE product_id = ?");
$stmt->execute([$product_status, $id]);

header("Location: view_product.php?product_id=$id&msg=$msg");
exit;