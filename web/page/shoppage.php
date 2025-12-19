<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/category.php';
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

<!-- Mobile Filter Toggle Button -->
<button id="filterToggleMobile" style="display: none; width: 100%; padding: 10px; margin-bottom: 20px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
    <i class="fas fa-filter"></i> Show Filters
</button>

<div style="display: flex; gap: 30px; position: relative;">

    <!-- LEFT: FILTERS SIDEBAR -->
    <div id="filterSidebar" style="width: 250px; flex-shrink: 0;">
        <form method="get" id="filterForm">
            <!-- Hidden fields to maintain pagination and sorting -->
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="sort" value="<?= $sort ?>">
            <input type="hidden" name="dir" value="<?= $dir ?>">

            <!-- Mobile Filter Header -->
            <div id="mobileFilterHeader" style="display: none; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #2c3e50;">Filters</h3>
                <button type="button" id="closeFilters" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
            </div>

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
                            class="auto-submit">
                        <span>All Products</span>
                    </label>
                    <?php foreach ($categories as $id => $name): ?>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="radio"
                                name="cat"
                                value="<?= $id ?>"
                                <?= ($selectedCat === $id) ? 'checked' : '' ?>
                                class="auto-submit">
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
                                class="auto-submit">
                            <span>All Prices</span>
                        </label>
                        <?php foreach ($priceRanges as $range => $label): ?>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="radio"
                                    name="price_range"
                                    value="<?= $range ?>"
                                    <?= ($priceRange === $range) ? 'checked' : '' ?>
                                    class="auto-submit">
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
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="font-size: 14px;">RM</span>
                            <input type="number"
                                name="min_price"
                                value="<?= $minPrice ?>"
                                min="0"
                                max="<?= $actualMaxPrice ?>"
                                step="10"
                                style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <span>to</span>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="font-size: 14px;">RM</span>
                            <input type="number"
                                name="max_price"
                                value="<?= $maxPrice ?>"
                                min="0"
                                max="<?= $actualMaxPrice ?>"
                                step="10"
                                style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </div>
                    <button type="submit" name="apply_custom_price" value="1"
                        style="padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Apply Custom Price
                    </button>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div style="margin-top: 30px;">
                <button type="submit"
                    style="width: 100%; padding: 10px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    Apply Filters
                </button>

                <!-- CLEAR FILTERS BUTTON -->
                <?php if ($search || $selectedCat || $priceRange || $minPrice > 0 || $maxPrice < 1000): ?>
                    <a href="shoppage.php?sort=<?= $sort ?>&dir=<?= $dir ?>"
                        style="display: block; padding: 10px; background: #e74c3c; color: white; text-align: center; border-radius: 4px; text-decoration: none; margin-top: 10px; font-weight: bold;">
                        Clear All Filters
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- RIGHT: PRODUCT GRID -->
    <div style="flex: 1;">
        <!-- Sorting Header with Search Info -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <div>
                <p style="margin: 0; font-weight: 500;">
                    <?php if ($pager->item_count === 0): ?>
                        No products found
                    <?php else: ?>
                        <span>Showing <?= $pager->count ?> of <?= $pager->item_count ?> product(s)</span>
                        <?php if ($search): ?>
                            <span style="margin-left: 10px; background: #3498db; color: white; padding: 4px 10px; border-radius: 12px; font-size: 14px;">
                                for "<?= htmlspecialchars($search) ?>"
                                <button type="button" style="background: none; border: none; color: white; cursor: pointer; margin-left: 5px;"
                                    onclick="location.href='?<?= http_build_query(array_merge($currentParams, ['search' => '', 'page' => 1])) ?>'">
                                    ×
                                </button>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-weight: 500;">Sort by:</span>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
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
                            style="padding: 6px 12px; background: <?= $isActive ? '#2c3e50' : '#7f8c8d' ?>; color: white; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-size: 14px;">
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
                    $folder = $categoryFolders[$p->category_id] ?? 'others';
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
                        <div style="height:51px; overflow:hidden; margin-bottom:10px;">
                            <h3 style="font-size:17px; margin:0;"><?= encode($p->product_name) ?></h3>
                        </div>

                        <!-- PRICE -->
                        <div class="product-price"
                            style="font-weight:bold; margin-bottom:10px; font-size:18px;">
                            RM <?= number_format($p->product_price, 2) ?>
                        </div>

                        <!-- STOCK -->
                        <div style="margin-bottom: 10px; font-size: 14px;">
                            <?php if ($p->product_stock <= 10): ?>
                                <span style="color: #c0392b; font-weight:bold;">
                                    Stock: <?= number_format($p->product_stock) ?> (Selling Fast!)
                                </span>
                            <?php else: ?>
                                <span style="color: #27ae60;">
                                    Stock: <?= number_format($p->product_stock) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- BUTTONS -->
                        <div style="margin-top:auto;">
                            <!-- Top row: View Details (left) and Wishlist (right) -->
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <a href="product_detail.php?id=<?= $p->product_id ?>"
                                    style="padding:8px 15px; background:#7f8c8d; color:white; border-radius:5px; text-decoration:none; flex:1; margin-right:10px; text-align:center;">
                                    View Details
                                </a>

                                <button class="wishlist-btn"
                                    data-product-id="<?= $p->product_id ?>"
                                    style="background:none; border:1px solid #ddd; cursor:pointer; font-size:20px; color:#333; padding:5px 10px; border-radius:5px; transition: all 0.3s ease;"
                                    title="Add to Wishlist">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>

                            <!-- Bottom row: Add to Cart (full width) -->
                            <a href="#" class="add-to-cart" data-product-id="<?= $p->product_id ?>"
                                style="display:block; padding:10px 20px; background:#2c3e50; color:white; border-radius:5px; text-decoration:none; cursor:pointer; text-align:center; transition: all 0.3s ease;">
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
                            style="padding: 5px 10px; background: #7f8c8d; color: white; border-radius: 4px; text-decoration: none;">
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

<!-- Mobile Overlay -->
<div id="mobileOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>

<script>
$(document).ready(function() {
    const filterToggleMobile = $('#filterToggleMobile');
    const filterSidebar = $('#filterSidebar');
    const mobileFilterHeader = $('#mobileFilterHeader');
    const closeFilters = $('#closeFilters');
    const mobileOverlay = $('#mobileOverlay');
    
    // Check screen size on load and resize
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            // Mobile view
            filterToggleMobile.show();
            filterSidebar.css({
                'position': 'fixed',
                'top': '0',
                'left': '-280px',
                'width': '250px',
                'height': '100vh',
                'background': 'white',
                'z-index': '1000',
                'padding': '20px',
                'overflow-y': 'auto',
                'box-shadow': '2px 0 10px rgba(0,0,0,0.1)',
                'transition': 'left 0.3s ease'
            });
            mobileFilterHeader.show();
            
            // Update product grid for mobile
            $('.products-grid').css({
                'grid-template-columns': 'repeat(auto-fill, minmax(160px, 1fr))',
                'gap': '15px'
            });
            $('.product-card').css({
                'height': '400px',
                'padding': '15px'
            });
        } else {
            // Desktop view
            filterToggleMobile.hide();
            filterSidebar.css({
                'position': 'static',
                'left': '0',
                'width': '250px',
                'height': 'auto',
                'background': 'transparent',
                'z-index': 'auto',
                'padding': '0',
                'box-shadow': 'none'
            });
            mobileFilterHeader.hide();
            mobileOverlay.hide();
            
            // Reset product grid for desktop
            $('.products-grid').css({
                'grid-template-columns': 'repeat(auto-fill, minmax(250px, 1fr))',
                'gap': '20px'
            });
            $('.product-card').css({
                'height': '450px',
                'padding': '20px'
            });
        }
        
        // Adjust grid for smaller screens
        if (window.innerWidth <= 480) {
            $('.products-grid').css({
                'grid-template-columns': 'repeat(2, 1fr)',
                'gap': '10px'
            });
            $('.product-card').css({
                'height': '380px'
            });
        }
    }
    
    // Initial check
    checkScreenSize();
    
    // Check on resize
    $(window).resize(checkScreenSize);
    
    // Toggle filter sidebar on mobile
    filterToggleMobile.click(function() {
        filterSidebar.css('left', '0');
        mobileOverlay.show();
        $('body').css('overflow', 'hidden');
    });
    
    // Close filter sidebar
    function closeFilterSidebar() {
        filterSidebar.css('left', '-280px');
        mobileOverlay.hide();
        $('body').css('overflow', 'auto');
    }
    
    mobileOverlay.click(closeFilterSidebar);
    closeFilters.click(closeFilterSidebar);
    
    // Auto-submit for radio buttons
    $('.auto-submit').change(function() {
        $(this).closest('form').submit();
    });
    
    // Prevent form submission on Enter in search field
    $('#filterForm input[name="search"]').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $(this).closest('form').submit();
        }
    });
});
</script>

<script src="/js/notifications.js"></script>
<script src="/js/wishlist.js"></script>
<?php include '../_foot.php'; ?>