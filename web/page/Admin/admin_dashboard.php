<?php require '../../_base.php';
require '../../lib/db.php';
$_title = 'Admin Dashboard';
include '../../_admin_head.php';

auth('Admin');
$user_id = $_user->user_id;

if ($_user && $_user->force_password_change == 1) {
    redirect('/page/force_password_change.php');
}

// Fetch data 
$stmt = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);

$user = $stmt->fetch();
if (!$user) {
    echo "User not found.";
    exit();
}

$member = $_db->query("SELECT COUNT(*) as total FROM users WHERE role = 'Member'")->fetch()->total;
$orders = $_db->query("SELECT COUNT(*) AS total FROM `order`")->fetch()->total;
$totalSales = $_db->query("SELECT IFNULL(SUM(oi.subtotal), 0) AS total FROM `order` o JOIN order_item oi ON o.order_id = oi.order_id WHERE status = 'completed'")->fetch()->total;

// Get success message from temp() if it exists
$notification_message = '';
$notification_type = 'success';

if (get('msg') == 'password_changed') {
    $notification_message = 'Password changed successfully!';
}
?>
<!-- Show statistic cards -->
<div class="admin-content">
    <h1 class="dashboard-title">Welcome Back, <?= $user->name ?> </h1>
    <p class="dashboard-subtitle">Manage your eyewear store from here.</p>

    <div class="dashboard-layout">
        <div class="admin-cards-column">
            <div class="admin-card-container">
                <div class="admin-card">
                    <div class="card-title"> <i class="fas fa-users"></i>Total Member</div>
                    <div class="card-value"><?= $member ?></div>
                </div>
                
                <div class="admin-card">
                    <div class="card-title"><i class="fas fa-shopping-bag"></i>Total Orders</div>
                    <div class="card-value"><?= $orders ?></div>
                </div>

                <div class="admin-card">
                    <div class="card-title"><i class="fas fa-chart-line"></i>Total Sales</div>
                    <div class="card-value">
                        RM <?= number_format($totalSales, 2) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-chart-column">
            <div class="admin-card chart-card">
                <div class="card-title" style="padding-left:0%; ">Sales (Last 7 Days)</div>
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/js/notifications.js"></script>

<script>
    // Show notification on page load if there's a message
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($notification_message): ?>
            showNotification('<?= addslashes($notification_message) ?>', 'success');
        <?php endif; ?>
    });

$(function () {

    $.ajax({
        url: '../ajax/dashboard_sales_data.php',
        dataType: 'json',
        success: function (data) {

            const labels = [];
            const values = [];

            $.each(data, function (i, row) {
                labels.push(row.date);
                values.push(row.total);
            });

            const ctx = document.getElementById('salesChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',  // line chart
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales (RM)',
                        data: values,
                        fill: true,   // fill area under line with color
                        tension: 0.4, // make line curved
                        borderWidth: 3,
                        borderColor: '#162b65',
                        backgroundColor: 'rgba(22, 43, 101, 0.15)',
                        pointRadius: 4,
                        pointBackgroundColor: '#162b65'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' RM ' + ctx.raw.toFixed(2)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: v => 'RM ' + v
                            }
                        }
                    }
                }
            });
        },
        error: function () {
            alert('Failed to load dashboard sales data.');
        }
    });

});
</script>
</body>
</html>