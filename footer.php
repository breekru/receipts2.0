<?php
// footer.php - Common footer for all pages
?>
    </div> <!-- Close container from header -->
    
    <footer class="mt-5 py-4 bg-light text-center d-none d-md-block">
        <div class="container">
            <p class="text-muted mb-0">
                &copy; 2024 LogIt - Simple Receipt Management
                <span class="mx-2">|</span>
                <small>Keep your receipts organized</small>
            </p>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced PWA Install Prompt -->
    <div id="pwaInstallPrompt" class="pwa-install-prompt d-none">
        <div class="d-flex align-items-center justify-content-between">
            <div class="flex-grow-1 me-3">
                <div class="fw-bold mb-1">📱 Install LogIt</div>
                <small>Add to your home screen for the best experience!</small>
            </div>
            <div class="d-flex gap-2">
                <button id="pwaInstallBtn" class="btn btn-light btn-sm">Install</button>
                <button id="pwaDismissBtn" class="btn btn-outline-light btn-sm">✕</button>
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
    
    <!-- Session Timeout Warning -->
    <div id="sessionTimeoutModal" class="modal fade" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-clock me-2"></i>Session Expiring
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas fa-hourglass-half fa-3x text-warning mb-3"></i>
                    <h5>Your session will expire in <span id="timeoutCountdown">60</span> seconds</h5>
                    <p class="text-muted">Click "Stay Logged In" to continue your session.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="handleSessionTimeout(false)">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout Now
                    </button>
                    <button type="button" class="btn btn-primary" onclick="handleSessionTimeout(true)">
                        <i class="fas fa-refresh me-2"></i>Stay Logged In
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PWA and Service Worker Registration -->
    <script>
        // Enhanced Session Management
        let sessionTimeoutWarning = null;
        let sessionTimeoutFinal = null;
        let timeoutCountdown = 60;
        
        <?php if (is_logged_in()): ?>
        // Check session every 5 minutes
        setInterval(checkSessionStatus, 5 * 60 * 1000);
        
        // Show warning 1 minute before session expires (at 29 minutes)
        setTimeout(showSessionTimeoutWarning, 29 * 60 * 1000);
        
        function checkSessionStatus() {
            fetch('config.php', {
                method: 'HEAD',
                cache: 'no-cache'
            }).catch(() => {
                // If request fails, likely session expired or network issue
                showSessionExpiredMessage();
            });
        }
        
        function showSessionTimeoutWarning() {
            const modal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'));
            modal.show();
            
            // Start countdown
            const countdownElement = document.getElementById('timeoutCountdown');
            timeoutCountdown = 60;
            
            const countdownInterval = setInterval(() => {
                timeoutCountdown--;
                if (countdownElement) countdownElement.textContent = timeoutCountdown;
                
                if (timeoutCountdown <= 0) {
                    clearInterval(countdownInterval);
                    handleSessionTimeout(false);
                }
            }, 1000);
            
            // Auto-logout after 1 minute if no response
            sessionTimeoutFinal = setTimeout(() => {
                clearInterval(countdownInterval);
                handleSessionTimeout(false);
            }, 60000);
        }
        
        function handleSessionTimeout(stayLoggedIn) {
            if (sessionTimeoutFinal) {
                clearTimeout(sessionTimeoutFinal);
                sessionTimeoutFinal = null;
            }
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('sessionTimeoutModal'));
            if (modal) modal.hide();
            
            if (stayLoggedIn) {
                // Refresh session by making a simple request
                fetch('dashboard.php', { 
                    method: 'HEAD',
                    cache: 'no-cache'
                }).then(() => {
                    showNotification('Session extended successfully!', 'success');
                    // Reset the timeout warning for another 29 minutes
                    setTimeout(showSessionTimeoutWarning, 29 * 60 * 1000);
                }).catch(() => {
                    showSessionExpiredMessage();
                });
            } else {
                // Logout
                window.location.href = 'logout.php';
            }
        }
        
        function showSessionExpiredMessage() {
            showNotification('Your session has expired. Redirecting to login...', 'warning');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        }
        <?php endif; ?>

        // Enhanced Service Worker Registration with better error handling
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

        // Enhanced PWA Install functionality
        let deferredPrompt;
        const installPrompt = document.getElementById('pwaInstallPrompt');
        const installBtn = document.getElementById('pwaInstallBtn');
        const dismissBtn = document.getElementById('pwaDismissBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Don't show if already dismissed
            if (!localStorage.getItem('pwaInstallDismissed')) {
                showInstallPrompt();
            }
        });

        function showInstallPrompt() {
            if (installPrompt) {
                installPrompt.classList.remove('d-none');
                // Auto-hide after 10 seconds if not interacted with
                setTimeout(() => {
                    if (installPrompt && !installPrompt.classList.contains('d-none')) {
                        installPrompt.classList.add('d-none');
                    }
                }, 10000);
            }
        }

        if (installBtn) {
            installBtn.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`User ${outcome} the install prompt`);
                    deferredPrompt = null;
                    if (installPrompt) {
                        installPrompt.classList.add('d-none');
                    }
                    if (outcome === 'accepted') {
                        showNotification('LogIt has been installed!', 'success');
                    }
                }
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                if (installPrompt) {
                    installPrompt.classList.add('d-none');
                }
                localStorage.setItem('pwaInstallDismissed', 'true');
            });
        }

        // Check if already installed
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            if (installPrompt) {
                installPrompt.classList.add('d-none');
            }
            showNotification('LogIt installed successfully!', 'success');
        });

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

        // Enhanced notification system
        function showNotification(message, type = 'info', duration = 5000) {
            if (!message) return;
            
            const alertClass = type === 'error' ? 'danger' : type;
            const iconClass = type === 'error' ? 'exclamation-triangle' : 
                             type === 'success' ? 'check-circle' : 
                             type === 'warning' ? 'exclamation-triangle' : 'info-circle';
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${alertClass} alert-dismissible fade show position-fixed`;
            alert.style.cssText = `
                top: 20px; 
                right: 20px; 
                z-index: 9999; 
                min-width: 300px;
                max-width: 90vw;
                animation: slideInRight 0.3s ease-out;
            `;
            alert.innerHTML = `
                <i class="fas fa-${iconClass} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alert);
            
            // Auto-remove
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.animation = 'slideOutRight 0.3s ease-in';
                    setTimeout(() => alert.remove(), 300);
                }
            }, duration);
        }

        // Add CSS for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Global error handler for AJAX requests
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            if (event.reason && event.reason.toString().includes('NetworkError')) {
                showNotification('Network connection lost. Please check your internet connection.', 'warning');
            }
        });
        
        // Check for flash messages on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php 
            $success = get_flash('success');
            $error = get_flash('error');
            $info = get_flash('info');
            
            if ($success): ?>
                showNotification('<?php echo addslashes($success); ?>', 'success');
            <?php endif; ?>
            
            <?php if ($error): ?>
                showNotification('<?php echo addslashes($error); ?>', 'error');
            <?php endif; ?>
            
            <?php if ($info): ?>
                showNotification('<?php echo addslashes($info); ?>', 'info');
            <?php endif; ?>
        });

        // Handle service worker messages
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'SW_UPDATED') {
                showUpdateBanner();
            }
        });

        // Make showNotification globally available
        window.showNotification = showNotification;
    </script>
</body>
</html>