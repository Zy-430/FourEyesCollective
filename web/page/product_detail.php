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
$rawImages = array_filter(array_map('trim', explode(',', $p->product_image)));
$images = $rawImages;

// If no images → fallback to placeholder
if (empty($images)) {
    $images = ['no-image.png'];
    $folder = ''; // no folder needed for fallback
}

$return_url = get('return_url', '/page/homepage.php');
$return_url = trim($return_url);

// Normalize path and strip query/fragment
$path = parse_url($return_url, PHP_URL_PATH) ?: $return_url;

// Validate return URL to prevent open redirects
$allowed_returns = ['/page/homepage.php', '/page/shoppage.php', '/page/Member/wishlist.php'];
if (!in_array($path, $allowed_returns)) {
    $return_url = '/page/homepage.php';
} else {
    // Use the full validated path
    $return_url = $path;
}

// Fetch reviews
$stm_reviews = $_db->prepare("
    SELECT oi.*, o.user_id, u.name, u.photo
    FROM order_item oi
    JOIN `order` o ON oi.order_id = o.order_id
    JOIN users u ON o.user_id = u.user_id
    WHERE oi.product_id = ? 
    AND oi.user_rating IS NOT NULL
    AND oi.review_status = 'visible'
    ORDER BY oi.rated_at DESC
");
$stm_reviews->execute([$id]);
$reviews = $stm_reviews->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $total_rating = array_sum(array_column($reviews, 'user_rating'));
    $avg_rating = round($total_rating / $total_reviews, 1);
}

$_title = $p->product_name;
include '../_head.php';
?>
<!-- BACK BUTTON -->
<div style="margin-top:20px;margin-bottom: 5px;">
    <a href="<?= encode($return_url) ?>" style="text-decoration: none; color: #2c3e50; font-size: 16px; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fa fa-long-arrow-left"></i>
        <span>Back to 
            <?php 
            switch($return_url) {
                case '/page/homepage.php':
                    echo 'Homepage';
                    break;
                case '/page/shoppage.php':
                    echo 'Shop';
                    break;
                case '/page/Member/wishlist.php':
                    echo 'Wishlist';
                    break;
                default:
                    echo 'Homepage';
            }
            ?>
        </span>
    </a>
</div>

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
            <button type="button" class="action-btn btn-add-to-cart add-to-cart"
                data-product-id="<?= $p->product_id ?>">
                <i class="fas fa-shopping-cart"></i>
                Add to Cart
            </button>

            <button type="button" class="action-btn btn-add-to-wishlist wishlist-btn"
                data-product-id="<?= $p->product_id ?>">
                <i class="far fa-heart"></i>
                Add to Wishlist
            </button>
        </div>
    </div>

</div>

<!-- REVIEWS SECTION -->
<div style="margin-top:50px; padding:30px; background:#f8f9fa; border-radius:10px;">
    <h2 style="margin-bottom:20px; font-size:24px; color:#2c3e50;">Customer Reviews</h2>

    <?php if ($total_reviews > 0): ?>
        <div style="display:flex; align-items:center; margin-bottom:30px; padding:15px; background:white; border-radius:8px;">
            <div style="text-align:center; margin-right:30px;">
                <div style="font-size:48px; font-weight:bold; color:#f39c12;"><?= $avg_rating ?></div>
                <div style="color:#7f8c8d; margin-bottom:8px;">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <span style="color:<?= $i < floor($avg_rating) ? '#f39c12' : '#ddd'; ?> ;font-size:18px;">★</span>
                    <?php endfor; ?>
                </div>
                <div style="color:#666; font-size:14px;">Based on <?= $total_reviews ?> review<?= $total_reviews !== 1 ? 's' : '' ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($total_reviews > 0): ?>
        <div id="reviewsList" style="display:flex; flex-direction:column; gap:15px;">
            <?php foreach ($reviews as $review):
                $photos = [];
                if (!empty($review['rating_photo'])) {
                    $decoded = json_decode($review['rating_photo'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $photos = array_filter(array_map('trim', $decoded));
                    } else {
                        $clean = trim($review['rating_photo']);
                        $clean = preg_replace('/^\[+|\]+$/', '', $clean);
                        $clean = str_replace(['"', "'"], '', $clean);
                        $photos = array_filter(array_map('trim', explode(',', $clean)));
                    }
                }
                $videos = [];
                if (!empty($review['rating_video'])) {
                    $decoded = json_decode($review['rating_video'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $videos = array_filter(array_map('trim', $decoded));
                    }
                }

            ?>
                <div class="review-item" style="background:white; padding:20px; border-radius:8px; border-left:4px solid #f39c12;">
                    <div style="display:flex; align-items:center; margin-bottom:15px;">
                        <img src="<?= !empty($review['photo']) ? '/images/users/' . encode($review['photo']) : '/images/default-avatar.jpg' ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:10px;">
                        <div>
                            <div style="font-weight:bold; color:#2c3e50;"><?= encode($review['name']) ?></div>
                            <div style="font-size:12px; color:#7f8c8d;"><?= date('d M Y', strtotime($review['rated_at'])) ?></div>
                        </div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $review['user_rating']): ?>
                                <span style="color:#f39c12;">★</span>
                            <?php else: ?>
                                <span style="color:#ccc;">☆</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                    </div>
                    <div style="color:#333; line-height:1.6; margin-bottom:15px;"><?= nl2br(encode($review['user_comment'])) ?></div>
                    <div class="review-media" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                        <?php foreach ($photos as $photo):
                            $photoPath = '/images/review/' . encode($photo); ?>
                            <div class="media-photo" data-src="<?= $photoPath ?>" style="position:relative; width:80px; height:80px; border-radius:6px; overflow:hidden; cursor:pointer;">
                                <img src="<?= $photoPath ?>" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($videos as $video):
                            $videoPath = '/images/review/' . encode($video); ?>
                            <div class="media-video" data-src="<?= $videoPath ?>" style="position:relative; width:80px; height:80px; border-radius:6px; overflow:hidden; background:#000; cursor:pointer;">
                                <video style="width:100%; height:100%; object-fit:cover;">
                                    <source src="<?= $videoPath ?>" type="video/mp4">
                                </video>
                                <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:24px; color:white;">▶</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:30px; color:#7f8c8d;">
            <p>No reviews yet. Be the first to review this product!</p>
        </div>
    <?php endif; ?>
</div>

<div id="mediaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
    <div style="position:relative; max-width:90%; max-height:90%;">
        <button id="closeModalBtn" style="position:absolute; top:-40px; right:0; background:white; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:20px;">✕</button>
        <img id="modalImage" style="max-width:100%; max-height:100%; display:none; border-radius:8px;">
        <video id="modalVideo" style="max-width:100%; max-height:100%; display:none; border-radius:8px;" controls></video>
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

<script>
    $(function() {
        const $modal = $('#mediaModal');
        const $modalImage = $('#modalImage');
        const $modalVideo = $('#modalVideo');

        // Photo click
        $(document).on('click', '.media-photo', function() {
            const src = $(this).data('src');

            $modalVideo.hide().attr('src', '');
            $modalImage.attr('src', src).show();

            $modal.fadeIn().css('display', 'flex');
        });

        // Video click
        $(document).on('click', '.media-video', function() {
            const src = $(this).data('src');

            $modalImage.hide().attr('src', '');
            $modalVideo.attr('src', src).show()[0].play();

            $modal.fadeIn().css('display', 'flex');
        });

        // Close modal
        $('#closeModalBtn').on('click', function() {
            closeModal();
        });

        // Click outside content to close
        $modal.on('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        function closeModal() {
            $modal.fadeOut();
            $modalImage.hide().attr('src', '');
            $modalVideo.hide().attr('src', '').each(function() {
                this.pause();
            });
        }
    });
</script>

<script src="/js/notifications.js"></script>
<?php include '../_foot.php'; ?>