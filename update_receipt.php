<?php
// update_receipt.php - API endpoint for updating receipt status
require_once 'config.php';

header('Content-Type: application/json');

Utils::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$receiptId = (int)($_POST['receipt_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = Utils::getCurrentUserId();

if (!$receiptId || !in_array($action, ['mark_logged', 'mark_unlogged'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Verify user has access to this receipt
    $stmt = $db->prepare("
        SELECT r.receipt_id, r.box_id, r.is_logged 
        FROM receipts r
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
    
    // Update receipt status
    $newStatus = ($action === 'mark_logged') ? 1 : 0;
    $loggedAt = ($action === 'mark_logged') ? date('Y-m-d H:i:s') : null;
    $loggedBy = ($action === 'mark_logged') ? $userId : null;
    
    $stmt = $db->prepare("
        UPDATE receipts 
        SET is_logged = ?, logged_at = ?, logged_by = ?, updated_at = CURRENT_TIMESTAMP
        WHERE receipt_id = ?
    ");
    $stmt->execute([$newStatus, $loggedAt, $loggedBy, $receiptId]);
    
    // Log the action
    $stmt = $db->prepare("
        INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address) 
        VALUES (?, ?, 'receipts', ?, ?, ?, ?)
    ");
    
    $oldValues = json_encode(['is_logged' => $receipt['is_logged']]);
    $newValues = json_encode(['is_logged' => $newStatus]);
    
    $stmt->execute([
        $userId, 
        strtoupper($action), 
        $receiptId, 
        $oldValues, 
        $newValues, 
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $action === 'mark_logged' ? 'Receipt marked as logged' : 'Receipt marked as unlogged',
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Update receipt error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>