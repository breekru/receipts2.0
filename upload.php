<?php
// upload.php - Simple upload page
require_once 'config.php';
require_login();

$user_id = get_current_user_id();

// Get user's boxes
$stmt = $pdo->prepare("SELECT id, name FROM receipt_boxes WHERE owner_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$boxes = $stmt->fetchAll();

if (empty($boxes)) {
    redirect('boxes.php', 'Please create a receipt box first.', 'info');
}

$error = '';
$success = '';

// Handle upload
if ($_POST && isset($_FILES['receipt_file'])) {
    $box_id = (int)($_POST['box_id'] ?? 0);
    $title = clean_input($_POST['title'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
    $receipt_date = $_POST['receipt_date'] ?? date('Y-m-d');
    $category = clean_input($_POST['category'] ?? '');
    $vendor = clean_input($_POST['vendor'] ?? '');
    
    // Validate box access
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM receipt_boxes WHERE id = ? AND owner_id = ?");
    $stmt->execute([$box_id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        $error = 'Invalid receipt box selected.';
    } elseif (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        $file = $_FILES['receipt_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])) {
            $error = 'Invalid file type. Please upload JPG, PNG, GIF, or PDF files only.';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $error = 'File size too large. Maximum size is 10MB.';
        } else {
            // Create upload directory structure
            $upload_year = date('Y');
            $upload_month = date('m');
            $upload_dir = UPLOAD_DIR . $upload_year . '/' . $upload_month . '/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $filename = uniqid('receipt_' . $user_id . '_') . '.' . $ext;
            $filepath = $upload_dir . $filename;
            $relative_path = 'uploads/' . $upload_year . '/' . $upload_month . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Generate title if empty
                if (empty($title)) {
                    $title = $vendor ?: ($category ?: 'Receipt');
                    if ($vendor && $category) {
                        $title = $category . ' - ' . $vendor;
                    }
                }
                
                try {
                    // Insert receipt record
                    $stmt = $pdo->prepare("
                        INSERT INTO receipts (box_id, title, description, amount, receipt_date, category, vendor, file_name, file_path, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$box_id, $title, $description, $amount, $receipt_date, $category, $vendor, $filename, $relative_path, $user_id]);
                    
                    $success = 'Receipt uploaded successfully!';
                    
                    // Clear form
                    $_POST = [];
                    
                } catch (Exception $e) {
                    // Clean up file on error
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                    $error = 'Failed to save receipt. Please try again.';
                }
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        }
    }
}

$page_title = 'Upload Receipt';
include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="fas fa-cloud-upload-alt fa-3x text-accent mb-3"></i>
                    <h2 class="fw-bold">Upload Receipt</h2>
                    <p class="text-muted">Add a new receipt to your collection</p>
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
                        <a href="dashboard.php" class="btn btn-success btn-sm me-2">
                            <i class="fas fa-tachometer-alt me-1"></i>View Dashboard
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-success btn-small">
                            <i class="fas fa-plus me-1"></i>Upload Another
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Receipt Box *</label>
                        <select class="form-select" name="box_id" required>
                            <?php foreach ($boxes as $box): ?>
                            <option value="<?php echo $box['id']; ?>" 
                                    <?php echo ($_POST['box_id'] ?? '') == $box['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($box['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Receipt File *</label>
                        <input type="file" class="form-control" name="receipt_file" 
                               accept="image/*,.pdf" required>
                        <div class="form-text">JPG, PNG, GIF, or PDF files only. Max 10MB.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                               placeholder="e.g., Office Supplies - Staples">
                        <div class="form-text">Leave blank to auto-generate from vendor/category</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="amount" 
                                       step="0.01" min="0" 
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                                       placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="receipt_date" 
                                   value="<?php echo $_POST['receipt_date'] ?? date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" 
                                   value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>"
                                   placeholder="e.g., Office Supplies, Meals, Travel">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vendor</label>
                            <input type="text" class="form-control" name="vendor" 
                                   value="<?php echo htmlspecialchars($_POST['vendor'] ?? ''); ?>"
                                   placeholder="e.g., Amazon, Walmart, Starbucks">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Additional notes about this receipt"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-accent btn-lg">
                            <i class="fas fa-upload me-2"></i>Upload Receipt
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="btn btn-link">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill title based on vendor and category
document.querySelector('input[name="vendor"]').addEventListener('blur', updateTitle);
document.querySelector('input[name="category"]').addEventListener('blur', updateTitle);

function updateTitle() {
    const vendor = document.querySelector('input[name="vendor"]').value;
    const category = document.querySelector('input[name="category"]').value;
    const titleField = document.querySelector('input[name="title"]');
    
    if (!titleField.value && (vendor || category)) {
        let title = '';
        if (category && vendor) {
            title = `${category} - ${vendor}`;
        } else if (vendor) {
            title = vendor;
        } else if (category) {
            title = category;
        }
        titleField.value = title;
    }
}

// File preview
document.querySelector('input[name="receipt_file"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You could add image preview here if desired
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include 'footer.php'; ?>