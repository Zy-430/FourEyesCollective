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

// Handle upload
if (is_post() && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];

    // Validate
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $error = "File too large. Maximum 2MB.";
    } else {
        $upload_dir = '../images/users/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        // Determine filename
        if (empty($user->photo)) {
            $filename = 'user_' . $user_id . '.' . $ext;
            $_db->prepare("UPDATE users SET photo = ? WHERE user_id = ?")
                ->execute([$filename, $user_id]);
        } else {
            $filename = $user->photo;
        }

        $file_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $success = "Profile photo updated successfully!";
        } else {
            $error = "Failed to upload file.";
        }
    }
}

$_title = "Change Profile Photo | Four Eyes Collective";
if ($_user->role === 'Admin') {
    include '../_admin_head.php'; // Admin header
} else {
    $_css = ['profile.css'];
    include '../_head.php'; // Member header
}

// Display current photo or default
$current_photo = (!empty($user->photo) && file_exists('../images/users/' . $user->photo))
    ? $user->photo
    : 'default.jpg';
?>

<section class="profile-section">
    <div class="profile-card narrow-card centered-card">
        <h1 class="centered-title">Change Profile Photo</h1>

        <?php if (!empty($error)): ?>
            <div class="alert error">
                <?= encode($error) ?>
            </div>
        <?php endif; ?>

        <img id="photo_preview" src="../images/users/<?= encode($current_photo) ?>?t=<?= time() ?>" class="avatar-preview">

        <form method="post" enctype="multipart/form-data" id="upload_form">
            <div id="drop_zone" class="drop-zone">Drag & Drop your photo here or click to select</div>
            <input type="file" name="profile_photo" accept="image/*" id="photo_input" required style="display:none;">
            <div class="form-actions">
                <button type="submit" class="cta-button upload-primary">Upload</button>
                <a href="profile_page.php" class="cta-button secondary">Back</a>
            </div>
        </form>
    </div>
</section>

<script>
    $(document).ready(function() {
        var $input = $('#photo_input');
        var $preview = $('#photo_preview');
        var $dropZone = $('#drop_zone');

        // Click to select file
        $dropZone.on('click', function() {
            $input.click();
        });

        // File input change
        $input.on('change', function() {
            if (this.files[0]) previewFile(this.files[0]);
        });

        // Drag & drop
        $dropZone.on('dragover', function(e) {
            e.preventDefault();
            $(this).css({
                borderColor: '#27ae60',
                background: '#ecf9f1'
            });
        });

        $dropZone.on('dragleave', function(e) {
            $(this).css({
                borderColor: '#ccc',
                background: '#fff'
            });
        });

        $dropZone.on('drop', function(e) {
            e.preventDefault();
            $(this).css({
                borderColor: '#ccc',
                background: '#fff'
            });
            var file = e.originalEvent.dataTransfer.files[0];
            if (file) {
                $input[0].files = e.originalEvent.dataTransfer.files; // assign file to input
                previewFile(file);
            }
        });

        function previewFile(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $preview.attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }

        <?php if (!empty($success)): ?>
            if (typeof showNotification === 'function') showNotification('<?= $success ?>', 'success');
            setTimeout(function() {
                window.location.href = 'profile_page.php';
            }, 1200);
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