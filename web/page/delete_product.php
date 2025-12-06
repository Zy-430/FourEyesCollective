<?php
require '../_base.php';
require '../lib/db.php';
auth('Admin');


$id = get('id');

// Delete product
$stm = $_db->prepare("DELETE FROM product WHERE product_id = ?");
$stm->execute([$id]);

header("Location: view_product.php?msg=deleted");
exit;
