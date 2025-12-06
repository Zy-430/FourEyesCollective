<?php
require '../_base.php';
require '../lib/db.php';

// Include SimplePager class (assuming it's in lib folder)
require '../lib/SimplePager.php';

$_title = 'Home | Shop';
include '../_head.php';

// Get filter parameters
$search = get('search', '');
$selectedCat = get('cat');
$minPrice = get('min_price', 0);
$maxPrice = get('max_price', 1000);
$priceRange = get('price_range', '');

// Get sorting parameters
$sort = get('sort', 'product_id');
$dir = get('dir', 'asc');

// Get page parameter for pagination
$page = get('page', 1);

// Validate sort field and direction
$allowedSortFields = ['product_id', 'product_name', 'product_price', 'product_stock'];
if (!in_array($sort, $allowedSortFields)) {
    $sort = 'product_id';
}

if (!in_array($dir, ['asc', 'desc'])) {
    $dir = 'asc';
}

// Build SQL query with filters
$sql = "SELECT * FROM product WHERE product_status = 1";
$params = [];

// Search filter
if ($search) {
    $sql .= " AND (product_name LIKE ? OR product_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Category filter
if ($selectedCat) {
    $sql .= " AND category_id = ?";
    $params[] = $selectedCat;
}

// Price range filter
if ($priceRange) {
    list($min, $max) = explode('-', $priceRange);
    $sql .= " AND product_price >= ? AND product_price <= ?";
    $params[] = floatval($min);
    $params[] = floatval($max);
} elseif ($minPrice > 0 || $maxPrice < 1000) {
    // Custom price range from inputs
    $sql .= " AND product_price >= ? AND product_price <= ?";
    $params[] = floatval($minPrice);
    $params[] = floatval($maxPrice);
}

// Add sorting
$sql .= " ORDER BY $sort $dir";

// Execute query with pagination (6 products per page)
$limit = 6; // 6 products per page as requested
$pager = new SimplePager($sql, $params, $limit, $page);
$products = $pager->result; // This gets the current page's products

// Get price range for products
$priceStm = $_db->query("SELECT MIN(product_price) as min_price, MAX(product_price) as max_price FROM product WHERE product_status = 1");
$priceRangeData = $priceStm->fetch();
$actualMinPrice = $priceRangeData->min_price ?? 0;
$actualMaxPrice = $priceRangeData->max_price ?? 1000;

// Category mapping
function categoryFolder($catId)
{
    return [
        'CA0001' => 'glasses',
        'CA0002' => 'sunglasses',
        'CA0003' => 'contactlens',
        'CA0004' => 'kids'
    ][$catId] ?? 'others';
}

$categoryNames = [
    'CA0001' => 'Glasses',
    'CA0002' => 'Sunglasses',
    'CA0003' => 'Contact Lens',
    'CA0004' => 'Kids'
];

// Predefined price ranges
$priceRanges = [
    '0-100' => 'Under RM 100',
    '100-250' => 'RM 100 - RM 250',
    '250-400' => 'RM 250 - RM 400',
    '400-1000' => 'RM 400+'
];

// Build current filter parameters for maintaining state in URLs
$currentParams = [
    'search' => $search,
    'cat' => $selectedCat,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'price_range' => $priceRange,
    'sort' => $sort,
    'dir' => $dir
];
$filterQuery = http_build_query($currentParams);
?>

<h1 style="margin-bottom: 20px;">Our Eyewear Collection</h1>

<div style="display: flex; gap: 30px;">

    <!-- LEFT: FILTERS SIDEBAR -->
    <div style="width: 250px; flex-shrink: 0;">
        <form method="get" id="filterForm">
            <!-- Hidden fields to maintain pagination and sorting -->
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="sort" value="<?= $sort ?>">
            <input type="hidden" name="dir" value="<?= $dir ?>">

            <!-- SEARCH -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 10px; color: #2c3e50;">Search</h3>
                <input type="text"
                    name="search"
                    placeholder="Search products..."
                    value="<?= htmlspecialchars($search) ?>"
                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <!-- CATEGORY FILTER -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 10px; color: #2c3e50;">Category</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="radio"
                            name="cat"
                            value=""
                            <?= !$selectedCat ? 'checked' : '' ?>
                            onchange="this.form.submit()">
                        <span>All Products</span>
                    </label>
                    <?php foreach ($categoryNames as $id => $name): ?>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="radio"
                                name="cat"
                                value="<?= $id ?>"
                                <?= ($selectedCat === $id) ? 'checked' : '' ?>
                                onchange="this.form.submit()">
                            <span><?= $name ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PRICE RANGE FILTER -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 10px; color: #2c3e50;">Price Range</h3>

                <!-- Predefined price ranges -->
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="radio"
                                name="price_range"
                                value=""
                                <?= !$priceRange ? 'checked' : '' ?>
                                onchange="this.form.submit()">
                            <span>All Prices</span>
                        </label>
                        <?php foreach ($priceRanges as $range => $label): ?>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="radio"
                                    name="price_range"
                                    value="<?= $range ?>"
                                    <?= ($priceRange === $range) ? 'checked' : '' ?>
                                    onchange="this.form.submit()">
                                <span><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Custom price range -->
                <div>
                    <div style="margin-bottom: 10px; font-size: 14px;">
                        Custom Range:
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <input type="number"
                            name="min_price"
                            value="<?= $minPrice ?>"
                            min="0"
                            max="<?= $actualMaxPrice ?>"
                            step="10"
                            style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        <span>to</span>
                        <input type="number"
                            name="max_price"
                            value="<?= $maxPrice ?>"
                            min="0"
                            max="<?= $actualMaxPrice ?>"
                            step="10"
                            style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <button type="submit"
                        style="padding: 8px 16px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Apply Price
                    </button>
                </div>
            </div>

            <!-- APPLY SEARCH BUTTON -->
            <button type="submit"
                style="width: 100%; padding: 10px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                Apply Filters
            </button>

            <!-- CLEAR FILTERS BUTTON -->
            <?php if ($search || $selectedCat || $priceRange || $minPrice > 0 || $maxPrice < 1000): ?>
                <a href="shoppage.php?sort=<?= $sort ?>&dir=<?= $dir ?>"
                    style="display: block; padding: 10px; background: #e74c3c; color: white; text-align: center; border-radius: 4px; text-decoration: none; margin-top: 10px;">
                    Clear All Filters
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- RIGHT: PRODUCT GRID -->
    <div style="flex: 1;">
        <!-- Sorting Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
            <div>
                <p style="margin: 0;">
                    <?php if ($pager->item_count === 0): ?>
                        No products found
                    <?php else: ?>
                        Showing <?= $pager->count ?> of <?= $pager->item_count ?> product(s)
                        <?php if ($search): ?>
                            for "<?= htmlspecialchars($search) ?>"
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <span>Sort by:</span>
                <div style="display: flex; gap: 10px;">
                    <?php
                    // Define sorting options
                    $sortOptions = [
                        'product_name' => 'Name',
                        'product_price' => 'Price',
                        'product_id' => 'Newest'
                    ];

                    foreach ($sortOptions as $field => $label):
                        // Determine next direction (toggle between asc/desc)
                        $nextDir = ($sort == $field && $dir == 'asc') ? 'desc' : 'asc';
                        $isActive = ($sort == $field);
                        $currentDir = $isActive ? $dir : '';
                    ?>
                        <a href="?<?= http_build_query(array_merge($currentParams, ['sort' => $field, 'dir' => $nextDir, 'page' => 1])) ?>"
                            style="padding: 5px 10px; background: <?= $isActive ? '#2c3e50' : '#888' ?>; color: white; border-radius: 4px; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                            <?= $label ?>
                            <?php if ($isActive): ?>
                                <?php if ($currentDir == 'asc'): ?>↑<?php else: ?>↓<?php endif; ?>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- PRODUCT GRID -->
        <?php if (count($products) > 0): ?>
            <div class="products-grid"
                style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">

                <?php foreach ($products as $p): ?>
                    <?php
                    $folder = categoryFolder($p->category_id);
                    $imgArray = explode(',', $p->product_image);
                    $firstImage = trim($imgArray[0]);
                    $imgPath = "/images/product/$folder/$firstImage";
                    ?>

                    <div class="product-card"
                        style="border:1px solid #eee; padding:20px; border-radius:8px; 
                                background:white; height:450px; display:flex; flex-direction:column;">

                        <!-- IMAGE -->
                        <img src="<?= $imgPath ?>"
                            style="width:100%; height:180px; object-fit:cover; border-radius:6px; margin-bottom:10px;">

                        <!-- NAME -->
                        <div style="height:50px; overflow:hidden; margin-bottom:10px;">
                            <h3 style="font-size:17px; margin:0;"><?= encode($p->product_name) ?></h3>
                        </div>

                        <!-- CATEGORY -->
                        <div style="margin-bottom: 10px;">
                            <span style="background: #eee; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                                <?= $categoryNames[$p->category_id] ?? 'Other' ?>
                            </span>
                        </div>

                        <!-- PRICE -->
                        <div class="product-price"
                            style="font-weight:bold; margin-bottom:10px; font-size:18px;">
                            RM <?= number_format($p->product_price, 2) ?>
                        </div>

                        <!-- STOCK -->
                        <div style="margin-bottom: 10px; font-size: 14px; color: #666;">
                            Stock: <?= number_format($p->product_stock) ?>
                        </div>

                        <!-- BUTTONS -->
                        <div style="margin-top:auto;">
                            <a href="product_detail.php?id=<?= $p->product_id ?>"
                                style="display:inline-block; margin-bottom:8px; padding:8px 15px; background:#888; color:white; border-radius:5px; text-decoration:none;">
                                View Details
                            </a>

                            <?php $is_logged_in = isset($_SESSION['user_id']); ?>

                            <a href="javascript:void(0)" onclick="addToCart('<?= $p->product_id ?>')"
                                class="add-to-cart"
                                style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none; cursor:pointer;">
                                Add to Cart
                            </a>
                        </div>

                    </div>

                <?php endforeach; ?>
            </div>

            <!-- PAGINATION -->
            <div style="margin-top: 30px; display: flex; justify-content: center; align-items: center; gap: 10px;">
                <?php if ($pager->page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($currentParams, ['page' => $pager->page - 1])) ?>"
                        style="padding: 8px 12px; background: #2c3e50; color: white; border-radius: 4px; text-decoration: none;">
                        &laquo; Previous
                    </a>
                <?php endif; ?>

                <span style="padding: 8px 12px;">
                    Page <?= $pager->page ?> of <?= $pager->page_count ?>
                </span>

                <?php if ($pager->page < $pager->page_count): ?>
                    <a href="?<?= http_build_query(array_merge($currentParams, ['page' => $pager->page + 1])) ?>"
                        style="padding: 8px 12px; background: #2c3e50; color: white; border-radius: 4px; text-decoration: none;">
                        Next &raquo;
                    </a>
                <?php endif; ?>
            </div>

            <!-- Page Numbers -->
            <div style="margin-top: 10px; display: flex; justify-content: center; flex-wrap: wrap; gap: 5px;">
                <?php for ($i = 1; $i <= $pager->page_count; $i++): ?>
                    <?php if ($i == $pager->page): ?>
                        <span style="padding: 5px 10px; background: #2c3e50; color: white; border-radius: 4px;">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($currentParams, ['page' => $i])) ?>"
                            style="padding: 5px 10px; background: #888; color: white; border-radius: 4px; text-decoration: none;">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                <p style="font-size: 18px; color: #666;">No products found matching your criteria.</p>
                <a href="shoppage.php"
                    style="display:inline-block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none;">
                    View All Products
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
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