<?php
// edit_receipt.php - Edit receipt details
require_once 'config.php';
require_login();

$user_id = get_current_user_id();
$receipt_id = (int)($_GET['id'] ?? 0);

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

// Handle form submission
if ($_POST && isset($_POST['update_receipt'])) {
    $title = clean_input($_POST['title'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
    $receipt_date = $_POST['receipt_date'] ?? null;
    $category = clean_input($_POST['category'] ?? '');
    $vendor = clean_input($_POST['vendor'] ?? '');
    $is_logged = isset($_POST['is_logged']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Receipt title is required.';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE receipts 
                SET title = ?, description = ?, amount = ?, receipt_date = ?, 
                    category = ?, vendor = ?, is_logged = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $amount, $receipt_date, $category, $vendor, $is_logged, $receipt_id]);
            
            $success = 'Receipt updated successfully!';
            
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
            $receipt = $stmt->fetch();
            
        } catch (Exception $e) {
            $error = 'Failed to update receipt. Please try again.';
        }
    }
}

$page_title = 'Edit Receipt';
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
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
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
            </h5>
            
            <form method="POST">
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
                               placeholder="e.g., Office Supplies, Meals, Travel"
                               list="categoryList">
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
                        <input class="form-check-input status-toggle" type="checkbox" 
                               name="is_logged" id="isLogged" 
                               <?php echo $receipt['is_logged'] ? 'checked' : ''; ?>>
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
                    <button type="submit" name="update_receipt" class="btn btn-primary btn-lg flex-fill">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="dashboard.php?box=<?php echo $receipt['box_id']; ?>" 
                       class="btn btn-outline-secondary btn-lg">
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
// Image modal functionality
function openImageModal(imagePath, title) {
    document.getElementById('modalImage').src = imagePath;
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

// Form enhancement
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save draft functionality
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', debounce(saveDraft, 1000));
    });
    
    function saveDraft() {
        const formData = new FormData(form);
        const draftData = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== 'update_receipt') {
                draftData[key] = value;
            }
        }
        
        localStorage.setItem('receipt_edit_draft_<?php echo $receipt_id; ?>', JSON.stringify(draftData));
    }
    
    // Load draft on page load
    function loadDraft() {
        const draft = localStorage.getItem('receipt_edit_draft_<?php echo $receipt_id; ?>');
        if (draft) {
            const draftData = JSON.parse(draft);
            Object.keys(draftData).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = draftData[key] === 'on';
                    } else {
                        input.value = draftData[key];
                    }
                }
            });
        }
    }
    
    // Clear draft on successful save
    form.addEventListener('submit', function() {
        localStorage.removeItem('receipt_edit_draft_<?php echo $receipt_id; ?>');
    });
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Smart category suggestions based on vendor
    const vendorInput = document.querySelector('input[name="vendor"]');
    const categoryInput = document.querySelector('input[name="category"]');
    
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
    
    if (vendorInput && categoryInput) {
        vendorInput.addEventListener('blur', function() {
            const vendor = this.value.toLowerCase();
            if (!categoryInput.value && vendor) {
                for (const [key, category] of Object.entries(vendorCategoryMap)) {
                    if (vendor.includes(key)) {
                        categoryInput.value = category;
                        break;
                    }
                }
            }
        });
    }
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const title = document.querySelector('input[name="title"]').value.trim();
        if (!title) {
            e.preventDefault();
            alert('Please enter a title for the receipt.');
            document.querySelector('input[name="title"]').focus();
            return false;
        }
        
        // Show loading state
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S or Cmd+S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.querySelector('button[type="submit"]').click();
    }
    
    // Escape to cancel
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        if (modals.length === 0) {
            window.location.href = 'dashboard.php?box=<?php echo $receipt['box_id']; ?>';
        }
    }
});
</script>

<?php include 'footer.php'; ?>