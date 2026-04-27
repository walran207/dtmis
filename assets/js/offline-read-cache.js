(function () {
    'use strict';

    if (typeof window === 'undefined' || typeof window.fetch !== 'function') {
        return;
    }

    if (window.__edatsOfflineReadCacheInstalled) {
        return;
    }
    window.__edatsOfflineReadCacheInstalled = true;

    if (typeof window.indexedDB === 'undefined') {
        return;
    }

    var DB_NAME = 'edats-offline-read-cache';
    var DB_VERSION = 1;
    var STORE_NAME = 'responses';
    var MAX_RECORDS = 300;
    var MAX_RECORDS_PER_SCOPE = 120;
    var MAX_RECORD_BODY_BYTES = 1024 * 1024;
    var MAX_SCOPE_STORAGE_BYTES = 20 * 1024 * 1024;
    var SESSION_INVALID_STATUSES = { 401: true, 419: true };
    var VOLATILE_QUERY_KEYS = {
        t: true,
        _: true,
        ts: true,
        timestamp: true,
        cachebust: true,
        cb: true
    };
    var TARGET_RULES = [
        {
            kind: 'dashboard_live',
            ttlMs: 5 * 60 * 1000,
            pattern: /\/(?:app\/)?actions\/dashboard-live\.php$/i
        },
        {
            kind: 'document_details',
            ttlMs: 24 * 60 * 60 * 1000,
            pattern: /\/(?:app\/)?actions\/document-details\.php$/i
        },
        {
            kind: 'notifications_feed',
            ttlMs: 2 * 60 * 1000,
            pattern: /\/(?:app\/)?actions\/notifications-feed\.php$/i
        }
    ];

    var dbPromise = null;
    var nativeFetch = window.fetch.bind(window);

    function textBytes(value) {
        var text = String(value == null ? '' : value);
        try {
            return new Blob([text]).size;
        } catch (error) {
            return text.length * 2;
        }
    }

    function scopeFromCacheKey(cacheKey) {
        var key = String(cacheKey || '');
        var idx = key.indexOf('|');
        return idx > 0 ? key.slice(0, idx) : key;
    }

    function notifySessionInvalid(statusCode, reason) {
        try {
            window.dispatchEvent(new CustomEvent('edats:session-invalid', {
                detail: {
                    source: 'offline-read-cache',
                    statusCode: Number(statusCode || 0),
                    reason: String(reason || 'session-invalid')
                }
            }));
        } catch (error) {
            // Ignore custom event errors.
        }
    }

    function requestToPromise(request) {
        return new Promise(function (resolve, reject) {
            request.onsuccess = function () {
                resolve(request.result);
            };
            request.onerror = function () {
                reject(request.error || new Error('IndexedDB request failed.'));
            };
        });
    }

    function openDatabase() {
        if (dbPromise) {
            return dbPromise;
        }

        dbPromise = new Promise(function (resolve) {
            var openRequest;
            try {
                openRequest = indexedDB.open(DB_NAME, DB_VERSION);
            } catch (error) {
                resolve(null);
                return;
            }

            openRequest.onupgradeneeded = function (event) {
                var db = event.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME, { keyPath: 'key' });
                }
            };

            openRequest.onsuccess = function () {
                resolve(openRequest.result);
            };

            openRequest.onerror = function () {
                resolve(null);
            };
        });

        return dbPromise;
    }

    async function getRecord(key) {
        var db = await openDatabase();
        if (!db) {
            return null;
        }

        try {
            var tx = db.transaction(STORE_NAME, 'readonly');
            var store = tx.objectStore(STORE_NAME);
            var result = await requestToPromise(store.get(key));
            return result || null;
        } catch (error) {
            return null;
        }
    }

    async function putRecord(record) {
        var db = await openDatabase();
        if (!db) {
            return false;
        }

        try {
            await new Promise(function (resolve) {
                var tx = db.transaction(STORE_NAME, 'readwrite');
                tx.oncomplete = function () {
                    resolve(true);
                };
                tx.onerror = function () {
                    resolve(false);
                };
                tx.onabort = function () {
                    resolve(false);
                };
                tx.objectStore(STORE_NAME).put(record);
            });
            return true;
        } catch (error) {
            return false;
        }
    }

    async function pruneRecordsIfNeeded() {
        if (Math.random() > 0.04) {
            return;
        }

        var db = await openDatabase();
        if (!db) {
            return;
        }

        var now = Date.now();
        try {
            await new Promise(function (resolve) {
                var tx = db.transaction(STORE_NAME, 'readwrite');
                var store = tx.objectStore(STORE_NAME);
                var cursorRequest = store.openCursor();
                var allRecords = [];
                var deleteMap = Object.create(null);

                cursorRequest.onsuccess = function (event) {
                    var cursor = event.target.result;
                    if (!cursor) {
                        allRecords.sort(function (a, b) {
                            return b.updatedAt - a.updatedAt;
                        });

                        if (allRecords.length > MAX_RECORDS) {
                            var overflow = allRecords.slice(MAX_RECORDS);
                            overflow.forEach(function (entry) {
                                deleteMap[String(entry.key || '')] = true;
                            });
                        }

                        var byScope = Object.create(null);
                        allRecords.forEach(function (entry) {
                            var key = String(entry.key || '');
                            if (key === '' || deleteMap[key]) {
                                return;
                            }
                            var scope = String(entry.scope || '');
                            if (!Object.prototype.hasOwnProperty.call(byScope, scope)) {
                                byScope[scope] = [];
                            }
                            byScope[scope].push(entry);
                        });

                        Object.keys(byScope).forEach(function (scope) {
                            var records = byScope[scope];
                            records.sort(function (a, b) {
                                return b.updatedAt - a.updatedAt;
                            });
                            var scopeBytes = 0;
                            for (var i = 0; i < records.length; i += 1) {
                                var entry = records[i];
                                var key = String(entry.key || '');
                                if (deleteMap[key]) {
                                    continue;
                                }
                                var recordBytes = Math.max(0, Number(entry.sizeBytes || 0));
                                var keepByCount = i < MAX_RECORDS_PER_SCOPE;
                                var keepBySize = (scopeBytes + recordBytes) <= MAX_SCOPE_STORAGE_BYTES;
                                if (keepByCount && keepBySize) {
                                    scopeBytes += recordBytes;
                                    continue;
                                }
                                deleteMap[key] = true;
                            }
                        });

                        Object.keys(deleteMap).forEach(function (key) {
                            if (key !== '') {
                                store.delete(key);
                            }
                        });
                        return;
                    }

                    var value = cursor.value || {};
                    var expiresAt = Number(value.expiresAt || 0);
                    if (expiresAt > 0 && expiresAt < now) {
                        cursor.delete();
                    } else {
                        allRecords.push({
                            key: String(value.key || ''),
                            updatedAt: Number(value.updatedAt || 0),
                            sizeBytes: Math.max(0, Number(value.sizeBytes || 0)),
                            scope: scopeFromCacheKey(String(value.key || ''))
                        });
                    }
                    cursor.continue();
                };

                cursorRequest.onerror = function () {
                    resolve();
                };
                tx.oncomplete = function () {
                    resolve();
                };
                tx.onerror = function () {
                    resolve();
                };
                tx.onabort = function () {
                    resolve();
                };
            });
        } catch (error) {
            // Ignore cleanup failures.
        }
    }

    function getCurrentScopeKey() {
        var explicitScope = String(window.__EDATS_CACHE_SCOPE || '').trim();
        if (explicitScope !== '') {
            return explicitScope;
        }

        var roleKey = '';
        try {
            roleKey = String(
                (document.body && document.body.getAttribute('data-role-key')) || ''
            ).trim().toUpperCase();
        } catch (error) {
            roleKey = '';
        }

        var pathParts = String(window.location.pathname || '').split('/').filter(Boolean);
        var rootSegment = pathParts.length > 0 ? pathParts[0].toLowerCase() : 'root';
        if (roleKey !== '') {
            return 'role:' + roleKey + '|root:' + rootSegment;
        }
        return 'root:' + rootSegment;
    }

    function matchTargetRule(pathname) {
        for (var i = 0; i < TARGET_RULES.length; i += 1) {
            if (TARGET_RULES[i].pattern.test(pathname)) {
                return TARGET_RULES[i];
            }
        }
        return null;
    }

    function normalizeUrlForCache(rawUrl) {
        var url = new URL(rawUrl.toString());
        var entries = [];

        url.hash = '';
        url.searchParams.forEach(function (value, key) {
            var normalizedKey = String(key || '').toLowerCase();
            if (VOLATILE_QUERY_KEYS[normalizedKey]) {
                return;
            }
            entries.push([key, value]);
        });

        entries.sort(function (a, b) {
            if (a[0] < b[0]) {
                return -1;
            }
            if (a[0] > b[0]) {
                return 1;
            }
            if (a[1] < b[1]) {
                return -1;
            }
            if (a[1] > b[1]) {
                return 1;
            }
            return 0;
        });

        url.search = '';
        for (var i = 0; i < entries.length; i += 1) {
            url.searchParams.append(entries[i][0], entries[i][1]);
        }

        return url;
    }

    function buildCacheKey(url, rule) {
        var normalized = normalizeUrlForCache(url);
        var search = normalized.search ? normalized.search : '';
        return [
            getCurrentScopeKey(),
            String(rule.kind || 'read'),
            normalized.pathname.toLowerCase(),
            search
        ].join('|');
    }

    function isJsonResponse(response) {
        if (!response || !response.headers) {
            return false;
        }
        var contentType = String(response.headers.get('Content-Type') || '').toLowerCase();
        return contentType.indexOf('application/json') !== -1 || contentType.indexOf('+json') !== -1;
    }

    function serializeHeaders(response) {
        var safeHeaders = [];
        if (!response || !response.headers) {
            return safeHeaders;
        }

        response.headers.forEach(function (value, key) {
            var normalizedKey = String(key || '').toLowerCase();
            if (normalizedKey === 'content-type' || normalizedKey === 'content-language') {
                safeHeaders.push([key, value]);
            }
        });
        return safeHeaders;
    }

    async function cacheNetworkResponse(cacheKey, rule, requestUrl, response) {
        if (!response || !response.ok || !isJsonResponse(response)) {
            return;
        }

        var bodyText = '';
        try {
            bodyText = await response.clone().text();
        } catch (error) {
            return;
        }

        if (String(bodyText || '').trim() === '') {
            return;
        }
        var bodyBytes = textBytes(bodyText);
        if (bodyBytes > MAX_RECORD_BODY_BYTES) {
            return;
        }

        var now = Date.now();
        var record = {
            key: cacheKey,
            kind: String(rule.kind || ''),
            url: normalizeUrlForCache(requestUrl).toString(),
            status: Number(response.status || 200),
            statusText: String(response.statusText || 'OK'),
            headers: serializeHeaders(response),
            bodyText: bodyText,
            sizeBytes: bodyBytes,
            updatedAt: now,
            expiresAt: now + Number(rule.ttlMs || 0)
        };

        await putRecord(record);
        pruneRecordsIfNeeded();
    }

    async function responseFromCache(cacheKey) {
        var record = await getRecord(cacheKey);
        if (!record || String(record.bodyText || '') === '') {
            return null;
        }

        var headers = new Headers(Array.isArray(record.headers) ? record.headers : []);
        if (!headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/json; charset=UTF-8');
        }
        headers.set('X-eDATS-Offline-Cache', 'HIT');
        headers.set('X-eDATS-Offline-Stale', Date.now() > Number(record.expiresAt || 0) ? '1' : '0');
        if (Number(record.updatedAt || 0) > 0) {
            headers.set('X-eDATS-Offline-Updated-At', new Date(Number(record.updatedAt)).toISOString());
        }

        return new Response(String(record.bodyText || ''), {
            status: Number(record.status || 200),
            statusText: String(record.statusText || 'OK'),
            headers: headers
        });
    }

    window.fetch = async function (input, init) {
        var request;
        try {
            request = input instanceof Request
                ? (init ? new Request(input, init) : input)
                : new Request(input, init);
        } catch (error) {
            return nativeFetch(input, init);
        }

        var method = String(request.method || 'GET').toUpperCase();
        if (method !== 'GET') {
            return nativeFetch(input, init);
        }

        var requestUrl = new URL(request.url, window.location.origin);
        if (requestUrl.origin !== window.location.origin) {
            return nativeFetch(input, init);
        }

        var rule = matchTargetRule(requestUrl.pathname);
        if (!rule) {
            return nativeFetch(input, init);
        }

        var cacheKey = buildCacheKey(requestUrl, rule);

        try {
            var networkResponse = await nativeFetch(request);
            if (SESSION_INVALID_STATUSES[Number(networkResponse.status || 0)]) {
                notifySessionInvalid(Number(networkResponse.status || 0), 'network-response');
            }
            cacheNetworkResponse(cacheKey, rule, requestUrl, networkResponse);
            return networkResponse;
        } catch (error) {
            if (error && error.name === 'AbortError') {
                throw error;
            }

            var cachedResponse = await responseFromCache(cacheKey);
            if (cachedResponse) {
                try {
                    window.dispatchEvent(new CustomEvent('edats:offline-read-hit', {
                        detail: {
                            endpoint: rule.kind,
                            key: cacheKey,
                            url: normalizeUrlForCache(requestUrl).toString()
                        }
                    }));
                } catch (eventError) {
                    // Ignore custom event errors.
                }
                return cachedResponse;
            }
            throw error;
        }
    };

    window.edatsOfflineReadCache = {
        version: 'phase6',
        limits: {
            maxRecords: MAX_RECORDS,
            maxRecordsPerScope: MAX_RECORDS_PER_SCOPE,
            maxRecordBodyBytes: MAX_RECORD_BODY_BYTES,
            maxScopeStorageBytes: MAX_SCOPE_STORAGE_BYTES
        },
        clear: async function () {
            var db = null;
            try {
                db = await openDatabase();
            } catch (error) {
                db = null;
            }
            if (db && typeof db.close === 'function') {
                try {
                    db.close();
                } catch (closeError) {
                    // Ignore close errors and continue delete attempt.
                }
            }

            dbPromise = null;
            return new Promise(function (resolve) {
                var deleteRequest;
                try {
                    deleteRequest = indexedDB.deleteDatabase(DB_NAME);
                } catch (error) {
                    resolve(false);
                    return;
                }
                deleteRequest.onsuccess = function () {
                    dbPromise = null;
                    resolve(true);
                };
                deleteRequest.onerror = function () {
                    resolve(false);
                };
                deleteRequest.onblocked = function () {
                    resolve(false);
                };
            });
        }
    };
})();
