<?php
// register.php - User Registration
require_once 'config.php';

// Redirect if already logged in
if (Utils::isLoggedIn()) {
    Utils::redirect('dashboard.php');
}

$registration_error = '';
$registration_success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Verify CSRF token
    //if (!Utils::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    //    $registration_error = 'Invalid request. Please try again.';
    //} else {
        // Sanitize inputs
        $username = Utils::sanitizeInput($_POST['username'] ?? '');
        $email = Utils::sanitizeInput($_POST['email'] ?? '');
        $fullName = Utils::sanitizeInput($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            $registration_error = 'All required fields must be filled.';
        } elseif ($password !== $confirmPassword) {
            $registration_error = 'Passwords do not match.';
        } else {
            // Attempt registration
            $result = $userManager->register($username, $email, $password, $fullName);
            
            if ($result['success']) {
                $registration_success = $result['message'];
                // Clear form data
                unset($_POST);
            } else {
                $registration_error = $result['message'];
            }
        }
    }
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
                    
                    <form method="POST" action="register.php" id="registerForm" novalidate>
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
                                   minlength="3"
                                   pattern="[a-zA-Z0-9_]{3,}"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <label for="username"><i class="fas fa-at me-2"></i>Username</label>
                            <div class="form-text">Choose a unique username (3+ characters, letters, numbers, underscore only)</div>
                            <div class="invalid-feedback">
                                Username must be at least 3 characters long and contain only letters, numbers, and underscores.
                            </div>
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
                            <div class="invalid-feedback">
                                Please provide a valid email address.
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password" 
                                   required
                                   minlength="8">
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="password-strength mt-2">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="password-requirements mt-2" id="passwordRequirements">
                                <div class="requirement" id="lengthReq">
                                    <i class="fas fa-times text-danger"></i>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="requirement" id="uppercaseReq">
                                    <i class="fas fa-times text-danger"></i>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="requirement" id="lowercaseReq">
                                    <i class="fas fa-times text-danger"></i>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="requirement" id="numberReq">
                                    <i class="fas fa-times text-danger"></i>
                                    <span>One number</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="confirmPassword" 
                                   name="confirm_password" 
                                   placeholder="Confirm Password" 
                                   required>
                            <label for="confirmPassword"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                            <div class="form-text" id="passwordMatch"></div>
                            <div class="invalid-feedback">
                                Passwords must match.
                            </div>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="agreeTerms" 
                                   required>
                            <label class="form-check-label" for="agreeTerms">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> 
                                and <a href="#" class="text-decoration-none">Privacy Policy</a>
                            </label>
                            <div class="invalid-feedback">
                                You must agree to the terms and conditions.
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" 
                                    name="register" 
                                    class="btn btn-accent btn-lg" 
                                    id="registerBtn" 
                                    disabled>
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
    <script>
        // Form validation and interactive features
        const form = document.getElementById('registerForm');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const strengthBar = document.getElementById('strengthBar');
        const registerBtn = document.getElementById('registerBtn');
        const agreeTerms = document.getElementById('agreeTerms');
        const passwordMatch = document.getElementById('passwordMatch');
        const passwordRequirements = document.getElementById('passwordRequirements');
        
        // Password requirements elements
        const lengthReq = document.getElementById('lengthReq');
        const uppercaseReq = document.getElementById('uppercaseReq');
        const lowercaseReq = document.getElementById('lowercaseReq');
        const numberReq = document.getElementById('numberReq');
        
        let passwordStrength = 0;
        let passwordsMatch = false;
        
        // Show/hide password requirements
        passwordInput.addEventListener('focus', () => {
            passwordRequirements.style.display = 'block';
        });
        
        passwordInput.addEventListener('blur', (e) => {
            if (!e.target.value) {
                passwordRequirements.style.display = 'none';
            }
        });
        
        // Real-time password validation
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let score = 0;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            
            // Update requirement indicators
            updateRequirement(lengthReq, hasLength);
            updateRequirement(uppercaseReq, hasUppercase);
            updateRequirement(lowercaseReq, hasLowercase);
            updateRequirement(numberReq, hasNumber);
            
            // Calculate strength score
            if (hasLength) score++;
            if (hasUppercase) score++;
            if (hasLowercase) score++;
            if (hasNumber) score++;
            
            passwordStrength = score;
            
            // Update strength bar
            updateStrengthBar(score);
            
            // Check form validity
            checkFormValidity();
            
            // Recheck password match if confirm password has value
            if (confirmPasswordInput.value) {
                checkPasswordMatch();
            }
        });
        
        // Password confirmation
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        // Terms checkbox
        agreeTerms.addEventListener('change', checkFormValidity);
        
        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const isValid = /^[a-zA-Z0-9_]{3,}$/.test(username);
            
            if (username.length > 0) {
                if (isValid) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
            
            checkFormValidity();
        });
        
        // Email validation
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            
            if (email.length > 0) {
                if (isValid) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
            
            checkFormValidity();
        });
        
        function updateRequirement(element, met) {
            const icon = element.querySelector('i');
            if (met) {
                element.classList.add('text-success');
                element.classList.remove('text-danger');
                icon.className = 'fas fa-check text-success';
            } else {
                element.classList.add('text-danger');
                element.classList.remove('text-success');
                icon.className = 'fas fa-times text-danger';
            }
        }
        
        function updateStrengthBar(score) {
            strengthBar.className = 'password-strength-bar';
            strengthBar.style.width = (score * 25) + '%';
            
            if (score === 1) {
                strengthBar.style.background = '#dc3545';
            } else if (score === 2) {
                strengthBar.style.background = '#ffc107';
            } else if (score === 3) {
                strengthBar.style.background = '#fd7e14';
            } else if (score === 4) {
                strengthBar.style.background = '#28a745';
            }
        }
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword === '') {
                passwordMatch.textContent = '';
                passwordMatch.className = 'form-text';
                confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
                passwordsMatch = false;
            } else if (password === confirmPassword) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.className = 'form-text text-success';
                confirmPasswordInput.classList.remove('is-invalid');
                confirmPasswordInput.classList.add('is-valid');
                passwordsMatch = true;
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.className = 'form-text text-danger';
                confirmPasswordInput.classList.remove('is-valid');
                confirmPasswordInput.classList.add('is-invalid');
                passwordsMatch = false;
            }
            
            checkFormValidity();
        }
        
        function checkFormValidity() {
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const termsAgreed = agreeTerms.checked;
            
            const usernameValid = /^[a-zA-Z0-9_]{3,}$/.test(username);
            const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            const passwordValid = passwordStrength === 4;
            const confirmPasswordValid = passwordsMatch && confirmPassword !== '';
            
            const isValid = usernameValid && emailValid && passwordValid && confirmPasswordValid && termsAgreed;
            
            registerBtn.disabled = !isValid;
            
            // Update button appearance
            if (isValid) {
                registerBtn.classList.remove('btn-secondary');
                registerBtn.classList.add('btn-accent');
            } else {
                registerBtn.classList.remove('btn-accent');
                registerBtn.classList.add('btn-secondary');
            }
        }
        
        // Form submission handling
        form.addEventListener('submit', function(e) {
            // Final validation
            if (registerBtn.disabled) {
                e.preventDefault();
                return false;
            }
            
            // Disable button and show loading state
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
            
            // Allow form to submit
        });
        
        // Initialize form validation
        checkFormValidity();
        
        // Add CSS for password strength bar
        const style = document.createElement('style');
        style.textContent = `
            .password-strength {
                height: 4px;
                background: #e9ecef;
                border-radius: 2px;
                overflow: hidden;
            }
            .password-strength-bar {
                height: 100%;
                width: 0%;
                transition: all 0.3s ease;
                border-radius: 2px;
            }
            .password-requirements {
                font-size: 0.875rem;
                display: none;
            }
            .requirement {
                display: flex;
                align-items: center;
                margin-bottom: 0.25rem;
            }
            .requirement i {
                width: 16px;
                margin-right: 0.5rem;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
        