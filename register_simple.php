<?php
// register_simple.php - Simple registration without complex validation
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Redirect if already logged in
if (Utils::isLoggedIn()) {
    Utils::redirect('dashboard.php');
}

$registration_error = '';
$registration_success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Simple validation
    if (empty($username)) {
        $registration_error = 'Username is required.';
    } elseif (empty($email)) {
        $registration_error = 'Email is required.';
    } elseif (empty($password)) {
        $registration_error = 'Password is required.';
    } elseif (strlen($username) < 3) {
        $registration_error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $registration_error = 'Password must be at least 8 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registration_error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $registration_error = 'Passwords do not match.';
    } else {
        // Try registration
        try {
            $result = $userManager->register($username, $email, $password, $fullName);
            
            if ($result['success']) {
                $registration_success = $result['message'];
                // Clear form data on success
                $_POST = [];
            } else {
                $registration_error = $result['message'];
            }
        } catch (Exception $e) {
            $registration_error = 'Registration failed: ' . $e->getMessage();
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderHeader('Register', 'Create your LogIt account to start managing receipts'); ?>
</head>
<body class="auth-background">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
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
                    
                    <form method="POST" action="register_simple.php">
                        
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
                            <div class="form-text">Choose a unique username (3 or more characters)</div>
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
                            <div class="form-text">Must be at least 8 characters</div>
                        </div>
                        
                        <div class="form-floating mb-4">
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
    
    <!-- Minimal JavaScript for better UX -->
    <script>
        // Simple form enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[name="register"]');
            
            // Show loading state on submit
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
            });
            
            // Simple password match indicator
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            
            function checkPasswordMatch() {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#dc3545';
                } else if (confirmPassword.value) {
                    confirmPassword.style.borderColor = '#198754';
                } else {
                    confirmPassword.style.borderColor = '';
                }
            }
            
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
            
            // Auto-focus first field
            document.getElementById('fullName').focus();
        });
    </script>
</body>
</html>