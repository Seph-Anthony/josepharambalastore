<?php
// cart-process.php

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/helpers/functions.php'; // Include your functions.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use Aries\MiniFrameworkStore\Models\Product; // Assuming this is your Product model namespace

// Check for AJAX clear cart action first
if (isset($_POST['action']) && $_POST['action'] === 'clear_cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $_SESSION['cart'] = []; // Clear the cart

    $response = [
        'status' => 'success',
        'message' => 'Cart cleared successfully.',
        'cart_total_formatted' => '₱0.00', // Or use your formatter for 0
        'cart_item_count' => 0
    ];

    // Initialize formatter for the zero value if you want to be precise
    if (extension_loaded('intl')) {
        $amounLocale = 'en_PH';
        $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
        $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
        $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
        $response['cart_total_formatted'] = $pesoFormatter->formatCurrency(0, 'PHP');
    } else if (function_exists('formatCurrencyFallback')) {
        $response['cart_total_formatted'] = formatCurrencyFallback(0);
    }

    echo json_encode($response);
    exit();
}


// --- Existing code for adding items to cart (form submission) ---
// This part will only execute if it's not the 'clear_cart' AJAX action.

$redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php'; // Default redirect

// For adding items, user must be logged in
if (!isLoggedIn()) {
    // Store intended action or redirect URL if needed before sending to login
    $_SESSION['message'] = ['type' => 'error', 'text' => 'You need to login to add items to your cart.'];
    header('Location: login.php?redirect=' . urlencode($redirectUrl)); // Redirect to login
    exit;
}

if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $productId = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT, ["options" => ["default" => 1, "min_range" => 1]]);


    if ($productId === false || $productId <= 0) {
        error_log("Invalid product ID received in cart-process.php. ID: " . $_POST['product_id']);
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: Invalid product ID.'];
        header('Location: ' . $redirectUrl);
        exit;
    }
     if ($quantity === false || $quantity <= 0) {
        error_log("Invalid quantity received in cart-process.php. QTY: " . $_POST['quantity']);
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: Invalid quantity.'];
        // Default quantity to 1 if invalid from form, or handle as error
        $quantity = 1;
    }


    $_SESSION['cart'] = $_SESSION['cart'] ?? [];

    $productModel = new Product();
    $productDetails = $productModel->getById($productId);

    if (!$productDetails) {
        error_log("Product not found in DB for ID: " . $productId . " in cart-process.php.");
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: Product not found.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Add or update product in cart
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
        // Optionally, check against max stock:
        // $_SESSION['cart'][$productId]['quantity'] = min($_SESSION['cart'][$productId]['quantity'], $productDetails['stock']);
        $_SESSION['cart'][$productId]['total'] = $_SESSION['cart'][$productId]['price'] * $_SESSION['cart'][$productId]['quantity'];
        $_SESSION['message'] = ['type' => 'success', 'text' => $productDetails['name'] . ' quantity updated in cart.'];
    } else {
        $_SESSION['cart'][$productId] = [
            'id' => $productId,
            'name' => $productDetails['name'],
            'price' => (float)$productDetails['price'], // Ensure price is float
            'image_path' => $productDetails['image_path'],
            'slug' => $productDetails['slug'], // Make sure to fetch slug if needed for cart.php links
            'short_description' => $productDetails['short_description'], // And short description
            'quantity' => $quantity,
            'total' => (float)$productDetails['price'] * $quantity
        ];
        $_SESSION['message'] = ['type' => 'success', 'text' => $productDetails['name'] . ' added to cart!'];
    }
     // Redirect to cart page after processing add/update
    header('Location: cart.php');
    exit;

} else if ($_SERVER['REQUEST_METHOD'] === 'POST') { // If it's a POST but not add or clear action
    error_log("No product_id or valid action received in POST for cart-process.php.");
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: No product data or valid action received.'];
    header('Location: ' . $redirectUrl);
    exit;
} else {
    // If accessed via GET or other methods without specific actions
    $_SESSION['message'] = ['type' => 'info', 'text' => 'Welcome to the cart processing page.'];
    header('Location: index.php'); // Or cart.php
    exit;
}
?>