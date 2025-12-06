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

// Malaysia states
$states = [
    "Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan",
    "Pahang", "Perak", "Perlis", "Pulau Pinang", "Sabah",
    "Sarawak", "Selangor", "Terengganu", "Kuala Lumpur",
    "Labuan", "Putrajaya"
];

// Get the address ID from GET
$address_id = $_GET['id'] ?? null;
if (!$address_id) {
    $_SESSION['error'] = "No address selected.";
    redirect("profile_address_list.php");
}

// Fetch address data
$stmt = $_db->prepare("SELECT * FROM address WHERE address_id = ? AND user_id = ?");
$stmt->execute([$address_id, $user_id]);
$addr = $stmt->fetch();

if (!$addr) {
    $_SESSION['error'] = "Address not found.";
    redirect("profile_address_list.php");
}

// Handle form submission
if (is_post()) {
    $line1 = trim($_POST['address_line1']);
    $line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postcode = trim($_POST['postcode']);
    $country = "Malaysia"; // fixed
    $is_default = isset($_POST['default_flag']) ? 1 : 0;

    if ($is_default == 1) {
        // Remove default from all previous addresses
        $_db->prepare("UPDATE address SET default_flag = 0 WHERE user_id = ?")
            ->execute([$user_id]);
    }

    // Update the address
    $update = $_db->prepare("
        UPDATE address SET 
            address_line1 = ?, 
            address_line2 = ?, 
            city = ?, 
            state = ?, 
            postcode = ?, 
            country = ?, 
            default_flag = ?
        WHERE address_id = ? AND user_id = ?
    ");

    $update->execute([
        $line1, $line2, $city, $state, $postcode, $country, $is_default,
        $address_id, $user_id
    ]);

    $_SESSION['success'] = "Address updated successfully.";
    redirect("profile_address_list.php");
}

$_title = "Edit Address | Four Eyes Collective";
include '../_head.php';
?>

<section style="padding:60px 0; background:#ecf0f1;">
<div style="max-width:700px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

    <h1 style="text-align:center; margin-bottom:30px;">Edit Address</h1>

    <form method="post">

        <div class="form-group">
            <label>Address Line 1 *</label>
            <input type="text" name="address_line1" class="form-control" required value="<?= htmlspecialchars($addr->address_line1) ?>">
        </div>

        <div class="form-group">
            <label>Address Line 2</label>
            <input type="text" name="address_line2" class="form-control" value="<?= htmlspecialchars($addr->address_line2) ?>">
        </div>

        <div class="form-group">
            <label>City *</label>
            <input type="text" name="city" class="form-control" required value="<?= htmlspecialchars($addr->city) ?>">
        </div>

        <div class="form-group">
            <label>State *</label>
            <select name="state" class="form-control" required>
                <option value="">-- Select State --</option>
                <?php foreach ($states as $s): ?>
                    <option value="<?= $s ?>" <?= $addr->state == $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Postcode *</label>
            <input type="text" name="postcode" class="form-control" required value="<?= htmlspecialchars($addr->postcode) ?>">
        </div>

        <div class="form-group">
            <label>Country *</label>
            <input type="text" name="country" class="form-control" value="Malaysia" readonly>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="default_flag" value="1" <?= $addr->default_flag ? 'checked' : '' ?>>
                Set as default address
            </label>
        </div>

        <div class="form-group">
            <button class="cta-button" type="submit" style="width:100%; margin-top:20px;">
                Update Address
            </button>
        </div>

    </form>

</div>
</section>

<?php include '../_foot.php'; ?>
