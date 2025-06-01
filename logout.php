<?php
/**
 * Logout Script
 * 
 * Terminates the user session and redirects to the login page.
 */

// Initialize the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set a logout message
session_start();
$_SESSION['alert_message'] = "You have been logged out successfully.";
$_SESSION['alert_type'] = "info";

// Redirect to login page
header("location: login.php");
exit;
?>