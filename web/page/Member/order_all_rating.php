<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');
$user_id = $_user->user_id;

// Fetch all rated order items for this user
$stm = $_db->prepare("
    SELECT oi.order_item_id, oi.order_id, oi.product_id, oi.user_rating, oi.user_comment, oi.rated_at,
           oi.rating_photo, oi.rating_video,
           p.product_name, p.product_image, p.category_id,
           o.order_date
    FROM order_item oi
    JOIN `order` o ON oi.order_id = o.order_id
    JOIN product p ON oi.product_id = p.product_id
    WHERE o.user_id = ?
      AND oi.user_rating IS NOT NULL
    ORDER BY oi.rated_at DESC
");
$stm->execute([$user_id]);
$reviews = $stm->fetchAll(PDO::FETCH_ASSOC);

// Fetch category folder mapping dynamically
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categories = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR);

$_title = "My Reviews | Four Eyes Collective";
$_css = ['review.css'];
include '../../_head.php';
?>

<div class="review-container">
    <h1>My Rating</h1>

    <?php if (empty($reviews)): ?>
        <p class="empty-note">You haven't submitted any reviews yet.</p>
    <?php else: ?>
        <?php foreach ($reviews as $r): ?>
            <?php
                // Normalize photo/video fields to JSON arrays for safe use in data-attributes
                $photos = [];
                $videos = [];

                if (!empty($r['rating_photo'])) {
                    $decoded = json_decode($r['rating_photo'], true);
                    if (is_array($decoded)) {
                        $photos = $decoded;
                    } else {
                        // fallback if stored as comma-separated string
                        $photos = array_values(array_filter(array_map('trim', explode(',', $r['rating_photo']))));
                    }
                }

                if (!empty($r['rating_video'])) {
                    $decoded = json_decode($r['rating_video'], true);
                    if (is_array($decoded)) {
                        $videos = $decoded;
                    } else {
                        $videos = array_values(array_filter(array_map('trim', explode(',', $r['rating_video']))));
                    }
                }
            ?>
            <div class="review-card">
                <!-- Product Image -->
                <img class="product-image" src="/images/product/<?= encode($categories[$r['category_id']] ?? 'other') ?>/<?= encode($r['product_image']) ?>" alt="<?= encode($r['product_name']) ?>">

                <!-- Review Details -->
                <div class="review-details">
                    <div class="review-main">
                        <p class="product-name"><?= encode($r['product_name']) ?></p>
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

                        <!-- data attributes use JSON-encoded arrays (escaped) so JS can reliably parse -->
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
$(function () {

    const $modal = $('#reviewModal');
    const $modalContent = $('#modalContent');

    $('.viewReviewBtn').on('click', function () {
        // read raw attribute (avoid jQuery .data() automatic parsing differences across versions)
        const rawPhotos = $(this).attr('data-photo') || '[]';
        const rawVideos = $(this).attr('data-video') || '[]';
        const comment = $(this).attr('data-comment') || '';

        let photos = [];
        let videos = [];

        // Try to parse JSON encoded arrays; fallback to comma-split if parsing fails
        try {
            const parsed = JSON.parse(rawPhotos);
            if (Array.isArray(parsed)) photos = parsed;
        } catch (e) {
            photos = rawPhotos.split(',').map(s => s.trim()).filter(Boolean);
        }

        try {
            const parsedV = JSON.parse(rawVideos);
            if (Array.isArray(parsedV)) videos = parsedV;
        } catch (e) {
            videos = rawVideos.split(',').map(s => s.trim()).filter(Boolean);
        }

        let html = '';

        if (comment) {
            // safely escape
            html += '<p>' + $('<div/>').text(comment).html() + '</p>';
        }

        if (photos.length) {
            html += '<h4>Photos:</h4>';
            photos.forEach(p => {
                if (!p) return;
                html += '<img class="modal-photo" src="/images/review/' + encodeURIComponent(p) + '" alt="Review photo">';
            });
        }

        if (videos.length) {
            html += '<h4>Videos:</h4>';
            videos.forEach(v => {
                if (!v) return;
                html += '<video controls class="modal-video">' +
                        '<source src="/images/review/' + encodeURIComponent(v) + '" type="video/mp4">' +
                        '</video>';
            });
        }

        if (!comment && photos.length === 0 && videos.length === 0) {
            html = '<p>No details available for this review.</p>';
        }

        $modalContent.html(html);

        // ensure modal is shown and centered using flex
        $modal.css('display', 'flex').attr('aria-hidden', 'false');
    });

    $('#closeModal').on('click', function () {
        $modal.hide().attr('aria-hidden', 'true');
    });

    $(window).on('click', function (e) {
        if ($(e.target).is($modal)) {
            $modal.hide().attr('aria-hidden', 'true');
        }
    });

});
</script>

<?php include '../../_foot.php'; ?>