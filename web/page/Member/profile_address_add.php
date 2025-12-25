<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');

$user_id = $_user->user_id;

// get return URL with fallbacks
$return_url = get('return', 'profile_address_list.php');
$return_url = trim($return_url);
$path = parse_url($return_url, PHP_URL_PATH) ?: $return_url;
$base = basename($path);

$allowed_returns = ['checkout.php', 'profile_address_list.php', 'profile_page.php', 'cart.php'];
if (!in_array($base, $allowed_returns)) {
    $return_url = 'profile_address_list.php';
} else {
    $return_url = $base;
}

// Count current addresses
$stmt = $_db->prepare("SELECT * FROM address WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_addresses = $stmt->rowCount();

if ($total_addresses >= 10) {
    $_SESSION['error'] = 'You have reached the maximum number of saved addresses.';
    redirect('profile_address_list.php');
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
    $recipient_name = trim($_POST['recipient_name']);
    $line1 = trim($_POST['address_line1']);
    $line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postcode = trim($_POST['postcode']);
    $country = trim($_POST['country']);
    $is_default = isset($_POST['default_flag']) ? 1 : 0;

    // Required validation
    if ($recipient_name == "" || $line1 == "" || $city == "" || $postcode == "" || $state == "" || $country == "") {
        $_SESSION['error'] = "Please fill in all required fields.";
        redirect('profile_address_add.php');
    }

    if (!preg_match('/^\d{5}$/', $postcode)) {
        $_SESSION['error'] = "Invalid postcode format. Must be 5 digits.";
        redirect('profile_address_add.php');
    }

    if ($is_default) {
        $_db->prepare("UPDATE address SET default_flag = 0 WHERE user_id = ?")->execute([$user_id]);
    }

    // Generate address_id ADRS0001
    $last = $_db->query("SELECT address_id FROM address ORDER BY address_id DESC LIMIT 1")->fetch();
    if ($last) {
        $num = intval(substr($last->address_id, 4)) + 1;
        $new_id = "ADRS" . str_pad($num, 4, "0", STR_PAD_LEFT);
    } else {
        $new_id = "ADRS0001";
    }

    $insert = $_db->prepare("
        INSERT INTO address(address_id,user_id,recipient_name,address_line1,address_line2,city,state,postcode,country,default_flag)
        VALUES(?,?,?,?,?,?,?,?,?,?)
    ");
    $insert->execute([$new_id, $user_id, $recipient_name, $line1, $line2, $city, $state, $postcode, $country, $is_default]);

    $_SESSION['success'] = "Address added successfully.";
    redirect($return_url);
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

            <div class="form-group">
                <label>City *</label>
                <input type="text" name="city" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Postcode *</label>
                <input type="text" name="postcode" class="form-control" maxlength="5" inputmode="numeric" required>
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
                <label>Country *</label>
                <input type="text" class="form-control disabled" value="Malaysia" disabled>
                <input type="hidden" name="country" value="Malaysia">
            </div>

            <div class="form-group checkbox-group">
                <label><input type="checkbox" name="default_flag"> Set as default address</label>
            </div>

            <div class="form-group">
                <button class="cta-button full-width" type="submit">Add Address</button>

                <?php if ($return_url === 'checkout.php'): ?>
                    <a href="checkout.php" class="cta-button secondary full-width" style="margin-top:10px">Back to Checkout</a>
                <?php else: ?>
                    <a href="profile_address_list.php" class="cta-button secondary full-width" style="margin-top:10px">Back to Address List</a>
                <?php endif; ?>
            </div>
        </form>

    </div>
</section>

<?php include '../../_foot.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/js/notifications.js"></script>

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

    // -------- postcode → auto select state + validate like edit page --------

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

        function detectState(pc) {
            pc = parseInt(pc);
            for (const e of postcodeRanges) {
                for (const r of e.ranges) {
                    if (pc >= r[0] && pc <= r[1]) return e.state;
                }
            }
            return null;
        }

        function validate(manual = false) {
            let pc = $postcode.val();
            if (pc.length !== 5) {
                $submit.prop('disabled', true);
                return;
            }
            let st = detectState(pc);

            if (!st) {
                showNotification("Invalid Malaysian postcode.", "error");
                $submit.prop('disabled', true);
                return;
            }

            if (!manual) {
                $state.val(st);
                showNotification("State auto-detected ✔", "success");
                $submit.prop('disabled', false);
                return;
            }

            if ($state.val() !== st) {
                showNotification("Postcode does not match selected state.", "error");
                $submit.prop('disabled', true);
                return;
            }

            showNotification("Address valid ✔", "success");
            $submit.prop('disabled', false);
        }

        $postcode.on('input', () => {
            let v = $postcode.val().replace(/\D/g, '').slice(0, 5);
            $postcode.val(v);
            if (v.length === 5) validate(false);
        });
        $state.on('change', () => {
            if ($postcode.val().length === 5) validate(true);
        });

    });
</script>