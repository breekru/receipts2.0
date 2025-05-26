<?php
// actions.php - Handle AJAX actions
require_once 'config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = get_current_user_id();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_status':
        $receipt_id = (int)($_POST['receipt_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        // Verify user owns this receipt
        $stmt = $pdo->prepare("
            SELECT r.id FROM receipts r 
            JOIN receipt_boxes rb ON r.box_id = rb.id 
            WHERE r.id = ? AND rb.owner_id = ?
        ");
        $stmt->execute([$receipt_id, $user_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE receipts SET is_logged = ? WHERE id = ?");
            $stmt->execute([$status, $receipt_id]);
            
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        break;
        
    case 'delete_receipt':
        $receipt_id = (int)($_POST['receipt_id'] ?? 0);
        
        // Get receipt info and verify ownership
        $stmt = $pdo->prepare("
            SELECT r.id, r.file_path FROM receipts r 
            JOIN receipt_boxes rb ON r.box_id = rb.id 
            WHERE r.id = ? AND rb.owner_id = ?
        ");
        $stmt->execute([$receipt_id, $user_id]);
        $receipt = $stmt->fetch();
        
        if (!$receipt) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM receipts WHERE id = ?");
            $stmt->execute([$receipt_id]);
            
            // Delete file
            $file_path = __DIR__ . '/' . $receipt['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            echo json_encode(['success' => true, 'message' => 'Receipt deleted']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>