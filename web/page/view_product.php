<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/category.php';

auth('Admin');

$_title = 'Manage Products';
include '../_head.php';

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
?>

<h1 style="margin-bottom:20px;">Manage Products</h1>

<div style="display:flex; gap:30px;">

    <!-- ================= LEFT FILTER SIDEBAR ================= -->
    <div style="width:260px;">

        <form method="get">

            <h3>Search</h3>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Search products..."
                style="width:100%; padding:8px; margin-bottom:15px; border: 1px solid #ccc; border-radius: 4px;">

            <h3>Category</h3>
            <label><input type="radio" name="cat" value="" <?= !$selectedCat ? 'checked' : '' ?>> All</label><br>
            <?php foreach ($categories as $id => $name): ?>
                <label>
                    <input type="radio" name="cat" value="<?= $id ?>" <?= $selectedCat === $id ? 'checked' : '' ?>>
                    <?= $name ?>
                </label><br>
            <?php endforeach; ?>

            <h3 style="margin-top:15px;">Price Range</h3>

            <label><input type="radio" name="price_range" value="" <?= !$priceRange ? 'checked' : '' ?>> All</label><br>
            <?php foreach ($priceRanges as $range => $label): ?>
                <label>
                    <input type="radio" name="price_range" value="<?= $range ?>" <?= $priceRange === $range ? 'checked' : '' ?>>
                    <?= $label ?>
                </label><br>
            <?php endforeach; ?>

            <h4 style="margin-top:10px;">Custom</h4>
            <input type="number" name="min_price" value="<?= $minPrice ?>"
                style="width:80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            to
            <input type="number" name="max_price" value="<?= $maxPrice ?>"
                style="width:80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">

            <br><br>
            <button type="submit" style="width:100%; padding:10px; background:#2c3e50; color:white;">
                Apply Filters
            </button>

            <a href="view_product.php" style="display:block; margin-top:10px; text-align:center; color:#c0392b;">
                Clear All
            </a>

        </form>
    </div>

    <!-- ================= RIGHT CONTENT ================= -->
    <div style="flex:1;">

        <!-- SORT HEADER -->
        <div style="display:flex; justify-content:space-between; align-items:center;
            padding:10px; background:#f8f9fa; margin-bottom:20px;">

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
                        background:<?= $sort == $field ? '#2c3e50' : '#888' ?>; color:white; 
                        text-decoration:none; border-radius: 4px;">
                        <?= $label ?>
                        <?php if ($sort == $field): ?><?= $dir == 'asc' ? '↑' : '↓' ?><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add New Product Button -->
        <a href="add_product.php"
        style="padding:10px 20px; background:#2c3e50; color:white; 
                border-radius:5px; text-decoration:none; margin-bottom:20px; display:inline-block;">
            ➕ Add New Product
        </a>

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
                            <a href="modify_product.php?id=<?= $p->product_id ?>" style="flex:1; padding:8px; background:#2980b9; color:white; text-align:center; border-radius:5px;">✏ Modify</a> 
                            <a href="delete_product.php?id=<?= $p->product_id ?>" onclick="return confirm('Are you sure?');" 
                                style="flex:1; padding:8px; background:#c0392b; color:white; text-align:center; border-radius:5px;">🗑 Delete</a> 
                        </div>
                    </div> 
                <?php endforeach; ?> 
            </div> 
        <?php endif; ?> 
        
        <!-- NORMAL STOCK ROW -->
        <h2 style="margin-top:20px;">All Products</h2>
        <div class="products-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px; margin-top:20px;">
            <?php foreach ($normalStock as $p): ?> 
            <?php $folder = $categoryFolders[$p->category_id] ?? 'others';
                $imgArr = explode(",", $p->product_image);
                $img = trim($imgArr[0]); ?> 
                <div class="product-card" style="border:1px solid #ddd; padding:15px; border-radius:8px; background:white;"> 
                <img src="/images/product/<?= $folder ?>/<?= $img ?>" style="width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:10px;">
                    <h3><?= encode($p->product_name) ?></h3>
                    <p>RM <?= number_format($p->product_price, 2) ?></p>
                    <p>Stock: <strong><?= $p->product_stock ?></strong></p>
                    <p>Status: <span style="font-weight:bold; color:<?= $p->product_status ? 'green' : 'red' ?>;"> <?= $p->product_status ? 'Active' : 'Inactive' ?> </span> </p>
                    <div style="margin-top:10px; display:flex; gap:10px;"> 
                        <a href="modify_product.php?id=<?= $p->product_id ?>" style="flex:1; padding:8px; background:#2980b9; color:white; text-align:center; border-radius:5px;">✏ Modify</a> 
                        <a href="delete_product.php?id=<?= $p->product_id ?>" onclick="return confirm('Are you sure?');" style="flex:1; 
                            padding:8px; background:#c0392b; color:white; text-align:center; border-radius:5px;">🗑 Delete</a> 
                    </div>
                </div> 
            <?php endforeach; ?> 
        </div>
    </div>
</div>

<?php include '../_foot.php'; ?>