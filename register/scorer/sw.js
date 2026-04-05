const CACHE_NAME = 'b2l-scorer-v1';
const urlsToCache = [
    '/b2l/scorer/',
    '/b2l/scorer/css/scorer.css',
    '/b2l/scorer/js/app.js',
    '/b2l/scorer/js/game.js'
];

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE_NAME).then(c => c.addAll(urlsToCache)));
});

self.addEventListener('fetch', e => {
    // API呼び出しはキャッシュしない
    if (e.request.url.includes('/api/')) {
        e.respondWith(fetch(e.request));
        return;
    }
    e.respondWith(
        caches.match(e.request).then(r => r || fetch(e.request))
    );
});
