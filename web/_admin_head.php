<?php
// Get current page
$current_script = basename($_SERVER['PHP_SELF']);
$current_url = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_white.png">
    <link rel="stylesheet" href="/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="/js/app.js"></script>
    <script src="/js/notifications.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="logo">
            <img src="/images/WIS_logo_white.png" alt="Logo">
        </div>

        <nav class="admin-menu">
            <a href="/page/Admin/admin_dashboard.php"
                class="<?= $current_script == 'admin_dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>Dashboard
            </a>

            <a href="/page/Admin/view_user.php?role=Admin"
                class="<?= (strpos($current_url, 'view_user.php') !== false && isset($_GET['role']) && $_GET['role'] == 'Admin') ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i>Admin
            </a>

            <a href="/page/Admin/view_user.php?role=Member"
                class="<?= (strpos($current_url, 'view_user.php') !== false && isset($_GET['role']) && $_GET['role'] == 'Member') ? 'active' : '' ?>">
                <i class="fas fa-user-friends"></i>Member
            </a>

            <a href="/page/Admin/view_category.php"
                class="<?= $current_script == 'view_category.php' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i>Category
            </a>

            <a href="/page/Admin/view_product.php"
                class="<?= $current_script == 'view_product.php' ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i>Product
            </a>

            <a href="/page/Admin/admin_order.php"
                class="<?= $current_script == 'admin_order.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>Order
            </a>

            <a href="/page/Admin/admin_review.php"
                class="<?= $current_script == 'admin_review.php' ? 'active' : '' ?>">
                <i class="fas fa-star"></i>Review
            </a>

            <a href="/page/Admin/view_product_report.php"
                class="<?= $current_script == 'view_product_report.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>Product Analytics
            </a>
        </nav>

        <!-- Show User Account Icon at Bottom (clickable) -->
        <?php
        // Fetch current user info
        $user_id = $_user->user_id;
        $stmt = $_db->prepare("SELECT name, photo FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $currentUser = $stmt->fetch();
        // Get user profile photo else show default user photo
        $photoPath = "/images/users/" . ($currentUser->photo ?: "default.jpg");
        ?>
        <div class="admin-account">
            <a href="/page/profile_page.php">
                <img src="<?= $photoPath ?>" alt="Admin Photo">
                <span><?= htmlspecialchars($currentUser->name) ?></span>
            </a>
        </div>
    </aside>

    <!-- Collapse sidebar -->
    <button class="collapse-btn" onclick="toggleSidebar()">
        <i class="fas fa-chevron-left"></i>
    </button>

    <div class="admin-header">
        <h2 class="admin-title">Four Eyes Collective Administration Panel</h2>
        <div class="admin-logout">
            <a href="/page/logout.php"><i class="fa-solid fa-right-from-bracket" style="color: #162b65"></i>
            </a>
        </div>
    </div>

    