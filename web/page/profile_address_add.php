<?php
require '../_base.php';
require '../lib/db.php';

auth();

$user_id = $_user->user_id;

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

    // Generate new ID (ADRS0001)
    $stmt = $_db->query("SELECT address_id FROM address ORDER BY address_id DESC LIMIT 1");
    $last = $stmt->fetch();
    if ($last) {
        $num = intval(substr($last->address_id, 4)) + 1;
        $new_id = "ADRS" . str_pad($num, 4, "0", STR_PAD_LEFT);
    } else {
        $new_id = "ADRS0001"; // First address
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
    redirect("profile_address_list.php");
}

$_title = "Add New Address | Four Eyes Collective";
include '../_head.php';

?>

<section style="padding:60px 0; background:#ecf0f1;">
<div style="max-width:700px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

    <h1 style="text-align:center; margin-bottom:30px;">Add New Address</h1>

    <form method="post">

        <div class="form-group">
            <label>Address Line 1 *</label>
            <input type="text" name="address_line1" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Address Line 2</label>
            <input type="text" name="address_line2" class="form-control">
        </div>

        <div class="form-group">
            <label>City *</label>
            <input type="text" name="city" class="form-control" required>
        </div>

        <div class="form-group">
            <label>State *</label>
            <select name="state" class="form-control" required>
                <option value="">-- Select State --</option>
                <?php foreach ($states as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Postcode *</label>
            <input type="text" name="postcode" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Country *</label>
            <select class="form-control" disabled style="pointer-events:none; background:#f1f1f1;">
                <option value="Malaysia" selected>Malaysia</option>
            </select>
            <input type="hidden" name="country" value="Malaysia">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="default_flag" value="1">
                Set as default address
            </label>
        </div>

        <div class="form-group">
            <button class="cta-button" type="submit" style="width:100%; margin-top:20px;">
                Add Address
            </button>
        </div>

    </form>

</div>
</section>
<?php include '../_foot.php'; ?>
