<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$id = get('id');

// Check if category is used by any product
$check = $_db->prepare(
    "SELECT COUNT(*) FROM product WHERE category_id = ?"
);
$check->execute([$id]);
$count = $check->fetchColumn();

if ($count > 0) {
    // Category in use will block delete
    header('Location: view_category.php?msg=error&cat_id=' . $id);
    exit;
}

// Safe to delete
$del = $_db->prepare(
    "DELETE FROM category WHERE category_id = ?"
);
if ($del->execute([$id])) {
    // Success
    header('Location: view_category.php?msg=deleted&cat_id=' . $id);
} else {
    // Error occurred
    header('Location: view_category.php?msg=error&cat_id=' . $id);
}
exit;