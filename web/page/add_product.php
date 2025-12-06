<?php
require '../_base.php';
require '../lib/db.php';
auth('Admin');

$_title = 'Add Product';
include '../_head.php';

// Auto-generate product ID
function generateProductID($db) {
    $last = $db->query("SELECT product_id FROM product ORDER BY product_id DESC LIMIT 1")->fetchColumn();
    if (!$last) return "PR0001";

    $num = intval(substr($last, 2)) + 1;
    return "PR" . str_pad($num, 4, "0", STR_PAD_LEFT);
}


$product_id = generateProductID($_db);

// CATEGORY MAPPING (folder + name)
$categories = [
    'CA0001' => ['name' => 'Glasses', 'folder' => 'glasses'],
    'CA0002' => ['name' => 'Sunglasses', 'folder' => 'sunglasses'],
    'CA0003' => ['name' => 'Contact Lens', 'folder' => 'contactlens'],
    'CA0004' => ['name' => 'Kids', 'folder' => 'kids'],
];

// FORM SUBMIT
if (is_post()) {

    $product_name = post('product_name');
    $product_description = post('product_description');
    $product_price = post('product_price');
    $product_stock = post('product_stock');
    $category_id = post('category_id');
    $product_status = post('product_status') ? 1 : 0;

    $folder = $categories[$category_id]['folder'];

    // MULTIPLE IMAGE UPLOAD HANDLER
    $uploadedImages = [];

    if (!empty($_FILES['product_images']['name'][0])) {
        foreach ($_FILES['product_images']['name'] as $key => $name) {

            $tmp = $_FILES['product_images']['tmp_name'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", $name);

            $targetDir = "../images/product/$folder/";
            $targetFile = $targetDir . $safeName;

            if (move_uploaded_file($tmp, $targetFile)) {
                $uploadedImages[] = $safeName;
            }
        }
    }

    // CONVERT TO COMMA STRING
    $product_image = implode(", ", $uploadedImages);

    // INSERT INTO DB
    $stm = $_db->prepare("
        INSERT INTO product (product_id, product_name, product_description, product_price, product_stock, product_image, category_id, product_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stm->execute([
        $product_id,
        $product_name,
        $product_description,
        $product_price,
        $product_stock,
        $product_image,
        $category_id,
        $product_status
    ]);

    // Redirect after successful insert
    header("Location: view_product.php?msg=added");
    exit;
}
?>

<h1 style="margin-bottom:20px;">Add New Product</h1>

<form method="post" enctype="multipart/form-data" style="max-width:600px;">

    <label>Product ID:</label>
    <input type="text" name="product_id" value="<?= $product_id ?>" readonly
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Product Name:</label>
    <input type="text" name="product_name" required
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Description:</label>
    <textarea name="product_description" rows="4" required
              style="width:100%; padding:8px; margin-bottom:15px;"></textarea>

    <label>Price (RM):</label>
    <input type="number" step="0.01" name="product_price" required
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Stock Quantity:</label>
    <input type="number" name="product_stock" required
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Category:</label>
    <select name="category_id" required style="width:100%; padding:8px; margin-bottom:15px;">
        <?php foreach ($categories as $id => $cat): ?>
            <option value="<?= $id ?>"><?= $cat['name'] ?></option>
        <?php endforeach; ?>
    </select>

    <label>Upload Product Images (Multiple):</label>
    <input type="file" name="product_images[]" multiple accept="image/*"
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Status:</label><br>
    <label style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
        <input type="checkbox" name="product_status" checked>
        <span>Active</span>
    </label>

    <button type="submit" 
            style="padding:12px 25px; background:#2c3e50; color:white; border:none; border-radius:5px; cursor:pointer;">
        Add Product
    </button>

</form>

<?php include '../_foot.php'; ?>