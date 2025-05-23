<?php
// category.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use Aries\MiniFrameworkStore\Models\Product;
use Aries\MiniFrameworkStore\Models\Category;

$productModel = new Product();
$categoryModel = new Category();

$categoryId = null;
$categoryName = "All Products";
$products = []; // Initialize products array

// --- DEBUGGING START ---
error_log("category.php accessed.");
error_log("GET parameters: " . print_r($_GET, true));
// --- DEBUGGING END ---

// Check if a category slug or ID is provided in the URL
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $categorySlug = htmlspecialchars($_GET['slug']);
    error_log("Attempting to get category by slug: " . $categorySlug); // DEBUG
    $category = $categoryModel->getBySlug($categorySlug);
    if ($category) {
        $categoryId = $category['id'];
        $categoryName = htmlspecialchars($category['name']);
        error_log("Category found by slug: " . $categoryName . " (ID: " . $categoryId . ")"); // DEBUG
    } else {
        error_log("Category NOT found for slug: " . $categorySlug); // DEBUG
        $products = []; // No specific category found for this slug
        $categoryName = "Category Not Found";
    }
} else if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $categoryId = (int)$_GET['id'];
    error_log("Attempting to get category by ID: " . $categoryId); // DEBUG
    $category = $categoryModel->getById($categoryId);
    if ($category) {
        $categoryName = htmlspecialchars($category['name']);
        error_log("Category found by ID: " . $categoryName . " (ID: " . $categoryId . ")"); // DEBUG
    } else {
        error_log("Category NOT found for ID: " . $categoryId); // DEBUG
        $products = [];
        $categoryName = "Category Not Found";
    }
}

// Fetch products based on category ID, or all products if no valid category selected
if ($categoryId) {
    error_log("Fetching products for category ID: " . $categoryId); // DEBUG
    $products = $productModel->getByCategoryId($categoryId);
    error_log("Products fetched for category: " . count($products)); // DEBUG
} else {
    // If no valid category or if showing 'All Products'
    error_log("No specific category ID. Fetching ALL products."); // DEBUG
    $products = $productModel->getAll();
    error_log("Total products fetched (all): " . count($products)); // DEBUG
}

// Initialize NumberFormatter for currency display (rest of your code is here)
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
}

template('templates/header.php');

?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="text-center my-5 display-4 fw-bold"><?php echo $categoryName; ?> Products</h1>
            <p class="text-center lead">Browse products in the <?php echo $categoryName; ?> category.</p>
        </div>
    </div>

    <div class="row my-5">
        <div class="col-md-12">
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0 rounded">
                                <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'assets/img/placeholder.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-truncate mb-1"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h5>
                                    <p class="card-text text-success fs-5 fw-bold mb-2">
                                        <?php
                                        if ($pesoFormatter) {
                                            echo htmlspecialchars($pesoFormatter->formatCurrency($product['price'] ?? 0, 'PHP'));
                                        } else {
                                            echo htmlspecialchars(formatCurrencyFallback($product['price'] ?? 0));
                                        }
                                        ?>
                                    </p>
                                    <p class="card-text flex-grow-1"><small class="text-muted"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . (mb_strlen($product['description'] ?? '') > 100 ? '...' : ''); ?></small></p>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <a href="product.php?slug=<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" class="btn btn-primary btn-sm">View Product</a>
                                    <?php if (isLoggedIn()): ?>
                                        <form action="cart-process.php" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-warning btn-sm">Add to Cart</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-warning btn-sm">Login to Buy</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center" role="alert">
                            No products found in this category.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
template('templates/footer.php');
?>