<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';

auth('Admin');

$_title = 'Manage User';

$role = req('role', 'Member');
$user_id = get('user_id');

// Table fields 
$fields = [
    'user_id' => (strtolower($role) === 'admin') ? 'Admin ID' : 'Member ID',
    'name' => 'Name',
    'email' => 'Email',
    'gender' => 'Gender',
    'phone' => 'Phone',
    'date_of_birth' => 'Date of Birth',
    'registration_date' => 'Registration Date',
    'status' => 'Status'
];

// Filter and search parameters
$search = req('search', '');
$gender = req('gender', '');
$status = req('status', '');

// Filter options (gender & status)
$genders = [
    '' => 'All Genders',
    'M' => 'Male',
    'F' => 'Female',
    'N' => 'Prefer not to say'
];

$statuses = [
    '' => 'All Status',
    'Active' => 'Active',
    'Inactive' => 'Inactive'
];

// Sorting
$sort = req('sort');
key_exists($sort, $fields) || $sort = 'user_id';

$dir = req('dir');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

// Pagination
$page = req('page', 1);
require_once '../../lib/SimplePager.php';

// Build query 
$where = [];
$params = [];

// Role filter and exclude current user
$where[] = "role = ?";
$params[] = $role;
$where[] = "user_id != ?";
$params[] = $_user->user_id;

// Search condition
if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR user_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, array_fill(0, 4, $search_param));
}

// Gender filter
if ($gender !== '') {
    $where[] = "gender = ?";
    $params[] = $gender;
}

// Status filter
if ($status !== '') {
    $where[] = "status = ?";
    $params[] = $status;
}

// Combine final query
$where_clause = $where ? implode(' AND ', $where) : '1';
$query = "SELECT * FROM users WHERE $where_clause ORDER BY $sort $dir";

// Execute result with pagination
$p = new SimplePager($query, $params, 8, $page);
$member = $p->result;

$query_params = [];

if ($search) {
    $query_params[] = "search=" . urlencode($search);
}

if ($gender !== '') {
    $query_params[] = "gender=$gender";
}

if ($status !== '') {
    $query_params[] = "status=" . urlencode($status);
}

$query_params[] = "sort=$sort";
$query_params[] = "dir=$dir";

$query_string = implode('&', $query_params);

// Store notification message 
$notification_message = '';
$notification_type = 'success';

if (get('msg') == 'added') {
    $notification_message = $role . ' (' . $user_id . ') added successfully!';
} elseif (get('msg') == 'updated') {
    $notification_message = $role . ' (' . $user_id . ') updated successfully!';
} elseif (get('msg') == 'deleted') {
    $notification_message = $role . ' (' . $user_id . ') blocked successfully!';
} elseif (get('msg') == 'restored') {
    $notification_message = $role . ' (' . $user_id . ') unblocked successfully!';
} elseif (get('msg') == 'added_no_email') {
    $notification_message = $role . ' (' . $user_id . ') added but email sending failed!';
    $notification_type = 'error';
} elseif (get('error') == 'last_admin') {
    $notification_message = 'Cannot block the last active admin!';
    $notification_type = 'error';
} elseif (get('error') == 'user_not_found') {
    $notification_message = 'User not found!';
    $notification_type = 'error';
}
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title"><?= $role ?> Management </h1>
        <div class="header-actions small" style="margin-top:10px;">
            <form method="GET" style="display:flex; gap:10px; align-items:center;">

                <!-- Preserve role so admin and member can use same folder -->
                <input type="hidden" name="role" value="<?= $role ?>">

                <!-- Search -->
                <input type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by ID, Name, Email"
                    style="padding:8px; width:260px; border-radius:5px; border:1px solid #ccc;">

                <!-- Status filter -->
                <select name="status"
                    style="padding:8px; border-radius:5px; border:1px solid #ccc;">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <!-- Gender filter -->
                <select name="gender"
                    style="padding:8px; border-radius:5px; border:1px solid #ccc;">
                    <?php foreach ($genders as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $gender === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <!-- Preserve sorting -->
                <input type="hidden" name="sort" value="<?= $sort ?>">
                <input type="hidden" name="dir" value="<?= $dir ?>">
                <input type="hidden" name="page" value="1">

                <!-- Filter button -->
                <button type="submit" class="btn-default btn-add">
                    <i class="fas fa-filter"></i>Filter
                </button>

                <!-- Clear button -->
                <button type="button"
                    class="btn-default btn-clear"
                    onclick="location.href='?role=<?= $role ?>'">
                    <i class="fas fa-eraser"></i>Clear
                </button>

                <!-- Add button -->
                <button type="button"
                    class="btn-default btn-add"
                    onclick="location.href='add_user.php?role=<?= $role ?>'">
                    <i class="fas fa-plus"></i> Add
                </button>

            </form>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-small">
            <tr>
                <th>Actions</th>
                <?= table_headers($fields, $sort, $dir, "role=$role&page=$page") ?>
            </tr>

            <?php foreach ($member as $u): ?>

                <?php
                // First image
                $imgArr = explode(",", $u->photo);
                $firstImage = trim($imgArr[0]);
                $imgPath = "/images/users/$firstImage";
                ?>

                <tr>
                    <td class="actions-row">
                        <div class="action-buttons">
                            <!-- Modify user button -->
                            <a href="modify_user.php?user_id=<?= $u->user_id ?>" class="btn-default edit-btn">
                                <i class="fas fa-edit"></i>
                            </a>
                            <!-- Display button based on status (Active: delete ; Inactive: restore) -->
                            <?php if ($u->status == "Active"): ?>
                                <form method="post" action="delete_user.php" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?= $u->user_id ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="role" value="<?= $role ?>">
                                    <button type="submit"
                                        onclick="return confirm('Are you sure you want to block <?= $role ?> (<?= $u->user_id ?>) ?');"
                                        class="btn-default delete-btn">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="delete_user.php" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?= $u->user_id ?>">
                                    <input type="hidden" name="role" value="<?= $role ?>">
                                    <input type="hidden" name="action" value="restore">

                                    <button type="submit"
                                        onclick="return confirm('Are you sure you want to unblock <?= $role ?> (<?= $u->user_id ?>) ?');"
                                        class="btn-default restore-btn">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                </form>
                            <?php endif ?>
                        </div>
                    </td>
                    <td><?= $u->user_id ?></td>
                    <!-- Show user name and profile photo (click the photo can enlarge it) -->
                    <td class="name-container">
                        <img src="../../images/users/<?= $firstImage ?>" alt="<?= htmlspecialchars($u->name) ?>"
                            class="member-avatar clickable-photo" onclick="openPhotoModal(this.src)">
                        <?= $u->name ?>
                    </td>
                    <td style="max-width: 150px;"><?= $u->email ?></td>
                    <td><?= $u->gender ?></td>
                    <td><?= $u->phone ?></td>
                    <td><?= $u->date_of_birth ?></td>
                    <td><?= $u->registration_date ?></td>
                    <!-- Badge to show user status -->
                    <td>
                        <span class="status-badge status-<?= strtolower($u->status) ?>">
                            <?= $u->status ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>

        <!-- Model used for photo preview -->
        <div id="photoModal" class="photo-preview-modal" onclick="closePhotoModal()">
            <span class="close">&times;</span>
            <img class="photo-content" id="modalImg">
        </div>
    </div>
    <!-- show pagination if page more than 1-->
    <?php if ($p->page_count > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?= (($page - 1) * 8) + 1 ?> - <?= min($page * 8, $p->item_count) ?> of <?= $p->item_count ?> members
            </div>
            <div class="pagination">
                <?= $p->html($query_string) ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
    // Show notification on page load if there's a message
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($notification_message): ?>
            showNotification('<?= addslashes($notification_message) ?>', '<?= $notification_type ?>');
        <?php endif; ?>
    });
</script>
</body>
</html>