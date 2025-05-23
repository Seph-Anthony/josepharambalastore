<?php
// index.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use Aries\MiniFrameworkStore\Models\Product;
use Aries\MiniFrameworkStore\Models\Category;

// Initialize NumberFormatter for currency display
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
}

$productModel = new Product();
$products = $productModel->getAll();

// Fetch categories using your existing 'Category' model
$categoryModel = new Category();
$categories = $categoryModel->getAll();

template('templates/header.php', ['categories' => $categories]);
?>

<div class="container-fluid p-0 mb-5">
    <div class="hero-section text-white text-center d-flex align-items-center justify-content-center" style="background: url('assets/img/hero-bg.jpg') no-repeat center center / cover;">
        <div class="overlay"></div> <div class="container z-index-1 position-relative py-5">
            <h1 class="display-3 fw-bold mb-3 animate__animated animate__fadeInDown">Your Style, Delivered.</h1>
            <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">Explore the latest trends in fashion, electronics, home goods & more.</p>
            <a href="#products-section" class="btn btn-light btn-lg rounded-pill shadow-lg animate__animated animate__zoomIn animate__delay-2s">Shop Now <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
    </div>
</div>

<div class="container my-5">
    <?php
    // MODIFIED SECTION START
    // Display potential session messages
    if (isset($_SESSION['message']) && is_array($_SESSION['message'])) {
        $alertType = $_SESSION['message']['type'] ?? 'info'; // Default to 'info' if 'type' is missing
        $alertText = $_SESSION['message']['text'] ?? 'An unknown message occurred.'; // Default text

        // Map your custom types to Bootstrap alert types
        if ($alertType === 'error') {
            $alertType = 'danger';
        } else if ($alertType === 'success') {
            $alertType = 'success';
        } else {
            $alertType = 'info'; // Fallback for 'info' or other types
        }

        echo '<div class="alert alert-' . htmlspecialchars($alertType) . ' text-center mb-4" role="alert">' . htmlspecialchars($alertText) . '</div>';
        unset($_SESSION['message']); // Clear the message after displaying
    }
    // MODIFIED SECTION END
    ?>

    <h2 class="mb-4 text-center fw-bold text-dark section-title" id="products-section">Featured Products</h2>
    <hr class="mb-5 section-divider">

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 product-grid">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm product-card border-0">
                        <a href="product.php?slug=<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" class="text-decoration-none d-block">
                            <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'assets/img/placeholder.jpg'); ?>" class="card-img-top product-card-img" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>">
                        </a>
                        <div class="card-body d-flex flex-column product-card-body">
                            <a href="product.php?slug=<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" class="text-decoration-none text-dark">
                                <h5 class="card-title text-truncate mb-1 product-card-title fw-semibold"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h5>
                            </a>
                            <p class="card-text fs-5 fw-bold mb-2 product-card-price text-primary">
                                <?php
                                if ($pesoFormatter) {
                                    echo htmlspecialchars($pesoFormatter->formatCurrency($product['price'] ?? 0, 'PHP'));
                                } else {
                                    echo htmlspecialchars(formatCurrencyFallback($product['price'] ?? 0));
                                }
                                ?>
                            </p>
                            <p class="card-text flex-grow-1 text-muted small product-card-description"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)) . (mb_strlen($product['description'] ?? '') > 100 ? '...' : ''); ?></p>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center bg-white border-0 pt-0 pb-3 px-3">
                            <a href="product.php?slug=<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" class="btn btn-outline-primary btn-sm rounded-pill btn-product-action">View Product</a>
                            <?php if (isLoggedIn()): ?>
                                <form action="cart-process.php" method="POST" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-warning btn-sm rounded-pill btn-product-action"><i class="fas fa-cart-plus me-1"></i> Add to Cart</button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-warning btn-sm rounded-pill btn-product-action"><i class="fas fa-sign-in-alt me-1"></i> Login to Buy</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center empty-state-alert" role="alert">
                    <i class="fas fa-info-circle me-2 fa-lg"></i> No products found. Please add some products.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php
    if (isset($categories) && !empty($categories)): ?>
        <h2 class="mb-4 mt-5 text-center fw-bold text-dark section-title">Shop by Category</h2>
        <hr class="mb-5 section-divider">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-5 category-grid">
            <?php foreach ($categories as $category): ?>
                <div class="col">
                    <a href="category.php?id=<?php echo htmlspecialchars($category['id']); ?>" class="card text-center text-decoration-none text-dark shadow-sm h-100 category-card border-0">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                            <i class="fas fa-folder fa-3x text-muted mb-3 category-icon"></i> <h5 class="card-title mb-0 fw-semibold"><?php echo htmlspecialchars($category['name']); ?></h5>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php
template('templates/footer.php');
?>

<style>
    /* Global Background from my-account.php / customer-orders.php */
    body {
        background: linear-gradient(135deg, #f0f2f5 0%, #e0e4eb 100%);
        min-height: 100vh;
    }

    .container {
        max-width: 1100px; /* Consistent max width for content */
    }

    /* Hero Section */
    .hero-section {
        height: 450px; /* Tall enough for impact */
        position: relative;
        overflow: hidden; /* Ensure content stays within bounds */
        background-color: #333; /* Fallback */
    }

    .hero-section::before { /* Subtle texture for the hero background */
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('assets/img/dots-texture.png') repeat; /* A subtle dot pattern or similar */
        opacity: 0.1; /* Very faint */
        z-index: 0;
    }

    .hero-section .overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4); /* Darker overlay for text readability */
        z-index: 0;
    }

    .hero-section h1 {
        font-size: 3.5rem; /* Larger heading */
        letter-spacing: -1px;
        text-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }

    .hero-section p.lead {
        font-size: 1.25rem;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        text-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .hero-section .btn-light {
        color: #007bff; /* Primary brand color for the button text */
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .hero-section .btn-light:hover {
        background-color: #e2e6ea;
        transform: translateY(-2px); /* Lift effect */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.2) !important;
    }

    /* Section Titles and Dividers */
    .section-title {
        font-size: 2.2rem;
        font-weight: 700;
        color: #343a40;
        margin-bottom: 1rem;
    }

    .section-divider {
        width: 80px; /* Short and centered */
        height: 4px;
        background-color: #007bff; /* Primary color */
        border: 0;
        opacity: 1; /* Ensure full visibility */
        margin: 0 auto 3rem auto; /* Center it */
        border-radius: 2px;
    }

    /* Product Cards */
    .product-card {
        border-radius: 0.75rem;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        overflow: hidden; /* Ensures image corners are clipped */
    }

    .product-card:hover {
        transform: translateY(-5px); /* More pronounced lift */
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1) !important; /* Stronger shadow on hover */
    }

    .product-card-img {
        height: 220px; /* Fixed height for consistent image sizing */
        object-fit: cover; /* Crop image to fit */
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        transition: transform 0.3s ease; /* Smooth zoom on hover */
    }

    .product-card-img:hover {
        transform: scale(1.05); /* Slight zoom effect on image hover */
    }

    .product-card-body {
        padding: 1rem 1.25rem;
    }

    .product-card-title {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .product-card-price {
        color: #007bff; /* Primary color for price */
        font-size: 1.4rem !important; /* Slightly larger price */
        font-weight: 700 !important;
    }

    .product-card-description {
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .product-card .card-footer {
        background-color: #fcfcfc; /* Very light footer background */
        border-top: 1px solid #f0f0f0; /* Subtle border */
    }

    .btn-product-action {
        font-size: 0.85rem;
        padding: 0.4rem 0.9rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-outline-primary {
        color: #007bff;
        border-color: #007bff;
    }
    .btn-outline-primary:hover {
        background-color: #007bff;
        color: #fff;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #212529; /* Dark text for warning */
    }
    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #e0a800;
        color: #212529;
    }

    /* Category Cards */
    .category-card {
        border-radius: 0.75rem;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out, background-color 0.2s ease;
        background-color: #ffffff;
        border: 1px solid #e9ecef; /* Light border */
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.8rem 1.8rem rgba(0, 0, 0, 0.09) !important;
        background-color: #f8f9fa; /* Slight background change on hover */
    }

    .category-icon {
        color: #6c757d; /* Muted grey for category icons */
        transition: color 0.2s ease;
    }

    .category-card:hover .category-icon {
        color: #007bff; /* Primary color on hover */
    }

    /* Alerts */
    .empty-state-alert {
        border-radius: 0.75rem;
        border: 1px dashed #adb5bd;
        background-color: #fefefe;
        color: #6c757d;
        padding: 2rem;
        box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.03);
    }
</style>