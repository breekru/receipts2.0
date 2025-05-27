<script>
// Enhanced Mobile-Friendly Upload with Drag & Drop
const dropZone = document.getElementById('uploadDropZone');
const fileInput = document.getElementById('receiptFile');
const uploadContent = document.getElementById('uploadContent');
const uploadPreview = document.getElementById('uploadPreview');
const previewImage = document.getElementById('previewImage');
const previewFilename = document.getElementById('previewFilename');
const previewFilesize = document.getElementById('previewFilesize');

// Drag and drop functionality
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
});

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight(e) {
    dropZone.classList.add('dragover');
}

function unhighlight(e) {
    dropZone.classList.remove('dragover');
}

// Handle file drop
dropZone.addEventListener('drop', handleDrop, false);
dropZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', handleFileSelect);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
}

function handleFileSelect(e) {
    const files = e.target.files;
    handleFiles(files);
}

function handleFiles(files) {
    if (files.length > 0) {
        const file = files[0];
        if (validateFile(file)) {
            displayPreview(file);
        }
    }
}

function validateFile(file) {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!allowedTypes.includes(file.type)) {
        alert('Please select a valid image or PDF file.');
        return false;
    }
    
    if (file.size > maxSize) {
        alert('File size must be less than 10MB.');
        return false;
    }
    
    return true;
}

function displayPreview(file) {
    uploadContent.classList.add('d-none');
    uploadPreview.classList.remove('d-none');
    
    previewFilename.textContent = file.name;
    previewFilesize.textContent = formatFileSize(file.size);
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewImage.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        previewImage.classList.add('d-none');
    }
}

function removeFile() {
    fileInput.value = '';
    uploadContent.classList.remove('d-none');
    uploadPreview.classList.add('d-none');
    previewImage.src = '';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Mobile camera capture
function captureFromCamera() {
    fileInput.accept = 'image/*';
    fileInput.capture = 'environment';
    fileInput.click();
}

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

// Form submission with progress
document.querySelector('form').addEventListener('submit', function(e) {
    if (!fileInput.files.length) {
        e.preventDefault();
        alert('Please select a file to upload.');
        return;
    }
    
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
});
</script>

<style>
.upload-drop-zone {
    border: 3px dashed #dee2e6;
    border-radius: 15px;
    padding: 2rem 1rem;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-drop-zone:hover,
.upload-drop-zone.dragover {
    border-color: var(--accent-color);
    background: rgba(253, 126, 20, 0.05);
    transform: scale(1.02);
}

.upload-drop-content {
    text-align: center;
}

.upload-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 1rem;
    border-radius: 10px;
    border: 2px solid var(--accent-color);
}

.preview-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
}

.preview-info {
    flex-grow: 1;
    text-align: left;
}

.preview-filename {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.25rem;
}

.preview-filesize {
    color: #6c757d;
    font-size: 0.875rem;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .upload-drop-zone {
        padding: 1.5rem 1rem;
        min-height: 150px;
    }
    
    .upload-preview {
        flex-direction: column;
        text-align: center;
    }
    
    .preview-info {
        text-align: center;
    }
}
</style><?php
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
                        
                        <!-- Mobile Camera Capture Button -->
                        <div class="d-md-none mb-3">
                            <button type="button" class="btn btn-success w-100 btn-lg" onclick="captureFromCamera()">
                                <i class="fas fa-camera me-2"></i>Take Photo with Camera
                            </button>
                        </div>
                        
                        <!-- Drag & Drop Upload Area -->
                        <div class="upload-drop-zone" id="uploadDropZone">
                            <div class="upload-drop-content" id="uploadContent">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3" id="uploadIcon"></i>
                                <h5 id="uploadText">Drag & Drop Receipt Here</h5>
                                <p class="text-muted mb-3" id="uploadSubtext">or click to browse files</p>
                                <button type="button" class="btn btn-outline-primary">Choose File</button>
                            </div>
                            
                            <div class="upload-preview d-none" id="uploadPreview">
                                <img id="previewImage" src="" alt="Preview" class="preview-image">
                                <div class="preview-info">
                                    <div class="preview-filename" id="previewFilename"></div>
                                    <div class="preview-filesize" id="previewFilesize"></div>
                                    <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="removeFile()">
                                        <i class="fas fa-times me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <input type="file" class="d-none" name="receipt_file" id="receiptFile"
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