<?php
// config.php - Enhanced configuration with better session and cache handling
// Set session configuration before starting session
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
ini_set('session.cookie_lifetime', 0); // Until browser closes
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Disable output caching for dynamic pages
ini_set('session.cache_limiter', 'nocache');
ini_set('session.cache_expire', 0);

session_start();

// Add cache-busting headers for edit pages
if (strpos($_SERVER['REQUEST_URI'], 'edit_receipt.php') !== false) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Session expired - clean up and redirect
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['error'] = 'Your session has expired. Please log in again.';
    if (!in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'login.php', 'register.php'])) {
        header('Location: index.php');
        exit;
    }
}

// Database connection - update these with your actual values
$host = 'localhost';
$username = 'logit_user';
$password = 'aycbkdTs*3kw2NLuFaD*';  
$database = 'receiptV2';

// Create PDO connection with retry logic and better error handling
$pdo = null;
$max_retries = 3;
$retry_count = 0;

while ($retry_count < $max_retries && $pdo === null) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
        
        // Test the connection
        $pdo->query("SELECT 1");
        
        // Set SQL mode for consistent behavior
        $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        
    } catch (PDOException $e) {
        $retry_count++;
        error_log("Database connection attempt $retry_count failed: " . $e->getMessage());
        
        if ($retry_count >= $max_retries) {
            // Log the final error
            error_log("Database connection failed after $max_retries attempts: " . $e->getMessage());
            
            // If it's an AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
                exit;
            }
            
            // For regular requests, show maintenance page
            http_response_code(503);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>LogIt - Maintenance</title>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
                <style>
                    body {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    }
                    .maintenance-card {
                        background: rgba(255, 255, 255, 0.1);
                        backdrop-filter: blur(20px);
                        border-radius: 20px;
                        padding: 3rem;
                        text-align: center;
                        max-width: 500px;
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                    }
                    .btn-light {
                        background: rgba(255, 255, 255, 0.2);
                        border: 2px solid rgba(255, 255, 255, 0.3);
                        color: white;
                        transition: all 0.3s ease;
                    }
                    .btn-light:hover {
                        background: rgba(255, 255, 255, 0.3);
                        border-color: rgba(255, 255, 255, 0.5);
                        color: white;
                    }
                </style>
            </head>
            <body>
                <div class="maintenance-card">
                    <i class="fas fa-database fa-4x mb-4 text-warning"></i>
                    <h2 class="mb-3">Database Connection Issue</h2>
                    <p class="mb-4">LogIt is experiencing database connectivity issues. Our team has been notified and we're working to resolve this quickly.</p>
                    <div class="mb-4">
                        <small class="text-muted">Error occurred at: <?php echo date('Y-m-d H:i:s'); ?></small>
                    </div>
                    <button onclick="location.reload()" class="btn btn-light me-2">
                        <i class="fas fa-refresh me-2"></i>Try Again
                    </button>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            // Wait before retry
            sleep(1);
        }
    }
}

// Simple constants
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
    mkdir(UPLOAD_DIR . 'thumbs/', 0755, true);
}

// Enhanced utility functions
function is_logged_in() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['username']) && 
           isset($_SESSION['last_activity']);
}

function require_login() {
    if (!is_logged_in()) {
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Session expired', 'redirect' => 'index.php']);
            exit;
        }
        
        $_SESSION['error'] = 'Please log in to continue.';
        header('Location: index.php');
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
    
    // Prevent header injection
    $url = filter_var($url, FILTER_SANITIZE_URL);
    
    // Add cache busting to redirects
    $separator = strpos($url, '?') !== false ? '&' : '?';
    $url .= $separator . '_t=' . time();
    
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

// Enhanced error handling for AJAX requests
function handle_ajax_error($message, $code = 400) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}

// Clear any PHP output caching
function clear_all_caches() {
    // Clear output buffer if exists
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Clear opcache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Send headers to prevent caching
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

// FIXED: Check for suspicious activity
function check_security() {
    // Rate limiting - simple implementation
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $current_time = time();
    
    // Initialize rate limit array if not exists
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    // Clean up old entries (older than 1 hour) - FIXED VERSION
    $_SESSION['rate_limit'] = array_filter($_SESSION['rate_limit'], function($entry) use ($current_time) {
        return is_array($entry) && isset($entry['time']) && ($current_time - $entry['time']) < 3600;
    });
    
    // Count requests from this IP in the last hour - FIXED VERSION
    $request_count = 0;
    foreach ($_SESSION['rate_limit'] as $entry) {
        if (is_array($entry) && isset($entry['ip']) && $entry['ip'] === $ip) {
            $request_count++;
        }
    }
    
    // Allow max 1000 requests per hour per IP
    if ($request_count > 1000) {
        http_response_code(429);
        die('Too many requests. Please try again later.');
    }
    
    // Log this request
    $_SESSION['rate_limit'][] = ['ip' => $ip, 'time' => $current_time];
    
    // Limit the size of the rate limit array to prevent memory issues
    if (count($_SESSION['rate_limit']) > 2000) {
        $_SESSION['rate_limit'] = array_slice($_SESSION['rate_limit'], -1000);
    }
}

// Update last activity time - MOVED AFTER FUNCTION DEFINITIONS
if (is_logged_in()) {
    $_SESSION['last_activity'] = time();
}

// Call security check for non-static requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    check_security();
}

// Force fresh data on edit pages
if (strpos($_SERVER['REQUEST_URI'], 'edit_receipt.php') !== false) {
    clear_all_caches();
}
?>