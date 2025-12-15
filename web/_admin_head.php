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
    <link rel="shortcut icon" href="/images/WIS_logo_1.png">
    <link rel="stylesheet" href="/css/admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="/js/app.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="logo">
            <img src="/images/WIS_logo_white.png" alt="Logo">
        </div>

        <nav class="admin-menu">
            <a href="/page/admin_dashboard.php"
                class="<?= $current_script == 'admin_dashboard.php' ? 'active' : '' ?>">
                Dashboard
            </a>

            <a href="user_list.php?role=Admin"
                class="<?= (strpos($current_url, 'user_list.php') !== false && isset($_GET['role']) && $_GET['role'] == 'Admin') ? 'active' : '' ?>">
                Admin Management
            </a>

            <a href="user_list.php?role=Member"
                class="<?= (strpos($current_url, 'user_list.php') !== false && isset($_GET['role']) && $_GET['role'] == 'Member') ? 'active' : '' ?>">
                Member Management
            </a>

            <a href="/page/customer_management.php"
                class="<?= $current_script == 'customer_management.php' ? 'active' : '' ?>">
                Customer Management
            </a>

            <a href="/page/category_management.php"
                class="<?= $current_script == 'category_management.php' ? 'active' : '' ?>">
                Category Management
            </a>

            <a href="/page/add_product.php"
                class="<?= $current_script == 'add_product.php' ? 'active' : '' ?>">
                Product Management
            </a>

            <a href="/page/order_history.php"
                class="<?= $current_script == 'order_history.php' ? 'active' : '' ?>">
                Order History
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
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket" style="color: #162b65"></i>
            </a>
        </div>
    </div>

    