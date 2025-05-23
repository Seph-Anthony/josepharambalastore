<?php
// Ensure session is started if not already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define user type/role for easier checks
// Assuming 'user_type_name' will be 'Customer', 'Seller', or 'Admin' (case might vary)
// Default to 'guest' or 'customer' if not logged in or type not set, for easier comparison.
$currentUserType = strtolower($_SESSION['user']['user_type_name'] ?? 'customer');
$isLoggedIn = isset($_SESSION['user']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arambala Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">

    <style>
        /* Style for product thumbnails in order tables */
        .product-thumb {
            width: 50px; /* Adjust as needed */
            height: 50px; /* Adjust as needed */
            object-fit: cover; /* Ensures the image fills the container without distortion */
            border-radius: 5px; /* Optional: Add rounded corners */
            border: 1px solid #ddd; /* Optional: Add a light border */
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    </head>
<body>

<header class="py-3 mb-4 border-bottom bg-white shadow-sm">
    <div class="container-fluid d-flex flex-wrap align-items-center justify-content-center justify-content-lg-between">

        <a href="index.php" class="d-flex align-items-center mb-2 mb-lg-0 text-dark text-decoration-none">
            <h4 class="fw-bold m-0 me-2">SEAMLESS SHOPPING</h4>
        </a>

        <div class="flex-grow-1 mx-lg-5 order-lg-2 order-3 w-100 w-lg-auto mt-2 mt-lg-0">
            <form class="d-flex" role="search" action="search.php" method="GET">
                <input class="form-control form-control-lg me-2 rounded-pill" type="search" name="q" placeholder="Search over 50,000 products..." aria-label="Search">
                <button class="btn btn-outline-secondary rounded-circle" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div class="d-flex align-items-center order-lg-3 order-2 ms-auto ms-lg-0">
            <?php if ($isLoggedIn): ?>
                <div class="dropdown me-3">
                    <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle fa-lg me-1"></i> Hello, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                    </a>
                    <ul class="dropdown-menu text-small shadow" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="my-account.php">My Account</a></li>

                        <?php
                        // "My Orders" for all logged-in users who are NOT 'admin'
                        if ($currentUserType !== 'admin'):
                            ?>
                            <li><a class="dropdown-item" href="customer-orders.php">My Orders</a></li>
                        <?php endif; ?>

                        <?php
                        // Seller-specific links
                        if ($currentUserType === 'seller'):
                            ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="add-product.php">Add Product</a></li>
                            <li><a class="dropdown-item" href="seller-dashboard.php">Seller Dashboard</a></li>
                        <?php endif; ?>

                        <?php
                        // Admin-specific links (only "Admin Dashboard" for Admin)
                        if ($currentUserType === 'admin'):
                            ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="seller-dashboard.php">Admin Dashboard</a></li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-link text-decoration-none text-dark me-2">Login / Register</a>
            <?php endif; ?>

            <a href="cart.php" class="btn btn-link text-decoration-none text-dark position-relative">
                <i class="fas fa-shopping-cart fa-lg"></i>
                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" id="cart-count">
                    <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                </span>
            </a>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light w-100 order-lg-1 order-1">
            <button class="navbar-toggler mx-auto" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <li><a class="dropdown-item" href="category.php">All Products</a></li>
                            <?php
                            // $categories variable is expected to be passed to the template() function
                            // Ensure $categories is defined (e.g., fetched from DB) before this header is included.
                            if (isset($categories) && is_array($categories)):
                                foreach ($categories as $category): ?>
                                    <li><a class="dropdown-item" href="category.php?id=<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                                <?php endforeach;
                            endif;
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

    </div>
</header>