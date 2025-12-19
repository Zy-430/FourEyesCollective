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

// Determine which field is being edited
$edit = $_GET['edit'] ?? '';

// Update individual fields
if (is_post()) {

    // Update Name
    if (isset($_POST['save_name'])) {
        $name = req('name');
        if ($name != '') {
            $_db->prepare("UPDATE users SET name = ? WHERE user_id = ?")
                ->execute([$name, $user_id]);
        }
        redirect('profile_edit.php');
    }

    // Update Email
    if (isset($_POST['save_email'])) {
        $email = req('email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_db->prepare("UPDATE users SET email = ? WHERE user_id = ?")
                ->execute([$email, $user_id]);
        }
        redirect('profile_edit.php');
    }

    // Update Phone (stored WITHOUT 0)
    if (isset($_POST['save_phone'])) {
        $phone = req('phone'); // e.g. "123456789"
        if (preg_match('/^[1-9][0-9]{7,9}$/', $phone)) {
            $_db->prepare("UPDATE users SET phone = ? WHERE user_id = ?")
                ->execute([$phone, $user_id]);
        }
        redirect('profile_edit.php');
    }

    // Update Gender (only once)
    if (!$gender_locked && isset($_POST['save_gender'])) {
        $gender = req('gender');
        $_db->prepare("UPDATE users SET gender = ? WHERE user_id = ?")
            ->execute([$gender, $user_id]);
        redirect('profile_edit.php');
    }

    // Update DOB (only once)
    if (!$dob_locked && isset($_POST['save_dob'])) {
        $dob = req('date_of_birth');
        $_db->prepare("UPDATE users SET date_of_birth = ? WHERE user_id = ?")
            ->execute([$dob, $user_id]);
        redirect('profile_edit.php');
    }
}

$_title = "Edit Profile | Four Eyes Collective";
$_css = ['profile.css'];
include '../_head.php';
?>

<section class="profile-section">
    <div class="profile-card large-card">

        <h1 class="edit-title">Edit Profile</h1>

        <?php
        function inputBox($content)
        {
            return '<div class="input-row">' . $content . '</div>';
        }

        // replace inline style variables with class attributes (UI only)
        $btnEdit = 'class="btn-edit"';
        $btnCancel = 'class="btn-cancel"';
        $btnSave = 'class="btn-save"';
        $inputStyle = 'class="form-control"';
        ?>

        <!-- NAME -->
        <?= inputBox(
            '
    <strong>Name</strong><br>' .

                ($edit === 'name' ?
                    '<form method="post" class="mini-form">
        <input type="text" name="name" value="' . encode($user->name) . '" required ' . $inputStyle . '>
        <br><br>
        <button type="submit" name="save_name" ' . $btnSave . '>Save</button>
        <a href="profile_edit.php" ' . $btnCancel . '>Cancel</a>
    </form>'
                    :
                    encode($user->name) . ' <a href="?edit=name" ' . $btnEdit . '>Edit</a>'
                )
        ) ?>

        <!-- EMAIL -->
        <?= inputBox(
            '
    <strong>Email</strong><br>' .

                ($edit === 'email' ?
                    '<form method="post" class="mini-form">
        <input type="email" name="email" value="' . encode($user->email) . '" required ' . $inputStyle . '>
        <br><br>
        <button type="submit" name="save_email" ' . $btnSave . '>Save</button>
        <a href="profile_edit.php" ' . $btnCancel . '>Cancel</a>
    </form>'
                    :
                    encode($user->email) . ' <a href="?edit=email" ' . $btnEdit . '>Edit</a>'
                )
        ) ?>

        <!-- PHONE -->
        <?php $phone_display = ltrim($user->phone, '0'); ?>
        <?= inputBox(
            '
    <strong>Phone (+60)</strong><br>' .

                ($edit === 'phone' ?
                    '<form method="post" class="mini-form">
        <div style="display:flex; gap:10px;">
            <span style="
                background:#ecf0f1;
                padding:12px 15px;
                border-radius:8px;
            ">+60</span>

            <input type="text" name="phone" value="' . encode($phone_display) . '"
                pattern="[1-9][0-9]{7,9}" required ' . $inputStyle . ' style="flex:1;">
        </div>
        <br>
        <button type="submit" name="save_phone" ' . $btnSave . '>Save</button>
        <a href="profile_edit.php" ' . $btnCancel . '>Cancel</a>
    </form>'
                    :
                    '+60 ' . encode($phone_display) . ' <a href="?edit=phone" ' . $btnEdit . '>Edit</a>'
                )
        ) ?>

        <!-- GENDER -->
        <?= inputBox(
            '
    <strong>Gender</strong><br>' .

                ($gender_locked ?
                    encode($user->gender) . ' <span style="color:#7f8c8d;">(Locked)</span>'
                    : ($edit === 'gender' ?
                        '<form method="post" class="mini-form">
                <select name="gender" ' . $inputStyle . '>
                    <option value="Male" ' . ($user->gender == 'Male' ? 'selected' : '') . '>Male</option>
                    <option value="Female" ' . ($user->gender == 'Female' ? 'selected' : '') . '>Female</option>
                </select><br><br>
                <button type="submit" name="save_gender" ' . $btnSave . '>Save</button>
                <a href="profile_edit.php" ' . $btnCancel . '>Cancel</a>
            </form>'
                        :
                        encode($user->gender ?: "Not set") . ' <a href="?edit=gender" ' . $btnEdit . '>Edit</a>'
                    )
                )
        ) ?>

        <!-- DOB -->
        <?= inputBox(
            '
    <strong>Date of Birth</strong><br>' .

                ($dob_locked ?
                    strtoupper(date("M-d-Y", strtotime($user->date_of_birth))) . ' 
        <span style="color:#7f8c8d;">(Locked)</span>'
                    : ($edit === 'dob' ?
                        '<form method="post" class="mini-form">
                <input type="date" name="date_of_birth"
                    value="' . $user->date_of_birth . '" required ' . $inputStyle . '>
                <br><br>
                <button type="submit" name="save_dob" ' . $btnSave . '>Save</button>
                <a href="profile_edit.php" ' . $btnCancel . '>Cancel</a>
            </form>'
                        : ($user->date_of_birth
                            ? strtoupper(date("M-d-Y", strtotime($user->date_of_birth)))
                            : "Not set"
                        ) . ' <a href="?edit=dob" ' . $btnEdit . '>Edit</a>'
                    )
                )
        ) ?>

        <div class="center-actions">
            <a href="profile_page.php" class="cta-button secondary">Back to Profile</a>
        </div>

    </div>
</section>

<?php include '../_foot.php'; ?>