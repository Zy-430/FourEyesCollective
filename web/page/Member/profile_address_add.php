<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');

$user_id = $_user->user_id;

$states = ["Johor","Kedah","Kelantan","Melaka","Negeri Sembilan","Pahang","Perak","Perlis",
           "Pulau Pinang","Sabah","Sarawak","Selangor","Terengganu","Kuala Lumpur","Labuan","Putrajaya"];

// Count existing addresses
$stmt = $_db->prepare("SELECT COUNT(*) FROM address WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_addresses = $stmt->fetchColumn();
if($total_addresses >= 10){
    $_SESSION['error'] = "You have reached the maximum of 10 addresses.";
    redirect('profile_address_list.php');
}

if(is_post()){
    $recipient_name = trim($_POST['recipient_name']);
    $line1 = trim($_POST['address_line1']);
    $line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postcode = trim($_POST['postcode']);
    $country = "Malaysia";
    $is_default = isset($_POST['default_flag']) ? 1 : 0;

    if(!preg_match('/^\d{5}$/', $postcode)){
        $_SESSION['error'] = "Invalid postcode format. Must be 5 digits.";
        redirect('profile_address_add.php');
    }

    try {
        $_db->beginTransaction();

        // If new address is default, remove previous defaults
        if($is_default){
            $_db->prepare("UPDATE address SET default_flag=0 WHERE user_id=?")
                 ->execute([$user_id]);
        } else {
            // If no default exists yet, make this one default
            $stmt = $_db->prepare("SELECT COUNT(*) FROM address WHERE user_id=? AND default_flag=1");
            $stmt->execute([$user_id]);
            if($stmt->fetchColumn() == 0){
                $is_default = 1;
            }
        }

        // Generate new ID
        $stmt = $_db->query("SELECT address_id FROM address ORDER BY address_id DESC LIMIT 1");
        $last = $stmt->fetch();
        $new_id = $last ? "ADRS".str_pad(intval(substr($last->address_id,4))+1,4,"0",STR_PAD_LEFT) : "ADRS0001";

        // Insert
        $insert = $_db->prepare("
            INSERT INTO address (address_id,user_id,recipient_name,address_line1,address_line2,city,state,postcode,country,default_flag)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");
        $insert->execute([$new_id,$user_id,$recipient_name,$line1,$line2,$city,$state,$postcode,$country,$is_default]);

        $_db->commit();
        $_SESSION['success'] = "Address added successfully.";
        redirect('profile_address_list.php');
    } catch(Exception $e){
        $_db->rollBack();
        $_SESSION['error'] = "Failed to add address.";
    }
}

$_title = "Add New Address | Four Eyes Collective";
$_css = ['profile.css'];
include '../../_head.php';
?>

<section class="profile-section">
<div class="profile-card compact-card">
    <h1 class="compact-title">Add New Address</h1>
    <form method="post" class="compact-form">

        <div class="form-group">
            <label>Recipient Name *</label>
            <input type="text" name="recipient_name" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Address Line 1 *</label>
            <input type="text" name="address_line1" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Address Line 2</label>
            <input type="text" name="address_line2" class="form-control">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>City *</label>
                <input type="text" name="city" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Postcode *</label>
                <input type="text" name="postcode" class="form-control" maxlength="5" inputmode="numeric" required>
                <div id="postcodeHint" class="postcode-hint"></div>
            </div>
        </div>

        <div class="form-group">
            <label>State *</label>
            <select name="state" class="form-control" required>
                <option value="">Select state</option>
                <?php foreach($states as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Country</label>
            <input type="text" class="form-control disabled" value="Malaysia" disabled>
            <input type="hidden" name="country" value="Malaysia">
        </div>

        <div class="checkbox-group compact-checkbox">
            <label>
                <input type="checkbox" name="default_flag" value="1"> Set as default
            </label>
        </div>

        <div class="form-actions">
            <button class="cta-button primary" type="submit">Add Address</button>
            <a href="profile_address_list.php" class="cta-button secondary">Cancel</a>
        </div>

    </form>
</div>
</section>

<?php include '../../_foot.php'; ?>

<script>
    $(function() {

        const postcodeRanges = [{
                state: "Perlis",
                ranges: [
                    [1000, 2999]
                ]
            },
            {
                state: "Kedah",
                ranges: [
                    [5000, 9999]
                ]
            },
            {
                state: "Pulau Pinang",
                ranges: [
                    [10000, 14999]
                ]
            },
            {
                state: "Kelantan",
                ranges: [
                    [15000, 19999]
                ]
            },
            {
                state: "Terengganu",
                ranges: [
                    [20000, 24999]
                ]
            },
            {
                state: "Pahang",
                ranges: [
                    [25000, 28999],
                    [39000, 39999],
                    [49000, 49999],
                    [69000, 69999]
                ]
            },
            {
                state: "Perak",
                ranges: [
                    [30000, 36999]
                ]
            },
            {
                state: "Selangor",
                ranges: [
                    [40000, 48999],
                    [63000, 68999]
                ]
            },
            {
                state: "Kuala Lumpur",
                ranges: [
                    [50000, 60999],
                    [68100, 68100]
                ]
            },
            {
                state: "Putrajaya",
                ranges: [
                    [62000, 62999]
                ]
            },
            {
                state: "Negeri Sembilan",
                ranges: [
                    [70000, 73999]
                ]
            },
            {
                state: "Melaka",
                ranges: [
                    [75000, 78999]
                ]
            },
            {
                state: "Johor",
                ranges: [
                    [79000, 86999]
                ]
            },
            {
                state: "Labuan",
                ranges: [
                    [87000, 87999]
                ]
            },
            {
                state: "Sabah",
                ranges: [
                    [88000, 91999]
                ]
            },
            {
                state: "Sarawak",
                ranges: [
                    [93000, 98999]
                ]
            }
        ];

        const $postcode = $('input[name="postcode"]');
        const $state = $('select[name="state"]');
        const $hint = $('#postcodeHint');
        const $submit = $('button[type="submit"]');

        $submit.prop('disabled', true);

        function detectState(postcode) {
            const num = parseInt(postcode, 10);
            for (const entry of postcodeRanges) {
                for (const range of entry.ranges) {
                    if (num >= range[0] && num <= range[1]) {
                        return entry.state;
                    }
                }
            }
            return null;
        }

        $postcode.on('input', function() {
            let val = $(this).val().replace(/\D/g, '').slice(0, 5);
            $(this).val(val);

            $hint.removeClass('valid error');

            if (val.length < 5) {
                $hint.text('Postcode must be 5 digits.');
                $submit.prop('disabled', true);
                return;
            }

            const detectedState = detectState(val);

            if (!detectedState) {
                $hint.text('Invalid Malaysian postcode.');
                $hint.addClass('error');
                $submit.prop('disabled', true);
                return;
            }

            $state.val(detectedState);

            $hint
                .text('Valid postcode for ' + detectedState)
                .addClass('valid');

            $submit.prop('disabled', true); // wait for state match check
            $state.trigger('change');
        });

        $state.on('change', function() {
            const postcode = $postcode.val();
            if (postcode.length !== 5) return;

            const detectedState = detectState(postcode);

            if ($(this).val() !== detectedState) {
                $hint
                    .text('Postcode does not match selected state.')
                    .removeClass('valid')
                    .addClass('error');
                $submit.prop('disabled', true);
            } else {
                $hint
                    .text('Address looks good ✔')
                    .removeClass('error')
                    .addClass('valid');
                $submit.prop('disabled', false);
            }
        });

    });
</script>


<?php include '../_foot.php'; ?>