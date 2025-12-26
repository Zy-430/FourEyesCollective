<?php
require_once '../../_base.php';
auth('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['collapsed'])) {
        $_SESSION['admin_sidebar_collapsed'] = (bool)$data['collapsed'];
        echo json_encode(['success' => true]);
        exit;
    }
}
echo json_encode(['success' => false]);