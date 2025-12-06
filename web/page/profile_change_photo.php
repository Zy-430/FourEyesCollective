<?php
require '../_base.php';
require '../lib/db.php';

// TEMPORARY: simulate login
$_SESSION['user_id'] = 'ME0001';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

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
    $allowed = ['jpg','jpeg','png','gif'];
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
include '../_head.php';

// Display current photo or default
$current_photo = (!empty($user->photo) && file_exists('../images/users/' . $user->photo))
                 ? $user->photo 
                 : 'default.jpg';
?>

<section style="padding:60px 0; background:#ecf0f1;">
<div style="
    max-width:500px;
    margin:auto;
    background:white;
    padding:40px;
    border-radius:14px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    text-align:center;
">

<h1 style="font-size:2em; font-family:'Playfair Display', serif; margin-bottom:30px; color:#2c3e50;">
    Change Profile Photo
</h1>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?= encode($error) ?></p>
<?php endif; ?>

<img id="photo_preview" src="../images/users/<?= encode($current_photo) ?>?t=<?= time() ?>" 
    style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:20px;">

<form method="post" enctype="multipart/form-data">
    <input type="file" name="profile_photo" accept="image/*" id="photo_input" required 
        style="width:100%; padding:12px 15px; border:1px solid #ccc; border-radius:8px; margin-bottom:20px;">
    <br>
    <button type="submit" style="
        background:#27ae60;
        color:white;
        padding:10px 20px;
        border-radius:6px;
        border:none;
        cursor:pointer;
        font-size:1em;
    ">Upload</button>
    <a href="profile_page.php" style="
        background:#34495e;
        color:white;
        padding:10px 20px;
        border-radius:6px;
        text-decoration:none;
        font-size:1em;
        margin-left:10px;
    ">Back</a>
</form>

<script>
    const input = document.getElementById("photo_input");
    const preview = document.getElementById("photo_preview");

    input.addEventListener("change", function() {
        const file = this.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = e => preview.src = e.target.result;
            reader.readAsDataURL(file);
        }
    });
</script>

<script>
    alert("<?= $success ?>");
    window.location.href = "profile_page.php";
</script>

</div>
</section>

<?php include '../_foot.php'; ?>
