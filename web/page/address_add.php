<?php
require '../_base.php';
require '../lib/db.php';

auth();

$user_id = $_user->user_id;

// Get return URL with fallbacks
$return_url = get('return', 'profile_address_list.php');
// Validate return URL to prevent open redirects
$allowed_returns = ['checkout.php', 'profile_address_list.php', 'profile_page.php', 'cart.php'];
if (!in_array(basename($return_url), $allowed_returns)) {
    $return_url = 'profile_address_list.php';
}

// Count current addresses
$stmt = $_db->prepare("SELECT COUNT(*) as count FROM address WHERE user_id = ?");
$stmt->execute([$user_id]);
$address_count = $stmt->fetch()->count;

if ($address_count >= 10) {
    $_SESSION['error'] = 'You have reached the maximum of 10 saved addresses';
    redirect($return_url);
}

// Malaysia states
$states = [
    "Johor",
    "Kedah",
    "Kelantan",
    "Melaka",
    "Negeri Sembilan",
    "Pahang",
    "Perak",
    "Perlis",
    "Pulau Pinang",
    "Sabah",
    "Sarawak",
    "Selangor",
    "Terengganu",
    "Kuala Lumpur",
    "Labuan",
    "Putrajaya"
];

// Handle form submission
if (is_post()) {
    $line1 = trim($_POST['address_line1']);
    $line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postcode = trim($_POST['postcode']);
    $country = trim($_POST['country']);
    $is_default = isset($_POST['default_flag']) ? 1 : 0;
    
    // Validation
    if (empty($line1) || empty($city) || empty($state) || empty($postcode) || empty($country)) {
        $_SESSION['error'] = 'Please fill in all required fields';
        redirect('address_add.php?return=' . urlencode($return_url));
    }
    
    if ($is_default == 1) {
        // Remove default from all previous addresses
        $_db->prepare("UPDATE address SET default_flag = 0 WHERE user_id = ?")
            ->execute([$user_id]);
    }
    
    // Generate new ID
    $stmt = $_db->query("SELECT address_id FROM address WHERE address_id LIKE 'ADRS%' ORDER BY address_id DESC LIMIT 1");
    $last = $stmt->fetch();
    if ($last) {
        $num = intval(substr($last->address_id, 4)) + 1;
        if ($num > 9999) {
            $_SESSION['error'] = 'Maximum address limit reached (9999)';
            redirect($return_url);
        }
        $new_id = "ADRS" . str_pad($num, 4, "0", STR_PAD_LEFT);
    } else {
        $new_id = "ADRS0001";
    }
    
    // Insert
    $insert = $_db->prepare("
        INSERT INTO address (
            address_id, user_id, recipient_name, address_line1, address_line2,
            city, state, postcode, country, default_flag, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $recipient_name = $_user->name; // Default to user's name
    
    $insert->execute([
        $new_id,
        $user_id,
        $recipient_name,
        $line1,
        $line2,
        $city,
        $state,
        $postcode,
        $country,
        $is_default
    ]);
    
    $_SESSION['success'] = "Address added successfully.";
    redirect($return_url);
}

// Set page title based on return URL
if (strpos($return_url, 'checkout.php') !== false) {
    $_title = "Add Shipping Address | Four Eyes Collective";
} else {
    $_title = "Add New Address | Four Eyes Collective";
}

include '../_head.php';
?>

<section style="padding:60px 0; background:#ecf0f1;">
    <div style="max-width:700px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

        <h2 style="margin-bottom:25px;">
            <?= strpos($return_url, 'checkout.php') !== false ? 'Add Shipping Address' : 'Add New Address' ?>
        </h2>
        
        <?php if (strpos($return_url, 'checkout.php') !== false): ?>
            <p style="color:#666; margin-bottom:30px;">Add a new shipping address for your current order.</p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Recipient Name *</label>
                <input type="text" name="recipient_name" value="<?= encode($_user->name) ?>" required 
                       class="form-control" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px;">
            </div>
            
            <div class="form-group">
                <label>Address Line 1 *</label>
                <input type="text" name="address_line1" required placeholder="Street address, P.O. Box, company name"
                       class="form-control" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px;">
            </div>
            
            <div class="form-group">
                <label>Address Line 2</label>
                <input type="text" name="address_line2" placeholder="Apartment, suite, unit, building, floor, etc."
                       class="form-control" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px;">
            </div>
            
            <div class="form-group">
                <label>City *</label>
                <input type="text" name="city" required placeholder="City"
                       class="form-control" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px;">
            </div>
            
            <div class="form-group">
                <label>State *</label>
                <select name="state" required
                    style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px;">
                    <option value="">-- Select State --</option>
                    <?php foreach ($states as $st): ?>
                        <option value="<?= encode($st) ?>"><?= encode($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Postcode *</label>
                <input type="text" name="postcode" required placeholder="Postal code"
                       class="form-control" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px;"
                       pattern="[0-9]{5}" title="5-digit postcode">
            </div>
            
            <div class="form-group">
                <label>Country *</label>
                <input type="text" name="country" value="Malaysia" required readonly
                       class="form-control" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:15px; background:#f8f9fa;">
            </div>
            
            <div class="form-group" style="margin-top:20px;">
                <label>
                    <input type="checkbox" name="default_flag" value="1">
                    Set as Default Address
                </label>
            </div>

            <div style="margin-top:30px;">
                <button type="submit" class="btn btn-primary" style="padding:12px 30px; background:#2c3e50; color:white; border:none; border-radius:5px; cursor:pointer; margin-right:10px;">
                    Save Address
                </button>
                
                <?php if (strpos($return_url, 'checkout.php') !== false): ?>
                    <a href="checkout.php" class="btn btn-secondary" style="padding:12px 30px; background:#ecf0f1; color:#2c3e50; text-decoration:none; border-radius:5px; display:inline-block;">
                        Cancel & Return to Checkout
                    </a>
                <?php else: ?>
                    <a href="profile_address_list.php" class="btn btn-secondary" style="padding:12px 30px; background:#ecf0f1; color:#2c3e50; text-decoration:none; border-radius:5px; display:inline-block;">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>

    </div>
</section>

<?php include '../_foot.php'; ?>