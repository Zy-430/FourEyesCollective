<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_1.png">
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="/js/app.js"></script>
</head>

<body>
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
                <a href="/page/cart.php">
                    <img src="/images/shopping-bag.png" alt="Shopping Cart">
                </a>
                <?php if ($_user): ?>
                <a href="/page/logout.php">
                    <img src="/images/logout.png" alt="Logout">
                </a>
                <?php endif ?>
            </div>
        </nav>
    </header>

    <main>
