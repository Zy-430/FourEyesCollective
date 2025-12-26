<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
require_once '../../lib/SimplePager.php';

auth('Admin');
$admin_id = $_user->user_id;

// --- Search & Filter ---
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';

// Sorting
$currentSort = $_GET['sort'] ?? 'rated_at';
$currentDir  = $_GET['dir'] ?? 'desc';

$allowedSorts = [
    'order_item_id' => 'oi.order_item_id',
    'user_name'     => 'u.name',
    'product_name'  => 'p.product_name',
    'user_rating'   => 'oi.user_rating',
    'review_status' => 'oi.review_status',
    'rated_at'      => 'oi.rated_at'
];

if (!array_key_exists($currentSort, $allowedSorts)) $currentSort = 'rated_at';
if (!in_array(strtolower($currentDir), ['asc', 'desc'])) $currentDir = 'desc';

$orderBy = "ORDER BY {$allowedSorts[$currentSort]} $currentDir";

// Build base query
$where = [];
$params = [];

// Filter by review_status
if ($status_filter !== 'all') {
    $where[] = "review_status = ?";
    $params[] = $status_filter;
}

// Filter by rating
if ($rating_filter !== 'all') {
    $where[] = "user_rating = ?";
    $params[] = $rating_filter;
}

// Search by product name, user comment
if ($search !== '') {
    $where[] = "(oi.user_comment LIKE ? OR p.product_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Build base SQL
$baseSql = "
    SELECT oi.*, u.name AS user_name, p.product_name
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    JOIN `order` o ON oi.order_id = o.order_id
    JOIN users u ON o.user_id = u.user_id
    $whereSql
    $orderBy
";

// Initialize SimplePager
$page = max(1, (int)($_GET['page'] ?? 1));
$p = new SimplePager($baseSql, $params, 5, $page); // 5 reviews per page
$reviews = $p->result;

// Build query string for pagination links
$query_params = [];

if ($search !== '') {
    $query_params[] = "search=" . urlencode($search);
}

if ($status_filter !== 'all') {
    $query_params[] = "status=" . urlencode($status_filter);
}

if ($rating_filter !== 'all') {
    $query_params[] = "rating=" . urlencode($rating_filter);
}

$query_params[] = "sort=" . urlencode($currentSort);
$query_params[] = "dir=" . urlencode($currentDir);

$query_string = implode('&', $query_params);

$_title = "Admin Review | Four Eyes Collective";
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Review Management</h1>
        <?php
        function sortLink($column, $label, $currentSort, $currentDir, $search, $status_filter, $rating_filter)
        {
            $dir = 'asc';
            $class = '';

            if ($currentSort === $column) {
                if ($currentDir === 'asc') {
                    $class = 'asc';  
                    $dir = 'desc';
                } else {
                    $class = 'desc'; 
                    $dir = 'asc';
                }
            }

            $url = "?page=1"
                . "&search=" . urlencode($search)
                . "&status=" . urlencode($status_filter)
                . "&rating=" . urlencode($rating_filter)
                . "&sort={$column}"
                . "&dir={$dir}";

            return "<a href='{$url}' class='{$class}'>{$label}</a>";
        }

        ?>
        <div class="header-actions small" style="margin-top:10px;">
            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                <!-- Preserve sorting and page -->
                <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($currentDir) ?>">
                <input type="hidden" name="page" value="1">
                
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Product or Comment" style="padding:8px; width:300px; border-radius:5px; border:1px solid #ccc;">
                <select name="status" style="padding:8px; border-radius:5px; border:1px solid #ccc;">
                    <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="visible" <?= $status_filter == 'visible' ? 'selected' : '' ?>>Visible</option>
                    <option value="hidden" <?= $status_filter == 'hidden' ? 'selected' : '' ?>>Hidden</option>
                </select>
                <select name="rating" style="padding:8px; border-radius:5px; border:1px solid #ccc;">
                    <option value="all" <?= $rating_filter == 'all' ? 'selected' : '' ?>>All Ratings</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $rating_filter == (string)$i ? 'selected' : '' ?>><?= $i ?> Stars</option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-default btn-add"><i class="fas fa-filter"></i>Filter</button>

                <!-- Clear button -->
                <button type="button" class="btn-default btn-clear" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>'">
                    <i class="fas fa-eraser"></i> Clear
                </button>
            </form>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-small">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= sortLink('order_item_id', 'Review ID', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th style="width: 10%;"><?= sortLink('user_name', 'User', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th style="width: 14%;"><?= sortLink('product_name', 'Product', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th style="width: 10%;"><?= sortLink('user_rating', 'Rating', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th style="width: 13%;">Comment</th>
                    <th style="width: 7%;">Photo</th>
                    <th style="width: 7%;">Video</th>
                    <th style="width: 9%;"><?= sortLink('review_status', 'Status', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th style="width: 9%;"><?= sortLink('rated_at', 'Date', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr class="review_tr">
                        <td><?= $r->order_item_id ?></td>
                        <td><?= htmlspecialchars($r->user_name) ?></td>
                        <td><?= htmlspecialchars($r->product_name) ?></td>
                        <td><?= $r->user_rating ?></td>
                        <td><?= htmlspecialchars($r->user_comment) ?></td>
                        <!-- Photos -->
                        <td>
                            <?php
                            $photos = [];
                            if (!empty($r->rating_photo)) {
                                $decoded = json_decode($r->rating_photo, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $photos = array_filter(array_map('trim', $decoded));
                                } else {
                                    // fallback if stored as string
                                    $clean = trim($r->rating_photo);
                                    $clean = preg_replace('/^\[+|\]+$/', '', $clean);
                                    $clean = str_replace(['"', "'"], '', $clean);
                                    $photos = array_filter(array_map('trim', explode(',', $clean)));
                                }
                            }

                            $videos = [];
                            if (!empty($r->rating_video)) {
                                $decoded = json_decode($r->rating_video, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $videos = array_filter(array_map('trim', $decoded));
                                } else {
                                    $clean = trim($r->rating_video);
                                    $clean = preg_replace('/^\[+|\]+$/', '', $clean);
                                    $clean = str_replace(['"', "'"], '', $clean);
                                    $videos = array_filter(array_map('trim', explode(',', $clean)));
                                }
                            }
                            ?>

                            <?php foreach ($photos as $photo):
                                $photoPath = '/images/review/' . htmlspecialchars($photo); ?>
                                <img src="<?= $photoPath ?>" data-src="<?= $photoPath ?>"
                                    class="admin-media-photo" style="width:50px;height:50px;object-fit:cover;border-radius:4px;margin:2px;cursor:pointer;">
                            <?php endforeach; ?>

                        </td>

                        <!-- Videos -->
                        <td>
                            <?php foreach ($videos as $video):
                                $videoPath = '/images/review/' . htmlspecialchars($video); ?>
                                <video width="80" height="50" data-src="<?= $videoPath ?>" class="admin-media-video" style="object-fit:cover; margin:2px; cursor:pointer;">
                                    <source src="<?= $videoPath ?>" type="video/mp4">
                                </video>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $r->review_status ?>">
                            <?= ucfirst($r->review_status) ?></span>
                        </td>
                        <td><?= $r->rated_at ?></td>
                        <td>
                            <button class="btn-default toggle-review-btn"
                                data-id="<?= $r->order_item_id ?>"
                                data-status="<?= $r->review_status ?>">
                                <?= $r->review_status === 'visible' ? '<i class="fas fa-eye-slash">' : '<i class="fas fa-eye">' ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Media Modal -->
        <div id="adminMediaModal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
            <div style="position:relative; max-width:90%; max-height:90%;">
                <button id="adminCloseModalBtn" style="position:absolute; top:-40px; right:0; background:white; 
            border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; font-size:20px;">✕</button>
                <img id="adminModalImage" style="max-width:100%; max-height:100%; display:none; border-radius:8px;">
                <video id="adminModalVideo" style="max-width:100%; max-height:100%; display:none; border-radius:8px;" controls></video>
            </div>
        </div>


        <!-- Pagination -->
        <?php if ($p->page_count > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?= (($page - 1) * 5) + 1 ?> - <?= min($page * 5, $p->item_count) ?> of <?= $p->item_count ?> reviews
                </div>
                <div class="pagination">
                    <?= $p->html($query_string) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.toggle-review-btn').click(function() {
            var id = $(this).data('id');
            var status = $(this).data('status');
            var newStatus = status === 'visible' ? 'hidden' : 'visible';

            if (!confirm('Are you sure you want to ' + (newStatus === 'hidden' ? 'hide' : 'unhide') + ' this review?')) return;

            $.post('admin_review_hide.php', {
                order_item_id: id,
                action: newStatus === 'hidden' ? 'hide' : 'unhide'
            }, function(res) {
                if (res.status === 'success') {
                    // Update the button state and status badge in-place without reloading
                    var $btn = $('.toggle-review-btn[data-id="' + id + '"]');
                    var $row = $btn.closest('tr');
                    var updatedStatus = res.review_status || newStatus;

                    // Update data-status
                    $btn.data('status', updatedStatus);
                    $btn.attr('data-status', updatedStatus);

                    // Update status badge text and class
                    var $badge = $row.find('.status-badge');
                    $badge.removeClass('status-visible status-hidden').addClass('status-' + updatedStatus).text(updatedStatus.charAt(0).toUpperCase() + updatedStatus.slice(1));

                    // Update button icon
                    var iconHtml = updatedStatus === 'visible' ? '<i class="fas fa-eye-slash">' : '<i class="fas fa-eye">';
                    $btn.html(iconHtml);

                    // Show success notification
                    if (typeof showNotification === 'function') {
                        var msg = updatedStatus === 'hidden' ? 'Review (' + id + ') hidden successfully' : 'Review (' + id + ') made visible successfully';
                        showNotification(msg, 'success');
                    } else {
                        alert(updatedStatus === 'hidden' ? 'Review (' + id + ') hidden successfully' : 'Review (' + id + ') made visible successfully');
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(res.message || 'Action failed', 'error');
                    } else {
                        alert(res.message || 'Action failed');
                    }
                }
            }, 'json');
        });
    });

    $(function() {
        const $modal = $('#adminMediaModal');
        const $modalImage = $('#adminModalImage');
        const $modalVideo = $('#adminModalVideo');

        // Photo click
        $(document).on('click', '.admin-media-photo', function() {
            const src = $(this).data('src');
            $modalVideo.hide().attr('src', '');
            $modalImage.attr('src', src).show();
            $modal.fadeIn().css('display', 'flex');
        });

        // Video click
        $(document).on('click', '.admin-media-video', function() {
            const src = $(this).data('src');
            $modalImage.hide().attr('src', '');
            $modalVideo.attr('src', src).show()[0].play();
            $modal.fadeIn().css('display', 'flex');
        });

        // Close modal button
        $('#adminCloseModalBtn').on('click', function() {
            closeAdminModal();
        });

        // Click outside content
        $modal.on('click', function(e) {
            if (e.target === this) closeAdminModal();
        });

        function closeAdminModal() {
            $modal.fadeOut();
            $modalImage.hide().attr('src', '');
            $modalVideo.hide().attr('src', '').each(function() {
                this.pause();
            });
        }
    });
</script>
