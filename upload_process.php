<?php
// upload_process.php - Handle receipt uploads
require_once 'config.php';

Utils::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::redirect('upload.php', 'Invalid request method.', 'error');
}

$userId = Utils::getCurrentUserId();
$boxId = (int)$_POST['box_id'];
$title = Utils::sanitizeInput($_POST['title'] ?? '');
$description = Utils::sanitizeInput($_POST['description'] ?? '');
$amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
$receiptDate = !empty($_POST['receipt_date']) ? $_POST['receipt_date'] : date('Y-m-d');
$category = Utils::sanitizeInput($_POST['category'] ?? '');
$vendor = Utils::sanitizeInput($_POST['vendor'] ?? '');

// Validate box access
$stmt = $db->prepare("SELECT COUNT(*) FROM user_box_access WHERE user_id = ? AND box_id = ? AND access_level IN ('owner', 'editor')");
$stmt->execute([$userId, $boxId]);
if ($stmt->fetchColumn() == 0) {
    Utils::redirect('upload.php', 'You do not have permission to upload to this box.', 'error');
}

// Handle file upload
if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
    Utils::redirect('upload.php', 'Please select a file to upload.', 'error');
}

$file = $_FILES['receipt_file'];
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validate file type
if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
    Utils::redirect('upload.php', 'Invalid file type. Please upload JPG, PNG, GIF, or PDF files only.', 'error');
}

// Validate file size
if ($file['size'] > MAX_FILE_SIZE) {
    Utils::redirect('upload.php', 'File size too large. Maximum size is ' . Utils::formatFileSize(MAX_FILE_SIZE) . '.', 'error');
}

// Generate unique filename
$fileName = uniqid('receipt_' . $userId . '_') . '.' . $fileExtension;
$filePath = UPLOAD_DIR . $fileName;

// Create upload directory structure by year/month
$uploadYear = date('Y');
$uploadMonth = date('m');
$uploadDir = UPLOAD_DIR . $uploadYear . '/' . $uploadMonth . '/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileName = uniqid('receipt_' . $userId . '_') . '.' . $fileExtension;
$filePath = $uploadDir . $fileName;
$relativeFilePath = 'uploads/' . $uploadYear . '/' . $uploadMonth . '/' . $fileName;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    Utils::redirect('upload.php', 'Failed to upload file. Please try again.', 'error');
}

// Get file size and MIME type
$fileSize = filesize($filePath);
$mimeType = mime_content_type($filePath);

// Generate title if not provided
if (empty($title)) {
    $title = $vendor ?: ($category ?: 'Receipt');
    if ($vendor && $category) {
        $title = $category . ' - ' . $vendor;
    }
}

try {
    $db->beginTransaction();
    
    // Insert receipt record
    $stmt = $db->prepare("
        INSERT INTO receipts (
            box_id, uploaded_by, title, description, amount, receipt_date, 
            category, vendor, file_name, file_path, file_size, mime_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $boxId, $userId, $title, $description, $amount, $receiptDate,
        $category, $vendor, $fileName, $relativeFilePath, $fileSize, $mimeType
    ]);
    
    $receiptId = $db->lastInsertId();
    
    // Log the upload action
    $stmt = $db->prepare("
        INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address) 
        VALUES (?, 'INSERT', 'receipts', ?, ?, ?)
    ");
    
    $newValues = json_encode([
        'title' => $title,
        'amount' => $amount,
        'category' => $category,
        'vendor' => $vendor
    ]);
    
    $stmt->execute([$userId, $receiptId, $newValues, $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $db->commit();
    
    // Process image for thumbnail generation (if image)
    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
        generateThumbnail($filePath, $fileExtension);
    }
    
    Utils::redirect('dashboard.php', 'Receipt uploaded successfully!', 'success');
    
} catch (Exception $e) {
    $db->rollBack();
    
    // Clean up uploaded file on error
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    error_log("Upload error: " . $e->getMessage());
    Utils::redirect('upload.php', 'Failed to save receipt. Please try again.', 'error');
}

function generateThumbnail($filePath, $extension) {
    $thumbnailDir = dirname($filePath) . '/thumbs/';
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }
    
    $thumbnailPath = $thumbnailDir . basename($filePath);
    $maxWidth = 300;
    $maxHeight = 300;
    
    try {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($filePath);
                break;
            case 'png':
                $source = imagecreatefrompng($filePath);
                break;
            case 'gif':
                $source = imagecreatefromgif($filePath);
                break;
            default:
                return false;
        }
        
        if (!$source) return false;
        
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        
        // Calculate thumbnail dimensions
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $thumbWidth = round($originalWidth * $ratio);
        $thumbHeight = round($originalHeight * $ratio);
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }
        
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $originalWidth, $originalHeight);
        
        // Save thumbnail
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbnail, $thumbnailPath, 85);
                break;
            case 'png':
                imagepng($thumbnail, $thumbnailPath, 8);
                break;
            case 'gif':
                imagegif($thumbnail, $thumbnailPath);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Thumbnail generation error: " . $e->getMessage());
        return false;
    }
}
?>