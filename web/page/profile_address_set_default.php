<?php
require '../_base.php';
require '../lib/db.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$address_id = $_GET['id'] ?? null;

if ($address_id) {
    // 1. Remove default from all user's addresses
    $_db->prepare("UPDATE address SET default_flag = 0 WHERE user_id = ?")
        ->execute([$user_id]);

    // 2. Set clicked address as default
    $_db->prepare("UPDATE address SET default_flag = 1 WHERE address_id = ?")
        ->execute([$address_id]);

    $_SESSION['success'] = "Default address updated successfully.";
}

redirect('profile_address_list.php');
?>
