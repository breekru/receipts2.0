<?php
// debug_sharing.php - Debug script for box sharing issues
require_once 'config.php';
require_login();

$user_id = get_current_user_id();

echo "<h2>Box Sharing Debug Information</h2>";
echo "<p>Current User ID: $user_id</p>";

// Check if tables exist
echo "<h3>1. Checking Database Tables</h3>";
try {
    $tables_to_check = ['box_shares', 'box_invitations'];
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch() ? 'EXISTS' : 'MISSING';
        echo "<p>Table '$table': <strong>$exists</strong></p>";
        
        if ($exists === 'EXISTS') {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<ul>";
            foreach ($columns as $col) {
                echo "<li>{$col['Field']} - {$col['Type']}</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking tables: " . $e->getMessage() . "</p>";
}

// Check current user's boxes
echo "<h3>2. User's Boxes</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM receipt_boxes WHERE owner_id = ?");
    $stmt->execute([$user_id]);
    $boxes = $stmt->fetchAll();
    
    if (empty($boxes)) {
        echo "<p>No boxes found for this user.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Created</th></tr>";
        foreach ($boxes as $box) {
            echo "<tr>";
            echo "<td>{$box['id']}</td>";
            echo "<td>{$box['name']}</td>";
            echo "<td>{$box['description']}</td>";
            echo "<td>{$box['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting boxes: " . $e->getMessage() . "</p>";
}

// Check box shares
echo "<h3>3. Box Shares</h3>";
try {
    $stmt = $pdo->query("SELECT bs.*, rb.name as box_name, u.username FROM box_shares bs 
                         JOIN receipt_boxes rb ON bs.box_id = rb.id 
                         JOIN users u ON bs.user_id = u.id");
    $shares = $stmt->fetchAll();
    
    if (empty($shares)) {
        echo "<p>No box shares found in the system.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Box ID</th><th>Box Name</th><th>User</th><th>Can Edit</th><th>Created</th></tr>";
        foreach ($shares as $share) {
            echo "<tr>";
            echo "<td>{$share['box_id']}</td>";
            echo "<td>{$share['box_name']}</td>";
            echo "<td>{$share['username']}</td>";
            echo "<td>" . ($share['can_edit'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$share['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting box shares: " . $e->getMessage() . "</p>";
}

// Check box invitations
echo "<h3>4. Box Invitations</h3>";
try {
    $stmt = $pdo->query("SELECT bi.*, rb.name as box_name, u.username as invited_by_name 
                         FROM box_invitations bi 
                         JOIN receipt_boxes rb ON bi.box_id = rb.id 
                         JOIN users u ON bi.invited_by = u.id");
    $invitations = $stmt->fetchAll();
    
    if (empty($invitations)) {
        echo "<p>No box invitations found in the system.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Box Name</th><th>Email</th><th>Can Edit</th><th>Invited By</th><th>Expires</th><th>Accepted</th></tr>";
        foreach ($invitations as $inv) {
            echo "<tr>";
            echo "<td>{$inv['box_name']}</td>";
            echo "<td>{$inv['email']}</td>";
            echo "<td>" . ($inv['can_edit'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$inv['invited_by_name']}</td>";
            echo "<td>{$inv['expires_at']}</td>";
            echo "<td>" . ($inv['accepted_at'] ? $inv['accepted_at'] : 'Not accepted') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting box invitations: " . $e->getMessage() . "</p>";
}

// Check all users
echo "<h3>5. All Users</h3>";
try {
    $stmt = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Created</th></tr>";
    foreach ($users as $user) {
        $current = ($user['id'] == $user_id) ? ' (YOU)' : '';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}{$current}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting users: " . $e->getMessage() . "</p>";
}

// Test form for manual sharing
if (!empty($boxes)) {
    echo "<h3>6. Test Sharing (Manual)</h3>";
    echo "<form method='post'>";
    echo "<select name='test_box_id'>";
    foreach ($boxes as $box) {
        echo "<option value='{$box['id']}'>{$box['name']}</option>";
    }
    echo "</select>";
    echo "<input type='email' name='test_email' placeholder='Email to share with' required>";
    echo "<select name='test_can_edit'>";
    echo "<option value='0'>View Only</option>";
    echo "<option value='1'>Can Edit</option>";
    echo "</select>";
    echo "<button type='submit' name='test_share'>Test Share</button>";
    echo "</form>";
    
    if (isset($_POST['test_share'])) {
        $test_box_id = (int)$_POST['test_box_id'];
        $test_email = strtolower(trim($_POST['test_email']));
        $test_can_edit = (int)$_POST['test_can_edit'];
        
        echo "<h4>Test Share Results:</h4>";
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$test_email]);
        $target_user = $stmt->fetch();
        
        if ($target_user) {
            echo "<p>Target user found with ID: {$target_user['id']}</p>";
            
            try {
                $stmt = $pdo->prepare("INSERT INTO box_shares (box_id, user_id, can_edit, shared_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$test_box_id, $target_user['id'], $test_can_edit, $user_id]);
                echo "<p style='color: green;'>Share created successfully!</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error creating share: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>User with email '$test_email' not found. Creating invitation...</p>";
            
            try {
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                $stmt = $pdo->prepare("INSERT INTO box_invitations (box_id, email, can_edit, invited_by, invitation_token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$test_box_id, $test_email, $test_can_edit, $user_id, $token, $expires_at]);
                echo "<p style='color: green;'>Invitation created successfully!</p>";
                echo "<p>Invitation token: $token</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error creating invitation: " . $e->getMessage() . "</p>";
            }
        }
    }
}

echo "<hr>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a> | <a href='boxes.php'>Back to Boxes</a></p>";
?>