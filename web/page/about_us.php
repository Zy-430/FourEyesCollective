<?php
require '../_base.php';

$_title = 'About Us - Four Eyes Collective';
include '../_head.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <h2>Our Story</h2>
    <p style="line-height: 1.8; margin-bottom: 30px; color: #555;">
        Four Eyes Collective was born from a simple idea: eyewear should be more than just a vision correction tool. 
        It should be an expression of personality, a statement of style, and a testament to quality craftsmanship.
    </p>
    
    <h3>Our Mission</h3>
    <p style="line-height: 1.8; margin-bottom: 30px; color: #555;">
        We're committed to providing exceptional eyewear that combines timeless design with modern innovation. 
        Each pair in our collection is carefully curated to ensure it meets our high standards of quality, comfort, and style.
    </p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin: 40px 0;">
        <div style="text-align: center;">
            <h4 style="color: #2c3e50;">Quality</h4>
            <p>Premium materials and expert craftsmanship</p>
        </div>
        <div style="text-align: center;">
            <h4 style="color: #2c3e50;">Style</h4>
            <p>Curated designs for every personality</p>
        </div>
        <div style="text-align: center;">
            <h4 style="color: #2c3e50;">Service</h4>
            <p>Expert guidance and support</p>
        </div>
    </div>
</div>

<?php
include '../_foot.php';
?>