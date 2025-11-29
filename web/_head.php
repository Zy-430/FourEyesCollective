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
                <a href="/page/membership_page.php">Membership</a>
            </div>

            <div id="right-sidebar">
                <a href="/page/membership_page.php">
                    <img src="/images/user.png" alt="User Account">
                </a>
                <a href="/page/cart.php">
                    <img src="/images/shopping-bag.png" alt="Shopping Cart">
                </a>
            </div>
        </nav>
    </header>

    <main>
