<?php
// logout.php - Enhanced logout with better handling
require_once 'config.php';

// Clear session completely
if (session_status() == PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Start new session for flash message
session_start();

// Set flash message and redirect to index
$_SESSION['success'] = 'You have been logged out successfully.';
header('Location: index.php');
exit;
?>