<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
auth('Admin');

$_title = 'Manage Categories';

// Sorting
$fields = [
    'category_id' => 'Category ID',
    'category_name' => 'Category Name',
    'active_count' => 'Active Products',
    'inactive_count' => 'Inactive Products',
    'total_count' => 'Total',
];

$sort = req('sort');
key_exists($sort, $fields) || $sort = 'category_id';

$dir = req('dir');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

// Fetch categories with sorting
$sql = "SELECT 
            c.*, 
            COUNT(p.product_id) as total_count,
            SUM(CASE WHEN p.product_status = 1 THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN p.product_status = 0 THEN 1 ELSE 0 END) as inactive_count
        FROM category c
        LEFT JOIN product p ON c.category_id = p.category_id
        GROUP BY c.category_id, c.category_name
        ORDER BY $sort $dir";

$stmt = $_db->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll();

// Store notification messages
$notification_message = '';
$notification_type = 'success';
$category_id = get('cat_id');

if (get('msg') == 'added') {
    $notification_message = 'Category (' . $category_id . ') added successfully!';
} elseif (get('msg') == 'updated') {
    $notification_message = 'Category (' . $category_id . ') updated successfully!';
} elseif (get('msg') == 'deleted') {
    $notification_message = 'Category (' . $category_id . ') deleted successfully!';
} elseif (get('msg') == 'error') {
    $notification_message = 'Cannot delete category (' . $category_id . ') because it contains products!';
    $notification_type = 'error';
}
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Manage Categories</h1>
        <div class="header-actions small">
            <!-- ADD CATEGORY BUTTON -->
            <button class="btn-default btn-add" onclick="location.href='add_category.php'">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
    </div>
    <div class="table-container">
        <!-- CATEGORY TABLE -->
        <table class="table table-small">

            <thead>
                <tr>
                    <th style="text-align:center;">Actions</th>
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
                                    <a href="modify_category.php?id=<?= $c->category_id ?>" class="btn-default edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="delete_category.php?id=<?= $c->category_id ?>"
                                        onclick="return confirm('⚠ WARNING ⚠\n\nAre you VERY sure you want to delete this category?\n\nCategory: <?= $c->category_id ?>\nFolder will be deleted ONLY if empty.\n\nThis action cannot be undone!');"
                                        class="btn-default delete-btn">

                                        <i class="fas fa-trash"></i>
                                    </a>`
                                </div>
                            </td>

                            <td>
                                <?= encode($c->category_id) ?>
                            </td>

                            <td>
                                <?= encode($c->category_name) ?>
                            </td>
                            
                            
                            <td>
                                <?php if ($c->active_count > 0): ?>
                                    <a href="view_product.php?cat=<?= $c->category_id ?>&status=active"
                                        style="color:black; text-decoration:none; font-weight:bold;"
                                        title="View active products">
                                        <?= $c->active_count ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">0</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if ($c->inactive_count > 0): ?>
                                    <a href="view_product.php?cat=<?= $c->category_id ?>&status=inactive"
                                        style="color:black; text-decoration:none; font-weight:bold;"
                                        title="View inactive products">
                                        <?= $c->inactive_count ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">0</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($c->total_count > 0): ?>
                                    <a href="view_product.php?cat=<?= $c->category_id ?>"
                                        style="color:black; text-decoration:none; font-weight:bold;"
                                        title="View all products in this category">
                                        <?= $c->total_count ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">0</span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    <?php if ($notification_message): ?>
        showNotification('<?= addslashes($notification_message) ?>', '<?= $notification_type ?>');
    <?php endif; ?>
});
</script>