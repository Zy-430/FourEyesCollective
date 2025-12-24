<?php
require '../../_base.php';
require '../../lib/db.php';
auth('Member');

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) exit("Invalid order");

// Fetch items not yet rated
$stm_items = $_db->prepare("
    SELECT oi.order_item_id, p.product_name, p.product_image, p.category_id
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ? AND oi.user_rating IS NULL
");
$stm_items->execute([$order_id]);
$items = $stm_items->fetchAll(PDO::FETCH_ASSOC);

// Category -> folder mapping
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categoryFolders = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR);

$allRated = empty($items);

$_title = "Rate Your Order | Four Eyes Collective";
$_css = ['order.css', 'review.css'];
include '../../_head.php';
?>

<div style="max-width:900px;margin:40px auto;">
    <a href="javascript:history.back()" style="text-decoration: none; color: #2c3e50; font-size: 16px; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <div class="rate-page">
        <h2>Rate Your Order</h2>
        <p>Select an item and give your rating:</p>

        <div id="rateItems" class="rate-items">
            <?php foreach ($items as $item): ?>
                <div class="rate-card" data-order-item-id="<?= encode($item['order_item_id']) ?>">
                    <?php
                    $folder = encode($categoryFolders[$item['category_id']] ?? 'other');
                    $images = explode(',', $item['product_image']);
                    $firstImage = trim(encode($images[0] ?? 'placeholder.png'));
                    ?>
                    <img src="/images/product/<?= $folder ?>/<?= $firstImage ?>" alt="<?= encode($item['product_name']) ?>" class="rate-thumb">

                    <div class="rate-body">
                        <p class="rate-title"><?= encode($item['product_name']) ?></p>

                        <div class="stars" aria-hidden="true">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star" data-value="<?= $i ?>">☆</span>
                            <?php endfor; ?>
                        </div>

                        <textarea class="comment form-control" placeholder="Leave a comment..." rows="3"></textarea>

                        <div class="file-upload-section" style="margin-top: 10px;">
                            <label for="file-upload-<?= $item['order_item_id'] ?>" class="file-upload-btn" >
                                <i class="fas fa-camera"></i> Choose Photos/Videos
                            </label>
                            <input type="file" id="file-upload-<?= $item['order_item_id'] ?>" class="rating-media" accept="image/*,video/*" multiple style="display: none;"> 
                            <small class="text-muted">Max 5 photos & videos per item. Images ≤ 5MB, Videos ≤ 5MB</small>
                        </div>

                        <div class="file-preview"></div>
                    </div>

                    <button class="submitRate view-btn" style="margin-top: -6%;">Submit</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/js/notifications.js"></script>

<script>
    $(function() {
        <?php if ($allRated): ?>
            showNotification('All items have already been rated.', 'success');
            setTimeout(() => window.location.href = '/page/Member/order_history.php', 2000);
        <?php endif; ?>

        $('.rate-card').each(function() {
            var $card = $(this);
            var selectedRating = 0;
            var selectedFiles = []; // Track all selected files
            var $stars = $card.find('.star');
            var $fileInput = $card.find('.rating-media');
            var $filePreview = $card.find('.file-preview');

            // Star click
            $stars.on('click', function() {
                selectedRating = $(this).data('value');
                $stars.each(function() {
                    $(this).text($(this).data('value') <= selectedRating ? '★' : '☆');
                });
            });

            // File selection
            $fileInput.on('change', function(e) {
                const files = Array.from(e.target.files);

                files.forEach(f => {
                    const ext = f.name.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
                    const isVideo = ['mp4', 'mov', 'webm'].includes(ext);

                    if (!isImage && !isVideo) {
                        showNotification('Invalid file type. Only images/videos allowed.', 'error');
                        return;
                    }

                    if (f.size > 5 * 1024 * 1024) {
                        showNotification('File size must be less than 5MB', 'error');
                        return;
                    }

                    // Total count limit (images + videos <= 5)
                    if (selectedFiles.length >= 5) {
                        showNotification('Maximum 5 files allowed per item.', 'error');
                        return;
                    }

                    selectedFiles.push({
                        file: f,
                        type: isImage ? 'image' : 'video',
                        name: f.name
                    });
                });

                renderPreview();
                $fileInput.val(''); // clear input to allow adding more
            });


            function renderPreview() {
                $filePreview.empty();
                selectedFiles.forEach((f, i) => {
                    const fileTypeIcon = f.type === 'image' ? '🖼️' : '🎬';
                    $filePreview.append('<div class="preview-item">' + fileTypeIcon + ' ' + f.name +
                        ' <span class="remove-file" data-index="' + i + '">&times;</span></div>');
                });

                // Remove file
                $filePreview.find('.remove-file').on('click', function() {
                    const idx = $(this).data('index');
                    selectedFiles.splice(idx, 1);
                    renderPreview();
                });
            }

            // Submit
            $card.find('.submitRate').on('click', function() {
                if (selectedRating === 0) {
                    showNotification('Please select a star rating.', 'error');
                    return;
                }
                var comment = $card.find('.comment').val();
                var $btn = $(this);
                $btn.prop('disabled', true).text('Submitting...');

                var formData = new FormData();
                formData.append('order_item_id', $card.data('order-item-id'));
                formData.append('user_rating', selectedRating);
                formData.append('user_comment', comment);

                selectedFiles.forEach(f => formData.append('rating_media[]', f.file));

                $.ajax({
                    url: '/page/Member/order_rate_submit.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            if (data.allRated) {
                                showNotification('Thank you for rating your order!', 'success');
                                window.location.href = '/page/Member/order_history.php';
                            } else {
                                showNotification('Rating saved. Please rate remaining items.', 'success');
                                location.reload();
                            }
                        } else {
                            $btn.prop('disabled', false).text('Submit');
                            showNotification(data.message || 'Something went wrong.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        $btn.prop('disabled', false).text('Submit');
                        console.error('AJAX Error:', error);
                        showNotification('Something went wrong. Please try again.', 'error');
                    }
                });
            });
        });
    });
</script>

<?php include '../../_foot.php'; ?>