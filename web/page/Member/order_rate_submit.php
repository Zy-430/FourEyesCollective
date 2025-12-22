<?php
require '../../_base.php';
require '../../lib/db.php';

auth('Member');
header('Content-Type: application/json');

$order_item_id = $_POST['order_item_id'] ?? null;
$user_rating   = (int)($_POST['user_rating'] ?? 0);
$user_comment  = $_POST['user_comment'] ?? '';

if (!$order_item_id || $user_rating < 1 || $user_rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid rating data']);
    exit;
}

// SECURITY: Verify item belongs to this user AND not rated yet
$verify = $_db->prepare("
    SELECT oi.order_item_id
    FROM order_item oi
    JOIN `order` o ON oi.order_id = o.order_id
    WHERE oi.order_item_id = ?
      AND o.user_id = ?
      AND oi.user_rating IS NULL
");
$verify->execute([$order_item_id, $_user->user_id]);

if (!$verify->fetch()) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unauthorized or already rated'
    ]);
    exit;
}

/* ---------------------------
   Handle photo & video uploads
   Max 5 files each item
---------------------------- */
$photos = [];
$videos = [];

if (!empty($_FILES['rating_media']['name'][0])) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/images/review/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    foreach ($_FILES['rating_media']['tmp_name'] as $i => $tmpName) {
        if (!is_uploaded_file($tmpName)) continue;

        $originalName = $_FILES['rating_media']['name'][$i];
        $fileSize     = $_FILES['rating_media']['size'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $allowedImage = ['jpg', 'jpeg', 'png', 'gif'];
        $allowedVideo = ['mp4', 'mov', 'webm'];

        // Extension check
        if (!in_array($ext, array_merge($allowedImage, $allowedVideo))) continue;

        // File size check 
        if (in_array($ext, $allowedImage) && $fileSize > 5 * 1024 * 1024) continue;
        if (in_array($ext, $allowedVideo) && $fileSize > 5 * 1024 * 1024) continue;

        // Save file, prevent same file name overwrite
        $filename = uniqid('rev_', true) . '.' . $ext;
        move_uploaded_file($tmpName, $uploadDir . $filename);

        if (in_array($ext, $allowedImage) && count($photos) < 5) {
            $photos[] = $filename;
        } elseif (in_array($ext, $allowedVideo) && count($videos) < 5) {
            $videos[] = $filename;
        }
    }
}

/* ---------------------------
   Update rating
---------------------------- */
$stmUpdate = $_db->prepare("
    UPDATE order_item
    SET user_rating = ?,
        user_comment = ?,
        rated_at = NOW(),
        rating_photo = ?,
        rating_video = ?
    WHERE order_item_id = ?
");
$stmUpdate->execute([
    $user_rating,
    $user_comment,
    json_encode($photos),
    json_encode($videos),
    $order_item_id
]);

/* ---------------------------
   Check remaining unrated items
---------------------------- */
$stmRemain = $_db->prepare("
    SELECT COUNT(*)
    FROM order_item oi
    JOIN `order` o ON oi.order_id = o.order_id
    WHERE o.user_id = ?
      AND oi.user_rating IS NULL
");
$stmRemain->execute([$_user->user_id]);
$remaining = $stmRemain->fetchColumn();

/* ---------------------------
   Response
---------------------------- */
echo json_encode([
    'status'   => 'success',
    'allRated' => ($remaining == 0)
]);
