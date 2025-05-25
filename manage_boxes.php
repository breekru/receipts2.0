<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Receipt Boxes - Receipt Logger</title>
    <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .box-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 2rem;
        }
        
        .box-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .box-header {
            background: linear-gradient(45deg, #0d6efd, #6610f2);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }
        
        .access-level-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .owner { background: #198754; color: white; }
        .editor { background: #0d6efd; color: white; }
        .viewer { background: #6c757d; color: white; }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin-right: 0.75rem;
        }
        
        .invite-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .pending-invitation {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .stats-mini {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        
        .create-box-card {
            border: 2px dashed #dee2e6;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .create-box-card:hover {
            border-color: #0d6efd;
            background: rgba(13, 110, 253, 0.05);
            transform: none;
        }
        
        .user-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php
    require_once 'config.php';
    Utils::requireLogin();
    
    $userId = Utils::getCurrentUserId();
    $currentUser = $_SESSION['username'];
    
    // Get user's receipt boxes with stats
    $stmt = $db->prepare("
        SELECT 
            rb.*,
            uba.access_level,
            u.username as created_by_name,
            COUNT(r.receipt_id) as receipt_count,
            SUM(CASE WHEN r.is_logged THEN 1 ELSE 0 END) as logged_count,
            COALESCE(SUM(r.amount), 0) as total_amount
        FROM receipt_boxes rb
        LEFT JOIN user_box_access uba ON rb.box_id = uba.box_id AND uba.user_id = ?
        LEFT JOIN users u ON rb.created_by = u.user_id
        LEFT JOIN receipts r ON rb.box_id = r.box_id
        WHERE uba.user_id = ?
        GROUP BY rb.box_id, uba.access_level
        ORDER BY rb.box_name
    ");
    $stmt->execute([$userId, $userId]);
    $userBoxes = $stmt->fetchAll();
    ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="icons/ReceiptLogger.png" alt="Logo" width="40" height="40" class="rounded-circle me-2">
                <span class="fw-bold">Receipt Logger</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php
        $flash = Utils::getFlashMessage();
        if ($flash['message']):
        ?>
        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-boxes me-2 text-primary"></i>Receipt Boxes
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBoxModal">
                <i class="fas fa-plus me-2"></i>Create New Box
            </button>
        </div>
        
        <div class="row">
            <!-- Existing boxes -->
            <?php foreach ($userBoxes as $box): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="box-card">
                    <div class="box-header">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0"><?php echo htmlspecialchars($box['box_name']); ?></h5>
                            <span class="access-level-badge <?php echo $box['access_level']; ?>">
                                <?php echo ucfirst($box['access_level']); ?>
                            </span>
                        </div>
                        
                        <?php if ($box['description']): ?>
                        <p class="mb-3 opacity-75"><?php echo htmlspecialchars($box['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stats-mini">
                                    <div class="h4 mb-0"><?php echo number_format($box['receipt_count']); ?></div>
                                    <small>Receipts</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-mini">
                                    <div class="h4 mb-0"><?php echo number_format($box['logged_count']); ?></div>
                                    <small>Logged</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stats-mini">
                                    <div class="h4 mb-0">$<?php echo number_format($box['total_amount'], 0); ?></div>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Users with Access</h6>
                            <?php if ($box['access_level'] === 'owner'): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="showInviteModal(<?php echo $box['box_id']; ?>, '<?php echo htmlspecialchars($box['box_name']); ?>')">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="user-list" id="userList<?php echo $box['box_id']; ?>">
                            <?php
                            $stmt = $db->prepare("
                                SELECT u.username, u.full_name, uba.access_level, uba.granted_at
                                FROM user_box_access uba
                                JOIN users u ON uba.user_id = u.user_id
                                WHERE uba.box_id = ?
                                ORDER BY uba.access_level = 'owner' DESC, u.username
                            ");
                            $stmt->execute([$box['box_id']]);
                            $boxUsers = $stmt->fetchAll();
                            
                            foreach ($boxUsers as $boxUser):
                                $avatarColor = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                                $initials = strtoupper(substr($boxUser['username'], 0, 2));
                            ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="user-avatar" style="background: <?php echo $avatarColor; ?>">
                                    <?php echo $initials; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($boxUser['username']); ?></div>
                                    <?php if ($boxUser['full_name']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($boxUser['full_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="access-level-badge <?php echo $boxUser['access_level']; ?>">
                                    <?php echo ucfirst($boxUser['access_level']); ?>
                                </span>
                                <?php if ($box['access_level'] === 'owner' && $boxUser['access_level'] !== 'owner'): ?>
                                <button class="btn btn-sm btn-outline-danger ms-2 btn-icon" onclick="removeUserAccess(<?php echo $box['box_id']; ?>, '<?php echo $boxUser['username']; ?>')">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pending invitations -->
                        <?php if ($box['access_level'] === 'owner'): ?>
                        <div id="pendingInvitations<?php echo $box['box_id']; ?>">
                            <?php
                            $stmt = $db->prepare("
                                SELECT email, access_level, created_at, expires_at
                                FROM invitations
                                WHERE box_id = ? AND accepted_at IS NULL AND expires_at > NOW()
                                ORDER BY created_at DESC
                            ");
                            $stmt->execute([$box['box_id']]);
                            $pendingInvites = $stmt->fetchAll();
                            
                            if (!empty($pendingInvites)):
                            ?>
                            <h6 class="mt-3 mb-2">Pending Invitations</h6>
                            <?php foreach ($pendingInvites as $invite): ?>
                            <div class="pending-invitation">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($invite['email']); ?></strong>
                                        <span class="access-level-badge <?php echo $invite['access_level']; ?> ms-2">
                                            <?php echo ucfirst($invite['access_level']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Expires: <?php echo date('M j, Y', strtotime($invite['expires_at'])); ?>
                                        </small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger" onclick="cancelInvitation('<?php echo $invite['email']; ?>', <?php echo $box['box_id']; ?>)">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 d-flex gap-2">
                            <a href="dashboard.php?box_id=<?php echo $box['box_id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View Receipts
                            </a>
                            <?php if ($box['access_level'] === 'owner'): ?>
                            <button class="btn btn-outline-secondary btn-sm" onclick="editBox(<?php echo $box['box_id']; ?>, '<?php echo htmlspecialchars($box['box_name']); ?>', '<?php echo htmlspecialchars($box['description']); ?>')">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Create new box card -->
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="box-card create-box-card" data-bs-toggle="modal" data-bs-target="#createBoxModal">
                    <div class="text-center">
                        <i class="fas fa-plus fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Create New Receipt Box</h5>
                        <p class="text-muted">Organize receipts by project, client, or category</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Box Modal -->
    <div class="modal fade" id="createBoxModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Receipt Box</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="manage_boxes.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="boxName" class="form-label">Box Name *</label>
                            <input type="text" class="form-control" id="boxName" name="box_name" required placeholder="e.g., Business Expenses 2024">
                        </div>
                        <div class="mb-3">
                            <label for="boxDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="boxDescription" name="description" rows="3" placeholder="Optional description for this receipt box"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_box" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create Box
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Invite User Modal -->
    <div class="modal fade" id="inviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inviteModalTitle">Invite User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="manage_boxes.php">
                    <input type="hidden" id="inviteBoxId" name="box_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="inviteEmail" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="inviteEmail" name="invite_email" required placeholder="user@example.com">
                        </div>
                        <div class="mb-3">
                            <label for="accessLevel" class="form-label">Access Level</label>
                            <select class="form-select" id="accessLevel" name="access_level">
                                <option value="editor">Editor - Can upload and edit receipts</option>
                                <option value="viewer">Viewer - Can only view receipts</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            An invitation email will be sent to the user. They'll need to accept it to gain access.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="invite_user" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Box Modal -->
    <div class="modal fade" id="editBoxModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Receipt Box</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="manage_boxes.php">
                    <input type="hidden" id="editBoxId" name="box_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editBoxName" class="form-label">Box Name *</label>
                            <input type="text" class="form-control" id="editBoxName" name="box_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editBoxDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editBoxDescription" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_box" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function showInviteModal(boxId, boxName) {
            document.getElementById('inviteBoxId').value = boxId;
            document.getElementById('inviteModalTitle').textContent = `Invite User to "${boxName}"`;
            new bootstrap.Modal(document.getElementById('inviteModal')).show();
        }
        
        function editBox(boxId, boxName, description) {
            document.getElementById('editBoxId').value = boxId;
            document.getElementById('editBoxName').value = boxName;
            document.getElementById('editBoxDescription').value = description;
            new bootstrap.Modal(document.getElementById('editBoxModal')).show();
        }
        
        function removeUserAccess(boxId, username) {
            if (confirm(`Are you sure you want to remove ${username}'s access to this receipt box?`)) {
                fetch('manage_boxes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `remove_user=1&box_id=${boxId}&username=${encodeURIComponent(username)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing user access.');
                });
            }
        }
        
        function cancelInvitation(email, boxId) {
            if (confirm(`Are you sure you want to cancel the invitation for ${email}?`)) {
                fetch('manage_boxes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cancel_invitation=1&box_id=${boxId}&email=${encodeURIComponent(email)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while canceling the invitation.');
                });
            }
        }
    </script>

    <?php
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create_box'])) {
            $boxName = Utils::sanitizeInput($_POST['box_name']);
            $description = Utils::sanitizeInput($_POST['description']);
            
            if (empty($boxName)) {
                Utils::redirect('manage_boxes.php', 'Box name is required.', 'error');
            }
            
            $boxId = $userManager->createBox($userId, $boxName, $description);
            if ($boxId) {
                Utils::redirect('manage_boxes.php', 'Receipt box created successfully!', 'success');
            } else {
                Utils::redirect('manage_boxes.php', 'Failed to create receipt box.', 'error');
            }
        }
        
        if (isset($_POST['invite_user'])) {
            $boxId = (int)$_POST['box_id'];
            $email = Utils::sanitizeInput($_POST['invite_email']);
            $accessLevel = $_POST['access_level'];
            
            // Verify user owns this box
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_box_access WHERE user_id = ? AND box_id = ? AND access_level = 'owner'");
            $stmt->execute([$userId, $boxId]);
            
            if ($stmt->fetchColumn() == 0) {
                Utils::redirect('manage_boxes.php', 'You do not have permission to invite users to this box.', 'error');
            }
            
            if (!Utils::validateEmail($email)) {
                Utils::redirect('manage_boxes.php', 'Please enter a valid email address.', 'error');
            }
            
            $token = $userManager->inviteUser($email, $boxId, $userId, $accessLevel);
            if ($token) {
                // Send invitation email (implement email sending)
                Utils::redirect('manage_boxes.php', 'Invitation sent successfully!', 'success');
            } else {
                Utils::redirect('manage_boxes.php', 'Failed to send invitation.', 'error');
            }
        }
        
        if (isset($_POST['update_box'])) {
            $boxId = (int)$_POST['box_id'];
            $boxName = Utils::sanitizeInput($_POST['box_name']);
            $description = Utils::sanitizeInput($_POST['description']);
            
            // Verify user owns this box
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_box_access WHERE user_id = ? AND box_id = ? AND access_level = 'owner'");
            $stmt->execute([$userId, $boxId]);
            
            if ($stmt->fetchColumn() == 0) {
                Utils::redirect('manage_boxes.php', 'You do not have permission to edit this box.', 'error');
            }
            
            $stmt = $db->prepare("UPDATE receipt_boxes SET box_name = ?, description = ? WHERE box_id = ?");
            if ($stmt->execute([$boxName, $description, $boxId])) {
                Utils::redirect('manage_boxes.php', 'Receipt box updated successfully!', 'success');
            } else {
                Utils::redirect('manage_boxes.php', 'Failed to update receipt box.', 'error');
            }
        }
        
        // AJAX endpoints
        if (isset($_POST['remove_user'])) {
            header('Content-Type: application/json');
            
            $boxId = (int)$_POST['box_id'];
            $username = $_POST['username'];
            
            try {
                // Verify ownership
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_box_access WHERE user_id = ? AND box_id = ? AND access_level = 'owner'");
                $stmt->execute([$userId, $boxId]);
                
                if ($stmt->fetchColumn() == 0) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    exit;
                }
                
                // Get user ID
                $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $targetUserId = $stmt->fetchColumn();
                
                if (!$targetUserId) {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    exit;
                }
                
                // Remove access
                $stmt = $db->prepare("DELETE FROM user_box_access WHERE user_id = ? AND box_id = ?");
                $stmt->execute([$targetUserId, $boxId]);
                
                echo json_encode(['success' => true]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            exit;
        }
        
        if (isset($_POST['cancel_invitation'])) {
            header('Content-Type: application/json');
            
            $boxId = (int)$_POST['box_id'];
            $email = $_POST['email'];
            
            try {
                // Verify ownership
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_box_access WHERE user_id = ? AND box_id = ? AND access_level = 'owner'");
                $stmt->execute([$userId, $boxId]);
                
                if ($stmt->fetchColumn() == 0) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied']);
                    exit;
                }
                
                // Cancel invitation
                $stmt = $db->prepare("DELETE FROM invitations WHERE email = ? AND box_id = ? AND invited_by = ?");
                $stmt->execute([$email, $boxId, $userId]);
                
                echo json_encode(['success' => true]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            exit;
        }
    }
    ?>
</body>
</html>