<?php
require '../_base.php';
require '../lib/db.php';
auth('Admin');

$_title = 'Manage Categories';
include '../_head.php';

// Fetch all categories
$categories = $_db->query("SELECT * FROM category ORDER BY category_id")->fetchAll();
?>

<h1 style="margin-bottom:20px;">Manage Categories</h1>

<!-- SUCCESS MESSAGE -->
<?php if (get('msg')): ?>
    <div style="padding:10px; background:#d4f8d4; border:1px solid #8acb8a; margin-bottom:15px;">
        <?= get('msg') === 'added' ? '✅ Category added successfully!' : '' ?>
        <?= get('msg') === 'updated' ? '✏️ Category updated successfully!' : '' ?>
        <?= get('msg') === 'deleted' ? '🗑 Category deleted successfully!' : '' ?>
    </div>
<?php endif; ?>

<!-- ADD CATEGORY BUTTON -->
<a href="add_category.php"
   style="display:inline-block; margin-bottom:20px;
          padding:10px 20px; background:#2c3e50; color:white;
          border-radius:5px; text-decoration:none;">
    ➕ Add New Category
</a>

<!-- CATEGORY TABLE -->
<table style="width:100%; border-collapse:collapse; background:white;">

    <thead>
        <tr style="background:#f4f4f4;">
            <th style="padding:12px; border:1px solid #ddd;">Category ID</th>
            <th style="padding:12px; border:1px solid #ddd;">Category Name</th>
            <th style="padding:12px; border:1px solid #ddd;">Actions</th>
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
                    <td style="padding:10px; border:1px solid #ddd; text-align:center;">
                        <?= encode($c->category_id) ?>
                    </td>

                    <td style="padding:10px; border:1px solid #ddd;">
                        <?= encode($c->category_name) ?>
                    </td>

                    <td style="padding:10px; border:1px solid #ddd; text-align:center;">
                        <a href="modify_category.php?id=<?= $c->category_id ?>"
                           style="padding:6px 12px; background:#2980b9;
                                  color:white; border-radius:4px;
                                  text-decoration:none; margin-right:5px;">
                            ✏ Edit
                        </a>

                        <a href="delete_category.php?id=<?= $c->category_id ?>"
                           onclick="return confirm('Are you sure you want to delete this category?');"
                           style="padding:6px 12px; background:#c0392b;
                                  color:white; border-radius:4px;
                                  text-decoration:none;">
                            🗑 Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>

</table>

<?php include '../_foot.php'; ?>