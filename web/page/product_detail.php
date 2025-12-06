<?php
require '../_base.php';
require '../lib/db.php';

$id = get('id');
$stm = $_db->prepare("SELECT * FROM product WHERE product_id = ?");
$stm->execute([$id]);
$p = $stm->fetch();

function categoryFolder($catId)
{
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

        <?php $is_logged_in = isset($_SESSION['user']); ?>

        <a href="javascript:void(0)" onclick="addToCart('<?= $p->product_id ?>')"
            class="add-to-cart"
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

    function addToCart(productId) {
        <?php if (!$is_logged_in): ?>
            if (confirm('You need to login to add items to cart. Go to login page?')) {
                const currentUrl = encodeURIComponent(window.location.href);
                window.location.href = '/page/login.php?redirect=' + currentUrl;
            }
        <?php else: ?>
            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);

            xhr.open('POST', 'cart.php');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    showNotification('Product added to cart successfully!', 'success');
                } else {
                    showNotification('Error adding product to cart', 'error');
                }
            };
            xhr.onerror = function() {
                showNotification('Network error. Please try again.', 'error');
            };
            xhr.send(formData);
        <?php endif; ?>
    }

    // Function to show notifications
    function showNotification(message, type) {
        // Remove any existing notifications first
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'custom-notification';
        notification.style.position = 'fixed';
        notification.style.top = '50%';
        notification.style.left = '50%';
        notification.style.transform = 'translate(-50%, -50%)';
        notification.style.padding = '20px 30px';
        notification.style.borderRadius = '8px';
        notification.style.color = 'white';
        notification.style.zIndex = '10000';
        notification.style.fontWeight = 'bold';
        notification.style.textAlign = 'center';
        notification.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
        notification.style.minWidth = '300px';
        notification.style.maxWidth = '80%';
        notification.style.cursor = 'pointer';
        notification.style.transition = 'opacity 0.3s ease';

        if (type === 'success') {
            notification.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
            notification.style.borderLeft = '5px solid #229954';
        } else {
            notification.style.background = 'linear-gradient(135deg, #e74c3c, #c0392b)';
            notification.style.borderLeft = '5px solid #922b21';
        }

        // Add icon
        const icon = document.createElement('span');
        icon.style.marginRight = '10px';
        icon.style.fontSize = '20px';

        if (type === 'success') {
            icon.textContent = '✓';
        } else {
            icon.textContent = '✗';
        }

        const text = document.createElement('span');
        text.textContent = message;

        notification.appendChild(icon);
        notification.appendChild(text);
        document.body.appendChild(notification);

        // Add click to remove functionality
        notification.addEventListener('click', function() {
            this.style.opacity = '0';
            setTimeout(() => {
                if (this.parentNode) {
                    this.parentNode.removeChild(this);
                }
            }, 300); // Match transition duration
        });

        // Remove notification after 3 seconds
        const timeoutId = setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);

        // Clear timeout if notification is clicked
        notification.addEventListener('click', function() {
            clearTimeout(timeoutId);
        });
    }
</script>

<?php include '../_foot.php'; ?>