<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');

$user_id = $_user->user_id;

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

$address_id = $_GET['id'] ?? null;
if (!$address_id) {
    $_SESSION['error'] = "No address selected.";
    redirect('profile_address_list.php');
}

$stmt = $_db->prepare("SELECT * FROM address WHERE address_id=? AND user_id=?");
$stmt->execute([$address_id, $user_id]);
$addr = $stmt->fetch();
if (!$addr) {
    $_SESSION['error'] = "Address not found.";
    redirect('profile_address_list.php');
}

if (is_post()) {
    $recipient_name = trim($_POST['recipient_name']);
    $line1 = trim($_POST['address_line1']);
    $line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postcode = trim($_POST['postcode']);
    $country = "Malaysia";
    $is_default = isset($_POST['default_flag']) ? 1 : 0;

    // Client-side validation redundancy
    if (!preg_match('/^\d{5}$/', $postcode)) {
        $_SESSION['error'] = "Invalid postcode. Must be 5 digits.";
        redirect("profile_address_edit.php?id=$address_id");
    }

    try {
        $_db->beginTransaction();

        // If default, remove old default
        if ($is_default) {
            $_db->prepare("UPDATE address SET default_flag=0 WHERE user_id=?")->execute([$user_id]);
        } else {
            // Ensure at least one default remains
            $stmtCheck = $_db->prepare("SELECT COUNT(*) FROM address WHERE user_id=? AND default_flag=1 AND address_id!=?");
            $stmtCheck->execute([$user_id, $address_id]);
            if ($stmtCheck->fetchColumn() == 0) {
                // Cannot uncheck the last default
                $_db->rollBack();
                $_SESSION['error'] = "You must have at least one default address.";
                redirect("profile_address_edit.php?id=$address_id");
                exit;
            }
        }

        // Update address
        $update = $_db->prepare("
            UPDATE address SET
                recipient_name=?, address_line1=?, address_line2=?, city=?, state=?, postcode=?, country=?, default_flag=?
            WHERE address_id=? AND user_id=?
        ");
        $update->execute([
            $recipient_name,
            $line1,
            $line2,
            $city,
            $state,
            $postcode,
            $country,
            $is_default,
            $address_id,
            $user_id
        ]);

        $_db->commit();
        $_SESSION['success'] = "Address updated successfully.";
    } catch (Exception $e) {
        $_db->rollBack();
        $_SESSION['error'] = "Failed to update address.";
    }

    redirect("profile_address_list.php");
}

$_title = "Edit Address | Four Eyes Collective";
$_css = ['profile.css'];
include '../../_head.php';
?>

<section class="profile-section">
    <div class="profile-card compact-card">
        <h1 class="compact-title">Edit Address</h1>
        <form method="post" class="compact-form">

            <div class="form-group">
                <label>Recipient Name *</label>
                <input type="text" name="recipient_name" class="form-control" required
                    value="<?= htmlspecialchars($addr->recipient_name) ?>">
            </div>

            <div class="form-group">
                <label>Address Line 1 *</label>
                <input type="text" name="address_line1" class="form-control" required
                    value="<?= htmlspecialchars($addr->address_line1) ?>">
            </div>

            <div class="form-group">
                <label>Address Line 2</label>
                <input type="text" name="address_line2" class="form-control"
                    value="<?= htmlspecialchars($addr->address_line2) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" class="form-control" required
                        value="<?= htmlspecialchars($addr->city) ?>">
                </div>
                <div class="form-group">
                    <label>Postcode *</label>
                    <input type="text" name="postcode" class="form-control" maxlength="5" inputmode="numeric" required
                        value="<?= htmlspecialchars($addr->postcode) ?>">
                    <div id="postcodeHint" class="postcode-hint"></div>
                </div>
            </div>

            <div class="form-group">
                <label>State *</label>
                <select name="state" class="form-control" required>
                    <option value="">Select state</option>
                    <?php foreach ($states as $s): ?>
                        <option value="<?= $s ?>" <?= $addr->state === $s ? 'selected' : '' ?>><?= $s ?></option>
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
                    <input type="checkbox" name="default_flag" value="1" <?= $addr->default_flag ? 'checked' : '' ?>>
                    Set as default
                </label>
            </div>

            <div class="form-actions">
                <button class="cta-button primary" type="submit">Save Changes</button>
                <a href="profile_address_list.php" class="cta-button secondary">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include '../../_foot.php'; ?>
<script src="notification.js"></script>

<script>
    $(function() {
        <?php if (!empty($_SESSION['error'])): ?>
            showNotification("<?= addslashes($_SESSION['error']) ?>", "error");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            showNotification("<?= addslashes($_SESSION['success']) ?>", "success");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    });
    
    $(function() {

        const $postcode = $('input[name="postcode"]');
        const $state = $('select[name="state"]');
        const $submit = $('button[type="submit"]');

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

        function validateAddress(autoSelect) {
            const postcode = $postcode.val();
            const selectedState = $state.val();

            if (postcode.length !== 5) {
                $submit.prop('disabled', true);
                return;
            }

            const detectedState = detectState(postcode);

            if (!detectedState) {
                showNotification("Invalid Malaysian postcode. Please enter a valid 5-digit postcode.", "error");
                $submit.prop('disabled', true);
                return;
            }

            // Auto-select state when postcode is entered
            if (autoSelect) {
                $state.val(detectedState);
                showNotification("State automatically selected based on postcode.", "success");
                $submit.prop('disabled', false);
                return;
            }

            // Validate when state is manually selected
            if (selectedState !== detectedState) {
                showNotification(
                    "The postcode does not belong to the selected state. Please correct either the postcode or state.",
                    "error"
                );
                $submit.prop('disabled', true);
                return;
            }

            showNotification("Address details are valid ✔", "success");
            $submit.prop('disabled', false);
        }

        // Postcode typing → auto select state
        $postcode.on('input', function() {
            let val = $(this).val().replace(/\D/g, '').slice(0, 5);
            $(this).val(val);

            if (val.length === 5) {
                validateAddress(true);
            } else {
                $submit.prop('disabled', true);
            }
        });

        // State change → validate postcode
        $state.on('change', function() {
            if ($postcode.val().length === 5) {
                validateAddress(false);
            }
        });

    });
</script>