
<?php

// ============================================================================
// PHP Setups
// ============================================================================

date_default_timezone_set('Asia/Kuala_Lumpur');
session_start(); //enable session to retain data

// ============================================================================
// General Page Functions
// ============================================================================

// Is GET request?
function is_get()
{
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

// Is POST request?
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// Obtain GET parameter
function get($key, $value = null)
{
    $value = $_GET[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain POST parameter
function post($key, $value = null)
{
    $value = $_POST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain REQUEST (GET and POST) parameter
function req($key, $value = null)
{
    $value = $_REQUEST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Redirect to URL
function redirect($url = null)
{
    $url ??= $_SERVER['REQUEST_URI'];  // if url = null, set url to current url
    header("Location: $url");  // set HTTP response header, so client will load the url
    exit();
}

// Set or get temporary session variable
function temp($key, $value = null)
{
    if ($value !== null) {   //if value is not null , perform SET 
        $_SESSION["temp_$key"] = $value;     // store value in session value
    } else {   //else perform GET
        $value = $_SESSION["temp_$key"] ?? null;   //read value from session variable 
        unset($_SESSION["temp_$key"]);   // remove session variable , effectively making 1-time value
        return $value;
    }
}

// Is unique?
function is_unique($value, $table, $field)
{
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() == 0;
}

// Use for update (so it will check unique by excluding the currect detail)
function is_unique_except($value, $table, $field, $same_field, $same_value)
{
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ? AND $same_field != ?");
    $stm->execute([$value, $same_value]);
    return $stm->fetchColumn() == 0;
}

// Is email?
function is_email($value)
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

// Return base url (host + port)
function base($path = '')
{
    return "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/$path";
}

// Is exists?
function is_exists($value, $table, $field)
{
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() > 0;
}

// Is strong password
function is_strong_password($password)
{

    return
        strlen($password) >= 8 && // Minimum 8 characters
        preg_match('/[A-Z]/', $password) && // Contain uppercase
        preg_match('/[a-z]/', $password) && // Contain lowercase
        preg_match('/[0-9]/', $password) && // Contain digit
        preg_match('/[_\W]/', $password); // Contain symbol
}

// ============================================================================
// HTML Helpers
// ============================================================================
function table_headers($fields, $sort, $dir, $href = '')
{
    foreach ($fields as $k => $v) {
        $d = 'asc'; // Default direction
        $c = '';    // Default class

        // Alternative direction , set css class
        if ($k == $sort) {
            $d = $dir == 'asc' ? 'desc' : 'asc';
            $c = $dir;
        }

        echo "<th><a href='?sort=$k&dir=$d&$href' class='$c'>$v</a></th>";
    }
}


// Encode HTML special characters
function encode($value)
{
    return htmlentities($value);
}

// Generate <input type='text'>
function html_text($key, $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='radio'> list
function html_radios($key, $items, $br = false)
{
    $value = encode($GLOBALS[$key] ?? '');
    echo '<div>';
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'checked' : '';
        echo "<label><input type='radio' id='{$key}_$id' name='$key' value='$id' $state>$text</label>";
        if ($br) {
            echo '<br>';
        }
    }
    echo '</div>';
}

// Generate <select>
function html_select($key, $items, $default = '- Select One -', $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<select id='$key' name='$key' $attr>";
    if ($default !== null) {
        echo "<option value=''>$default</option>";
    }
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'selected' : '';
        echo "<option value='$id' $state>$text</option>";
    }
    echo '</select>';
}

// Generate <input type='password'>
function html_password($key, $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='password' id='$key' name='$key' value='$value' $attr>";
}


// Get image file
function get_file($key)
{
    $f = $_FILES[$key] ?? null;

    if ($f && $f['error'] == 0) {
        return (object)$f;
    }

    return null;
}

// Crop, resize and save photo
function save_photo($f, $folder, $width = 200, $height = 200)
{
    $photo = uniqid() . '.jpg';

    require_once 'lib/SimpleImage.php';
    $img = new SimpleImage();
    $img->fromFile($f->tmp_name)
        ->thumbnail($width, $height)
        ->toFile("$folder/$photo", 'image/jpeg');

    return $photo;
}

// Generate <input type='file'>
function html_file($key, $accept = '', $attr = '')
{
    echo "<input type='file' id='$key' name='$key' accept='$accept' $attr>";
}


// ============================================================================
// Error Handlings
// ============================================================================

// Global error array
$_err = [];

// Generate <span class='err'>
function err($key)
{
    global $_err;
    if ($_err[$key] ?? false) {
        echo '<span style="color: #e74c3c;" class="err">' . $_err[$key] . '</span>';
    } else {
        echo '<span></span>';
    }
}
// ============================================================================
// Global Constants and Variables
// ============================================================================

$_genders = [
    'F' => 'Female',
    'M' => 'Male',
    'N' => 'Prefer not to say'
];

$_days = [];
for ($i = 1; $i <= 31; $i++) {
    $_days[$i] = $i;
}

$_months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];

$_years = [];
$current_year = date('Y');
for ($i = $current_year; $i >= $current_year - 100; $i--) {
    $_years[$i] = $i;
}

$_user_statuses = [
    'Pending' => 'Pending Verification',
    'Active' => 'Active',
    'Blocked' => 'Blocked'
];

// ============================================================================
// Security
// ============================================================================

// Global user object
$_user = $_SESSION['user'] ?? null;

// Login user
function login($user, $url = '/')
{
    if ($user->force_password_change == 1) {
        $_SESSION['temp_user'] = $user;
        redirect('/page/force_password_change.php');
    } else {
        $_SESSION['user'] = $user;
        redirect($url);
    }
}

// Logout user
function logout($url = '/')
{
    unset($_SESSION['user']);
    redirect($url);
}

// Authorization
function auth(...$roles)
{
    global $_user;
    if ($_user) {
        if ($roles) {
            if (in_array($_user->role, $roles)) {
                return;
            }
        } else {
            return;
        }
    }

    redirect('/page/login.php');
}

// ============================================================================
// Email Function
// ============================================================================

function get_mail()
{
    require_once 'lib/PHPMailer.php';
    require_once 'lib/SMTP.php';

    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->SMTPAuth = true;
    $m->Host = 'smtp.gmail.com';
    $m->Port = 587;
    $m->Username = 'foureyecollective2025@gmail.com';
    $m->Password = 'tzah szfn nkip rmtu';
    $m->CharSet = 'utf-8';
    $m->setFrom($m->Username, 'Four Eyes Collective');

    return $m;
}


// ============================================================================
// Generate ID
// ============================================================================

function generateHistoryID($db)
{
    // Get the last history_id
    $last = $db->query("SELECT history_id FROM order_history ORDER BY history_id DESC LIMIT 1")->fetchColumn();

    if (!$last) {
        return "HIS0001";
    }

    // Extract numeric part and increment
    $num = intval(substr($last, 3)) + 1; // skip 'HIS'
    return "HIS" . str_pad($num, 4, "0", STR_PAD_LEFT);
}

function statusColor($status)
{
    return match ($status) {
        'pending' => '#f39c12',
        'shipped' => '#3498db',
        'delivered', 'completed' => '#27ae60',
        'cancelled' => '#e74c3c',
        'return_requested' => '#bdc3c7',
        'returned' => '#95a5a6 ',
        default => '#7f8c8d',
    };
}

function generateMemberID($db)
{
    $last = $db->query("SELECT user_id FROM users WHERE role='Member'ORDER BY user_id DESC LIMIT 1")->fetchColumn();

    // If no member exists, start with ME0001
    if (!$last) return "ME0001";

    // Else get the last member id  and extract numeric part, increment, and pad with zeros
    $num = intval(substr($last, 2)) + 1;
    return "ME" . str_pad($num, 4, "0", STR_PAD_LEFT);
}

function generateAdminID($db)
{
    $last = $db->query("SELECT user_id FROM users WHERE role='Admin'ORDER BY user_id DESC LIMIT 1")->fetchColumn();

    // If no admin exists, start with ME0001
    if (!$last) return "AD0001";

    // Else get the last admin id  and extract numeric part, increment, and pad with zeros
    $num = intval(substr($last, 2)) + 1;
    return "AD" . str_pad($num, 4, "0", STR_PAD_LEFT);
}

// ============================================================================
// Admin sidebar state
// ============================================================================
function getSidebarState() {
    // Check session first
    if (isset($_SESSION['admin_sidebar_collapsed'])) {
        return $_SESSION['admin_sidebar_collapsed'];
    }
    return false; // Default expanded
}

function setSidebarState($collapsed) {
    $_SESSION['admin_sidebar_collapsed'] = $collapsed;
}
