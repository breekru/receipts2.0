<?php
// test_form.php - Minimal test to isolate the registration issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Form Submission Test</h1>";

// Show all POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h2>Individual Field Tests:</h2>";
    
    // Test each field
    $username = $_POST['username'] ?? 'NOT SET';
    $email = $_POST['email'] ?? 'NOT SET';
    $password = $_POST['password'] ?? 'NOT SET';
    $confirmPassword = $_POST['confirm_password'] ?? 'NOT SET';
    $fullName = $_POST['full_name'] ?? 'NOT SET';
    
    echo "Username: <strong>$username</strong><br>";
    echo "Email: <strong>$email</strong><br>";
    echo "Password: <strong>" . ($password === 'NOT SET' ? 'NOT SET' : 'HAS VALUE (length: ' . strlen($password) . ')') . "</strong><br>";
    echo "Confirm Password: <strong>" . ($confirmPassword === 'NOT SET' ? 'NOT SET' : 'HAS VALUE (length: ' . strlen($confirmPassword) . ')') . "</strong><br>";
    echo "Full Name: <strong>$fullName</strong><br>";
    
    // Test if register button was clicked
    if (isset($_POST['register'])) {
        echo "<p style='color: green;'>✅ Register button was clicked</p>";
    } else {
        echo "<p style='color: red;'>❌ Register button was NOT clicked</p>";
    }
    
    // Test CSRF token if it exists
    if (isset($_POST['csrf_token'])) {
        echo "<p style='color: green;'>✅ CSRF token present: " . substr($_POST['csrf_token'], 0, 10) . "...</p>";
    } else {
        echo "<p style='color: red;'>❌ CSRF token missing</p>";
    }
    
    // Test actual registration if we have data
    if ($password !== 'NOT SET' && $username !== 'NOT SET' && $email !== 'NOT SET') {
        echo "<h2>Testing Registration:</h2>";
        
        try {
            require_once 'config.php';
            
            // Skip CSRF check for testing
            $result = $userManager->register($username, $email, $password, $fullName);
            
            echo "Registration Result:<br>";
            echo "- Success: " . ($result['success'] ? 'YES' : 'NO') . "<br>";
            echo "- Message: " . htmlspecialchars($result['message']) . "<br>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Registration Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-container { border: 1px solid #ccc; padding: 20px; max-width: 400px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="form-container">
        <h3>Simple Registration Test Form</h3>
        <form method="POST">
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" value="Test User">
            </div>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" value="testuser<?php echo rand(100, 999); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="test<?php echo rand(100, 999); ?>@example.com" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" value="TestPass123" required>
            </div>
            
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" value="TestPass123" required>
            </div>
            
            <button type="submit" name="register" class="btn">Test Registration</button>
        </form>
    </div>
    
    <hr>
    <h3>Debug Your Original Form</h3>
    <p>If this simple form works but your original doesn't, the issue is likely:</p>
    <ul>
        <li><strong>JavaScript validation</strong> preventing form submission</li>
        <li><strong>CSRF token validation</strong> failing</li>
        <li><strong>Form field names</strong> not matching</li>
        <li><strong>Button disabled</strong> by JavaScript</li>
    </ul>
    
    <h3>Quick Fixes to Try:</h3>
    <ol>
        <li><strong>Disable JavaScript validation temporarily</strong> - Remove the <code>disabled</code> attribute from the register button and the form validation JavaScript</li>
        <li><strong>Skip CSRF validation temporarily</strong> - Comment out the CSRF check in register.php</li>
        <li><strong>Add debug output</strong> - Add <code>echo "Form submitted!";</code> at the very beginning of your POST handler</li>
    </ol>
    
    <p><a href="register.php">← Back to original registration form</a></p>
</body>
</html>