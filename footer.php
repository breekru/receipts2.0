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
    
    <!-- PWA Install Banner -->
    <div id="installBanner" class="position-fixed bottom-0 start-0 end-0 bg-primary text-white p-3 d-none" style="z-index: 9999;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Install LogIt</strong>
                    <small class="d-block">Get the full app experience on your device</small>
                </div>
                <div>
                    <button id="installBtn" class="btn btn-light btn-sm me-2">Install</button>
                    <button id="dismissBtn" class="btn btn-outline-light btn-sm">&times;</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Service Worker Update Banner -->
    <div id="updateBanner" class="position-fixed bottom-0 start-0 end-0 bg-success text-white p-3 d-none" style="z-index: 9999;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Update Available</strong>
                    <small class="d-block">A new version of LogIt is ready</small>
                </div>
                <div>
                    <button id="updateBtn" class="btn btn-light btn-sm me-2">Update</button>
                    <button id="dismissUpdateBtn" class="btn btn-outline-light btn-sm">&times;</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PWA and Service Worker Registration -->
    <script>
        // Service Worker Registration with update handling
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then((registration) => {
                        console.log('SW registered: ', registration);
                        
                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New version available
                                    showUpdateBanner(newWorker);
                                }
                            });
                        });
                        
                        // Check for update on focus
                        window.addEventListener('focus', () => {
                            registration.update();
                        });
                    })
                    .catch((registrationError) => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }

        // Update banner functionality
        function showUpdateBanner(newWorker) {
            const updateBanner = document.getElementById('updateBanner');
            const updateBtn = document.getElementById('updateBtn');
            const dismissUpdateBtn = document.getElementById('dismissUpdateBtn');
            
            if (updateBanner) {
                updateBanner.classList.remove('d-none');
                
                if (updateBtn) {
                    updateBtn.addEventListener('click', () => {
                        newWorker.postMessage({ type: 'SKIP_WAITING' });
                        window.location.reload();
                    });
                }
                
                if (dismissUpdateBtn) {
                    dismissUpdateBtn.addEventListener('click', () => {
                        updateBanner.classList.add('d-none');
                    });
                }
            }
        }

        // PWA Install functionality
        let deferredPrompt;
        const installBanner = document.getElementById('installBanner');
        const installBtn = document.getElementById('installBtn');
        const dismissBtn = document.getElementById('dismissBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (installBanner) {
                installBanner.classList.remove('d-none');
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`User ${outcome} the install prompt`);
                    deferredPrompt = null;
                    if (installBanner) {
                        installBanner.classList.add('d-none');
                    }
                }
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                if (installBanner) {
                    installBanner.classList.add('d-none');
                }
            });
        }

        // Handle service worker messages
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'SW_UPDATED') {
                showUpdateBanner();
            }
        });
    </script>
    
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