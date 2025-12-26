<?php
require '../../_base.php';
require '../../lib/db.php';
require '../../lib/category.php';

auth('Member');

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="/images/WIS_logo_white.png">
        <title>Invalid Order | Four Eyes Collective</title>
        <link rel="stylesheet" href="/css/checkout_flow.css">
        <link rel="stylesheet" href="/css/app.css">
    </head>

    <body>
        <div class="checkout-status-container status-error">
            <div class="page-header">
                <h1>❌ Invalid Order</h1>
                <p>Invalid order ID provided.</p>
            </div>
            <div class="checkout-section" style="text-align: center;">
                <div class="error-message">
                    <p>Invalid order ID provided.</p>
                </div>
                <div class="action-buttons">
                    <a href="cart.php" class="btn btn-secondary">Return to Cart</a>
                    <a href="../shoppage.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

$_db->beginTransaction();

try {
    // Verify order belongs to user and is in pending_payment status
    $stm = $_db->prepare("SELECT * FROM `order` WHERE order_id = ? AND user_id = ? AND status = 'pending_payment'");
    $stm->execute([$order_id, $_user->user_id]);
    $order = $stm->fetch(PDO::FETCH_OBJ);

    if (!$order) {
        throw new Exception("Order not found, already processed, or unauthorized access");
    }

    // Set order as cancelled
    $_db->prepare("
        UPDATE `order` SET 
            status = 'cancelled',
            cancelled_reason = 'Payment failed'
        WHERE order_id = ? AND user_id = ?
    ")->execute([$order_id, $_user->user_id]);

    // Get order items with category information
    $stm = $_db->prepare("
        SELECT oi.product_id, oi.product_qty, p.product_name, p.product_image, c.category_id
        FROM order_item oi
        JOIN product p ON oi.product_id = p.product_id
        LEFT JOIN category c ON p.category_id = c.category_id
        WHERE oi.order_id = ?
    ");
    $stm->execute([$order_id]);
    $items = $stm->fetchAll(PDO::FETCH_OBJ);

    // Get last cart_item_id
    $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(cart_item_id, 3) AS UNSIGNED)) AS max_id FROM cart_item");
    $max_id = $stm->fetch()->max_id ?? 0;

    // Insert NEW cart items
    foreach ($items as $item) {
        $new_cart_item_id = 'CI' . str_pad(++$max_id, 4, '0', STR_PAD_LEFT);

        $_db->prepare("
            INSERT INTO cart_item (
                cart_item_id,
                user_id,
                product_id,
                product_qty,
                item_status,
                created_at
            ) VALUES (?, ?, ?, ?, 'in_cart', NOW())
        ")->execute([
            $new_cart_item_id,
            $_user->user_id,
            $item->product_id,
            $item->product_qty
        ]);

        // Restore stock
        $_db->prepare("
            UPDATE product
            SET product_stock = product_stock + ?
            WHERE product_id = ?
        ")->execute([$item->product_qty, $item->product_id]);
    }

    // Update payment status if exists
    $_db->prepare("
        UPDATE payment SET status = 'cancelled' 
        WHERE order_id = ?
    ")->execute([$order_id]);

    // Insert order history
    $stm = $_db->query("SELECT MAX(CAST(SUBSTRING(history_id, 4) AS UNSIGNED)) AS max_id FROM order_history");
    $max_history_id = $stm->fetch()->max_id ?? 0;
    $history_id = 'HIS' . str_pad($max_history_id + 1, 4, '0', STR_PAD_LEFT);
    $_db->prepare("
        INSERT INTO order_history (history_id, order_id, status, changed_at, changed_by, message)
        VALUES (?, ?, 'cancelled', NOW(), ?, 'Payment failed, order cancelled')
    ")->execute([$history_id, $order_id, $_user->user_id]);

    $_db->commit();

    $item_count = count($items);
    $total_quantity = 0;
    foreach ($items as $item) {
        $total_quantity += $item->product_qty;
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="/images/WIS_logo_white.png">
        <title>Payment Failed | Four Eyes Collective</title>
        <link rel="stylesheet" href="/css/checkout_flow.css">
        <link rel="stylesheet" href="/css/app.css">
    </head>

    <body>
        <div class="checkout-status-container status-warning">
            <div class="page-header">
                <h1>Payment Failed</h1>
                <p>Your payment could not be processed</p>
            </div>

            <div class="checkout-section">
                <div class="status-icon">⚠️</div>

                <div class="warning-message">
                    <h3>Payment Unsuccessful</h3>
                    <p>Your payment for order <strong><?= encode($order_id) ?></strong> failed to process.</p>
                    <p>No charges have been made to your account.</p>
                    <p><strong><?= $item_count ?> item(s)</strong> have been restored to your shopping cart.</p>
                </div>

                <!-- Order Details -->
                <div class="order-details-grid">
                    <div class="detail-box">
                        <div class="detail-label">Order ID</div>
                        <div class="detail-value"><?= encode($order_id) ?></div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Items Restored</div>
                        <div class="detail-value"><?= $item_count ?> product(s)</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Total Quantity</div>
                        <div class="detail-value"><?= $total_quantity ?> item(s)</div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Order Status</div>
                        <div class="detail-value" style="color:#ffc107;">Cancelled</div>
                    </div>
                </div>

                <!-- Order Items Preview -->
                <div class="checkout-section">
                    <h3>Items Restored to Cart</h3>
                    <div class="order-items" style="max-height: 200px; overflow-y: auto; margin-bottom: 20px;">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $folder = $categoryFolders[$item->category_id] ?? 'others';
                            $imgArray = explode(',', $item->product_image);
                            $firstImage = trim($imgArray[0]);
                            $imgPath = "/images/product/$folder/$firstImage";
                            ?>
                            <div class="order-item">
                                <img src="<?= $imgPath ?>" alt="<?= encode($item->product_name) ?>" class="order-item-image">
                                <div class="item-details">
                                    <div class="item-name"><?= encode($item->product_name) ?></div>
                                    <div style="color: #666; font-size: 0.9rem;">Qty: <?= $item->product_qty ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="info-message">
                    <h4>What would you like to do?</h4>
                    <ul>
                        <li><strong>Order Again:</strong> All items from this order have been added to your cart</li>
                        <li><strong>Shop Products:</strong> Browse our collection and add different items</li>
                        <li><strong>View Cart:</strong> Review and modify your current cart</li>
                    </ul>
                </div>

                <div class="action-buttons">
                    <a href="../shoppage.php" class="btn btn-warning">Shop Other Products</a>
                    <a href="cart.php" class="btn btn-secondary">View Cart</a>
                </div>
                <!-- <div style="">
                     <a href="/page/contact.php" class="btn">Contact Support</a>
                </div> -->
            </div>
        </div>

        <script>
            setTimeout(() => {
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
            }, 500);
        </script>
    </body>

    </html>
<?php
} catch (Exception $ex) {
    $_db->rollBack();
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" href="/images/WIS_logo_white.png">
        <title>Error Processing Cancellation | Four Eyes Collective</title>
        <link rel="stylesheet" href="/css/checkout_flow.css">
        <link rel="stylesheet" href="/css/app.css">
    </head>

    <body>
        <div class="checkout-status-container status-error">
            <div class="page-header">
                <h1>⚠️ System Error</h1>
                <p>An error occurred while processing your cancellation</p>
            </div>
            <div class="checkout-section" style="text-align: center;">
                <div class="error-message">
                    <p>An error occurred while processing your cancellation:</p>
                    <p><strong><?= encode($ex->getMessage()) ?></strong></p>
                </div>
                <div class="action-buttons">
                    <a href="cart.php" class="btn btn-secondary">Return to Cart</a>
                    <a href="/page/contact.php" class="btn">Contact Support</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
}
?>