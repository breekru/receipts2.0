<?php
// debug_register.php - Debug version for registration issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Registration Debug Information</h2>";

// Test 1: Check if config.php loads
echo "<h3>1. Testing config.php load...</h3>";
try {
    require_once 'config.php';
    echo "✅ Config loaded successfully<br>";
    echo "APP_NAME: " . APP_NAME . "<br>";
} catch (Exception $e) {
    echo "❌ Config load failed: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Check database connection
echo "<h3>2. Testing database connection...</h3>";
try {
    $testDb = Database::getInstance()->getConnection();
    echo "✅ Database connected successfully<br>";
    
    // Test query
    $stmt = $testDb->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Current users in database: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check UserManager
echo "<h3>3. Testing UserManager...</h3>";
try {
    $userManager = new UserManager();
    echo "✅ UserManager created successfully<br>";
} catch (Exception $e) {
    echo "❌ UserManager creation failed: " . $e->getMessage() . "<br>";
}

// Test 4: Check table structure
echo "<h3>4. Testing table structure...</h3>";
try {
    $stmt = $testDb->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "✅ Users table structure:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
} catch (Exception $e) {
    echo "❌ Table structure check failed: " . $e->getMessage() . "<br>";
}

// Test 5: Process registration if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_register'])) {
    echo "<h3>5. Processing test registration...</h3>";
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    
    echo "Form data received:<br>";
    echo "- Username: " . htmlspecialchars($username) . "<br>";
    echo "- Email: " . htmlspecialchars($email) . "<br>";
    echo "- Password length: " . strlen($password) . "<br>";
    echo "- Full name: " . htmlspecialchars($fullName) . "<br>";
    
    // Test password validation
    echo "<br>Password validation:<br>";
    $passwordTest = Utils::validatePassword($password);
    echo "- Valid: " . ($passwordTest['valid'] ? 'Yes' : 'No') . "<br>";
    echo "- Message: " . $passwordTest['message'] . "<br>";
    
    // Test email validation
    echo "<br>Email validation:<br>";
    $emailValid = Utils::validateEmail($email);
    echo "- Valid: " . ($emailValid ? 'Yes' : 'No') . "<br>";
    
    // Check if user already exists
    echo "<br>User existence check:<br>";
    try {
        $stmt = $testDb->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $existingCount = $stmt->fetchColumn();
        echo "- Existing users with this username/email: " . $existingCount . "<br>";
    } catch (Exception $e) {
        echo "- Error checking existing users: " . $e->getMessage() . "<br>";
    }
    
    // Test password hashing
    echo "<br>Password hashing test:<br>";
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        echo "- Hash created: " . (strlen($hashedPassword) > 0 ? 'Yes' : 'No') . "<br>";
        echo "- Hash length: " . strlen($hashedPassword) . "<br>";
        
        // Test verification
        $verifyTest = password_verify($password, $hashedPassword);
        echo "- Verification test: " . ($verifyTest ? 'Pass' : 'Fail') . "<br>";
    } catch (Exception $e) {
        echo "- Password hashing error: " . $e->getMessage() . "<br>";
    }
    
    // Attempt actual registration
    echo "<br>Attempting registration:<br>";
    try {
        $result = $userManager->register($username, $email, $password, $fullName);
        echo "- Registration result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "<br>";
        echo "- Message: " . $result['message'] . "<br>";
        
        if ($result['success']) {
            echo "- ✅ Account created successfully!<br>";
            
            // Verify the user was actually created
            $stmt = $testDb->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $newUser = $stmt->fetch();
            if ($newUser) {
                echo "- ✅ User verified in database<br>";
                echo "- User ID: " . $newUser['user_id'] . "<br>";
                echo "- Created at: " . $newUser['created_at'] . "<br>";
            } else {
                echo "- ❌ User not found in database after registration<br>";
            }
        }
    } catch (Exception $e) {
        echo "- ❌ Registration exception: " . $e->getMessage() . "<br>";
        echo "- Stack trace: " . $e->getTraceAsString() . "<br>";
    }
}

// Display the debug form
?>

<h3>Test Registration Form</h3>
<form method="POST" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label>Full Name:</label><br>
        <input type="text" name="full_name" value="Test User" style="width: 300px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label>Username:</label><br>
        <input type="text" name="username" value="testuser<?php echo rand(100, 999); ?>" style="width: 300px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label>Email:</label><br>
        <input type="email" name="email" value="test<?php echo rand(100, 999); ?>@example.com" style="width: 300px; padding: 5px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label>Password:</label><br>
        <input type="password" name="password" value="TestPass123" style="width: 300px; padding: 5px;">
    </div>
    
    <button type="submit" name="test_register" style="background: #007bff; color: white; padding: 10px 20px; border: none;">
        Test Registration
    </button>
</form>

<h3>Manual Database Test</h3>
<p>You can also test manually by running this SQL in your database:</p>
<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">
INSERT INTO users (username, email, password_hash, full_name, is_active, created_at) 
VALUES ('manualtest', 'manual@test.com', '<?php echo password_hash('TestPass123', PASSWORD_DEFAULT); ?>', 'Manual Test', 1, NOW());
</pre>

<h3>Error Log Check</h3>
<p>Check your PHP error log for any additional errors. The log should be at:</p>
<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">
<?php echo ini_get('error_log') ?: '/path/to/your/error.log'; ?>
</pre>

<hr>
<p><a href="register.php">← Back to normal registration</a></p>