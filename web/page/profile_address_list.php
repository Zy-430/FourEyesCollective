<?php
require '../_base.php';
require '../lib/db.php';

auth();

$user_id = $_user->user_id;

$stmt = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC, created_at ASC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$total_addresses = count($addresses);

$_title = "My Address | Four Eyes Collective";
include '../_head.php';

?>

<section style="padding:60px 0; background:#ecf0f1;">
<div style="max-width:900px; margin:auto; background:white; padding:40px; border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
<h1 style="text-align:center; font-size:2.5em; margin-bottom:30px;">My Addresses</h1>

<?php if (isset($_SESSION['success'])): ?>
    <div style="padding:15px; margin-bottom:20px; background:#27ae60; color:white; border-radius:4px; text-align:center;">
        <?= $_SESSION['success'] ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div style="padding:15px; margin-bottom:20px; background:#e74c3c; color:white; border-radius:4px; text-align:center;">
        <?= $_SESSION['error'] ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if ($total_addresses === 0): ?>
    <p style="text-align:center; color:#7f8c8d;">No addresses added yet.</p>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
        <?php foreach ($addresses as $addr): ?>
        <div style="border:1px solid #ccc; padding:15px; border-radius:8px; position:relative;">
            <?php if ($addr->default_flag): ?>
                <span style="position:absolute; top:10px; right:10px; background:#27ae60; color:white; padding:2px 6px; border-radius:4px; font-size:0.8em;">Default</span>
            <?php endif; ?>
            
            <p><?= encode($addr->address_line1) ?></p>
            <?php if (!empty($addr->address_line2)): ?><p><?= encode($addr->address_line2) ?></p><?php endif; ?>
            <p><?= encode($addr->city . ', ' . $addr->state . ' ' . $addr->postcode) ?></p>
            <p><?= encode($addr->country) ?></p>

            <div style="margin-top:10px;">
                <a href="profile_address_edit.php?id=<?= $addr->address_id ?>" class="cta-button" style="padding:5px 12px; background:#2c3e50; color:white; border-radius:4px; text-decoration:none; font-size:0.85em;">Edit</a>
                <a href="profile_address_delete.php?id=<?= $addr->address_id ?>" class="cta-button" style="padding:5px 12px; background:#e74c3c; color:white; border-radius:4px; text-decoration:none; font-size:0.85em;" onclick="return confirm('Delete this address?');">Delete</a>
                <?php if (!$addr->default_flag): ?>
                    <a href="profile_address_set_default.php?id=<?= $addr->address_id ?>" style="padding:5px 12px; background:#27ae60; color:white; border-radius:4px; text-decoration:none; font-size:0.85em;">Set Default</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div style="display:flex; justify-content:center; gap:20px; margin-top:30px;">
    <?php if ($total_addresses < 10): ?>
        <a href="profile_address_add.php" class="cta-button"
           style="padding:10px 20px; background:#27ae60; color:white; border-radius:6px; text-decoration:none;">
           Add New Address
        </a>
    <?php endif; ?>

    <a href="profile_page.php" class="cta-button"
       style="padding:10px 25px; background:#34495e; color:white; border-radius:6px; text-decoration:none;">
       Back to Profile
    </a>
</div>

</div>
</section>
<?php include '../_foot.php'; ?>