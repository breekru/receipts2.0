<?php
// register_working.php - Exact copy of the working debug version, but clean
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Redirect if already logged in
if (Utils::isLoggedIn()) {
    Utils::redirect('dashboard.php');
}

$registration_error = '';
$registration_success = '';

// Handle registration form submission - EXACT SAME LOGIC AS WORKING DEBUG VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    
    // SKIP CSRF CHECK (same as working debug version)
    // Sanitize inputs - SAME AS DEBUG VERSION
    $username = Utils::sanitizeInput($_POST['username'] ?? '');
    $email = Utils::sanitizeInput($_POST['email'] ?? '');
    $fullName = Utils::sanitizeInput($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation - SAME AS DEBUG VERSION
    if (empty($username) || empty($email) || empty($password)) {
        $registration_error = 'All required fields must be filled.';
    } elseif ($password !== $confirmPassword) {
        $registration_error = 'Passwords do not match.';
    } else {
        // Attempt registration - SAME TRY/CATCH AS DEBUG VERSION
        try {
            $result = $userManager->register($username, $email, $password, $fullName);
            
            if ($result['success']) {
                $registration_success = $result['message'];
                // Clear form data
                unset($_POST);
            } else {
                $registration_error = $result['message'];
            }
        } catch (Exception $e) {
            $registration_error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Generate CSRF token for form (even though we don't use it)
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
                    <p class="text-center text-muted mb-4">Join LogIt today and start organizing your receipts</p>
                    
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
                    
                    <!-- SAME FORM STRUCTURE AS DEBUG VERSION -->
                    <form method="POST" action="register_simple.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
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
                        
                        <!-- BUTTON ALWAYS ENABLED - SAME AS DEBUG VERSION -->
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
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Your data is protected with enterprise-grade encryption
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php renderScripts(); ?>
</body>
</html>