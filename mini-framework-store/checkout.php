<?php
// checkout.php

session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

// Use the template() function to include header BEFORE any HTML output from checkout.php starts
template('templates/header.php');

use Aries\MiniFrameworkStore\Models\Checkout;
// use Aries\MiniFrameworkStore\Models\Product; // Uncomment if you need to fetch full product details for display from DB

$checkout = new Checkout();

$superTotal = 0;
$orderId = null;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $superTotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }
}

// === NumberFormatter INITIALIZATION ===
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
} else {
    // Fallback function if intl extension is not loaded (ensuring it's defined)
    if (!function_exists('formatCurrencyFallback')) {
        function formatCurrencyFallback($amount) {
            return '₱' . number_format($amount, 2);
        }
    }
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Basic server-side validation (can be expanded)
    if (empty($name) || empty($address) || empty($phone)) {
        $_SESSION['message'] = 'Please fill in all required shipping details.';
        // Note: With the alert removal, this message will no longer be displayed on the checkout page
        // If you still want to handle this visually, consider JavaScript alerts or re-adding a different error display mechanism.
        echo "<script>window.location.href='/arambalastore/mini-framework-store/checkout.php';</script>";
        exit();
    }

    if (isset($_SESSION['user'])) {
        $orderId = $checkout->userCheckout([
            'user_id' => $_SESSION['user']['id'],
            'total' => $superTotal
        ]);
    } else {
        $orderId = $checkout->guestCheckout([
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'total' => $superTotal
        ]);
    }

    if ($orderId) {
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                if (isset($item['id'])) {
                    $checkout->saveOrderDetails([
                        'order_id' => $orderId,
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'] ?? 0,
                        'price' => $item['price'] ?? 0,
                        'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 0)
                    ]);
                } else {
                    error_log("Warning: Skipping cart item as it has no 'id'. Item: " . print_r($item, true));
                }
            }
        }

        unset($_SESSION['cart']);
        $_SESSION['message'] = 'Order placed successfully! Your order number is #' . $orderId . '.';
        echo "<script>window.location.href='/arambalastore/mini-framework-store/index.php';</script>";
        exit();
    } else {
        $_SESSION['message'] = 'Error placing order. Please try again.';
        // Note: Similar to the validation message, this error will also not be displayed visually on checkout.php
        echo "<script>window.location.href='/arambalastore/mini-framework-store/checkout.php';</script>";
        exit();
    }
}

?>

<div class="container my-5 checkout-page-wrapper">
    <h1 class="mb-4 text-center fw-bold text-dark checkout-title">Complete Your Order</h1>
    <p class="lead text-center text-muted mb-5 checkout-subtitle">Almost there! Just confirm your details and place your order.</p>

    <?php if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0): ?>
        <div class="empty-cart-message text-center p-5 border rounded-3 bg-white shadow-sm">
            <i class="fas fa-shopping-cart fa-5x text-muted mb-4 animate__animated animate__bounceIn"></i>
            <h3 class="mb-3 fw-bold text-dark">Your cart is empty!</h3>
            <p class="lead text-muted">You need to add items to your cart before proceeding to checkout.</p>
            <a href="/arambalastore/mini-framework-store/index.php" class="btn btn-primary btn-lg mt-3 rounded-pill px-4 py-2">
                <i class="fas fa-arrow-left me-2"></i> Start Shopping Now
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 checkout-section-card">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-shopping-basket me-3 fa-lg text-primary"></i>
                        <h4 class="mb-0 fw-bold text-dark">Order Summary</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 checkout-order-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-muted small text-uppercase py-3 ps-4">Product</th>
                                        <th scope="col" class="text-center text-muted small text-uppercase py-3">Qty</th>
                                        <th scope="col" class="text-end text-muted small text-uppercase py-3 pe-4">Price</th>
                                        <th scope="col" class="text-end text-muted small text-uppercase py-3 pe-4">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($_SESSION['cart'] as $item): ?>
                                        <tr>
                                            <td class="py-3 ps-4">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($item['image_path'] ?? 'assets/img/placeholder.jpg'); ?>" class="checkout-item-img me-3 rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <a href="product.php?slug=<?php echo htmlspecialchars($item['slug'] ?? ''); ?>" class="text-decoration-none text-dark fw-semibold" style="max-width: 200px;"><?php echo htmlspecialchars($item['name'] ?? 'N/A'); ?></a>
                                                </div>
                                            </td>
                                            <td class="text-center py-3 fw-bold"><?php echo htmlspecialchars($item['quantity'] ?? 0); ?></td>
                                            <td class="text-end py-3 text-primary"><?php echo $pesoFormatter ? $pesoFormatter->formatCurrency($item['price'] ?? 0, 'PHP') : formatCurrencyFallback($item['price'] ?? 0); ?></td>
                                            <td class="text-end py-3 fw-bold text-dark"><?php echo $pesoFormatter ? $pesoFormatter->formatCurrency(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 'PHP') : formatCurrencyFallback(($item['price'] ?? 0) * ($item['quantity'] ?? 0)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-bold py-3 text-dark fs-5">Grand Total</td>
                                        <td class="text-end fw-bold fs-4 py-3 text-primary">
                                            <?php echo $pesoFormatter ? $pesoFormatter->formatCurrency($superTotal, 'PHP') : formatCurrencyFallback($superTotal); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 text-end border-top">
                        <a href="/arambalastore/mini-framework-store/cart.php" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                            <i class="fas fa-pencil-alt me-2"></i> Modify Cart
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 checkout-section-card mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-truck-fast me-3 fa-lg text-primary"></i>
                        <h4 class="mb-0 fw-bold text-dark">Shipping Details</h4>
                    </div>
                    <div class="card-body">
                        <form action="checkout.php" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" required value="<?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?>" placeholder="Enter your full name">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label fw-semibold">Delivery Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="address" name="address" required value="<?php echo htmlspecialchars($_SESSION['user']['address'] ?? ''); ?>" placeholder="Street, City, Zip Code">
                            </div>
                            <div class="mb-4">
                                <label for="phone" class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone" required value="<?php echo htmlspecialchars($_SESSION['user']['phone'] ?? ''); ?>" placeholder="+63 9xx xxx xxxx">
                            </div>

                            <h5 class="mb-3 fw-bold text-dark"><i class="fas fa-wallet me-2"></i> Payment Method</h5>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                <label class="form-check-label fw-semibold" for="cod">
                                    Cash on Delivery (COD)
                                </label>
                                <p class="text-muted small mb-0">Pay upon delivery of your order.</p>
                            </div>
                            <hr class="my-4">

                            <button type="submit" class="btn btn-primary btn-lg w-100 place-order-final-btn" name="submit">
                                <i class="fas fa-check-circle me-2"></i> Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php template('templates/footer.php'); ?>

<style>
    /* Global Background */
    body {
        background: linear-gradient(135deg, #f0f2f5 0%, #e0e4eb 100%);
        min-height: 10vh;
    }

    .container {
        max-width: 1100px; /* Consistent max width for content */
    }

    /* Page Title */
    .checkout-title {
        font-size: 2.5rem;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }

    .checkout-subtitle {
        font-size: 1.15rem;
        color: #6c757d;
    }

    /* Empty Cart Message (re-used from cart.php for consistency) */
    .empty-cart-message {
        border-radius: 1rem; /* More rounded corners */
        background-color: #ffffff;
        padding: 3rem !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        color: #495057; /* Darker text for readability */
    }

    .empty-cart-message h3 {
        font-weight: 700;
        color: #343a40;
    }

    .empty-cart-message p.lead {
        font-size: 1.15rem;
        color: #6c757d;
    }

    .empty-cart-message .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .empty-cart-message .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 0.3rem 0.6rem rgba(0, 123, 255, 0.2);
    }

    /* Card Styling for Sections */
    .checkout-section-card {
        border-radius: 0.75rem;
        background-color: #ffffff;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        overflow: hidden; /* Ensures table/content corners are clipped */
    }

    .checkout-section-card .card-header {
        background-color: #f8f9fa !important; /* Lighter header background */
        border-bottom: 1px solid #dee2e6;
        padding: 1.25rem 1.5rem; /* More padding */
    }

    .checkout-section-card .card-header h4 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #343a40;
    }

    .checkout-section-card .card-header .fa-lg {
        color: #007bff; /* Primary color for icons */
    }

    /* Order Table */
    .checkout-order-table thead th {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6c757d !important;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .checkout-order-table tbody tr {
        border-bottom: 1px solid #f2f2f2;
    }
    .checkout-order-table tbody tr:last-child {
        border-bottom: none; /* No border for the very last row */
    }

    .checkout-item-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 0.25rem;
        border: 1px solid #f0f0f0;
    }

    .checkout-order-table td {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .checkout-order-table tfoot tr {
        background-color: #e9f2ff !important; /* Light blue background for total row */
        border-top: 2px solid #007bff; /* Stronger border for total */
    }

    .checkout-order-table tfoot td {
        color: #007bff; /* Primary color for total */
    }

    /* Form Styling */
    .form-label {
        font-size: 0.95rem;
        color: #495057;
        margin-bottom: 0.4rem;
    }

    .form-control-lg {
        border-radius: 0.5rem; /* Slightly more rounded inputs */
        padding: 0.8rem 1rem;
        font-size: 1rem;
    }

    .form-control-lg:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
    }

    .form-check-input {
        margin-top: 0.35rem; /* Align checkbox/radio better */
    }
    .form-check-label {
        color: #343a40;
    }
    .form-check p {
        font-size: 0.85rem;
    }

    /* Buttons */
    .place-order-final-btn {
        background-color: #007bff; /* Primary blue for final action */
        border-color: #007bff;
        font-weight: 600;
        letter-spacing: 0.5px;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem; /* Consistent button radius */
        transition: all 0.2s ease;
    }

    .place-order-final-btn:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 0.4rem 0.8rem rgba(0, 123, 255, 0.3);
    }

    .btn-outline-secondary {
        font-weight: 500;
        padding: 0.5rem 1.2rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
    }

    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: #fff;
    }

    /* Alerts */
    .alert {
        border-radius: 0.75rem;
        box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.05);
    }

    /* Media Queries for Responsiveness */
    @media (max-width: 767.98px) {
        .checkout-title {
            font-size: 2rem;
        }
        .checkout-subtitle {
            font-size: 1rem;
        }
        .checkout-section-card .card-header {
            padding: 1rem;
        }
        .checkout-section-card .card-header h4 {
            font-size: 1.1rem;
        }
        .checkout-order-table th,
        .checkout-order-table td {
            font-size: 0.9rem;
            padding-left: 10px !important;
            padding-right: 10px !important;
        }
        .checkout-item-img {
            width: 45px;
            height: 45px;
        }
        .checkout-order-table tfoot td {
            font-size: 1.2rem !important;
        }
        .form-control-lg {
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
        }
        .place-order-final-btn,
        .btn-outline-secondary {
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
        }
    }
</style>