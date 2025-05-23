<?php

session_start();

// Unset all of the session variables.
// This effectively clears the $_SESSION superglobal, including $_SESSION['cart'].
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

header('Location: login.php'); // Redirect to the login page
exit; // Ensure no further code is executed after the redirect

?>