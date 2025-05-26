<?php
// boxes.php - Simple box management
require_once 'config.php';
require_login();

$user_id = get_current_user_id();
$error = '';
$success = '';

// Handle box creation
if ($_POST && isset($_POST['create_box'])) {
    $name = clean_input($_POST['name'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = 'Box name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO receipt_boxes (name, description, owner_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $user_id]);
            
            $success = 'Receipt box created successfully!';
            $_POST = []; // Clear form
        } catch (Exception $e) {
            $error = 'Failed to create box. Please try again.';
        }
    }
}

// Handle box deletion
if ($_POST && isset($_POST['delete_box'])) {
    $box_id = (int)($_POST['box_id'] ?? 0);
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM receipt_boxes WHERE id = ? AND owner_id = ?");
    $stmt->execute([$box_id, $user_id]);
    
    if (!$stmt->fetch()) {
        $error = 'Access denied.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get receipts to delete files
            $stmt = $pdo->prepare("SELECT file_path FROM receipts WHERE box_id = ?");
            $stmt->execute([$box_id]);
            $receipts = $stmt->fetchAll();
            
            // Delete receipt files
            foreach ($receipts as $receipt) {
                $file_path = __DIR__ . '/' . $receipt['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Delete box (cascades to receipts)
            $stmt = $pdo->prepare("DELETE FROM receipt_boxes WHERE id = ?");
            $stmt->execute([$box_id]);
            
            $pdo->commit();
            $success = 'Receipt box deleted successfully.';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to delete box. Please try again.';
        }
    }
}

// Get user's boxes with stats
$stmt = $pdo->prepare("
    SELECT rb.*, 
           COUNT(r.id) as receipt_count,
           SUM(CASE WHEN r.is_logged THEN 1 ELSE 0 END) as logged_count,
           COALESCE(SUM(r.amount), 0) as total_amount
    FROM receipt_boxes rb 
    LEFT JOIN receipts r ON rb.id = r.box_id 
    WHERE rb.owner_id = ? 
    GROUP BY rb.id 
    ORDER BY rb.name
");
$stmt->execute([$user_id]);
$boxes = $stmt->fetchAll();

$page_title = 'Receipt Boxes';
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="fw-bold">
            <i class="fas fa-boxes text-primary me-2"></i>Receipt Boxes
        </h1>
        <p class="text-muted">Organize your receipts into different categories or projects</p>
    </div>
    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createBoxModal">
        <i class="fas fa-plus me-2"></i>Create Box
    </button>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
</div>
<?php endif; ?>

<?php if (empty($boxes)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No receipt boxes yet</h5>
        <p class="text-muted">Create your first receipt box to start organizing your receipts</p>
        <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createBoxModal">
            <i class="fas fa-plus me-2"></i>Create Your First Box
        </button>
    </div>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($boxes as $box): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($box['name']); ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php?box=<?php echo $box['id']; ?>">
                                <i class="fas fa-eye me-2"></i>View Receipts
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item text-danger" onclick="deleteBox(<?php echo $box['id']; ?>, '<?php echo htmlspecialchars($box['name']); ?>')">
                                <i class="fas fa-trash me-2"></i>Delete Box
                            </button></li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($box['description']): ?>
                <p class="card-text text-muted"><?php echo htmlspecialchars($box['description']); ?></p>
                <?php endif; ?>
                
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="border-end">
                            <div class="h5 mb-0 text-primary"><?php echo number_format($box['receipt_count']); ?></div>
                            <small class="text-muted">Receipts</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <div class="h5 mb-0 text-success"><?php echo number_format($box['logged_count']); ?></div>
                            <small class="text-muted">Logged</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="h5 mb-0 text-info">$<?php echo number_format($box['total_amount'], 0); ?></div>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="dashboard.php?box=<?php echo $box['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-tachometer-alt me-1"></i>View Dashboard
                    </a>
                    <a href="upload.php" class="btn btn-outline-accent btn-sm">
                        <i class="fas fa-plus me-1"></i>Add Receipt
                    </a>
                </div>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    Created <?php echo date('M j, Y', strtotime($box['created_at'])); ?>
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Create Box Modal -->
<div class="modal fade" id="createBoxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Receipt Box</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Box Name *</label>
                        <input type="text" class="form-control" name="name" required 
                               placeholder="e.g., Business Expenses 2024">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Optional description for this receipt box"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_box" class="btn btn-accent">
                        <i class="fas fa-plus me-2"></i>Create Box
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Box Form (hidden) -->
<form id="deleteBoxForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_box" value="1">
    <input type="hidden" name="box_id" id="deleteBoxId">
</form>

<script>
function deleteBox(boxId, boxName) {
    if (confirm(`Are you sure you want to delete "${boxName}"?\n\nThis will permanently delete the box and ALL receipts inside it. This cannot be undone.`)) {
        document.getElementById('deleteBoxId').value = boxId;
        document.getElementById('deleteBoxForm').submit();
    }
}

// Auto-focus on box name input when modal opens
document.getElementById('createBoxModal').addEventListener('shown.bs.modal', function() {
    document.querySelector('input[name="name"]').focus();
});
</script>

<?php include 'footer.php'; ?>