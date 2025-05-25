<?php
// delete_receipt.php - API endpoint for deleting receipts
require_once 'config.php';

header('Content-Type: application/json');

Utils::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!Utils::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$receiptId = (int)($_POST['receipt_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = Utils::getCurrentUserId();

if (!$receiptId || $action !== 'delete') {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Verify user has access to this receipt
    $stmt = $db->prepare("
        SELECT r.receipt_id, r.box_id, r.file_path, r.file_name, rb.box_name
        FROM receipts r
        JOIN receipt_boxes rb ON r.box_id = rb.box_id
        JOIN user_box_access uba ON r.box_id = uba.box_id
        WHERE r.receipt_id = ? AND uba.user_id = ? AND uba.access_level IN ('owner', 'editor')
    ");
    $stmt->execute([$receiptId, $userId]);
    $receipt = $stmt->fetch();
    
    if (!$receipt) {
        echo json_encode(['success' => false, 'message' => 'Receipt not found or access denied']);
        exit;
    }
    
    $db->beginTransaction();
    
    // Log the deletion
    $stmt = $db->prepare("
        INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, ip_address) 
        VALUES (?, 'DELETE', 'receipts', ?, ?, ?)
    ");
    
    $oldValues = json_encode([
        'file_name' => $receipt['file_name'],
        'box_name' => $receipt['box_name']
    ]);
    
    $stmt->execute([
        $userId, 
        $receiptId, 
        $oldValues, 
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    // Delete the receipt record
    $stmt = $db->prepare("DELETE FROM receipts WHERE receipt_id = ?");
    $stmt->execute([$receiptId]);
    
    $db->commit();
    
    // Delete the physical file (after successful database deletion)
    $filePath = $receipt['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
        
        // Also delete thumbnail if it exists
        $thumbnailPath = dirname($filePath) . '/thumbs/' . basename($filePath);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
    }
    
    Utils::logActivity("Receipt deleted: ID $receiptId, File: {$receipt['file_name']}", 'INFO');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Receipt deleted successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete receipt error: " . $e->getMessage());
    Utils::logActivity("Delete receipt error: ID $receiptId - " . $e->getMessage(), 'ERROR');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>