<?php
// Function to get total sold quantity for a product
function getProductSoldCount($product_id) {
    global $_db;
    
    $sql = "SELECT SUM(oi.product_qty) as total_sold 
            FROM order_item oi
            JOIN `order` o ON oi.order_id = o.order_id
            WHERE oi.product_id = ? 
            AND o.status NOT IN ('cancelled')";
    
    $stm = $_db->prepare($sql);
    $stm->execute([$product_id]);
    $result = $stm->fetch();
    
    return $result->total_sold ?? 0;
}

// Function to check if product is in user's wishlist
function isInWishlist($user_id, $product_id) {
    global $_db;
    
    if (!$user_id) return false;
    
    $sql = "SELECT COUNT(*) as count FROM wishlist 
            WHERE user_id = ? AND product_id = ? AND active = 1";
    
    $stm = $_db->prepare($sql);
    $stm->execute([$user_id, $product_id]);
    $result = $stm->fetch();
    
    return $result->count > 0;
}

// Function to get top selling products
function getTopSellingProducts($limit = 5) {
    global $_db;
    
    $sql = "SELECT 
                p.product_id,
                p.product_name,
                p.product_price,
                p.product_image,
                p.category_id,
                COALESCE(SUM(oi.product_qty), 0) as total_sold,
                COUNT(DISTINCT oi.order_item_id) as order_count
            FROM product p
            LEFT JOIN order_item oi ON p.product_id = oi.product_id
            LEFT JOIN `order` o ON oi.order_id = o.order_id 
                AND o.status NOT IN ('cancelled')
            WHERE p.product_status = 1
            GROUP BY p.product_id, p.product_name, p.product_price, p.product_image, p.category_id
            ORDER BY total_sold DESC, order_count DESC
            LIMIT ?";
    
    $stm = $_db->prepare($sql);
    $stm->bindValue(1, $limit, PDO::PARAM_INT);
    $stm->execute();
    return $stm->fetchAll();
}

// Function to get category folder name
function categoryFolder($catId) {
    return [
        'CA0001' => 'glasses',
        'CA0002' => 'sunglasses',
        'CA0003' => 'contactlens',
        'CA0004' => 'kids'
    ][$catId] ?? 'others';
}