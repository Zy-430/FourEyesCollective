<?php

function restoreOrderItemsToCart($db, $user_id, $order_id) {
    try {
        // Get order items
        $stm = $db->prepare("
            SELECT oi.product_id, oi.product_qty 
            FROM order_item oi 
            WHERE oi.order_id = ?
        ");
        $stm->execute([$order_id]);
        $items = $stm->fetchAll(PDO::FETCH_OBJ);
        
        $restored_items = [];
        $total_quantity = 0;
        
        foreach ($items as $item) {
            // Check if product exists and has stock
            $checkStm = $db->prepare("
                SELECT product_id, product_stock 
                FROM product 
                WHERE product_id = ?
            ");
            $checkStm->execute([$item->product_id]);
            $product = $checkStm->fetch(PDO::FETCH_OBJ);
            
            if (!$product) {
                continue; // Skip if product no longer exists
            }
            
            // Generate NEW cart item ID (don't reuse old ones)
            $newCartItemId = generateCartItemId($db);
            
            // Insert NEW cart item with in_cart status
            $insertStm = $db->prepare("
                INSERT INTO cart_item (
                    cart_item_id, user_id, product_id, product_qty, 
                    item_status, created_at
                ) VALUES (?, ?, ?, ?, 'in_cart', NOW())
            ");
            $insertStm->execute([
                $newCartItemId,
                $user_id,
                $item->product_id,
                $item->product_qty
            ]);
            
            $restored_items[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->product_qty,
                'cart_item_id' => $newCartItemId
            ];
            
            $total_quantity += $item->product_qty;
        }
        
        return [
            'success' => true,
            'restored_items' => $restored_items,
            'total_quantity' => $total_quantity,
            'total_items' => count($restored_items)
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate a new unique cart item ID
 */
function generateCartItemId($db) {
    $maxAttempts = 10;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        $new_id = "CI" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT);
        
        $stm = $db->prepare("SELECT COUNT(*) as count FROM cart_item WHERE cart_item_id = ?");
        $stm->execute([$new_id]);
        $result = $stm->fetch(PDO::FETCH_OBJ);
        
        if ($result->count == 0) {
            return $new_id;
        }
    }
    
    // Fallback with timestamp
    return "CI" . str_pad(rand(1000, 9999), 4, "0", STR_PAD_LEFT) . substr(time(), -4);
}
?>