<?php
require '../_base.php';
require '../lib/db.php';

$id = get('id');
$stm = $_db->prepare("SELECT * FROM product WHERE product_id = ?");
$stm->execute([$id]);
$p = $stm->fetch();

function categoryFolder($catId) {
    return [
        'CA0001' => 'glasses',
        'CA0002' => 'sunglasses',
        'CA0003' => 'contactlens',
        'CA0004' => 'kids'
    ][$catId] ?? 'others';
}

$folder = categoryFolder($p->category_id);
$images = explode(',', $p->product_image);

$_title = $p->product_name;
include '../_head.php';
?>

<h1 style="margin-bottom:20px;"><?= encode($p->product_name) ?></h1>

<div style="display:flex; gap:30px;">

    <!-- LEFT: IMAGE CAROUSEL -->
    <div style="width:400px;">

        <!-- MAIN BIG IMAGE -->
        <div style="position:relative; width:400px; height:400px; overflow:hidden; border:1px solid #ccc; border-radius:8px;">
            <img id="mainImage"
                 src="/images/product/<?= $folder ?>/<?= trim($images[0]) ?>"
                 style="width:100%; height:100%; object-fit:cover;">
            
            <!-- BUTTONS -->
            <button onclick="prevImage()" 
                style="position:absolute; top:50%; left:10px; transform:translateY(-50%); 
                       background:black; color:white; border:none; padding:10px; border-radius:5px; cursor:pointer; opacity:0.7;">
                ❮
            </button>

            <button onclick="nextImage()" 
                style="position:absolute; top:50%; right:10px; transform:translateY(-50%); 
                       background:black; color:white; border:none; padding:10px; border-radius:5px; cursor:pointer; opacity:0.7;">
                ❯
            </button>
        </div>

        <!-- THUMBNAILS -->
        <div style="display:flex; gap:10px; margin-top:10px;">
            <?php foreach ($images as $index => $img): ?>
                <?php $img = trim($img); ?>
                <img onclick="showImage(<?= $index ?>)"
                     src="/images/product/<?= $folder ?>/<?= encode($img) ?>" 
                     style="width:70px; height:70px; object-fit:cover; border:2px solid #ccc; border-radius:6px; cursor:pointer;"
                     id="thumb<?= $index ?>">
            <?php endforeach; ?>
        </div>

    </div>

    <!-- RIGHT: PRODUCT INFO -->
    <div style="flex:1;">
        <p style="font-size:16px;"><?= nl2br(encode($p->product_description)) ?></p>

        <p><strong>Price:</strong> 
            <span style="font-size:22px; color:#2c3e50; font-weight:bold;">
                RM <?= number_format($p->product_price, 2) ?>
            </span>
        </p>

        <p><strong>Quantity in Stock:</strong> <?= number_format($p->product_stock) ?> left</p>

        <a href="add_to_cart.php?id=<?= $p->product_id ?>" 
        style="padding:12px 25px; background:#2c3e50; color:white; border-radius:5px; 
               text-decoration:none; display:inline-block; font-size:16px; margin-top:15px;">
            Add to Cart
        </a>
    </div>

</div>

<!-- CAROUSEL SCRIPT -->
<script>
let images = <?= json_encode(array_map('trim', $images)) ?>;
let folder = "<?= $folder ?>";
let index = 0;

function showImage(i) {
    index = i;
    document.getElementById("mainImage").src = "/images/product/" + folder + "/" + images[index];
}

function nextImage() {
    index = (index + 1) % images.length;
    showImage(index);
}

function prevImage() {
    index = (index - 1 + images.length) % images.length;
    showImage(index);
}

// Auto slideshow (every 3 seconds)
setInterval(nextImage, 3000);
</script>

<?php include '../_foot.php'; ?>
