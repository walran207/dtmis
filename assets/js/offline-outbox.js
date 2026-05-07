(function () {
    'use strict';

    if (typeof window === 'undefined' || typeof window.fetch !== 'function' || typeof window.indexedDB === 'undefined') {
        return;
    }
    if (window.__edatsOfflineOutboxInstalled) {
        return;
    }
    window.__edatsOfflineOutboxInstalled = true;

    var DB_NAME = 'edats-offline-outbox';
    var STORE_NAME = 'operations';
    var ROUTE_CONFIGS = [
        {
            kind: 'intake_create',
            pattern: /\/(?:app\/)?actions\/create-document\.php$/i,
            label: 'intake submission'
        },
        {
            kind: 'document_action',
            pattern: /\/(?:app\/)?actions\/document-action\.php$/i,
            label: 'workflow action'
        }
    ];
    var QUEUEABLE_DOCUMENT_ACTIONS = {
        RECEIVE: true,
        FORWARD: true,
        REROUTE: true,
        RETURN: true,
        APPROVE: true,
        OVERRIDE: true,
        RELEASE: true,
        UNSIGN: true,
        PENDING: true,
        EDIT: true,
        DELETE: true,
        DELETE_ATTACHMENT: true
    };
    var BASE_DELAY_MS = 5000;
    var MAX_DELAY_MS = 300000;
    var TERMINAL_DELAY_MS = 900000;
    var REAUTH_BLOCK_DELAY_MS = 1800000;
    var HEARTBEAT_MS = 10000;
    var MAX_PENDING_PER_SCOPE = 50;
    var MAX_PENDING_BYTES_PER_SCOPE = 120 * 1024 * 1024;
    var MAX_OPERATION_BYTES = 25 * 1024 * 1024;
    var MAX_TEXT_ENTRY_BYTES = 256 * 1024;
    var SESSION_INVALID_STATUSES = { 401: true, 419: true };
    var REAUTH_REQUIRED_STATUS = 428;
    var AUTH_PAGE_PATTERN = /\/auth\/(?:login|register|forgot-password|logout)\.php$/i;
    var EXPLICIT_OFFLINE_CLEAR_QUERY_KEY = 'offline_clear';
    var SERVICE_WORKER_CACHE_PREFIXES = ['edats-shell'];
    var SENSITIVE_LOCAL_STORAGE_PREFIXES = [
        'edats_intake_draft_',
        'edats_notif_read_until_'
    ];

    var nativeFetch = window.fetch.bind(window);
    var dbPromise = null;
    var syncBusy = false;
    var syncQueued = false;
    var sessionInvalidated = false;
    var state = { online: navigator.onLine, pending: 0, syncing: 0, failed: 0, blocked: 0, synced: 0 };
    var syncedAt = 0;
    var badge = null;
    var badgeText = null;
    var badgeBtn = null;

    function reqToPromise(req) {
        return new Promise(function (resolve, reject) {
            req.onsuccess = function () { resolve(req.result); };
            req.onerror = function () { reject(req.error || new Error('IndexedDB request failed.')); };
        });
    }

    function txDone(tx) {
        return new Promise(function (resolve) {
            tx.oncomplete = function () { resolve(true); };
            tx.onerror = function () { resolve(false); };
            tx.onabort = function () { resolve(false); };
        });
    }

    function openDb() {
        if (dbPromise) {
            return dbPromise;
        }
        dbPromise = new Promise(function (resolve) {
            var open;
            try {
                open = indexedDB.open(DB_NAME, 1);
            } catch (error) {
                resolve(null);
                return;
            }
            open.onupgradeneeded = function (event) {
                var db = event.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME, { keyPath: 'operationId' });
                }
            };
            open.onsuccess = function () { resolve(open.result); };
            open.onerror = function () { resolve(null); };
        });
        return dbPromise;
    }

    async function getOp(operationId) {
        var db = await openDb();
        if (!db) { return null; }
        try {
            var tx = db.transaction(STORE_NAME, 'readonly');
            return await reqToPromise(tx.objectStore(STORE_NAME).get(String(operationId || '')));
        } catch (error) {
            return null;
        }
    }

    async function putOp(op) {
        var db = await openDb();
        if (!db) { return false; }
        try {
            var tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).put(op);
            return await txDone(tx);
        } catch (error) {
            return false;
        }
    }

    async function deleteOp(operationId) {
        var db = await openDb();
        if (!db) { return false; }
        try {
            var tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).delete(String(operationId || ''));
            return await txDone(tx);
        } catch (error) {
            return false;
        }
    }

    async function allOps() {
        var db = await openDb();
        if (!db) { return []; }
        try {
            return await new Promise(function (resolve) {
                var tx = db.transaction(STORE_NAME, 'readonly');
                var rows = [];
                var cursor = tx.objectStore(STORE_NAME).openCursor();
                cursor.onsuccess = function (event) {
                    var c = event.target.result;
                    if (!c) { resolve(rows); return; }
                    rows.push(c.value || {});
                    c.continue();
                };
                cursor.onerror = function () { resolve(rows); };
            });
        } catch (error) {
            return [];
        }
    }

    function now() { return Date.now(); }

    function scopeKey() {
        var explicit = String(window.__EDATS_CACHE_SCOPE || '').trim();
        if (explicit !== '') {
            return explicit;
        }
        return 'root:' + String(window.location.pathname || '/').split('/').filter(Boolean)[0];
    }

    function uuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (ch) {
            var r = Math.random() * 16 | 0;
            return (ch === 'x' ? r : ((r & 0x3) | 0x8)).toString(16);
        });
    }

    function textBytes(value) {
        var text = String(value == null ? '' : value);
        try {
            return new Blob([text]).size;
        } catch (error) {
            return text.length * 2;
        }
    }

    function entryBytes(entry) {
        if (!entry || typeof entry !== 'object') {
            return 0;
        }

        var kind = String(entry.kind || '').toLowerCase();
        if (kind === 'file') {
            var blob = entry.blob instanceof Blob ? entry.blob : null;
            var size = blob ? Number(blob.size || 0) : textBytes(String(entry.blob || ''));
            return Math.max(0, size + 512);
        }

        return Math.max(0, textBytes(String(entry.value == null ? '' : entry.value)) + 64);
    }

    function entriesBytes(entries) {
        var list = Array.isArray(entries) ? entries : [];
        var total = 0;
        for (var i = 0; i < list.length; i += 1) {
            total += entryBytes(list[i]);
        }
        return Math.max(0, total);
    }

    function opStoredBytes(op) {
        if (!op || typeof op !== 'object') {
            return 0;
        }
        var explicit = Number(op.sizeBytes || 0);
        if (explicit > 0) {
            return Math.max(0, explicit);
        }
        return entriesBytes(op.bodyEntries || []);
    }

    function toEntries(fd) {
        var out = [];
        fd.forEach(function (value, key) {
            if (value instanceof File || value instanceof Blob) {
                out.push({
                    key: String(key || ''),
                    kind: 'file',
                    blob: value,
                    name: value instanceof File ? String(value.name || 'upload.bin') : 'upload.bin',
                    type: String(value.type || 'application/octet-stream'),
                    lastModified: value instanceof File ? Number(value.lastModified || now()) : now()
                });
            } else {
                out.push({ key: String(key || ''), kind: 'text', value: String(value == null ? '' : value) });
            }
        });
        return out;
    }

    function fromEntries(entries) {
        var fd = new FormData();
        (Array.isArray(entries) ? entries : []).forEach(function (entry) {
            var key = String(entry && entry.key ? entry.key : '');
            if (key === '') { return; }
            if (String(entry.kind || '') === 'file') {
                var blob = entry.blob instanceof Blob
                    ? entry.blob
                    : new Blob([String(entry.blob || '')], { type: String(entry.type || 'application/octet-stream') });
                var name = String(entry.name || 'upload.bin');
                try {
                    fd.append(key, new File([blob], name, {
                        type: String(entry.type || blob.type || 'application/octet-stream'),
                        lastModified: Number(entry.lastModified || now())
                    }), name);
                } catch (error) {
                    fd.append(key, blob, name);
                }
            } else {
                fd.append(key, String(entry.value == null ? '' : entry.value));
            }
        });
        return fd;
    }

    function withSyncMarker(entries) {
        var list = Array.isArray(entries) ? entries.slice() : [];
        var hasSyncMarker = false;
        for (var i = 0; i < list.length; i += 1) {
            var entry = list[i];
            if (!entry) {
                continue;
            }
            if (String(entry.key || '') === 'sync_mode' && String(entry.kind || '') === 'text') {
                entry.value = '1';
                hasSyncMarker = true;
                break;
            }
        }
        if (!hasSyncMarker) {
            list.push({ key: 'sync_mode', kind: 'text', value: '1' });
        }
        return list;
    }

    function ensureOperationId(entries) {
        var id = '';
        for (var i = 0; i < entries.length; i += 1) {
            if (entries[i] && entries[i].key === 'operation_id' && entries[i].kind === 'text') {
                id = String(entries[i].value || '').trim();
                if (id !== '') { return id; }
                entries[i].value = uuid();
                return String(entries[i].value);
            }
        }
        id = uuid();
        entries.push({ key: 'operation_id', kind: 'text', value: id });
        return id;
    }

    function routeConfigForPath(pathname) {
        for (var i = 0; i < ROUTE_CONFIGS.length; i += 1) {
            if (ROUTE_CONFIGS[i].pattern.test(String(pathname || ''))) {
                return ROUTE_CONFIGS[i];
            }
        }
        return null;
    }

    function getTextEntryValue(entries, key) {
        var expectedKey = String(key || '');
        var list = Array.isArray(entries) ? entries : [];
        for (var i = 0; i < list.length; i += 1) {
            var entry = list[i];
            if (!entry || String(entry.kind || '') !== 'text') {
                continue;
            }
            if (String(entry.key || '') !== expectedKey) {
                continue;
            }
            return String(entry.value == null ? '' : entry.value);
        }
        return '';
    }

    function normalizeActionName(raw) {
        return String(raw == null ? '' : raw).trim().toUpperCase();
    }

    function normalizeRoleKey(raw) {
        return String(raw == null ? '' : raw)
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function defaultWorkflowActionAllowlist() {
        return Object.keys(QUEUEABLE_DOCUMENT_ACTIONS);
    }

    function readOfflinePolicy() {
        var raw = window.__EDATS_OFFLINE_POLICY;
        var policy = (raw && typeof raw === 'object') ? raw : {};
        var bodyRoleKey = '';
        try {
            bodyRoleKey = normalizeRoleKey((document.body && document.body.getAttribute('data-role-key')) || '');
        } catch (error) {
            bodyRoleKey = '';
        }

        var list = Array.isArray(policy.workflow_allowed_actions)
            ? policy.workflow_allowed_actions
            : defaultWorkflowActionAllowlist();
        var allowActions = {};
        for (var i = 0; i < list.length; i += 1) {
            var actionKey = normalizeActionName(list[i]);
            if (actionKey !== '') {
                allowActions[actionKey] = true;
            }
        }
        if (Object.keys(allowActions).length === 0) {
            defaultWorkflowActionAllowlist().forEach(function (item) {
                var key = normalizeActionName(item);
                if (key !== '') {
                    allowActions[key] = true;
                }
            });
        }

        return {
            phase: String(policy.phase || ''),
            rolloutStage: String(policy.rollout_stage || ''),
            roleKey: normalizeRoleKey(policy.role_key || bodyRoleKey),
            pilotRoleKey: normalizeRoleKey(policy.pilot_role_key || ''),
            intakeOutboxEnabled: policy.intake_outbox_enabled !== false,
            workflowOutboxEnabled: policy.workflow_outbox_enabled !== false,
            workflowAllowedActions: allowActions,
            syncLogEnabled: policy.sync_log_enabled !== false
        };
    }

    function rolloutBlockedMessage(policy, actionName) {
        var actionLabel = actionName !== '' ? actionName + ' action' : 'workflow action';
        var pilotRole = normalizeRoleKey(policy && policy.pilotRoleKey ? policy.pilotRoleKey : '');
        if (pilotRole !== '') {
            return 'Offline queue for ' + actionLabel + ' is in pilot for role ' + pilotRole + ' only. Reconnect and submit online.';
        }
        return 'Offline queue for ' + actionLabel + ' is currently disabled for this role. Reconnect and submit online.';
    }

    function queueDecision(config, bodyEntries) {
        var policy = readOfflinePolicy();
        var kind = String(config && config.kind ? config.kind : '');
        var actionName = normalizeActionName(getTextEntryValue(bodyEntries, 'action'));
        if (!config || typeof config !== 'object') {
            return {
                allowed: false,
                policy: policy,
                actionName: actionName,
                reason: 'This action is not configured for offline queueing.'
            };
        }

        if (kind === 'intake_create') {
            if (!policy.intakeOutboxEnabled) {
                return {
                    allowed: false,
                    policy: policy,
                    actionName: actionName,
                    reason: 'Offline queue for intake submissions is disabled for this role during rollout.'
                };
            }

            return { allowed: true, policy: policy, actionName: actionName, reason: '' };
        }

        if (kind === 'document_action') {
            if (actionName === '' || !QUEUEABLE_DOCUMENT_ACTIONS[actionName]) {
                return {
                    allowed: false,
                    policy: policy,
                    actionName: actionName,
                    reason: 'This workflow action is not supported for offline queueing.'
                };
            }
            if (!policy.workflowOutboxEnabled) {
                return {
                    allowed: false,
                    policy: policy,
                    actionName: actionName,
                    reason: rolloutBlockedMessage(policy, actionName)
                };
            }
            if (!policy.workflowAllowedActions[actionName]) {
                return {
                    allowed: false,
                    policy: policy,
                    actionName: actionName,
                    reason: 'Offline queue for ' + actionName + ' action is disabled during rollout.'
                };
            }
            return { allowed: true, policy: policy, actionName: actionName, reason: '' };
        }

        return {
            allowed: false,
            policy: policy,
            actionName: actionName,
            reason: 'This action is not configured for offline queueing.'
        };
    }

    function queueKindLabel(config) {
        if (!config || typeof config !== 'object') {
            return 'request';
        }
        return String(config.label || 'request');
    }

    function isAuthPagePath() {
        return AUTH_PAGE_PATTERN.test(String(window.location.pathname || ''));
    }

    function shouldClearSensitiveDataOnAuthBoot() {
        if (!isAuthPagePath()) {
            return false;
        }

        try {
            var url = new URL(window.location.href);
            return url.searchParams.get(EXPLICIT_OFFLINE_CLEAR_QUERY_KEY) === '1';
        } catch (error) {
            return /(?:\?|&)offline_clear=1(?:&|$)/.test(String(window.location.search || ''));
        }
    }

    function clearAuthBootFlagFromUrl() {
        try {
            var url = new URL(window.location.href);
            if (url.searchParams.get(EXPLICIT_OFFLINE_CLEAR_QUERY_KEY) !== '1') {
                return;
            }
            url.searchParams.delete(EXPLICIT_OFFLINE_CLEAR_QUERY_KEY);
            var normalizedPath = url.pathname + (url.search ? url.search : '') + (url.hash ? url.hash : '');
            if (window.history && typeof window.history.replaceState === 'function') {
                window.history.replaceState({}, document.title, normalizedPath);
            }
        } catch (error) {
            // Ignore URL rewrite failures.
        }
    }

    function clearSensitiveLocalStorage() {
        if (typeof window.localStorage === 'undefined') {
            return;
        }

        try {
            for (var i = window.localStorage.length - 1; i >= 0; i -= 1) {
                var key = String(window.localStorage.key(i) || '');
                if (key === '') {
                    continue;
                }
                var shouldDelete = false;
                for (var j = 0; j < SENSITIVE_LOCAL_STORAGE_PREFIXES.length; j += 1) {
                    if (key.indexOf(SENSITIVE_LOCAL_STORAGE_PREFIXES[j]) === 0) {
                        shouldDelete = true;
                        break;
                    }
                }
                if (shouldDelete) {
                    window.localStorage.removeItem(key);
                }
            }
        } catch (error) {
            // Ignore localStorage cleanup errors.
        }
    }

    async function clearOutboxByFilter(filterFn) {
        var rows = await allOps();
        for (var i = 0; i < rows.length; i += 1) {
            var row = rows[i] || {};
            if (typeof filterFn === 'function' && !filterFn(row)) {
                continue;
            }
            var operationId = String(row.operationId || '');
            if (operationId !== '') {
                await deleteOp(operationId);
            }
        }
    }

    async function clearServiceWorkerCaches() {
        if (typeof window.caches === 'undefined' || !window.caches || typeof window.caches.keys !== 'function') {
            return;
        }

        try {
            var cacheKeys = await window.caches.keys();
            var deletions = [];
            for (var i = 0; i < cacheKeys.length; i += 1) {
                var cacheName = String(cacheKeys[i] || '');
                if (cacheName === '') {
                    continue;
                }
                var shouldDelete = false;
                for (var j = 0; j < SERVICE_WORKER_CACHE_PREFIXES.length; j += 1) {
                    if (cacheName.indexOf(SERVICE_WORKER_CACHE_PREFIXES[j]) === 0) {
                        shouldDelete = true;
                        break;
                    }
                }
                if (shouldDelete) {
                    deletions.push(window.caches.delete(cacheName));
                }
            }
            if (deletions.length > 0) {
                await Promise.allSettled(deletions);
            }
        } catch (error) {
            // Ignore CacheStorage cleanup errors.
        }
    }

    async function clearSensitiveOfflineData(reason, options) {
        var opts = options || {};
        var includeOutbox = opts.includeOutbox !== false;
        var includeReadCache = opts.includeReadCache !== false;
        var includeLocalStorage = opts.includeLocalStorage !== false;
        var includeServiceWorkerCache = opts.includeServiceWorkerCache !== false;
        var publishAfter = opts.publishAfter !== false;

        if (includeOutbox) {
            await clearOutboxByFilter(function () { return true; });
        }

        if (includeReadCache && window.edatsOfflineReadCache && typeof window.edatsOfflineReadCache.clear === 'function') {
            try {
                await window.edatsOfflineReadCache.clear();
            } catch (error) {
                // Ignore read-cache cleanup errors.
            }
        }

        if (includeLocalStorage) {
            clearSensitiveLocalStorage();
        }

        if (includeServiceWorkerCache) {
            await clearServiceWorkerCaches();
        }

        if (publishAfter) {
            await publish();
        }

        try {
            window.dispatchEvent(new CustomEvent('edats:offline-data-cleared', {
                detail: {
                    reason: String(reason || 'manual'),
                    source: 'offline-outbox'
                }
            }));
        } catch (error) {
            // Ignore custom event errors.
        }
    }

    function notifySessionInvalid(reason, statusCode, source) {
        try {
            window.dispatchEvent(new CustomEvent('edats:session-invalid', {
                detail: {
                    reason: String(reason || 'session-invalid'),
                    statusCode: Number(statusCode || 0),
                    source: String(source || 'offline-outbox')
                }
            }));
        } catch (error) {
            // Ignore custom event errors.
        }
    }

    async function blockQueuedOperationsForReauth(reason, statusCode) {
        var rows = await allOps();
        var scope = scopeKey();
        var normalizedStatusCode = Number(statusCode || 0);
        var message = 'Session expired while syncing. Sign in again, then tap Sync now to resume queued work.';
        if (String(reason || '').trim() !== '') {
            message = message + ' (' + String(reason).trim() + ')';
        }

        for (var i = 0; i < rows.length; i += 1) {
            var row = rows[i] || {};
            if (String(row.scopeKey || '') !== scope) {
                continue;
            }

            var status = String(row.status || '').toLowerCase();
            if (status === 'synced') {
                continue;
            }

            row.status = 'blocked_reauth';
            row.updatedAt = now();
            row.nextRetryAt = now();
            row.lastError = message;
            if (normalizedStatusCode > 0) {
                row.lastHttpStatus = normalizedStatusCode;
            }
            await putOp(row);
        }
    }

    async function markSessionInvalid(reason, statusCode) {
        if (sessionInvalidated) {
            return;
        }
        sessionInvalidated = true;
        logSyncEvent('SESSION_INVALID', {
            source: 'client-outbox',
            http_status: Number(statusCode || 0),
            message: String(reason || 'session-invalid')
        });
        await blockQueuedOperationsForReauth(reason || 'session-invalid', Number(statusCode || 0));
        await clearSensitiveOfflineData(reason || 'session-invalid', {
            includeOutbox: false,
            includeReadCache: true,
            includeLocalStorage: true,
            includeServiceWorkerCache: true,
            publishAfter: true
        });
        notifySessionInvalid(reason || 'session-invalid', statusCode || 0, 'offline-outbox');
    }

    function queuedResponse(operationId, reason, config, bodyEntries) {
        var actionName = normalizeActionName(getTextEntryValue(bodyEntries, 'action'));
        var label = queueKindLabel(config);
        if (String(config && config.kind || '') === 'document_action' && actionName !== '') {
            label = actionName + ' action';
        }
        var message = reason === 'network'
            ? 'Connection dropped. ' + label + ' was saved to outbox and will sync automatically.'
            : 'Offline mode: ' + label + ' was saved to outbox and will sync automatically.';
        return new Response(JSON.stringify({
            ok: true,
            queued_offline: true,
            status: 'pending',
            operation_id: operationId,
            queued_kind: String(config && config.kind ? config.kind : ''),
            queued_action: actionName,
            message: message
        }), {
            status: 202,
            statusText: 'Accepted',
            headers: { 'Content-Type': 'application/json; charset=UTF-8', 'X-eDATS-Outbox': 'QUEUED' }
        });
    }

    function queueLimitResponse(message) {
        return new Response(JSON.stringify({
            ok: false,
            queued_offline: false,
            queue_limited: true,
            message: String(message || 'Offline queue limit reached. Reconnect and submit online.')
        }), {
            status: 413,
            statusText: 'Payload Too Large',
            headers: { 'Content-Type': 'application/json; charset=UTF-8', 'X-eDATS-Outbox': 'LIMIT' }
        });
    }

    function queueSnapshot() {
        return {
            pending: Number(state && state.pending ? state.pending : 0),
            failed: Number(state && state.failed ? state.failed : 0),
            blocked: Number(state && state.blocked ? state.blocked : 0)
        };
    }

    function sanitizeBodyEntriesForUi(entries) {
        var list = Array.isArray(entries) ? entries : [];
        var sanitized = [];
        for (var i = 0; i < list.length; i += 1) {
            var entry = list[i];
            if (!entry || typeof entry !== 'object') {
                continue;
            }
            var key = String(entry.key || '').trim();
            if (key === '') {
                continue;
            }
            var kind = String(entry.kind || 'text').toLowerCase();
            if (kind === 'file') {
                var blob = entry.blob instanceof Blob ? entry.blob : null;
                sanitized.push({
                    key: key,
                    kind: 'file',
                    name: String(entry.name || 'upload.bin'),
                    type: String(entry.type || (blob ? String(blob.type || '') : '')),
                    size: blob ? Number(blob.size || 0) : 0,
                    lastModified: Number(entry.lastModified || 0)
                });
                continue;
            }
            sanitized.push({
                key: key,
                kind: 'text',
                value: String(entry.value == null ? '' : entry.value)
            });
        }
        return sanitized;
    }

    function mapOperationForUi(op) {
        var row = op && typeof op === 'object' ? op : {};
        return {
            operationId: String(row.operationId || ''),
            scopeKey: String(row.scopeKey || ''),
            kind: String(row.kind || ''),
            action: String(row.action || ''),
            status: String(row.status || ''),
            url: String(row.url || ''),
            createdAt: Number(row.createdAt || 0),
            updatedAt: Number(row.updatedAt || 0),
            attemptCount: Number(row.attemptCount || 0),
            nextRetryAt: Number(row.nextRetryAt || 0),
            lastError: String(row.lastError || ''),
            lastHttpStatus: Number(row.lastHttpStatus || 0),
            sizeBytes: Number(row.sizeBytes || 0),
            bodyEntries: sanitizeBodyEntriesForUi(row.bodyEntries || [])
        };
    }

    function resolveSyncLogUrl(contextUrl) {
        var explicit = String(window.__EDATS_OFFLINE_SYNC_LOG_URL || '').trim();
        var base = '';
        if (contextUrl) {
            try {
                base = String(contextUrl);
            } catch (error) {
                base = '';
            }
        }
        if (base === '') {
            base = window.location.href;
        }

        if (explicit !== '') {
            try {
                return new URL(explicit, base).toString();
            } catch (error) {
                // ignore explicit URL parse errors
            }
        }

        if (contextUrl) {
            try {
                return new URL('offline-sync-log.php', base).toString();
            } catch (error) {
                // ignore inferred URL parse errors
            }
        }

        return '';
    }

    function logSyncEvent(eventType, payload, contextUrl) {
        var policy = readOfflinePolicy();
        if (!policy.syncLogEnabled) {
            return;
        }

        var endpoint = resolveSyncLogUrl(contextUrl || (payload && payload.request_url ? payload.request_url : ''));
        if (endpoint === '') {
            return;
        }

        var queue = queueSnapshot();
        var body = payload && typeof payload === 'object' ? Object.assign({}, payload) : {};
        body.event_type = String(eventType || body.event_type || 'UNKNOWN').toUpperCase();
        body.route_kind = String(body.route_kind || '');
        body.action_name = normalizeActionName(body.action_name || '');
        body.operation_id = String(body.operation_id || '');
        body.request_url = String(body.request_url || '');
        body.http_status = Number(body.http_status || 0);
        body.attempt_count = Number(body.attempt_count || 0);
        body.queue_pending = queue.pending;
        body.queue_failed = queue.failed;
        body.queue_blocked = queue.blocked;
        body.source = String(body.source || 'client-outbox');
        body.message = String(body.message || '');
        body.payload = Object.assign({
            online: navigator.onLine,
            phase: String(policy.phase || ''),
            rollout_stage: String(policy.rolloutStage || ''),
            role_key: String(policy.roleKey || '')
        }, (body.payload && typeof body.payload === 'object') ? body.payload : {});

        try {
            nativeFetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                body: JSON.stringify(body),
                keepalive: true
            }).catch(function () {
                // ignore sync log transport errors
            });
        } catch (error) {
            // ignore sync log serialization/network errors
        }
    }

    function ensureBadge() {
        if (badge || !document.body) { return; }
        badge = document.createElement('aside');
        badge.id = 'edatsOfflineOutboxBadge';
        badge.style.position = 'fixed';
        badge.style.left = 'auto';
        badge.style.right = '16px';
        badge.style.bottom = '16px';
        badge.style.zIndex = '9999';
        badge.style.maxWidth = 'min(420px, calc(100vw - 24px))';
        badge.style.padding = '10px 12px';
        badge.style.borderRadius = '12px';
        badge.style.border = '1px solid #c7d8ec';
        badge.style.background = '#f3f8ff';
        badge.style.color = '#17324b';
        badge.style.display = 'flex';
        badge.style.gap = '10px';
        badge.style.alignItems = 'center';
        badge.style.font = '13px/1.35 "Segoe UI", Tahoma, Arial, sans-serif';
        badge.style.boxShadow = '0 10px 26px rgba(15, 40, 69, 0.16)';
        badge.style.cursor = 'default';
        badge.hidden = true;
        badge.style.display = 'none';
        badge.setAttribute('aria-hidden', 'true');
        badge.setAttribute('role', 'status');
        badge.setAttribute('aria-live', 'polite');
        badge.tabIndex = 0;
        badge.setAttribute('aria-label', 'Offline queue status');
        badgeText = document.createElement('span');
        badgeText.style.flex = '1 1 auto';
        badgeBtn = document.createElement('button');
        badgeBtn.type = 'button';
        badgeBtn.textContent = 'Sync now';
        badgeBtn.style.border = '1px solid #2e6da9';
        badgeBtn.style.background = '#2e6da9';
        badgeBtn.style.color = '#fff';
        badgeBtn.style.borderRadius = '9px';
        badgeBtn.style.padding = '6px 10px';
        badgeBtn.style.fontSize = '12px';
        badgeBtn.style.fontWeight = '600';
        badgeBtn.style.cursor = 'pointer';
        badgeBtn.addEventListener('click', function () { sync(true); });
        badge.addEventListener('click', function (event) {
            if (event && event.target && typeof event.target.closest === 'function' && event.target.closest('button') === badgeBtn) {
                return;
            }
            try {
                window.dispatchEvent(new CustomEvent('edats:offline-badge-click', {
                    detail: { online: !!navigator.onLine, state: Object.assign({}, state) }
                }));
            } catch (error) {
                // Ignore event dispatch errors.
            }
        });
        badge.addEventListener('keydown', function (event) {
            var key = String(event && event.key ? event.key : '');
            if (key !== 'Enter' && key !== ' ') {
                return;
            }
            event.preventDefault();
            try {
                window.dispatchEvent(new CustomEvent('edats:offline-badge-click', {
                    detail: { online: !!navigator.onLine, state: Object.assign({}, state) }
                }));
            } catch (error) {
                // Ignore event dispatch errors.
            }
        });
        badge.appendChild(badgeText);
        badge.appendChild(badgeBtn);
        document.body.appendChild(badge);
    }

    function render() {
        ensureBadge();
        if (!badge || !badgeText || !badgeBtn) { return; }
        var pending = Number(state.pending || 0);
        var syncing = Number(state.syncing || 0);
        var failed = Number(state.failed || 0);
        var blocked = Number(state.blocked || 0);
        var queued = pending + syncing + failed + blocked;
        var text = '';
        var show = false;

        if (!state.online && queued > 0) {
            show = true;
            text = 'Offline mode: ' + queued + ' offline request(s) queued.';
            badge.style.background = '#fff4dc';
            badge.style.borderColor = '#edd59f';
            badge.style.color = '#5c4311';
        } else if (syncing > 0) {
            show = true;
            text = 'Syncing queued offline requests...';
            badge.style.background = '#e8f4ff';
            badge.style.borderColor = '#b8d6f5';
            badge.style.color = '#123e6a';
        } else if (blocked > 0) {
            show = true;
            text = blocked + ' queued sync action(s) require re-authentication. Sign in again, then tap Sync now.';
            badge.style.background = '#fff5e8';
            badge.style.borderColor = '#f1c895';
            badge.style.color = '#5f3d16';
        } else if (pending > 0) {
            show = true;
            text = pending + ' queued request(s) waiting to sync.';
            badge.style.background = '#eff6ff';
            badge.style.borderColor = '#cadbf0';
            badge.style.color = '#1f4263';
        } else if (failed > 0) {
            show = true;
            text = failed + ' submission(s) failed to sync. Retrying automatically.';
            badge.style.background = '#fff0f0';
            badge.style.borderColor = '#f0c3c3';
            badge.style.color = '#6b1d1d';
        } else if (syncedAt > 0 && now() - syncedAt < 12000) {
            show = true;
            text = 'Queued offline requests synced.';
            badge.style.background = '#e9faef';
            badge.style.borderColor = '#bde5cb';
            badge.style.color = '#1a5a32';
        }

        if (!String(text || '').trim()) {
            show = false;
        }

        badge.hidden = !show;
        badge.style.display = show ? 'flex' : 'none';
        badge.setAttribute('aria-hidden', show ? 'false' : 'true');
        badgeText.textContent = text;
        badge.style.cursor = show ? 'pointer' : 'default';
        badgeBtn.hidden = !(state.online && (pending > 0 || failed > 0 || blocked > 0));
        badgeBtn.disabled = syncing > 0;
    }

    async function publish() {
        var rows = await allOps();
        var scope = scopeKey();
        var next = { online: navigator.onLine, pending: 0, syncing: 0, failed: 0, blocked: 0, synced: 0 };
        rows.forEach(function (row) {
            if (String(row.scopeKey || '') !== scope) { return; }
            var status = String(row.status || '').toLowerCase();
            if (status === 'pending') { next.pending += 1; return; }
            if (status === 'syncing') { next.syncing += 1; return; }
            if (status === 'failed') { next.failed += 1; return; }
            if (status === 'blocked_reauth') { next.blocked += 1; return; }
            if (status === 'synced') { next.synced += 1; return; }
            next.pending += 1;
        });

        var prevQueued = state.pending + state.syncing + state.failed + state.blocked;
        var nextQueued = next.pending + next.syncing + next.failed + next.blocked;
        if (prevQueued > 0 && nextQueued === 0 && next.online) {
            syncedAt = now();
        }
        state = next;
        render();

        try {
            window.dispatchEvent(new CustomEvent('edats:outbox-state', { detail: { scopeKey: scope, state: next } }));
        } catch (error) {
            // noop
        }
    }

    function retryDelay(attempt, statusCode) {
        var status = Number(statusCode || 0);
        if (status > 0 && status < 500 && status !== 408 && status !== 409 && status !== 425 && status !== 429) {
            return TERMINAL_DELAY_MS;
        }
        return Math.min(
            MAX_DELAY_MS,
            Math.max(BASE_DELAY_MS, BASE_DELAY_MS * Math.pow(2, Math.max(0, Number(attempt || 1) - 1)))
        );
    }

    async function nextDue(force) {
        var rows = await allOps();
        var allowBlocked = !!force;
        var bypassRetryWindow = !!force;
        var due = rows.filter(function (row) {
            if (String(row.scopeKey || '') !== scopeKey()) { return false; }
            var status = String(row.status || '').toLowerCase();
            if (status === 'blocked_reauth') {
                if (!allowBlocked) {
                    return false;
                }
                return bypassRetryWindow || Number(row.nextRetryAt || 0) <= now();
            }
            if (status !== 'pending' && status !== 'failed') { return false; }
            if (bypassRetryWindow) {
                return true;
            }
            return Number(row.nextRetryAt || 0) <= now();
        });
        due.sort(function (a, b) { return Number(a.createdAt || 0) - Number(b.createdAt || 0); });
        return due[0] || null;
    }

    async function pruneSynced() {
        var rows = await allOps();
        var scope = scopeKey();
        var synced = rows.filter(function (row) {
            return String(row.scopeKey || '') === scope && String(row.status || '').toLowerCase() === 'synced';
        }).sort(function (a, b) {
            return Number(b.updatedAt || 0) - Number(a.updatedAt || 0);
        });
        var toDelete = [];
        for (var i = 0; i < synced.length; i += 1) {
            var old = now() - Number(synced[i].updatedAt || 0) > (7 * 24 * 60 * 60 * 1000);
            if (i >= 120 || old) {
                toDelete.push(String(synced[i].operationId || ''));
            }
        }
        for (var j = 0; j < toDelete.length; j += 1) {
            if (toDelete[j] !== '') {
                await deleteOp(toDelete[j]);
            }
        }
    }

    async function syncOne(op, force) {
        op.status = 'syncing';
        op.attemptCount = Number(op.attemptCount || 0) + 1;
        op.updatedAt = now();
        await putOp(op);
        await publish();
        logSyncEvent('SYNC_ATTEMPT', {
            route_kind: String(op.kind || ''),
            action_name: String(op.action || ''),
            operation_id: String(op.operationId || ''),
            request_url: String(op.url || ''),
            attempt_count: Number(op.attemptCount || 0),
            source: 'client-outbox'
        }, op.url);

        try {
            var response = await nativeFetch(op.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-eDATS-Outbox-Sync': '1' },
                body: fromEntries(withSyncMarker(op.bodyEntries || []))
            });

            var json = null;
            try {
                json = await response.clone().json();
            } catch (error) {
                json = null;
            }

            var statusCode = Number(response.status || 0);
            if (SESSION_INVALID_STATUSES[statusCode]) {
                await markSessionInvalid('session-expired-sync', statusCode);
                return;
            }

            if (statusCode === REAUTH_REQUIRED_STATUS && json && json.reauth_required) {
                op.status = 'blocked_reauth';
                op.updatedAt = now();
                op.lastHttpStatus = statusCode;
                op.lastError = json && json.message
                    ? String(json.message)
                    : 'Re-authentication required before syncing this action.';
                op.nextRetryAt = now() + (force ? 0 : REAUTH_BLOCK_DELAY_MS);
                await putOp(op);
                logSyncEvent('SYNC_BLOCKED_REAUTH', {
                    route_kind: String(op.kind || ''),
                    action_name: String(op.action || ''),
                    operation_id: String(op.operationId || ''),
                    request_url: String(op.url || ''),
                    http_status: statusCode,
                    attempt_count: Number(op.attemptCount || 0),
                    message: String(op.lastError || ''),
                    source: 'client-outbox'
                }, op.url);
                return;
            }

            if (!response.ok || (json && json.ok === false)) {
                op.status = 'failed';
                op.updatedAt = now();
                op.lastHttpStatus = statusCode;
                op.lastError = json && json.message ? String(json.message) : 'Sync failed.';
                op.nextRetryAt = now() + retryDelay(op.attemptCount, statusCode);
                await putOp(op);
                logSyncEvent('SYNC_FAILED', {
                    route_kind: String(op.kind || ''),
                    action_name: String(op.action || ''),
                    operation_id: String(op.operationId || ''),
                    request_url: String(op.url || ''),
                    http_status: statusCode,
                    attempt_count: Number(op.attemptCount || 0),
                    message: String(op.lastError || ''),
                    payload: json && typeof json === 'object' ? { response: json } : null,
                    source: 'client-outbox'
                }, op.url);
                return;
            }

            op.status = 'synced';
            op.updatedAt = now();
            op.nextRetryAt = 0;
            op.lastHttpStatus = statusCode > 0 ? statusCode : 200;
            op.lastError = '';
            await putOp(op);
            logSyncEvent('SYNC_SUCCEEDED', {
                route_kind: String(op.kind || ''),
                action_name: String(op.action || ''),
                operation_id: String(op.operationId || ''),
                request_url: String(op.url || ''),
                http_status: statusCode > 0 ? statusCode : 200,
                attempt_count: Number(op.attemptCount || 0),
                payload: json && typeof json === 'object' ? { response: json } : null,
                source: 'client-outbox'
            }, op.url);

            try {
                window.dispatchEvent(new CustomEvent('edats:outbox-synced', {
                    detail: {
                        operationId: op.operationId,
                        trackingId: json && json.tracking_id ? String(json.tracking_id) : '',
                        kind: String(op.kind || ''),
                        action: String(op.action || '')
                    }
                }));
            } catch (error) {
                // noop
            }
        } catch (error) {
            if (sessionInvalidated) {
                return;
            }
            op.status = 'failed';
            op.updatedAt = now();
            op.lastHttpStatus = 0;
            op.lastError = error && error.message ? String(error.message) : 'Network error while syncing.';
            op.nextRetryAt = now() + retryDelay(op.attemptCount, 0);
            await putOp(op);
            logSyncEvent('SYNC_NETWORK_ERROR', {
                route_kind: String(op.kind || ''),
                action_name: String(op.action || ''),
                operation_id: String(op.operationId || ''),
                request_url: String(op.url || ''),
                http_status: 0,
                attempt_count: Number(op.attemptCount || 0),
                message: String(op.lastError || ''),
                source: 'client-outbox'
            }, op.url);
        }
    }

    async function sync(force) {
        if (!navigator.onLine) {
            await publish();
            return;
        }
        if (sessionInvalidated) {
            await publish();
            return;
        }
        if (syncBusy) {
            if (force) {
                syncQueued = true;
            }
            return;
        }

        syncBusy = true;
        try {
            while (navigator.onLine && !sessionInvalidated) {
                var op = await nextDue(force);
                if (!op) { break; }
                await syncOne(op, force);
            }
            await pruneSynced();
        } finally {
            syncBusy = false;
            await publish();
            if (syncQueued) {
                syncQueued = false;
                sync(true);
            }
        }
    }

    function validateTextEntrySizes(bodyEntries) {
        for (var i = 0; i < bodyEntries.length; i += 1) {
            var entry = bodyEntries[i];
            if (!entry || String(entry.kind || '') !== 'text') {
                continue;
            }
            var bytes = textBytes(String(entry.value == null ? '' : entry.value));
            if (bytes > MAX_TEXT_ENTRY_BYTES) {
                throw new Error('Offline queue blocked: one field is too large for secure local storage. Reconnect and submit online.');
            }
        }
    }

    async function scopeUsage(scope) {
        var rows = await allOps();
        var totalBytes = 0;
        var count = 0;
        rows.forEach(function (row) {
            if (String(row.scopeKey || '') !== scope) {
                return;
            }
            var status = String(row.status || '').toLowerCase();
            if (status === 'synced') {
                return;
            }
            count += 1;
            totalBytes += opStoredBytes(row);
        });
        return {
            count: count,
            totalBytes: totalBytes
        };
    }

    async function queueRequest(requestUrl, bodyEntries, operationId, reason, config) {
        var existing = await getOp(operationId);
        if (existing) {
            return existing;
        }

        validateTextEntrySizes(bodyEntries);

        var opSizeBytes = entriesBytes(bodyEntries);
        if (opSizeBytes > MAX_OPERATION_BYTES) {
            throw new Error('Offline queue blocked: this payload is too large for secure local storage. Reconnect and submit online.');
        }

        var scope = scopeKey();
        var usage = await scopeUsage(scope);
        if (usage.count >= MAX_PENDING_PER_SCOPE) {
            throw new Error('Offline queue is full (' + MAX_PENDING_PER_SCOPE + ' pending requests). Sync existing items first.');
        }
        if ((usage.totalBytes + opSizeBytes) > MAX_PENDING_BYTES_PER_SCOPE) {
            throw new Error('Offline storage limit reached for queued requests. Reconnect and sync current items before queuing more.');
        }

        var time = now();
        var op = {
            operationId: String(operationId),
            scopeKey: scope,
            kind: String(config && config.kind ? config.kind : 'request'),
            action: normalizeActionName(getTextEntryValue(bodyEntries, 'action')),
            method: 'POST',
            url: String(new URL(requestUrl.toString(), window.location.origin)),
            bodyEntries: bodyEntries,
            sizeBytes: opSizeBytes,
            status: 'pending',
            attemptCount: 0,
            nextRetryAt: time,
            createdAt: time,
            updatedAt: time,
            lastError: String(reason || ''),
            lastHttpStatus: 0
        };

        await putOp(op);
        await publish();
        logSyncEvent('QUEUED', {
            route_kind: String(op.kind || ''),
            action_name: String(op.action || ''),
            operation_id: String(op.operationId || ''),
            request_url: String(op.url || ''),
            message: String(reason || ''),
            source: 'client-outbox'
        }, requestUrl);

        try {
            window.dispatchEvent(new CustomEvent('edats:outbox-queued', {
                detail: { operationId: operationId, kind: op.kind, action: op.action, sizeBytes: opSizeBytes }
            }));
        } catch (error) {
            // noop
        }

        sync(false);
        return op;
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
        if (method !== 'POST') {
            return nativeFetch(input, init);
        }

        var requestUrl = new URL(request.url, window.location.origin);
        var routeConfig = requestUrl.origin === window.location.origin
            ? routeConfigForPath(requestUrl.pathname)
            : null;
        if (!routeConfig) {
            return nativeFetch(input, init);
        }

        var formData;
        try {
            formData = await request.clone().formData();
        } catch (error) {
            return nativeFetch(input, init);
        }

        var bodyEntries = toEntries(formData);
        var decision = queueDecision(routeConfig, bodyEntries);
        var queueAllowed = !!decision.allowed;
        var operationId = ensureOperationId(bodyEntries);
        var actionName = normalizeActionName(decision.actionName || getTextEntryValue(bodyEntries, 'action'));
        var requestInit = {
            method: 'POST',
            credentials: 'same-origin',
            body: fromEntries(bodyEntries)
        };

        if (!navigator.onLine) {
            if (!queueAllowed) {
                logSyncEvent('QUEUE_BLOCKED_POLICY', {
                    route_kind: String(routeConfig && routeConfig.kind ? routeConfig.kind : ''),
                    action_name: actionName,
                    operation_id: operationId,
                    request_url: requestUrl.toString(),
                    message: String(decision.reason || 'Offline queueing is disabled for this action.'),
                    source: 'client-outbox'
                }, requestUrl);
                return new Response(JSON.stringify({
                    ok: false,
                    queued_offline: false,
                    message: String(decision.reason || 'This action requires an online connection and cannot be queued offline.')
                }), {
                    status: 503,
                    statusText: 'Offline',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' }
                });
            }
            try {
                await queueRequest(requestUrl, bodyEntries, operationId, 'offline', routeConfig);
                return queuedResponse(operationId, 'offline', routeConfig, bodyEntries);
            } catch (queueError) {
                logSyncEvent('QUEUE_LIMIT_REJECTED', {
                    route_kind: String(routeConfig && routeConfig.kind ? routeConfig.kind : ''),
                    action_name: actionName,
                    operation_id: operationId,
                    request_url: requestUrl.toString(),
                    message: queueError && queueError.message ? String(queueError.message) : 'Unable to queue request offline.',
                    source: 'client-outbox'
                }, requestUrl);
                return queueLimitResponse(queueError && queueError.message ? String(queueError.message) : 'Unable to queue request offline.');
            }
        }

        try {
            var liveResponse = await nativeFetch(requestUrl.toString(), requestInit);
            if (SESSION_INVALID_STATUSES[Number(liveResponse.status || 0)]) {
                await markSessionInvalid('session-expired-live-request', Number(liveResponse.status || 0));
            }
            return liveResponse;
        } catch (error) {
            if (!queueAllowed) {
                logSyncEvent('QUEUE_BLOCKED_POLICY', {
                    route_kind: String(routeConfig && routeConfig.kind ? routeConfig.kind : ''),
                    action_name: actionName,
                    operation_id: operationId,
                    request_url: requestUrl.toString(),
                    message: String(decision.reason || 'Offline queueing is disabled for this action.'),
                    source: 'client-outbox'
                }, requestUrl);
                throw error;
            }
            try {
                await queueRequest(requestUrl, bodyEntries, operationId, 'network', routeConfig);
                return queuedResponse(operationId, 'network', routeConfig, bodyEntries);
            } catch (queueError) {
                logSyncEvent('QUEUE_LIMIT_REJECTED', {
                    route_kind: String(routeConfig && routeConfig.kind ? routeConfig.kind : ''),
                    action_name: actionName,
                    operation_id: operationId,
                    request_url: requestUrl.toString(),
                    message: queueError && queueError.message ? String(queueError.message) : 'Unable to queue request after network failure.',
                    source: 'client-outbox'
                }, requestUrl);
                return queueLimitResponse(queueError && queueError.message ? String(queueError.message) : 'Unable to queue request after network failure.');
            }
        }
    };

    window.edatsOfflineOutbox = {
        version: 'phase7',
        limits: {
            maxPendingPerScope: MAX_PENDING_PER_SCOPE,
            maxPendingBytesPerScope: MAX_PENDING_BYTES_PER_SCOPE,
            maxOperationBytes: MAX_OPERATION_BYTES,
            maxTextEntryBytes: MAX_TEXT_ENTRY_BYTES
        },
        requestSync: function () { sync(true); },
        getPolicy: function () { return readOfflinePolicy(); },
        getState: function () { return Object.assign({}, state); },
        listOperations: async function (options) {
            var opts = options && typeof options === 'object' ? options : {};
            var includeSynced = !!opts.includeSynced;
            var includeAllScopes = !!opts.allScopes;
            var requestedScope = String(opts.scopeKey || '').trim();
            var activeScope = requestedScope !== '' ? requestedScope : scopeKey();
            var rows = await allOps();
            return rows.filter(function (row) {
                if (!includeAllScopes && String(row.scopeKey || '') !== activeScope) {
                    return false;
                }
                if (!includeSynced && String(row.status || '').toLowerCase() === 'synced') {
                    return false;
                }
                return true;
            }).sort(function (a, b) {
                return Number(b.createdAt || 0) - Number(a.createdAt || 0);
            }).map(mapOperationForUi);
        },
        clear: async function () {
            var scope = scopeKey();
            await clearOutboxByFilter(function (row) {
                return String((row || {}).scopeKey || '') === scope;
            });
            await publish();
            return true;
        },
        clearAll: async function (reason) {
            await clearSensitiveOfflineData(reason || 'manual-clear', {
                includeReadCache: true,
                includeLocalStorage: true,
                publishAfter: true
            });
            return true;
        }
    };

    window.edatsOfflineSecurity = Object.assign({}, window.edatsOfflineSecurity || {}, {
        version: 'phase7',
        clearSensitiveData: function (reason) {
            return clearSensitiveOfflineData(reason || 'manual-clear', {
                includeReadCache: true,
                includeLocalStorage: true,
                publishAfter: true
            });
        },
        markSessionInvalid: function (reason, statusCode) {
            return markSessionInvalid(reason || 'session-invalid', Number(statusCode || 0));
        }
    });

    window.addEventListener('edats:session-invalid', function (event) {
        var detail = event && event.detail ? event.detail : {};
        if (String(detail.source || '') === 'offline-outbox') {
            return;
        }
        markSessionInvalid(String(detail.reason || 'session-invalid'), Number(detail.statusCode || 0));
    });

    window.addEventListener('edats:offline-security-clear', function (event) {
        var detail = event && event.detail ? event.detail : {};
        clearSensitiveOfflineData(String(detail.reason || 'manual-clear'), {
            includeReadCache: true,
            includeLocalStorage: true,
            publishAfter: true
        });
    });

    window.addEventListener('online', function () {
        publish();
        sync(true);
    });

    window.addEventListener('offline', function () {
        publish();
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && navigator.onLine) {
            sync(false);
        }
    });

    window.setInterval(function () { sync(false); }, HEARTBEAT_MS);

    async function boot() {
        if (shouldClearSensitiveDataOnAuthBoot()) {
            await clearSensitiveOfflineData('auth-page-explicit-logout', {
                includeOutbox: true,
                includeReadCache: true,
                includeLocalStorage: true,
                includeServiceWorkerCache: true,
                publishAfter: false
            });
            clearAuthBootFlagFromUrl();
        }
        await publish();
        if (navigator.onLine && !sessionInvalidated) {
            sync(false);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            boot();
        });
    } else {
        boot();
    }
})();
