<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/category.php';
require '../lib/product_stats.php';

$id = get('id');
$stm = $_db->prepare("SELECT * FROM product WHERE product_id = ?");
$stm->execute([$id]);
$p = $stm->fetch();

$folder = $categoryFolders[$p->category_id] ?? 'others';

$images = explode(',', $p->product_image);

$_title = $p->product_name;
include '../_head.php';
?>

<h1 style="margin-bottom:20px;"><?= encode($p->product_name) ?></h1>

<div style="display:flex; gap:30px;">

    <!-- LEFT: IMAGE CAROUSEL -->
    <div style="width:400px;">

        <!-- MAIN BIG IMAGE -->
        <div style="position:relative; width:400px; height:400px; overflow:hidden; border:1px solid #ccc; border-radius:8px;">
            <img id="mainImage"
                src="/images/product/<?= $folder ?>/<?= trim($images[0]) ?>"
                style="width:100%; height:100%; object-fit:cover;">

            <!-- BUTTONS -->
            <button class="carousel-prev"
                style="position:absolute; top:50%; left:10px; transform:translateY(-50%); 
                       background:black; color:white; border:none; padding:10px; border-radius:5px; cursor:pointer; opacity:0.7;">
                ❮
            </button>

            <button class="carousel-next"
                style="position:absolute; top:50%; right:10px; transform:translateY(-50%); 
                       background:black; color:white; border:none; padding:10px; border-radius:5px; cursor:pointer; opacity:0.7;">
                ❯
            </button>
        </div>

        <!-- THUMBNAILS -->
        <div style="display:flex; gap:10px; margin-top:10px;">
            <?php foreach ($images as $index => $img): ?>
                <?php $img = trim($img); ?>
                <img class="thumb" data-index="<?= $index ?>"
                    src="/images/product/<?= $folder ?>/<?= encode($img) ?>"
                    style="width:70px; height:70px; object-fit:cover; border:2px solid #ccc; border-radius:6px; cursor:pointer;"
                    id="thumb<?= $index ?>">
            <?php endforeach; ?>
        </div>

    </div>

    <!-- RIGHT: PRODUCT INFO -->
    <div style="flex:1;">
        <p style="font-size:16px; line-height:1.6; margin-bottom:20px;">
            <?= nl2br(encode($p->product_description)) ?>
        </p>

        <!-- Price -->
        <p style="margin-bottom: 30px;">
            <strong style="font-size: 18px;">Price:</strong>
            <span style="font-size:32px; color:#2c3e50; font-weight:bold;">
                RM <?= number_format($p->product_price, 2) ?>
            </span>
        </p>

        <!-- Quantity in Stock -->
        <p style="margin-bottom: 30px; font-size: 16px;">
            <strong>Quantity in Stock:</strong>
            <span style="color: <?= $p->product_stock > 10 ? '#27ae60' : '#e74c3c' ?>; font-weight:bold;">
                <?= number_format($p->product_stock) ?> left
            </span>
            <?php if ($p->product_stock <= 10): ?>
                <span style="color: #e74c3c; font-weight:bold;">(Selling Fast!)</span>
            <?php endif; ?>
        </p>

        <!-- Sold Count (if available) -->
        <?php
        $soldCount = getProductSoldCount($id);
        if ($soldCount > 0): ?>
            <p style="margin-bottom: 30px; font-size: 16px;">
                <strong>Total Sold:</strong>
                <span style="color: #2c3e50; font-weight:bold;">
                    <?= number_format($soldCount) ?> units
                </span>
            </p>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="product-actions">
            <button class="action-btn btn-add-to-cart add-to-cart"
                data-product-id="<?= $p->product_id ?>">
                <i class="fas fa-shopping-cart"></i>
                Add to Cart
            </button>

            <button class="action-btn btn-add-to-wishlist wishlist-btn"
                data-product-id="<?= $p->product_id ?>"
                onclick="toggleWishlist('<?= $p->product_id ?>', this)">
                <i class="far fa-heart"></i>
                Add to Wishlist
            </button>
        </div>
    </div>

    <!-- CAROUSEL SCRIPT -->
    <script>
        (function($) {
            let images = <?= json_encode(array_map('trim', $images)) ?>;
            let folder = "<?= $folder ?>";
            let idx = 0;

            function showImage(i) {
                idx = i;
                $('#mainImage').attr('src', '/images/product/' + folder + '/' + images[idx]);
            }

            function nextImage() {
                idx = (idx + 1) % images.length;
                showImage(idx);
            }

            function prevImage() {
                idx = (idx - 1 + images.length) % images.length;
                showImage(idx);
            }

            // DOM bindings
            $(function() {
                $(document).on('click', '.carousel-prev', function(e) {
                    e.preventDefault();
                    prevImage();
                });
                $(document).on('click', '.carousel-next', function(e) {
                    e.preventDefault();
                    nextImage();
                });
                $(document).on('click', '.thumb', function(e) {
                    e.preventDefault();
                    showImage(Number($(this).data('index')));
                });

                // Auto slideshow every 3 seconds
                setInterval(nextImage, 3000);
            });
        })(jQuery);
    </script>
    <script src="/js/notifications.js"></script>
    <?php include '../_foot.php'; ?>