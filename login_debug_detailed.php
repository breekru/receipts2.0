<?php
// login_debug_detailed.php - Detailed login debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h1>Login Debug Information</h1>";

// Check if user is already logged in
if (Utils::isLoggedIn()) {
    echo "<p style='color: green;'>✅ User is already logged in</p>";
    echo "<p>Current user ID: " . Utils::getCurrentUserId() . "</p>";
    echo "<p>Current username: " . Utils::getCurrentUsername() . "</p>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
    echo "<p><a href='logout.php'>Logout</a></p>";
} else {
    echo "<p style='color: orange;'>⚠️ User is not logged in</p>";
}

$login_error = '';
$debug_messages = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $debug_messages[] = "✅ Form submitted and login button clicked";
    
    // Show all POST data
    $debug_messages[] = "📋 POST data received:";
    foreach ($_POST as $key => $value) {
        if ($key === 'password') {
            $debug_messages[] = "- $key: " . (empty($value) ? "EMPTY" : "HAS VALUE (length: " . strlen($value) . ")");
        } else {
            $debug_messages[] = "- $key: '$value'";
        }
    }
    
    $username = Utils::sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $debug_messages[] = "📝 Processed form data:";
    $debug_messages[] = "- Username: '$username' (length: " . strlen($username) . ")";
    $debug_messages[] = "- Password: " . (empty($password) ? "EMPTY" : "HAS VALUE (length: " . strlen($password) . ")");
    
    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password.';
        $debug_messages[] = "❌ Validation failed: empty fields";
        if (empty($username)) $debug_messages[] = "  - Username is empty";
        if (empty($password)) $debug_messages[] = "  - Password is empty";
    } else {
        $debug_messages[] = "✅ Basic validation passed";
        
        try {
            $debug_messages[] = "🔄 Testing database connection...";
            
            // Test if user exists in database
            $stmt = $db->prepare("SELECT user_id, username, email, password_hash, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user) {
                $debug_messages[] = "👤 User found in database:";
                $debug_messages[] = "- User ID: " . $user['user_id'];
                $debug_messages[] = "- Username: " . $user['username'];
                $debug_messages[] = "- Email: " . $user['email'];
                $debug_messages[] = "- Is Active: " . ($user['is_active'] ? 'YES' : 'NO');
                $debug_messages[] = "- Password hash exists: " . (!empty($user['password_hash']) ? 'YES' : 'NO');
                
                // Test password verification
                if ($user['password_hash']) {
                    $passwordMatch = password_verify($password, $user['password_hash']);
                    $debug_messages[] = "🔐 Password verification: " . ($passwordMatch ? 'MATCH' : 'NO MATCH');
                    
                    if ($passwordMatch && $user['is_active']) {
                        $debug_messages[] = "✅ Manual verification passed - should be able to login";
                    } else {
                        if (!$passwordMatch) $debug_messages[] = "❌ Password does not match";
                        if (!$user['is_active']) $debug_messages[] = "❌ User account is not active";
                    }
                }
            } else {
                $debug_messages[] = "❌ User not found in database with username/email: $username";
                
                // Show all users for debugging
                $stmt = $db->prepare("SELECT user_id, username, email FROM users LIMIT 5");
                $stmt->execute();
                $allUsers = $stmt->fetchAll();
                $debug_messages[] = "📋 Available users in database:";
                foreach ($allUsers as $u) {
                    $debug_messages[] = "- ID: {$u['user_id']}, Username: {$u['username']}, Email: {$u['email']}";
                }
            }
            
            $debug_messages[] = "🔄 Now calling userManager->authenticate()";
            $result = $userManager->authenticate($username, $password);
            
            $debug_messages[] = "📊 UserManager authenticate result:";
            $debug_messages[] = "- Success: " . ($result['success'] ? 'YES' : 'NO');
            $debug_messages[] = "- Message: " . $result['message'];
            
            if ($result['success']) {
                $debug_messages[] = "✅ Authentication successful!";
                $debug_messages[] = "🔄 Checking session after authentication...";
                
                // Check session
                $debug_messages[] = "Session data:";
                $debug_messages[] = "- user_id: " . ($_SESSION['user_id'] ?? 'NOT SET');
                $debug_messages[] = "- username: " . ($_SESSION['username'] ?? 'NOT SET');
                $debug_messages[] = "- login_time: " . ($_SESSION['login_time'] ?? 'NOT SET');
                
                $debug_messages[] = "🔄 Testing Utils::isLoggedIn()...";
                $isLoggedIn = Utils::isLoggedIn();
                $debug_messages[] = "- Utils::isLoggedIn() returns: " . ($isLoggedIn ? 'TRUE' : 'FALSE');
                
                if ($isLoggedIn) {
                    $debug_messages[] = "✅ Login successful - would redirect to dashboard";
                    echo "<div style='background: green; color: white; padding: 10px; margin: 10px 0;'>";
                    echo "<h3>LOGIN SUCCESS!</h3>";
                    echo "<p><a href='dashboard.php' style='color: white;'>Go to Dashboard</a></p>";
                    echo "</div>";
                } else {
                    $debug_messages[] = "❌ Authentication succeeded but isLoggedIn() returns false";
                }
                
            } else {
                $login_error = $result['message'];
                $debug_messages[] = "❌ Authentication failed: " . $result['message'];
            }
        } catch (Exception $e) {
            $login_error = 'Login failed: ' . $e->getMessage();
            $debug_messages[] = "❌ Exception during login: " . $e->getMessage();
            $debug_messages[] = "❌ Stack trace: " . $e->getTraceAsString();
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderHeader('Login Debug', 'Debug login issues'); ?>
</head>
<body style="padding: 20px; font-family: Arial, sans-serif;">
    
    <!-- Debug Messages -->
    <?php if (!empty($debug_messages)): ?>
    <div style="background: #f0f8ff; border: 1px solid #0066cc; padding: 15px; margin: 20px 0;">
        <strong>🐛 Debug Messages:</strong><br>
        <?php foreach ($debug_messages as $msg): ?>
        <?php echo htmlspecialchars($msg); ?><br>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($login_error): ?>
    <div style="background: #ffeeee; border: 1px solid #cc0000; padding: 15px; margin: 20px 0; color: #cc0000;">
        <strong>❌ Login Error:</strong><br>
        <?php echo htmlspecialchars($login_error); ?>
    </div>
    <?php endif; ?>
    
    <div style="max-width: 400px; border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
        <h3>Test Login Form</h3>
        <form method="POST" action="login_debug_detailed.php">
            
            <div style="margin-bottom: 15px;">
                <label>Username or Email:</label><br>
                <input type="text" 
                       name="username" 
                       style="width: 100%; padding: 8px;"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>Password:</label><br>
                <input type="password" 
                       name="password" 
                       style="width: 100%; padding: 8px;">
            </div>
            
            <button type="submit" name="login" style="background: #007bff; color: white; padding: 10px 20px; border: none; width: 100%;">
                Test Login
            </button>
        </form>
    </div>
    
    <div style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
        <h4>Session Information:</h4>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <p><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></p>
        <p><strong>Current Session Data:</strong></p>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div style="margin: 20px 0;">
        <h4>Test Links:</h4>
        <p><a href="dashboard.php">Try Dashboard</a></p>
        <p><a href="register_simple.php">Register New Account</a></p>
        <p><a href="login.php">Back to Regular Login</a></p>
    </div>
</body>
</html>