<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Admin');

$id = post('user_id');
$action = post('action');

// Get user
$stmt = $_db->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$target = $stmt->fetch();

// Fail then back to view_user
if (!$target) {
    header("Location: view_user.php?error=user_not_found");
    exit;
}

// Prevent delete last admin
if ($target->role === 'Admin' && $action === 'delete') {
    $count = $_db->query("SELECT COUNT(*) FROM users WHERE role='Admin' AND status='Active'")->fetchColumn();
    if ($count <= 1) {
        header("Location: view_user.php?error=last_admin");
        exit;
    }
}

// User status
if ($action === 'delete') {
    $status = 'Inactive';
    $msg = 'deleted';
} else {
    $status = 'Active';
    $msg = 'restored';
}

// Update
$stmt = $_db->prepare("UPDATE users SET status=? WHERE user_id=?");
$stmt->execute([$status, $id]);

$role = post('role', 'Member');
header("Location: view_user.php?role=$role&user_id=$id&msg=$msg");

exit;