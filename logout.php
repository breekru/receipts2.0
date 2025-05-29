<?php
// logout.php - Fixed logout handling
// Don't include config.php to avoid session conflicts
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Clear session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a fresh session for the flash message
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';

// Redirect to index page
header('Location: index.php');
exit;
?>