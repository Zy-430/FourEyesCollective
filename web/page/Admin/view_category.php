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
                            
                            
                            <td style="text-align:center;">
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
                            
                            <td style="text-align:center;">
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

                            <td style="text-align:center;">
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