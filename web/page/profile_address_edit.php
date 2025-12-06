<?php
require '../_base.php';
require '../lib/db.php';

// TEMPORARY: simulate login for testing
$_SESSION['user_id'] = 'ME0001';

// Check login
if (!isset($_SESSION['user_id'])) {
    //header("Location: login.php");
    //exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC, created_at ASC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$total_addresses = count($addresses);

$_title = "Edit Address | Four Eyes Collective";
include '../_head.php';
?>

<section style="padding:60px 0; background:#ecf0f1;">
<div style="max-width:900px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
<h1 style="text-align:center; font-size:2.5em; margin-bottom:30px;">My Addresses</h1>


</div>
</section>
<?php include '../_foot.php'; ?>