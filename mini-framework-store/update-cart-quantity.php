<?php
// update-cart-quantity.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php'; // Make sure this contains formatCurrencyFallback if needed

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header to indicate JSON response
header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'An unexpected error occurred.',
    'cart_item_count' => 0,
    'cart_total_formatted' => '₱0.00', // Default values
    'new_subtotal_formatted' => '₱0.00', // Default
    'item_removed' => false,
    'new_quantity' => 0 // Initialize new_quantity in the response
];

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

// Check if product_id and quantity are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    $response['message'] = 'Invalid product ID or quantity provided.';
    echo json_encode($response);
    exit();
}

$productId = (int)$_POST['product_id'];
$newQuantity = (int)$_POST['quantity'];

// Validate quantity - allow 0 for removal
if ($newQuantity < 0) {
    $response['message'] = 'Quantity cannot be negative.';
    echo json_encode($response);
    exit();
}

// Initialize formatter outside the loop if using it
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
} else {
    // Define fallback if intl is not loaded
    if (!function_exists('formatCurrencyFallback')) {
        function formatCurrencyFallback($amount) {
            return '₱' . number_format($amount, 2);
        }
    }
}


// Get cart from session
$_SESSION['cart'] = $_SESSION['cart'] ?? []; // Ensure cart exists

if (isset($_SESSION['cart'][$productId])) {
    // Product found in cart
    if ($newQuantity === 0) {
        // If new quantity is 0, remove the item from the cart
        unset($_SESSION['cart'][$productId]);
        $response['item_removed'] = true;
        $response['message'] = 'Product removed from cart.';
        // When an item is removed, its subtotal for that item effectively becomes 0
        $response['new_subtotal_formatted'] = $pesoFormatter ? $pesoFormatter->formatCurrency(0, 'PHP') : formatCurrencyFallback(0);
        $response['new_quantity'] = 0; // The quantity is 0 if removed

    } else {
        // Update the quantity
        $_SESSION['cart'][$productId]['quantity'] = $newQuantity;
        // Recalculate subtotal for the updated item
        $_SESSION['cart'][$productId]['subtotal'] = (float)$_SESSION['cart'][$productId]['price'] * (int)$_SESSION['cart'][$productId]['quantity'];
        $response['new_subtotal_formatted'] = $pesoFormatter ? $pesoFormatter->formatCurrency($_SESSION['cart'][$productId]['subtotal'], 'PHP') : formatCurrencyFallback($_SESSION['cart'][$productId]['subtotal']);
        $response['message'] = 'Cart quantity updated successfully!';
        // *** CRUCIAL ADDITION: Send back the new quantity ***
        $response['new_quantity'] = $newQuantity;
    }
    $response['status'] = 'success';

} else {
    // Product not found in cart (this shouldn't happen for updates/removals if cart.php is correctly displaying items)
    // However, if a user manually tries to update an item not in cart, handle it.
    if ($newQuantity > 0) { // If a positive quantity was sent for a non-existent item
        $response['message'] = 'Product not found in cart to update.';
    } else { // If 0 quantity was sent for a non-existent item, it's effectively "removed" from a UI perspective
        $response['status'] = 'success';
        $response['message'] = 'Product was not in cart (no action needed).';
        $response['item_removed'] = true; // Treat as removed from UI perspective
        $response['new_quantity'] = 0; // Since it's effectively removed/not found
        $response['new_subtotal_formatted'] = $pesoFormatter ? $pesoFormatter->formatCurrency(0, 'PHP') : formatCurrencyFallback(0);
    }
}

// Recalculate overall cart total and item count after modification
$currentCartTotal = 0;
$currentCartItemCount = 0;
foreach ($_SESSION['cart'] as $item) {
    // Ensure 'price' and 'quantity' exist and are numeric for safe calculation
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
    $currentCartTotal += ($price * $quantity);
    $currentCartItemCount += $quantity;
}

$response['cart_total_formatted'] = $pesoFormatter ? $pesoFormatter->formatCurrency($currentCartTotal, 'PHP') : formatCurrencyFallback($currentCartTotal);
$response['cart_item_count'] = $currentCartItemCount;


echo json_encode($response);
exit();
?>