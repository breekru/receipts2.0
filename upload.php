<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Receipt - Receipt Logger</title>
    <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding-top: 20px;
            padding-bottom: 20px;
        }
        
        .upload-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin: 0 auto;
            max-width: 500px;
        }
        
        .drag-drop-area {
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 2rem 1rem;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .drag-drop-area:hover,
        .drag-drop-area.dragover {
            border-color: #0d6efd;
            background: rgba(13, 110, 253, 0.05);
        }
        
        .drag-drop-area.has-file {
            border-color: #198754;
            background: rgba(25, 135, 84, 0.05);
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .drag-drop-area.has-file .upload-icon {
            color: #198754;
        }
        
        .preview-container {
            position: relative;
            max-width: 100%;
            margin: 1rem 0;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .remove-preview {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #0d6efd, #6610f2);
            border: none;
            border-radius: 50px;
            padding: 12px 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .quick-amount {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        
        .quick-amount .btn {
            flex: 1;
            min-width: 60px;
            font-size: 0.875rem;
        }
        
        .progress {
            height: 6px;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .camera-capture {
            width: 100%;
            background: #198754;
            border: none;
            border-radius: 15px;
            padding: 1rem;
            color: white;
            font-weight: 600;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .camera-capture:hover {
            background: #157347;
            transform: translateY(-2px);
        }
        
        @media (max-width: 576px) {
            .upload-container {
                margin: 0 10px;
                padding: 1rem;
            }
            
            .drag-drop-area {
                padding: 1.5rem 0.5rem;
            }
            
            .upload-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <?php
    require_once 'config.php';
    Utils::requireLogin();
    
    // Get user's receipt boxes
    $userBoxes = $userManager->getUserBoxes(Utils::getCurrentUserId());
    
    if (empty($userBoxes)) {
        Utils::redirect('dashboard.php', 'Please create a receipt box first.', 'warning');
    }
    
    // Get categories
    $stmt = $db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    ?>

    <div class="container-fluid">
        <div class="upload-container">
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">
                    <i class="fas fa-receipt me-2"></i>Upload Receipt
                </h2>
                <p class="text-muted">Capture or select your receipt image</p>
            </div>
            
            <?php
            $flash = Utils::getFlashMessage();
            if ($flash['message']):
            ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form id="uploadForm" method="POST" enctype="multipart/form-data" action="upload_process.php">
                <!-- Camera Capture Button (Mobile) -->
                <button type="button" class="camera-capture d-md-none" onclick="captureFromCamera()">
                    <i class="fas fa-camera me-2"></i>Take Photo
                </button>
                
                <!-- File Upload Area -->
                <div class="drag-drop-area" id="dropArea">
                    <i class="fas fa-cloud-upload-alt upload-icon" id="uploadIcon"></i>
                    <h5 id="uploadText">Drag & Drop Receipt Here</h5>
                    <p class="text-muted mb-3" id="uploadSubtext">or click to browse files</p>
                    <input type="file" id="receiptFile" name="receipt_file" accept="image/*,.pdf" style="display: none;" required>
                    
                    <div id="previewContainer" class="preview-container d-none">
                        <img id="previewImage" class="preview-image" alt="Receipt Preview">
                        <button type="button" class="remove-preview" onclick="removeFile()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Receipt Details -->
                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <label for="box_id" class="form-label">Receipt Box *</label>
                        <select class="form-select" id="box_id" name="box_id" required>
                            <?php foreach ($userBoxes as $box): ?>
                            <option value="<?php echo $box['box_id']; ?>">
                                <?php echo htmlspecialchars($box['box_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Office Supplies - Staples">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" placeholder="0.00">
                        </div>
                        <div class="quick-amount">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(5)">$5</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(10)">$10</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(25)">$25</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(50)">$50</button>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="receipt_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="receipt_date" name="receipt_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="vendor" class="form-label">Vendor</label>
                        <input type="text" class="form-control" id="vendor" name="vendor" placeholder="e.g., Amazon, Walmart">
                    </div>
                    
                    <div class="col-12">
                        <label for="description" class="form-label">Notes</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Additional notes or description"></textarea>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div id="uploadProgress" class="progress d-none">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                
                <!-- Submit Button -->
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-upload me-2"></i>Upload Receipt
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="btn btn-link">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('receiptFile');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const uploadIcon = document.getElementById('uploadIcon');
        const uploadText = document.getElementById('uploadText');
        const uploadSubtext = document.getElementById('uploadSubtext');
        const submitBtn = document.getElementById('submitBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = uploadProgress.querySelector('.progress-bar');
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            dropArea.classList.add('dragover');
        }
        
        function unhighlight(e) {
            dropArea.classList.remove('dragover');
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        dropArea.addEventListener('click', () => fileInput.click());
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
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                    uploadIcon.style.display = 'none';
                    uploadText.textContent = file.name;
                    uploadSubtext.textContent = formatFileSize(file.size);
                    dropArea.classList.add('has-file');
                };
                reader.readAsDataURL(file);
            } else {
                // PDF file
                previewContainer.classList.add('d-none');
                uploadIcon.innerHTML = '<i class="fas fa-file-pdf"></i>';
                uploadIcon.style.display = 'block';
                uploadText.textContent = file.name;
                uploadSubtext.textContent = formatFileSize(file.size);
                dropArea.classList.add('has-file');
            }
        }
        
        function removeFile() {
            fileInput.value = '';
            previewContainer.classList.add('d-none');
            uploadIcon.innerHTML = '<i class="fas fa-cloud-upload-alt"></i>';
            uploadIcon.style.display = 'block';
            uploadText.textContent = 'Drag & Drop Receipt Here';
            uploadSubtext.textContent = 'or click to browse files';
            dropArea.classList.remove('has-file');
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function setAmount(value) {
            document.getElementById('amount').value = value;
        }
        
        function captureFromCamera() {
            fileInput.accept = 'image/*';
            fileInput.capture = 'environment';
            fileInput.click();
        }
        
        // Form submission with progress
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Please select a file to upload.');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            uploadProgress.classList.remove('d-none');
            
            // Simulate progress (in real implementation, use XMLHttpRequest for actual progress)
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress >= 90) {
                    progress = 90;
                    clearInterval(progressInterval);
                }
                progressBar.style.width = progress + '%';
            }, 200);
        });
        
        // Auto-fill title based on vendor and category
        document.getElementById('vendor').addEventListener('blur', updateTitle);
        document.getElementById('category').addEventListener('change', updateTitle);
        
        function updateTitle() {
            const vendor = document.getElementById('vendor').value;
            const category = document.getElementById('category').value;
            const titleField = document.getElementById('title');
            
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
    </script>
</body>
</html>
                