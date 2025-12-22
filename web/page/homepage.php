<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/category.php';
require '../lib/product_stats.php';

$_title = 'Four Eyes Collective - Premium Eyewear';
include '../_head.php';

// Get top 5 best selling products
$topProducts = getTopSellingProducts(5);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <h1>See the World Clearly</h1>
        <p class="hero-lead">Discover our curated collection of premium eyewear, crafted for style and comfort.</p>
        <div class="hero-cta">
            <a href="/page/shoppage.php" class="cta-button primary">Shop Collection</a>
        </div>
    </div>
</section>

<!-- Top 5 Best Sellers - Now in one row -->
<section class="featured-collection">
    <div class="container">
        <div class="collection-header">
            <h2>Top 5 Best Sellers</h2>
            <p class="collection-sub">Our most popular eyewear chosen by thousands of satisfied customers</p>
        </div>

        <!-- Products Grid - Special Top 5 Layout -->
        <div class="top5-grid">
            <?php foreach ($topProducts as $index => $product): ?>
                <?php
                $folder = $categoryFolders[$product->category_id] ?? 'others';
                $imgArray = explode(',', $product->product_image);
                $firstImage = trim($imgArray[0]);
                $imgPath = "/images/product/$folder/$firstImage";
                $soldCount = $product->total_sold ?? 0;
                ?>

                <div class="top5-product">
                    <a href="product_detail.php?id=<?= $product->product_id ?>&return_url=/page/homepage.php" class="product-card">
                        <!-- Image Container -->
                        <div class="product-image-container">
                            <img class="product-image" src="<?= $imgPath ?>" alt="<?= encode($product->product_name) ?>">

                            <!-- Heart Icon (Wishlist) -->
                            <button class="product-heart-btn wishlist-btn" data-product-id="<?= $product->product_id ?>">
                                <i class="far fa-heart"></i>
                            </button>

                            <div class="top-product-sold"><span class="sold-count"><?= number_format($soldCount) ?></span> sold</div>
                        </div>

                        <!-- Product Info -->
                        <div class="product-info">

                            <!-- Category -->
                            <div class="product-category">
                                <?= encode($categories[$product->category_id] ?? 'Unknown') ?>
                            </div>

                            <!-- Product Name -->
                            <h3 class="product-name">
                                <?= encode($product->product_name) ?>
                            </h3>

                            <!-- Price & Sold  -->
                            <div class="product-footer">
                                <div class="product-price">
                                    RM <?= number_format($product->product_price, 2) ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Categories Quick Access Section -->
<section class="categories-section">
    <div class="container">
        <h2>Browse by Category</h2>
        <div class="categories-grid">
            <a href="/page/shoppage.php?cat=CA0001" class="category-card">
                <div class="category-icon">
                    <img src="/images/glasses-icon.png" alt="Glasses">
                </div>
                <div class="category-name">Glasses</div>
            </a>

            <a href="/page/shoppage.php?cat=CA0002" class="category-card">
                <div class="category-icon">
                    <img src="/images/sunglasses-icon.png" alt="Sunglasses">
                </div>
                <div class="category-name">Sunglasses</div>
            </a>

            <a href="/page/shoppage.php?cat=CA0003" class="category-card">
                <div class="category-icon">
                    <img src="/images/contactlens-icon.png" alt="Contact Lens">
                </div>
                <div class="category-name">Contact Lens</div>
            </a>

            <a href="/page/shoppage.php?cat=CA0004" class="category-card">
                <div class="category-icon">
                    <img src="/images/kids-icon.png" alt="Kids">
                </div>
                <div class="category-name">Kids</div>
            </a>
        </div>
    </div>
</section>

<!-- Premium Features -->
<section class="premium-features">
    <div class="container">

        <div class="collection-header">
            <h2>Why Choose Four Eyes Collective?</h2>
            <p class="features-sub">Experience the difference with our exceptional eyewear and services</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon feature-icon-blue">✓</div>
                <h3>Premium Quality</h3>
                <p>All frames crafted with the finest materials and attention to detail</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon feature-icon-green">👓</div>
                <h3>Expert Service</h3>
                <p>Professional guidance to help you find the perfect pair</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon feature-icon-red">🚚</div>
                <h3>Free Shipping</h3>
                <p>Complimentary shipping on all orders over RM 500</p>
            </div>
        </div>
    </div>
</section>

<?php if (!$is_logged_in): ?>
    <!-- Membership CTA -->
    <section class="membership-cta">
        <div class="membership-content">
            <h2>Join Four Eyes Collective Today!</h2>
            <p>Register for a free exclusive membership</p>
            <div class="membership-cta-actions">
                <a href="/page/member_registration.php" class="btn-hover btn-primary">Register Free</a>
                <a href="/page/login.php" class="btn-hover btn-outline">Sign In</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
include '../_foot.php';
?>