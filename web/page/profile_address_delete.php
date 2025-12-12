<?php
require '../_base.php';
require '../lib/db.php';

auth();

$user_id = $_user->user_id;

$stmt = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC, created_at ASC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$total_addresses = count($addresses);
include '../_head.php';
?>

<section style="padding:60px 0; background:#ecf0f1;">
<div style="max-width:900px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
<h1 style="text-align:center; font-size:2.5em; margin-bottom:30px;">My Addresses</h1>


</div>
</section>
<?php include '../_foot.php'; ?>