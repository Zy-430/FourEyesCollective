<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
require_once '../../lib/SimplePager.php';

auth('Admin');
$admin_id = $_user->user_id;

// --- Pagination settings ---
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

// --- Search & Filter ---
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';

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

// Count total
$countSql = "
    SELECT COUNT(*)
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    JOIN `order` o ON oi.order_id = o.order_id
    JOIN users u ON o.user_id = u.user_id
    $whereSql
";
$stm = $_db->prepare($countSql);
$stm->execute($params);
$totalRecords = $stm->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

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

// Fetch review data
$dataSql = "
    SELECT oi.*, u.name AS user_name, p.product_name
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    JOIN `order` o ON oi.order_id = o.order_id
    JOIN users u ON o.user_id = u.user_id
    $whereSql
    $orderBy
    LIMIT $limit OFFSET $offset
";
$stm = $_db->prepare($dataSql);
$stm->execute($params);
$reviews = $stm->fetchAll(PDO::FETCH_ASSOC);

$_title = "Admin Review Management | Four Eyes Collective";
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Review Management</h1>
        <?php
        function sortLink($column, $label, $currentSort, $currentDir, $search, $status_filter)
        {
            $dir = 'asc';
            $arrow = '';
            if ($currentSort === $column) {
                if ($currentDir === 'asc') {
                    $dir = 'desc';
                    $arrow = ' ▲';
                } else {
                    $dir = 'asc';
                    $arrow = ' ▼';
                }
            }
            $url = "?page=1&search=" . urlencode($search) . "&status=" . $status_filter . "&sort={$column}&dir={$dir}";
            return "<a href='{$url}' style='text-decoration:none; color:inherit;'>{$label}{$arrow}</a>";
        }
        ?>
        <div class="header-actions small" style="margin-top:10px;">
            <form method="GET" style="display:flex; gap:10px; align-items:center;">
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
                    <th><?= sortLink('order_item_id', 'Review ID', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th><?= sortLink('user_name', 'User', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th><?= sortLink('product_name', 'Product', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th><?= sortLink('user_rating', 'Rating', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th>Comment</th>
                    <th>Photo</th>
                    <th>Video</th>
                    <th><?= sortLink('review_status', 'Status', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>
                    <th><?= sortLink('rated_at', 'Date', $currentSort, $currentDir, $search, $status_filter, $rating_filter) ?></th>

                    
                    
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr style="height: 60px;">
                        <td><?= $r['order_item_id'] ?></td>
                        <td><?= htmlspecialchars($r['user_name']) ?></td>
                        <td><?= htmlspecialchars($r['product_name']) ?></td>
                        <td><?= $r['user_rating'] ?></td>
                        <td><?= htmlspecialchars($r['user_comment']) ?></td>
                        <!-- Photos -->
                        <td>
                            <?php
                            $photos = [];
                            if (!empty($r['rating_photo'])) {
                                $decoded = json_decode($r['rating_photo'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $photos = array_filter(array_map('trim', $decoded));
                                } else {
                                    // fallback if stored as string
                                    $clean = trim($r['rating_photo']);
                                    $clean = preg_replace('/^\[+|\]+$/', '', $clean);
                                    $clean = str_replace(['"', "'"], '', $clean);
                                    $photos = array_filter(array_map('trim', explode(',', $clean)));
                                }
                            }

                            $videos = [];
                            if (!empty($r['rating_video'])) {
                                $decoded = json_decode($r['rating_video'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $videos = array_filter(array_map('trim', $decoded));
                                } else {
                                    $clean = trim($r['rating_video']);
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
                        <td><?= ucfirst($r['review_status']) ?></td>
                        <td><?= $r['rated_at'] ?></td>
                        <td>
                            <button class="toggle-review-btn"
                                data-id="<?= $r['order_item_id'] ?>"
                                data-status="<?= $r['review_status'] ?>">
                                <?= $r['review_status'] === 'visible' ? 'Hide' : 'Unhide' ?>
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
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?= (($page - 1) * $limit) + 1 ?> - <?= min($page * $limit, $totalRecords) ?> of <?= $totalRecords ?> reviews
                </div>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&rating=<?= $rating_filter ?>"
                            class="pagination" style=" <?= $i == $page ? 'background:#2c3e50;color:white;' : 'background:#ecf0f1;color:#333;' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
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
                    location.reload();
                } else {
                    alert(res.message || 'Action failed');
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