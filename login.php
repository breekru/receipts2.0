<?php
// login.php - User Login
require_once 'config.php';

// Redirect if already logged in
if (Utils::isLoggedIn()) {
    Utils::redirect('dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Verify CSRF token
    if (!Utils::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        Utils::redirect('login.php', 'Invalid request. Please try again.', 'error');
    }
    
    $username = Utils::sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        Utils::redirect('login.php', 'Please enter both username and password.', 'error');
    }
    
    $result = $userManager->authenticate($username, $password);
    
    if ($result['success']) {
        Utils::redirect('dashboard.php', 'Welcome back!', 'success');
    } else {
        Utils::redirect('login.php', $result['message'], 'error');
    }
}

// Generate CSRF token
$csrf_token = Utils::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderHeader('Login', 'Sign in to your LogIt account'); ?>
</head>
<body class="auth-background">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="auth-container">
                    <img src="icons/LogIt.png" alt="LogIt" class="logo float">
                    <h2 class="text-center mb-4 fw-bold">Welcome Back</h2>
                    <p class="text-center text-muted mb-4">Sign in to your LogIt account</p>
                    
                    <?php
                    $flash = Utils::getFlashMessage();
                    if ($flash['message']):
                    ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $flash['type'] === 'error' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php" id="loginForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Username or Email" 
                                   required
                                   autocomplete="username">
                            <label for="username"><i class="fas fa-user me-2"></i>Username or Email</label>
                            <div class="invalid-feedback">
                                Please enter your username or email.
                            </div>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Password" 
                                   required
                                   autocomplete="current-password">
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                                    <label class="form-check-label" for="rememberMe">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <div class="col text-end">
                                <a href="forgot-password.php" class="text-decoration-none text-muted">
                                    Forgot password?
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn btn-primary btn-lg" id="loginBtn">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <div class="mb-2">
                                <span class="text-muted">Don't have an account?</span>
                            </div>
                            <a href="register.php" class="btn btn-outline-accent">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </a>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Your receipts are secure and encrypted
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php renderScripts(); ?>
    <script>
        // Form validation and enhancement
        const form = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const loginBtn = document.getElementById('loginBtn');
        
        // Real-time validation
        function validateForm() {
            const username = usernameInput.value.trim();
            const password = passwordInput.value;
            
            // Username validation
            if (username.length > 0) {
                usernameInput.classList.remove('is-invalid');
                usernameInput.classList.add('is-valid');
            } else {
                usernameInput.classList.remove('is-valid', 'is-invalid');
            }
            
            // Password validation
            if (password.length > 0) {
                passwordInput.classList.remove('is-invalid');
                passwordInput.classList.add('is-valid');
            } else {
                passwordInput.classList.remove('is-valid', 'is-invalid');
            }
            
            // Enable/disable submit button
            const isValid = username.length > 0 && password.length > 0;
            loginBtn.disabled = !isValid;
        }
        
        // Add event listeners
        usernameInput.addEventListener('input', validateForm);
        passwordInput.addEventListener('input', validateForm);
        
        // Form submission
        form.addEventListener('submit', function(e) {
            const username = usernameInput.value.trim();
            const password = passwordInput.value;
            
            // Client-side validation
            if (!username || !password) {
                e.preventDefault();
                
                if (!username) {
                    usernameInput.classList.add('is-invalid');
                }
                if (!password) {
                    passwordInput.classList.add('is-invalid');
                }
                
                return false;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
        });
        
        // Initial validation
        validateForm();
        
        // Focus on username field
        usernameInput.focus();
        
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.log('Service Worker registration failed'));
        }
        
        // Install prompt for PWA
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button
            const installButton = document.createElement('button');
            installButton.className = 'btn btn-sm btn-outline-secondary mt-2 w-100';
            installButton.innerHTML = '<i class="fas fa-download me-1"></i>Install LogIt App';
            installButton.onclick = () => {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                    installButton.remove();
                });
            };
            
            document.querySelector('.auth-container').appendChild(installButton);
        });
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Enter key to submit form
            if (e.key === 'Enter' && !loginBtn.disabled) {
                form.submit();
            }
        });
    </script>
</body>
</html>