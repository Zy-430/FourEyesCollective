<?php
require '../../_base.php';
require '../../lib/db.php';

header('Content-Type: application/json');

if (!$_user) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$action = post('action');
$userId = $_user->user_id;

switch ($action) {
    case 'toggle':
        $productId = post('product_id');
        // Check if already in wishlist
        $stm = $_db->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ? AND removed_at IS NULL");
        $stm->execute([$userId, $productId]);
        $existing = $stm->fetch();
        
        if ($existing) {
            // Remove from wishlist
            $stm = $_db->prepare("UPDATE wishlist SET removed_at = NOW() WHERE wishlist_id = ?");
            $stm->execute([$existing->wishlist_id]);
            echo json_encode(['success' => true, 'is_in_wishlist' => false]);
        } else {
            // Add to wishlist
            // First check if previously added and removed
            $stm = $_db->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ? AND removed_at IS NOT NULL");
            $stm->execute([$userId, $productId]);
            $removed = $stm->fetch();
            
            if ($removed) {
                // Reactivate
                $stm = $_db->prepare("UPDATE wishlist SET removed_at = NULL, added_at = NOW() WHERE wishlist_id = ?");
                $stm->execute([$removed->wishlist_id]);
            } else {
                // New entry - Generate next wishlist ID
                // Get the maximum numeric part from existing WLXXXX IDs
                $stm = $_db->query("
                    SELECT MAX(CAST(SUBSTRING(wishlist_id, 3) AS UNSIGNED)) as max_id 
                    FROM wishlist 
                    WHERE wishlist_id LIKE 'WL%'
                ");
                $result = $stm->fetch();
                $nextId = ($result->max_id ?? 0) + 1;
                $wishlistId = 'WL' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                
                // Check if this ID already exists (just in case)
                $checkStm = $_db->prepare("SELECT COUNT(*) FROM wishlist WHERE wishlist_id = ?");
                $checkStm->execute([$wishlistId]);
                $exists = $checkStm->fetchColumn();
                
                // If exists, try alternative generation
                if ($exists > 0) {
                    // Fallback: generate ID with timestamp
                    $wishlistId = 'WL' . str_pad($nextId + time() % 10000, 4, '0', STR_PAD_LEFT);
                }
                
                $stm = $_db->prepare("INSERT INTO wishlist (wishlist_id, user_id, product_id, added_at) VALUES (?, ?, ?, NOW())");
                $stm->execute([$wishlistId, $userId, $productId]);
            }
            echo json_encode(['success' => true, 'is_in_wishlist' => true]);
        }
        break;
        
    case 'check_status':
        $productIds = post('product_ids', []);
        $status = [];
        
        if (!empty($productIds)) {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $stm = $_db->prepare("SELECT product_id FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders) AND removed_at IS NULL");
            $stm->execute(array_merge([$userId], $productIds));
            $wishlistItems = $stm->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($productIds as $pid) {
                $status[$pid] = in_array($pid, $wishlistItems);
            }
        }
        
        echo json_encode(['success' => true, 'wishlist_status' => $status]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}