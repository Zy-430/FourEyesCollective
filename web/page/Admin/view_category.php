<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$_title = 'Manage Categories';

// Sorting
$fields = [
    'category_id' => 'Category ID',
    'category_name' => 'Category Name'
];

$sort = req('sort');
key_exists($sort, $fields) || $sort = 'category_id';

$dir = req('dir');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

// Fetch categories with sorting
$stmt = $_db->prepare("SELECT * FROM category ORDER BY $sort $dir");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Manage Categories</h1>

        <!-- SUCCESS MESSAGE -->
        <?php if (get('msg')): ?>
            <div class="flash-msg" style="padding:10px; background:#d4f8d4; border:1px solid #8acb8a; margin-bottom:15px;">
                <?= get('msg') === 'added' ? 'Category added successfully!' : '' ?>
                <?= get('msg') === 'updated' ? 'Category updated successfully!' : '' ?>
                <?= get('msg') === 'deleted' ? 'Category deleted successfully!' : '' ?>
            </div>
        <?php endif; ?>
        <div class="header-actions small">
            <!-- ADD CATEGORY BUTTON -->
            <button class="btn-default btn-add" onclick="location.href='add_category.php'">
                <i class="fas fa-plus"></i> Add New Category
            </button>
        </div>
    </div>
    <div class="table-container">
        <!-- CATEGORY TABLE -->
        <table class="table table-small">

            <thead>
                <tr>
                    <th></th>
                    <?= table_headers($fields, $sort, $dir) ?>
                </tr>
            </thead>

            <tbody>
                <?php if (count($categories) === 0): ?>
                    <tr>
                        <td colspan="3" style="padding:15px; text-align:center;">
                            No categories found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td class="actions-row">
                                <div class="action-buttons" style="justify-content:center; gap:10px;">
                                    <a href="modify_category.php?id=<?= $c->category_id ?>" class="btn-default edit-btn" >
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="delete_category.php?id=<?= $c->category_id ?>"
                                        onclick="return confirm('Are you sure you want to delete this category?');"
                                        class="btn-default delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>

                            <td>
                                <?= encode($c->category_id) ?>
                            </td>

                            <td>
                                <?= encode($c->category_name) ?>
                            </td>


                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>