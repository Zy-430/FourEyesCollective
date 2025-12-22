<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');

$user_id = $_user->user_id;

// get return URL with fallbacks
$return_url = get('return', 'profile_address_list.php');
$return_url = trim($return_url);

// Normalize path and strip query/fragment
$path = parse_url($return_url, PHP_URL_PATH) ?: $return_url;
$base = basename($path);

// Validate return URL to prevent open redirects
$allowed_returns = ['checkout.php', 'profile_address_list.php', 'profile_page.php', 'cart.php'];
if (!in_array($base, $allowed_returns)) {
    $return_url = 'profile_address_list.php';
} else {
    // Keep just the base filename (no query strings)
    $return_url = $base;
} 

// Count current addresses
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
    redirect("$return_url");
}

$_title = "Add New Address | Four Eyes Collective";
$_css = ['profile.css'];
include '../../_head.php';

?>

<section class="profile-section">
<div class="profile-card">

    <h1 class="centered-title">Add New Address</h1>

    <form method="post">
        <input type="hidden" name="return" value="<?= htmlspecialchars($return_url) ?>">

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
            <select class="form-control disabled" disabled style="pointer-events:none;">
                <option value="Malaysia" selected>Malaysia</option>
            </select>
            <input type="hidden" name="country" value="Malaysia">
        </div>

        <div class="form-group checkbox-group">
            <label>
                <input type="checkbox" name="default_flag" value="1">
                Set as default address
            </label>
        </div>

        <div class="form-group">
            <button class="cta-button full-width" type="submit">
                Add Address
            </button>

            <?php if ($return_url === 'checkout.php'): ?>
                <a href="checkout.php" class="cta-button secondary full-width" style="margin-top:10px; text-align:center;">
                    Back to Checkout
                </a>
            <?php else: ?>
                <a href="profile_address_list.php" class="cta-button secondary full-width" style="margin-top:10px;text-align:center;">
                    Back to Address List
                </a>
            <?php endif; ?>
        </div>

    </form>

</div>
</section>
<?php include '../../_foot.php'; ?>