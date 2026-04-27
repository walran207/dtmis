(function () {
    'use strict';

    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    function normalizeBasePath(value) {
        var rawValue = String(value || '').trim();
        if (rawValue === '') {
            return '';
        }

        try {
            var parsedUrl = new URL(rawValue, window.location.origin);
            rawValue = parsedUrl.pathname;
        } catch (error) {
            // Keep original rawValue.
        }

        rawValue = rawValue.replace(/\/+$/, '');
        if (rawValue === '') {
            return '';
        }
        return rawValue.startsWith('/') ? rawValue : '/' + rawValue;
    }

    function scopePath(basePath) {
        if (!basePath || basePath === '/') {
            return '/';
        }
        return basePath + '/';
    }

    function toScopedPath(basePath, relativePath) {
        var cleanPath = String(relativePath || '').replace(/^\/+/, '');
        if (!basePath || basePath === '/') {
            return '/' + cleanPath;
        }
        return (basePath + '/' + cleanPath).replace(/\/{2,}/g, '/');
    }

    function getConfiguredBasePath() {
        var scriptTag = document.currentScript;
        var attributeBasePath = scriptTag && scriptTag.dataset ? scriptTag.dataset.basePath : '';
        var normalized = normalizeBasePath(attributeBasePath);
        if (normalized !== '' || attributeBasePath === '/') {
            return normalized;
        }

        if (typeof window.__EDATS_BASE_PATH === 'string') {
            return normalizeBasePath(window.__EDATS_BASE_PATH);
        }

        var currentPath = String(window.location.pathname || '');
        if (currentPath === '' || currentPath === '/') {
            return '';
        }
        var firstSegment = currentPath.split('/').filter(Boolean)[0] || '';
        return firstSegment === '' ? '' : '/' + firstSegment;
    }

    var basePath = getConfiguredBasePath();
    var pwaInstallPrompt = null;
    var installPromptRoot = null;
    var INSTALL_DISMISS_KEY = 'edats_pwa_install_prompt_dismissed';

    function removeInstallPrompt() {
        if (!installPromptRoot) {
            return;
        }
        installPromptRoot.remove();
        installPromptRoot = null;
    }

    function canShowInstallPrompt() {
        try {
            return localStorage.getItem(INSTALL_DISMISS_KEY) !== '1';
        } catch (error) {
            return true;
        }
    }

    function markInstallPromptDismissed() {
        try {
            localStorage.setItem(INSTALL_DISMISS_KEY, '1');
        } catch (error) {
            // Ignore storage errors.
        }
    }

    function showInstallPrompt() {
        if (!pwaInstallPrompt || installPromptRoot || !canShowInstallPrompt()) {
            return;
        }

        installPromptRoot = document.createElement('aside');
        installPromptRoot.id = 'edatsPwaInstallPrompt';
        installPromptRoot.setAttribute('role', 'status');
        installPromptRoot.setAttribute('aria-live', 'polite');
        installPromptRoot.style.position = 'fixed';
        installPromptRoot.style.right = '20px';
        installPromptRoot.style.bottom = '20px';
        installPromptRoot.style.zIndex = '9999';
        installPromptRoot.style.width = 'min(360px, calc(100vw - 24px))';
        installPromptRoot.style.background = '#ffffff';
        installPromptRoot.style.color = '#1e2c3a';
        installPromptRoot.style.border = '1px solid #d7e1ec';
        installPromptRoot.style.borderRadius = '14px';
        installPromptRoot.style.boxShadow = '0 16px 38px rgba(20, 41, 71, 0.18)';
        installPromptRoot.style.padding = '14px';
        installPromptRoot.style.fontFamily = '"Segoe UI", Arial, sans-serif';
        installPromptRoot.innerHTML = ''
            + '<p style="margin:0 0 6px;font-size:14px;font-weight:700;">Install eDATS app</p>'
            + '<p style="margin:0 0 12px;font-size:13px;line-height:1.45;color:#41566b;">'
            + 'Use eDATS as an app for quicker access and better offline support.'
            + '</p>'
            + '<div style="display:flex;gap:8px;justify-content:flex-end;">'
            + '  <button type="button" data-install-ignore="1" style="border:1px solid #c8d6e6;background:#f3f7fb;color:#1f4d7a;border-radius:10px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;">Not now</button>'
            + '  <button type="button" data-install-now="1" style="border:0;background:#1f4d7a;color:#ffffff;border-radius:10px;padding:8px 12px;font-size:13px;font-weight:600;cursor:pointer;">Install</button>'
            + '</div>';

        var installNowButton = installPromptRoot.querySelector('[data-install-now="1"]');
        var installIgnoreButton = installPromptRoot.querySelector('[data-install-ignore="1"]');

        if (installIgnoreButton) {
            installIgnoreButton.addEventListener('click', function () {
                markInstallPromptDismissed();
                removeInstallPrompt();
            });
        }

        if (installNowButton) {
            installNowButton.addEventListener('click', async function () {
                var result = await window.edatsInstallApp();
                if (result && result.outcome === 'accepted') {
                    removeInstallPrompt();
                }
            });
        }

        document.body.appendChild(installPromptRoot);
    }

    window.edatsCanInstallApp = function () {
        return pwaInstallPrompt !== null;
    };

    window.edatsInstallApp = async function () {
        if (!pwaInstallPrompt) {
            return { available: false, outcome: 'unavailable' };
        }

        pwaInstallPrompt.prompt();
        var choiceResult = await pwaInstallPrompt.userChoice;
        var outcome = choiceResult && typeof choiceResult.outcome === 'string'
            ? choiceResult.outcome
            : 'dismissed';

        if (outcome !== 'accepted') {
            markInstallPromptDismissed();
        }
        pwaInstallPrompt = null;
        removeInstallPrompt();

        return {
            available: true,
            outcome: outcome,
        };
    };

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        pwaInstallPrompt = event;
        showInstallPrompt();
        window.dispatchEvent(new CustomEvent('edats:pwa-install-ready'));
    });

    window.addEventListener('appinstalled', function () {
        pwaInstallPrompt = null;
        removeInstallPrompt();
        window.dispatchEvent(new CustomEvent('edats:pwa-installed'));
    });

    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', function () {
        var serviceWorkerPath = toScopedPath(basePath, 'service-worker.js');
        navigator.serviceWorker
            .register(serviceWorkerPath, { scope: scopePath(basePath) })
            .catch(function () {
                // Keep silent for end users. PWA is enhancement-only.
            });
    });
})();
