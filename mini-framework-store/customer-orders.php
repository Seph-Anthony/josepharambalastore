<?php
// customer-orders.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

use Aries\MiniFrameworkStore\Models\Checkout;
use Carbon\Carbon;

// --- IMPORTANT: AUTHENTICATION AND REDIRECTS MUST BE BEFORE ANY HTML OUTPUT ---
// 1. Check if the user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = 'Please login to view your order history.';
    header('Location: login.php');
    exit();
}

// MODIFIED: No longer restricting access based on user type.
// All logged-in users (Customer, Seller, Admin) should be able to view their *personal* orders.
$userType = strtolower($_SESSION['user']['user_type_name'] ?? ''); // Get user type and make it lowercase for comparison
$currentUserId = $_SESSION['user']['id'] ?? null;

// Ensure currentUserId is valid before proceeding
if (!$currentUserId) {
    $_SESSION['message'] = 'User ID not found. Please login again.';
    header('Location: login.php');
    exit();
}

$checkoutModel = new Checkout();

// Fetch orders for the current logged-in user (customer, seller, or admin)
// This is the crucial part: getOrdersByUserId fetches orders for the specific user ID.
$customerOrders = $checkoutModel->getOrdersByUserId($currentUserId); 

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
foreach ($customerOrders as $orderItem) {
    $orderId = $orderItem['order_id'];
    if (!isset($groupedOrders[$orderId])) {
        $groupedOrders[$orderId] = [
            'order_id' => $orderItem['order_id'],
            'order_date' => $orderItem['order_date'],
            'order_total' => $orderItem['order_total'], // Assuming order_total is part of the query result for the main order
            'current_status_name' => $orderItem['status_name']
        ];
    }
    // Add product item details to the 'items' array of the respective order
    $groupedOrders[$orderId]['items'][] = [
        'product_name' => $orderItem['product_name'],
        'product_image' => $orderItem['product_image'],
        'quantity' => $orderItem['quantity'],
        'item_price_at_order' => $orderItem['item_price_at_order'],
        'item_subtotal' => $orderItem['item_subtotal']
    ];
}


// NOW, AND ONLY NOW, INCLUDE THE HEADER AND START HTML OUTPUT
template('templates/header.php');
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card p-4 shadow-sm rounded-0 border-0 text-center">
                <div class="profile-avatar mb-3">
                    <i class="fas fa-user-circle fa-5x text-muted"></i>
                </div>
                <h5 class="mb-1 profile-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
                <p class="text-muted mb-3 logged-in-as">Logged in as a <span class="badge bg-primary profile-role"><?php echo htmlspecialchars($_SESSION['user']['user_type_name']); ?></span></p>

                <ul class="list-group list-group-flush account-sidebar-nav">
                    <li class="list-group-item border-0 px-0">
                        <a href="my-account.php" class="account-sidebar-link">
                            <i class="fas fa-user-gear me-2"></i> Account Details
                        </a>
                    </li>
                    <li class="list-group-item border-0 px-0">
                        <a href="customer-orders.php" class="account-sidebar-link active"> <i class="fas fa-box me-2"></i> My Orders
                        </a>
                    </li>
                    <?php if ($userType === 'seller'): ?>
                        <li class="list-group-item border-0 px-0">
                            <a href="seller-dashboard.php" class="account-sidebar-link">
                                <i class="fas fa-chart-line me-2"></i> Seller Dashboard
                            </a>
                        </li>
                        <li class="list-group-item border-0 px-0">
                            <a href="add-product.php" class="account-sidebar-link">
                                <i class="fas fa-plus-circle me-2"></i> Add Product
                            </a>
                        </li>
                    <?php elseif ($userType === 'admin'): ?>
                        <li class="list-group-item border-0 px-0">
                            <a href="seller-dashboard.php" class="account-sidebar-link">
                                <i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="list-group-item border-0 px-0 mt-3">
                        <a href="logout.php" class="account-sidebar-link text-danger logout-link">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <h1 class="mb-4 fw-bold text-dark">My Purchase History</h1>

            <?php
            // Display potential session messages
            if (isset($_SESSION['message'])) {
                $alertType = (strpos($_SESSION['message'], 'successfully') !== false || strpos($_SESSION['message'], 'Success') !== false) ? 'success' : 'danger';
                echo '<div class="alert alert-' . $alertType . ' text-center mb-4" role="alert">' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']); // Clear the message after displaying
            }
            ?>

            <?php if (empty($groupedOrders)): ?>
                <div class="empty-orders-message text-center p-5 border rounded bg-light shadow-sm">
                    <i class="fas fa-box-open fa-5x text-muted mb-4"></i>
                    <h3 class="mb-3">No orders found!</h3>
                    <p class="lead">It looks like you haven't placed any orders yet. Start exploring our products!</p>
                    <a href="index.php" class="btn btn-primary btn-lg mt-3">Start Shopping Now</a>
                </div>
            <?php else: ?>
                <div class="row justify-content-center">
                    <div class="col-lg-12"> <?php foreach ($groupedOrders as $orderInfo): ?>
                            <div class="card mb-4 border-0 shadow-sm order-card">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0 flex-wrap">
                                    <div class="order-header-info">
                                        <h5 class="mb-1 text-dark">Order #<span class="fw-bold"><?php echo htmlspecialchars($orderInfo['order_id']); ?></span></h5>
                                        <small class="text-muted">Placed on: <?php echo htmlspecialchars(Carbon::parse($orderInfo['order_date'])->format('M d, Y h:i A')); ?></small>
                                    </div>
                                    <div class="order-status-total text-end mt-2 mt-md-0">
                                        <h5 class="mb-1">
                                            <small class="text-muted">Total:</small>
                                            <span class="fw-bold text-primary fs-5">
                                                <?php
                                                if ($pesoFormatter) {
                                                    echo htmlspecialchars($pesoFormatter->formatCurrency($orderInfo['order_total'] ?? 0, 'PHP'));
                                                } else {
                                                    echo htmlspecialchars(formatCurrencyFallback($orderInfo['order_total'] ?? 0));
                                                }
                                                ?>
                                            </span>
                                        </h5>
                                        <?php
                                        $statusClass = 'text-muted';
                                        switch (strtolower($orderInfo['current_status_name'])) {
                                            case 'pending':
                                                $statusClass = 'badge bg-warning text-dark';
                                                break;
                                            case 'processing':
                                                $statusClass = 'badge bg-info text-dark';
                                                break;
                                            case 'shipped':
                                                $statusClass = 'badge bg-primary';
                                                break;
                                            case 'delivered':
                                                $statusClass = 'badge bg-success';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'badge bg-danger';
                                                break;
                                            default:
                                                $statusClass = 'badge bg-secondary';
                                                break;
                                        }
                                        ?>
                                        <span class="<?php echo $statusClass; ?> fs-6 py-1 px-2 rounded-pill"><?php echo htmlspecialchars($orderInfo['current_status_name']); ?></span>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($orderInfo['items'] as $item): ?>
                                            <li class="list-group-item d-flex align-items-center py-3 px-4">
                                                <div class="flex-shrink-0 me-3">
                                                    <img src="<?php echo htmlspecialchars($item['product_image'] ?? 'assets/img/placeholder.jpg'); ?>"
                                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                        class="img-fluid rounded-2" style="width: 70px; height: 70px; object-fit: cover; border: 1px solid #eee;">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-normal text-dark"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                    <small class="text-muted">Qty: <?php echo htmlspecialchars($item['quantity']); ?> &times;
                                                        <?php
                                                        echo $pesoFormatter ? $pesoFormatter->formatCurrency($item['item_price_at_order'] ?? 0, 'PHP') : formatCurrencyFallback($item['item_price_at_order'] ?? 0);
                                                        ?>
                                                    </small>
                                                </div>
                                                <div class="text-end fw-bold text-dark">
                                                    <?php
                                                    echo $pesoFormatter ? $pesoFormatter->formatCurrency($item['item_subtotal'] ?? 0, 'PHP') : formatCurrencyFallback($item['item_subtotal'] ?? 0);
                                                    ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="card-footer bg-white text-end py-3 border-top-0">
                                    <h5 class="mb-0 text-dark">
                                        <small class="text-muted">Order Total:</small>
                                        <span class="fw-bold text-primary fs-5">
                                            <?php
                                            echo $pesoFormatter ? $pesoFormatter->formatCurrency($orderInfo['order_total'] ?? 0, 'PHP') : formatCurrencyFallback($orderInfo['order_total'] ?? 0);
                                            ?>
                                        </span>
                                    </h5>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php template('templates/footer.php'); ?>

<style>
    /* Custom CSS for Zalora-like minimalist design */
    body {
        /* Previous: background-color: #f8f9fa; */
        /* New: Very subtle linear gradient for a lively feel */
        background: linear-gradient(135deg, #f0f2f5 0%, #e0e4eb 100%);
        min-height: 100vh; /* Ensure gradient covers full viewport height */
    }

    .container {
        max-width: 1100px; /* Max width for content */
    }

    .cart-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #343a40;
    }

    .order-card {
        border-radius: 0.75rem; /* Slightly more rounded corners */
        overflow: hidden; /* Ensures img borders are clipped */
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .order-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08) !important; /* Slightly stronger shadow on hover */
    }

    .card-header {
        background-color: #ffffff !important; /* Pure white header */
        border-bottom: 1px solid #eaeaea !important; /* Lighter border */
    }

    .order-header-info h5 {
        font-size: 1.35rem;
    }

    .order-header-info small {
        font-size: 0.85rem;
    }

    .order-status-total h5 {
        font-size: 1.35rem;
    }

    .order-status-total .badge {
        font-size: 0.9rem !important; /* Slightly larger badge text */
        padding: 0.4em 0.8em;
        font-weight: 600;
        letter-spacing: 0.5px;
        min-width: 80px; /* Give badges a consistent minimum width */
        text-align: center;
    }

    /* Specific badge colors for better visual distinction */
    .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }
    .badge.bg-info {
        background-color: #17a2b8 !important; /* Bootstrap's info color */
        color: #fff !important;
    }
    .badge.bg-primary {
        background-color: #0d6efd !important; /* Bootstrap's primary color */
        color: #fff !important;
    }
    .badge.bg-success {
        background-color: #28a745 !important; /* Bootstrap's success color */
        color: #fff !important;
    }
    .badge.bg-danger {
        background-color: #dc3545 !important; /* Bootstrap's danger color */
        color: #fff !important;
    }
    .badge.bg-secondary {
        background-color: #6c757d !important; /* Bootstrap's secondary color */
        color: #fff !important;
    }


    .list-group-item {
        border-color: #eee; /* Lighter borders for list items */
    }

    .list-group-flush .list-group-item:last-child {
        border-bottom: none; /* Remove last border */
    }

    .list-group-item img {
        border: 1px solid #e9ecef; /* Subtle border around product images */
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* Very subtle shadow on images */
    }

    .card-footer {
        background-color: #ffffff !important; /* Pure white footer */
        border-top: 1px solid #eaeaea !important; /* Lighter border */
    }

    .empty-orders-message {
        border: 1px dashed #ced4da !important; /* Dashed border for empty state */
        background-color: #fdfdfd !important;
        color: #6c757d;
        border-radius: 0.75rem;
    }

    .empty-orders-message i {
        color: #adb5bd !important;
    }

    .btn-primary {
        background-color: #007bff; /* Standard Bootstrap primary, can be customized */
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }
</style>