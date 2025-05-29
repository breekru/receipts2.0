<?php
// login.php - Fixed login page with better error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = clean_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Check user credentials with proper error handling
            $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name FROM users WHERE username = ? OR email = ? LIMIT 1");
            
            if (!$stmt) {
                error_log("Login: Failed to prepare statement - " . implode(', ', $pdo->errorInfo()));
                $error = 'Database error. Please try again.';
            } else {
                $result = $stmt->execute([$username, $username]);
                
                if (!$result) {
                    error_log("Login: Failed to execute statement - " . implode(', ', $stmt->errorInfo()));
                    $error = 'Database error. Please try again.';
                } else {
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Login successful - regenerate session ID for security
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                        $_SESSION['last_activity'] = time();
                        
                        // Log successful login
                        error_log("Successful login for user: " . $user['username']);
                        
                        redirect('dashboard.php', 'Welcome back!', 'success');
                    } else {
                        // Log failed login attempt
                        error_log("Failed login attempt for username: " . $username);
                        $error = 'Invalid username or password.';
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Login PDO Error: " . $e->getMessage());
        $error = 'Database connection error. Please try again later.';
    } catch (Exception $e) {
        error_log("Login General Error: " . $e->getMessage());
        $error = 'An unexpected error occurred. Please try again.';
    }
}

$page_title = 'Login';
include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-receipt fa-3x text-primary mb-3"></i>
                    <h2 class="fw-bold">Welcome Back</h2>
                    <p class="text-muted">Sign in to your LogIt account</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <input type="text" class="form-control" name="username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               autocomplete="username">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required
                               autocomplete="current-password">
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <span class="text-muted">Don't have an account?</span>
                        <a href="register.php" class="text-decoration-none">Create one</a>
                    </div>
                </form>
                
                <hr class="my-4">
                <div class="text-center">
                    <small class="text-muted">
                        <strong>Demo Login:</strong><br>
                        Username: <code>demo</code><br>
                        Password: <code>password</code>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focus on username field if empty
    const usernameField = document.querySelector('input[name="username"]');
    if (usernameField && !usernameField.value) {
        usernameField.focus();
    }
    
    // Add form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const username = document.querySelector('input[name="username"]').value.trim();
        const password = document.querySelector('input[name="password"]').value;
        
        if (!username || !password) {
            e.preventDefault();
            alert('Please enter both username and password.');
            return false;
        }
        
        // Show loading state
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
        }
    });
});
</script>

<?php include 'footer.php'; ?>