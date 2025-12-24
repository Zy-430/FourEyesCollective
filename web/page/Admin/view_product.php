<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
require '../../lib/category.php';
// Use to ensure this page's body has the `product` class for page-specific styling
?>
<script>
    (function(){
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('product'); });
        } else {
            document.body.classList.add('product');
        }
    })();
</script>
<?php
auth('Admin');

$_title = 'Manage Products';

/* =========================
   FILTER & SORT PARAMETERS
========================= */
$search = get('search', '');
$selectedCat = get('cat');
$priceRange = get('price_range', '');
$minPrice = get('min_price', 0);
$maxPrice = get('max_price', 1000);

$sort = get('sort', 'product_id');
$dir  = get('dir', 'asc');

$allowedSort = ['product_id', 'product_name', 'product_price', 'product_stock'];
if (!in_array($sort, $allowedSort)) $sort = 'product_id';
if (!in_array($dir, ['asc', 'desc'])) $dir = 'asc';

/* =========================
   BUILD SQL (ADMIN)
========================= */
$sql = "SELECT * FROM product WHERE 1=1";
$params = [];

// Search
if ($search) {
    $sql .= " AND (product_name LIKE ? OR product_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Category
if ($selectedCat) {
    $sql .= " AND category_id = ?";
    $params[] = $selectedCat;
}

// Price Range (Preset)
if ($priceRange) {
    list($min, $max) = explode('-', $priceRange);
    $sql .= " AND product_price BETWEEN ? AND ?";
    $params[] = $min;
    $params[] = $max;
}

// Custom Price
if ($minPrice || $maxPrice < 1000) {
    $sql .= " AND product_price BETWEEN ? AND ?";
    $params[] = $minPrice;
    $params[] = $maxPrice;
}

// Sorting
$sql .= " ORDER BY $sort $dir";
$stm = $_db->prepare($sql);
$stm->execute($params);
$products = $stm->fetchAll();

/* =========================
   SPLIT LOW / NORMAL STOCK
========================= */
$lowStock = [];
$normalStock = [];

foreach ($products as $p) {
    ($p->product_stock <= 10) ? $lowStock[] = $p : $normalStock[] = $p;
}

/* =========================
   PRICE RANGE LABELS
========================= */
$priceRanges = [
    '0-100' => 'Under RM100',
    '100-250' => 'RM100 - RM250',
    '250-400' => 'RM250 - RM400',
    '400-1000' => 'RM400+'
];

// Store notification messages
$notification_message = '';
$notification_type = 'success';
$product_id = get('product_id');

if (get('msg') == 'added') {
    $notification_message = 'Product (' . $product_id . ') added successfully!';
} elseif (get('msg') == 'updated') {
    $notification_message = 'Product (' . $product_id . ') updated successfully!';
} elseif (get('msg') == 'deleted') {
    $notification_message = 'Product (' . $product_id . ') deleted successfully!';
} elseif (get('msg') == 'restored') {
    $notification_message = 'Product (' . $product_id . ') restored successfully!';
} 
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Manage Products</h1>
        <div class="header-actions small">
            <!-- Add New Product Button -->
            <a href="add_product.php" class="btn-default btn-add" style="text-decoration: none; font-weight:bolder;">
                <i class="fas fa-plus"></i> Add
            </a>
        </div>

    </div>

    <div class="view-products-container">

        <!-- ================= LEFT FILTER SIDEBAR ================= -->
        <div style="width:260px;">

            <form method="get">
                <br>
                <label class="left-filter">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control"
                    placeholder="Search products...">
                <br><br>
                <label class="left-filter">Category</label><br>
                <label style="font-size: 15px; "><input type="radio" name="cat" value="" <?= !$selectedCat ? 'checked' : '' ?>> All</label><br>
                <?php foreach ($categories as $id => $name): ?>
                    <label style="font-size: 15px; ">
                        <input type="radio" name="cat" value="<?= $id ?>" <?= $selectedCat === $id ? 'checked' : '' ?>>
                        <?= $name ?>
                    </label><br>
                <?php endforeach; ?>
                <br>
                <label class="left-filter">Price Range</label><br>

                <label style="font-size: 15px; "><input type="radio" name="price_range" value="" <?= !$priceRange ? 'checked' : '' ?>> All</label><br>
                <?php foreach ($priceRanges as $range => $label): ?>
                    <label style="font-size: 15px; ">
                        <input type="radio" name="price_range" value="<?= $range ?>" <?= $priceRange === $range ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label><br>
                <?php endforeach; ?>
                <br>
                <label class="left-filter">Custom</label><br>
                <input type="number" name="min_price" value="<?= $minPrice ?>" class="form-control" style="width:70px;margin-right:10px;">
                to
                <input type="number" name="max_price" value="<?= $maxPrice ?>" class="form-control" style="width:70px; margin-left:10px;">

                <br><br>
                <button type="submit" style="width:100%; padding:10px; background:#162b65; color:white;">
                    Apply Filters
                </button>

                <a href="view_product.php" style="display:block; margin-top:10px; text-align:center; color:#c0392b; text-decoration:none;">
                    Clear All
                </a>

            </form>
        </div>

        <!-- ================= RIGHT CONTENT ================= -->
        <div class="view-products-content">

            <!-- SORT HEADER -->
            <div class="view-products-sort-header">

                <span>Total Products: <?= count($products) ?></span>

                <div>
                    Sort by:
                    <?php
                    $sortOptions = [
                        'product_name' => 'Name',
                        'product_price' => 'Price',
                        'product_stock' => 'Stock',
                        'product_id' => 'Newest'
                    ];

                    foreach ($sortOptions as $field => $label):
                        $nextDir = ($sort == $field && $dir == 'asc') ? 'desc' : 'asc';
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => $field, 'dir' => $nextDir])) ?>"
                            style="margin-left:8px; padding:5px 10px; 
                        background:<?= $sort == $field ? '#162b65' : '#888' ?>; color:white; 
                        text-decoration:none; border-radius: 4px;">
                            <?= $label ?>
                            <?php if ($sort == $field): ?><?= $dir == 'asc' ? '↑' : '↓' ?><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- LOW STOCK ROW (ONLY IF EXISTS)  -->
            <?php if (count($lowStock) > 0): ?>
                <h2 style="color:#b10000; margin-top:20px;">⚠️ Low Stock Items</h2>
                <div class="products-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px; margin-bottom:40px;">
                    <?php foreach ($lowStock as $p): ?>
                        <?php $folder = $categoryFolders[$p->category_id] ?? 'others';
                        $imgArr = explode(",", $p->product_image);
                        $img = trim($imgArr[0]); ?>
                        <div class="product-card" style="border:1px solid #f5b5b5; padding:15px; border-radius:8px; background:#ffeaea;">
                            <img src="/images/product/<?= $folder ?>/<?= $img ?>" style="width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:10px;">
                            <h3><?= encode($p->product_name) ?></h3>
                            <p>RM <?= number_format($p->product_price, 2) ?></p>
                            <p><strong>Stock: <?= $p->product_stock ?></strong></p>
                            <p style="color:#b10000;">⚠️ Low Stock</p>
                            <div style="margin-top:10px; display:flex; gap:10px;">
                                <a href="modify_product.php?id=<?= $p->product_id ?>" style="flex:1; padding:8px; background:#2980b9; color:white; text-align:center; border-radius:5px; text-decoration:none;"><i class="fas fa-edit" style="font-size:14px;"></i> Modify</a>
                                <?php if ($p->product_status == 1): ?>
                                    <a href="delete_product.php?id=<?= $p->product_id ?>&action=delete" onclick="return confirm('Are you sure you want to delete product (<?= $p->product_id ?>) ?');" style="flex:1; 
            padding:8px; background:#c0392b; color:white; text-align:center; border-radius:5px; text-decoration:none;">
                                        <i class="fas fa-trash" style="font-size:14px;padding-right:5px;"></i>Delete
                                    </a>
                                <?php else: ?>
                                    <a href="delete_product.php?id=<?= $p->product_id ?>&action=restore" onclick="return confirm('Are you sure want to restore product (<?= $p->product_id ?>) ?');" style="flex:1; 
            padding:8px; background:#27ae60; color:white; text-align:center; border-radius:5px; text-decoration:none;">
                                        <i class="fas fa-redo" style="font-size:14px;padding-right:5px;"></i>Restore
                                    </a>
                                <?php endif ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- NORMAL STOCK ROW -->
            <div class="products-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:20px; margin-top:20px;">
                <?php foreach ($normalStock as $p): ?>
                    <?php $folder = $categoryFolders[$p->category_id] ?? 'others';
                    $imgArr = explode(",", $p->product_image);
                    $img = trim($imgArr[0]); ?>
                    <div class="product-card" style="border:1px solid #ddd; padding:15px; border-radius:8px; background:white;">
                        <img src="/images/product/<?= $folder ?>/<?= $img ?>" style="width:100%; height:170px; object-fit:cover; border-radius:6px; margin-bottom:10px;">
                        <h3><?= encode($p->product_name) ?></h3>
                        <p>RM <?= number_format($p->product_price, 2) ?></p>
                        <p>Stock: <strong><?= $p->product_stock ?></strong></p>
                        <p>Status: <span style="font-weight:bold; color:<?= $p->product_status ? 'green' : 'red' ?>;"> <?= $p->product_status ? 'Active' : 'Inactive' ?> </span> </p>
                        <div style="margin-top:10px; display:flex; gap:10px;">
                            <a href="modify_product.php?id=<?= $p->product_id ?>" style="flex:1; padding:8px; background:#2980b9; color:white; text-align:center; border-radius:5px; text-decoration:none;"><i class="fas fa-edit" style="font-size:14px;"></i> Modify</a>
                            <?php if ($p->product_status == 1): ?>
        <a href="delete_product.php?id=<?= $p->product_id ?>&action=delete" onclick="return confirm('Are you sure you want to delete product (<?= $p->product_id ?>) ?');" style="flex:1; 
            padding:8px; background:#c0392b; color:white; text-align:center; border-radius:5px; text-decoration:none;">
            <i class="fas fa-trash" style="font-size:14px;padding-right:5px;"></i>Delete
        </a>
    <?php else: ?>
        <a href="delete_product.php?id=<?= $p->product_id ?>&action=restore" onclick="return confirm('Are you sure you want to restore product (<?= $p->product_id ?>) ?');" style="flex:1; 
            padding:8px; background:#27ae60; color:white; text-align:center; border-radius:5px; text-decoration:none;">
            <i class="fas fa-redo" style="font-size:14px;padding-right:5px;"></i>Restore
        </a>
    <?php endif ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    <?php if ($notification_message): ?>
        showNotification('<?= addslashes($notification_message) ?>', '<?= $notification_type ?>');
    <?php endif; ?>
});
</script>