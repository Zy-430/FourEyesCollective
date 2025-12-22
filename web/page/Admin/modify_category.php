<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
$_title = 'Modify Category';

auth('Admin');

$id = get('id');

$stm = $_db->prepare("SELECT * FROM category WHERE category_id = ?");
$stm->execute([$id]);
$c = $stm->fetch();

if (!$c) {
    die('Category not found');
}

if (is_post()) {
    $category_name = post('category_name');

    $update = $_db->prepare(
        "UPDATE category SET category_name = ? WHERE category_id = ?"
    );
    $update->execute([$category_name, $id]);

    header('Location: view_category.php?msg=updated&cat_id=' . $id);
    exit;
}
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
                    <input type="text"
                        value="<?= encode($c->category_id) ?>"
                        readonly
                        class="form-control">

                </div>
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="category_name"
                        value="<?= encode($c->category_name) ?>"
                        required
                        class="form-control">
                </div>

            </div>
            <div class="form-row button-row" style="margin-top: 344px;">
                <button type="button" class="btn btn-white" onclick="location.href='view_category.php'">Back</button>
                <button type="submit" class="btn btn-add">Update </button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>

        </form>
    </div>

</div>
</body>

</html>