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

/* ---------------------------
   Handle photo & video uploads
   Max 5 files each
---------------------------- */
$photos = [];
$videos = [];

if (!empty($_FILES['rating_media']['name'][0])) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/images/review/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    foreach ($_FILES['rating_media']['tmp_name'] as $i => $tmpName) {
        if (!is_uploaded_file($tmpName)) continue;

        $originalName = basename($_FILES['rating_media']['name'][$i]);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $allowedImage = ['jpg','jpeg','png','gif'];
        $allowedVideo = ['mp4','mov','webm'];

        if (!in_array($ext, array_merge($allowedImage, $allowedVideo))) continue;

        $filename = uniqid('rev_') . '_' . $originalName;
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
