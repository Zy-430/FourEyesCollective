<?php
require '../_base.php';
require '../lib/db.php';
auth('Admin');

$_title = 'Add Category';
include '../_head.php';

// Auto-generate next category ID
$stm = $_db->query("SELECT MAX(category_id) FROM category");
$lastId = $stm->fetchColumn();

if ($lastId) {
    $num = intval(substr($lastId, 2)) + 1;
} else {
    $num = 1;
}

$newCategoryId = 'CA' . str_pad($num, 4, '0', STR_PAD_LEFT);

if (is_post()) {
    $category_id   = $newCategoryId;
    $category_name = post('category_name');

    // Auto-generate folder
    $folder = strtolower(preg_replace('/\s+/', '', $category_name));

    // create image folder
    $dir = "../images/product/$folder";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Insert into database
    $stm = $_db->prepare(
        "INSERT INTO category (category_id, category_name, folder) VALUES (?, ?, ?)"
    );
    $stm->execute([$category_id, $category_name, $folder]);

    header('Location: view_category.php?msg=added');
    exit;
}

?>

<h1>Add New Category</h1>

<form method="post" style="max-width:400px;">

    <label>Category ID</label>
    <input type="text"
        name="category_id"
        value="<?= $newCategoryId ?>"
        readonly
        style="width:100%; padding:8px; margin-bottom:15px; background:#eee;">

    <label>Category Name</label>
    <input type="text" name="category_name" required
           placeholder="Example: Accessories"
           style="width:100%; padding:8px; margin-bottom:20px;">

    <button type="submit"
            style="padding:10px 20px; background:#2c3e50; color:white; border:none; border-radius:5px;">
        Save Category
    </button>

    <a href="view_category.php"
       style="margin-left:10px; text-decoration:none;">
        Cancel
    </a>

</form>

<?php include '../_foot.php'; ?>