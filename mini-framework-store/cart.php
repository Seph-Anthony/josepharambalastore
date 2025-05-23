<?php
// cart.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$cartItems = [];
$totalPrice = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $price = isset($item['price']) ? (float) $item['price'] : 0.0;
        $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

        // Use 'subtotal' key if it exists, otherwise calculate.
        // It's good practice to ensure 'subtotal' is always calculated or updated on every load
        // in case the session data wasn't perfectly consistent.
        $item['subtotal'] = $price * $quantity;
        $cartItems[] = $item;
        $totalPrice += $item['subtotal'];
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
    // Fallback function if intl extension is not loaded (from previous index.php)
    if (!function_exists('formatCurrencyFallback')) {
        function formatCurrencyFallback($amount) {
            return '₱' . number_format($amount, 2);
        }
    }
}

template('templates/header.php');

?>

<div class="container my-5">
    <h1 class="text-center mb-5 fw-bold text-dark cart-page-title">Your Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
        <div class="empty-cart-message text-center p-5 border rounded-3 bg-white shadow-sm">
            <i class="fas fa-shopping-cart fa-5x text-muted mb-4 animate__animated animate__bounceIn"></i>
            <h3 class="mb-3 fw-bold text-dark">Your cart is currently empty!</h3>
            <p class="lead text-muted">Looks like you haven't added any products to your cart yet. Let's find something great!</p>
            <a href="index.php" class="btn btn-primary btn-lg mt-3 rounded-pill px-4 py-2">
                <i class="fas fa-arrow-left me-2"></i> Start Shopping Now
            </a>
        </div>
    <?php else: ?>
        <div class="row gx-4 gy-4 cart-content-wrapper">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 cart-items-container">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">Cart Items (<?php echo count($cartItems); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0 cart-table">
                                <thead class="border-bottom">
                                    <tr>
                                        <th scope="col" class="py-3 ps-4 text-muted small text-uppercase">Product</th>
                                        <th scope="col" class="py-3 text-center text-muted small text-uppercase">Price</th>
                                        <th scope="col" class="py-3 text-center text-muted small text-uppercase">Quantity</th>
                                        <th scope="col" class="py-3 text-center text-muted small text-uppercase">Subtotal</th>
                                        <th scope="col" class="py-3 text-end pe-4 text-muted small text-uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr class="cart-item-row border-bottom" data-product-id="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">
                                            <td class="py-3 ps-4 d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($item['image_path'] ?? 'assets/img/placeholder.jpg'); ?>" class="cart-item-img me-3 rounded-3" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <div>
                                                    <h6 class="mb-0 text-truncate cart-item-name fw-semibold" style="max-width: 250px;">
                                                        <a href="product.php?slug=<?php echo htmlspecialchars($item['slug'] ?? ''); ?>" class="text-decoration-none text-dark">
                                                            <?php echo htmlspecialchars($item['name'] ?? 'N/A'); ?>
                                                        </a>
                                                    </h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($item['short_description'] ?? '', 0, 50)) . (mb_strlen($item['short_description'] ?? '') > 50 ? '...' : ''); ?></small>
                                                </div>
                                            </td>
                                            <td class="py-3 text-center cart-item-price fw-semibold text-primary">
                                                <?php echo $pesoFormatter ? $pesoFormatter->formatCurrency($item['price'] ?? 0, 'PHP') : formatCurrencyFallback($item['price'] ?? 0); ?>
                                            </td>
                                            <td class="py-3 text-center">
                                                <div class="input-group quantity-input-group mx-auto" style="width: 120px;">
                                                    <button class="btn btn-outline-secondary btn-sm quantity-minus" type="button" data-product-id="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">-</button>
                                                    <input type="text" class="form-control form-control-sm text-center quantity-input"
                                                           value="<?php echo htmlspecialchars($item['quantity'] ?? 0); ?>"
                                                           data-product-id="<?php echo htmlspecialchars($item['id'] ?? ''); ?>"
                                                           data-original-value="<?php echo htmlspecialchars($item['quantity'] ?? 0); ?>"
                                                           min="1">
                                                    <button class="btn btn-outline-secondary btn-sm quantity-plus" type="button" data-product-id="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">+</button>
                                                </div>
                                            </td>
                                            <td class="py-3 text-center fw-bold product-subtotal text-dark">
                                                <?php echo $pesoFormatter ? $pesoFormatter->formatCurrency($item['subtotal'] ?? 0, 'PHP') : formatCurrencyFallback($item['subtotal'] ?? 0); ?>
                                            </td>
                                            <td class="py-3 text-end pe-4">
                                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill remove-from-cart-btn" data-product-id="<?php echo htmlspecialchars($item['id'] ?? ''); ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center border-top">
                        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                        </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 order-summary-card">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush summary-list">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                Subtotal
                                <span class="fw-bold fs-5 text-dark cart-total-amount">
                                    <?php echo $pesoFormatter ? $pesoFormatter->formatCurrency($totalPrice, 'PHP') : formatCurrencyFallback($totalPrice); ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                Shipping
                                <span class="text-muted">Calculated at checkout</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-4 total-line">
                                <h4 class="mb-0 fw-bold text-dark">Grand Total</h4>
                                <h4 class="mb-0 fw-bold text-primary cart-total-amount">
                                    <?php echo $pesoFormatter ? $pesoFormatter->formatCurrency($totalPrice, 'PHP') : formatCurrencyFallback($totalPrice); ?>
                                </h4>
                            </li>
                        </ul>
                        <div class="d-grid gap-3 mt-4">
                            <?php if (isLoggedIn()): ?>
                                <a href="checkout.php" class="btn btn-success btn-lg proceed-checkout-btn rounded-pill py-2">
                                    <i class="fas fa-credit-card me-2"></i> Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=checkout.php" class="btn btn-success btn-lg proceed-checkout-btn rounded-pill py-2">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login to Checkout
                                </a>
                                <p class="text-center text-muted small mt-2 mb-0">You must be logged in to proceed.</p>
                            <?php endif; ?>
                            <a href="index.php" class="btn btn-outline-secondary btn-lg continue-shopping-summary-btn rounded-pill py-2">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php template('templates/footer.php'); ?>

<script>
$(document).ready(function() {

    // Function to update cart item quantity via AJAX (now also handles removal when quantity is 0)
    function updateCartItem(productId, newQuantity) {
        var productRow = $('tr[data-product-id="' + productId + '"]');
        var inputField = productRow.find('.quantity-input'); // Get the input field
        // Get the original quantity from the data attribute or the current value
        var originalQuantity = parseInt(inputField.data('original-value') || inputField.val());

        // Client-side validation: Ensure quantity is non-negative
        if (newQuantity < 0) {
            newQuantity = 0; // If attempted to go below 0, set to 0 for removal
        }

        // *** IMPORTANT CHANGE: Optimistically update UI *before* AJAX call ***
        // Update the displayed value immediately
        inputField.val(newQuantity);
        // Update the 'data-original-value' attribute so subsequent clicks work from the new value
        inputField.data('original-value', newQuantity);


        $.ajax({
            url: 'update-cart-quantity.php', // This needs to handle quantity 0 for removal
            method: 'POST',
            data: {
                product_id: productId,
                quantity: newQuantity
            },
            dataType: 'json', // Expect JSON response
            success: function(response) {
                if (response.status === 'success') {
                    if (response.item_removed) {
                        productRow.fadeOut(300, function() {
                            $(this).remove();
                            // Check if cart is now truly empty after removal
                            if ($('.cart-item-row').length === 0) {
                                $('.cart-page-title').hide(); // Hide the cart title
                                // Use the .cart-content-wrapper to replace content
                                $('.cart-content-wrapper').html(
                                    '<div class="empty-cart-message text-center p-5 border rounded-3 bg-white shadow-sm">' +
                                    '<i class="fas fa-shopping-cart fa-5x text-muted mb-4 animate__animated animate__bounceIn"></i>' +
                                    '<h3 class="mb-3 fw-bold text-dark">Your cart is currently empty!</h3>' +
                                    '<p class="lead text-muted">Looks like you haven\'t added any products to your cart yet. Let\'s find something great!</p>' +
                                    '<a href="index.php" class="btn btn-primary btn-lg mt-3 rounded-pill px-4 py-2"><i class="fas fa-arrow-left me-2"></i> Start Shopping Now</a>' +
                                    '</div>'
                                );
                            }
                            updateOverallCartSummary(response); // Update total and badge
                        });
                    } else {
                        // Update subtotal and total in UI for quantity changes
                        productRow.find('.product-subtotal').text(response.new_subtotal_formatted);
                        // Make sure the displayed quantity is updated to the *actual* quantity from the server response
                        // (though we already updated it optimistically, this confirms server sync)
                        inputField.val(response.new_quantity);
                        // CRUCIAL: Update the original value data attribute with the server's confirmed quantity
                        inputField.data('original-value', response.new_quantity);
                        updateOverallCartSummary(response);
                    }
                } else {
                    // Revert UI change if update failed
                    inputField.val(originalQuantity);
                    // Revert data attribute too if update failed
                    inputField.data('original-value', originalQuantity);
                    alert(response.message || 'Error updating cart quantity.');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                // Revert UI change and show error
                inputField.val(originalQuantity);
                // Revert data attribute too on error
                inputField.data('original-value', originalQuantity);
                alert("An error occurred while updating the cart. Please try again.");
            }
        });
    }

    // Function to update the cart summary (total and badge)
    function updateOverallCartSummary(response) {
        $('.cart-total-amount').text(response.cart_total_formatted);
        $('#cart-count').text(response.cart_item_count); // Assuming you have a span/badge with id="cart-count" in your header
        $('.cart-items-container .card-header h5').text('Cart Items (' + response.cart_item_count + ')'); // Update header count
    }


    // Event listener for quantity input change
    $(document).on('change', '.quantity-input', function() {
        var productId = $(this).data('product-id');
        var newQuantity = parseInt($(this).val());
        var originalQuantity = parseInt($(this).data('original-value'));

        if (isNaN(newQuantity) || newQuantity < 0) {
            alert('Please enter a valid quantity.');
            $(this).val(originalQuantity); // Revert to original
            $(this).data('original-value', originalQuantity); // IMPORTANT: Revert data attribute too
            return;
        }

        if (newQuantity === originalQuantity) {
            return; // No change, no need to update
        }

        updateCartItem(productId, newQuantity);
    });


    // Event listener for quantity plus button
    $(document).on('click', '.quantity-plus', function() {
        var productId = $(this).data('product-id');
        var inputField = $('input.quantity-input[data-product-id="' + productId + '"]');
        var currentQuantity = parseInt(inputField.val());
        var newQuantity = currentQuantity + 1;
        updateCartItem(productId, newQuantity);
    });

    // Event listener for quantity minus button
    $(document).on('click', '.quantity-minus', function() {
        var productId = $(this).data('product-id');
        var inputField = $('input.quantity-input[data-product-id="' + productId + '"]');
        var currentQuantity = parseInt(inputField.val());
        var newQuantity = currentQuantity - 1;

        if (newQuantity < 1) { // If it goes below 1, treat as removal
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                updateCartItem(productId, 0); // Remove item
            } else {
                // If user cancels removal, revert the displayed quantity and its data attribute
                inputField.val(currentQuantity);
                inputField.data('original-value', currentQuantity);
            }
        } else {
            updateCartItem(productId, newQuantity);
        }
    });


    // Event listener for individual "Remove" (trash can) button
    $(document).on('click', '.remove-from-cart-btn', function() {
        var productId = $(this).data('product-id');
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            updateCartItem(productId, 0); // Send quantity 0 to remove the item
        }
    });

});
</script>

<style>
    /* Global Background */
    body {
        background: linear-gradient(135deg, #f0f2f5 0%, #e0e4eb 100%);
        min-height: 100vh;
    }

    .container {
        max-width: 1100px; /* Consistent max width for content */
    }

    /* Cart Title */
    .cart-page-title {
        font-size: 2.5rem;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }

    /* Empty Cart Message */
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

    /* Cart Items Container */
    .cart-items-container, .order-summary-card {
        border-radius: 0.75rem;
        background-color: #ffffff;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        overflow: hidden; /* Important for table border-radius */
    }

    .cart-items-container .card-header,
    .order-summary-card .card-header {
        background-color: #f8f9fa !important; /* Lighter header background */
        border-bottom: 1px solid #dee2e6;
        padding: 1.25rem 1.5rem; /* More padding */
    }

    .cart-items-container .card-header h5,
    .order-summary-card .card-header h5 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #343a40;
    }

    /* Table Styling */
    .cart-table thead th {
        font-size: 0.85rem;
        font-weight: 600;
        color: #6c757d !important;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .cart-item-row {
        border-bottom: 1px solid #f2f2f2 !important; /* Lighter row dividers */
    }

    .cart-item-img {
        width: 80px; /* Fixed width */
        height: 80px; /* Fixed height */
        object-fit: cover; /* Crop image to fit */
        border-radius: 0.5rem !important; /* Rounded corners for images */
        border: 1px solid #f0f0f0;
    }

    .cart-item-name {
        font-size: 1rem;
        font-weight: 600;
        color: #343a40;
    }

    .cart-item-name a:hover {
        color: #0056b3 !important;
    }

    .cart-item-price, .product-subtotal {
        font-size: 1.15rem;
        font-weight: 700;
        color: #007bff; /* Primary color for prices */
    }

    /* Quantity Input Group */
    .quantity-input-group {
        width: 120px;
        margin: 0 auto;
    }

    .quantity-input-group .form-control {
        text-align: center;
        border-left: none;
        border-right: none;
        box-shadow: none;
        font-weight: 600;
        color: #343a40;
        padding: 0.375rem 0.2rem; /* Adjusted padding */
        height: calc(1.813rem + 2px); /* Match btn-sm height */
    }

    .quantity-input-group .btn {
        width: 35px;
        font-weight: bold;
        border-radius: 0.25rem;
        border-color: #ced4da;
        color: #495057;
        transition: background-color 0.2s ease, color 0.2s ease;
        padding: 0.25rem 0.5rem; /* Adjusted padding for sm buttons */
    }

    .quantity-input-group .btn:hover {
        background-color: #e9ecef;
        color: #212529;
    }

    /* Remove Button */
    .remove-from-cart-btn {
        font-size: 0.85rem;
        padding: 0.4rem 0.9rem;
        font-weight: 500;
        color: #dc3545;
        border-color: #dc3545;
        transition: all 0.2s ease;
    }

    .remove-from-cart-btn:hover {
        background-color: #dc3545;
        color: #fff !important;
        transform: translateY(-1px);
    }

    /* Cart Footer Actions */
    .cart-items-container .card-footer {
        border-top: 1px solid #dee2e6;
        padding: 1rem 1.5rem;
        background-color: #f8f9fa !important;
    }

    .cart-items-container .card-footer .btn-outline-secondary {
        font-weight: 500;
        padding: 0.5rem 1.2rem;
        transition: all 0.2s ease;
    }

    .cart-items-container .card-footer .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: #fff;
    }

    /* Order Summary Card */
    .order-summary-card {
        border-radius: 0.75rem;
    }

    .order-summary-card .summary-list .list-group-item {
        border: none;
        padding: 0.75rem 0 !important;
        font-size: 1.05rem;
        color: #343a40;
    }

    .order-summary-card .summary-list .list-group-item.total-line {
        border-top: 1px dashed #dee2e6; /* Dashed line for total */
        margin-top: 0.5rem;
        padding-top: 1rem !important;
    }

    .order-summary-card .summary-list .list-group-item h4 {
        font-size: 1.5rem; /* Larger total amount */
    }

    .proceed-checkout-btn, .continue-shopping-summary-btn {
        font-weight: 600;
        letter-spacing: 0.5px;
        padding: 0.75rem 1rem !important;
        transition: all 0.2s ease;
    }

    .proceed-checkout-btn {
        background-color: #28a745; /* Success green */
        border-color: #28a745;
    }
    .proceed-checkout-btn:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-1px);
        box-shadow: 0 0.3rem 0.6rem rgba(40, 167, 69, 0.2);
    }

    .continue-shopping-summary-btn:hover {
        background-color: #6c757d;
        color: #fff;
        transform: translateY(-1px);
    }

    /* Media Queries for Responsiveness */
    @media (max-width: 767.98px) {
        .cart-page-title {
            font-size: 2rem;
        }
        .cart-item-img {
            width: 60px;
            height: 60px;
        }
        .cart-item-name {
            font-size: 0.9rem;
            max-width: 150px !important;
        }
        .cart-item-price, .product-subtotal {
            font-size: 1rem;
        }
        .cart-table th, .cart-table td {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }
        .quantity-input-group {
            width: 90px;
        }
        .quantity-input-group .form-control {
            padding: 0.25rem;
        }
        .quantity-input-group .btn {
            width: 25px;
        }
        .remove-from-cart-btn {
            padding: 0.3rem 0.6rem;
        }
        .card-header, .card-footer {
            padding: 1rem !important;
        }
        .order-summary-card .summary-list .list-group-item {
            font-size: 0.95rem;
        }
        .order-summary-card .summary-list .list-group-item h4 {
            font-size: 1.2rem;
        }
    }
</style>