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
<section class="hero-section" style="
    background: linear-gradient(rgba(44, 62, 80, 0.85), rgba(52, 73, 94, 0.9)), 
                url('/images/background_2.jpg') center/cover no-repeat;
    color: white; 
    padding: 120px 0 80px;
    text-align: center;
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
">
    <div class="hero-content" style="
        max-width: 1200px; 
        margin: 0 auto;
        padding: 0 20px;
        position: relative;
        z-index: 2;
    ">
        <h1 style="
            font-family: 'Playfair Display', serif; 
            font-size: 4em; 
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            line-height: 1.1;
        ">See the World Clearly</h1>
        <p style="
            font-size: 1.4em; 
            margin-bottom: 40px; 
            opacity: 0.95;
            font-weight: 300;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        ">Discover our curated collection of premium eyewear, crafted for style and comfort.</p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="/page/shoppage.php" class="cta-button" style="
                background: #e74c3c; 
                color: white; 
                padding: 15px 40px; 
                text-decoration: none; 
                border-radius: 4px; 
                font-weight: 600; 
                font-size: 1.1em; 
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            ">Shop Collection</a>
        </div>
    </div>
</section>

<!-- Top 5 Best Sellers - Now in one row -->
<section class="featured-collection" style="margin: 80px 0; padding: 0 50px;">
    <div style="max-width: 1400px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="
                color: #2c3e50; 
                font-family: 'Playfair Display', serif;
                font-size: 2.8em;
                margin: 0 0 15px 0;
                font-weight: 700;
            ">Top 5 Best Sellers</h2>
            <p style="
                color: #7f8c8d; 
                font-size: 1.1em; 
                max-width: 600px; 
                margin: 0 auto 30px;
                line-height: 1.6;
            ">Our most popular eyewear chosen by thousands of satisfied customers</p>
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
                    <a href="product_detail.php?id=<?= $product->product_id ?>" class="product-card">
                        <!-- Image Container -->
                        <div class="product-image-container">
                            <img src="<?= $imgPath ?>"
                                alt="<?= encode($product->product_name) ?>">

                            <!-- Heart Icon (Wishlist) -->
                            <button class="product-heart-btn wishlist-btn"
                                data-product-id="<?= $product->product_id ?>"
                                onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist('<?= $product->product_id ?>', this);">
                                <i class="far fa-heart"></i>
                            </button>

                            <!-- Mobile badge for top 3 -->
                            <?php if ($index < 3): ?>
                                <div class="mobile-badge">
                                    #<?= $index + 1 ?>
                                </div>
                            <?php endif; ?>
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

                            <!-- Price & Sold - UPDATED STYLE -->
                            <div class="product-footer">
                                <div class="product-price">
                                    RM <?= number_format($product->product_price, 2) ?>
                                </div>
                                <div class="product-sold" style="
                            background: #f8f9fa;
                            padding: 4px 10px;
                            border-radius: 12px;
                            font-size: 12px;
                            color: #666;
                        ">
                                    <span style="font-weight: 700; color: #2c3e50; margin-right: 4px;">
                                        <?= number_format($soldCount) ?>
                                    </span> sold
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Categories Quick Access Section - Now in one row -->
<section class="categories-section" style="padding: 40px 50px; background: #f8f9fa;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 style="
            text-align: center;
            margin-bottom: 40px;
            color: #2c3e50;
            font-family: 'Playfair Display', serif;
            font-size: 2.5em;
            font-weight: 600;
        ">Browse by Category</h2>

        <div style="
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        ">
            <a href="/page/shoppage.php?cat=CA0001" class="category-card" style="
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 30px 20px;
                background: white;
                border-radius: 12px;
                text-decoration: none;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                border: 1px solid #e0e0e0;
                height: 100%;
            ">
                <div class="category-icon" style="
                    width: 80px;
                    height: 80px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f0f7ff;
                    border-radius: 50%;
                    transition: all 0.3s ease;
                ">
                    <img src="/images/glasses-icon.png" alt="Glasses" style="width: 40px; height: 40px;">
                </div>
                <div class="category-name" style="
                    font-size: 1.3rem;
                    font-weight: 600;
                    color: #2c3e50;
                    text-align: center;
                    margin-top: 10px;
                    font-family: 'Playfair Display', serif;
                ">Glasses</div>
            </a>

            <a href="/page/shoppage.php?cat=CA0002" class="category-card" style="
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 30px 20px;
                background: white;
                border-radius: 12px;
                text-decoration: none;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                border: 1px solid #e0e0e0;
                height: 100%;
            ">
                <div class="category-icon" style="
                    width: 80px;
                    height: 80px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f0f7ff;
                    border-radius: 50%;
                    transition: all 0.3s ease;
                ">
                    <img src="/images/sunglasses-icon.png" alt="Sunglasses" style="width: 40px; height: 40px;">
                </div>
                <div class="category-name" style="
                    font-size: 1.3rem;
                    font-weight: 600;
                    color: #2c3e50;
                    text-align: center;
                    margin-top: 10px;
                    font-family: 'Playfair Display', serif;
                ">Sunglasses</div>
            </a>

            <a href="/page/shoppage.php?cat=CA0003" class="category-card" style="
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 30px 20px;
                background: white;
                border-radius: 12px;
                text-decoration: none;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                border: 1px solid #e0e0e0;
                height: 100%;
            ">
                <div class="category-icon" style="
                    width: 80px;
                    height: 80px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f0f7ff;
                    border-radius: 50%;
                    transition: all 0.3s ease;
                ">
                    <img src="/images/contactlens-icon.png" alt="Contact Lens" style="width: 40px; height: 40px;">
                </div>
                <div class="category-name" style="
                    font-size: 1.3rem;
                    font-weight: 600;
                    color: #2c3e50;
                    text-align: center;
                    margin-top: 10px;
                    font-family: 'Playfair Display', serif;
                ">Contact Lens</div>
            </a>

            <a href="/page/shoppage.php?cat=CA0004" class="category-card" style="
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 30px 20px;
                background: white;
                border-radius: 12px;
                text-decoration: none;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                border: 1px solid #e0e0e0;
                height: 100%;
            ">
                <div class="category-icon" style="
                    width: 80px;
                    height: 80px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f0f7ff;
                    border-radius: 50%;
                    transition: all 0.3s ease;
                ">
                    <img src="/images/kids-icon.png" alt="Kids" style="width: 40px; height: 40px;">
                </div>
                <div class="category-name" style="
                    font-size: 1.3rem;
                    font-weight: 600;
                    color: #2c3e50;
                    text-align: center;
                    margin-top: 10px;
                    font-family: 'Playfair Display', serif;
                ">Kids</div>
            </a>
        </div>
    </div>
</section>

<!-- Premium Features -->
<section style="padding: 80px 50px; background: white;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
        ">
            <div class="feature-card" style="
                text-align: center;
                padding: 40px 30px;
                background: #f8f9fa;
                border-radius: 12px;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            ">
                <div style="
                    font-size: 50px;
                    color: #3498db;
                    margin-bottom: 20px;
                ">✓</div>
                <h3 style="
                    color: #2c3e50; 
                    font-family: 'Playfair Display', serif; 
                    margin-bottom: 15px;
                    font-size: 1.5em;
                ">Premium Quality</h3>
                <p style="color: #7f8c8d; line-height: 1.6; margin-bottom: 0;">
                    All frames crafted with the finest materials and attention to detail
                </p>
            </div>

            <div class="feature-card" style="
                text-align: center;
                padding: 40px 30px;
                background: #f8f9fa;
                border-radius: 12px;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            ">
                <div style="
                    font-size: 50px;
                    color: #2ecc71;
                    margin-bottom: 20px;
                ">👓</div>
                <h3 style="
                    color: #2c3e50; 
                    font-family: 'Playfair Display', serif; 
                    margin-bottom: 15px;
                    font-size: 1.5em;
                ">Expert Service</h3>
                <p style="color: #7f8c8d; line-height: 1.6; margin-bottom: 0;">
                    Professional guidance to help you find the perfect pair
                </p>
            </div>

            <div class="feature-card" style="
                text-align: center;
                padding: 40px 30px;
                background: #f8f9fa;
                border-radius: 12px;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            ">
                <div style="
                    font-size: 50px;
                    color: #e74c3c;
                    margin-bottom: 20px;
                ">🚚</div>
                <h3 style="
                    color: #2c3e50; 
                    font-family: 'Playfair Display', serif; 
                    margin-bottom: 15px;
                    font-size: 1.5em;
                ">Free Shipping</h3>
                <p style="color: #7f8c8d; line-height: 1.6; margin-bottom: 0;">
                    Complimentary shipping on all orders over RM 500
                </p>
            </div>
        </div>
    </div>
</section>

<?php if (!$is_logged_in): ?>
    <!-- Membership CTA -->
    <section style="
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        padding: 80px 50px;
        margin: 40px 0;
    ">
        <div style="
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        ">
            <h2 style="
                font-family: 'Playfair Display', serif;
                margin-bottom: 20px;
                font-size: 2.8em;
            ">Join Four Eyes Collective Today!</h2>
            <p style="
                margin-bottom: 40px;
                font-size: 1.2em;
                opacity: 0.95;
                line-height: 1.6;
            ">
                Register for a free exclusive membership
            </p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="/page/member_registration.php" class="btn-hover" style="
                    background: #e74c3c;
                    color: white;
                    padding: 15px 40px;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: 600;
                    font-size: 1.1em;
                    transition: all 0.3s ease;
                    border: 2px solid #e74c3c;
               ">
                    Register Free
                </a>
                <a href="/page/login.php"
                    style="
                    background: transparent;
                    color: white;
                    padding: 15px 40px;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: 600;
                    font-size: 1.1em;
                    transition: all 0.3s ease;
                    border: 2px solid white;
               "
                    class="btn-hover">
                    Sign In
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects to category cards without changing icon color
        const categoryCards = document.querySelectorAll('.category-card');
        categoryCards.forEach(card => {
            const icon = card.querySelector('.category-icon');

            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px)';
                card.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.15)';
                card.style.borderColor = '#2c3e50';
                // Only scale the icon, don't change color
                if (icon) {
                    icon.style.transform = 'scale(1.1)';
                }
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.08)';
                card.style.borderColor = '#e0e0e0';
                if (icon) {
                    icon.style.transform = 'scale(1)';
                    // Ensure background color stays the same
                    icon.style.background = '#f0f7ff';
                }
            });
        });

        // Add hover effects to product cards
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.1)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = 'none';
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const topProducts = document.querySelectorAll('.top5-product');

        function updateBadgeVisibility() {
            if (window.innerWidth <= 768) {
                // Show mobile badges
                topProducts.forEach((product, index) => {
                    const badge = product.querySelector('div[style*="position: absolute"]');
                    if (badge && index < 3) {
                        badge.style.display = 'block';
                    }
                });
            } else {
                // Hide mobile badges
                topProducts.forEach(product => {
                    const badge = product.querySelector('div[style*="position: absolute"]');
                    if (badge) {
                        badge.style.display = 'none';
                    }
                });
            }
        }

        // Initial check
        updateBadgeVisibility();

        // Update on resize
        window.addEventListener('resize', updateBadgeVisibility);
    });
</script>

<style>
    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .products-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }

        .categories-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }

        .categories-grid {
            grid-template-columns: 1fr !important;
        }

        section {
            padding: 40px 20px !important;
        }

        .featured-collection {
            padding: 0 20px !important;
        }

        .hero-content h1 {
            font-size: 2.5em !important;
        }

        .hero-content p {
            font-size: 1.1em !important;
        }
    }

    @media (max-width: 480px) {
        .products-grid {
            grid-template-columns: 1fr !important;
        }

        .hero-content h1 {
            font-size: 2em !important;
        }
    }
</style>

<?php
include '../_foot.php';
?>