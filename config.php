<?php
// config.php - Simple configuration
session_start();

// Database connection - update these with your actual values
$host = 'localhost';
$username = 'logit_user';
$password = 'aycbkdTs*3kw2NLuFaD*';  
$database = 'receiptV2';


// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Simple constants
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    mkdir(UPLOAD_DIR . 'thumbs/', 0755, true);
}

// Simple utility functions
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: $url");
    exit;
}

function get_flash($type = 'info') {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return '';
}

function clean_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>