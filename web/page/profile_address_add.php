<?php
require '../_base.php';
require '../lib/db.php';

// TEMPORARY: simulate login for testing
$_SESSION['user_id'] = 'ME0001';

// Check login
if (!isset($_SESSION['user_id'])) {
    //header("Location: login.php");
    //exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $_db->prepare("SELECT * FROM address WHERE user_id = ?");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();
$total_addresses = count($addresses);

if ($total_addresses >= 10){
    $_SESSION['error'] = 'You have reached maximum length of saved address';
    redirect('profile_address_list.php');
}

// Malaysia states
$states = [
    "Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan",
    "Pahang", "Perak", "Perlis", "Pulau Pinang", "Sabah",
    "Sarawak", "Selangor", "Terengganu", "Kuala Lumpur",
    "Labuan", "Putrajaya"
];

// Handle form submission
if (is_post()){
    $line1 = trim($_POST['address_line1']);
    $line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postcode = trim($_POST['postcode']);
    $country = trim($_POST['country']);
    $is_default = isset($_POST['default_flag']) ? 1 : 0;

    if ($is_default == 1) {
        // Remove default from all previous addresses
        $_db->prepare("UPDATE address SET default_flag = 0 WHERE user_id = ?")
            ->execute([$user_id]);
    }

    // Generate new ID (AD0001)
    $stmt = $_db->query("SELECT address_id FROM address ORDER BY address_id DESC LIMIT 1");
    $last = $stmt->fetch();
    if ($last) {
        $num = intval(substr($last->address_id, 2)) + 1;
        $new_id = "AD" . str_pad($num, 6, "0", STR_PAD_LEFT);
    } else {
        $new_id = "AD0001"; // First address
    }

    // Insert
    $insert = $_db->prepare("
        INSERT INTO address (
            address_id, user_id, address_line1, address_line2,
            city, state, postcode, country, default_flag
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insert->execute([
        $new_id, $user_id, $line1, $line2,
        $city, $state, $postcode, $country, $is_default
    ]);

    $_SESSION['success'] = "Address added successfully.";
    redirect("profile_address.php");
}

$_title= "Add New Address";
include '../head.php';

?>



<section style="padding:60px 0; background:#ecf0f1;">
<div style="max-width:700px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

<h2 style="margin-bottom:25px;">Add New Address</h2>

<form method="post">

    <label>Address Line 1 *</label>
    <input name="address_line1" required class="form-input">

    <label>Address Line 2</label>
    <input name="address_line2" class="form-input">

    <label>City *</label>
    <input name="city" required class="form-input">

    <label>State</label>
        <select name="state" required
            style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px;">
            <option value="">-- Select State --</option>
            <?php foreach ($states as $st): ?>
                <option value="<?= encode($st) ?>"><?= encode($st) ?></option>
            <?php endforeach; ?>
        </select>

    <label>Postcode *</label>
    <input name="postcode" required class="form-input">

    <label>Country</label>
    <input name="country" value="Malaysia" required class="form-input">

    <label style="margin-top:20px;">
        <input type="checkbox" name="default_flag"> Set as Default Address
    </label>

    <br><br>

    <button type="submit" class="btn btn-primary">Save</button>
    <a href="profile_address.php" class="btn btn-secondary">Cancel</a>

</form>

</div>
</section>
<?php include '../_foot.php'; ?>
