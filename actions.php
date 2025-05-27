<?php
// actions.php - Handle AJAX actions
require_once 'config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = get_current_user_id();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'toggle_status':
        $receipt_id = (int)($_POST['receipt_id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        
        // Verify user has access to this receipt
        $stmt = $pdo->prepare("
            SELECT r.id FROM receipts r 
            JOIN receipt_boxes rb ON r.box_id = rb.id 
            LEFT JOIN box_shares bs ON rb.id = bs.box_id AND bs.user_id = ?
            WHERE r.id = ? AND (rb.owner_id = ? OR (bs.user_id = ? AND bs.can_edit = 1))
        ");
        $stmt->execute([$user_id, $receipt_id, $user_id, $user_id]);
        
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
            LEFT JOIN box_shares bs ON rb.id = bs.box_id AND bs.user_id = ?
            WHERE r.id = ? AND (rb.owner_id = ? OR (bs.user_id = ? AND bs.can_edit = 1))
        ");
        $stmt->execute([$user_id, $receipt_id, $user_id, $user_id]);
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
        
    case 'invite_user':
        $box_id = (int)($_POST['box_id'] ?? 0);
        $email = strtolower(trim($_POST['email'] ?? ''));
        $can_edit = (int)($_POST['can_edit'] ?? 0);
        
        // Verify user owns this box
        $stmt = $pdo->prepare("SELECT id FROM receipt_boxes WHERE id = ? AND owner_id = ?");
        $stmt->execute([$box_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        // Check if user is trying to invite themselves
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $target_user = $stmt->fetch();
        
        if ($target_user && $target_user['id'] == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Cannot invite yourself']);
            exit;
        }
        
        // If user exists, add them directly
        if ($target_user) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO box_shares (box_id, user_id, can_edit, shared_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$box_id, $target_user['id'], $can_edit, $user_id]);
                echo json_encode(['success' => true, 'message' => 'User added to box']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Already has access or error occurred']);
            }
        } else {
            // Create invitation
            try {
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO box_invitations (box_id, email, can_edit, invited_by, invitation_token, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    can_edit = VALUES(can_edit), 
                    invitation_token = VALUES(invitation_token), 
                    expires_at = VALUES(expires_at)
                ");
                $stmt->execute([$box_id, $email, $can_edit, $user_id, $token, $expires_at]);
                
                // TODO: Send email invitation here
                echo json_encode(['success' => true, 'message' => 'Invitation sent']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to create invitation']);
            }
        }
        break;
        
    case 'get_box_shares':
        $box_id = (int)($_GET['box_id'] ?? 0);
        
        // Verify user owns this box
        $stmt = $pdo->prepare("SELECT id FROM receipt_boxes WHERE id = ? AND owner_id = ?");
        $stmt->execute([$box_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        // Get current shares
        $stmt = $pdo->prepare("
            SELECT bs.user_id, bs.can_edit, u.username, u.email
            FROM box_shares bs
            JOIN users u ON bs.user_id = u.id
            WHERE bs.box_id = ?
            ORDER BY u.username
        ");
        $stmt->execute([$box_id]);
        $shares = $stmt->fetchAll();
        
        // Get pending invitations
        $stmt = $pdo->prepare("
            SELECT email, can_edit, expires_at
            FROM box_invitations
            WHERE box_id = ? AND accepted_at IS NULL AND expires_at > NOW()
            ORDER BY email
        ");
        $stmt->execute([$box_id]);
        $invitations = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'shares' => $shares,
            'invitations' => $invitations
        ]);
        break;
        
    case 'remove_share':
        $box_id = (int)($_POST['box_id'] ?? 0);
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        
        // Verify user owns this box
        $stmt = $pdo->prepare("SELECT id FROM receipt_boxes WHERE id = ? AND owner_id = ?");
        $stmt->execute([$box_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM box_shares WHERE box_id = ? AND user_id = ?");
            $stmt->execute([$box_id, $target_user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Access removed']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to remove access']);
        }
        break;
        
    case 'cancel_invitation':
        $box_id = (int)($_POST['box_id'] ?? 0);
        $email = $_POST['email'] ?? '';
        
        // Verify user owns this box
        $stmt = $pdo->prepare("SELECT id FROM receipt_boxes WHERE id = ? AND owner_id = ?");
        $stmt->execute([$box_id, $user_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM box_invitations WHERE box_id = ? AND email = ? AND invited_by = ?");
            $stmt->execute([$box_id, $email, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Invitation cancelled']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel invitation']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>