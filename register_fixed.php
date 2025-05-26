<?php
// register_fixed.php - Registration with better error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Redirect if already logged in
if (Utils::isLoggedIn()) {
    Utils::redirect('dashboard.php');
}

$registration_error = '';
$registration_success = '';
$debug_info = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $debug_info[] = "Form submitted";
    
    // Get form data
    $username = Utils::sanitizeInput($_POST['username'] ?? '');
    $email = Utils::sanitizeInput($_POST['email'] ?? '');
    $fullName = Utils::sanitizeInput($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $debug_info[] = "Form data: username=$username, email=$email";
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $registration_error = 'All required fields must be filled.';
        $debug_info[] = "Validation failed: empty fields";
    } elseif (strlen($username) < 3) {
        $registration_error = 'Username must be at least 3 characters long.';
        $debug_info[] = "Validation failed: username too short";
    } elseif (!Utils::validateEmail($email)) {
        $registration_error = 'Please enter a valid email address.';
        $debug_info[] = "Validation failed: invalid email";
    } elseif (strlen($password) < 8) {
        $registration_error = 'Password must be at least 8 characters long.';
        $debug_info[] = "Validation failed: password too short";
    } elseif ($password !== $confirmPassword) {
        $registration_error = 'Passwords do not match.';
        $debug_info[] = "Validation failed: passwords don't match";
    } else {
        $debug_info[] = "Basic validation passed";
        
        // Try registration
        try {
            $debug_info[] = "Attempting registration...";
            
            // Check if user exists first
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existingCount = $stmt->fetchColumn();
            
            if ($existingCount > 0) {
                $registration_error = 'Username or email already exists.';
                $debug_info[] = "Registration failed: user already exists";
            } else {
                $debug_info[] = "User doesn't exist, proceeding with creation";
                
                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $debug_info[] = "Password hashed successfully";
                
                // Begin transaction
                $db->beginTransaction();
                $debug_info[] = "Transaction started";
                
                // Insert user
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                $result = $stmt->execute([$username, $email, $passwordHash, $fullName]);
                
                if ($result) {
                    $userId = $db->lastInsertId();
                    $debug_info[] = "User created with ID: $userId";
                    
                    // Create default receipt box
                    $stmt = $db->prepare("
                        INSERT INTO receipt_boxes (box_name, description, created_by, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute(["$username's Receipts", 'Default receipt box', $userId]);
                    $boxId = $db->lastInsertId();
                    $debug_info[] = "Receipt box created with ID: $boxId";
                    
                    // Grant owner access
                    $stmt = $db->prepare("
                        INSERT INTO user_box_access (user_id, box_id, access_level, granted_by, granted_at) 
                        VALUES (?, ?, 'owner', ?, NOW())
                    ");
                    $stmt->execute([$userId, $boxId, $userId]);
                    $debug_info[] = "Access granted";
                    
                    $db->commit();
                    $debug_info[] = "Transaction committed";
                    
                    $registration_success = 'Account created successfully! You can now sign in.';
                    Utils::logActivity("User registered successfully: $username", 'INFO');
                    $debug_info[] = "Registration completed successfully";
                    
                } else {
                    $db->rollBack();
                    $registration_error = 'Failed to create user account.';
                    $debug_info[] = "Registration failed: user insert failed";
                }
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $registration_error = 'Registration failed: ' . $e->getMessage();
            $debug_info[] = "Exception: " . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Generate CSRF token for form (optional for testing)
$csrf_token = Utils::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderHeader('Register', 'Create your LogIt account to start managing receipts'); ?>
</head>
<body class="auth-background">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-container">
                    <img src="icons/LogIt.png" alt="LogIt" class="logo float">
                    <h2 class="text-center mb-3 fw-bold">Create Account</h2>
                    <p class="text-center text-muted mb-4">Join LogIt today</p>
                    
                    <!-- Debug Information (remove in production) -->
                    <?php if (!empty($debug_info)): ?>
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        <?php foreach ($debug_info as $info): ?>
                        • <?php echo htmlspecialchars($info); ?><br>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($registration_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($registration_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($registration_success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($registration_success); ?>
                        <div class="mt-2">
                            <a href="login.php" class="btn btn-success btn-sm">
                                <i class="fas fa-sign-in-alt me-1"></i>Sign In Now
                            </a>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="register_fixed.php" id="registerForm">
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="fullName" 
                                   name="full_name" 
                                   placeholder="Full Name"
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            <label for="fullName"><i class="fas fa-user me-2"></i>Full Name</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Username" 
                                   required 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <label for="username"><i class="fas fa-at me-2"></i>Username</label>
                            <div class="form-text">Choose a unique username (3+ characters)</div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="Email" 
                                   required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password" 
                                   required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="form-text">At least 8 characters</div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="confirmPassword" 
                                   name="confirm_password" 
                                   placeholder="Confirm Password" 
                                   required>
                            <label for="confirmPassword"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" 
                                    name="register" 
                                    class="btn btn-accent btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <div class="mb-2">
                                <span class="text-muted">Already have an account?</span>
                            </div>
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php renderScripts(); ?>
</body>
</html>