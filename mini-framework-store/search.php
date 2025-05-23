<?php
// search.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use Aries\MiniFrameworkStore\Models\Product;
use Aries\MiniFrameworkStore\Models\Category; // <--- Corrected to use your existing 'Category' model

$searchQuery = $_GET['q'] ?? ''; // Get the search query from the URL parameter

$products = [];
if (!empty($searchQuery)) {
    $productModel = new Product();
    $products = $productModel->searchProducts($searchQuery); // Call the new searchProducts method
}

// Fetch categories for the header navigation
$categoryModel = new Category();
$categories = $categoryModel->getAll();

// Initialize NumberFormatter for currency display
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
}

template('templates/header.php', ['categories' => $categories]);
?>

<div class="container my-5">
    <h2 class="mb-4 text-center">Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
    <hr class="mb-5">

    <?php if (empty($searchQuery)): ?>
        <div class="alert alert-info text-center" role="alert">
            Please enter a search query to find products.
        </div>
    <?php elseif (empty($products)): ?>
        <div class="alert alert-warning text-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> No products found matching "<?php echo htmlspecialchars($searchQuery); ?>".
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm product-card">
                        <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'assets/img/placeholder.jpg'); ?>" class="card-img-top product-card-img" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>">
                        <div class="card-body d-flex flex-column product-card-body">
                            <h5 class="card-title text-truncate mb-1 product-card-title"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h5>
                            <p class="card-text fs-5 fw-bold mb-2 product-card-price">
                                <?php
                                if ($pesoFormatter) {
                                    echo htmlspecialchars($pesoFormatter->formatCurrency($product['price'] ?? 0, 'PHP'));
                                } else {
                                    echo htmlspecialchars(formatCurrencyFallback($product['price'] ?? 0));
                                }
                                ?>
                            </p>
                            <p class="card-text flex-grow-1 text-muted small"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . (mb_strlen($product['description'] ?? '') > 100 ? '...' : ''); ?></p>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center bg-white border-0 pt-0 pb-3 px-3">
                            <a href="product.php?slug=<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" class="btn btn-outline-primary btn-sm rounded-pill">View Product</a>
                            <?php if (isLoggedIn()): ?>
                                <form action="cart-process.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-warning btn-sm rounded-pill"><i class="fas fa-cart-plus me-1"></i> Add to Cart</button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-warning btn-sm rounded-pill"><i class="fas fa-sign-in-alt me-1"></i> Login to Buy</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
template('templates/footer.php');
?>