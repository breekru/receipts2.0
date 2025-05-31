<?php
// debug_receipt_edit.php - Debug script to troubleshoot edit issues
require_once 'config.php';
require_login();

$user_id = get_current_user_id();
$receipt_id = (int)($_GET['id'] ?? 0);

if (!$receipt_id) {
    die('No receipt ID provided');
}

// Debug information
$debug_info = [
    'receipt_id' => $receipt_id,
    'user_id' => $user_id,
    'timestamp' => date('Y-m-d H:i:s'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'session_data' => [
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'username' => $_SESSION['username'] ?? 'not set',
        'last_activity' => $_SESSION['last_activity'] ?? 'not set'
    ]
];

// Get current receipt data
try {
    $stmt = $pdo->prepare("
        SELECT r.*, rb.name as box_name, rb.owner_id,
               CASE 
                   WHEN rb.owner_id = ? THEN 'owner'
                   WHEN bs.can_edit = 1 THEN 'editor'
                   ELSE 'viewer'
               END as access_level
        FROM receipts r 
        JOIN receipt_boxes rb ON r.box_id = rb.id 
        LEFT JOIN box_shares bs ON rb.id = bs.box_id AND bs.user_id = ?
        WHERE r.id = ? AND (rb.owner_id = ? OR bs.user_id = ?)
    ");
    $stmt->execute([$user_id, $user_id, $receipt_id, $user_id, $user_id]);
    $receipt = $stmt->fetch();
    
    $debug_info['receipt_data'] = $receipt;
    $debug_info['receipt_found'] = $receipt ? 'yes' : 'no';
    
} catch (Exception $e) {
    $debug_info['database_error'] = $e->getMessage();
}

// Check if this is a POST request (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $debug_info['form_submission'] = 'yes';
    
    // Test the update query
    try {
        $title = clean_input($_POST['title'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
        $receipt_date = !empty($_POST['receipt_date']) ? $_POST['receipt_date'] : null;
        $category = clean_input($_POST['category'] ?? '');
        $vendor = clean_input($_POST['vendor'] ?? '');
        $is_logged = isset($_POST['is_logged']) ? 1 : 0;
        
        $debug_info['processed_data'] = [
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'receipt_date' => $receipt_date,
            'category' => $category,
            'vendor' => $vendor,
            'is_logged' => $is_logged
        ];
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Execute update
        $stmt = $pdo->prepare("
            UPDATE receipts 
            SET title = ?, 
                description = ?, 
                amount = ?, 
                receipt_date = ?, 
                category = ?, 
                vendor = ?, 
                is_logged = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $title, 
            $description, 
            $amount, 
            $receipt_date, 
            $category, 
            $vendor, 
            $is_logged, 
            $receipt_id
        ]);
        
        $debug_info['update_result'] = $result ? 'success' : 'failed';
        $debug_info['affected_rows'] = $stmt->rowCount();
        $debug_info['error_info'] = $stmt->errorInfo();
        
        if ($result) {
            $pdo->commit();
            
            // Get the updated data immediately
            $stmt = $pdo->prepare("
                SELECT r.*, rb.name as box_name, rb.owner_id
                FROM receipts r 
                JOIN receipt_boxes rb ON r.box_id = rb.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$receipt_id]);
            $updated_receipt = $stmt->fetch();
            
            $debug_info['updated_receipt_data'] = $updated_receipt;
            
        } else {
            $pdo->rollBack();
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $debug_info['update_error'] = $e->getMessage();
    }
}

// Check for caching headers
$debug_info['response_headers'] = [];
foreach (headers_list() as $header) {
    $debug_info['response_headers'][] = $header;
}

// Check PHP configuration
$debug_info['php_config'] = [
    'opcache_enabled' => function_exists('opcache_get_status') ? opcache_get_status() : 'not available',
    'output_buffering' => ini_get('output_buffering'),
    'session_cache_limiter' => session_cache_limiter(),
    'session_cache_expire' => session_cache_expire()
];

// Check database connection
try {
    $stmt = $pdo->query("SELECT CONNECTION_ID(), NOW() as db_current_time");
    $db_info = $stmt->fetch();
    $debug_info['database_connection'] = $db_info;
} catch (Exception $e) {
    $debug_info['database_connection_error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>