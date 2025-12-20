<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
require '../../lib/category.php';
auth('Admin');

$_title = 'Add Product';

// Auto-generate product ID
function generateProductID($db)
{
    $last = $db->query("SELECT product_id FROM product ORDER BY product_id DESC LIMIT 1")->fetchColumn();
    if (!$last) return "PR0001";

    $num = intval(substr($last, 2)) + 1;
    return "PR" . str_pad($num, 4, "0", STR_PAD_LEFT);
}


$product_id = generateProductID($_db);

// FORM SUBMIT
if (is_post()) {

    $product_name = post('product_name');
    $product_description = post('product_description');
    $product_price = post('product_price');
    $product_stock = post('product_stock');
    $category_id = post('category_id');
    $product_status = post('product_status') ? 1 : 0;

    $folder = $categoryFolders[$category_id] ?? 'others';

    // MULTIPLE IMAGE UPLOAD HANDLER
    $uploadedImages = [];

    if (!empty($_FILES['product_images']['name'][0])) {
        foreach ($_FILES['product_images']['name'] as $key => $name) {

            $tmp = $_FILES['product_images']['tmp_name'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", $name);

            $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/images/product/$folder/";
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

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Add New Product</h1>
    </div>

    <div class="form-container ">

        <form method="post" enctype="multipart/form-data" class="add-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Product ID:</label>
                    <input type="text" name="product_id" value="<?= $product_id ?>" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Price (RM):</label>
                    <input type="number" step="0.01" name="product_price" class="form-control" required>
                </div>

            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="product_description" rows="4" class="form-control" required></textarea>
                </div>

                <div class="form-group">
                    <label>Stock Quantity:</label>
                    <input type="number" name="product_stock" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Category:</label>
                    <select name="category_id" required class="dob-select">
                        <?php foreach ($categories as $id => $name): ?>
                            <option value="<?= $id ?>"><?= encode($name) ?></option>
                        <?php endforeach; ?>

                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Upload Product Images (Multiple):</label>
                    <input type="file" name="product_images[]" multiple accept="image/*"
                        style="width:100%; padding:8px; margin-bottom:15px;">
                </div>

                <div class="form-group">
                    <label>Status:</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="checkbox" name="product_status" checked>
                            <label style="padding-top:5px; text-transform:none;">Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="form-row button-row" style="margin-top: 100px;">
                <button type="button" class="btn btn-white" onclick="location.href='view_product.php'">Back</button>
                <button type="submit" class="btn btn-add">Add</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>
        </form>
    </div>
</div>
</body>

</html>