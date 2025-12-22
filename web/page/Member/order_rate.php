<?php
require '../../_base.php';
require '../../lib/db.php';
auth('Member');

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) exit("Invalid order");


// Fetch items in the order that are not yet rated (include category for image folder)
$stm_items = $_db->prepare("
    SELECT oi.order_item_id, p.product_name, p.product_image, p.category_id
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ? AND oi.user_rating IS NULL
");
$stm_items->execute([$order_id]);
$items = $stm_items->fetchAll(PDO::FETCH_ASSOC);

// Build category -> folder map for product images
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categoryFolders = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR);

$allRated = empty($items);

$_title = "Rate Your Order | Four Eyes Collective";
$_css = ['order.css', 'rate.css'];
include '../../_head.php';
?>

<div style="max-width:900px;margin:40px auto;">

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

                        <input type="file" class="rating-photo" accept="image/*" multiple>
                    </div>

                    <button class="submitRate btn-submit">Submit</button>

                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {

        // Handle case where all items are already rated
        <?php if ($allRated): ?>
            $(function() {
                if (typeof showNotification === 'function') {
                    showNotification('All items have already been rated.', 'success');
                }
                setTimeout(function() {
                    window.location.href = '/page/Member/order_history.php';
                }, 2000);
            });
        <?php endif; ?>

        // Star rating & submit handling
        $('.rate-card').each(function() {
            var $card = $(this);
            var selectedRating = 0;
            var $stars = $card.find('.star');

            // Click on stars
            $stars.on('click', function() {
                selectedRating = $(this).data('value');
                $stars.each(function() {
                    $(this).text($(this).data('value') <= selectedRating ? '★' : '☆');
                });
            });

            // Submit rating
            $card.find('.submitRate').on('click', function() {
                var $btn = $(this);
                var orderItemId = $card.data('order-item-id');
                var comment = $card.find('.comment').val();
                var photos = $card.find('.rating-photo')[0].files;

                if (selectedRating === 0) {
                    if (typeof showNotification === 'function') showNotification('Please select a star rating.', 'error');
                    return;
                }

                $btn.prop('disabled', true).text('Submitting...');

                var formData = new FormData();
                formData.append('order_item_id', orderItemId);
                formData.append('user_rating', selectedRating);
                formData.append('user_comment', comment);
                $.each(photos, function(i, file) {
                    formData.append('rating_photo[]', file);
                });

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
                                if (typeof showNotification === 'function') showNotification('Thank you for rating your order!', 'success');
                                window.location.href = '/page/Member/order_history.php';
                            } else {
                                if (typeof showNotification === 'function') showNotification('Rating saved. Please rate remaining items.', 'success');
                                location.reload();
                            }
                        } else {
                            $btn.prop('disabled', false).text('Submit');
                            if (typeof showNotification === 'function') showNotification(data.message || 'Something went wrong.', 'error');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('Submit');
                        if (typeof showNotification === 'function') showNotification('Something went wrong.', 'error');
                    }
                });
            });
        });

    });
</script>

<?php include '../../_foot.php'; ?>