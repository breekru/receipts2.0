<?php
// config.php - Main configuration file

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'Receipt Logger');
define('APP_VERSION', '2.0');
define('BASE_URL', 'https://receipts.blkfarms.com');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Database configuration
require_once('/home/blkfarms/secure/db_config.php');

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        global $host, $user, $pass, $dbname;
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed");
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
}

// Utility functions
class Utils {
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function redirect($url, $message = '', $type = 'info') {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        header("Location: $url");
        exit;
    }
    
    public static function getFlashMessage() {
        $message = $_SESSION['flash_message'] ?? '';
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
}

// User management class
class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function authenticate($username, $password) {
        $stmt = $this->db->prepare("SELECT user_id, username, password_hash, is_active FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash']) && $user['is_active']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
        return false;
    }
    
    public function register($username, $email, $password, $fullName = '') {
        try {
            // Check if user exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetchColumn() > 0) {
                error_log("Registration failed: User already exists - $username, $email");
                return false; // User already exists
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, full_name, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
            $result = $stmt->execute([$username, $email, $passwordHash, $fullName]);
            
            if ($result) {
                error_log("User registered successfully: $username");
                return true;
            } else {
                error_log("Registration failed: Database insert failed for $username");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }
    
    public function getUserBoxes($userId) {
        $stmt = $this->db->prepare("
            SELECT rb.*, uba.access_level 
            FROM receipt_boxes rb 
            JOIN user_box_access uba ON rb.box_id = uba.box_id 
            WHERE uba.user_id = ? 
            ORDER BY rb.box_name
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function createBox($userId, $boxName, $description = '') {
        $this->db->beginTransaction();
        try {
            // Create box
            $stmt = $this->db->prepare("INSERT INTO receipt_boxes (box_name, description, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$boxName, $description, $userId]);
            $boxId = $this->db->lastInsertId();
            
            // Grant owner access
            $stmt = $this->db->prepare("INSERT INTO user_box_access (user_id, box_id, access_level, granted_by) VALUES (?, ?, 'owner', ?)");
            $stmt->execute([$userId, $boxId, $userId]);
            
            $this->db->commit();
            return $boxId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function inviteUser($email, $boxId, $invitedBy, $accessLevel = 'editor') {
        $token = Utils::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $this->db->prepare("INSERT INTO invitations (email, box_id, invited_by, access_level, invitation_token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$email, $boxId, $invitedBy, $accessLevel, $token, $expiresAt]) ? $token : false;
    }
}

// Initialize database connection
$db = Database::getInstance()->getConnection();
$userManager = new UserManager();

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>