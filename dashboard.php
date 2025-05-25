<?php
// dashboard.php - Main Dashboard
require_once 'config.php';
Utils::requireLogin();

$userId = Utils::getCurrentUserId();
$currentUser = Utils::getCurrentUsername();

// Get user's receipt boxes
$userBoxes = $userManager->getUserBoxes($userId);
$selectedBoxId = $_GET['box_id'] ?? ($userBoxes[0]['box_id'] ?? null);

if (!$selectedBoxId) {
    Utils::redirect('manage_boxes.php', 'Please create a receipt box first.', 'info');
}

// Verify user has access to selected box
$hasAccess = false;
foreach ($userBoxes as $box) {
    if ($box['box_id'] == $selectedBoxId) {
        $hasAccess = true;
        $currentBox = $box;
        break;
    }
}

if (!$hasAccess) {
    Utils::redirect('dashboard.php?box_id=' . $userBoxes[0]['box_id'], 'Invalid receipt box selected.', 'error');
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

// Build query conditions
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

// Get receipts with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total receipts
$countSql = "
    SELECT COUNT(*) 
    FROM receipts r
    WHERE " . implode(' AND ', $whereConditions);
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalReceipts = $stmt->fetchColumn();
$totalPages = ceil($totalReceipts / $perPage);

// Get receipts for current page
$sql = "
    SELECT r.*, u.username as uploaded_by_name,
           CASE WHEN r.is_logged THEN 'Logged' ELSE 'Pending' END as status_text
    FROM receipts r
    LEFT JOIN users u ON r.uploaded_by = u.user_id
    WHERE " . implode(' AND ', $whereConditions) . "
    ORDER BY r.receipt_date DESC, r.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $perPage;
$params[] = $offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$receipts = $stmt->fetchAll();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_receipts,
        SUM(CASE WHEN is_logged THEN 1 ELSE 0 END) as logged_receipts,
        SUM(CASE WHEN NOT is_logged THEN 1 ELSE 0 END) as pending_receipts,
        COALESCE(SUM(amount), 0) as total_amount,
        COUNT(DISTINCT category) as categories_count,
        COUNT(DISTINCT vendor) as vendors_count
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
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderHeader('Dashboard', 'Manage and organize your receipts with LogIt'); ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="icons/LogIt.png" alt="LogIt">
                <span class="fw-bold">LogIt</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="boxDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-box me-1"></i><?php echo htmlspecialchars($currentBox['box_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($userBoxes as $box): ?>
                            <li>
                                <a class="dropdown-item <?php echo $box['box_id'] == $selectedBoxId ? 'active' : ''; ?>" 
                                   href="?box_id=<?php echo $box['box_id']; ?>">
                                    <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($box['box_name']); ?>
                                    <small class="text-muted d-block"><?php echo ucfirst($box['access_level']); ?> access</small>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="manage_boxes.php"><i class="fas fa-cog me-2"></i>Manage Boxes</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="fas fa-upload me-1"></i>Upload
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
        <!-- Flash Messages -->
        <?php
        $flash = Utils::getFlashMessage();
        if ($flash['message']):
        ?>
        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : 'success'); ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $flash['type'] === 'error' ? 'exclamation-triangle' : ($flash['type'] === 'warning' ? 'exclamation-circle' : 'check-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-4 fw-bold mb-2">
                    <i class="fas fa-tachometer-alt text-accent me-3"></i>Dashboard
                </h1>
                <p class="lead text-muted">
                    Managing receipts in <strong><?php echo htmlspecialchars($currentBox['box_name']); ?></strong>
                    <?php if ($currentBox['description']): ?>
                    - <?php echo htmlspecialchars($currentBox['description']); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card total">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-primary"><?php echo number_format($stats['total_receipts']); ?></h3>
                            <p class="text-muted mb-0">Total Receipts</p>
                        </div>
                        <i class="fas fa-receipt fa-2x text-primary opacity-25"></i>
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
                        <i class="fas fa-check-circle fa-2x text-success opacity-25"></i>
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
                        <i class="fas fa-clock fa-2x text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card amount">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-accent">$<?php echo number_format($stats['total_amount'], 2); ?></h3>
                            <p class="text-muted mb-0">Total Amount</p>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-accent opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tags fa-2x text-info me-3"></i>
                            <div>
                                <h5 class="mb-0"><?php echo number_format($stats['categories_count']); ?> Categories</h5>
                                <small class="text-muted">Different expense types</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-store fa-2x text-info me-3"></i>
                            <div>
                                <h5 class="mb-0"><?php echo number_format($stats['vendors_count']); ?> Vendors</h5>
                                <small class="text-muted">Different suppliers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-filter text-accent me-2"></i>Filter Receipts
                </h5>
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
                        <button type="submit" class="btn btn-accent me-2" title="Apply Filters">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="?box_id=<?php echo $selectedBoxId; ?>" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Summary -->
        <?php if (array_filter($filters)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Showing <?php echo number_format(count($receipts)); ?> of <?php echo number_format($totalReceipts); ?> receipts
            <?php if ($filters['search']): ?>
            matching "<?php echo htmlspecialchars($filters['search']); ?>"
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Receipts Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Recent Receipts
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="view" id="tableView" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="tableView"><i class="fas fa-table"></i></label>
                        
                        <input type="radio" class="btn-check" name="view" id="cardView" autocomplete="off">
                        <label class="btn btn-outline-primary" for="cardView"><i class="fas fa-th-large"></i></label>
                    </div>
                </div>
            </div>
            
            <!-- Table View -->
            <div id="tableViewContent" class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="100">Preview</th>
                            <th>Title</th>
                            <th width="120">Amount</th>
                            <th width="120">Date</th>
                            <th width="120">Category</th>
                            <th width="120">Vendor</th>
                            <th width="100">Status</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($receipts)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No receipts found</h5>
                                <p class="text-muted">
                                    <?php if (array_filter($filters)): ?>
                                    No receipts match your current filters.
                                    <a href="?box_id=<?php echo $selectedBoxId; ?>" class="text-decoration-none">Clear filters</a>
                                    <?php else: ?>
                                    Start by uploading your first receipt!
                                    <?php endif; ?>
                                </p>
                                <a href="upload.php" class="btn btn-accent">
                                    <i class="fas fa-plus me-1"></i>Upload Receipt
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($receipts as $receipt): ?>
                        <tr>
                            <td>
                                <?php if (in_array(strtolower(pathinfo($receipt['file_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                                     class="receipt-thumbnail" 
                                     alt="Receipt Preview" 
                                     data-bs-toggle="modal" 
                                     data-bs-target="#receiptModal"
                                     data-image="<?php echo htmlspecialchars($receipt['file_path']); ?>"
                                     data-title="<?php echo htmlspecialchars($receipt['title']); ?>"
                                     loading="lazy">
                                <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light receipt-thumbnail">
                                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($receipt['title']); ?></div>
                                <?php if ($receipt['description']): ?>
                                <small class="text-muted d-block">
                                    <?php echo htmlspecialchars(substr($receipt['description'], 0, 60)) . (strlen($receipt['description']) > 60 ? '...' : ''); ?>
                                </small>
                                <?php endif; ?>
                                <small class="text-muted">
                                    by <?php echo htmlspecialchars($receipt['uploaded_by_name']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($receipt['amount']): ?>
                                <span class="fw-bold text-success">$<?php echo number_format($receipt['amount'], 2); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="d-block"><?php echo date('M j, Y', strtotime($receipt['receipt_date'])); ?></span>
                                <small class="text-muted"><?php echo date('g:i A', strtotime($receipt['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($receipt['category']): ?>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($receipt['category']); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($receipt['vendor']): ?>
                                <?php echo htmlspecialchars($receipt['vendor']); ?>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="receipt-status <?php echo $receipt['is_logged'] ? 'status-logged' : 'status-pending'; ?>">
                                    <?php echo $receipt['status_text']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if (!$receipt['is_logged']): ?>
                                    <button class="btn btn-success" 
                                            onclick="markAsLogged(<?php echo $receipt['receipt_id']; ?>)"
                                            title="Mark as Logged">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-warning" 
                                            onclick="markAsUnlogged(<?php echo $receipt['receipt_id']; ?>)"
                                            title="Mark as Unlogged">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                                       class="btn btn-outline-primary" 
                                       download="<?php echo htmlspecialchars($receipt['file_name']); ?>"
                                       title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    <button class="btn btn-outline-secondary" 
                                            onclick="editReceipt(<?php echo $receipt['receipt_id']; ?>)"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button class="btn btn-outline-danger" 
                                            onclick="deleteReceipt(<?php echo $receipt['receipt_id']; ?>)"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Card View (Hidden by default) -->
            <div id="cardViewContent" class="d-none">
                <div class="row p-3">
                    <?php foreach ($receipts as $receipt): ?>
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="receipt-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-4">
                                        <?php if (in_array(strtolower(pathinfo($receipt['file_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                                             class="receipt-thumbnail w-100" 
                                             alt="Receipt Preview"
                                             data-bs-toggle="modal" 
                                             data-bs-target="#receiptModal"
                                             data-image="<?php echo htmlspecialchars($receipt['file_path']); ?>"
                                             data-title="<?php echo htmlspecialchars($receipt['title']); ?>"
                                             loading="lazy">
                                        <?php else: ?>
                                        <div class="receipt-thumbnail w-100 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-8">
                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($receipt['title']); ?></h6>
                                        <?php if ($receipt['amount']): ?>
                                        <div class="h5 text-success mb-2">$<?php echo number_format($receipt['amount'], 2); ?></div>
                                        <?php endif; ?>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($receipt['receipt_date'])); ?>
                                                <?php if ($receipt['vendor']): ?>
                                                <br><?php echo htmlspecialchars($receipt['vendor']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                        <span class="receipt-status <?php echo $receipt['is_logged'] ? 'status-logged' : 'status-pending'; ?>">
                                            <?php echo $receipt['status_text']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Receipt pagination">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>"><?php echo $totalPages; ?></a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" onclick="window.location.href='upload.php'" aria-label="Upload Receipt">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Receipt Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center position-relative">
                    <div class="position-absolute top-0 end-0 p-3" style="z-index: 1060;">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-light" onclick="zoomIn()" title="Zoom In">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light" onclick="zoomOut()" title="Zoom Out">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light" onclick="resetZoom()" title="Reset Zoom">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>
                        </div>
                    </div>
                    <img id="receiptModalImage" 
                         src="" 
                         alt="Receipt" 
                         class="img-fluid"
                         style="transform-origin: center; transition: transform 0.3s ease; cursor: grab;">
                </div>
            </div>
        </div>
    </div>

    <?php renderScripts(); ?>
    <script>
        // Global variables for image zoom and pan
        let currentZoom = 1;
        let isDragging = false;
        let startX, startY, translateX = 0, translateY = 0;
        
        // Modal image handling
        document.getElementById('receiptModal').addEventListener('show.bs.modal', function(event) {
            const trigger = event.relatedTarget;
            const imageSrc = trigger.getAttribute('data-image');
            const title = trigger.getAttribute('data-title');
            
            const modalImage = document.getElementById('receiptModalImage');
            const modalTitle = document.getElementById('receiptModalLabel');
            
            modalImage.src = imageSrc;
            modalTitle.textContent = title || 'Receipt Preview';
            
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
            image.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        }
        
        // Image dragging functionality
        const modalImage = document.getElementById('receiptModalImage');
        
        modalImage.addEventListener('mousedown', (e) => {
            if (currentZoom > 1) {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                modalImage.style.cursor = 'grabbing';
                e.preventDefault();
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
        
        // View toggle functionality
        document.getElementById('tableView').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('tableViewContent').classList.remove('d-none');
                document.getElementById('cardViewContent').classList.add('d-none');
            }
        });
        
        document.getElementById('cardView').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('tableViewContent').classList.add('d-none');
                document.getElementById('cardViewContent').classList.remove('d-none');
            }
        });
        
        // Receipt actions
        function markAsLogged(receiptId) {
            updateReceiptStatus(receiptId, 'mark_logged');
        }
        
        function markAsUnlogged(receiptId) {
            updateReceiptStatus(receiptId, 'mark_unlogged');
        }
        
        function updateReceiptStatus(receiptId, action) {
            const formData = new FormData();
            formData.append('receipt_id', receiptId);
            formData.append('action', action);
            formData.append('csrf_token', '<?php echo Utils::generateCSRFToken(); ?>');
            
            fetch('update_receipt.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Receipt updated successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error updating receipt', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating the receipt', 'error');
            });
        }
        
        function editReceipt(receiptId) {
            window.location.href = `edit_receipt.php?id=${receiptId}`;
        }
        
        function deleteReceipt(receiptId) {
            if (confirm('Are you sure you want to delete this receipt? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('receipt_id', receiptId);
                formData.append('action', 'delete');
                formData.append('csrf_token', '<?php echo Utils::generateCSRFToken(); ?>');
                
                fetch('delete_receipt.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Receipt deleted successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Error deleting receipt', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while deleting the receipt', 'error');
                });
            }
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }
        
        // Auto-refresh to keep session alive
        setInterval(() => {
            fetch('keep_alive.php').catch(() => {});
        }, 300000); // 5 minutes
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'u':
                        e.preventDefault();
                        window.location.href = 'upload.php';
                        break;
                    case 'h':
                        e.preventDefault();
                        window.location.href = 'dashboard.php';
                        break;
                }
            }
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>