<?php
// test_edit.php - Simple test to verify data flow
require_once 'config.php';
require_login();

$user_id = get_current_user_id();
$receipt_id = (int)($_GET['id'] ?? 0);

// Force no caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (!$receipt_id) {
    die('No receipt ID provided');
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $new_title = clean_input($_POST['title'] ?? '');
        
        if (empty($new_title)) {
            throw new Exception('Title is required');
        }
        
        // Update with explicit timestamp
        $stmt = $pdo->prepare("
            UPDATE receipts 
            SET title = ?, updated_at = NOW()
            WHERE id = ? AND (
                box_id IN (SELECT id FROM receipt_boxes WHERE owner_id = ?) OR
                box_id IN (SELECT box_id FROM box_shares WHERE user_id = ? AND can_edit = 1)
            )
        ");
        
        $result = $stmt->execute([$new_title, $receipt_id, $user_id, $user_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            $message = "✓ Updated successfully at " . date('H:i:s');
        } else {
            $message = "❌ No rows updated - check permissions";
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Get current data with explicit fresh query
$stmt = $pdo->prepare("
    SELECT r.*, rb.name as box_name
    FROM receipts r 
    JOIN receipt_boxes rb ON r.box_id = rb.id 
    WHERE r.id = ? AND (
        rb.owner_id = ? OR 
        rb.id IN (SELECT box_id FROM box_shares WHERE user_id = ?)
    )
");
$stmt->execute([$receipt_id, $user_id, $user_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die('Receipt not found or access denied');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Edit - Receipt <?php echo $receipt_id; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .data-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .timestamp {
            font-family: monospace;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Test Edit - Receipt #<?php echo $receipt_id; ?></h1>
        
        <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Current Data Display -->
        <div class="data-display">
            <h5>Current Database Data</h5>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($receipt['title']); ?></p>
            <p><strong>Amount:</strong> $<?php echo number_format($receipt['amount'], 2); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($receipt['category']); ?></p>
            <p><strong>Vendor:</strong> <?php echo htmlspecialchars($receipt['vendor']); ?></p>
            <p><strong>Status:</strong> <?php echo $receipt['is_logged'] ? 'Logged' : 'Pending'; ?></p>
            <p class="timestamp"><strong>Created:</strong> <?php echo $receipt['created_at']; ?></p>
            <p class="timestamp"><strong>Updated:</strong> <?php echo $receipt['updated_at'] ?? 'Not set'; ?></p>
            <p class="timestamp"><strong>Page loaded:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <!-- Simple Edit Form -->
        <div class="card">
            <div class="card-header">
                <h5>Quick Test Edit</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" 
                               value="<?php echo htmlspecialchars($receipt['title']); ?>" required>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Title</button>
                        <button type="button" class="btn btn-secondary" onclick="location.reload()">Refresh Page</button>
                        <a href="edit_receipt.php?id=<?php echo $receipt_id; ?>" class="btn btn-outline-primary">Go to Full Edit</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Raw Data for Debugging -->
        <div class="data-display mt-4">
            <h6>Raw Database Row</h6>
            <pre style="font-size: 0.8em;"><?php echo json_encode($receipt, JSON_PRETTY_PRINT); ?></pre>
        </div>
        
        <!-- Test Multiple Queries -->
        <div class="card mt-4">
            <div class="card-header">
                <h6>Multiple Query Test</h6>
            </div>
            <div class="card-body">
                <?php
                // Test multiple queries to see if we get consistent data
                for ($i = 1; $i <= 3; $i++) {
                    $test_stmt = $pdo->prepare("SELECT title, updated_at FROM receipts WHERE id = ?");
                    $test_stmt->execute([$receipt_id]);
                    $test_result = $test_stmt->fetch();
                    echo "<p><strong>Query $i:</strong> Title: '{$test_result['title']}' | Updated: {$test_result['updated_at']}</p>";
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 5 seconds to test for data changes
        let autoRefresh = false;
        
        function toggleAutoRefresh() {
            autoRefresh = !autoRefresh;
            if (autoRefresh) {
                setTimeout(function refresh() {
                    if (autoRefresh) {
                        location.reload();
                    }
                }, 5000);
            }
        }
        
        // Add auto-refresh toggle
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'btn btn-sm btn-outline-secondary position-fixed';
            toggleBtn.style.cssText = 'top: 10px; right: 10px; z-index: 1000;';
            toggleBtn.textContent = 'Auto Refresh: OFF';
            toggleBtn.onclick = function() {
                toggleAutoRefresh();
                this.textContent = 'Auto Refresh: ' + (autoRefresh ? 'ON' : 'OFF');
                this.className = autoRefresh ? 
                    'btn btn-sm btn-success position-fixed' : 
                    'btn btn-sm btn-outline-secondary position-fixed';
            };
            document.body.appendChild(toggleBtn);
        });
    </script>
</body>
</html>