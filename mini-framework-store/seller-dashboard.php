<?php
// seller-dashboard.php (Revamped with sidebar and styled content, corrected Admin/Seller links)

require_once __DIR__ . '/vendor/autoload.php';
include 'helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// MODIFIED: Allow both Seller and Admin to access this page
if (!isset($_SESSION['user']['user_type_name']) || ($_SESSION['user']['user_type_name'] !== 'Seller' && $_SESSION['user']['user_type_name'] !== 'Admin')) {
    header('Location: my-account.php'); // Redirect if not Seller or Admin
    exit();
}

use Aries\MiniFrameworkStore\Models\Checkout;
use Carbon\Carbon; // Ensure Carbon is available if needed for date formatting

$userId = $_SESSION['user']['id'];
$userType = strtolower($_SESSION['user']['user_type_name']); // Make sure to use lowercase for consistent comparison

$checkoutModel = new Checkout();
$recentOrders = [];

// Fetch orders based on user type
if ($userType === 'seller') {
    $recentOrders = $checkoutModel->getRecentSellerOrders($userId, 20); // Get orders for this specific seller's products
} elseif ($userType === 'admin') {
    $recentOrders = $checkoutModel->getAllRecentOrders(20); // Get all orders for admin
}


// Ensure NumberFormatter is available
if (!class_exists('NumberFormatter')) {
    // Fallback if intl extension is not enabled (though it should be for a live server)
    function format_currency_fallback($amount, $currency = 'PHP') {
        return '₱' . number_format($amount, 2);
    }
    $pesoFormatter = null; // Mark as null to use fallback
} else {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
}

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
                    <?php
                    // "My Orders" is now available for all logged-in users (customer, seller, admin)
                    // It will show their personal purchase history.
                    ?>
                    <li class="list-group-item border-0 px-0">
                        <a href="customer-orders.php" class="account-sidebar-link">
                            <i class="fas fa-box me-2"></i> My Orders
                        </a>
                    </li>

                    <?php
                    // Show "Seller Dashboard" for Sellers
                    if ($userType === 'seller'):
                    ?>
                        <li class="list-group-item border-0 px-0">
                            <a href="seller-dashboard.php" class="account-sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'seller-dashboard.php') ? 'active' : ''; ?>">
                                <i class="fas fa-chart-line me-2"></i> Seller Dashboard
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    // Show "Add Product" link only for Sellers
                    if ($userType === 'seller'):
                    ?>
                        <li class="list-group-item border-0 px-0">
                            <a href="add-product.php" class="account-sidebar-link">
                                <i class="fas fa-plus-circle me-2"></i> Add Product
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    // Show "Admin Dashboard" link for Admins
                    if ($userType === 'admin'):
                    ?>
                        <li class="list-group-item border-0 px-0">
                            <a href="seller-dashboard.php" class="account-sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'seller-dashboard.php') ? 'active' : ''; ?>">
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
            <div class="card p-5 shadow-sm rounded-0 border-0 main-content-card">
                <h3 class="mb-4 section-sub-title">
                    <?php echo ($userType === 'admin') ? 'All Recent Orders' : 'Recent Orders for Your Products'; ?>
                </h3>

                <?php if (empty($recentOrders)): ?>
                    <div class="alert alert-info" role="alert">
                        No recent orders found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover product-orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $orderItem): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($orderItem['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars(Carbon::parse($orderItem['order_date'])->format('M d, Y H:i')); ?></td>
                                        <td><?php echo htmlspecialchars($orderItem['customer_name']); ?></td>
                                        <td class="product-info-cell">
                                            <img src="<?php echo htmlspecialchars($orderItem['product_image']); ?>" alt="<?php echo htmlspecialchars($orderItem['product_name']); ?>" class="product-thumb me-2">
                                            <span><?php echo htmlspecialchars($orderItem['product_name']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($orderItem['quantity']); ?></td>
                                        <td>
                                            <?php
                                            if ($pesoFormatter) {
                                                echo $pesoFormatter->formatCurrency($orderItem['item_price_at_order'], 'PHP');
                                            } else {
                                                echo format_currency_fallback($orderItem['item_price_at_order']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($pesoFormatter) {
                                                echo $pesoFormatter->formatCurrency($orderItem['item_subtotal'], 'PHP');
                                            } else {
                                                echo format_currency_fallback($orderItem['item_subtotal']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Access the 'status_name' key from the $orderItem array
                                            $status = htmlspecialchars($orderItem['status_name'] ?? 'N/A');
                                            $badgeClass = '';
                                            switch ($status) {
                                                case 'Pending':
                                                    $badgeClass = 'bg-warning text-dark';
                                                    break;
                                                case 'Delivered':
                                                    $badgeClass = 'bg-success';
                                                    break;
                                                case 'Processing':
                                                    $badgeClass = 'bg-info';
                                                    break;
                                                case 'Cancelled':
                                                    $badgeClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $badgeClass = 'bg-secondary';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge rounded-pill <?php echo $badgeClass; ?> status-badge"><?php echo $status; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php template('templates/footer.php'); ?>