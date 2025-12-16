<?php
require '../_base.php';
require '../lib/db.php';
auth('Admin');

$id = get('id');

// Check if category is used by any product
$check = $_db->prepare(
    "SELECT COUNT(*) FROM product WHERE category_id = ?"
);
$check->execute([$id]);
$count = $check->fetchColumn();

if ($count > 0) {
    // Category in use → block delete
    header('Location: view_category.php?msg=error');
    exit;
}

// Safe to delete
$del = $_db->prepare(
    "DELETE FROM category WHERE category_id = ?"
);
$del->execute([$id]);

header('Location: view_category.php?msg=deleted');
exit;