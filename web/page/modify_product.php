<?php
require '../_base.php';
require '../lib/db.php';
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
    $updatedImages = array_filter($currentImages, function($img) use ($imgToDelete) {
        return $img !== $imgToDelete;
    });

    // Update DB
    $finalImageString = implode(", ", $updatedImages);

    $stm = $_db->prepare("UPDATE product SET product_image = ? WHERE product_id = ?");
    $stm->execute([$finalImageString, $id]);

    // Redirect back to the modify page
    header("Location: modify_product.php?id=$id&msg=img_deleted");
    exit;
}

$_title = 'Modify Product';
include '../_head.php';

// Category mapping
$categories = [
    'CA0001' => ['name' => 'Glasses', 'folder' => 'glasses'],
    'CA0002' => ['name' => 'Sunglasses', 'folder' => 'sunglasses'],
    'CA0003' => ['name' => 'Contact Lens', 'folder' => 'contactlens'],
    'CA0004' => ['name' => 'Kids', 'folder' => 'kids']
];

$folder = $categories[$p->category_id]['folder'];

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
        foreach ($_FILES['product_images']['name'] as $key => $name) {
            $tmp = $_FILES['product_images']['tmp_name'][$key];
            $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", $name);
            $target = "../images/product/" . $categories[$category_id]['folder'] . "/" . $safeName;

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
    header("Location: view_product.php?msg=updated");
    exit;
}
?>

<h1>Modify Product</h1>

<form method="post" enctype="multipart/form-data" style="max-width:600px;">

    <label>Product ID:</label>
    <input type="text" value="<?= $p->product_id ?>" readonly
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Name:</label>
    <input type="text" name="product_name" value="<?= encode($p->product_name) ?>" required
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Description:</label>
    <textarea name="product_description" rows="4" required
              style="width:100%; padding:8px; margin-bottom:15px;"><?= encode($p->product_description) ?></textarea>

    <label>Price (RM):</label>
    <input type="number" step="0.01" name="product_price" value="<?= $p->product_price ?>" required
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Stock:</label>
    <input type="number" name="product_stock" value="<?= $p->product_stock ?>" required
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Category:</label>
    <select name="category_id" required style="width:100%; padding:8px; margin-bottom:15px;">
        <?php foreach ($categories as $id => $c): ?>
            <option value="<?= $id ?>" <?= $id == $p->category_id ? 'selected' : '' ?>>
                <?= $c['name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Existing Images:</label><br>

    <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:20px;">

    <?php foreach (explode(",", $p->product_image) as $img): ?>
        <?php $img = trim($img); ?>
        <div style="position:relative; width:180px; height:180px;">

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

            <img src="/images/product/<?= $folder ?>/<?= $img ?>" 
                style="width:180px; height:180px; object-fit:cover; border-radius:8px; border:1px solid #ccc;">
        </div>
    <?php endforeach; ?>

    </div>

    <br><br>

    <label>Upload Additional Images:</label>
    <input type="file" name="product_images[]" multiple accept="image/*"
           style="width:100%; padding:8px; margin-bottom:15px;">

    <label>Status:</label><br>
    <input type="checkbox" name="product_status" <?= $p->product_status ? 'checked' : '' ?>>
    <span>Active</span>

    <br><br>

    <button type="submit"
            style="padding:12px 25px; background:#2c3e50; color:white; border:none; 
                   border-radius:5px; cursor:pointer;">
        Save Changes
    </button>

    <a href="view_product.php"
       style="padding:12px 20px; background:#888; color:white; 
              border-radius:5px; text-decoration:none; margin-left:10px;">
        Back to Product List
    </a>

</form>

<?php include '../_foot.php'; ?>
