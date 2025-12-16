<?php
require '../_base.php';
require '../lib/db.php';
auth('Admin');

$id = get('id');

$stm = $_db->prepare("SELECT * FROM category WHERE category_id = ?");
$stm->execute([$id]);
$c = $stm->fetch();

if (!$c) {
    die('Category not found');
}

$_title = 'Modify Category';
include '../_head.php';

if (is_post()) {
    $category_name = post('category_name');

    $update = $_db->prepare(
        "UPDATE category SET category_name = ? WHERE category_id = ?"
    );
    $update->execute([$category_name, $id]);

    header('Location: view_category.php?msg=updated');
    exit;
}
?>

<h1>Modify Category</h1>

<form method="post" style="max-width:400px;">

    <label>Category ID</label>
    <input type="text"
        value="<?= encode($c->category_id) ?>"
        readonly
        style="width:100%; padding:8px; margin-bottom:15px; background:#eee;">


    <label>Category Name</label>
    <input type="text" name="category_name"
           value="<?= encode($c->category_name) ?>"
           required
           style="width:100%; padding:8px; margin-bottom:20px;">

    <button type="submit"
            style="padding:10px 20px; background:#2c3e50; color:white; border:none; border-radius:5px;">
        Save Changes
    </button>

    <a href="view_category.php"
       style="margin-left:10px; text-decoration:none;">
        Back
    </a>

</form>

<?php include '../_foot.php'; ?>