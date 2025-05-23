<?php
// add-product.php (Form on grey background, no sidebar)

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    $_SESSION['message'] = 'Please login to add products.';
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['user']['user_type_name']) || $_SESSION['user']['user_type_name'] !== 'Seller') {
    $_SESSION['message'] = 'Access denied. You must be a Seller to add products.';
    header('Location: my-account.php');
    exit();
}

use Aries\MiniFrameworkStore\Models\Category;
use Aries\MiniFrameworkStore\Models\Product;
use Carbon\Carbon;

$categoryModel = new Category();
$productModel = new Product();
$categories = $categoryModel->getAll();

$message = '';
$messageType = '';

// Initialize form fields for sticky form
$productName = '';
$productDescription = '';
$productPrice = '';
$categoryId = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['name'] ?? '';
    $productDescription = $_POST['description'] ?? '';
    $productPrice = $_POST['price'] ?? 0;
    $categoryId = $_POST['category_id'] ?? null;
    $sellerId = $_SESSION['user']['id'];

    $imagePathForDb = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imagePathForDb = $targetFilePath;
            $message = 'Image uploaded successfully.';
            $messageType = 'success';
        } else {
            $message = 'Error uploading image.';
            $messageType = 'danger';
        }
    } else if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $phpFileUploadErrors = array(
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
        );
        $message = 'Image upload error: ' . ($phpFileUploadErrors[$_FILES['image']['error']] ?? 'Unknown error.');
        $messageType = 'danger';
    } else {
        $message = 'No image selected for upload.';
        $messageType = 'warning';
    }

    if ($messageType !== 'danger' || !isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $slug = generateSlug($productName);

        $now = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

        $productData = [
            'name' => $productName,
            'description' => $productDescription,
            'price' => $productPrice,
            'category_id' => $categoryId,
            'seller_id' => $sellerId,
            'image_path' => $imagePathForDb,
            'slug' => $slug,
            'created_at' => $now,
            'updated_at' => $now
        ];

        if ($productModel->insert($productData)) {
            $message = 'Product added successfully!';
            $messageType = 'success';
            $productName = $productDescription = $productPrice = $categoryId = '';
        } else {
            $message = 'Failed to add product to database.';
            $messageType = 'danger';
            if ($imagePathForDb && file_exists($imagePathForDb)) {
                unlink($imagePathForDb);
            }
        }
    }
}
template('templates/header.php');
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-5 shadow-sm rounded-0 border-0 main-content-card"> <h2 class="text-center mb-5 section-title">Add New Product</h2>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="add-product.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="name" class="form-label form-label-custom">Product Name</label>
                        <input type="text" class="form-control form-control-custom" id="name" name="name" value="<?php echo htmlspecialchars($productName); ?>" required>
                        <div class="invalid-feedback">
                            Product name is required.
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="description" class="form-label form-label-custom">Description</label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="5" required><?php echo htmlspecialchars($productDescription); ?></textarea>
                        <div class="invalid-feedback">
                            Product description is required.
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="price" class="form-label form-label-custom">Price</label>
                        <div class="input-group">
                            <span class="input-group-text currency-symbol">₱</span>
                            <input type="number" class="form-control form-control-custom" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($productPrice); ?>" required>
                            <div class="invalid-feedback">
                                Please enter a valid price.
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="category" class="form-label form-label-custom">Category</label>
                        <select class="form-select form-control-custom" id="category" name="category_id" required>
                            <option value="">Select a Category</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo ($categoryId == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a category.
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="image" class="form-label form-label-custom">Product Image</label>
                        <input type="file" class="form-control form-control-custom" id="image" name="image" accept="image/*" required>
                        <div class="invalid-feedback">
                            Product image is required.
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-dark w-100 btn-submit-custom">Add Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php template('templates/footer.php'); ?>

<script>
(function () {
  'use strict'

  var forms = document.querySelectorAll('.needs-validation')

  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
})()
</script>