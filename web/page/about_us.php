<?php
require '../_base.php';
require '../lib/db.php';

$_title = "About Us | Four Eyes Collective";
include '../_head.php';
?>

<!-- Hero Section -->
<section class="about-hero">
    <h1>About Four Eyes Collective</h1>
    <p class="tagline">Where Vision Meets Style, and Craftsmanship Creates Legacy</p>
</section>

<!-- Story Section -->
<div class="about-content">
    <section class="about-story-section">
        <div class="story-text">
            <h2>Our Visionary Journey</h2>
            <p>Four Eyes Collective was born from a simple yet powerful idea: eyewear should transcend mere vision correction. It should be an extension of personality, a statement of style, and a testament to impeccable craftsmanship.</p>
            <p>Founded in 2020, our journey began with a passion for helping people see the world clearly while expressing their unique identities. We believe that the right pair of glasses can transform not just how you see the world, but how the world sees you.</p>
            <p>Today, we stand as a collective of designers, opticians, and style enthusiasts dedicated to redefining eyewear culture.</p>
        </div>
        <div class="story-image">
            <img src="/images/about-story.png" alt="Our Story - Crafting Eyewear">
        </div>
    </section>

    <!-- Core Values -->
    <section class="values-grid">
        <div class="value-card">
            <div class="value-icon">👑</div>
            <h4>Uncompromising Quality</h4>
            <p>Every frame is crafted using premium acetate, titanium, and stainless steel, ensuring durability and comfort that lasts.</p>
        </div>
        
        <div class="value-card">
            <div class="value-icon">🎨</div>
            <h4>Timeless Design</h4>
            <p>Our designs blend classic elegance with contemporary aesthetics, creating pieces that remain stylish for years to come.</p>
        </div>
        
        <div class="value-card">
            <div class="value-icon">❤️</div>
            <h4>Personalized Service</h4>
            <p>From virtual try-ons to expert fittings, we provide personalized guidance to find your perfect match.</p>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="about-story-section">
        <div class="story-image">
            <img src="/images/about-mission.png" alt="Our Mission - Quality Eyewear">
        </div>
        <div class="story-text">
            <h2>Our Commitment</h2>
            <p>We're on a mission to revolutionize the eyewear experience. Our commitment extends beyond providing exceptional eyewear to creating lasting relationships with our community.</p>
            <p>We carefully curate each collection, ensuring every pair meets our rigorous standards of optical precision, comfort, and style. From prescription glasses to designer sunglasses, every product tells a story of meticulous craftsmanship.</p>
            <p>Through sustainable practices and community engagement, we aim to not just be an eyewear brand, but a movement toward clearer vision and confident style.</p>
        </div>
    </section>
</div>

<?php
include '../_foot.php';
?>