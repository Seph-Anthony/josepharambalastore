<?php
// product.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use Aries\MiniFrameworkStore\Models\Product;

// Initialize $pesoFormatter
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
}

// Check if slug is received
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: index.php');
    exit();
}

$productSlug = $_GET['slug'];
$productModel = new Product();

$product = $productModel->getBySlug($productSlug);

// Redirect to index.php if product is not found
if (!$product) {
    header('Location: index.php');
    exit();
}

// Ensure essential product data exists with fallbacks for display
$product['image_path'] = $product['image_path'] ?? 'assets/img/placeholder.jpg';
$product['name'] = $product['name'] ?? 'Unknown Product';
$product['price'] = $product['price'] ?? 0;
$product['description'] = $product['description'] ?? 'No description available.';
// Placeholder comments for optional fields (removed from display HTML)
// $product['short_description'] = $product['short_description'] ?? '';
// $product['best_seller'] = $product['best_seller'] ?? false;
// $product['in_stock'] = $product['in_stock'] ?? true;
// $product['stock_quantity'] = $product['stock_quantity'] ?? 10;
// $product['rating'] = $product['rating'] ?? 0;
// $product['review_count'] = $product['review_count'] ?? 0;


template('templates/header.php');
?>

<div class="container my-5 product-detail-container">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-light p-2 rounded">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <?php // If your product data includes category_name and category_id, uncomment this:
            /*
            if (isset($product['category_name']) && isset($product['category_id'])): ?>
                <li class="breadcrumb-item"><a href="category.php?id=<?php echo htmlspecialchars($product['category_id']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <?php endif;
            */ ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="product-image-wrapper card shadow-sm">
                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" class="img-fluid rounded product-main-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
        </div>

        <div class="col-lg-6">
            <div class="product-details-wrapper">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <?php // If you add 'best_seller' field to your DB, uncomment this:
                /*
                if ($product['best_seller']): ?>
                    <span class="badge bg-warning text-dark mb-2 best-seller-badge"><i class="fas fa-star me-1"></i> Best Seller</span>
                <?php endif;
                */ ?>

                <?php // If you add 'rating' and 'review_count' fields to your DB, uncomment this:
                /*
                <div class="d-flex align-items-center mb-3">
                    <div class="product-rating me-2">
                        <?php
                        $rating = (float)($product['rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= floor($rating)): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php elseif ($i - 0.5 == $rating): ?>
                                <i class="fas fa-star-half-alt text-warning"></i>
                            <?php else: ?>
                                <i class="far fa-star text-warning"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="text-muted product-review-count">(<?php echo htmlspecialchars($product['review_count'] ?? 0); ?> reviews)</span>
                </div>
                */ ?>

                <p class="product-price mb-3">
                    <?php
                    if ($pesoFormatter) {
                        echo htmlspecialchars($pesoFormatter->formatCurrency($product['price'], 'PHP'));
                    } else {
                        echo htmlspecialchars(formatCurrencyFallback($product['price']));
                    }
                    ?>
                </p>

                <?php // If you add 'short_description' field to your DB, uncomment this:
                /*
                <p class="product-short-description mb-4 text-muted"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></p>
                */ ?>

                <form action="cart-process.php" method="POST" class="add-to-cart-form mb-4">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id'] ?? ''); ?>">

                    <?php if (isLoggedIn()): ?>
                        <div class="d-flex align-items-center mb-3">
                            <label for="quantity" class="form-label me-3 mb-0 fw-bold">Quantity:</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" class="form-control quantity-input" style="width: 80px;">
                        </div>

                        <?php // If not using in_stock/stock_quantity fields, use a simpler Add to Cart button: ?>
                        <button type="submit" name="action" value="add" class="btn btn-primary btn-lg add-to-cart-btn w-100">
                            <i class="fas fa-cart-plus me-2"></i> Add to Cart
                        </button>

                    <?php else: ?>
                        <a href="login.php" class="btn btn-warning btn-lg w-100 login-to-buy-btn">Login to Buy</a>
                    <?php endif; ?>
                </form>

                <hr class="my-4">

                <div class="product-full-description">
                    <h4 class="mb-3">Product Details</h4>
                    <p class="text-justify"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php template('templates/footer.php'); ?>