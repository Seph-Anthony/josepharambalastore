<?php
// login.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the User model is used
use Aries\MiniFrameworkStore\Models\User;
use Aries\MiniFrameworkStore\Models\Category; // Also need to use the Category model for the header

$userModel = new User(); // Instantiate the User model
$errorMessage = ''; // Use $errorMessage for consistency with previous examples

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: my-account.php');
    exit;
}

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']); // Trim whitespace
    $password = $_POST['password'];

    // Basic server-side validation
    if (empty($email) || empty($password)) {
        $errorMessage = 'Please enter both email and password.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        // Assuming your User model's login method is `authenticate` or `getByEmail`
        // and then you verify the password. Let's adjust to match common practice.
        // If your User::login() method already performs password verification and returns user info
        // upon success, then the original logic is fine.
        // Assuming `authenticate` as per my previous suggested User model structure
        $user_info = $userModel->authenticate($email, $password);

        if ($user_info) { // If authentication is successful
            // Fetch user type name to store in session
            // Assuming your authenticate method already returns user_type_name
            $_SESSION['user'] = [
                'id' => $user_info['id'],
                'name' => $user_info['name'],
                'email' => $user_info['email'],
                'user_type_id' => $user_info['user_type_id'],
                'user_type_name' => $user_info['user_type_name'] ?? 'Customer' // Default to Customer if not set
            ];
            $_SESSION['message'] = 'You have been successfully logged in!';
            header('Location: index.php'); // Redirect to home page
            exit;
        } else {
            $errorMessage = 'Invalid email or password.';
        }
    }
}

// Fetch categories for the header navigation
$categoryModel = new Category();
$categories = $categoryModel->getAll();

// Pass categories data to the header template
template('templates/header.php', ['categories' => $categories]);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-lg mt-5">
                <div class="card-header bg-white text-center py-4 border-bottom-0">
                    <h3 class="fw-bold my-0">Login to Your Account</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger text-center" role="alert">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input name="email" type="email" class="form-control form-control-lg" id="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <div class="form-text text-muted small mt-1">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input name="password" type="password" class="form-control form-control-lg" id="password" placeholder="Password" required>
                        </div>
                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="rememberMeCheckbox">
                            <label class="form-check-label" for="rememberMeCheckbox">Remember me</label>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg rounded-pill">Login</button>
                        </div>
                        <div class="text-center">
                            <a href="#" class="text-decoration-none small text-muted">Forgot Password?</a>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3 bg-light border-top-0">
                    <div class="small">
                        Don't have an account? <a href="register.php" class="text-decoration-none fw-bold">Register here.</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
template('templates/footer.php');
?>