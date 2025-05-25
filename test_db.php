<?php
// test_db.php - Database connection and table test script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

try {
    // Test basic database config
    echo "<h3>1. Testing Database Configuration</h3>";
    require('/home/blkfarms/secure/db_config.php');
    echo "✓ Database config file loaded<br>";
    echo "Host: $host<br>";
    echo "Database: $dbname<br>";
    echo "User: $user<br>";
    echo "<br>";
    
    // Test PDO connection
    echo "<h3>2. Testing Database Connection</h3>";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ PDO connection successful<br><br>";
    
    // Test if users table exists
    echo "<h3>3. Checking Tables</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "✓ Users table exists<br>";
        
        // Show table structure
        $stmt = $pdo->prepare("DESCRIBE users");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<strong>Users table structure:</strong><br>";
        foreach ($columns as $column) {
            echo "• " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        echo "<br>";
        
        // Count existing users
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        echo "Current users in database: $count<br><br>";
        
    } else {
        echo "❌ Users table does NOT exist<br>";
        echo "<strong>You need to create the users table first!</strong><br><br>";
        
        echo "<h3>4. Creating Users Table</h3>";
        $createTable = "
        CREATE TABLE users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        try {
            $pdo->exec($createTable);
            echo "✓ Users table created successfully!<br>";
        } catch (Exception $e) {
            echo "❌ Failed to create users table: " . $e->getMessage() . "<br>";
        }
    }
    
    // Test other required tables
    echo "<h3>5. Checking Other Required Tables</h3>";
    $requiredTables = ['receipt_boxes', 'user_box_access', 'receipts', 'categories'];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "✓ $table table exists<br>";
        } else {
            echo "❌ $table table missing<br>";
        }
    }
    echo "<br>";
    
    // Test config.php
    echo "<h3>6. Testing Config.php Integration</h3>";
    require_once('config.php');
    echo "✓ Config.php loaded<br>";
    
    // Test UserManager
    $userManager = new UserManager();
    echo "✓ UserManager created<br>";
    
    // Test a registration (with a test user)
    echo "<h3>7. Testing Registration Function</h3>";
    $testUsername = 'test_' . time();
    $testEmail = 'test_' . time() . '@example.com';
    $testPassword = 'TestPass123';
    
    try {
        $result = $userManager->register($testUsername, $testEmail, $testPassword, 'Test User');
        if ($result) {
            echo "✓ Test registration successful<br>";
            
            // Clean up test user
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$testUsername]);
            echo "✓ Test user cleaned up<br>";
        } else {
            echo "❌ Test registration failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Registration error: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}

echo "<br><h3>Summary</h3>";
echo "If you see any ❌ errors above, those need to be fixed before registration will work.<br>";
echo "If everything shows ✓, then registration should work properly.<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
</style>