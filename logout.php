<?php
// logout.php - Fixed logout handling with proper redirect
// Ensure no output before headers
ob_start();

// Don't include config.php to avoid session conflicts
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Store user info for logging before destroying session
$logged_out_user = $_SESSION['username'] ?? 'unknown';

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

// Destroy the session completely
session_destroy();

// Clear any output buffer before redirect
ob_end_clean();

// Start a fresh session for the flash message
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';

// Log the logout
error_log("User logout: $logged_out_user");

// Add cache-busting headers to prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to index page with cache-busting parameter
$redirect_url = 'index.php?logout=1&t=' . time();
header('Location: ' . $redirect_url);

// Ensure script stops executing
exit();
?>