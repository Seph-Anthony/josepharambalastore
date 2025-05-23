<?php
// helpers/functions.php

// This is where all your helper functions go.

/**
 * Checks if a user is currently logged in.
 * @return bool True if a user is logged in, false otherwise.
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
}

/**
 * Generates a URL-friendly slug from a given string.
 * @param string $text The string to slugify (e.g., product name).
 * @return string The generated slug.
 */
if (!function_exists('generateSlug')) {
    function generateSlug($text) {
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Transliterate non-latin characters to latin equivalents (requires intl extension)
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        }
        // Remove duplicate hyphens
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trim hyphens from start and end
        $text = trim($text, '-');
        // Convert to lowercase
        $text = strtolower($text);
        return $text;
    }
}


/**
 * Includes a template file and extracts provided data variables into its scope.
 *
 * @param string $relativePath The path to the template file relative to the mini-framework-store root (e.g., 'templates/header.php').
 * @param array $data An associative array of data to be made available in the template (e.g., ['categories' => $categories]).
 */
if (!function_exists('template')) {
    function template($relativePath, $data = []) { // Added $data parameter with a default empty array
        $projectRoot = __DIR__ . '/../';
        $fullPath = $projectRoot . $relativePath;

        if (file_exists($fullPath)) {
            extract($data); // This is the crucial line: it extracts array keys as variables
            include $fullPath;
        } else {
            error_log("Template file not found: " . $fullPath);
            // echo "<div style='color: red; text-align: center;'>Template Error: Could not find file: " . htmlspecialchars($relativePath) . "</div>";
        }
    }
}

/**
 * Fallback currency formatting function if the intl extension is not available.
 * @param float|int $amount The numeric amount to format.
 * @return string The formatted currency string.
*/
if (!function_exists('formatCurrencyFallback')) {
    function formatCurrencyFallback($amount) {
        return '₱' . number_format((float)$amount, 2);
    }
}

/**
 * Counts the total quantity of items in the session cart.
 * @return int The total number of items in the cart.
 */
if (!function_exists('countCart')) {
    function countCart() {
        $totalItems = 0;
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                // Ensure 'quantity' exists and is numeric before adding
                $totalItems += isset($item['quantity']) && is_numeric($item['quantity']) ? (int)$item['quantity'] : 0;
            }
        }
        return $totalItems;
    }
}

// Add any other helper functions you might have here.
// For example, validation functions, debugging functions, etc.

?>