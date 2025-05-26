<?php
// footer.php - Common footer for all pages
?>
    </div> <!-- Close container from header -->
    
    <footer class="mt-5 py-4 bg-light text-center">
        <div class="container">
            <p class="text-muted mb-0">
                &copy; 2024 LogIt - Simple Receipt Management
                <span class="mx-2">|</span>
                <small>Keep your receipts organized</small>
            </p>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Simple notification system -->
    <script>
        // Show flash messages
        function showFlash(message, type = 'info') {
            if (!message) return;
            
            const alertClass = type === 'error' ? 'danger' : type;
            const iconClass = type === 'error' ? 'exclamation-triangle' : 
                             type === 'success' ? 'check-circle' : 'info-circle';
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${alertClass} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="fas fa-${iconClass} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
        
        // Check for flash messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php 
            $success = get_flash('success');
            $error = get_flash('error');
            $info = get_flash('info');
            
            if ($success): ?>
                showFlash('<?php echo addslashes($success); ?>', 'success');
            <?php endif; ?>
            
            <?php if ($error): ?>
                showFlash('<?php echo addslashes($error); ?>', 'error');
            <?php endif; ?>
            
            <?php if ($info): ?>
                showFlash('<?php echo addslashes($info); ?>', 'info');
            <?php endif; ?>
        });
    </script>
</body>
</html>