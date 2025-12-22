<?php
require '../../_base.php';
require '../../lib/db.php';
auth('Admin');

$id = get('id');

// 1. Fetch category (to get folder name)
$stm = $_db->prepare("SELECT folder FROM category WHERE category_id = ?");
$stm->execute([$id]);
$c = $stm->fetch();

if (!$c) {
    header("Location: view_category.php?msg=error&cat_id=$id");
    exit;
}

$folder = $c->folder;
$folderPath = $_SERVER['DOCUMENT_ROOT'] . "/images/product/" . $folder;

// 2. Check if category is used by any product
$check = $_db->prepare("SELECT COUNT(*) FROM product WHERE category_id = ?");
$check->execute([$id]);
$count = $check->fetchColumn();

if ($count > 0) {
    header('Location: view_category.php?msg=error&cat_id=' . $id);
    exit;
}

// 3. Delete folder (ONLY if empty)
if (is_dir($folderPath)) {
    $files = array_diff(scandir($folderPath), ['.', '..']);

    if (count($files) === 0) {
        // Empty → safe to delete
        rmdir($folderPath);
    }
}

// 4. Delete category (DB)
$del = $_db->prepare("DELETE FROM category WHERE category_id = ?");
$del->execute([$id]);

header('Location: view_category.php?msg=deleted&cat_id=' . $id);
exit;
