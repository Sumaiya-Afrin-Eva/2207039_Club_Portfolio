// Service Worker for Admin Security
// Intercepts page loads and NEVER caches admin pages - always fetch fresh
// Optimized for Chrome's aggressive caching behavior

const ADMIN_PAGES = [
    'admin_dashboard.html',
    'admin_login.html'
];

// Install event - clear caches for admin pages
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing...');
    self.skipWaiting(); // Activate immediately
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    return caches.open(cacheName).then((cache) => {
                        return cache.keys().then((requests) => {
                            return Promise.all(
                                requests.map((request) => {
                                    if (ADMIN_PAGES.some(page => request.url.includes(page))) {
                                        console.log('[ServiceWorker] Deleting cached admin page:', request.url);
                                        return cache.delete(request);
                                    }
                                })
                            );
                        });
                    });
                })
            );
        })
    );
});

// Activate event - clean up old caches and take over immediately
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');
    event.waitUntil(clients.claim()); // Take control of all clients immediately
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    return caches.open(cacheName).then((cache) => {
                        return cache.keys().then((requests) => {
                            return Promise.all(
                                requests.map((request) => {
                                    if (ADMIN_PAGES.some(page => request.url.includes(page))) {
                                        console.log('[ServiceWorker] Deleting cached admin page on activate:', request.url);
                                        return cache.delete(request);
                                    }
                                })
                            );
                        });
                    });
                })
            );
        })
    );
});

// Fetch event - NEVER cache admin pages, always fetch fresh for Chrome compatibility
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    const pathname = url.pathname;
    
    // Check if this is an admin page
    const isAdminPage = ADMIN_PAGES.some(page => pathname.includes(page));
    
    if (isAdminPage && event.request.method === 'GET') {
        // ALWAYS fetch fresh for admin pages - no caching whatsoever
        // This is critical for Chrome which has aggressive caching
        event.respondWith(
            fetch(event.request, {
                cache: 'no-store',
                credentials: 'include'
            }).then((response) => {
                // Never cache - create new response without caching
                if (response && response.status === 200) {
                    const clonedResponse = response.clone();
                    
                    // Create response with no-cache headers
                    const headers = new Headers(clonedResponse.headers);
                    headers.set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                    headers.set('Pragma', 'no-cache');
                    headers.set('Expires', '-1');
                    
                    return new Response(clonedResponse.body, {
                        status: clonedResponse.status,
                        statusText: clonedResponse.statusText,
                        headers: headers
                    });
                }
                return response;
            }).catch((error) => {
                console.error('[ServiceWorker] Fetch error for admin page:', error);
                // Network error - don't serve cached version
                return new Response('Network error - please check your connection', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain' }
                });
            })
        );
    } else {
        // For non-admin pages, use default behavior
        event.respondWith(fetch(event.request));
    }
});

// Handle messages from clients (for cache clearing)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (event.data && event.data.type === 'CLEAR_ADMIN_CACHE') {
        // Clear admin page cache on demand
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    return caches.open(cacheName).then((cache) => {
                        return cache.keys().then((requests) => {
                            return Promise.all(
                                requests.map((request) => {
                                    if (ADMIN_PAGES.some(page => request.url.includes(page))) {
                                        return cache.delete(request);
                                    }
                                })
                            );
                        });
                    });
                })
            );
        });
    }
});
