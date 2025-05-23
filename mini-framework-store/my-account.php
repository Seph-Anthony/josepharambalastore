<?php
// my-account.php (Corrected to remove Admin Dashboard from sidebar for Admin)

// THIS IS THE ABSOLUTE FIRST LINE OF CODE IN MY-ACCOUNT.PHP.
// NO SPACES, NO NEWLINES, NO HTML ABOVE THIS.
require_once __DIR__ . '/vendor/autoload.php'; // Load Composer's autoloader FIRST
require_once __DIR__ . '/helpers/functions.php'; // Use require_once with absolute path

if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start session early
}

use Aries\MiniFrameworkStore\Models\User;
use Carbon\Carbon;

// --- IMPORTANT: AUTHENTICATION AND REDIRECTS MUST BE BEFORE ANY HTML OUTPUT ---
// 1. Check if the user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = 'You must be logged in to access your account.'; // Optional: message for user
    header('Location: login.php'); // Redirect to login page if not logged in
    exit(); // IMPORTANT: Always exit after a header redirect
}

// 2. Any POST processing logic for my-account.php (e.g., updating profile)
// This also must come BEFORE any HTML output
$userModel = new User(); // Instantiate model after authentication check
if(isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $birthdate = $_POST['birthdate'] ?? null;

    // Update user details in the database
    $updateSuccess = $userModel->update([
        'id' => $_SESSION['user']['id'],
        'name' => $name,
        'email' => $email,
        'address' => $address,
        'phone' => $phone,
        'birthdate' => !empty($birthdate) ? Carbon::createFromFormat('Y-m-d', $birthdate)->format('Y-m-d') : null
    ]);

    // After updating in DB, refresh session data to reflect changes immediately
    if ($updateSuccess) {
        $updatedUserInfo = $userModel->getById($_SESSION['user']['id']);
        if ($updatedUserInfo) {
            $_SESSION['user'] = $updatedUserInfo; // Replace session user data with fresh data
            $_SESSION['message'] = 'Account details updated successfully!'; // Success message
            // header('Location: my-account.php'); // Optional: Redirect to prevent form re-submission on refresh
            // exit();
        } else {
            $_SESSION['message'] = 'Failed to retrieve updated user info.'; // Error message
        }
    } else {
        $_SESSION['message'] = 'Failed to update account details. Please try again.'; // Error message
    }
}

// NOW, AND ONLY NOW, INCLUDE THE HEADER AND START HTML OUTPUT
template('templates/header.php'); // Corrected template path

?>

<div class="container my-5">
    <h1 class="text-center mb-5 fw-bold text-dark">My Account</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <?php
            $alertType = 'info';
            if (strpos($_SESSION['message'], 'successfully') !== false || strpos($_SESSION['message'], 'Success') !== false) {
                $alertType = 'success';
            } elseif (strpos($_SESSION['message'], 'Failed') !== false || strpos($_SESSION['message'], 'Access denied') !== false) {
                $alertType = 'danger';
            }
        ?>
        <div class="alert alert-<?php echo $alertType; ?> text-center mb-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php unset($_SESSION['message']); // Clear the message after display ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-4 mb-4">
            <div class="card p-4 shadow-sm border-0 account-sidebar">
                <div class="text-center mb-4">
                    <i class="fas fa-user-circle fa-5x text-muted mb-3"></i>
                    <h4 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h4>
                    <?php if (isset($_SESSION['user']['user_type_name'])): ?>
                        <p class="small text-muted mb-0">Logged in as: <span class="badge bg-primary fs-6 fw-normal"><?php echo htmlspecialchars($_SESSION['user']['user_type_name']); ?></span></p>
                    <?php endif; ?>
                </div>

                <hr class="my-3">

                <div class="list-group list-group-flush account-navigation">
                    <a href="my-account.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-id-card me-2"></i> Account Details
                    </a>
                    <?php
                    // Allow 'My Orders' for both Customer and Seller in the sidebar
                    $sidebarUserType = strtolower($_SESSION['user']['user_type_name'] ?? '');
                    if ($sidebarUserType === 'customer' || $sidebarUserType === 'seller'):
                        ?>
                        <a href="customer-orders.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-receipt me-2"></i> My Orders
                        </a>
                    <?php endif; ?>

                    <?php
                    // ONLY show "Seller Dashboard" if the user is explicitly a 'Seller'.
                    // This will hide it from 'Admin' users.
                    if (isset($_SESSION['user']['user_type_name']) && $_SESSION['user']['user_type_name'] === 'Seller'):
                    ?>
                        <a href="seller-dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-store me-2"></i> Seller Dashboard
                        </a>
                    <?php endif; ?>

                    <?php
                    // Add Product link (only for Sellers)
                    if (isset($_SESSION['user']['user_type_name']) && $_SESSION['user']['user_type_name'] === 'Seller'):
                    ?>
                        <a href="add-product.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Add Product
                        </a>
                    <?php endif; ?>

                    <?php
                    // No "Admin Dashboard" link if user is Admin, as this is 'My Account' page
                    // and 'seller-dashboard.php' effectively serves as the Admin Dashboard.
                    // The main dashboard link will be in the header dropdown only.
                    ?>

                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card p-4 shadow-sm border-0 account-details-form">
                <h2 class="mb-4 text-dark fw-bold border-bottom pb-3">Edit Account Details</h2>
                <form action="my-account.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_SESSION['user']['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label fw-semibold">Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($_SESSION['user']['address'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_SESSION['user']['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="birthdate" class="form-label fw-semibold">Birthdate</label>
                        <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($_SESSION['user']['birthdate'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary mt-3 w-100 py-2 fw-bold" name="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php template('templates/footer.php'); // Corrected template path ?>

<style>
    /* Global Background from customer-orders.php */
    body {
        background: linear-gradient(135deg, #f0f2f5 0%, #e0e4eb 100%);
        min-height: 100vh;
    }

    .container {
        max-width: 1100px;
    }

    /* Account Page Specific Styles */
    .account-sidebar, .account-details-form {
        border-radius: 0.75rem; /* Consistent with order cards */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); /* Subtle shadow */
        background-color: #ffffff; /* White background for cards */
        transition: all 0.2s ease-in-out;
    }

    .account-sidebar:hover, .account-details-form:hover {
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08); /* Slightly stronger shadow on hover */
    }

    .account-sidebar i {
        color: #adb5bd; /* Muted icon color */
    }

    .account-navigation .list-group-item {
        border: none; /* Remove default list group item borders */
        padding: 0.75rem 1rem;
        font-weight: 500;
        color: #495057; /* Darker text for navigation items */
        transition: background-color 0.2s ease, color 0.2s ease;
        border-radius: 0.5rem; /* Rounded corners for navigation items */
        margin-bottom: 0.25rem; /* Small gap between items */
    }

    .account-navigation .list-group-item i {
        font-size: 1.1rem; /* Slightly larger icons */
    }

    .account-navigation .list-group-item:hover {
        background-color: #f0f2f5; /* Lighter hover background */
        color: #007bff; /* Primary color on hover */
    }

    .account-navigation .list-group-item.active {
        background-color: #007bff; /* Primary color for active state */
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 0.2rem 0.5rem rgba(0, 123, 255, 0.2); /* Subtle shadow for active item */
    }

    .account-navigation .list-group-item.active i {
        color: #ffffff;
    }

    .account-navigation .list-group-item.text-danger:hover {
        background-color: #dc3545; /* Red hover for logout */
        color: #ffffff !important;
    }

    .account-details-form .form-label {
        color: #343a40; /* Darker label color */
        font-size: 0.95rem; /* Slightly larger labels */
    }

    .account-details-form .form-control {
        border-radius: 0.5rem; /* Rounded input fields */
        border: 1px solid #ced4da;
        padding: 0.75rem 1rem;
        box-shadow: inset 0 1px 2px rgba(0,0,0,.03); /* Subtle inner shadow */
    }

    .account-details-form .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); /* Bootstrap focus glow */
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.1s ease;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        transform: translateY(-1px); /* Slight lift on hover */
    }

    .badge.bg-primary {
        background-color: #007bff !important;
        color: #fff !important;
        font-size: 0.85rem !important;
        padding: 0.35em 0.7em;
        border-radius: 0.35rem;
    }

    /* Alert messages styling */
    .alert {
        border-radius: 0.75rem;
        font-weight: 500;
        border: none; /* Remove default alert border */
        box-shadow: 0 0.2rem 0.5rem rgba(0, 0, 0, 0.05);
    }
    .alert-success { background-color: #d4edda; color: #155724; }
    .alert-danger { background-color: #f8d7da; color: #721c24; }
    .alert-info { background-color: #d1ecf1; color: #0c5460; }

</style>