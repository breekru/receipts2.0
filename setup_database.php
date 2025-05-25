<?php
// setup_database.php - Complete database setup script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Receipt Logger Database Setup</h2>";

try {
    // Load database config
    require('/home/blkfarms/secure/db_config.php');
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Database connected successfully<br><br>";
    
    // Check if users table exists and get its structure
    echo "<h3>1. Checking Current Users Table</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Users table exists. Checking structure...<br>";
        
        // Get current columns
        $stmt = $pdo->prepare("DESCRIBE users");
        $stmt->execute();
        $currentColumns = $stmt->fetchAll();
        
        $hasEmail = false;
        $hasFullName = false;
        $hasIsActive = false;
        $hasPasswordHash = false;
        $hasPassword = false;
        
        echo "Current columns:<br>";
        foreach ($currentColumns as $column) {
            echo "• " . $column['Field'] . "<br>";
            if ($column['Field'] === 'email') $hasEmail = true;
            if ($column['Field'] === 'full_name') $hasFullName = true;
            if ($column['Field'] === 'is_active') $hasIsActive = true;
            if ($column['Field'] === 'password_hash') $hasPasswordHash = true;
            if ($column['Field'] === 'password') $hasPassword = true;
        }
        
        // Add missing columns
        echo "<br><strong>Adding missing columns:</strong><br>";
        
        if (!$hasEmail) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username");
            echo "✓ Added email column<br>";
        }
        
        // Handle password column rename
        if ($hasPassword && !$hasPasswordHash) {
            $pdo->exec("ALTER TABLE users CHANGE COLUMN password password_hash VARCHAR(255) NOT NULL");
            echo "✓ Renamed password column to password_hash<br>";
        } elseif (!$hasPasswordHash && !$hasPassword) {
            $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER email");
            echo "✓ Added password_hash column<br>";
        }
        
        if (!$hasFullName) {
            $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100) AFTER password_hash");
            echo "✓ Added full_name column<br>";
        }
        
        if (!$hasIsActive) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER full_name");
            echo "✓ Added is_active column<br>";
        }
        
        // Check if created_at and updated_at exist
        $hasCreatedAt = false;
        $hasUpdatedAt = false;
        foreach ($currentColumns as $column) {
            if ($column['Field'] === 'created_at') $hasCreatedAt = true;
            if ($column['Field'] === 'updated_at') $hasUpdatedAt = true;
        }
        
        if (!$hasCreatedAt) {
            $pdo->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_active");
            echo "✓ Added created_at column<br>";
        }
        
        if (!$hasUpdatedAt) {
            $pdo->exec("ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            echo "✓ Added updated_at column<br>";
        }
        
        echo "✓ Users table structure updated<br><br>";
        
    } else {
        echo "Creating users table...<br>";
        $createUsers = "
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
        
        $pdo->exec($createUsers);
        echo "✓ Users table created<br><br>";
    }
    
    // Create receipt_boxes table
    echo "<h3>2. Creating Receipt Boxes Table</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'receipt_boxes'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $createReceiptBoxes = "
        CREATE TABLE receipt_boxes (
            box_id INT AUTO_INCREMENT PRIMARY KEY,
            box_name VARCHAR(100) NOT NULL,
            description TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createReceiptBoxes);
        echo "✓ Receipt boxes table created<br>";
    } else {
        echo "✓ Receipt boxes table already exists<br>";
    }
    
    // Create user_box_access table
    echo "<h3>3. Creating User Box Access Table</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_box_access'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $createUserBoxAccess = "
        CREATE TABLE user_box_access (
            access_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            box_id INT NOT NULL,
            access_level ENUM('owner', 'editor', 'viewer') DEFAULT 'editor',
            granted_by INT NOT NULL,
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (box_id) REFERENCES receipt_boxes(box_id) ON DELETE CASCADE,
            FOREIGN KEY (granted_by) REFERENCES users(user_id),
            UNIQUE KEY unique_user_box (user_id, box_id)
        )";
        
        $pdo->exec($createUserBoxAccess);
        echo "✓ User box access table created<br>";
    } else {
        echo "✓ User box access table already exists<br>";
    }
    
    // Create receipts table
    echo "<h3>4. Creating Receipts Table</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'receipts'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $createReceipts = "
        CREATE TABLE receipts (
            receipt_id INT AUTO_INCREMENT PRIMARY KEY,
            box_id INT NOT NULL,
            uploaded_by INT NOT NULL,
            title VARCHAR(200),
            description TEXT,
            amount DECIMAL(10,2),
            receipt_date DATE,
            category VARCHAR(50),
            vendor VARCHAR(100),
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT,
            mime_type VARCHAR(100),
            is_logged BOOLEAN DEFAULT FALSE,
            logged_at TIMESTAMP NULL,
            logged_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (box_id) REFERENCES receipt_boxes(box_id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
            FOREIGN KEY (logged_by) REFERENCES users(user_id),
            INDEX idx_receipt_date (receipt_date),
            INDEX idx_category (category),
            INDEX idx_is_logged (is_logged)
        )";
        
        $pdo->exec($createReceipts);
        echo "✓ Receipts table created<br>";
    } else {
        echo "✓ Receipts table already exists<br>";
    }
    
    // Create categories table
    echo "<h3>5. Creating Categories Table</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'categories'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $createCategories = "
        CREATE TABLE categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(50) UNIQUE NOT NULL,
            category_color VARCHAR(7) DEFAULT '#007bff',
            is_active BOOLEAN DEFAULT TRUE
        )";
        
        $pdo->exec($createCategories);
        echo "✓ Categories table created<br>";
        
        // Insert default categories
        $defaultCategories = [
            ['Business Meals', '#28a745'],
            ['Office Supplies', '#007bff'],
            ['Travel', '#ffc107'],
            ['Equipment', '#dc3545'],
            ['Professional Services', '#6f42c1'],
            ['Marketing', '#fd7e14'],
            ['Utilities', '#20c997'],
            ['Other', '#6c757d']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (category_name, category_color) VALUES (?, ?)");
        foreach ($defaultCategories as $category) {
            $stmt->execute($category);
        }
        echo "✓ Default categories inserted<br>";
        
    } else {
        echo "✓ Categories table already exists<br>";
    }
    
    // Create additional tables
    echo "<h3>6. Creating Additional Tables</h3>";
    
    // Invitations table
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'invitations'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $createInvitations = "
        CREATE TABLE invitations (
            invitation_id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            box_id INT NOT NULL,
            invited_by INT NOT NULL,
            access_level ENUM('editor', 'viewer') DEFAULT 'editor',
            invitation_token VARCHAR(255) UNIQUE NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            accepted_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (box_id) REFERENCES receipt_boxes(box_id) ON DELETE CASCADE,
            FOREIGN KEY (invited_by) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createInvitations);
        echo "✓ Invitations table created<br>";
    } else {
        echo "✓ Invitations table already exists<br>";
    }
    
    // User sessions table
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_sessions'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $createUserSessions = "
        CREATE TABLE user_sessions (
            session_id VARCHAR(128) PRIMARY KEY,
            user_id INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createUserSessions);
        echo "✓ User sessions table created<br>";
    } else {
        echo "✓ User sessions table already exists<br>";
    }
    
    // Audit log table
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'audit_log'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $createAuditLog = "
        CREATE TABLE audit_log (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(50) NOT NULL,
            table_name VARCHAR(50) NOT NULL,
            record_id INT,
            old_values JSON,
            new_values JSON,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )";
        
        $pdo->exec($createAuditLog);
        echo "✓ Audit log table created<br>";
    } else {
        echo "✓ Audit log table already exists<br>";
    }
    
    echo "<br><h3>7. Final Test</h3>";
    
    // Test registration function
    require_once('config.php');
    $userManager = new UserManager();
    
    $testUsername = 'test_' . time();
    $testEmail = 'test_' . time() . '@example.com';
    $testPassword = 'TestPass123';
    
    try {
        $result = $userManager->register($testUsername, $testEmail, $testPassword, 'Test User');
        if ($result) {
            echo "✓ Registration test successful<br>";
            
            // Clean up test user
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$testUsername]);
            echo "✓ Test user cleaned up<br>";
        } else {
            echo "❌ Registration test failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ Registration test error: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ DATABASE SETUP COMPLETE!</strong><br>";
    echo "Your Receipt Logger database is now ready to use.<br>";
    echo "You can now test user registration on your register.php page.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ DATABASE SETUP FAILED:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
</style>