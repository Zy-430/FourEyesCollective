<?php

// ============================================================================
// PHP Setups
// ============================================================================

date_default_timezone_set('Asia/Kuala_Lumpur');
session_start(); //enable session to retain data



// For demo purposes, automatically log in as a test user
// Remove this in production
if (!isset($_SESSION['user_id']) && isset($_GET['demo'])) {
    $_SESSION['user_id'] = 'ME0001'; // Demo user ID
    $_SESSION['user_name'] = 'John Doe';
    $_SESSION['user_email'] = 'john.doe@example.com';
    $_SESSION['user_role'] = 'member';
}





// ============================================================================
// General Page Functions
// ============================================================================

// Is GET request?
function is_get() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

// Is POST request?
function is_post() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// Obtain GET parameter
function get($key, $value = null) {
    $value = $_GET[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain POST parameter
function post($key, $value = null) {
    $value = $_POST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain REQUEST (GET and POST) parameter
function req($key, $value = null) {
    $value = $_REQUEST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Redirect to URL
function redirect($url = null) {
    $url ??= $_SERVER['REQUEST_URI'];  // if url = null, set url to current url
    header("Location: $url");  // set HTTP response header, so client will load the url
    exit();
}

// Set or get temporary session variable
function temp($key, $value = null) {
    if ($value !== null) {   //if value is not null , perform SET 
        $_SESSION["temp_$key"] = $value;     // store value in session value
    }
    else {   //else perform GET
        $value = $_SESSION["temp_$key"] ?? null;   //read value from session variable 
        unset($_SESSION["temp_$key"]);   // remove session variable , effectively making 1-time value
        return $value;
    }
}


// ============================================================================
// HTML Helpers
// ============================================================================


// Encode HTML special characters
function encode($value) {
    return htmlentities($value);
}

// Generate <input type='text'>
function html_text($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='radio'> list
function html_radios($key, $items, $br = false) {
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
function html_select($key, $items, $default = '- Select One -', $attr = '') {
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

// ============================================================================
// Error Handlings
// ============================================================================

// Global error array
$_err = [];

// Generate <span class='err'>
function err($key) {
    global $_err;
    if ($_err[$key] ?? false) {
        echo "<span class='err'>$_err[$key]</span>";
    }
    else {
        echo '<span></span>';
    }
}
// ============================================================================
// Global Constants and Variables
// ============================================================================

$_genders = [
    'F' => 'Female',
    'M' => 'Male',
];

$_days = [];
for($i = 1; $i <= 31; $i++) {
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
for($i = $current_year; $i >= $current_year - 100; $i--) {
    $_years[$i] = $i;
}

