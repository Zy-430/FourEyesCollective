<?php
require '../_base.php';
require '../lib/db.php';
auth('Admin');

$_title = 'Manage Products';
include '../_head.php';

// Fetch categories for display names
$categories = [
    'CA0001' => 'Glasses',
    'CA0002' => 'Sunglasses',
    'CA0003' => 'Contact Lens',
    'CA0004' => 'Kids'
];

// Category → folder mapping (DEFINE ONLY ONCE!)
function categoryFolder($catId) {
    return [
        'CA0001' => 'glasses',
        'CA0002' => 'sunglasses',
        'CA0003' => 'contactlens',
        'CA0004' => 'kids'
    ][$catId] ?? 'others';
}

// Fetch all products
$products = $_db->query("SELECT * FROM product")->fetchAll();
?>

<h1 style="margin-bottom:20px;">Manage Products</h1>

<?php if (get('msg') == 'added'): ?>
    <div style="padding:10px; background:#d4f8d4; border:1px solid #8acb8a; margin-bottom:15px;">
        ✅ Product added successfully!
    </div>
<?php endif; ?>

<?php if (get('msg') == 'updated'): ?>
    <div style="padding:10px; background:#d4f8d4; border:1px solid #8acb8a; margin-bottom:15px;">
        🔄 Product updated successfully!
    </div>
<?php endif; ?>

<?php if (get('msg') == 'deleted'): ?>
    <div style="padding:10px; background:#d4f8d4; border:1px solid #8acb8a; margin-bottom:15px;">
        ❌ Product deleted!
    </div>
<?php endif; ?>

<!-- Add New Product Button -->
<a href="add_product.php"
   style="padding:10px 20px; background:#2c3e50; color:white; 
          border-radius:5px; text-decoration:none; margin-bottom:20px; display:inline-block;">
    ➕ Add New Product
</a>

<!-- PRODUCT GRID LIKE SHOP PAGE -->
<div class="products-grid" 
     style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px; margin-top:20px;">

<?php foreach ($products as $p): ?>

    <?php
        $folder = categoryFolder($p->category_id);

        // First image
        $imgArr = explode(",", $p->product_image);
        $firstImage = trim($imgArr[0]);
        $imgPath = "/images/product/$folder/$firstImage";
    ?>

    <div class="product-card"
         style="border:1px solid #ddd; padding:15px; border-radius:8px; background:white;">

        <img src="<?= $imgPath ?>" 
             style="width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:10px;">

        <h3 style="font-size:16px; margin:0;"><?= encode($p->product_name) ?></h3>

        <p style="margin:5px 0; color:#555;">
            Category: <strong><?= $categories[$p->category_id] ?></strong>
        </p>

        <p style="color:#333; font-weight:bold;">
            RM <?= number_format($p->product_price, 2) ?>
        </p>

        <p>Status: 
            <span style="font-weight:bold; color:<?= $p->product_status ? 'green' : 'red' ?>;">
                <?= $p->product_status ? 'Active' : 'Inactive' ?>
            </span>
        </p>

        <!-- ACTION BUTTONS -->
        <div style="margin-top:10px; display:flex; gap:10px;">
            <a href="modify_product.php?id=<?= $p->product_id ?>"
               style="flex:1; padding:8px; background:#2980b9; color:white; text-align:center; border-radius:5px; text-decoration:none;">
                ✏ Modify
            </a>

            <a href="delete_product.php?id=<?= $p->product_id ?>"
               onclick="return confirm('Are you sure you want to delete this product?');"
               style="flex:1; padding:8px; background:#c0392b; color:white; text-align:center; border-radius:5px; text-decoration:none;">
                🗑 Delete
            </a>
        </div>

    </div>

<?php endforeach; ?>
</div>

<?php include '../_foot.php'; ?>