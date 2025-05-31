<?php
// check_schema.php - Check database schema and test updates
require_once 'config.php';

header('Content-Type: application/json');

$results = [];

try {
    // Check if updated_at column exists
    $stmt = $pdo->query("DESCRIBE receipts");
    $columns = $stmt->fetchAll();
    
    $results['table_structure'] = $columns;
    
    $has_updated_at = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'updated_at') {
            $has_updated_at = true;
            $results['updated_at_column'] = $column;
            break;
        }
    }
    
    $results['has_updated_at'] = $has_updated_at;
    
    // Check triggers
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'receipts'");
    $triggers = $stmt->fetchAll();
    $results['triggers'] = $triggers;
    
    // Test a sample receipt update
    if (isset($_GET['test_update']) && $_GET['test_update'] === '1') {
        $stmt = $pdo->query("SELECT id FROM receipts LIMIT 1");
        $test_receipt = $stmt->fetch();
        
        if ($test_receipt) {
            $receipt_id = $test_receipt['id'];
            
            // Get current data
            $stmt = $pdo->prepare("SELECT title, updated_at FROM receipts WHERE id = ?");
            $stmt->execute([$receipt_id]);
            $before = $stmt->fetch();
            
            // Update with a test change
            $new_title = $before['title'] . ' [TEST_' . time() . ']';
            $stmt = $pdo->prepare("UPDATE receipts SET title = ?, updated_at = NOW() WHERE id = ?");
            $update_result = $stmt->execute([$new_title, $receipt_id]);
            
            // Get updated data
            $stmt = $pdo->prepare("SELECT title, updated_at FROM receipts WHERE id = ?");
            $stmt->execute([$receipt_id]);
            $after = $stmt->fetch();
            
            // Revert the change
            $stmt = $pdo->prepare("UPDATE receipts SET title = ? WHERE id = ?");
            $stmt->execute([$before['title'], $receipt_id]);
            
            $results['update_test'] = [
                'receipt_id' => $receipt_id,
                'before' => $before,
                'after' => $after,
                'update_successful' => $update_result,
                'timestamp_changed' => $before['updated_at'] !== $after['updated_at']
            ];
        }
    }
    
    // Check MySQL version
    $stmt = $pdo->query("SELECT VERSION() as mysql_version");
    $version = $stmt->fetch();
    $results['mysql_version'] = $version['mysql_version'];
    
    // Check current timezone
    $stmt = $pdo->query("SELECT NOW() as server_time, @@session.time_zone as timezone");
    $time_info = $stmt->fetch();
    $results['time_info'] = $time_info;
    
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>

<!-- 
To use this script:
1. Save as check_schema.php in your LogIt directory
2. Visit: check_schema.php (shows schema info)
3. Visit: check_schema.php?test_update=1 (tests actual update)
-->