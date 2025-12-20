<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_white.png">
    <link rel="stylesheet" href="/css/app.css">
    <!-- Page-specific CSS -->
    <?php if (!empty($_css)): ?>
        <?php foreach ($_css as $css): ?>
            <link rel="stylesheet" href="/css/<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
    <script src="/js/notifications.js"></script>
    <script src="/js/addToCart.js"></script>
</head>

<body data-logged-in="<?= $_user ? '1' : '0' ?>">
    <header>
        <nav>
            <div id="left-sidebar">
                <a href="/">
                    <img src="/images/WIS_logo_2.png" alt="Four Eyes Collective Logo">
                    Four Eyes Collective
                </a>
            </div>

            <div id="medium-sidebar">
                <a href="/">Home</a>
                <a href="/page/shoppage.php">Shop</a>
                <a href="/page/about_us.php">About Us</a>
                <?php if ($_user): ?>
                    <a href="/page/Member/profile_recent_order.php">My Order</a>
                    <a href="/page/Member/order_all_rating.php">My Rating</a>
                <?php endif ?>
            </div>

            <div id="right-sidebar">

                <div class="user-dropdown">

                    <?php if ($_user): ?>
                        <!-- When user login - link to profile -->
                        <a href="/page/profile_page.php" class="user-icon-link">
                            <img src="/images/user.png" alt="User Account">
                        </a>
                    <?php else: ?>
                        <!-- else trigger dropdown menu for register / login -->
                        <div class="dropdown-container">
                            <a class="user-icon dropdown-trigger">
                                <img src="/images/user.png" alt="User Account">
                            </a>
                            <div class="dropdown-menu">
                                <a href="/page/login.php">Sign In</a>
                                <a href="/page/member_registration.php">Create Account</a>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
                <?php if ($_user): ?>
                    <!-- Get cart count for current user -->
                    <?php
                    $cart_count = 0;
                    if ($_user && isset($_db)) {
                        try {
                            $stm = $_db->prepare("SELECT SUM(product_qty) as total FROM cart_item 
                                                 WHERE user_id = ? AND item_status = 'in_cart'");
                            $stm->execute([$_user->user_id]);
                            $result = $stm->fetch();
                            $cart_count = $result->total ?? 0;
                        } catch (Exception $e) {
                            // Log error silently
                            error_log("Error getting cart count: " . $e->getMessage());
                            $cart_count = 0;
                        }
                    }
                    ?>

                    <a href="/page/Member/cart.php" style="position: relative;" id="cart-link">
                        <img src="/images/shopping-bag.png" alt="Shopping Cart">
                        <span id="cart-badge" style="display: <?= $cart_count > 0 ? 'flex' : 'none'; ?>;">
                            <?= $cart_count > 0 ? $cart_count : '' ?>
                        </span>
                    </a>

                    <a href="/page/wishlist.php" class="wishlist-link">
                        <img src="/images/heart.png" alt="Wishlist">
                    </a>


                    <a href="/page/logout.php">
                        <img src="/images/logout.png" alt="Logout">
                    </a>
                <?php endif ?>
            </div>
        </nav>
    </header>

    <main>