// sw.js - Service Worker for LogIt PWA
const CACHE_NAME = 'logit-v1.0.1';
const urlsToCache = [
    '/',
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
                            <html>
                            <head>
                                <title>LogIt - Offline</title>
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <style>
                                    body { 
                                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                                        text-align: center; 
                                        padding: 2rem; 
                                        background: #f8f9fa;
                                        color: #343a40;
                                        margin: 0;
                                    }
                                    .offline-container {
                                        max-width: 400px;
                                        margin: 2rem auto;
                                        padding: 2rem;
                                        background: white;
                                        border-radius: 12px;
                                        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
                                    }
                                    .offline-icon { 
                                        font-size: 4rem; 
                                        color: #fd7e14; 
                                        margin-bottom: 1rem; 
                                    }
                                    .btn {
                                        background: #fd7e14;
                                        color: white;
                                        padding: 0.75rem 1.5rem;
                                        border: none;
                                        border-radius: 8px;
                                        text-decoration: none;
                                        display: inline-block;
                                        margin-top: 1rem;
                                        cursor: pointer;
                                        font-size: 1rem;
                                    }
                                    .btn:hover {
                                        background: #e67e22;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="offline-container">
                                    <div class="offline-icon">📱</div>
                                    <h1>You're Offline</h1>
                                    <p>LogIt is currently offline. Please check your internet connection and try again.</p>
                                    <button class="btn" onclick="window.location.reload()">Try Again</button>
                                    <br><br>
                                    <a href="/" class="btn" style="background: #0d6efd;">Go to Home</a>
                                </div>
                            </body>
                            </html>
                        `, {
                            headers: { 'Content-Type': 'text/html' }
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