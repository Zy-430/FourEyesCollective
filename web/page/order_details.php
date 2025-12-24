<?php
require '../_base.php';
require '../lib/db.php';

?>
<script>
    (function() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.add('product');
            });
        } else {
            document.body.classList.add('product');
        }
    })();
</script>
<?php

auth();
$order_id = $_GET['order_id'] ?? null;
if (!$order_id) exit("Invalid order");

$isAdmin = $_user->role === 'Admin';

// Fetch order + payment + user + address
$stm = $_db->prepare("
    SELECT 
        o.order_id, o.order_date, o.total_amount, o.status, o.delivered_at,
        u.name AS customer_name,
        a.recipient_name, a.address_line1, a.address_line2, a.city, a.state, a.postcode, a.country,
        p.payment_method_type AS payment_brand,
        p.transaction_date
    FROM `order` o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN address a ON a.address_id = o.address_id
    LEFT JOIN payment p ON p.order_id = o.order_id
    WHERE o.order_id = ? 
    AND (o.user_id = ? OR ? = 1)
");

$stm->execute([$order_id, $_user->user_id, $isAdmin ? 1 : 0]);
$order = $stm->fetch(PDO::FETCH_ASSOC);

if (!$order) exit("Order not found or deniew access");

// ===== Return eligibility logic =====
$returnWindowDays = 30; // return within...days

$canReturn = false;
$returnDaysLeft = 0;

if (in_array($order['status'], ['delivered', 'completed']) && !empty($order['delivered_at'])) {

    $deliveredAt = new DateTime($order['delivered_at']);
    $now = new DateTime();

    $expiryDate = clone $deliveredAt;
    $expiryDate->modify("+{$returnWindowDays} days");

    if ($now <= $expiryDate) {

        // check existing return request
        $chk = $_db->prepare("
            SELECT COUNT(*) 
            FROM order_history 
            WHERE order_id = ? 
            AND status = 'return_requested'
        ");
        $chk->execute([$order['order_id']]);

        if ($chk->fetchColumn() == 0) {
            $canReturn = true;
            $returnDaysLeft = $now->diff($expiryDate)->days;
        }
    }
}

// Fetch items
$stm_items = $_db->prepare("
    SELECT oi.*, p.product_name, p.product_image, p.category_id, oi.product_qty, oi.price
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stm_items->execute([$order_id]);
$items = $stm_items->fetchAll(PDO::FETCH_ASSOC);

// Fetch category folder mapping dynamically
$stm_cat = $_db->query("SELECT category_id, folder FROM category");
$categories = $stm_cat->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch shipping history
$stm_hist = $_db->prepare("
    SELECT * FROM order_history
    WHERE order_id = ?
    ORDER BY changed_at DESC
");
$stm_hist->execute([$order_id]);
$history = $stm_hist->fetchAll(PDO::FETCH_ASSOC);

$_title = "Order Details | Four Eyes Collective";
// Determine which header/footer and CSS to use based on role
if ($_user->role === 'Admin') {
    include '../_admin_head.php'; // Admin header
} else {
    $_css = ['order.css'];
    include '../_head.php'; // Member header
}
?>


<div class="page-container">

    <a href="javascript:history.back()" style="text-decoration: none; color: #2c3e50; font-size: 16px; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fas fa-arrow-left" style="color: black;"></i> Back
    </a>

    <!-- Order Info Card -->
    <div class="card order-card">
        <span class="order-status-badge" style="background:<?= statusColor($order['status']) ?>;">
            <?= $order['status'] ?>
        </span>

        <h2>Order Details</h2>
        <p><strong>Order ID:</strong> <?= encode($order['order_id']) ?></p>
        <p><strong>Order Date:</strong> <?= date('d M Y H:i', strtotime($order['order_date'])) ?></p>
    </div>

    <!-- Products Card -->
    <div class="card">
        <h3>Order Item(s)</h3>

        <?php foreach ($items as $item): ?>
            <div class="item-row">
                <div class="product-info">
                    <img src="/images/product/<?= encode($categories[$item['category_id']] ?? 'other') ?>/<?= encode($item['product_image']) ?>"
                        class="product-thumb" alt="<?= encode($item['product_name']) ?>">
                    <div class="product-meta">
                        <p class="product-name"><?= encode($item['product_name']) ?></p>
                        <p class="product-qty">Qty: <?= $item['product_qty'] ?></p>
                    </div>
                </div>

                <div class="product-price">
                    RM <?= number_format($item['price'], 2) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($isAdmin):
    ?>
        <div class="admin-view-container">
            <!-- Payment Summary Card -->
            <div class="card admin-view">
                <h3>💳 Payment Details</h3>
                <p><strong>Total Amount:</strong> RM <?= number_format($order['total_amount'], 2) ?></p>
                <p><strong>Payment Method:</strong> <?= $order['payment_brand'] ?? '-' ?></p>
                <p><strong>Transaction Date:</strong> <?= $order['transaction_date'] ? date('d M Y H:i', strtotime($order['transaction_date'])) : '-' ?></p>
            </div>

            <!-- Shipping Address Card -->
            <div class="card admin-view">
                <h3>📍 Shipping Address</h3>
                <p><?= encode($order['recipient_name']) ?></p>
                <p><?= encode($order['address_line1']) ?> <?= encode($order['address_line2']) ?></p>
                <p><?= encode($order['city']) ?>, <?= encode($order['state']) ?> <?= encode($order['postcode']) ?></p>
                <p><?= encode($order['country']) ?></p>
            </div>
        </div>
    <?php else: ?>

        <!-- Payment Summary Card -->
        <div class="card admin-view">
            <h3>💳 Payment Details</h3>
            <p><strong>Total Amount:</strong> RM <?= number_format($order['total_amount'], 2) ?></p>
            <p><strong>Payment Method:</strong> <?= $order['payment_brand'] ?? '-' ?></p>
            <p><strong>Transaction Date:</strong> <?= $order['transaction_date'] ? date('d M Y H:i', strtotime($order['transaction_date'])) : '-' ?></p>
        </div>

        <!-- Shipping Address Card -->
        <div class="card admin-view">
            <h3>📍 Shipping Address</h3>
            <p><?= encode($order['recipient_name']) ?></p>
            <p><?= encode($order['address_line1']) ?> <?= encode($order['address_line2']) ?></p>
            <p><?= encode($order['city']) ?>, <?= encode($order['state']) ?> <?= encode($order['postcode']) ?></p>
            <p><?= encode($order['country']) ?></p>
        </div>
    <?php endif; ?>

    <!-- Shipping Timeline Card -->
    <div class="card">
        <h3>Shipping Timeline</h3>
        <?php if (empty($history)): ?>
            <p>No shipping updates yet.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($history as $h): ?>
                    <div class="timeline-item status-<?= encode($h['status']) ?> <?= $h['status'] === $order['status'] ? 'active' : '' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?= ucfirst($h['status']) ?></strong>
                                <span><?= date('d M Y H:i', strtotime($h['changed_at'])) ?></span>
                            </div>
                            <p><?= encode($h['message']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Documents Card (Completed Orders Only) -->
    <?php if ($order['status'] == 'completed'): ?>
        <div class="card">
            <h3>Documents</h3>
            <a href="/page/order_download_receipt.php?order_id=<?= encode($order['order_id']) ?>&type=pdf" class="cta-button admin-btn">
                Download PDF
            </a>
            <a href="/page/order_invoice.php?order_id=<?= encode($order['order_id']) ?>" class="cta-button admin-btn">
                View Invoice
            </a>
            <?php if (!$isAdmin): // only non-admins can send to email 
            ?>
                <button id="sendInvoiceBtn" data-order-id="<?= encode($order['order_id']) ?>" class="cta-button">
                    Send to Email
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (in_array($order['status'], ['delivered', 'pending', 'completed', 'returned', 'cancelled']) && !$isAdmin): ?>
        <div class="card">
            <div class="actions-row">

                <?php if ($order['status'] === 'delivered'): ?>
                    <button id="receiveBtn" data-order-id="<?= encode($order['order_id']) ?>" class="cta-button receive-btn large">
                        Mark as Received
                    </button>
                <?php endif; ?>

                <?php if ($order['status'] === 'pending'): ?>
                    <button id="cancelBtn" data-order-id="<?= encode($order['order_id']) ?>" class="cta-button cancel-btn large">
                        Cancel Order
                    </button>
                <?php endif; ?>

                <?php if ($order['status'] === 'completed'): ?>
                    <button id="rateBtn" data-order-id="<?= encode($order['order_id']) ?>" class="cta-button large">
                        Rate
                    </button>

                    <?php if ($canReturn): ?>
                        <button id="returnBtn"
                            data-order-id="<?= encode($order['order_id']) ?>"
                            class="cta-button large">
                            Return (<?= $returnDaysLeft ?> day<?= $returnDaysLeft > 1 ? 's' : '' ?> left)
                        </button>
                    <?php endif; ?>

                    <button id="orderAgainBtn" data-order-id="<?= encode($order['order_id']) ?>" class="cta-button large">
                        Order Again
                    </button>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['returned', 'cancelled'])): ?>
                    <button id="orderAgainBtn" data-order-id="<?= encode($order['order_id']) ?>" class="cta-button large">
                        Order Again
                    </button>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Return Modal (hidden) -->
<div id="returnModal" class="return-modal" aria-hidden="true">
    <div class="modal-box">
        <h4>Return Order</h4>

        <label>Reason</label>
        <select id="returnReason" class="custom-select">
            <option value="">-- Select a reason --</option>
            <option value="damaged">Item damaged</option>
            <option value="not_as_described">Not as described</option>
            <option value="wrong_item">Wrong item received</option>
            <option value="changed_mind">Changed my mind</option>
            <option value="other">Other</option>
        </select>

        <textarea id="returnOther" class="form-control" placeholder="Please describe..." style="display:none;"></textarea>

        <div class="modal-actions">
            <button id="returnCancel" class="btn-link">Cancel</button>
            <button id="returnSubmit" class="btn-primary">Submit</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="/js/notifications.js"></script>

<script>
    $(function() {
        var $returnModal = $('#returnModal');
        var $returnReason = $('#returnReason');
        var $returnOther = $('#returnOther');

        // MARK AS RECEIVED
        var $receiveBtn = $('#receiveBtn');
        if ($receiveBtn.length) {
            $receiveBtn.on('click', function() {
                var orderId = $(this).data('orderId');
                $.post('/page/Member/order_receive.php', {
                        order_id: orderId
                    })
                    .done(function() {
                        showNotification('Order marked as received', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    })
                    .fail(function() {
                        showNotification('Failed to mark as received', 'error');
                    });
            });
        }

        // CANCEL ORDER
        var $cancelBtn = $('#cancelBtn');
        if ($cancelBtn.length) {
            $cancelBtn.on('click', function() {
                var orderId = $(this).data('orderId');
                if (!confirm('Are you sure you want to cancel this order?')) return;
                $.post('/page/Member/order_cancel.php', {
                        order_id: orderId,
                        cancelled_reason: 'Cancelled from order details'
                    })
                    .done(function() {
                        showNotification('Order cancelled successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    })
                    .fail(function() {
                        showNotification('Failed to cancel order', 'error');
                    });
            });
        }

        // RATE ORDER -> Redirect to rate page
        var $rateBtn = $('#rateBtn');
        if ($rateBtn.length) {
            $rateBtn.on('click', function() {
                var orderId = $(this).data('orderId');
                window.location.href = '/page/Member/order_rate.php?order_id=' + encodeURIComponent(orderId);
            });
        }

        // RETURN ORDER -> modal show/submit
        var $returnBtn = $('#returnBtn');
        if ($returnBtn.length) {
            $returnBtn.on('click', function() {
                $returnReason.val('');
                $returnOther.val('').hide();
                $returnModal.css('display', 'flex');
            });
        }

        $returnReason.on('change', function() {
            if ($(this).val() === 'other') $returnOther.show();
            else $returnOther.hide();
        });

        $('#returnCancel').on('click', function() {
            $returnModal.hide();
        });

        $('#returnSubmit').on('click', function() {
            var reason = $returnReason.val();
            if (!reason) {
                showNotification('Please select a reason', 'error');
                return;
            }
            if (reason === 'other') {
                var otherText = $returnOther.val().trim();
                if (!otherText) {
                    showNotification('Please provide details for "Other"', 'error');
                    return;
                }
                reason = otherText;
            }
            var orderId = $returnBtn.data('orderId');
            $.post('/page/Member/order_return_request.php', {
                    order_id: orderId,
                    reason: reason
                })
                .done(function(data) {
                    // if server returns JSON string, ensure it's parsed
                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {}
                    }
                    if (data && data.status === 'success') {
                        showNotification('Return request submitted', 'success');
                        $returnModal.hide();
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    } else {
                        showNotification((data && data.message) || 'Failed to submit return request', 'error');
                    }
                })
                .fail(function() {
                    showNotification('Something went wrong', 'error');
                });
        });

        // ORDER AGAIN
        var $orderAgainBtn = $('#orderAgainBtn');
        if ($orderAgainBtn.length) {
            $orderAgainBtn.on('click', function() {
                var orderId = $(this).data('orderId');
                if (!orderId) {
                    showNotification('Order ID not found', 'error');
                    return;
                }
                $.post('/page/Member/order_again.php', {
                        order_id: orderId
                    })
                    .done(function(data) {
                        if (typeof data === 'string') {
                            try {
                                data = JSON.parse(data);
                            } catch (e) {}
                        }
                        if (data && data.status === 'success') {
                            showNotification('Items added to cart', 'success');
                            setTimeout(function() {
                                window.location.href = data.redirect;
                            }, 1200);
                        } else {
                            showNotification((data && data.message) || 'Failed to add items', 'error');
                        }
                    })
                    .fail(function() {
                        showNotification('Something went wrong', 'error');
                    });
            });
        }

        // SEND INVOICE
        $('#sendInvoiceBtn').on('click', function() {
            var orderId = $(this).data('orderId');
            $.post('/page/Member/order_send_invoice.php', {
                    order_id: orderId
                })
                .done(function(data) {
                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {}
                    }
                    showNotification((data && data.message) || 'Sent', (data && data.status) || 'success');
                })
                .fail(function() {
                    showNotification('Something went wrong', 'error');
                });
        });

    });
</script>

<?php
// Conditionally include footer based on role
if ($_user->role === 'Admin') {
    // Admin pages don't have a footer file, just close the HTML
    echo '</body></html>';
} else {
    include '../_foot.php'; // Member footer
}
?>