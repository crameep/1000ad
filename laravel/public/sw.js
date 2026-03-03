const CACHE_NAME = '1000ad-v2';
const SHELL_ASSETS = [
    '/css/game.css',
    '/js/game.js',
    '/images/bg.gif',
    '/images/wood.gif',
    '/images/food.gif',
    '/images/gold.gif',
    '/images/iron.gif',
    '/images/tools.gif',
    '/images/mland.gif',
    '/images/fland.gif',
    '/images/pland.gif',
    '/images/mland_free.gif',
    '/images/fland_free.gif',
    '/images/pland_free.gif',
    '/images/people.gif',
    '/images/map.jpg',
    '/images/icons/icon-192.png',
    '/manifest.json'
];

// Install: cache static assets
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) { return cache.addAll(SHELL_ASSETS); })
            .then(function() { return self.skipWaiting(); })
    );
});

// Activate: clean old caches
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(
                keys.filter(function(key) { return key !== CACHE_NAME; })
                    .map(function(key) { return caches.delete(key); })
            );
        }).then(function() { return self.clients.claim(); })
    );
});

// Fetch: network-first for CSS/JS, cache-first for images, network-first for HTML
self.addEventListener('fetch', function(event) {
    var url = new URL(event.request.url);

    // CSS and JS: network-first (these change during development)
    if (url.pathname.match(/\.(css|js)$/)) {
        event.respondWith(
            fetch(event.request).then(function(response) {
                var clone = response.clone();
                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(event.request, clone);
                });
                return response;
            }).catch(function() {
                return caches.match(event.request);
            })
        );
        return;
    }

    // Images and fonts: cache-first (rarely change)
    if (url.pathname.match(/\.(gif|jpg|png|ico|woff2?|ttf|svg)$/)) {
        event.respondWith(
            caches.match(event.request)
                .then(function(cached) {
                    if (cached) return cached;
                    return fetch(event.request).then(function(response) {
                        var clone = response.clone();
                        caches.open(CACHE_NAME).then(function(cache) {
                            cache.put(event.request, clone);
                        });
                        return response;
                    });
                })
        );
        return;
    }

    // HTML pages: network-first (game data must be live)
    event.respondWith(
        fetch(event.request).catch(function() {
            return new Response(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
                '<style>body{background:#0d0b09;color:#e8dcc8;font-family:Verdana,sans-serif;text-align:center;padding-top:30vh;}' +
                'h1{font-family:Cinzel,serif;color:#c9a84c;font-size:32px;}p{color:#a89878;}</style></head>' +
                '<body><h1>1000 A.D.</h1><p>You are offline.<br>Please check your connection and try again.</p></body></html>',
                { headers: { 'Content-Type': 'text/html' } }
            );
        })
    );
});
