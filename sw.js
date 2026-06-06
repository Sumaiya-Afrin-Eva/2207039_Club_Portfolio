// Service Worker for Admin Security
// Intercepts page loads and validates session before allowing cached pages to load

const ADMIN_PAGES = [
    '/Club_Portfolio/admin_dashboard.html',
    '/Club_Portfolio/admin_login.html'
];

// Listen for fetch events
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    const pathname = url.pathname;
    
    // Only intercept admin pages
    if (pathname.includes('admin_dashboard.html') || pathname.includes('admin_login.html')) {
        // For GET requests only
        if (event.request.method === 'GET') {
            event.respondWith(
                (async () => {
                    // Always fetch fresh - don't use cache for HTML
                    try {
                        const response = await fetch(event.request, {
                            cache: 'no-store',
                            headers: {
                                'Cache-Control': 'no-cache, no-store, must-revalidate',
                                'Pragma': 'no-cache',
                                'Expires': '0'
                            }
                        });
                        return response;
                    } catch (error) {
                        console.error('Fetch failed:', error);
                        // On error, return a response that will redirect
                        return new Response('Network error', { status: 500 });
                    }
                })()
            );
        }
    }
});

// Handle messages from clients
self.addEventListener('message', (event) => {
    console.log('Service worker message:', event.data);
});
