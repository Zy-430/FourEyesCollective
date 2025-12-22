<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$_title = 'Add Category';

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

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Add New Category</h1>
    </div>
    <div class="form-container ">
        <form method="post" sclass="add-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Category ID</label>
                    <input type="text"
                        name="category_id"
                        value="<?= $newCategoryId ?>"
                        class="form-control"
                        readonly>
                </div>

                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="category_name" class="form-control" required
                        placeholder="Example: Accessories">


                </div>
            </div>
            <!-- Submit Buttons -->
            <div class="form-row button-row" style="margin-top: 344px;">
                <button type="button" class="btn btn-white" onclick="location.href='view_category.php'">Back</button>
                <button type="submit" class="btn btn-add">Add</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>
        </form>
    </div>
</div>
</body>

</html>