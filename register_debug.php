<?php
// register_debug.php - Registration with debugging and bypassed validation
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Redirect if already logged in
if (Utils::isLoggedIn()) {
    Utils::redirect('dashboard.php');
}

$registration_error = '';
$registration_success = '';
$debug_messages = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $debug_messages[] = "✅ Form submitted and register button clicked";
    
    // DEBUG: Show all POST data
    $debug_messages[] = "POST data keys: " . implode(', ', array_keys($_POST));
    
    // TEMPORARILY SKIP CSRF CHECK FOR DEBUGGING
    /*
    if (!Utils::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $registration_error = 'Invalid request. Please try again.';
        $debug_messages[] = "❌ CSRF token validation failed";
    } else {
    */
        $debug_messages[] = "✅ CSRF check bypassed for debugging";
        
        // Sanitize inputs
        $username = Utils::sanitizeInput($_POST['username'] ?? '');
        $email = Utils::sanitizeInput($_POST['email'] ?? '');
        $fullName = Utils::sanitizeInput($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $debug_messages[] = "Form fields extracted:";
        $debug_messages[] = "- Username: '$username' (length: " . strlen($username) . ")";
        $debug_messages[] = "- Email: '$email' (length: " . strlen($email) . ")";
        $debug_messages[] = "- Full Name: '$fullName' (length: " . strlen($fullName) . ")";
        $debug_messages[] = "- Password: " . (empty($password) ? "EMPTY" : "HAS VALUE (length: " . strlen($password) . ")");
        $debug_messages[] = "- Confirm Password: " . (empty($confirmPassword) ? "EMPTY" : "HAS VALUE (length: " . strlen($confirmPassword) . ")");
        
        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            $registration_error = 'All required fields must be filled.';
            $debug_messages[] = "❌ Validation failed: empty fields";
            if (empty($username)) $debug_messages[] = "  - Username is empty";
            if (empty($email)) $debug_messages[] = "  - Email is empty";
            if (empty($password)) $debug_messages[] = "  - Password is empty";
        } elseif ($password !== $confirmPassword) {
            $registration_error = 'Passwords do not match.';
            $debug_messages[] = "❌ Validation failed: passwords don't match";
        } else {
            $debug_messages[] = "✅ Basic validation passed";
            
            // Attempt registration
            try {
                $debug_messages[] = "🔄 Calling userManager->register()";
                $result = $userManager->register($username, $email, $password, $fullName);
                
                $debug_messages[] = "📊 Registration result received:";
                $debug_messages[] = "- Success: " . ($result['success'] ? 'YES' : 'NO');
                $debug_messages[] = "- Message: " . $result['message'];
                
                if ($result['success']) {
                    $registration_success = $result['message'];
                    $debug_messages[] = "✅ Registration successful!";
                    // Clear form data
                    unset($_POST);
                } else {
                    $registration_error = $result['message'];
                    $debug_messages[] = "❌ Registration failed: " . $result['message'];
                }
            } catch (Exception $e) {
                $registration_error = 'Registration failed: ' . $e->getMessage();
                $debug_messages[] = "❌ Exception during registration: " . $e->getMessage();
            }
        }
    /*
    }
    */
}

// Generate CSRF token for form
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
                    <h2 class="text-center mb-3 fw-bold">Create Account (DEBUG MODE)</h2>
                    <p class="text-center text-muted mb-4">Registration with debugging enabled</p>
                    
                    <!-- Debug Messages -->
                    <?php if (!empty($debug_messages)): ?>
                    <div class="alert alert-info">
                        <strong>🐛 Debug Messages:</strong><br>
                        <?php foreach ($debug_messages as $msg): ?>
                        <?php echo htmlspecialchars($msg); ?><br>
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
                    
                    <!-- SIMPLIFIED FORM - NO JAVASCRIPT VALIDATION -->
                    <form method="POST" action="register_debug.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="fullName" 
                                   name="full_name" 
                                   placeholder="Full Name"
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : 'Test User'; ?>">
                            <label for="fullName"><i class="fas fa-user me-2"></i>Full Name</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Username" 
                                   required 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : 'testuser' . rand(100, 999); ?>">
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
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 'test' . rand(100, 999) . '@example.com'; ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password" 
                                   required
                                   value="TestPass123">
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="form-text">At least 8 characters</div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="confirmPassword" 
                                   name="confirm_password" 
                                   placeholder="Confirm Password" 
                                   required
                                   value="TestPass123">
                            <label for="confirmPassword"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                        </div>
                        
                        <!-- BUTTON ALWAYS ENABLED -->
                        <div class="d-grid mb-3">
                            <button type="submit" 
                                    name="register" 
                                    class="btn btn-accent btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account (Debug)
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
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-bug me-1"></i>
                            Debug mode - CSRF validation bypassed, JavaScript validation disabled
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php renderScripts(); ?>
</body>
</html>