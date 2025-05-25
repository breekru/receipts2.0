<?php
// Handle registration form submission FIRST
require_once 'config.php';

$debug_messages = [];
$registration_error = '';
$registration_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $debug_messages[] = "Form submitted";
    
    $username = Utils::sanitizeInput($_POST['username']);
    $email = Utils::sanitizeInput($_POST['email']);
    $fullName = Utils::sanitizeInput($_POST['full_name']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $debug_messages[] = "Data received: Username: $username, Email: $email";
    
    // Validate inputs
    if (strlen($username) < 3) {
        $registration_error = 'Username must be at least 3 characters long.';
    } elseif (!Utils::validateEmail($email)) {
        $registration_error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $registration_error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $registration_error = 'Passwords do not match.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $registration_error = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
    } else {
        // Try to register
        $debug_messages[] = "Validation passed, attempting registration";
        
        try {
            if ($userManager->register($username, $email, $password, $fullName)) {
                $registration_success = 'Account created successfully! Please sign in.';
                $debug_messages[] = "Registration successful";
            } else {
                $registration_error = 'Username or email already exists. Please choose different ones.';
                $debug_messages[] = "Registration failed - user exists";
            }
        } catch (Exception $e) {
            $registration_error = 'Registration failed: ' . $e->getMessage();
            $debug_messages[] = "Exception: " . $e->getMessage();
        }
    }
}

// Enable debug mode - remove this in production
$show_debug = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Receipt Logger</title>
    <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: block;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50px;
            padding: 12px 0;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-floating > label {
            opacity: 0.65;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: 1;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }
        
        .password-strength {
            height: 4px;
            margin-top: 0.5rem;
            border-radius: 2px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-fair { background: #ffc107; width: 50%; }
        .strength-good { background: #20c997; width: 75%; }
        .strength-strong { background: #198754; width: 100%; }
        
        .password-requirements {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
            color: #6c757d;
        }
        
        .requirement.met {
            color: #198754;
        }
        
        .requirement i {
            width: 16px;
            margin-right: 0.5rem;
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #6c757d;
        }
        
        .terms-checkbox {
            transform: scale(1.2);
            margin-right: 0.75rem;
        }
        
        .terms-text {
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="register-container">
                    <img src="icons/ReceiptLogger.png" alt="Receipt Logger" class="logo">
                    <h2 class="text-center mb-4 fw-bold">Create Account</h2>
                    <p class="text-center text-muted mb-4">Join Receipt Logger today</p>
                    
                    <?php if ($show_debug && !empty($debug_messages)): ?>
                    <div class="debug-info">
                        <strong>Debug Info:</strong><br>
                        <?php foreach ($debug_messages as $msg): ?>
                            • <?php echo htmlspecialchars($msg); ?><br>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($registration_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($registration_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($registration_success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($registration_success); ?>
                        <a href="login.php" class="btn btn-success btn-sm ms-2">Sign In Now</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="register.php" id="registerForm">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="fullName" name="full_name" placeholder="Full Name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            <label for="fullName"><i class="fas fa-user me-2"></i>Full Name</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <label for="username"><i class="fas fa-at me-2"></i>Username</label>
                            <div class="form-text">Choose a unique username (3+ characters)</div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="password-requirements" id="passwordRequirements">
                                <div class="requirement" id="lengthReq">
                                    <i class="fas fa-times"></i>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="requirement" id="uppercaseReq">
                                    <i class="fas fa-times"></i>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="requirement" id="lowercaseReq">
                                    <i class="fas fa-times"></i>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="requirement" id="numberReq">
                                    <i class="fas fa-times"></i>
                                    <span>One number</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                            <label for="confirmPassword"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                            <div class="form-text" id="passwordMatch"></div>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input terms-checkbox" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label terms-text" for="agreeTerms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> 
                                and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="register" class="btn btn-primary btn-lg" id="registerBtn">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                        
                        <div class="divider">
                            <span>or</span>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account?</p>
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

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By using Receipt Logger, you agree to these terms and conditions.</p>
                    
                    <h6>2. Service Description</h6>
                    <p>Receipt Logger is a digital receipt management system that allows users to upload, organize, and track receipts for personal or business use.</p>
                    
                    <h6>3. User Responsibilities</h6>
                    <ul>
                        <li>Maintain the security of your account credentials</li>
                        <li>Use the service only for lawful purposes</li>
                        <li>Ensure uploaded content doesn't violate copyright or privacy laws</li>
                    </ul>
                    
                    <h6>4. Data Usage</h6>
                    <p>We collect and process your data as outlined in our Privacy Policy. We do not sell or share your personal information with third parties.</p>
                    
                    <h6>5. Service Availability</h6>
                    <p>We strive to maintain 99.9% uptime but cannot guarantee uninterrupted service.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Information We Collect</h6>
                    <ul>
                        <li>Account information (username, email, name)</li>
                        <li>Receipt images and associated metadata</li>
                        <li>Usage analytics and system logs</li>
                    </ul>
                    
                    <h6>How We Use Your Information</h6>
                    <ul>
                        <li>Provide and improve our services</li>
                        <li>Communicate important updates</li>
                        <li>Ensure security and prevent fraud</li>
                    </ul>
                    
                    <h6>Data Security</h6>
                    <p>We use industry-standard encryption and security measures to protect your data. All receipt images are stored securely and are only accessible by authorized users.</p>
                    
                    <h6>Your Rights</h6>
                    <p>You have the right to access, modify, or delete your personal data at any time through your account settings.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const strengthBar = document.getElementById('strengthBar');
        const registerBtn = document.getElementById('registerBtn');
        const agreeTerms = document.getElementById('agreeTerms');
        const passwordMatch = document.getElementById('passwordMatch');
        
        // Password requirements elements
        const lengthReq = document.getElementById('lengthReq');
        const uppercaseReq = document.getElementById('uppercaseReq');
        const lowercaseReq = document.getElementById('lowercaseReq');
        const numberReq = document.getElementById('numberReq');
        
        // Password strength checking
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
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (score === 1) strengthBar.classList.add('strength-weak');
            else if (score === 2) strengthBar.classList.add('strength-fair');
            else if (score === 3) strengthBar.classList.add('strength-good');
            else if (score === 4) strengthBar.classList.add('strength-strong');
            
            checkFormValidity();
        });
        
        // Confirm password checking
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword === '') {
                passwordMatch.textContent = '';
                passwordMatch.className = 'form-text';
            } else if (password === confirmPassword) {
                passwordMatch.textContent = 'Passwords match';
                passwordMatch.className = 'form-text text-success';
            } else {
                passwordMatch.textContent = 'Passwords do not match';
                passwordMatch.className = 'form-text text-danger';
            }
            
            checkFormValidity();
        });
        
        // Terms agreement
        agreeTerms.addEventListener('change', checkFormValidity);
        
        function updateRequirement(element, met) {
            const icon = element.querySelector('i');
            if (met) {
                element.classList.add('met');
                icon.className = 'fas fa-check';
            } else {
                element.classList.remove('met');
                icon.className = 'fas fa-times';
            }
        }
        
        function checkFormValidity() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const passwordsMatch = password === confirmPassword && confirmPassword !== '';
            const termsAgreed = agreeTerms.checked;
            
            const isValid = hasLength && hasUppercase && hasLowercase && hasNumber && passwordsMatch && termsAgreed;
            
            registerBtn.disabled = !isValid;
        }
        
        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
        });
    </script>
</body>
</html>
                    
                    <form method="POST" action="register.php" id="registerForm">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="fullName" name="full_name" placeholder="Full Name" required>
                            <label for="fullName"><i class="fas fa-user me-2"></i>Full Name</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            <label for="username"><i class="fas fa-at me-2"></i>Username</label>
                            <div class="form-text">Choose a unique username (3+ characters)</div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="password-requirements" id="passwordRequirements">
                                <div class="requirement" id="lengthReq">
                                    <i class="fas fa-times"></i>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="requirement" id="uppercaseReq">
                                    <i class="fas fa-times"></i>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="requirement" id="lowercaseReq">
                                    <i class="fas fa-times"></i>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="requirement" id="numberReq">
                                    <i class="fas fa-times"></i>
                                    <span>One number</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                            <label for="confirmPassword"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                            <div class="form-text" id="passwordMatch"></div>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input terms-checkbox" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label terms-text" for="agreeTerms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> 
                                and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="register" class="btn btn-primary btn-lg" id="registerBtn" disabled>
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                        
                        <div class="divider">
                            <span>or</span>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account?</p>
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

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By using Receipt Logger, you agree to these terms and conditions.</p>
                    
                    <h6>2. Service Description</h6>
                    <p>Receipt Logger is a digital receipt management system that allows users to upload, organize, and track receipts for personal or business use.</p>
                    
                    <h6>3. User Responsibilities</h6>
                    <ul>
                        <li>Maintain the security of your account credentials</li>
                        <li>Use the service only for lawful purposes</li>
                        <li>Ensure uploaded content doesn't violate copyright or privacy laws</li>
                    </ul>
                    
                    <h6>4. Data Usage</h6>
                    <p>We collect and process your data as outlined in our Privacy Policy. We do not sell or share your personal information with third parties.</p>
                    
                    <h6>5. Service Availability</h6>
                    <p>We strive to maintain 99.9% uptime but cannot guarantee uninterrupted service.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Information We Collect</h6>
                    <ul>
                        <li>Account information (username, email, name)</li>
                        <li>Receipt images and associated metadata</li>
                        <li>Usage analytics and system logs</li>
                    </ul>
                    
                    <h6>How We Use Your Information</h6>
                    <ul>
                        <li>Provide and improve our services</li>
                        <li>Communicate important updates</li>
                        <li>Ensure security and prevent fraud</li>
                    </ul>
                    
                    <h6>Data Security</h6>
                    <p>We use industry-standard encryption and security measures to protect your data. All receipt images are stored securely and are only accessible by authorized users.</p>
                    
                    <h6>Your Rights</h6>
                    <p>You have the right to access, modify, or delete your personal data at any time through your account settings.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const strengthBar = document.getElementById('strengthBar');
        const registerBtn = document.getElementById('registerBtn');
        const agreeTerms = document.getElementById('agreeTerms');
        const passwordMatch = document.getElementById('passwordMatch');
        
        // Password requirements elements
        const lengthReq = document.getElementById('lengthReq');
        const uppercaseReq = document.getElementById('uppercaseReq');
        const lowercaseReq = document.getElementById('lowercaseReq');
        const numberReq = document.getElementById('numberReq');
        
        // Password strength checking
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
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (score === 1) strengthBar.classList.add('strength-weak');
            else if (score === 2) strengthBar.classList.add('strength-fair');
            else if (score === 3) strengthBar.classList.add('strength-good');
            else if (score === 4) strengthBar.classList.add('strength-strong');
            
            checkFormValidity();
        });
        
        // Confirm password checking
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword === '') {
                passwordMatch.textContent = '';
                passwordMatch.className = 'form-text';
            } else if (password === confirmPassword) {
                passwordMatch.textContent = 'Passwords match';
                passwordMatch.className = 'form-text text-success';
            } else {
                passwordMatch.textContent = 'Passwords do not match';
                passwordMatch.className = 'form-text text-danger';
            }
            
            checkFormValidity();
        });
        
        // Terms agreement
        agreeTerms.addEventListener('change', checkFormValidity);
        
        function updateRequirement(element, met) {
            const icon = element.querySelector('i');
            if (met) {
                element.classList.add('met');
                icon.className = 'fas fa-check';
            } else {
                element.classList.remove('met');
                icon.className = 'fas fa-times';
            }
        }
        
        function checkFormValidity() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const passwordsMatch = password === confirmPassword && confirmPassword !== '';
            const termsAgreed = agreeTerms.checked;
            
            const isValid = hasLength && hasUppercase && hasLowercase && hasNumber && passwordsMatch && termsAgreed;
            
            registerBtn.disabled = !isValid;
        }
        
        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
        });
    </script>
</body>
</html>

<?php
// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = Utils::sanitizeInput($_POST['username']);
    $email = Utils::sanitizeInput($_POST['email']);
    $fullName = Utils::sanitizeInput($_POST['full_name']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate inputs
    if (strlen($username) < 3) {
        Utils::redirect('register.php', 'Username must be at least 3 characters long.', 'error');
    }
    
    if (!Utils::validateEmail($email)) {
        Utils::redirect('register.php', 'Please enter a valid email address.', 'error');
    }
    
    if (strlen($password) < 8) {
        Utils::redirect('register.php', 'Password must be at least 8 characters long.', 'error');
    }
    
    if ($password !== $confirmPassword) {
        Utils::redirect('register.php', 'Passwords do not match.', 'error');
    }
    
    // Check password strength
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        Utils::redirect('register.php', 'Password must contain at least one uppercase letter, one lowercase letter, and one number.', 'error');
    }
    
    if ($userManager->register($username, $email, $password, $fullName)) {
        Utils::redirect('login.php', 'Account created successfully! Please sign in.', 'success');
    } else {
        Utils::redirect('register.php', 'Username or email already exists. Please choose different ones.', 'error');
    }
}
?>