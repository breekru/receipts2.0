<?php
// edit_receipt.php - Debug version with visible debugging
require_once 'config.php';
require_login();

$user_id = get_current_user_id();
$receipt_id = (int)($_GET['id'] ?? 0);
$debug_info = [];

if (!$receipt_id) {
    redirect('dashboard.php', 'Invalid receipt ID.', 'error');
}

// Get receipt with permission check
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

if (!$receipt) {
    redirect('dashboard.php', 'Receipt not found or access denied.', 'error');
}

if ($receipt['access_level'] === 'viewer') {
    redirect('dashboard.php', 'You do not have permission to edit this receipt.', 'error');
}

$error = '';
$success = '';

// Debug info
$debug_info[] = "Receipt ID: $receipt_id";
$debug_info[] = "User ID: $user_id";
$debug_info[] = "Access Level: " . $receipt['access_level'];
$debug_info[] = "Request Method: " . $_SERVER['REQUEST_METHOD'];
$debug_info[] = "POST Data: " . json_encode($_POST);

// Handle form submission - FIXED: Check for form fields instead of hidden field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $debug_info[] = "=== FORM SUBMITTED (detected via title field) ===";
    
    try {
        $title = clean_input($_POST['title'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
        $receipt_date = !empty($_POST['receipt_date']) ? $_POST['receipt_date'] : null;
        $category = clean_input($_POST['category'] ?? '');
        $vendor = clean_input($_POST['vendor'] ?? '');
        $is_logged = isset($_POST['is_logged']) ? 1 : 0;
        
        $debug_info[] = "Title: '$title'";
        $debug_info[] = "Amount: '$amount'";
        $debug_info[] = "Date: '$receipt_date'";
        $debug_info[] = "Category: '$category'";
        $debug_info[] = "Vendor: '$vendor'";
        $debug_info[] = "Is Logged: $is_logged";
        
        if (empty($title)) {
            $error = 'Receipt title is required.';
            $debug_info[] = "ERROR: Empty title";
        } else {
            $debug_info[] = "=== CHECKING PERMISSIONS ===";
            
            // Verify permission again before update
            $perm_stmt = $pdo->prepare("
                SELECT COUNT(*) as can_edit 
                FROM receipts r 
                JOIN receipt_boxes rb ON r.box_id = rb.id 
                LEFT JOIN box_shares bs ON rb.id = bs.box_id AND bs.user_id = ?
                WHERE r.id = ? AND (rb.owner_id = ? OR (bs.user_id = ? AND bs.can_edit = 1))
            ");
            $perm_stmt->execute([$user_id, $receipt_id, $user_id, $user_id]);
            $can_edit = $perm_stmt->fetchColumn();
            
            $debug_info[] = "Permission check result: $can_edit";
            
            if (!$can_edit) {
                $error = 'Permission denied to edit this receipt.';
                $debug_info[] = "ERROR: Permission denied";
            } else {
                $debug_info[] = "=== EXECUTING UPDATE ===";
                
                $update_sql = "UPDATE receipts SET title = ?, description = ?, amount = ?, receipt_date = ?, category = ?, vendor = ?, is_logged = ? WHERE id = ?";
                $debug_info[] = "SQL: $update_sql";
                $debug_info[] = "Parameters: ['" . implode("', '", [$title, $description, ($amount ?? 'NULL'), ($receipt_date ?? 'NULL'), $category, $vendor, $is_logged, $receipt_id]) . "']";
                
                $stmt = $pdo->prepare($update_sql);
                $result = $stmt->execute([$title, $description, $amount, $receipt_date, $category, $vendor, $is_logged, $receipt_id]);
                
                $debug_info[] = "Execute result: " . ($result ? 'TRUE' : 'FALSE');
                $debug_info[] = "Affected rows: " . $stmt->rowCount();
                $debug_info[] = "Error info: " . json_encode($stmt->errorInfo());
                
                if ($result) {
                    $success = 'Receipt updated successfully! Affected rows: ' . $stmt->rowCount();
                    $debug_info[] = "SUCCESS: Receipt updated";
                    
                    // Refresh receipt data
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
                        WHERE r.id = ?
                    ");
                    $stmt->execute([$user_id, $user_id, $receipt_id]);
                    $new_receipt = $stmt->fetch();
                    
                    if ($new_receipt) {
                        $receipt = $new_receipt;
                        $debug_info[] = "Receipt data refreshed - new title: '" . $receipt['title'] . "'";
                    } else {
                        $debug_info[] = "WARNING: Could not refresh receipt data";
                    }
                    
                } else {
                    $error = 'Failed to update receipt. Database error.';
                    $debug_info[] = "ERROR: Update failed";
                    $debug_info[] = "PDO Error Info: " . json_encode($stmt->errorInfo());
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Exception: ' . $e->getMessage();
        $debug_info[] = "EXCEPTION: " . $e->getMessage();
        $debug_info[] = "TRACE: " . $e->getTraceAsString();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_info[] = "POST request received but no title field found";
}

$page_title = 'Edit Receipt (Debug)';
include 'header.php';
?>

<style>
.debug-panel {
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    font-family: monospace;
    font-size: 0.9rem;
}
.debug-panel h5 {
    color: #dc3545;
    margin-bottom: 1rem;
}
.debug-line {
    margin: 0.25rem 0;
    padding: 0.25rem;
    background: white;
    border-radius: 4px;
}
.receipt-preview {
    position: sticky;
    top: 2rem;
}
.receipt-image {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s;
}
.receipt-image:hover {
    transform: scale(1.05);
}
.edit-form {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
}
.receipt-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
</style>

<!-- Debug Panel -->
<div class="debug-panel">
    <h5><i class="fas fa-bug me-2"></i>Debug Information</h5>
    <?php foreach ($debug_info as $info): ?>
        <div class="debug-line"><?php echo htmlspecialchars($info); ?></div>
    <?php endforeach; ?>
</div>

<div class="d-flex align-items-center mb-4">
    <a href="dashboard.php?box=<?php echo $receipt['box_id']; ?>" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div>
        <h1 class="mb-1">
            <i class="fas fa-edit text-primary me-2"></i>Edit Receipt (Debug Mode)
        </h1>
        <p class="text-muted mb-0">
            From <strong><?php echo htmlspecialchars($receipt['box_name']); ?></strong>
            <span class="badge bg-primary ms-2"><?php echo ucfirst($receipt['access_level']); ?></span>
        </p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Receipt Preview -->
    <div class="col-lg-5">
        <div class="receipt-preview">
            <div class="receipt-info-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($receipt['title']); ?></h5>
                        <small class="opacity-75">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo $receipt['receipt_date'] ? date('M j, Y', strtotime($receipt['receipt_date'])) : 'No date'; ?>
                        </small>
                    </div>
                    <div class="text-end">
                        <?php if ($receipt['amount']): ?>
                        <div class="h4 mb-0">$<?php echo number_format($receipt['amount'], 2); ?></div>
                        <?php endif; ?>
                        <small class="opacity-75">
                            <i class="fas fa-<?php echo $receipt['is_logged'] ? 'check-circle' : 'clock'; ?> me-1"></i>
                            <?php echo $receipt['is_logged'] ? 'Logged' : 'Pending'; ?>
                        </small>
                    </div>
                </div>
                
                <?php if ($receipt['vendor'] || $receipt['category']): ?>
                <div class="d-flex gap-3 mb-3">
                    <?php if ($receipt['vendor']): ?>
                    <small><i class="fas fa-store me-1"></i><?php echo htmlspecialchars($receipt['vendor']); ?></small>
                    <?php endif; ?>
                    <?php if ($receipt['category']): ?>
                    <small><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($receipt['category']); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <small class="opacity-75">
                    <i class="fas fa-clock me-1"></i>
                    Uploaded <?php echo date('M j, Y g:i A', strtotime($receipt['created_at'])); ?>
                </small>
            </div>
            
            <div class="text-center">
                <?php 
                $ext = strtolower(pathinfo($receipt['file_name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                ?>
                <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                     alt="Receipt" 
                     class="receipt-image">
                <div class="mt-2">
                    <small class="text-muted">Receipt Image</small>
                </div>
                <?php else: ?>
                <div class="card p-4 text-center">
                    <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                    <h6><?php echo htmlspecialchars($receipt['file_name']); ?></h6>
                    <a href="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                       class="btn btn-outline-primary btn-sm mt-2" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>Open PDF
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Form -->
    <div class="col-lg-7">
        <div class="edit-form p-4">
            <h5 class="mb-4">
                <i class="fas fa-pencil-alt text-primary me-2"></i>Receipt Details
            </h5>
            
            <form method="POST" action="edit_receipt.php?id=<?php echo $receipt_id; ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Title *</label>
                    <input type="text" class="form-control" name="title" required
                           value="<?php echo htmlspecialchars($receipt['title']); ?>"
                           placeholder="e.g., Office Supplies - Staples">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="amount" 
                                   step="0.01" min="0" 
                                   value="<?php echo $receipt['amount'] ? number_format($receipt['amount'], 2, '.', '') : ''; ?>"
                                   placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" class="form-control" name="receipt_date" 
                               value="<?php echo $receipt['receipt_date']; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <input type="text" class="form-control" name="category" 
                               value="<?php echo htmlspecialchars($receipt['category']); ?>"
                               placeholder="e.g., Office Supplies, Meals, Travel">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Vendor</label>
                        <input type="text" class="form-control" name="vendor" 
                               value="<?php echo htmlspecialchars($receipt['vendor']); ?>"
                               placeholder="e.g., Amazon, Walmart, Starbucks">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" name="description" rows="3" 
                              placeholder="Additional notes about this receipt"><?php echo htmlspecialchars($receipt['description']); ?></textarea>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" 
                               name="is_logged" id="isLogged" 
                               <?php echo $receipt['is_logged'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="isLogged">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Mark as Logged
                        </label>
                    </div>
                </div>
                
                <input type="hidden" name="update_receipt" value="1">
                
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary btn-lg flex-fill">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="dashboard.php?box=<?php echo $receipt['box_id']; ?>" 
                       class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>