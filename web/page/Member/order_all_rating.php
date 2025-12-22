<?php
require '../../_base.php';
require '../../lib/db.php';
auth('Member');
$user_id = $_user->user_id;

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 3;
$offset = ($page - 1) * $limit;

// Search & rating filter
$search = trim($_GET['search'] ?? '');
$ratingFilter = (int)($_GET['rating'] ?? 0);

$where = "o.user_id = ? AND oi.user_rating IS NOT NULL";
$params = [$user_id];

if ($search) {
    $where .= " AND p.product_name LIKE ?";
    $params[] = "%$search%";
}

if ($ratingFilter) {
    $where .= " AND oi.user_rating = ?";
    $params[] = $ratingFilter;
}

// Count total items
$stm_count = $_db->prepare("
    SELECT COUNT(*) 
    FROM order_item oi
    JOIN `order` o ON oi.order_id = o.order_id
    JOIN product p ON oi.product_id = p.product_id
    WHERE $where
");
$stm_count->execute($params);
$totalItems = $stm_count->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Fetch paginated reviews
$sql = "
    SELECT oi.order_item_id, oi.order_id, oi.product_id, oi.user_rating, oi.user_comment, oi.rated_at,
           oi.rating_photo, oi.rating_video, oi.review_status,
           p.product_name, p.product_image, p.category_id,
           o.order_date
    FROM order_item oi
    JOIN `order` o ON oi.order_id = o.order_id
    JOIN product p ON oi.product_id = p.product_id
    WHERE $where
    ORDER BY oi.rated_at DESC
    LIMIT $limit OFFSET $offset
";

$stm_reviews = $_db->prepare($sql);
$stm_reviews->execute($params);
$reviews = $stm_reviews->fetchAll(PDO::FETCH_ASSOC);

// Category mapping
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categories = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR);

$_title = "My Reviews | Four Eyes Collective";
$_css = ['review.css'];
include '../../_head.php';
?>

<div class="review-container">
    <h1>My Ratings</h1>
    <div class="review-filter">
        <form method="get" action="" class="filter-form">
            <input type="text" name="search" placeholder="Search product..." value="<?= encode($search) ?>">
            <select name="rating">
                <option value="0" <?= $ratingFilter === 0 ? 'selected' : '' ?>>All Ratings</option>
                <?php for ($r = 5; $r >= 1; $r--): ?>
                    <option value="<?= $r ?>" <?= $ratingFilter === $r ? 'selected' : '' ?>><?= $r ?> Star<?= $r > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>

    <?php if (empty($reviews)): ?>
        <p class="empty-note">You haven't submitted any reviews yet.</p>
    <?php else: ?>
        <?php foreach ($reviews as $r): ?>
            <?php
            // Handle multiple product images
            $prodImages = explode(',', $r['product_image']);
            $firstProdImage = trim($prodImages[0] ?? 'placeholder.png');

            // Normalize photo/video arrays
            $photos = !empty($r['rating_photo']) ? json_decode($r['rating_photo'], true) ?? [] : [];
            $videos = !empty($r['rating_video']) ? json_decode($r['rating_video'], true) ?? [] : [];
            ?>
            <div class="review-card">
                <!-- Product Image -->
                <img class="product-image" src="/images/product/<?= encode($categories[$r['category_id']] ?? 'other') ?>/<?= encode($firstProdImage) ?>" alt="<?= encode($r['product_name']) ?>">

                <!-- Review Details -->
                <div class="review-details">
                    <div class="review-main">
                        <p class="product-name"><?= encode($r['product_name']) ?></p>
                        <?php if ($r['review_status'] === 'hidden'): ?>
                            <span class="review-pill hidden">Hidden by Admin</span>
                        <?php endif; ?>
                        
                        <p class="rating-stars">
                            <?php
                            $stars = (int)$r['user_rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $stars ? '★' : '☆';
                            }
                            ?>
                        </p>

                        <?php if ($r['user_comment']): ?>
                            <p class="review-comment">"<?= encode($r['user_comment']) ?>"</p>
                        <?php endif; ?>
                    </div>

                    <div class="review-meta">
                        <span class="order-id">Order: <?= encode($r['order_id']) ?></span>
                        <span class="rated-at">Date: <?= date('d M Y', strtotime($r['rated_at'])) ?></span>

                        <a class="view-order-link" href="/page/order_details.php?order_id=<?= encode($r['order_id']) ?>">View Order</a>

                        <!-- View modal button -->
                        <button class="view-btn viewReviewBtn"
                            data-photo='<?= htmlspecialchars(json_encode($photos), ENT_QUOTES, 'UTF-8') ?>'
                            data-video='<?= htmlspecialchars(json_encode($videos), ENT_QUOTES, 'UTF-8') ?>'
                            data-comment='<?= htmlspecialchars($r['user_comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>'>
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="margin-top:30px; display:flex; gap:8px; justify-content:center;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&rating=<?= $ratingFilter ?>"
                        style="padding:6px 12px; border-radius:4px; text-decoration:none; font-size:14px; <?= $i == $page ? 'background:#2c3e50; color:white;' : 'background:#ecf0f1; color:#333;' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="reviewModal" class="review-modal" aria-hidden="true">
    <div class="modal-box" role="dialog" aria-modal="true">
        <button id="closeModal" class="modal-close" aria-label="Close">&times;</button>
        <h3 class="modal-title">Review Details</h3>
        <div id="modalContent" class="modal-content"></div>
    </div>
</div>

<script>
    $(function() {
        const $modal = $('#reviewModal');
        const $modalContent = $('#modalContent');

        $('.viewReviewBtn').on('click', function() {
            const photos = JSON.parse($(this).attr('data-photo') || '[]');
            const videos = JSON.parse($(this).attr('data-video') || '[]');
            const comment = $(this).attr('data-comment') || '';

            let html = '';
            if (comment) html += '<p>' + $('<div/>').text(comment).html() + '</p>';

            if (photos.length) {
                html += '<h4>Photos:</h4>';
                photos.forEach(p => {
                    if (p) html += '<img class="modal-photo" src="/images/review/' + encodeURIComponent(p) + '" alt="Review photo">';
                });
            }

            if (videos.length) {
                html += '<h4>Videos:</h4>';
                videos.forEach(v => {
                    if (v) html += '<video controls class="modal-video"><source src="/images/review/' + encodeURIComponent(v) + '" type="video/mp4"></video>';
                });
            }

            if (!comment && photos.length === 0 && videos.length === 0) {
                html = '<p>No details available for this review.</p>';
            }

            $modalContent.html(html);
            $modal.css('display', 'flex').attr('aria-hidden', 'false');
        });

        $('#closeModal').on('click', function() {
            $modal.hide().attr('aria-hidden', 'true');
        });

        $(window).on('click', function(e) {
            if ($(e.target).is($modal)) $modal.hide().attr('aria-hidden', 'true');
        });
    });
</script>

<?php include '../../_foot.php'; ?>