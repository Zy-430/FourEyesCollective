<?php
require '../_base.php';

$_title = 'Four Eyes Collective - Premium Eyewear';
include '../_head.php';
?>

<!-- Hero Section -->
<section class="hero-section" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 100px 0; text-align: center;">
    <div class="hero-content" style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 3.5em; margin-bottom: 20px;">See the World Clearly</h1>
        <p style="font-size: 1.3em; margin-bottom: 40px; opacity: 0.9;">Discover our curated collection of premium eyewear, crafted for style and comfort.</p>
        <a href="/page/shoppage.php" class="cta-button" style="background: #e74c3c; color: white; padding: 15px 40px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 1.1em; transition: background 0.3s ease;">Shop Now</a>
    </div>
</section>

<!-- Featured Collection -->
<section class="featured-collection">
    <div class="collection-header">
        <h2>Featured Collection</h2>
        <a href="/page/shoppage.php" class="view-all">View All</a>
    </div>
    
    <div class="products-grid">
        <div class="product-card">
            <div style="background: #f8f9fa; padding: 40px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Classic Aviator</h3>
            </div>
            <div class="product-code">CA-001</div>
            <div class="product-price">$189</div>
            <button class="add-to-cart">Add to Cart</button>
        </div>
        
        <div class="product-card">
            <div style="background: #f8f9fa; padding: 40px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Vintage Round</h3>
            </div>
            <div class="product-code">VR-002</div>
            <div class="product-price">$179</div>
            <button class="add-to-cart">Add to Cart</button>
        </div>
        
        <div class="product-card">
            <div style="background: #f8f9fa; padding: 40px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Modern Square</h3>
            </div>
            <div class="product-code">MS-003</div>
            <div class="product-price">$199</div>
            <button class="add-to-cart">Add to Cart</button>
        </div>
        
        <div class="product-card">
            <div style="background: #f8f9fa; padding: 40px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Designer Cat Eye</h3>
            </div>
            <div class="product-code">DC-004</div>
            <div class="product-price">$219</div>
            <button class="add-to-cart">Add to Cart</button>
        </div>
    </div>
</section>

<!-- Premium Features -->
<section class="premium-features">
    <div class="feature">
        <h3>Premium Quality</h3>
        <p>All frames crafted with the finest materials and attention to detail</p>
    </div>
    <div class="feature">
        <h3>Expert Service</h3>
        <p>Professional guidance to help you find the perfect pair</p>
    </div>
    <div class="feature">
        <h3>Free Shipping</h3>
        <p>Complimentary shipping on all orders over $100</p>
    </div>
</section>

<!-- Membership CTA -->
<section style="background: #ecf0f1; padding: 60px 0; text-align: center; margin: 60px 0;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="font-family: 'Playfair Display', serif; color: #2c3e50; margin-bottom: 20px;">Join Our Collective</h2>
        <p style="color: #7f8c8d; margin-bottom: 30px; line-height: 1.6;">Become a member and enjoy exclusive benefits, early access to new collections, and special pricing.</p>
        <a href="/page/membership_page.php" class="cta-button" style="background: #2c3e50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: 500;">Learn More</a>
    </div>
</section>

<?php
include '../_foot.php';
?>