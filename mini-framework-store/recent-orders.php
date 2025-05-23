<?php
// recent-orders.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

use Aries\MiniFrameworkStore\Models\Checkout;
use Carbon\Carbon;

// --- IMPORTANT: AUTHENTICATION AND REDIRECTS MUST BE BEFORE ANY HTML OUTPUT ---
if (!isLoggedIn()) {
    $_SESSION['message'] = 'Please login to view orders.';
    header('Location: login.php');
    exit();
}

$userType = $_SESSION['user']['user_type_name'] ?? null; // Use null coalescing for safety
$currentUserId = $_SESSION['user']['id'] ?? null; // Use null coalescing for safety

// Only allow Sellers or Admins to view this page.
if (!isset($userType) || ($userType !== 'Seller' && $userType !== 'Admin')) {
    $_SESSION['message'] = 'Access denied. You must be a Seller or Admin to view this page.';
    header('Location: my-account.php');
    exit();
}

$checkoutModel = new Checkout();

// --- START: Handle POST request for status update directly in this file ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add the error_log statements here as we did for update-order-status.php
    error_log("recent-orders.php (POST handler): Script started.");

    // 1. Validate input data
    if (!isset($_POST['order_id']) || !isset($_POST['status_id'])) {
        $_SESSION['message'] = 'Missing order ID or status ID.';
        error_log("recent-orders.php (POST handler): Missing POST data. order_id: " . ($_POST['order_id'] ?? 'N/A') . ", status_id: " . ($_POST['status_id'] ?? 'N/A'));
        header('Location: recent-orders.php'); // Redirect to self to prevent re-submission
        exit();
    }

    $orderId = (int)$_POST['order_id'];
    $newStatusId = (int)$_POST['status_id'];

    error_log("recent-orders.php (POST handler): Received order ID: " . $orderId . ", New Status ID: " . $newStatusId);

    // Prevent status changes to an invalid ID (e.g., 0 or excessively large)
    if ($newStatusId <= 0) { // Assuming status IDs start from 1
        $_SESSION['message'] = 'Invalid status selected.';
        error_log("recent-orders.php (POST handler): Invalid new status ID: " . $newStatusId . ". Redirecting.");
        header('Location: recent-orders.php'); // Redirect to self
        exit();
    }
    error_log("recent-orders.php (POST handler): New status ID is valid.");

    // Fetch existing order details to check if the current seller is authorized to update this order
    $orderDetails = $checkoutModel->getOrderDetailsById($orderId);

    if (empty($orderDetails)) {
        $_SESSION['message'] = 'Order not found.';
        error_log("recent-orders.php (POST handler): getOrderDetailsById returned empty for order ID: " . $orderId . ". Redirecting.");
        header('Location: recent-orders.php'); // Redirect to self
        exit();
    }
    error_log("recent-orders.php (POST handler): Order details fetched successfully for order ID: " . $orderId . ". Found " . count($orderDetails) . " items.");

    // For Sellers: Ensure they can only update orders for *their* products.
    $isAuthorized = false;
    if ($userType === 'Admin') {
        $isAuthorized = true; // Admin can update any order
        error_log("recent-orders.php (POST handler): User is Admin, full authorization.");
    } elseif ($userType === 'Seller') {
        foreach ($orderDetails as $item) {
            if (isset($item['seller_id']) && $item['seller_id'] == $currentUserId) { // Use $currentUserId here
                $isAuthorized = true;
                error_log("recent-orders.php (POST handler): Seller " . $currentUserId . " is authorized for order " . $orderId . " (item seller_id: " . $item['seller_id'] . ").");
                break;
            }
        }
        if (!$isAuthorized) {
            error_log("recent-orders.php (POST handler): Seller " . $currentUserId . " not authorized for order " . $orderId . ". No matching seller_id found in order items. Redirecting.");
        }
    }

    if (!$isAuthorized) {
        $_SESSION['message'] = 'You are not authorized to update this order.';
        header('Location: recent-orders.php'); // Redirect to self
        exit();
    }
    error_log("recent-orders.php (POST handler): User is authorized to update this specific order.");


    // 4. Update the order status in the database
    $rowsAffected = $checkoutModel->updateOrderStatus($orderId, $newStatusId);
    error_log("recent-orders.php (POST handler): Attempting to update order status via updateOrderStatus(). Result: " . ($rowsAffected === false ? "false (PDOException caught in model)" : $rowsAffected . " rows affected."));

    if ($rowsAffected !== false && $rowsAffected > 0) {
        $_SESSION['message'] = 'Order status updated successfully!';
        error_log("recent-orders.php (POST handler): Order status updated successfully for order ID: " . $orderId);
    } else {
        $_SESSION['message'] = 'Failed to update order status or no changes were made.';
        error_log("recent-orders.php (POST handler): Failed to update order status for order ID: " . $orderId . ". Rows affected: " . ($rowsAffected === false ? "false" : $rowsAffected) . ".");
    }

    // IMPORTANT: Redirect after POST to prevent form re-submission on refresh
    header('Location: recent-orders.php');
    exit();
}
// --- END: Handle POST request for status update ---


$recentOrders = [];
$pageTitle = "";

// Fetch all possible order statuses for the dropdown
$orderStatuses = $checkoutModel->getOrderStatuses();
$statusMap = []; // Map status ID to status Name for easy lookup
foreach ($orderStatuses as $status) {
    $statusMap[$status['id']] = $status['name'];
}

// Conditional fetching of orders based on user type
if ($userType === 'Admin') {
    $recentOrders = $checkoutModel->getAllRecentOrders(50); // Admin gets all recent orders
    $pageTitle = "All Customer Orders";
} elseif ($userType === 'Seller') {
    $recentOrders = $checkoutModel->getRecentSellerOrders($currentUserId, 20); // Seller gets only their recent sales
    $pageTitle = "Recent Sales (Your Products)";
}

// Initialize NumberFormatter for currency display
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
}

// Fallback function for currency formatting if intl extension is not available
if (!function_exists('formatCurrencyFallback')) {
    function formatCurrencyFallback($amount) {
        return '₱' . number_format($amount, 2);
    }
}

// Group order details by order_id for display
$groupedOrders = [];
foreach ($recentOrders as $orderItem) {
    $orderId = $orderItem['order_id'];
    if (!isset($groupedOrders[$orderId])) {
        $groupedOrders[$orderId] = [
            'order_id' => $orderItem['order_id'],
            'order_date' => $orderItem['order_date'],
            'order_total' => $orderItem['order_total'],
            'current_status_name' => $orderItem['status_name'],
            'customer_name' => $orderItem['customer_name'],
            'customer_email' => $orderItem['customer_email'] ?? null,
            'customer_phone' => $orderItem['customer_phone'] ?? null,
            'seller_name' => $orderItem['seller_name'] ?? null,
            // THIS IS THE KEY LINE FOR DISPLAYING THE CORRECT STATUS IN THE DROPDOWN
            // Use the status_id directly from the fetched data, not array_search on name
            'current_status_id' => (int)($orderItem['status_id'] ?? 1) // Default to 1 (Pending) if not found, though it should be
        ];
    }
    // Add product item details to the 'items' array of the respective order
    $groupedOrders[$orderId]['items'][] = [
        'product_name' => $orderItem['product_name'],
        'product_image' => $orderItem['product_image'],
        'quantity' => $orderItem['quantity'],
        'item_price_at_order' => $orderItem['item_price_at_order'],
        'item_subtotal' => $orderItem['item_subtotal'],
        'seller_id' => $orderItem['seller_id'] ?? null
    ];
}

// NOW, AND ONLY NOW, INCLUDE THE HEADER AND START HTML OUTPUT
template('templates/header.php');
?>

<div class="container my-5">
    <h1 class="text-center mb-4"><?php echo htmlspecialchars($pageTitle); ?></h1>

    <?php
    // Display potential session messages
    if (isset($_SESSION['message'])) {
        $alertType = (strpos($_SESSION['message'], 'successfully') !== false || strpos($_SESSION['message'], 'Success') !== false) ? 'success' : 'danger';
        echo '<div class="alert alert-' . $alertType . ' text-center" role="alert">' . htmlspecialchars($_SESSION['message']) . '</div>';
        unset($_SESSION['message']); // Clear the message after displaying
    }
    ?>

    <?php if (empty($groupedOrders)): ?>
        <div class="alert alert-info text-center" role="alert">
            <?php echo ($userType === 'Admin') ? 'No customer orders found.' : 'No recent sales found for your account.'; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <?php foreach ($groupedOrders as $orderInfo): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
                        <div class="order-header-info">
                            <h5 class="mb-1">Order #<?php echo htmlspecialchars($orderInfo['order_id']); ?></h5>
                            <small class="text-muted">Date: <?php echo htmlspecialchars(Carbon::parse($orderInfo['order_date'])->format('M d, Y h:i A')); ?></small><br>
                            <span class="fw-bold">Customer: <?php echo htmlspecialchars($orderInfo['customer_name']); ?></span><br>
                            <?php if (!empty($orderInfo['customer_email'])): ?>
                                <small>Email: <?php echo htmlspecialchars($orderInfo['customer_email']); ?></small><br>
                            <?php endif; ?>
                            <?php if (!empty($orderInfo['customer_phone'])): ?>
                                <small>Phone: <?php echo htmlspecialchars($orderInfo['customer_phone']); ?></small><br>
                            <?php endif; ?>
                            <?php if ($userType === 'Admin' && !empty($orderInfo['seller_name'])): ?>
                                <small>Seller: <strong><?php echo htmlspecialchars($orderInfo['seller_name']); ?></strong></small>
                            <?php endif; ?>
                        </div>
                        <div class="order-status-total text-end mt-2 mt-md-0">
                            <h5 class="mb-1">
                                <small class="text-muted">Total:</small>
                                <strong>
                                    <?php
                                    if ($pesoFormatter) {
                                        echo htmlspecialchars($pesoFormatter->formatCurrency($orderInfo['order_total'] ?? 0, 'PHP'));
                                    } else {
                                        echo htmlspecialchars(formatCurrencyFallback($orderInfo['order_total'] ?? 0));
                                    }
                                    ?>
                                </strong>
                            </h5>

                            <?php
                            $canUpdateThisOrder = false;
                            if ($userType === 'Admin') {
                                $canUpdateThisOrder = true;
                            } elseif ($userType === 'Seller') {
                                foreach ($orderInfo['items'] as $item) {
                                    if (isset($item['seller_id']) && $item['seller_id'] == $currentUserId) {
                                        $canUpdateThisOrder = true;
                                        break;
                                    }
                                }
                            }
                            ?>

                            <?php if ($canUpdateThisOrder): ?>
                                <form action="recent-orders.php" method="POST" class="d-inline-block">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($orderInfo['order_id']); ?>">
                                    <div class="input-group input-group-sm">
                                        <label for="status-<?php echo $orderInfo['order_id']; ?>" class="input-group-text">Status</label>
                                        <select name="status_id" id="status-<?php echo $orderInfo['order_id']; ?>" class="form-select" onchange="this.form.submit()">
                                            <?php foreach ($orderStatuses as $statusOption): ?>
                                                <option value="<?php echo htmlspecialchars($statusOption['id']); ?>"
                                                    <?php
                                                    // Use current_status_id for selection
                                                    echo ($orderInfo['current_status_id'] == $statusOption['id']) ? 'selected' : '';
                                                    ?>>
                                                    <?php echo htmlspecialchars($statusOption['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" style="display:none;" aria-hidden="true">Submit</button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($orderInfo['current_status_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 70px;"></th>
                                    <th>Product</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderInfo['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($item['product_image'] ?? 'assets/img/placeholder.jpg'); ?>"
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="img-fluid rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td class="text-end"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td class="text-end">
                                            <?php
                                            echo $pesoFormatter ? $pesoFormatter->formatCurrency($item['item_price_at_order'] ?? 0, 'PHP') : formatCurrencyFallback($item['item_price_at_order'] ?? 0);
                                            ?>
                                        </td>
                                        <td class="text-end">
                                            <?php
                                            echo $pesoFormatter ? $pesoFormatter->formatCurrency($item['item_subtotal'] ?? 0, 'PHP') : formatCurrencyFallback($item['item_subtotal'] ?? 0);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold pt-2">Order Total:</td>
                                    <td class="text-end fw-bold pt-2">
                                        <?php
                                        echo $pesoFormatter ? $pesoFormatter->formatCurrency($orderInfo['order_total'] ?? 0, 'PHP') : formatCurrencyFallback($orderInfo['order_total'] ?? 0);
                                        ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php template('templates/footer.php'); ?>