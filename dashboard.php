<?php
// dashboard.php - Enhanced dashboard with PWA, sharing, and advanced image modal
require_once 'config.php';
require_login();

$user_id = get_current_user_id();

// Get user's boxes (owned + shared)
$stmt = $pdo->prepare("
    (SELECT rb.*, 'owner' as access_level, COUNT(r.id) as receipt_count, COALESCE(SUM(r.amount), 0) as total_amount
     FROM receipt_boxes rb 
     LEFT JOIN receipts r ON rb.id = r.box_id 
     WHERE rb.owner_id = ? 
     GROUP BY rb.id)
    UNION
    (SELECT rb.*, IF(bs.can_edit, 'editor', 'viewer') as access_level, COUNT(r.id) as receipt_count, COALESCE(SUM(r.amount), 0) as total_amount
     FROM receipt_boxes rb
     JOIN box_shares bs ON rb.id = bs.box_id
     LEFT JOIN receipts r ON rb.id = r.box_id 
     WHERE bs.user_id = ?
     GROUP BY rb.id)
    ORDER BY access_level = 'owner' DESC, name
");
$stmt->execute([$user_id, $user_id]);
$boxes = $stmt->fetchAll();

// Get selected box
$selected_box_id = $_GET['box'] ?? ($boxes[0]['id'] ?? null);

if (!$selected_box_id) {
    redirect('boxes.php', 'Please create a receipt box first.', 'info');
}

// Verify access to selected box
$has_access = false;
foreach ($boxes as $box) {
    if ($box['id'] == $selected_box_id) {
        $has_access = true;
        $current_box = $box;
        break;
    }
}

if (!$has_access) {
    redirect('dashboard.php?box=' . $boxes[0]['id'], 'Invalid box selected.', 'error');
}

// Get receipts for selected box
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$where_clause = "WHERE r.box_id = ?";
$params = [$selected_box_id];

if ($search) {
    $where_clause .= " AND (r.title LIKE ? OR r.vendor LIKE ? OR r.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter === 'logged') {
    $where_clause .= " AND r.is_logged = 1";
} elseif ($filter === 'pending') {
    $where_clause .= " AND r.is_logged = 0";
}

$stmt = $pdo->prepare("
    SELECT r.*, u.username as uploaded_by_name 
    FROM receipts r 
    JOIN users u ON r.uploaded_by = u.id 
    $where_clause 
    ORDER BY r.created_at DESC
    LIMIT 50
");
$stmt->execute($params);
$receipts = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_logged THEN 1 ELSE 0 END) as logged,
        SUM(CASE WHEN is_logged = 0 THEN 1 ELSE 0 END) as pending,
        COALESCE(SUM(amount), 0) as total_amount
    FROM receipts WHERE box_id = ?
");
$stmt->execute([$selected_box_id]);
$stats = $stmt->fetch();

$page_title = 'Dashboard';
include 'header.php';
?>

<style>
.stats-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    transition: transform 0.2s ease;
    border: none;
    overflow: hidden;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-card .card-body {
    padding: 1.5rem;
}

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stats-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

.stats-icon {
    opacity: 0.3;
    font-size: 2.5rem;
}

.receipt-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
    border: none;
    margin-bottom: 1rem;
}

.receipt-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.receipt-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.receipt-thumbnail:hover {
    transform: scale(1.05);
}

.receipt-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-logged {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-pending {
    background: rgba(255, 193, 7, 0.1);
    color: #856404;
}

.box-selector {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: none;
    margin-bottom: 2rem;
}

.search-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: none;
    margin-bottom: 2rem;
}

.page-header {
    margin-bottom: 2rem;
}

.receipts-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: none;
}

.access-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.access-owner { background: #198754; color: white; }
.access-editor { background: #0d6efd; color: white; }
.access-viewer { background: #6c757d; color: white; }

/* Advanced Image Modal Styles */
.advanced-modal {
    background: rgba(0, 0, 0, 0.9);
}

.advanced-modal .modal-dialog {
    max-width: 95vw;
    max-height: 95vh;
    margin: 2.5vh auto;
}

.advanced-modal .modal-content {
    background: transparent;
    border: none;
    height: 90vh;
}

.advanced-modal .modal-header {
    background: rgba(0, 0, 0, 0.8);
    border: none;
    padding: 1rem;
}

.advanced-modal .modal-body {
    padding: 0;
    position: relative;
    height: 100%;
    overflow: hidden;
}

.image-container {
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.zoomable-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    cursor: grab;
    transition: transform 0.1s ease;
    transform-origin: center center;
}

.zoomable-image:active {
    cursor: grabbing;
}

.zoom-controls {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 1060;
    display: flex;
    gap: 0.5rem;
}

.zoom-controls .btn {
    background: rgba(0, 0, 0, 0.7);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.zoom-controls .btn:hover {
    background: rgba(0, 0, 0, 0.9);
    color: white;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-6 fw-bold mb-2">
                <i class="fas fa-times me-1"></i>Clear Filters
        </a>
        <?php endif; ?>
        <?php if ($current_box['access_level'] !== 'viewer'): ?>
        <a href="upload.php" class="btn btn-accent">
            <i class="fas fa-plus me-1"></i>Upload Receipt
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="card-body p-0">
        <?php foreach ($receipts as $receipt): ?>
        <div class="receipt-card mx-3 my-3">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <!-- Receipt Thumbnail -->
                            <div class="me-3">
                                <?php 
                                $ext = strtolower(pathinfo($receipt['file_name'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                ?>
                                <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                                     class="receipt-thumbnail" 
                                     alt="Receipt thumbnail"
                                     onclick="openAdvancedModal('<?php echo htmlspecialchars($receipt['file_path']); ?>', '<?php echo htmlspecialchars($receipt['title']); ?>')">
                                <?php else: ?>
                                <div class="receipt-thumbnail d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-file-pdf fa-lg text-danger"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Receipt Info -->
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($receipt['title']); ?></h6>
                                <?php if ($receipt['vendor']): ?>
                                <div class="text-muted small mb-1">
                                    <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($receipt['vendor']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($receipt['description']): ?>
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars(substr($receipt['description'], 0, 60)); ?>
                                    <?php echo strlen($receipt['description']) > 60 ? '...' : ''; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 text-center">
                        <?php if ($receipt['amount']): ?>
                        <div class="fw-bold text-success h5 mb-0">$<?php echo number_format($receipt['amount'], 2); ?></div>
                        <?php else: ?>
                        <span class="text-muted">No amount</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-2 text-center">
                        <?php if ($receipt['receipt_date']): ?>
                        <div class="fw-semibold"><?php echo date('M j', strtotime($receipt['receipt_date'])); ?></div>
                        <small class="text-muted"><?php echo date('Y', strtotime($receipt['receipt_date'])); ?></small>
                        <?php else: ?>
                        <span class="text-muted">No date</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-2 text-center">
                        <div class="mb-2">
                            <span class="receipt-status <?php echo $receipt['is_logged'] ? 'status-logged' : 'status-pending'; ?>">
                                <?php echo $receipt['is_logged'] ? 'Logged' : 'Pending'; ?>
                            </span>
                        </div>
                        <?php if ($current_box['access_level'] !== 'viewer'): ?>
                        <div class="btn-group btn-group-sm">
                            <?php if (!$receipt['is_logged']): ?>
                            <button class="btn btn-success" onclick="toggleStatus(<?php echo $receipt['id']; ?>, 1)" 
                                    title="Mark as Logged">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $receipt['id']; ?>, 0)" 
                                    title="Mark as Pending">
                                <i class="fas fa-undo"></i>
                            </button>
                            <?php endif; ?>
                            
                            <a href="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                               class="btn btn-outline-primary" download title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            
                            <button class="btn btn-outline-danger" onclick="deleteReceipt(<?php echo $receipt['id']; ?>)" 
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                           class="btn btn-outline-primary btn-sm" download title="Download">
                            <i class="fas fa-download"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Advanced Image Modal -->
<div class="modal fade advanced-modal" id="advancedImageModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="advancedModalTitle">Receipt Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="zoom-controls">
                    <button type="button" class="btn" onclick="zoomIn()" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="btn" onclick="zoomOut()" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="btn" onclick="resetZoom()" title="Reset">
                        <i class="fas fa-compress-arrows-alt"></i>
                    </button>
                    <button type="button" class="btn" onclick="rotateImage()" title="Rotate">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
                <div class="image-container" id="imageContainer">
                    <img id="advancedModalImage" src="" alt="Receipt" class="zoomable-image">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Box Modal -->
<?php if ($current_box['access_level'] === 'owner'): ?>
<div class="modal fade" id="shareBoxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share Receipt Box</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Share: <?php echo htmlspecialchars($current_box['name']); ?></h6>
                
                <!-- Invite Form -->
                <form id="inviteForm" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required 
                                   placeholder="user@example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Permission</label>
                            <select class="form-select" name="can_edit">
                                <option value="0">View Only</option>
                                <option value="1">Can Edit</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Current Shares -->
                <div id="currentShares">
                    <h6>Current Access</h6>
                    <div id="sharesList"></div>
                </div>
                
                <!-- Pending Invitations -->
                <div id="pendingInvitations" class="mt-3">
                    <h6>Pending Invitations</h6>
                    <div id="invitationsList"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Advanced Image Modal Variables
let currentZoom = 1;
let currentRotation = 0;
let isDragging = false;
let startX, startY, translateX = 0, translateY = 0;
let modal, modalImage, imageContainer;

// Initialize modal elements
document.addEventListener('DOMContentLoaded', function() {
    modal = document.getElementById('advancedImageModal');
    modalImage = document.getElementById('advancedModalImage');
    imageContainer = document.getElementById('imageContainer');
    
    if (modal) {
        // Reset on modal close
        modal.addEventListener('hidden.bs.modal', function() {
            resetZoom();
            currentRotation = 0;
            updateImageTransform();
        });
    }
    
    setupImageDragging();
    loadCurrentShares();
});

// Advanced Image Modal Functions
function openAdvancedModal(imagePath, title) {
    if (modalImage) modalImage.src = imagePath;
    if (document.getElementById('advancedModalTitle')) {
        document.getElementById('advancedModalTitle').textContent = title;
    }
    
    // Reset zoom and position
    currentZoom = 1;
    currentRotation = 0;
    translateX = 0;
    translateY = 0;
    updateImageTransform();
    
    if (modal) {
        new bootstrap.Modal(modal).show();
    }
}

function zoomIn() {
    currentZoom = Math.min(currentZoom * 1.3, 5);
    updateImageTransform();
}

function zoomOut() {
    currentZoom = Math.max(currentZoom / 1.3, 0.3);
    updateImageTransform();
}

function resetZoom() {
    currentZoom = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();
}

function rotateImage() {
    currentRotation = (currentRotation + 90) % 360;
    updateImageTransform();
}

function updateImageTransform() {
    if (modalImage) {
        modalImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoom}) rotate(${currentRotation}deg)`;
    }
}

// Image Dragging Setup
function setupImageDragging() {
    if (!modalImage) return;
    
    modalImage.addEventListener('mousedown', startDragging);
    modalImage.addEventListener('touchstart', startDragging);
    
    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag);
    
    document.addEventListener('mouseup', stopDragging);
    document.addEventListener('touchend', stopDragging);
    
    // Wheel zoom
    modalImage.addEventListener('wheel', function(e) {
        e.preventDefault();
        if (e.deltaY < 0) {
            zoomIn();
        } else {
            zoomOut();
        }
    });
}

function startDragging(e) {
    if (currentZoom <= 1) return;
    
    isDragging = true;
    if (modalImage) modalImage.style.cursor = 'grabbing';
    
    const clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
    const clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
    
    startX = clientX - translateX;
    startY = clientY - translateY;
    
    e.preventDefault();
}

function drag(e) {
    if (!isDragging) return;
    
    const clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
    const clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
    
    translateX = clientX - startX;
    translateY = clientY - startY;
    
    updateImageTransform();
    e.preventDefault();
}

function stopDragging() {
    isDragging = false;
    if (modalImage) {
        modalImage.style.cursor = currentZoom > 1 ? 'grab' : 'default';
    }
}

// Receipt Actions
function toggleStatus(receiptId, status) {
    fetch('actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_status&receipt_id=${receiptId}&status=${status}`
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
        alert('An error occurred');
    });
}

function deleteReceipt(receiptId) {
    if (confirm('Are you sure you want to delete this receipt? This cannot be undone.')) {
        fetch('actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_receipt&receipt_id=${receiptId}`
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
            alert('An error occurred');
        });
    }
}

// Box Sharing Functions
<?php if ($current_box['access_level'] === 'owner'): ?>
const inviteForm = document.getElementById('inviteForm');
if (inviteForm) {
    inviteForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'invite_user');
        formData.append('box_id', <?php echo $selected_box_id; ?>);
        
        fetch('actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.reset();
                loadCurrentShares();
                alert('Invitation sent successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });
}

function loadCurrentShares() {
    fetch(`actions.php?action=get_box_shares&box_id=<?php echo $selected_box_id; ?>`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayShares(data.shares);
            displayInvitations(data.invitations);
        }
    })
    .catch(error => console.error('Error loading shares:', error));
}

function displayShares(shares) {
    const sharesList = document.getElementById('sharesList');
    if (!sharesList) return;
    
    if (!shares.length) {
        sharesList.innerHTML = '<p class="text-muted">No one else has access to this box.</p>';
        return;
    }
    
    sharesList.innerHTML = shares.map(share => `
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
            <div>
                <strong>${share.username}</strong>
                <span class="access-badge access-${share.can_edit ? 'editor' : 'viewer'} ms-2">
                    ${share.can_edit ? 'Editor' : 'Viewer'}
                </span>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="removeShare(${share.user_id})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function displayInvitations(invitations) {
    const invitationsList = document.getElementById('invitationsList');
    if (!invitationsList) return;
    
    if (!invitations.length) {
        invitationsList.innerHTML = '<p class="text-muted">No pending invitations.</p>';
        return;
    }
    
    invitationsList.innerHTML = invitations.map(invitation => `
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-warning bg-opacity-10 rounded">
            <div>
                <strong>${invitation.email}</strong>
                <span class="access-badge access-${invitation.can_edit ? 'editor' : 'viewer'} ms-2">
                    ${invitation.can_edit ? 'Editor' : 'Viewer'}
                </span>
                <br><small class="text-muted">Expires: ${new Date(invitation.expires_at).toLocaleDateString()}</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="cancelInvitation('${invitation.email}')">
                Cancel
            </button>
        </div>
    `).join('');
}

function removeShare(userId) {
    if (confirm('Are you sure you want to remove this user\'s access?')) {
        fetch('actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_share&box_id=<?php echo $selected_box_id; ?>&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCurrentShares();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function cancelInvitation(email) {
    if (confirm('Are you sure you want to cancel this invitation?')) {
        fetch('actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=cancel_invitation&box_id=<?php echo $selected_box_id; ?>&email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCurrentShares();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
<?php endif; ?>
</script>

<?php include 'footer.php'; ?> fa-tachometer-alt text-primary me-2"></i>Dashboard
            </h1>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($current_box['access_level'] !== 'viewer'): ?>
            <a href="upload.php" class="btn btn-accent btn-lg">
                <i class="fas fa-plus me-2"></i>Upload Receipt
            </a>
            <?php endif; ?>
            <?php if ($current_box['access_level'] === 'owner'): ?>
            <button class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#shareBoxModal">
                <i class="fas fa-share me-2"></i>Share
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Box Selector -->
<div class="card box-selector">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <h5 class="mb-1">
                            <i class="fas fa-box text-primary me-2"></i>
                            <strong><?php echo htmlspecialchars($current_box['name']); ?></strong>
                        </h5>
                        <?php if ($current_box['description']): ?>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($current_box['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="access-badge access-<?php echo $current_box['access_level']; ?>">
                        <?php echo ucfirst($current_box['access_level']); ?>
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-exchange-alt me-1"></i>Switch Box
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($boxes as $box): ?>
                        <li>
                            <a class="dropdown-item <?php echo $box['id'] == $selected_box_id ? 'active' : ''; ?>" 
                               href="?box=<?php echo $box['id']; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span><?php echo htmlspecialchars($box['name']); ?></span>
                                        <span class="access-badge access-<?php echo $box['access_level']; ?> ms-2">
                                            <?php echo ucfirst($box['access_level']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted ms-2"><?php echo $box['receipt_count']; ?> receipts</small>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="boxes.php">
                            <i class="fas fa-cog me-2"></i>Manage Boxes
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-primary"><?php echo number_format($stats['total']); ?></div>
                        <p class="stats-label">Total Receipts</p>
                    </div>
                    <i class="fas fa-receipt stats-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-success"><?php echo number_format($stats['logged']); ?></div>
                        <p class="stats-label">Logged</p>
                    </div>
                    <i class="fas fa-check-circle stats-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-warning"><?php echo number_format($stats['pending']); ?></div>
                        <p class="stats-label">Pending</p>
                    </div>
                    <i class="fas fa-clock stats-icon text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-info">$<?php echo number_format($stats['total_amount'], 2); ?></div>
                        <p class="stats-label">Total Amount</p>
                    </div>
                    <i class="fas fa-dollar-sign stats-icon text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="card search-card">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="box" value="<?php echo $selected_box_id; ?>">
            
            <div class="col-md-5">
                <label class="form-label fw-semibold">
                    <i class="fas fa-search me-1"></i>Search Receipts
                </label>
                <input type="text" class="form-control" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search title, vendor, or description...">
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-filter me-1"></i>Filter by Status
                </label>
                <select class="form-select" name="filter">
                    <option value="">All Receipts</option>
                    <option value="logged" <?php echo $filter === 'logged' ? 'selected' : ''; ?>>Logged Only</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    <a href="?box=<?php echo $selected_box_id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Summary -->
<?php if ($search || $filter): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Showing <?php echo count($receipts); ?> receipts
    <?php if ($search): ?>
    matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
    <?php endif; ?>
    <?php if ($filter): ?>
    with status: <strong><?php echo ucfirst($filter); ?></strong>
    <?php endif; ?>
    <a href="?box=<?php echo $selected_box_id; ?>" class="text-decoration-none ms-2">Clear filters</a>
</div>
<?php endif; ?>

<!-- Receipts List -->
<div class="receipts-container">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Recent Receipts
            </h5>
            <small class="text-muted">Showing up to 50 most recent receipts</small>
        </div>
    </div>
    
    <?php if (empty($receipts)): ?>
    <div class="card-body text-center py-5">
        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
        <h4 class="text-muted mb-3">No receipts found</h4>
        <p class="text-muted mb-4">
            <?php if ($search || $filter): ?>
            No receipts match your search criteria.
            <?php else: ?>
            Start by uploading your first receipt to this box!
            <?php endif; ?>
        </p>
        <?php if ($search || $filter): ?>
        <a href="?box=<?php echo $selected_box_id; ?>" class="btn btn-outline-primary me-2">
            <i class="fas<?php
// dashboard.php - Simple dashboard (replaced by enhanced version)
// This file has been replaced by enhanced_dashboard_with_modal.php
// Please use the enhanced version which includes:
// - PWA support
// - Advanced image modal with zoom/pan/rotate
// - Box sharing functionality
// - Mobile-friendly upload

// Redirect to the enhanced dashboard
header('Location: enhanced_dashboard_with_modal.php' . (isset($_GET['box']) ? '?box=' . $_GET['box'] : ''));
exit;

// Get selected box
$selected_box_id = $_GET['box'] ?? ($boxes[0]['id'] ?? null);

if (!$selected_box_id) {
    redirect('boxes.php', 'Please create a receipt box first.', 'info');
}

// Verify access to selected box
$has_access = false;
foreach ($boxes as $box) {
    if ($box['id'] == $selected_box_id) {
        $has_access = true;
        $current_box = $box;
        break;
    }
}

if (!$has_access) {
    redirect('dashboard.php?box=' . $boxes[0]['id'], 'Invalid box selected.', 'error');
}

// Get receipts for selected box
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$where_clause = "WHERE r.box_id = ?";
$params = [$selected_box_id];

if ($search) {
    $where_clause .= " AND (r.title LIKE ? OR r.vendor LIKE ? OR r.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter === 'logged') {
    $where_clause .= " AND r.is_logged = 1";
} elseif ($filter === 'pending') {
    $where_clause .= " AND r.is_logged = 0";
}

$stmt = $pdo->prepare("
    SELECT r.*, u.username as uploaded_by_name 
    FROM receipts r 
    JOIN users u ON r.uploaded_by = u.id 
    $where_clause 
    ORDER BY r.created_at DESC
    LIMIT 50
");
$stmt->execute($params);
$receipts = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_logged THEN 1 ELSE 0 END) as logged,
        SUM(CASE WHEN is_logged = 0 THEN 1 ELSE 0 END) as pending,
        COALESCE(SUM(amount), 0) as total_amount
    FROM receipts WHERE box_id = ?
");
$stmt->execute([$selected_box_id]);
$stats = $stmt->fetch();

$page_title = 'Dashboard';
include 'header.php';
?>

<style>
.stats-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    transition: transform 0.2s ease;
    border: none;
    overflow: hidden;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-card .card-body {
    padding: 1.5rem;
}

.stats-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stats-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin: 0;
}

.stats-icon {
    opacity: 0.3;
    font-size: 2.5rem;
}

.receipt-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
    border: none;
    margin-bottom: 1rem;
}

.receipt-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.receipt-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
}

.receipt-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-logged {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-pending {
    background: rgba(255, 193, 7, 0.1);
    color: #856404;
}

.box-selector {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: none;
    margin-bottom: 2rem;
}

.search-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: none;
    margin-bottom: 2rem;
}

.page-header {
    margin-bottom: 2rem;
}

.receipts-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: none;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-6 fw-bold mb-2">
                <i class="fas fa-tachometer-alt text-primary me-2"></i>Dashboard
            </h1>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
        <a href="upload.php" class="btn btn-accent btn-lg">
            <i class="fas fa-plus me-2"></i>Upload Receipt
        </a>
    </div>
</div>

<!-- Box Selector -->
<div class="card box-selector">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1">
                    <i class="fas fa-box text-primary me-2"></i>
                    Current Box: <strong><?php echo htmlspecialchars($current_box['name']); ?></strong>
                </h5>
                <?php if ($current_box['description']): ?>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($current_box['description']); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-end">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-exchange-alt me-1"></i>Switch Box
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($boxes as $box): ?>
                        <li>
                            <a class="dropdown-item <?php echo $box['id'] == $selected_box_id ? 'active' : ''; ?>" 
                               href="?box=<?php echo $box['id']; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($box['name']); ?></span>
                                    <small class="text-muted ms-2"><?php echo $box['receipt_count']; ?> receipts</small>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="boxes.php">
                            <i class="fas fa-cog me-2"></i>Manage Boxes
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-primary"><?php echo number_format($stats['total']); ?></div>
                        <p class="stats-label">Total Receipts</p>
                    </div>
                    <i class="fas fa-receipt stats-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-success"><?php echo number_format($stats['logged']); ?></div>
                        <p class="stats-label">Logged</p>
                    </div>
                    <i class="fas fa-check-circle stats-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-warning"><?php echo number_format($stats['pending']); ?></div>
                        <p class="stats-label">Pending</p>
                    </div>
                    <i class="fas fa-clock stats-icon text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number text-info">$<?php echo number_format($stats['total_amount'], 2); ?></div>
                        <p class="stats-label">Total Amount</p>
                    </div>
                    <i class="fas fa-dollar-sign stats-icon text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="card search-card">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="box" value="<?php echo $selected_box_id; ?>">
            
            <div class="col-md-5">
                <label class="form-label fw-semibold">
                    <i class="fas fa-search me-1"></i>Search Receipts
                </label>
                <input type="text" class="form-control" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search title, vendor, or description...">
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-filter me-1"></i>Filter by Status
                </label>
                <select class="form-select" name="filter">
                    <option value="">All Receipts</option>
                    <option value="logged" <?php echo $filter === 'logged' ? 'selected' : ''; ?>>Logged Only</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    <a href="?box=<?php echo $selected_box_id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Summary -->
<?php if ($search || $filter): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Showing <?php echo count($receipts); ?> receipts
    <?php if ($search): ?>
    matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
    <?php endif; ?>
    <?php if ($filter): ?>
    with status: <strong><?php echo ucfirst($filter); ?></strong>
    <?php endif; ?>
    <a href="?box=<?php echo $selected_box_id; ?>" class="text-decoration-none ms-2">Clear filters</a>
</div>
<?php endif; ?>

<!-- Receipts List -->
<div class="receipts-container">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Recent Receipts
            </h5>
            <small class="text-muted">Showing up to 50 most recent receipts</small>
        </div>
    </div>
    
    <?php if (empty($receipts)): ?>
    <div class="card-body text-center py-5">
        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
        <h4 class="text-muted mb-3">No receipts found</h4>
        <p class="text-muted mb-4">
            <?php if ($search || $filter): ?>
            No receipts match your search criteria.
            <?php else: ?>
            Start by uploading your first receipt to this box!
            <?php endif; ?>
        </p>
        <?php if ($search || $filter): ?>
        <a href="?box=<?php echo $selected_box_id; ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-times me-1"></i>Clear Filters
        </a>
        <?php endif; ?>
        <a href="upload.php" class="btn btn-accent">
            <i class="fas fa-plus me-1"></i>Upload Receipt
        </a>
    </div>
    <?php else: ?>
    <div class="card-body p-0">
        <?php foreach ($receipts as $receipt): ?>
        <div class="receipt-card mx-3 my-3">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <!-- Receipt Thumbnail -->
                            <div class="me-3">
                                <?php 
                                $ext = strtolower(pathinfo($receipt['file_name'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                ?>
                                <img src="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                                     class="receipt-thumbnail" 
                                     alt="Receipt thumbnail"
                                     onclick="viewReceipt('<?php echo htmlspecialchars($receipt['file_path']); ?>', '<?php echo htmlspecialchars($receipt['title']); ?>')">
                                <?php else: ?>
                                <div class="receipt-thumbnail d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-file-pdf fa-lg text-danger"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Receipt Info -->
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($receipt['title']); ?></h6>
                                <?php if ($receipt['vendor']): ?>
                                <div class="text-muted small mb-1">
                                    <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($receipt['vendor']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($receipt['description']): ?>
                                <div class="text-muted small">
                                    <?php echo htmlspecialchars(substr($receipt['description'], 0, 60)); ?>
                                    <?php echo strlen($receipt['description']) > 60 ? '...' : ''; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2 text-center">
                        <?php if ($receipt['amount']): ?>
                        <div class="fw-bold text-success h5 mb-0">$<?php echo number_format($receipt['amount'], 2); ?></div>
                        <?php else: ?>
                        <span class="text-muted">No amount</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-2 text-center">
                        <?php if ($receipt['receipt_date']): ?>
                        <div class="fw-semibold"><?php echo date('M j', strtotime($receipt['receipt_date'])); ?></div>
                        <small class="text-muted"><?php echo date('Y', strtotime($receipt['receipt_date'])); ?></small>
                        <?php else: ?>
                        <span class="text-muted">No date</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-2 text-center">
                        <div class="mb-2">
                            <span class="receipt-status <?php echo $receipt['is_logged'] ? 'status-logged' : 'status-pending'; ?>">
                                <?php echo $receipt['is_logged'] ? 'Logged' : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <?php if (!$receipt['is_logged']): ?>
                            <button class="btn btn-success" onclick="toggleStatus(<?php echo $receipt['id']; ?>, 1)" 
                                    title="Mark as Logged">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $receipt['id']; ?>, 0)" 
                                    title="Mark as Pending">
                                <i class="fas fa-undo"></i>
                            </button>
                            <?php endif; ?>
                            
                            <a href="<?php echo htmlspecialchars($receipt['file_path']); ?>" 
                               class="btn btn-outline-primary" download title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            
                            <button class="btn btn-outline-danger" onclick="deleteReceipt(<?php echo $receipt['id']; ?>)" 
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Receipt Preview Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalTitle">Receipt Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="receiptModalImage" src="" alt="Receipt" class="img-fluid" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<script>
function toggleStatus(receiptId, status) {
    fetch('actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_status&receipt_id=${receiptId}&status=${status}`
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
        alert('An error occurred');
    });
}

function deleteReceipt(receiptId) {
    if (confirm('Are you sure you want to delete this receipt? This cannot be undone.')) {
        fetch('actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_receipt&receipt_id=${receiptId}`
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
            alert('An error occurred');
        });
    }
}

function viewReceipt(imagePath, title) {
    document.getElementById('receiptModalImage').src = imagePath;
    document.getElementById('receiptModalTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}
</script>

<?php include 'footer.php'; ?>