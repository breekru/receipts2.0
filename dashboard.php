<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Receipt Logger</title>
    <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(45deg, #0d6efd, #6610f2) !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card.total { border-left-color: #0d6efd; }
        .stats-card.logged { border-left-color: #198754; }
        .stats-card.pending { border-left-color: #ffc107; }
        .stats-card.amount { border-left-color: #6610f2; }
        
        .receipt-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 1rem;
        }
        
        .receipt-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .receipt-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .receipt-thumbnail:hover {
            transform: scale(1.05);
        }
        
        .receipt-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-logged {
            background: #d1eddb;
            color: #0a3622;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #664d03;
        }
        
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .btn-filter {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
        }
        
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .receipt-modal img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 10px;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }
        
        .table td {
            border: none;
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #f8f9fa;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .filter-section {
                padding: 1rem;
            }
            
            .receipt-card .card-body {
                padding: 1rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
        }
        
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 15px;
        }
        
        .zoom-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1060;
        }
        
        .zoom-controls .btn {
            margin: 0 2px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            padding: 0;
        }
    </style>
</head>
<body>
    <?php
    require_once 'config.php';
    Utils::requireLogin();
    
    $userId = Utils::getCurrentUserId();
    $currentUser = $_SESSION['username'];
    
    // Get user's receipt boxes
    $userBoxes = $userManager->getUserBoxes($userId);
    $selectedBoxId = $_GET['box_id'] ?? ($userBoxes[0]['box_id'] ?? null);
    
    if (!$selectedBoxId) {
        Utils::redirect('setup.php', 'Please create a receipt box first.', 'info');
    }
    
    // Apply filters
    $filters = [
        'category' => $_GET['category'] ?? '',
        'status' => $_GET['status'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'search' => $_GET['search'] ?? '',
        'vendor' => $_GET['vendor'] ?? ''
    ];
    
    // Build query
    $whereConditions = ["r.box_id = ?"];
    $params = [$selectedBoxId];
    
    if ($filters['category']) {
        $whereConditions[] = "r.category = ?";
        $params[] = $filters['category'];
    }
    
    if ($filters['status'] === 'logged') {
        $whereConditions[] = "r.is_logged = 1";
    } elseif ($filters['status'] === 'pending') {
        $whereConditions[] = "r.is_logged = 0";
    }
    
    if ($filters['date_from']) {
        $whereConditions[] = "r.receipt_date >= ?";
        $params[] = $filters['date_from'];
    }
    
    if ($filters['date_to']) {
        $whereConditions[] = "r.receipt_date <= ?";
        $params[] = $filters['date_to'];
    }
    
    if ($filters['search']) {
        $whereConditions[] = "(r.title LIKE ? OR r.description LIKE ? OR r.vendor LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($filters['vendor']) {
        $whereConditions[] = "r.vendor LIKE ?";
        $params[] = '%' . $filters['vendor'] . '%';
    }
    
    // Get receipts
    $sql = "
        SELECT r.*, u.username as uploaded_by_name,
               CASE WHEN r.is_logged THEN 'Logged' ELSE 'Pending' END as status_text
        FROM receipts r
        LEFT JOIN users u ON r.uploaded_by = u.user_id
        WHERE " . implode(' AND ', $whereConditions) . "
        ORDER BY r.receipt_date DESC, r.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $receipts = $stmt->fetchAll();
    
    // Get statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_receipts,
            SUM(CASE WHEN is_logged THEN 1 ELSE 0 END) as logged_receipts,
            SUM(CASE WHEN NOT is_logged THEN 1 ELSE 0 END) as pending_receipts,
            COALESCE(SUM(amount), 0) as total_amount
        FROM receipts 
        WHERE box_id = ?
    ";
    $stmt = $db->prepare($statsQuery);
    $stmt->execute([$selectedBoxId]);
    $stats = $stmt->fetch();
    
    // Get categories for filter
    $stmt = $db->prepare("SELECT DISTINCT category FROM receipts WHERE box_id = ? AND category IS NOT NULL AND category != '' ORDER BY category");
    $stmt->execute([$selectedBoxId]);
    $availableCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get vendors for filter
    $stmt = $db->prepare("SELECT DISTINCT vendor FROM receipts WHERE box_id = ? AND vendor IS NOT NULL AND vendor != '' ORDER BY vendor");
    $stmt->execute([$selectedBoxId]);
    $availableVendors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="icons/ReceiptLogger.png" alt="Logo" class="me-2">
                <span class="fw-bold">Receipt Logger</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="boxDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-box me-1"></i>Receipt Boxes
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($userBoxes as $box): ?>
                            <li>
                                <a class="dropdown-item <?php echo $box['box_id'] == $selectedBoxId ? 'active' : ''; ?>" 
                                   href="?box_id=<?php echo $box['box_id']; ?>">
                                    <?php echo htmlspecialchars($box['box_name']); ?>
                                    <small class="text-muted">(<?php echo ucfirst($box['access_level']); ?>)</small>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="manage_boxes.php"><i class="fas fa-cog me-1"></i>Manage Boxes</a></li>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($currentUser); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php
        $flash = Utils::getFlashMessage();
        if ($flash['message']):
        ?>
        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : 'success'); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card total">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-primary"><?php echo number_format($stats['total_receipts']); ?></h3>
                            <p class="text-muted mb-0">Total Receipts</p>
                        </div>
                        <i class="fas fa-receipt fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card logged">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-success"><?php echo number_format($stats['logged_receipts']); ?></h3>
                            <p class="text-muted mb-0">Logged</p>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card pending">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-warning"><?php echo number_format($stats['pending_receipts']); ?></h3>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                        <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card amount">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-purple">$<?php echo number_format($stats['total_amount'], 2); ?></h3>
                            <p class="text-muted mb-0">Total Amount</p>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-purple opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <input type="hidden" name="box_id" value="<?php echo $selectedBoxId; ?>">
                
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Title, description, vendor...">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($availableCategories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filters['category'] === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="logged" <?php echo $filters['status'] === 'logged' ? 'selected' : ''; ?>>Logged</option>
                        <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="?box_id=<?php echo $selectedBoxId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Receipts Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="100">Preview</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receipts)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No receipts found matching your criteria.</p>
                            <a href="upload.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Upload First Receipt
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($receipts as $receipt): ?>
                    <tr>
                        <td>
                            <?php if (in_array(pathinfo($receipt['file_name'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                                 class="receipt-thumbnail" 
                                 alt="Receipt" 
                                 data-bs-toggle="modal" 
                                 data-bs-target="#receiptModal"
                                 data-image="<?php echo htmlspecialchars($receipt['file_path']); ?>"
                                 data-title="<?php echo htmlspecialchars($receipt['title']); ?>">
                            <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light receipt-thumbnail">
                                <i class="fas fa-file-pdf fa-2x text-danger"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($receipt['title']); ?></strong>
                            <?php if ($receipt['description']): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($receipt['description'], 0, 50)) . (strlen($receipt['description']) > 50 ? '...' : ''); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($receipt['amount']): ?>
                            <strong class="text-success">$<?php echo number_format($receipt['amount'], 2); ?></strong>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($receipt['receipt_date'])); ?></td>
                        <td>
                            <?php if ($receipt['category']): ?>
                            <span class="category-badge bg-light text-dark"><?php echo htmlspecialchars($receipt['category']); ?></span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $receipt['vendor'] ? htmlspecialchars($receipt['vendor']) : '<span class="text-muted">-</span>'; ?></td>
                        <td>
                            <span class="receipt-status <?php echo $receipt['is_logged'] ? 'status-logged' : 'status-pending'; ?>">
                                <?php echo $receipt['status_text']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="quick-actions">
                                <?php if (!$receipt['is_logged']): ?>
                                <button class="btn btn-sm btn-success" onclick="markAsLogged(<?php echo $receipt['receipt_id']; ?>)">
                                    <i class="fas fa-check"></i> Log
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-warning" onclick="markAsUnlogged(<?php echo $receipt['receipt_id']; ?>)">
                                    <i class="fas fa-undo"></i> Unlog
                                </button>
                                <?php endif; ?>
                                
                                <a href="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   download="<?php echo htmlspecialchars($receipt['file_name']); ?>">
                                    <i class="fas fa-download"></i>
                                </a>
                                
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="editReceipt(<?php echo $receipt['receipt_id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" onclick="window.location.href='upload.php'">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalTitle">Receipt Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center position-relative">
                    <div class="zoom-controls">
                        <button type="button" class="btn btn-sm btn-light" onclick="zoomIn()">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light" onclick="zoomOut()">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light" onclick="resetZoom()">
                            <i class="fas fa-compress-arrows-alt"></i>
                        </button>
                    </div>
                    <img id="receiptModalImage" src="" alt="Receipt" style="transform-origin: center; transition: transform 0.3s ease;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentZoom = 1;
        let isDragging = false;
        let startX, startY, translateX = 0, translateY = 0;
        
        // Modal image handling
        document.getElementById('receiptModal').addEventListener('show.bs.modal', function(event) {
            const trigger = event.relatedTarget;
            const imageSrc = trigger.getAttribute('data-image');
            const title = trigger.getAttribute('data-title');
            
            const modalImage = document.getElementById('receiptModalImage');
            const modalTitle = document.getElementById('receiptModalTitle');
            
            modalImage.src = imageSrc;
            modalTitle.textContent = title;
            
            // Reset zoom and position
            currentZoom = 1;
            translateX = 0;
            translateY = 0;
            updateImageTransform();
        });
        
        // Zoom functions
        function zoomIn() {
            currentZoom = Math.min(currentZoom * 1.2, 3);
            updateImageTransform();
        }
        
        function zoomOut() {
            currentZoom = Math.max(currentZoom / 1.2, 0.5);
            updateImageTransform();
        }
        
        function resetZoom() {
            currentZoom = 1;
            translateX = 0;
            translateY = 0;
            updateImageTransform();
        }
        
        function updateImageTransform() {
            const image = document.getElementById('receiptModalImage');
            image.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoom})`;
        }
        
        // Dragging functionality
        const modalImage = document.getElementById('receiptModalImage');
        
        modalImage.addEventListener('mousedown', (e) => {
            if (currentZoom > 1) {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                modalImage.style.cursor = 'grabbing';
            }
        });
        
        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                updateImageTransform();
            }
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
            modalImage.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        });
        
        // Wheel zoom
        modalImage.addEventListener('wheel', (e) => {
            e.preventDefault();
            if (e.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        });
        
        // Receipt actions
        function markAsLogged(receiptId) {
            fetch('update_receipt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receipt_id=${receiptId}&action=mark_logged`
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
                alert('An error occurred while updating the receipt.');
            });
        }
        
        function markAsUnlogged(receiptId) {
            fetch('update_receipt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receipt_id=${receiptId}&action=mark_unlogged`
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
                alert('An error occurred while updating the receipt.');
            });
        }
        
        function editReceipt(receiptId) {
            window.location.href = `edit_receipt.php?id=${receiptId}`;
        }
        
        // Auto-refresh every 5 minutes to keep session alive
        setInterval(() => {
            fetch('keep_alive.php').catch(() => {});
        }, 300000);
    </script>
</body>
</html>