<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');

$user_id = $_user->user_id;
$address_id = $_GET['id'] ?? null;

if (!$address_id) {
    redirect('profile_address_list.php');
}

// Use transaction for safety
$_db->beginTransaction();

try {
    // 1. Remove default from all user's addresses
    $_db->prepare("
        UPDATE address 
        SET default_flag = 0 
        WHERE user_id = ?
    ")->execute([$user_id]);

    // 2. Set selected address as default (OWNED BY USER)
    $updated = $_db->prepare("
        UPDATE address 
        SET default_flag = 1 
        WHERE address_id = ? AND user_id = ?
    ");
    $updated->execute([$address_id, $user_id]);

    if ($updated->rowCount() === 0) {
        throw new Exception('Invalid address');
    }

    $_db->commit();
    $_SESSION['success'] = "Default address updated successfully.";
} catch (Exception $e) {
    $_db->rollBack();
    $_SESSION['error'] = "Failed to update default address.";
}

redirect('profile_address_list.php');
