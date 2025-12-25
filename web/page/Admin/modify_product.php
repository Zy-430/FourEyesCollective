<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
require '../../lib/category.php';
auth('Admin');

$id = get('id');

// Load product
$stm = $_db->prepare("SELECT * FROM product WHERE product_id = ?");
$stm->execute([$id]);
$p = $stm->fetch();

if (!$p) {
    die("Product not found.");
}

// DELETE EXISTING IMAGE
if (isset($_GET['delete_img'])) {

    $imgToDelete = trim($_GET['delete_img']);
    $currentImages = array_map('trim', explode(",", $p->product_image));

    // Remove from array
    $updatedImages = array_filter($currentImages, function ($img) use ($imgToDelete) {
        return $img !== $imgToDelete;
    });

    $folder = $categoryFolders[$p->category_id] ?? 'others';
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/images/product/$folder/$imgToDelete";

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Update DB
    $finalImageString = implode(", ", $updatedImages);

    $stm = $_db->prepare("UPDATE product SET product_image = ? WHERE product_id = ?");
    $stm->execute([$finalImageString, $id]);

    $stm = $_db->prepare("SELECT * FROM product WHERE product_id = ?");
    $stm->execute([$id]);
    $p = $stm->fetch();
    $folder = $categoryFolders[$p->category_id] ?? 'others';

    // Redirect back to the modify page
    header("Location: modify_product.php?id=$id&msg=img_deleted");
    exit;
}

$_title = "Modify Products | Four Eyes Collective";

$folder = $categoryFolders[$p->category_id] ?? 'others';

// If form submitted
if (is_post()) {

    $product_name = post('product_name');
    $product_description = post('product_description');
    $product_price = post('product_price');
    $product_stock = post('product_stock');
    $category_id = post('category_id');
    $product_status = post('product_status') ? 1 : 0;

    // Handle new images (append)
    $newImages = [];

    if (!empty($_FILES['product_images']['name'][0])) {
        $folder = $categoryFolders[$category_id] ?? 'others';
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/images/product/$folder/";

        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        foreach ($_FILES['product_images']['name'] as $key => $name) {
            $tmp = $_FILES['product_images']['tmp_name'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", $name);
            $folder = $categoryFolders[$category_id] ?? 'others';
            $target = $upload_dir . $safeName;

            if (move_uploaded_file($tmp, $target)) {
                $newImages[] = $safeName;
            }
        }
    }

    // Merge new + old images
    $currentImages = explode(",", $p->product_image);
    $finalImages = array_merge(array_map('trim', $currentImages), $newImages);
    $imageString = implode(", ", $finalImages);

    // Update database
    $update = $_db->prepare("
        UPDATE product SET
            product_name = ?, 
            product_description = ?, 
            product_price = ?, 
            product_stock = ?, 
            product_image = ?, 
            category_id = ?, 
            product_status = ?
        WHERE product_id = ?
    ");

    $update->execute([
        $product_name,
        $product_description,
        $product_price,
        $product_stock,
        $imageString,
        $category_id,
        $product_status,
        $id
    ]);

    // Redirect after successful update
    header("Location: view_product.php?msg=updated&product_id=$id");
    exit;
}
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Modify Product</h1>
    </div>

    <div class="form-container ">

        <form method="post" enctype="multipart/form-data" class="add-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Product ID:</label>
                    <input type="text" value="<?= $p->product_id ?>" class="form-control" disabled>
                </div>

                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" name="product_name" value="<?= encode($p->product_name) ?>" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Price (RM):</label>
                    <input type="number" step="0.01" name="product_price" value="<?= $p->product_price ?>" class="form-control" required>
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="product_description" rows="4" class="form-control" required><?= encode($p->product_description) ?></textarea>
                </div>


                <div class="form-group">
                    <label>Stock Quantity:</label>
                    <input type="number" name="product_stock" value="<?= $p->product_stock ?>" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Category:</label>
                    <select name="category_id" required class="dob-select">
                        <?php foreach ($categories as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $p->category_id ? 'selected' : '' ?>>
                                <?= encode($name) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Existing Images:</label>
                    <div style="display:flex; gap:15px; flex-wrap:wrap; ">

                        <?php foreach (explode(",", $p->product_image) as $img): ?>
                            <?php $img = trim($img); ?>
                            <div style="position:relative; width:120px; height:120px;">

                                <!-- DELETE BUTTON -->
                                <a href="modify_product.php?id=<?= $p->product_id ?>&delete_img=<?= $img ?>"
                                    onclick="return confirm('Remove this image?');"
                                    style="
                    position:absolute;
                    top:-8px;
                    right:-8px;
                    background:#e74c3c;
                    color:white;
                    width:25px;
                    height:25px;
                    text-align:center;
                    line-height:25px;
                    border-radius:50%;
                    text-decoration:none;
                    font-weight:bold;
                    cursor:pointer;">
                                    ×
                                </a>

                                <?php
                                    $imgPath = $img ? "/images/product/$folder/$img" : "/images/product/no-image.png";
                                ?>
                                <img src="<?= $imgPath ?>"
                                    style="width:120px; height:120px; object-fit:cover; border-radius:8px; border:1px solid #ccc;">
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Additional Images:</label>
                    <input type="file" name="product_images[]" multiple accept="image/*"
                        style="width:100%; padding:8px; margin-bottom:15px;">
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="checkbox" name="product_status" <?= $p->product_status ? 'checked' : '' ?>>
                            <label style="padding-top:5px; text-transform:none;">Active</label>
                        </div>
                    </div>
                </div>


            </div>
            <!-- Submit Buttons -->
            <div class="form-row button-row" style="margin-top: 40px;">
                <button type="button" class="btn btn-white" onclick="location.href='view_product.php'">Back</button>
                <button type="submit" class="btn btn-add">Update</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>
        </form>

    </div>
</div>
</body>

</html