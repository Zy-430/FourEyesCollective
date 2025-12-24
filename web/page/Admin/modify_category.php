<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$id = get('id');

$stm = $_db->prepare("SELECT * FROM category WHERE category_id = ?");
$stm->execute([$id]);
$c = $stm->fetch();

if (!$c) die("Category not found");

$oldFolder = $c->folder;

if (is_post()) {

    $category_name = post('category_name');
    $newFolder = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", post('folder')));

    // Rename folder if changed
    $root = $_SERVER['DOCUMENT_ROOT'] . "/images/product/";

    if ($newFolder !== $oldFolder) {
        $oldPath = $root . $oldFolder;
        $newPath = $root . $newFolder;

        if (is_dir($oldPath)) {
            rename($oldPath, $newPath);
        }
    }

    $stm = $_db->prepare("
        UPDATE category 
        SET category_name = ?, folder = ? 
        WHERE category_id = ?
    ");
    $stm->execute([$category_name, $newFolder, $id]);

    header("Location: view_category.php?msg=updated&cat_id=$id");
    exit;
}
$_title = "Modify Category | Four Eyes Collective";

?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Modify Category</h1>
    </div>

    <div class="form-container">
        <form method="post" class="add-form">

            <div class="form-row">
                <div class="form-group">
                    <label>Category ID</label>
                    <input type="text" value="<?= $c->category_id ?>" readonly class="form-control">
                </div>

                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="category_name" value="<?= encode($c->category_name) ?>" required class="form-control">
                </div>

                <div class="form-group">
                    <label>Folder Name</label>
                    <input type="text" name="folder" value="<?= encode($c->folder) ?>" required class="form-control">
                </div>
            </div>

            <div class="form-row button-row">
                <button type="button" class="btn btn-white" onclick="location.href='view_category.php'">Back</button>
                <button type="submit" class="btn btn-add">Update</button>
            </div>

        </form>
    </div>
</div>
</body>

</html>