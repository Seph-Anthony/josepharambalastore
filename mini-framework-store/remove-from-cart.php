<?php
// remove-from-cart.php (CLEANED UP VERSION - No Echoes, Redirect active)

require_once __DIR__ . '/vendor/autoload.php';
include 'helpers/functions.php'; // For isLoggedIn() and potentially other helpers

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in if that's a requirement for cart modification
if (!isLoggedIn()) {
    $_SESSION['message'] = 'Please log in to modify your cart.';
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}

// Get the product ID to remove from POST data
if (isset($_POST['product_id'])) {
    $productIdToRemove = (int)$_POST['product_id'];

    if ($productIdToRemove > 0) {
        // Check if the cart exists and the product is in it
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            if (isset($_SESSION['cart'][$productIdToRemove])) {
                // Remove the product from the cart session
                unset($_SESSION['cart'][$productIdToRemove]);
                $_SESSION['message'] = 'Product removed from cart.'; // Optional: success message
            } else {
                $_SESSION['message'] = 'Product not found in cart.'; // Optional: error message
            }
        }
    } else {
        $_SESSION['message'] = 'Invalid product ID for removal.'; // Optional: error message
    }
} else {
    $_SESSION['message'] = 'No product ID specified for removal.'; // Optional: error message
}

// Redirect back to the cart page after removal
header('Location: cart.php');
exit;

?>