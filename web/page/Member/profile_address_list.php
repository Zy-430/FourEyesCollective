<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');

$user_id = $_user->user_id;

$stmt = $_db->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY default_flag DESC, created_at ASC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$total_addresses = count($addresses);

$_title = "My Address | Four Eyes Collective";
$_css = ['profile.css'];
include '../../_head.php';

?>

<section class="profile-section">
<div class="profile-card">

<h1 class="centered-title">My Addresses</h1>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert success">
        <?= $_SESSION['success'] ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert error">
        <?= $_SESSION['error'] ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if ($total_addresses === 0): ?>
    <p class="empty-note">No addresses added yet.</p>
<?php else: ?>
    <div class="addresses-grid">
        <?php foreach ($addresses as $addr): ?>
        <div class="address-card">
            <?php if ($addr->default_flag): ?>
                <span class="default-badge">Default</span>
            <?php endif; ?>

            <p><?= encode($addr->address_line1) ?></p>
            <?php if (!empty($addr->address_line2)): ?><p><?= encode($addr->address_line2) ?></p><?php endif; ?>
            <p><?= encode($addr->city . ', ' . $addr->state . ' ' . $addr->postcode) ?></p>
            <p><?= encode($addr->country) ?></p>

            <div class="address-actions">
                <a href="profile_address_edit.php?id=<?= $addr->address_id ?>" class="cta-button small">Edit</a>
                <a href="profile_address_delete.php?id=<?= $addr->address_id ?>" class="cta-button danger small" onclick="return confirm('Delete this address?');">Delete</a>
                <?php if (!$addr->default_flag): ?>
                    <a href="profile_address_set_default.php?id=<?= $addr->address_id ?>" class="cta-button success small">Set Default</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="address-footer">
    <?php if ($total_addresses < 10): ?>
        <a href="profile_address_add.php" class="cta-button">Add New Address</a>
    <?php endif; ?>

    <a href="../profile_page.php" class="cta-button secondary">Back to Profile</a>
</div>

</div>
</section>
<?php include '../../_foot.php'; ?>