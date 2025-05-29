// sw.js - Service Worker for LogIt PWA
const CACHE_NAME = 'logit-v1.0.2';
const urlsToCache = [
    '/',
    '/index.php',
    '/login.php',
    '/register.php',
    '/manifest.json',
    'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js'
];

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
            .catch((error) => {
                console.error('Cache installation failed:', error);
            })
    );
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - improved redirect handling
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests and external resources
    if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                // Return cached version if available
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                // Fetch from network with proper redirect handling
                return fetch(event.request, {
                    redirect: 'follow',
                    credentials: 'same-origin'
                }).then((response) => {
                    // Check if we received a valid response
                    if (!response || response.status !== 200) {
                        return response;
                    }
                    
                    // Don't cache redirects or error responses
                    if (response.type !== 'basic' && response.type !== 'cors') {
                        return response;
                    }
                    
                    // Don't cache PHP pages that require authentication
                    const url = new URL(event.request.url);
                    const restrictedPages = [
                        '/dashboard.php',
                        '/upload.php',
                        '/boxes.php',
                        '/actions.php'
                    ];
                    
                    if (restrictedPages.some(page => url.pathname.includes(page))) {
                        return response;
                    }
                    
                    // Clone and cache for future use (only static resources)
                    const responseToCache = response.clone();
                    
                    caches.open(CACHE_NAME)
                        .then((cache) => {
                            cache.put(event.request, responseToCache);
                        })
                        .catch(() => {
                            // Silently fail cache operations
                        });
                    
                    return response;
                }).catch(() => {
                    // Return offline page for navigation requests
                    if (event.request.destination === 'document') {
                        return new Response(`
                            <!DOCTYPE html>
                            <html lang="en">
                            <head>
                                <title>LogIt - You're Offline</title>
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <meta name="theme-color" content="#fd7e14">
                                <style>
                                    * {
                                        box-sizing: border-box;
                                        margin: 0;
                                        padding: 0;
                                    }
                                    body { 
                                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                        color: white;
                                        min-height: 100vh;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        padding: 20px;
                                    }
                                    .offline-container {
                                        max-width: 400px;
                                        text-align: center;
                                        background: rgba(255, 255, 255, 0.1);
                                        backdrop-filter: blur(20px);
                                        border-radius: 20px;
                                        padding: 3rem 2rem;
                                        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                    }
                                    .offline-icon { 
                                        font-size: 4rem; 
                                        margin-bottom: 1.5rem;
                                        opacity: 0.9;
                                    }
                                    h1 {
                                        font-size: 2rem;
                                        font-weight: 600;
                                        margin-bottom: 1rem;
                                        color: white;
                                    }
                                    p {
                                        font-size: 1.1rem;
                                        margin-bottom: 2rem;
                                        color: rgba(255, 255, 255, 0.9);
                                        line-height: 1.6;
                                    }
                                    .btn {
                                        background: rgba(255, 255, 255, 0.2);
                                        color: white;
                                        padding: 0.75rem 2rem;
                                        border: 2px solid rgba(255, 255, 255, 0.3);
                                        border-radius: 25px;
                                        text-decoration: none;
                                        display: inline-block;
                                        margin: 0.5rem;
                                        cursor: pointer;
                                        font-size: 1rem;
                                        font-weight: 500;
                                        transition: all 0.3s ease;
                                        backdrop-filter: blur(10px);
                                    }
                                    .btn:hover {
                                        background: rgba(255, 255, 255, 0.3);
                                        border-color: rgba(255, 255, 255, 0.5);
                                        transform: translateY(-2px);
                                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                                    }
                                    .btn-primary {
                                        background: #fd7e14;
                                        border-color: #fd7e14;
                                    }
                                    .btn-primary:hover {
                                        background: #e67e22;
                                        border-color: #e67e22;
                                    }
                                    .connection-status {
                                        margin-top: 2rem;
                                        padding: 1rem;
                                        background: rgba(255, 255, 255, 0.1);
                                        border-radius: 10px;
                                        font-size: 0.9rem;
                                    }
                                    .status-indicator {
                                        display: inline-block;
                                        width: 10px;
                                        height: 10px;
                                        border-radius: 50%;
                                        background: #dc3545;
                                        margin-right: 0.5rem;
                                        animation: pulse 2s infinite;
                                    }
                                    @keyframes pulse {
                                        0% { opacity: 1; }
                                        50% { opacity: 0.5; }
                                        100% { opacity: 1; }
                                    }
                                    .logo {
                                        font-size: 1.5rem;
                                        font-weight: 700;
                                        margin-bottom: 2rem;
                                        color: #fd7e14;
                                    }
                                    
                                    @media (max-width: 480px) {
                                        .offline-container {
                                            padding: 2rem 1.5rem;
                                        }
                                        h1 {
                                            font-size: 1.75rem;
                                        }
                                        .offline-icon {
                                            font-size: 3rem;
                                        }
                                        .btn {
                                            padding: 0.75rem 1.5rem;
                                            font-size: 0.9rem;
                                        }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="offline-container">
                                    <div class="logo">📱 LogIt</div>
                                    <div class="offline-icon">🌐</div>
                                    <h1>You're Offline</h1>
                                    <p>It looks like you're not connected to the internet. LogIt needs an internet connection to sync your receipts and access your account.</p>
                                    
                                    <button type="button" class="btn btn-primary" onclick="retryConnection()">
                                        Try Again
                                    </button>
                                    <a href="/" class="btn">
                                        Go to Home
                                    </a>
                                    
                                    <div class="connection-status">
                                        <span class="status-indicator" id="statusIndicator"></span>
                                        <span id="statusText">Checking connection...</span>
                                    </div>
                                </div>
                                
                                <script>
                                    let isOnline = navigator.onLine;
                                    
                                    function updateConnectionStatus() {
                                        const indicator = document.getElementById('statusIndicator');
                                        const statusText = document.getElementById('statusText');
                                        
                                        if (navigator.onLine) {
                                            indicator.style.background = '#28a745';
                                            statusText.textContent = 'Connection restored! You can reload the page.';
                                        } else {
                                            indicator.style.background = '#dc3545';
                                            statusText.textContent = 'No internet connection detected.';
                                        }
                                    }
                                    
                                    function retryConnection() {
                                        const btn = event.target;
                                        btn.innerHTML = 'Checking...';
                                        btn.disabled = true;
                                        
                                        // Try to reload the page
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1000);
                                    }
                                    
                                    // Listen for connection changes
                                    window.addEventListener('online', () => {
                                        updateConnectionStatus();
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1500);
                                    });
                                    
                                    window.addEventListener('offline', updateConnectionStatus);
                                    
                                    // Initial status check
                                    updateConnectionStatus();
                                    
                                    // Periodic connection check
                                    setInterval(() => {
                                        fetch('/', { method: 'HEAD', cache: 'no-cache' })
                                            .then(() => {
                                                if (!isOnline) {
                                                    isOnline = true;
                                                    updateConnectionStatus();
                                                }
                                            })
                                            .catch(() => {
                                                if (isOnline) {
                                                    isOnline = false;
                                                    updateConnectionStatus();
                                                }
                                            });
                                    }, 5000);
                                </script>
                            </body>
                            </html>
                        `, {
                            headers: { 
                                'Content-Type': 'text/html',
                                'Cache-Control': 'no-cache'
                            }
                        });
                    }
                    
                    // For other resources, just fail
                    return new Response('Network error', { status: 408 });
                });
            })
    );
});

// Background sync for offline uploads
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync-upload') {
        event.waitUntil(processOfflineUploads());
    }
});

// Handle offline upload queue
async function processOfflineUploads() {
    try {
        const cache = await caches.open('offline-uploads');
        const requests = await cache.keys();
        
        for (const request of requests) {
            try {
                const response = await fetch(request, {
                    redirect: 'follow',
                    credentials: 'same-origin'
                });
                if (response.ok) {
                    await cache.delete(request);
                    console.log('Offline upload processed:', request.url);
                }
            } catch (error) {
                console.error('Failed to process offline upload:', error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Push notifications
self.addEventListener('push', (event) => {
    const options = {
        body: event.data ? event.data.text() : 'New notification from LogIt',
        icon: '/manifest.json',
        badge: '/manifest.json',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'view',
                title: 'View',
                icon: '/manifest.json'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('LogIt', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Message handling
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Handle failed requests more gracefully
self.addEventListener('error', (event) => {
    console.log('Service Worker error:', event.error);
});

self.addEventListener('unhandledrejection', (event) => {
    console.log('Service Worker unhandled rejection:', event.reason);
    event.preventDefault();
});