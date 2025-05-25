<?php
/**
 * LogIt - Configuration File
 * Centralized configuration and utilities
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Application Constants
define('APP_NAME', 'LogIt');
define('APP_VERSION', '3.0');
define('APP_DESCRIPTION', 'Modern receipt management and logging system');
define('BASE_URL', 'https://receipts.blkfarms.com');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('LOG_DIR', __DIR__ . '/logs/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Security Constants
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Create required directories
$required_dirs = [UPLOAD_DIR, LOG_DIR];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Database configuration - Load from secure location
require_once('/home/blkfarms/secure/db_config.php');

/**
 * Database Connection Class
 * Singleton pattern for database connections
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        global $host, $user, $pass, $dbname;
        
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'"
            ];
            
            $this->connection = new PDO($dsn, $user, $pass, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
}

/**
 * Utility Functions Class
 * Common helper functions used throughout the application
 */
class Utils {
    
    /**
     * Sanitize user input
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Format file size for display
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Password must be at least 8 characters long'];
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
        }
        
        if (!preg_match('/\d/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one number'];
        }
        
        return ['valid' => true, 'message' => 'Password is strong'];
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['username']) && 
               isset($_SESSION['login_time']) &&
               (time() - $_SESSION['login_time']) < SESSION_TIMEOUT;
    }
    
    /**
     * Require user to be logged in
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            self::redirect('login.php', 'Please log in to continue.', 'warning');
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current username
     */
    public static function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Redirect with optional flash message
     */
    public static function redirect($url, $message = '', $type = 'info') {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        
        // Ensure URL is safe
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9_\-\/\.]+\.php(\?.*)?$/', $url)) {
            $url = 'dashboard.php';
        }
        
        header("Location: $url");
        exit;
    }
    
    /**
     * Get and clear flash message
     */
    public static function getFlashMessage() {
        $message = $_SESSION['flash_message'] ?? '';
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    
    /**
     * Log activity
     */
    public static function logActivity($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $userId = self::getCurrentUserId() ?? 'anonymous';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logEntry = "[$timestamp] [$level] User:$userId IP:$ip - $message" . PHP_EOL;
        
        file_put_contents(LOG_DIR . 'activity.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($action, $limit = 5, $window = 300) {
        $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $attempts = $_SESSION['rate_limit'][$key] ?? [];
        $now = time();
        
        // Clean old attempts
        $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        if (count($attempts) >= $limit) {
            return false;
        }
        
        $attempts[] = $now;
        $_SESSION['rate_limit'][$key] = $attempts;
        return true;
    }
}

/**
 * User Management Class
 * Handles user authentication, registration, and management
 */
class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Authenticate user login
     */
    public function authenticate($username, $password) {
        try {
            // Rate limiting check
            if (!Utils::checkRateLimit('login', MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_TIME)) {
                Utils::logActivity("Login rate limit exceeded for: $username", 'WARNING');
                return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
            }
            
            $stmt = $this->db->prepare("
                SELECT user_id, username, email, password_hash, full_name, is_active 
                FROM users 
                WHERE (username = ? OR email = ?) AND is_active = 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Utils::logActivity("Login attempt with invalid username: $username", 'WARNING');
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }
            
            if (!password_verify($password, $user['password_hash'])) {
                Utils::logActivity("Login attempt with invalid password for user: $username", 'WARNING');
                return ['success' => false, 'message' => 'Invalid username or password.'];
            }
            
            // Successful login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            // Update last login
            $stmt = $this->db->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            Utils::logActivity("User logged in successfully: $username", 'INFO');
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            Utils::logActivity("Authentication error for user: $username - " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => 'An error occurred during login. Please try again.'];
        }
    }
    
    /**
     * Register new user
     */
    public function register($username, $email, $password, $fullName = '') {
        try {
            // Rate limiting check
            if (!Utils::checkRateLimit('register', 3, 3600)) {
                return ['success' => false, 'message' => 'Too many registration attempts. Please try again later.'];
            }
            
            // Validate input
            if (strlen($username) < 3) {
                return ['success' => false, 'message' => 'Username must be at least 3 characters long.'];
            }
            
            if (!Utils::validateEmail($email)) {
                return ['success' => false, 'message' => 'Please enter a valid email address.'];
            }
            
            $passwordValidation = Utils::validatePassword($password);
            if (!$passwordValidation['valid']) {
                return ['success' => false, 'message' => $passwordValidation['message']];
            }
            
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetchColumn() > 0) {
                Utils::logActivity("Registration attempt with existing username/email: $username, $email", 'WARNING');
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // Insert new user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
            ");
            $result = $stmt->execute([$username, $email, $passwordHash, $fullName]);
            
            if (!$result) {
                $this->db->rollBack();
                Utils::logActivity("Registration failed - database insert failed for: $username", 'ERROR');
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
            
            $userId = $this->db->lastInsertId();
            
            // Create default receipt box for new user
            $stmt = $this->db->prepare("
                INSERT INTO receipt_boxes (box_name, description, created_by, created_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute(["$username's Receipts", 'Default receipt box', $userId]);
            $boxId = $this->db->lastInsertId();
            
            // Grant owner access to the box
            $stmt = $this->db->prepare("
                INSERT INTO user_box_access (user_id, box_id, access_level, granted_by, granted_at) 
                VALUES (?, ?, 'owner', ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$userId, $boxId, $userId]);
            
            $this->db->commit();
            
            Utils::logActivity("User registered successfully: $username", 'INFO');
            return ['success' => true, 'message' => 'Account created successfully!'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Registration error: " . $e->getMessage());
            Utils::logActivity("Registration error for user: $username - " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Get user's receipt boxes
     */
    public function getUserBoxes($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT rb.*, uba.access_level 
                FROM receipt_boxes rb 
                JOIN user_box_access uba ON rb.box_id = uba.box_id 
                WHERE uba.user_id = ? 
                ORDER BY rb.box_name
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user boxes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create new receipt box
     */
    public function createBox($userId, $boxName, $description = '') {
        try {
            $this->db->beginTransaction();
            
            // Create box
            $stmt = $this->db->prepare("
                INSERT INTO receipt_boxes (box_name, description, created_by, created_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$boxName, $description, $userId]);
            $boxId = $this->db->lastInsertId();
            
            // Grant owner access
            $stmt = $this->db->prepare("
                INSERT INTO user_box_access (user_id, box_id, access_level, granted_by, granted_at) 
                VALUES (?, ?, 'owner', ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$userId, $boxId, $userId]);
            
            $this->db->commit();
            
            Utils::logActivity("Receipt box created: $boxName by user ID: $userId", 'INFO');
            return $boxId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating box: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Invite user to receipt box
     */
    public function inviteUser($email, $boxId, $invitedBy, $accessLevel = 'editor') {
        try {
            $token = Utils::generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $stmt = $this->db->prepare("
                INSERT INTO invitations (email, box_id, invited_by, access_level, invitation_token, expires_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $result = $stmt->execute([$email, $boxId, $invitedBy, $accessLevel, $token, $expiresAt]);
            
            if ($result) {
                Utils::logActivity("User invitation sent to: $email for box ID: $boxId", 'INFO');
                return $token;
            }
            return false;
            
        } catch (Exception $e) {
            error_log("Error inviting user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $username = Utils::getCurrentUsername();
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session for flash message
        session_start();
        
        Utils::logActivity("User logged out: $username", 'INFO');
    }
}

// Initialize global instances
try {
    $db = Database::getInstance()->getConnection();
    $userManager = new UserManager();
} catch (Exception $e) {
    error_log("Initialization error: " . $e->getMessage());
    die("Application initialization failed.");
}

// Common page header function
function renderHeader($title, $description = '') {
    $description = $description ?: APP_DESCRIPTION;
    echo "
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>$title - " . APP_NAME . "</title>
    <meta name=\"description\" content=\"$description\">
    <link rel=\"icon\" href=\"icons/LogIt.png\" type=\"image/png\">
    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\" rel=\"stylesheet\">
    <link href=\"styles.css\" rel=\"stylesheet\">
    <link rel=\"manifest\" href=\"manifest.json\">
    <meta name=\"theme-color\" content=\"#fd7e14\">
    ";
}

// Common scripts function
function renderScripts() {
    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>';
}
?>