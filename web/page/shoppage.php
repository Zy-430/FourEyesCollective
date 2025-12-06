<?php
require '../_base.php';
require '../lib/db.php';

$_title = 'Home | Shop';
include '../_head.php';

// Get selected category
$selectedCat = get('cat');

// Fetch products (filtered or all)
if ($selectedCat) {
    $stm = $_db->prepare("SELECT * FROM product 
                          WHERE category_id = ? AND product_status = 1");
    $stm->execute([$selectedCat]);
    $products = $stm->fetchAll();
} else {
    $products = $_db->query("SELECT * FROM product 
                             WHERE product_status = 1")->fetchAll();
}

// Category → folder mapping
function categoryFolder($catId) {
    return [
        'CA0001' => 'glasses',
        'CA0002' => 'sunglasses',
        'CA0003' => 'contactlens',
        'CA0004' => 'kids'
    ][$catId] ?? 'others';
}

// Category display names
$categoryNames = [
    'CA0001' => 'Glasses',
    'CA0002' => 'Sunglasses',
    'CA0003' => 'Contact Lens',
    'CA0004' => 'Kids'
];
?>

<h1 style="margin-bottom: 10px;">Our Eyewear Collection</h1>

<!-- CATEGORY FILTER BUTTONS -->
<div style="margin-bottom: 20px;">
    <a href="shoppage.php" 
       style="padding:8px 15px; margin-right:10px; background:<?= $selectedCat ? '#bbb' : '#2c3e50' ?>; color:white; text-decoration:none; border-radius:5px;">
       All
    </a>

    <?php foreach ($categoryNames as $id => $name): ?>
        <a href="shoppage.php?cat=<?= $id ?>" 
           style="padding:8px 15px; margin-right:10px; 
                  background:<?= ($selectedCat === $id) ? '#2c3e50' : '#888' ?>; 
                  color:white; text-decoration:none; border-radius:5px;">
            <?= $name ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- PRODUCT GRID -->
<div class="products-grid" 
     style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">
    
    <?php foreach ($products as $p): ?>

        <?php 
            $folder = categoryFolder($p->category_id);
            $imgArray = explode(',', $p->product_image);
            $firstImage = trim($imgArray[0]);
            $imgPath = "/images/product/$folder/$firstImage";
        ?>

        <div class="product-card" 
             style="border:1px solid #eee; padding:20px; border-radius:8px; 
                    background:white; height:450px; display:flex; flex-direction:column;">
            
            <!-- IMAGE -->
            <img src="<?= $imgPath ?>" 
                 style="width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:10px;">

            <!-- NAME (fixed height to align grid) -->
            <div style="height:51px; overflow:hidden; margin-bottom:10px;">
                <h3 style="font-size:17px; margin:0;"><?= encode($p->product_name) ?></h3>
            </div>

            <!-- PRICE -->
            <div class="product-price" 
                 style="font-weight:bold; margin-bottom:10px; font-size:18px;">
                RM <?= number_format($p->product_price, 2) ?>
            </div>

            <!-- BUTTONS -->
            <div style="margin-top:auto;">
                <a href="product_detail.php?id=<?= $p->product_id ?>" 
                   style="display:inline-block; margin-bottom:8px; padding:8px 15px; background:#888; color:white; border-radius:5px; text-decoration:none;">
                    View Details
                </a>

                <a href="add_to_cart.php?id=<?= $p->product_id ?>" 
                   class="add-to-cart" 
                   style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none;">
                    Add to Cart
                </a>
            </div>

        </div>

    <?php endforeach; ?>
</div>

<?php include '../_foot.php'; ?>
