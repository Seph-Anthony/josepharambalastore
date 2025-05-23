<?php
// admin/dashboard.php

// THIS IS THE ABSOLUTE FIRST LINE OF CODE in admin/dashboard.php
require_once __DIR__ . '/../vendor/autoload.php'; // Go up to root for autoloader
require_once __DIR__ . '/../helpers/functions.php'; // Go up to root for functions

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Basic access control: Only allow Admins to view this page
if (!isset($_SESSION['user']) || ($_SESSION['user']['user_type_name'] ?? '') !== 'Admin') {
    // Redirect to login or home if not an admin
    $_SESSION['message'] = 'Access denied. You must be an Admin to view this page.';
    header('Location: /login.php'); // Adjust path as needed for your project structure
    exit();
}

// You can fetch admin-specific data here later
// use Aries\MiniFrameworkStore\Models\User; // Example if you need user data

template('../templates/header.php'); // Go up to root, then into templates for header
?>

<div class="container my-5">
    <h1>Admin Dashboard</h1>
    <p class="lead">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> (Admin)!</p>

    <div class="row mt-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Manage Users</h5>
                    <p class="card-text">Add, edit, or delete user accounts.</p>
                    <a href="manage-users.php" class="btn btn-primary">Go to Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">View Orders</h5>
                    <p class="card-text">Review and process all customer orders.</p>
                    <a href="view-orders.php" class="btn btn-info">Go to Orders</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Manage Categories</h5>
                    <p class="card-text">Add, edit, or delete product categories.</p>
                    <a href="manage-categories.php" class="btn btn-success">Go to Categories</a>
                </div>
            </div>
        </div>
        </div>

</div>

<?php template('../templates/footer.php'); // Go up to root, then into templates for footer ?>