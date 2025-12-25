<?php
require '../_base.php';
require '../lib/db.php';

auth();

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
    $success = '';

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

        // Generate unique filename based on user info
        $sanitized_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($user->name));
        if (empty($sanitized_name)) {
            $sanitized_name = 'user';
        }

        $filename = '';

        // If current photo is NOT user_default.jpg, keep the same filename
        if ($user->photo && $user->photo !== 'user_default.jpg') {
            // Replace extension if needed
            $current_ext = pathinfo($user->photo, PATHINFO_EXTENSION);
            if (strtolower($current_ext) !== $ext) {
                // If extension changed, create new filename
                $counter = 1;
                do {
                    $filename = $sanitized_name . sprintf('_%02d', $counter) . '.' . $ext;
                    $file_path = $upload_dir . $filename;
                    $counter++;
                } while (file_exists($file_path) && $counter <= 99);
            } else {
                // Keep same filename
                $filename = $user->photo;
            }
        } else {
            // If current photo is user_default.jpg, then create new unique filename (avoid replace defualt user image)
            $counter = 1;
            do {
                $filename = $sanitized_name . sprintf('_%02d', $counter) . '.' . $ext;
                $file_path = $upload_dir . $filename;
                $counter++;
            } while (file_exists($file_path) && $counter <= 99);
        }

        $file_path = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Update database
            $updateStmt = $_db->prepare("UPDATE users SET photo = ? WHERE user_id = ?");
            if ($updateStmt->execute([$filename, $user_id])) {
                $success = "Profile photo updated successfully!";
            } else {
                $error = "Failed to update database.";
            }
        } else {
            $error = "Failed to upload file.";
        }
    }
}

$_title = "Change Profile Photo | Four Eyes Collective";
// Determine which header/footer and CSS to use based on role
if ($_user->role === 'Admin') {
    include '../_admin_head.php'; // Admin header
} else {
    $_css = ['profile.css'];
    include '../_head.php'; // Member header
}

// Display current photo or default
$current_photo = (!empty($user->photo) && file_exists('../images/users/' . $user->photo))
    ? $user->photo
    : 'user_default.jpg';
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

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/js/notifications.js"></script>
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