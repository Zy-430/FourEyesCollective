<?php
require '../../_base.php';
require '../../lib/db.php';
require '../../lib/category.php';
require '../../lib/product_stats.php';

auth('Member');

$_title = 'My Wishlist | Four Eyes Collective';
include '../../_head.php';

// Get all wishlist items for the current user
$userId = $_user->user_id ?? '';
$wishlistItems = [];

if ($_user) {
    $stm = $_db->prepare("
        SELECT w.*, p.*, c.category_name, c.folder 
        FROM wishlist w 
        JOIN product p ON w.product_id = p.product_id 
        JOIN category c ON p.category_id = c.category_id 
        WHERE w.user_id = ? AND w.removed_at IS NULL 
        ORDER BY w.added_at DESC
    ");
    $stm->execute([$userId]);
    $wishlistItems = $stm->fetchAll();
}
?>

<h1>My Wishlist</h1>

<?php if (!$_user): ?>
    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
        <p style="font-size: 18px; color: #666;">Please login to view your wishlist</p>
        <a href="../login.php" style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none;">
            Login Now
        </a>
    </div>
<?php elseif (count($wishlistItems) == 0): ?>
    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
        <p style="font-size: 18px; color: #666;">Your wishlist is empty</p>
        <a href="../shoppage.php" style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none;">
            Browse Products
        </a>
    </div>
<?php else: ?>
    <div class="product-grid" style="grid-template-columns: repeat(4, 1fr);
">
        <?php foreach ($wishlistItems as $item): ?>
            <?php
            $folder = $categoryFolders[$item->category_id] ?? 'others';
            $imgArray = explode(',', $item->product_image);
            $firstImage = trim($imgArray[0]);
            $imgPath = "/images/product/$folder/$firstImage";

            // Get sold count for product
            $soldCount = getProductSoldCount($item->product_id);
            ?>
            <a href="../product_detail.php?id=<?= $item->product_id ?>&return_url=/page/Member/wishlist.php" class="product-card">
                <!-- Image Container -->
                <div class="product-image-container">
                    <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>">


                    <button class="product-heart-btn wishlist-btn"
                        data-product-id="<?= $item->product_id ?>"
                        onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist('<?= $item->product_id ?>', this);"
                        style="opacity: 1; color: black; border: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Product Info -->
                <div class="product-info">
                    <!-- Category -->
                    <div class="product-category">
                        <?= encode($item->category_name) ?>
                    </div>

                    <!-- Product Name -->
                    <h3 class="product-name">
                        <?= encode($item->product_name) ?>
                    </h3>

                    <!-- Price & Sold -->
                    <div class="product-footer">
                        <div class="product-price">
                            RM <?= number_format($item->product_price, 2) ?>
                        </div>
                        <div class="product-sold">
                            <span class="number"><?= number_format($soldCount) ?></span> sold
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php include '../../_foot.php'; ?>