<?php
// edit_receipt.php - Fixed version with better data persistence
require_once 'config.php';
require_login();

$user_id = get_current_user_id();
$receipt_id = (int)($_GET['id'] ?? 0);

if (!$receipt_id) {
    redirect('dashboard.php', 'Invalid receipt ID.', 'error');
}

$error = '';
$success = '';

// Function to get fresh receipt data
function getReceiptData($pdo, $receipt_id, $user_id) {
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
    return $stmt->fetch();
}

// Get initial receipt data
$receipt = getReceiptData($pdo, $receipt_id, $user_id);

if (!$receipt) {
    redirect('dashboard.php', 'Receipt not found or access denied.', 'error');
}

if ($receipt['access_level'] === 'viewer') {
    redirect('dashboard.php', 'You do not have permission to edit this receipt.', 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['csrf_token'])) {
    // CSRF protection
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            // Start transaction for data consistency
            $pdo->beginTransaction();
            
            $title = clean_input($_POST['title'] ?? '');
            $description = clean_input($_POST['description'] ?? '');
            $amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
            $receipt_date = !empty($_POST['receipt_date']) ? $_POST['receipt_date'] : null;
            $category = clean_input($_POST['category'] ?? '');
            $vendor = clean_input($_POST['vendor'] ?? '');
            $is_logged = isset($_POST['is_logged']) ? 1 : 0;
            
            if (empty($title)) {
                throw new Exception('Receipt title is required.');
            }
            
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
            
            if (!$can_edit) {
                throw new Exception('Permission denied to edit this receipt.');
            }
            
            // Update receipt with explicit field mapping
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
            
            if (!$result) {
                throw new Exception('Database update failed: ' . implode(', ', $stmt->errorInfo()));
            }
            
            $affected_rows = $stmt->rowCount();
            if ($affected_rows === 0) {
                // Check if receipt still exists
                $check_stmt = $pdo->prepare("SELECT id FROM receipts WHERE id = ?");
                $check_stmt->execute([$receipt_id]);
                if (!$check_stmt->fetch()) {
                    throw new Exception('Receipt no longer exists.');
                }
                // If receipt exists but no rows affected, data might be identical
                error_log("Receipt update: No rows affected for receipt ID $receipt_id");
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Clear any cached data
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate(__FILE__);
            }
            
            $success = 'Receipt updated successfully!';
            
            // Get fresh data after update to ensure we show current values
            $receipt = getReceiptData($pdo, $receipt_id, $user_id);
            
            if (!$receipt) {
                throw new Exception('Failed to reload receipt data after update.');
            }
            
            // Log successful update
            error_log("Receipt updated successfully: ID $receipt_id, User $user_id");
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Receipt update error: " . $e->getMessage());
            $error = $e->getMessage();
            
            // Get fresh data even on error to ensure form shows current state
            $receipt = getReceiptData($pdo, $receipt_id, $user_id);
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = 'Edit Receipt';

// Add cache-busting headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

include 'header.php';
?>

<style>
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

.status-toggle {
    transform: scale(1.2);
}

.form-modified {
    border-left: 4px solid #ffc107;
    background-color: #fff3cd;
}

@media (max-width: 768px) {
    .receipt-preview {
        position: static;
        margin-bottom: 2rem;
    }
    
    .receipt-image {
        max-height: 250px;
    }
}
</style>

<div class="d-flex align-items-center mb-4">
    <a href="dashboard.php?box=<?php echo $receipt['box_id']; ?>" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div>
        <h1 class="mb-1">
            <i class="fas fa-edit text-primary me-2"></i>Edit Receipt
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
    <div class="mt-2">
        <a href="dashboard.php?box=<?php echo $receipt['box_id']; ?>" class="btn btn-success btn-sm">
            <i class="fas fa-tachometer-alt me-1"></i>Back to Dashboard
        </a>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Receipt Preview -->
    <div class="col-lg-5">
        <div class="receipt-preview">
            <div class="receipt-info-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1" id="preview-title"><?php echo htmlspecialchars($receipt['title']); ?></h5>
                        <small class="opacity-75">
                            <i class="fas fa-calendar me-1"></i>
                            <span id="preview-date"><?php echo $receipt['receipt_date'] ? date('M j, Y', strtotime($receipt['receipt_date'])) : 'No date'; ?></span>
                        </small>
                    </div>
                    <div class="text-end">
                        <div class="h4 mb-0" id="preview-amount">
                            <?php echo $receipt['amount'] ? '$' . number_format($receipt['amount'], 2) : 'No amount'; ?>
                        </div>
                        <small class="opacity-75">
                            <i class="fas fa-clock me-1" id="preview-status-icon"></i>
                            <span id="preview-status"><?php echo $receipt['is_logged'] ? 'Logged' : 'Pending'; ?></span>
                        </small>
                    </div>
                </div>
                
                <div class="d-flex gap-3 mb-3">
                    <small id="preview-vendor"><?php echo $receipt['vendor'] ? '<i class="fas fa-store me-1"></i>' . htmlspecialchars($receipt['vendor']) : ''; ?></small>
                    <small id="preview-category"><?php echo $receipt['category'] ? '<i class="fas fa-tag me-1"></i>' . htmlspecialchars($receipt['category']) : ''; ?></small>
                </div>
                
                <small class="opacity-75">
                    <i class="fas fa-clock me-1"></i>
                    Uploaded <?php echo date('M j, Y g:i A', strtotime($receipt['created_at'])); ?>
                    <?php if (isset($receipt['updated_at']) && $receipt['updated_at'] && $receipt['updated_at'] !== $receipt['created_at']): ?>
                    <br><i class="fas fa-edit me-1"></i>
                    Updated <?php echo date('M j, Y g:i A', strtotime($receipt['updated_at'])); ?>
                    <?php endif; ?>
                </small>
            </div>
            
            <div class="text-center">
                <?php 
                $ext = strtolower(pathinfo($receipt['file_name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                ?>
                <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>?v=<?php echo time(); ?>" 
                     alt="Receipt" 
                     class="receipt-image"
                     onclick="openImageModal('<?php echo htmlspecialchars($receipt['file_path']); ?>', '<?php echo htmlspecialchars($receipt['title']); ?>')">
                <div class="mt-2">
                    <small class="text-muted">Click to view full size</small>
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
                <span id="unsaved-indicator" class="badge bg-warning ms-2 d-none">Unsaved Changes</span>
            </h5>
            
            <form method="POST" action="edit_receipt.php?id=<?php echo $receipt_id; ?>" id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Title *</label>
                    <input type="text" class="form-control" name="title" id="title" required
                           value="<?php echo htmlspecialchars($receipt['title']); ?>"
                           placeholder="e.g., Office Supplies - Staples"
                           data-original="<?php echo htmlspecialchars($receipt['title']); ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="amount" id="amount"
                                   step="0.01" min="0" 
                                   value="<?php echo $receipt['amount'] ? number_format($receipt['amount'], 2, '.', '') : ''; ?>"
                                   placeholder="0.00"
                                   data-original="<?php echo $receipt['amount'] ? number_format($receipt['amount'], 2, '.', '') : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" class="form-control" name="receipt_date" id="receipt_date"
                               value="<?php echo $receipt['receipt_date']; ?>"
                               data-original="<?php echo $receipt['receipt_date']; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <input type="text" class="form-control" name="category" id="category"
                               value="<?php echo htmlspecialchars($receipt['category']); ?>"
                               placeholder="e.g., Office Supplies, Meals, Travel"
                               list="categoryList"
                               data-original="<?php echo htmlspecialchars($receipt['category']); ?>">
                        <datalist id="categoryList">
                            <option value="Office Supplies">
                            <option value="Meals & Entertainment">
                            <option value="Travel">
                            <option value="Equipment">
                            <option value="Utilities">
                            <option value="Marketing">
                            <option value="Professional Services">
                            <option value="Software">
                            <option value="Insurance">
                            <option value="Maintenance">
                        </datalist>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Vendor</label>
                        <input type="text" class="form-control" name="vendor" id="vendor"
                               value="<?php echo htmlspecialchars($receipt['vendor']); ?>"
                               placeholder="e.g., Amazon, Walmart, Starbucks"
                               data-original="<?php echo htmlspecialchars($receipt['vendor']); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" name="description" id="description" rows="3" 
                              placeholder="Additional notes about this receipt"
                              data-original="<?php echo htmlspecialchars($receipt['description']); ?>"><?php echo htmlspecialchars($receipt['description']); ?></textarea>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input status-toggle" type="checkbox" 
                               name="is_logged" id="isLogged" 
                               <?php echo $receipt['is_logged'] ? 'checked' : ''; ?>
                               data-original="<?php echo $receipt['is_logged'] ? '1' : '0'; ?>">
                        <label class="form-check-label fw-semibold" for="isLogged">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Mark as Logged
                        </label>
                        <div class="form-text">
                            Check this box if you've already recorded this expense in your accounting system.
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary btn-lg flex-fill" id="saveBtn">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="dashboard.php?box=<?php echo $receipt['box_id']; ?>" 
                       class="btn btn-outline-secondary btn-lg" id="cancelBtn">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Danger Zone -->
        <div class="card border-danger mt-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Permanently delete this receipt. This action cannot be undone.
                </p>
                <button class="btn btn-outline-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash me-2"></i>Delete Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="imageModalTitle">Receipt Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center p-0">
                <img id="modalImage" src="" alt="Receipt" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-3">
                    <i class="fas fa-trash fa-3x text-danger mb-3"></i>
                    <h5>Are you sure you want to delete this receipt?</h5>
                    <p class="text-muted">
                        This will permanently delete "<strong><?php echo htmlspecialchars($receipt['title']); ?></strong>" 
                        and its associated file. This action cannot be undone.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="deleteReceipt()">
                    <i class="fas fa-trash me-2"></i>Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let formModified = false;
let originalFormData = {};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const inputs = form.querySelectorAll('input, textarea, select');
    const unsavedIndicator = document.getElementById('unsaved-indicator');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Store original form data
    inputs.forEach(input => {
        if (input.type === 'checkbox') {
            originalFormData[input.name] = input.checked;
        } else {
            originalFormData[input.name] = input.value;
        }
    });
    
    // Monitor form changes
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            checkFormModified();
            updatePreview();
        });
        
        input.addEventListener('change', function() {
            checkFormModified();
            updatePreview();
        });
    });
    
    function checkFormModified() {
        let modified = false;
        inputs.forEach(input => {
            let currentValue, originalValue;
            
            if (input.type === 'checkbox') {
                currentValue = input.checked;
                originalValue = originalFormData[input.name];
            } else {
                currentValue = input.value;
                originalValue = originalFormData[input.name] || '';
            }
            
            if (currentValue !== originalValue) {
                modified = true;
            }
        });
        
        formModified = modified;
        
        if (modified) {
            unsavedIndicator.classList.remove('d-none');
            saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes*';
            form.classList.add('form-modified');
        } else {
            unsavedIndicator.classList.add('d-none');
            saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes';
            form.classList.remove('form-modified');
        }
    }
    
    function updatePreview() {
        // Update live preview
        const title = document.getElementById('title').value;
        const amount = document.getElementById('amount').value;
        const receiptDate = document.getElementById('receipt_date').value;
        const category = document.getElementById('category').value;
        const vendor = document.getElementById('vendor').value;
        const isLogged = document.getElementById('isLogged').checked;
        
        document.getElementById('preview-title').textContent = title || 'Untitled Receipt';
        
        if (amount) {
            document.getElementById('preview-amount').textContent = '$' + parseFloat(amount).toFixed(2);
        } else {
            document.getElementById('preview-amount').textContent = 'No amount';
        }
        
        if (receiptDate) {
            const date = new Date(receiptDate);
            document.getElementById('preview-date').textContent = date.toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric'
            });
        } else {
            document.getElementById('preview-date').textContent = 'No date';
        }
        
        document.getElementById('preview-vendor').innerHTML = vendor ? 
            `<i class="fas fa-store me-1"></i>${vendor}` : '';
        document.getElementById('preview-category').innerHTML = category ? 
            `<i class="fas fa-tag me-1"></i>${category}` : '';
        
        const statusIcon = document.getElementById('preview-status-icon');
        const statusText = document.getElementById('preview-status');
        if (isLogged) {
            statusIcon.className = 'fas fa-check-circle me-1';
            statusText.textContent = 'Logged';
        } else {
            statusIcon.className = 'fas fa-clock me-1';
            statusText.textContent = 'Pending';
        }
    }
    
    // Warn about unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (formModified) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
    
    // Handle cancel button
    cancelBtn.addEventListener('click', function(e) {
        if (formModified) {
            if (!confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        if (!title) {
            e.preventDefault();
            alert('Please enter a title for the receipt.');
            document.getElementById('title').focus();
            return false;
        }
        
        // Show loading state
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        
        // Clear unsaved changes flag
        formModified = false;
    });
    
    // Smart category suggestions based on vendor
    const vendorInput = document.getElementById('vendor');
    const categoryInput = document.getElementById('category');
    
    const vendorCategoryMap = {
        'amazon': 'Office Supplies',
        'staples': 'Office Supplies',
        'office depot': 'Office Supplies',
        'starbucks': 'Meals & Entertainment',
        'mcdonalds': 'Meals & Entertainment',
        'subway': 'Meals & Entertainment',
        'uber': 'Travel',
        'lyft': 'Travel',
        'shell': 'Travel',
        'exxon': 'Travel',
        'microsoft': 'Software',
        'adobe': 'Software',
        'google': 'Software',
        'verizon': 'Utilities',
        'at&t': 'Utilities',
        'comcast': 'Utilities'
    };
    
    vendorInput.addEventListener('blur', function() {
        const vendor = this.value.toLowerCase();
        if (!categoryInput.value && vendor) {
            for (const [key, category] of Object.entries(vendorCategoryMap)) {
                if (vendor.includes(key)) {
                    categoryInput.value = category;
                    checkFormModified();
                    updatePreview();
                    break;
                }
            }
        }
    });
});

// Image modal functionality
function openImageModal(imagePath, title) {
    document.getElementById('modalImage').src = imagePath + '?v=' + Date.now();
    document.getElementById('imageModalTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

// Delete confirmation
function confirmDelete() {
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function deleteReceipt() {
    const deleteBtn = event.target;
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    
    fetch('actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete_receipt&receipt_id=<?php echo $receipt_id; ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear unsaved changes flag before redirect
            formModified = false;
            window.location.href = 'dashboard.php?box=<?php echo $receipt['box_id']; ?>';
        } else {
            alert('Error: ' + data.message);
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Delete Permanently';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the receipt.');
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Delete Permanently';
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S or Cmd+S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('saveBtn').click();
    }
    
    // Escape to cancel
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        if (modals.length === 0) {
            if (formModified) {
                if (confirm('You have unsaved changes. Are you sure you want to leave?')) {
                    window.location.href = 'dashboard.php?box=<?php echo $receipt['box_id']; ?>';
                }
            } else {
                window.location.href = 'dashboard.php?box=<?php echo $receipt['box_id']; ?>';
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>