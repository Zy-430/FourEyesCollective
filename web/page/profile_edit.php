<?php
require '../_base.php';
require '../lib/db.php';

auth('Admin', 'Member');

$user_id = $_user->user_id;

// Fetch user
$stmt = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit();
}

// Lock rules
$gender_locked = !empty($user->gender);
$dob_locked = !empty($user->date_of_birth);

if (is_post()) {
    $errors = [];

    // Name: only letters and spaces, min 2 characters
    $name = trim(req('name'));
    if ($name === '') {
        $errors[] = "Name is required.";
    } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {
        $errors[] = "Name can only contain letters and spaces (no numbers or special characters).";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters.";
    }

    // Email
    $email = trim(req('email'));
    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format. Example: user@example.com";
    } else {
        $stmtCheck = $_db->prepare("SELECT COUNT(*) FROM users WHERE email=? AND user_id!=?");
        $stmtCheck->execute([$email, $user_id]);
        if ($stmtCheck->fetchColumn() > 0) {
            $errors[] = "This email is already in use by another account.";
        }
    }

    // Phone
    $phone = trim(req('phone'));
    if ($phone === '') {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[1-9][0-9]{7,9}$/', $phone)) {
        $errors[] = "Invalid phone number. Must be 8-10 digits without leading 0. Example: 12345678";
    }

    // Gender
    if (!$gender_locked) {
        $gender = req('gender');
        if (!in_array($gender, ['M', 'F'])) {
            $errors[] = "Please select a valid gender: Male (M) or Female (F).";
        }
    } else {
        $gender = $user->gender;
    }

    // DOB
    if (!$dob_locked) {
        $dob = req('date_of_birth');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $errors[] = "Invalid date of birth format. Please use the calendar selector (YYYY-MM-DD).";
        }
    } else {
        $dob = $user->date_of_birth;
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        redirect('profile_edit.php');
    }

    // Update user
    $update = $_db->prepare("UPDATE users SET name=?, email=?, phone=?, gender=?, date_of_birth=? WHERE user_id=?");
    $update->execute([$name, $email, $phone, $gender, $dob, $user_id]);

    $_SESSION['success'] = "Profile updated successfully.";
    redirect('profile_page.php');
}

$_title = "Edit Profile | Four Eyes Collective";
// Determine which header/footer and CSS to use based on role
if ($_user->role === 'Admin') {
    include '../_admin_head.php'; // Admin header
} else {
    include '../_head.php'; // Member header
}
?>

<section class="profile-section">
    <div class="profile-card compact-card">
        <h1 class="compact-title">Edit Profile</h1>
        <form method="post" class="compact-form">

            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user->name) ?>" pattern="[A-Za-z ]+" title="Only letters and spaces, minimum 2 characters">
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user->email) ?>" title="Enter a valid email address, e.g., user@example.com">
            </div>

            <div class="form-group">
                <label>Phone (+60) *</label>
                <div style="display:flex; gap:10px;">
                    <span style="background:#ecf0f1;padding:12px 15px;border-radius:8px;">+60</span>
                    <input type="text" name="phone" class="form-control" style="flex:1;" pattern="[1-9][0-9]{7,9}" required value="<?= ltrim($user->phone, '0') ?>" title="8-10 digits without leading 0. Example: 12345678">
                </div>
            </div>

            <div class="form-group">
                <label>Gender <?= $gender_locked ? '(Locked)' : '*' ?></label>
                <?php if ($gender_locked): ?>
                    <input type="text" class="form-control disabled" value="<?= $user->gender ?>" disabled>
                <?php else: ?>
                    <select name="gender" class="form-control" required title="Select gender: Male (M) or Female (F)">
                        <option value="">Select gender</option>
                        <option value="M" <?= $user->gender == 'M' ? 'selected' : '' ?>>Male</option>
                        <option value="F" <?= $user->gender == 'F' ? 'selected' : '' ?>>Female</option>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Date of Birth <?= $dob_locked ? '(Locked)' : '*' ?></label>
                <?php if ($dob_locked): ?>
                    <input type="text" class="form-control disabled" value="<?= $user->date_of_birth ?>" disabled>
                <?php else: ?>
                    <input type="date" name="date_of_birth" class="form-control" required value="<?= $user->date_of_birth ?>" title="Select date from the calendar">
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button class="cta-button primary" type="submit">Save</button>
                <a href="profile_page.php" class="cta-button secondary">Cancel</a>
            </div>
        </form>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="../js/notifications.js"></script>

<script>
    $(function() {
        <?php if (!empty($_SESSION['error'])): ?>
            showNotification("<?= addslashes($_SESSION['error']) ?>", "error");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            showNotification("<?= addslashes($_SESSION['success']) ?>", "success");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    });
</script>

<?php 
// Conditionally include footer based on role
if ($_user->role === 'Admin') {
    // Admin pages don't have a footer file, just close the HTML
    echo '</body></html>';
} else {
    include '../_foot.php'; // Member footer
}
?>