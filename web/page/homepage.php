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

<!-- Hero Section with Background Image -->
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
        max-width: 800px; 
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

<!-- Top 5 Best Sellers Carousel -->
<section class="featured-collection" style="margin: 60px 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        ">
            <h2 style="
                color: #2c3e50; 
                font-family: 'Playfair Display', serif;
                font-size: 2.5em;
                margin: 0;
            ">Top 5 Best Sellers</h2>
            <div style="display: flex; gap: 10px;">
                <button class="carousel-prev" style="
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: #2c3e50;
                    color: white;
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                ">❮</button>
                <button class="carousel-next" style="
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: #2c3e50;
                    color: white;
                    border: none;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.3s ease;
                ">❯</button>
            </div>
        </div>

        <!-- Carousel Container -->
        <div class="carousel-container" style="
            position: relative;
            overflow: hidden;
            padding: 10px 0;
        ">
            <div class="carousel-track" style="
                display: flex;
                gap: 30px;
                transition: transform 0.5s ease;
                padding: 10px;
            ">
                <?php foreach ($topProducts as $index => $product): ?>
                    <?php
                    $folder = $categoryFolders[$product->category_id] ?? 'others';
                    $imgArray = explode(',', $product->product_image);
                    $firstImage = trim($imgArray[0]);
                    $imgPath = "/images/product/$folder/$firstImage";
                    $soldCount = $product->total_sold ?? 0;
                    ?>

                    <div class="carousel-slide" style="
                        flex: 0 0 calc(33.333% - 20px);
                        min-width: 300px;
                        border: 1px solid #e0e0e0;
                        border-radius: 12px;
                        padding: 20px;
                        background: white;
                        position: relative;
                        transition: all 0.3s ease;
                        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
                    ">
                        <!-- Best Seller Badge -->
                        <div style="
                            position: absolute;
                            top: 15px;
                            left: 15px;
                            background: #e74c3c;
                            color: white;
                            padding: 5px 12px;
                            border-radius: 20px;
                            font-size: 12px;
                            font-weight: bold;
                            z-index: 1;
                        ">
                            #<?= $index + 1 ?> Best Seller
                        </div>

                        <!-- Image Container -->
                        <div style="
                            height: 220px;
                            overflow: hidden;
                            border-radius: 8px;
                            margin-bottom: 20px;
                            position: relative;
                        ">
                            <img src="<?= $imgPath ?>"
                                alt="<?= encode($product->product_name) ?>"
                                style="
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                    transition: transform 0.5s ease;
                                 "
                                onmouseover="this.style.transform='scale(1.05)'"
                                onmouseout="this.style.transform='scale(1)'">
                        </div>

                        <!-- Product Info -->
                        <div style="margin-bottom: 15px;">
                            <h3 style="
                                font-size: 18px;
                                margin: 0 0 8px 0;
                                color: #2c3e50;
                                font-family: 'Roboto', sans-serif;
                                font-weight: 600;
                                height: 44px;
                                overflow: hidden;
                                display: -webkit-box;
                                -webkit-line-clamp: 2;
                                -webkit-box-orient: vertical;
                            "><?= encode($product->product_name) ?></h3>

                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-size: 20px; font-weight: 700; color: #2c3e50;">
                                    RM <?= number_format($product->product_price, 2) ?>
                                </div>
                                <div style="font-size: 13px; color: #666;">
                                    <span style="color: #e74c3c; font-weight: bold;">
                                        <?= number_format($soldCount) ?> sold
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Button -->
                        <a href="product_detail.php?id=<?= $product->product_id ?>"
                            style="
                                display: block;
                                text-align: center;
                                padding: 12px;
                                background: #2c3e50;
                                color: white;
                                border-radius: 6px;
                                text-decoration: none;
                                font-weight: 600;
                                transition: all 0.3s ease;
                                border: 2px solid #2c3e50;
                           "
                            onmouseover="this.style.background='#34495e'; this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.background='#2c3e50'; this.style.transform='translateY(0)'">
                            View Details
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Carousel Indicators -->
        <div style="
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        ">
            <?php for ($i = 0; $i < ceil(count($topProducts) / 3); $i++): ?>
                <button class="carousel-dot" data-index="<?= $i ?>" style="
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    border: none;
                    background: <?= $i === 0 ? '#2c3e50' : '#bdc3c7' ?>;
                    cursor: pointer;
                    transition: background 0.3s ease;
                "></button>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- Premium Features -->
<section style="padding: 80px 0; background: white;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
        ">
            <div style="
                text-align: center;
                padding: 40px 30px;
                background: #f8f9fa;
                border-radius: 12px;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            "
                onmouseover="this.style.transform='translateY(-10px)'; this.style.borderColor='#3498db'"
                onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='transparent'">
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

            <div style="
                text-align: center;
                padding: 40px 30px;
                background: #f8f9fa;
                border-radius: 12px;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            "
                onmouseover="this.style.transform='translateY(-10px)'; this.style.borderColor='#2ecc71'"
                onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='transparent'">
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

            <div style="
                text-align: center;
                padding: 40px 30px;
                background: #f8f9fa;
                border-radius: 12px;
                transition: all 0.3s ease;
                border: 2px solid transparent;
            "
                onmouseover="this.style.transform='translateY(-10px)'; this.style.borderColor='#e74c3c'"
                onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='transparent'">
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
                    Complimentary shipping on all orders over RM 100
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
    padding: 80px 0;
    margin: 40px 0;
    position: relative;
    overflow: hidden;
">
        <div style="
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px;
        text-align: center;
        position: relative;
        z-index: 2;
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
                <a href="/page/member_registration.php"
                    style="
                    background: #e74c3c;
                    color: white;
                    padding: 15px 40px;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: 600;
                    font-size: 1.1em;
                    transition: all 0.3s ease;
                    border: 2px solid #e74c3c;
               "
                    onmouseover="this.style.background='#c0392b'; this.style.transform='translateY(-3px)'"
                    onmouseout="this.style.background='#e74c3c'; this.style.transform='translateY(0)'">
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
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(-3px)'"
                    onmouseout="this.style.background='transparent'; this.style.transform='translateY(0)'">
                    Sign In
                </a>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
    // Carousel functionality for top 5 products
    document.addEventListener('DOMContentLoaded', function() {
        const carouselTrack = document.querySelector('.carousel-track');
        const slides = document.querySelectorAll('.carousel-slide');
        const prevBtn = document.querySelector('.carousel-prev');
        const nextBtn = document.querySelector('.carousel-next');
        const dots = document.querySelectorAll('.carousel-dot');

        let currentIndex = 0;
        const slidesToShow = 3;
        const slideCount = slides.length;
        const maxIndex = Math.max(0, Math.ceil(slideCount / slidesToShow) - 1);

        // Initialize slide widths for responsiveness
        function updateSlideWidth() {
            const containerWidth = carouselTrack.parentElement.clientWidth;
            const slideWidth = containerWidth / slidesToShow - 20; // 20px for gap
            slides.forEach(slide => {
                slide.style.flex = `0 0 ${slideWidth}px`;
            });
        }

        updateSlideWidth();
        window.addEventListener('resize', updateSlideWidth);

        // Update carousel position
        function updateCarousel() {
            const slideWidth = slides[0].offsetWidth + 30; // width + gap
            carouselTrack.style.transform = `translateX(-${currentIndex * slideWidth * slidesToShow}px)`;

            // Update dots
            dots.forEach((dot, index) => {
                dot.style.background = index === currentIndex ? '#2c3e50' : '#bdc3c7';
            });
        }

        // Event listeners for buttons
        prevBtn.addEventListener('click', () => {
            currentIndex = Math.max(0, currentIndex - 1);
            updateCarousel();
        });

        nextBtn.addEventListener('click', () => {
            currentIndex = Math.min(maxIndex, currentIndex + 1);
            updateCarousel();
        });

        // Event listeners for dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentIndex = index;
                updateCarousel();
            });
        });

        // Auto-advance carousel (optional)
        let autoSlideInterval = setInterval(() => {
            if (currentIndex >= maxIndex) {
                currentIndex = 0;
            } else {
                currentIndex++;
            }
            updateCarousel();
        }, 5000);

        // Pause auto-slide on hover
        carouselTrack.parentElement.addEventListener('mouseenter', () => {
            clearInterval(autoSlideInterval);
        });

        carouselTrack.parentElement.addEventListener('mouseleave', () => {
            autoSlideInterval = setInterval(() => {
                if (currentIndex >= maxIndex) {
                    currentIndex = 0;
                } else {
                    currentIndex++;
                }
                updateCarousel();
            }, 5000);
        });

        // Initialize
        updateCarousel();

        // Category card hover effects
        const categoryCards = document.querySelectorAll('.category-card');
        categoryCards.forEach(card => {
            const overlay = card.querySelector('div:last-child');
            const button = overlay.querySelector('div');

            card.addEventListener('mouseenter', () => {
                overlay.style.opacity = '1';
                button.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 12px 25px rgba(0,0,0,0.2)';
            });

            card.addEventListener('mouseleave', () => {
                overlay.style.opacity = '0';
                button.style.transform = 'translateY(20px)';
                card.style.boxShadow = '0 6px 20px rgba(0,0,0,0.15)';
            });
        });
    });
</script>

<style>
    @media (max-width: 1024px) {
        .carousel-slide {
            flex: 0 0 calc(50% - 15px) !important;
        }
    }

    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 2.5em !important;
        }

        .carousel-slide {
            flex: 0 0 calc(100% - 10px) !important;
        }

        .category-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 480px) {
        .hero-content h1 {
            font-size: 2em !important;
        }

        .hero-content p {
            font-size: 1.1em !important;
        }

        .cta-buttons {
            flex-direction: column;
            gap: 10px !important;
        }

        .cta-button {
            width: 100%;
            text-align: center;
        }
    }
</style>

<?php
include '../_foot.php';
?>