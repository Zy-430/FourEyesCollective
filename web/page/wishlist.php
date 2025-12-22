<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/category.php';

$_title = 'My Wishlist';
include '../_head.php';

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
        <a href="/page/login.php" style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none;">
            Login Now
        </a>
    </div>
<?php elseif (count($wishlistItems) == 0): ?>
    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
        <p style="font-size: 18px; color: #666;">Your wishlist is empty</p>
        <a href="/page/shoppage.php" style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none;">
            Browse Products
        </a>
    </div>
<?php else: ?>
    <div class="products-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px; margin-top:20px;">
        <?php foreach ($wishlistItems as $item): ?>
            <?php
            $folder = $categoryFolders[$item->category_id] ?? 'others';
            $imgArray = explode(',', $item->product_image);
            $firstImage = trim($imgArray[0]);
            $imgPath = "/images/product/$folder/$firstImage";
            ?>
            
            <div class="product-card" style="border:1px solid #eee; padding:20px; border-radius:8px; background:white; height:450px; display:flex; flex-direction:column;">
                
                <!-- WISHLIST BUTTON (Remove from wishlist) -->
                <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
                    <button class="wishlist-btn" data-product-id="<?= $item->product_id ?>" data-wishlist-id="<?= $item->wishlist_id ?>"
                            style="background:none; border:none; cursor:pointer; font-size:20px; color:#e74c3c;">
                        <i class="fas fa-heart"></i> <!-- Solid heart for items already in wishlist -->
                    </button>
                </div>
                
                <!-- IMAGE -->
                <img src="<?= $imgPath ?>" 
                     style="width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:10px;">
                
                <!-- NAME -->
                <div style="height:51px; overflow:hidden; margin-bottom:10px;">
                    <h3 style="font-size:17px; margin:0;"><?= encode($item->product_name) ?></h3>
                </div>
                
                <!-- PRICE -->
                <div style="font-weight:bold; margin-bottom:10px; font-size:18px;">
                    RM <?= number_format($item->product_price, 2) ?>
                </div>
                
                <!-- STOCK -->
                <div style="margin-bottom: 10px; font-size: 14px;">
                    <?php if ($item->product_stock <= 10): ?>
                        <span style="color: #c0392b; font-weight:bold;">
                            Stock: <?= number_format($item->product_stock) ?> (Selling Fast!)
                        </span>
                    <?php else: ?>
                        <span style="color: #666;">
                            Stock: <?= number_format($item->product_stock) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- BUTTONS -->
                <div style="margin-top:auto;">
                    <a href="/page/product_detail.php?id=<?= $item->product_id ?>" 
                       style="display:inline-block; margin-bottom:8px; padding:8px 15px; background:#888; color:white; border-radius:5px; text-decoration:none;">
                        View Details
                    </a>
                    
                    <a href="#" class="add-to-cart" data-product-id="<?= $item->product_id ?>" 
                       style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none; cursor:pointer;">
                        Add to Cart
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Include FontAwesome for heart icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="/js/wishlist.js"></script>
<script src="/js/notifications.js"></script>
<?php include '../_foot.php'; ?>