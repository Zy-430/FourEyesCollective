<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$_title = 'Add Category';

// Auto-generate next category ID
$lastId = $_db->query("SELECT MAX(category_id) FROM category")->fetchColumn();
$num = $lastId ? intval(substr($lastId, 2)) + 1 : 1;
$newCategoryId = 'CA' . str_pad($num, 4, '0', STR_PAD_LEFT);

if (is_post()) {

    $category_id   = $newCategoryId;
    $category_name = post('category_name');
    $folder        = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", post('folder')));

    // Insert category
    $stm = $_db->prepare("
        INSERT INTO category (category_id, category_name, folder)
        VALUES (?, ?, ?)
    ");
    $stm->execute([$category_id, $category_name, $folder]);

    // Create image directory
    $dir = $_SERVER['DOCUMENT_ROOT'] . "/images/product/$folder";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    header("Location: view_category.php?msg=added&cat_id=$category_id");
    exit;
}
$_title = "Add Category | Four Eyes Collective";
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Add New Category</h1>
    </div>

    <div class="form-container">
        <form method="post" class="add-form">

            <div class="form-row">
                <div class="form-group">
                    <label>Category ID</label>
                    <input type="text" value="<?= $newCategoryId ?>" class="form-control" disabled>
                </div>

                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="category_name" class="form-control" autofocus required>
                </div>

                <div class="form-group">
                    <label>Folder Name (no spaces)</label>
                    <input type="text" name="folder" class="form-control" required placeholder="e.g. sunglasses">
                </div>
            </div>

            <div class="form-row button-row">
                <button type="button" class="btn btn-white" onclick="location.href='view_category.php'">Back</button>
                <button type="submit" class="btn btn-add">Add</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>

        </form>
    </div>
</div>
</body>

</html>