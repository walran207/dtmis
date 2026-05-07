const CACHE_PREFIX = 'edats-shell';
const CACHE_VERSION = 'v7';
const CACHE_NAME = `${CACHE_PREFIX}-${CACHE_VERSION}`;
const API_PATH_MARKERS = ['/actions/', '/app/actions/'];
const STATIC_EXTENSIONS = /\.(?:css|js|mjs|png|jpg|jpeg|gif|webp|svg|ico|woff2?|ttf|eot|wav|mp3|json)$/i;
const STATIC_DESTINATIONS = new Set(['style', 'script', 'image', 'font', 'audio']);

const scopeUrl = new URL(self.registration.scope);
const scopePath = scopeUrl.pathname.endsWith('/')
    ? scopeUrl.pathname.slice(0, -1)
    : scopeUrl.pathname;

function normalizedScopePath() {
    if (scopePath === '') {
        return '';
    }
    return scopePath.startsWith('/') ? scopePath : `/${scopePath}`;
}

function scopedAssetPath(relativePath) {
    const cleanPath = String(relativePath || '').replace(/^\/+/, '');
    const root = normalizedScopePath();
    const resolvedPath = root === '' ? `/${cleanPath}` : `${root}/${cleanPath}`;
    return resolvedPath.replace(/\/{2,}/g, '/');
}

const OFFLINE_FALLBACK_PATH = scopedAssetPath('offline.html');
const APP_SHELL_PATHS = [
    '',
    'index.php',
    'offline.html',
    'manifest.webmanifest',
    'assets/Logo.png',
    'assets/audio/notif-sound.wav',
    'partials-css/partial.css',
    'assets/css/dashboard.css',
    'assets/css/dark-overrides.css',
    'assets/design-system/tokens.css',
    'assets/design-system/themes.css',
    'assets/design-system/role-accents.css',
    'assets/design-system/responsive.css',
    'assets/design-system/components.css',
    'assets/design-system/utilities.css',
    'assets/design-system/theme-toggle.js',
    'assets/js/vendor/qrcode-generator.js',
    'assets/js/local-qr.js',
    'assets/js/offline-read-cache.js',
    'assets/js/offline-outbox.js',
    'assets/js/pwa-init.js',
    'auth/css/login.css',
    'auth/css/register.css',
    'auth/css/forgot-password.css',
    'auth/js/theme-sync.js',
    'auth/js/login.js',
    'auth/js/register.js',
    'auth/js/forgot-password.js',
].map(scopedAssetPath);

function requestIsInScope(url) {
    const root = normalizedScopePath();
    if (root === '' || root === '/') {
        return true;
    }
    return url.pathname === root || url.pathname.startsWith(`${root}/`);
}

function isApiRequest(pathname) {
    return API_PATH_MARKERS.some((marker) => pathname.includes(marker));
}

function isStaticRequest(request, pathname) {
    if (STATIC_DESTINATIONS.has(request.destination)) {
        return true;
    }
    return STATIC_EXTENSIONS.test(pathname);
}

function isCacheableResponse(response) {
    return response && (response.status === 200 || response.type === 'opaque');
}

function isNavigationCacheableResponse(response) {
    if (!response || response.status !== 200 || response.type === 'opaque') {
        return false;
    }

    const contentType = String(response.headers.get('Content-Type') || '').toLowerCase();
    return contentType.includes('text/html');
}

async function matchCachedNavigation(cache, request) {
    const candidates = [
        request,
        request.url,
        scopedAssetPath('index.php'),
        scopedAssetPath(''),
    ];

    for (const candidate of candidates) {
        const cachedResponse = await cache.match(candidate);
        if (cachedResponse) {
            return cachedResponse;
        }
    }

    return null;
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE_NAME);
            const prefetchTasks = APP_SHELL_PATHS.map(async (assetPath) => {
                try {
                    const response = await fetch(assetPath, { cache: 'no-cache' });
                    if (isCacheableResponse(response)) {
                        await cache.put(assetPath, response.clone());
                    }
                } catch (error) {
                    // Skip missing assets and continue caching the rest.
                }
            });
            await Promise.all(prefetchTasks);
            await self.skipWaiting();
        })()
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const cacheNames = await caches.keys();
            const staleCaches = cacheNames.filter((cacheName) => {
                return cacheName.startsWith(CACHE_PREFIX) && cacheName !== CACHE_NAME;
            });
            await Promise.all(staleCaches.map((cacheName) => caches.delete(cacheName)));
            await self.clients.claim();
        })()
    );
});

async function handleNavigationRequest(request) {
    const cache = await caches.open(CACHE_NAME);

    try {
        const networkResponse = await fetch(request);
        if (isNavigationCacheableResponse(networkResponse)) {
            await cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cachedNavigation = await matchCachedNavigation(cache, request);
        if (cachedNavigation) {
            return cachedNavigation;
        }

        const offlineFallback = await cache.match(OFFLINE_FALLBACK_PATH);
        if (offlineFallback) {
            return offlineFallback;
        }

        return new Response('Offline. Please reconnect and try again.', {
            status: 503,
            headers: {
                'Content-Type': 'text/plain; charset=UTF-8',
            },
        });
    }
}

function handleStaticRequest(event) {
    const request = event.request;

    return (async () => {
        const cache = await caches.open(CACHE_NAME);
        const cachedResponse = await cache.match(request);
        const networkPromise = fetch(request)
            .then(async (networkResponse) => {
                if (isCacheableResponse(networkResponse)) {
                    await cache.put(request, networkResponse.clone());
                }
                return networkResponse;
            })
            .catch(() => null);

        if (cachedResponse) {
            event.waitUntil(networkPromise);
            return cachedResponse;
        }

        const networkResponse = await networkPromise;
        if (networkResponse) {
            return networkResponse;
        }

        return new Response('', {
            status: 504,
            statusText: 'Offline',
        });
    })();
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (!requestIsInScope(url)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigationRequest(request));
        return;
    }

    if (isApiRequest(url.pathname)) {
        // Let page-level offline handlers decide how to recover API requests.
        return;
    }

    if (isStaticRequest(request, url.pathname)) {
        event.respondWith(handleStaticRequest(event));
        return;
    }
});
