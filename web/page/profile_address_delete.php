<?php
require '../_base.php';
require '../lib/db.php';

auth();

$user_id = $_user->user_id;

// Check if delete_id is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No address selected for deletion.";
    redirect("profile_address_list.php");
}

$delete_id = $_GET['id'];

// Fetch address info
$stmt = $_db->prepare("SELECT default_flag FROM address WHERE address_id = ? AND user_id = ?");
$stmt->execute([$delete_id, $user_id]);
$addr = $stmt->fetch();

if (!$addr) {
    $_SESSION['error'] = "Address not found.";
    redirect("profile_address_list.php");
}

// Confirm deletion (simple JS confirm)
if (!isset($_GET['confirm'])) {
    echo "<script>
        if(confirm('Are you sure you want to delete this address?')) {
            window.location.href = 'profile_address_delete.php?id=$delete_id&confirm=1';
        } else {
            window.location.href = 'profile_address_list.php';
        }
    </script>";
    exit();
}

// Delete the address
$_db->prepare("DELETE FROM address WHERE address_id = ? AND user_id = ?")->execute([$delete_id, $user_id]);

// If deleted address was default, set another as default
if ($addr->default_flag) {
    $stmt2 = $_db->prepare("SELECT address_id FROM address WHERE user_id = ? ORDER BY created_at ASC LIMIT 1");
    $stmt2->execute([$user_id]);
    $new_default = $stmt2->fetch();
    if ($new_default) {
        $_db->prepare("UPDATE address SET default_flag = 1 WHERE address_id = ?")->execute([$new_default->address_id]);
    }
}

$_SESSION['success'] = "Address deleted successfully.";
redirect("profile_address_list.php");
?>
