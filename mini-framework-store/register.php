<?php
// register.php

// 1. Load Composer's autoloader FIRST. This is crucial for namespaces to work.
require_once __DIR__ . '/vendor/autoload.php';

// 2. Then load your helper functions
require_once __DIR__ . '/helpers/functions.php';

// 3. Start the session AFTER autoload and helper functions
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 4. Use the necessary classes
use Aries\MiniFrameworkStore\Models\User;
use Aries\MiniFrameworkStore\Models\Category; // Also need for the header
use Carbon\Carbon; // For timestamps

// --- IMPORTANT: REDIRECTS MUST BE BEFORE ANY HTML OUTPUT ---
// Redirect if already logged in (using `$_SESSION['user']` from updated login)
if(isLoggedIn()) {
    header('Location: my-account.php'); // Redirect to my-account.php if logged in
    exit;
}

$userModel = new User(); // Instantiate User model (renamed $user to $userModel for consistency)

$registration_message = ''; // Initialize message variable

// Fetch user types for the dropdown
$userTypes = [];
try {
    // Assuming User model has a public method like getUserTypes()
    // If not, this direct DB query using getConnection() is a fallback.
    // It's better to add this to your User model if it doesn't exist:
    // public function getUserTypes() {
    //     $stmt = $this->getConnection()->query("SELECT id, name FROM user_types");
    //     return $stmt->fetchAll(PDO::FETCH_ASSOC); // PDO::FETCH_ASSOC is implicitly available
    // }
    $stmt = $userModel->getConnection()->query("SELECT id, name FROM user_types");
    $userTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching user types: " . $e->getMessage());
    // Handle error gracefully, maybe set a default or display an error message
    // Fallback to customer if types can't be loaded, assuming Customer is ID 1.
    // Make sure this fallback ID matches your 'Customer' user_type ID in the DB.
    $userTypes = [['id' => 1, 'name' => 'Customer']];
    $registration_message = '<div class="alert alert-warning text-center small" role="alert">Could not load user types. Defaulting to Customer registration.</div>';
}


if(isset($_POST['submit'])) {
    // Get the selected user type ID from the form
    $selectedUserTypeId = isset($_POST['user_type']) ? intval($_POST['user_type']) : null;

    // Basic validation for user type
    $isValidUserType = false;
    foreach ($userTypes as $type) {
        if ($type['id'] === $selectedUserTypeId) {
            $isValidUserType = true;
            break;
        }
    }

    $name = trim($_POST['full-name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');

    // Server-side validation
    if (empty($name) || empty($email) || empty($password) || $selectedUserTypeId === null || !$isValidUserType) {
        $registration_message = '<div class="alert alert-danger text-center" role="alert">Name, Email, Password, and User Type are required.</div>';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_message = '<div class="alert alert-danger text-center" role="alert">Please enter a valid email address.</div>';
    } else if (strlen($password) < 6) { // Example: minimum password length
        $registration_message = '<div class="alert alert-danger text-center" role="alert">Password must be at least 6 characters long.</div>';
    }
    // Add more validation for phone, birthdate format if needed
    else {
        $data_to_register = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT), // IMPORTANT: Hash the password!
            'address' => $address,
            'phone' => $phone,
            'birthdate' => !empty($birthdate) ? $birthdate : null, // Store null if empty, or '' based on your DB schema
            'user_type_id' => $selectedUserTypeId,
            'created_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s')
        ];

        try {
            $registeredId = $userModel->register($data_to_register); // Use $userModel

            if ($registeredId) {
                // Set a session message for successful registration
                $_SESSION['message'] = 'You have successfully registered! You may now login.';
                header('Location: login.php'); // Redirect to login page
                exit;
            } else {
                $registration_message = '<div class="alert alert-danger text-center" role="alert">Registration failed. Please try again.</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation (e.g., duplicate email)
                $registration_message = '<div class="alert alert-danger text-center" role="alert">The email address is already registered. Please use a different email.</div>';
            } else {
                error_log("Registration error: " . $e->getMessage());
                $registration_message = '<div class="alert alert-danger text-center" role="alert">An error occurred during registration. Please try again.</div>';
            }
        }
    }
}

// Fetch categories for the header navigation
$categoryModel = new Category();
$categories = $categoryModel->getAll();

// NOW, AND ONLY NOW, INCLUDE THE HEADER AND START HTML OUTPUT
template('templates/header.php', ['categories' => $categories]);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7"> <div class="card shadow-lg border-0 rounded-lg mt-5 mb-5"> <div class="card-header bg-white text-center py-4 border-bottom-0">
                    <h3 class="fw-bold my-0">Create Your Account</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($registration_message): ?>
                        <?php echo $registration_message; ?>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="full-name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input name="full-name" type="text" class="form-control form-control-lg" id="full-name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($_POST['full-name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control form-control-lg" id="email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <div class="form-text text-muted small mt-1">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input name="password" type="password" class="form-control form-control-lg" id="password" placeholder="Minimum 6 characters" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input name="address" type="text" class="form-control form-control-lg" id="address" placeholder="Your full address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input name="phone" type="text" class="form-control form-control-lg" id="phone" placeholder="e.g., 09123456789" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-4"> <label for="birthdate" class="form-label">Birthdate</label>
                            <input name="birthdate" type="date" class="form-control form-control-lg" id="birthdate" value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>">
                        </div>

                        <div class="mb-4">
                            <label for="user_type" class="form-label">I want to register as a: <span class="text-danger">*</span></label>
                            <select name="user_type" id="user_type" class="form-select form-select-lg" required>
                                <option value="">Select an option</option>
                                <?php foreach ($userTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['id']); ?>"
                                        <?php echo (isset($_POST['user_type']) && (int)$_POST['user_type'] === (int)$type['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid mb-3"> <button type="submit" name="submit" class="btn btn-primary btn-lg rounded-pill">Register Account</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3 bg-light border-top-0">
                    <div class="small">
                        Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Login here.</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php template('templates/footer.php'); ?>