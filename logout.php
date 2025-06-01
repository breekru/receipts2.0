<?php
// debug_logout.php - Debug version to identify redirect issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if headers can be sent
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line. Cannot redirect.");
}

echo "<!-- Debug: Starting logout process -->\n";

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
    echo "<!-- Debug: Session started -->\n";
} else {
    echo "<!-- Debug: Session already active -->\n";
}

// Store user info for debugging
$user_info = [
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'username' => $_SESSION['username'] ?? 'not set',
    'session_id' => session_id()
];

echo "<!-- Debug: User info before logout: " . json_encode($user_info) . " -->\n";

// Clear all session variables
$_SESSION = array();
echo "<!-- Debug: Session variables cleared -->\n";

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    $cookie_result = setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
    echo "<!-- Debug: Cookie clear result: " . ($cookie_result ? 'success' : 'failed') . " -->\n";
}

// Destroy the session
$destroy_result = session_destroy();
echo "<!-- Debug: Session destroy result: " . ($destroy_result ? 'success' : 'failed') . " -->\n";

// Start fresh session
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';
echo "<!-- Debug: New session started with success message -->\n";

// Check if we can still send headers
if (headers_sent($file, $line)) {
    echo "<script>window.location.href = 'index.php?logout=1&t=" . time() . "';</script>";
    echo "<p>If you are not redirected automatically, <a href='index.php?logout=1'>click here</a>.</p>";
} else {
    echo "<!-- Debug: About to redirect -->\n";
    header('Location: index.php?logout=1&t=' . time());
    exit();
}
?>