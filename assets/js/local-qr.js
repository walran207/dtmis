(function () {
    'use strict';

    if (typeof window === 'undefined') {
        return;
    }
    if (window.edatsLocalQr) {
        return;
    }

    function toPositiveInt(value, fallback) {
        var parsed = parseInt(String(value == null ? '' : value), 10);
        if (!Number.isFinite(parsed) || parsed <= 0) {
            return fallback;
        }
        return parsed;
    }

    function normalizeErrorCorrection(raw) {
        var level = String(raw == null ? '' : raw).trim().toUpperCase();
        if (level === 'L' || level === 'M' || level === 'Q' || level === 'H') {
            return level;
        }
        return 'M';
    }

    function encodeSvgToDataUrl(svg) {
        var encoded = encodeURIComponent(String(svg || ''))
            .replace(/%20/g, ' ')
            .replace(/%3D/g, '=')
            .replace(/%3A/g, ':')
            .replace(/%2F/g, '/');
        return 'data:image/svg+xml;charset=UTF-8,' + encoded;
    }

    function generateDataUrl(text, options) {
        if (typeof window.qrcode !== 'function') {
            throw new Error('QR generator library is not loaded.');
        }

        var payload = String(text == null ? '' : text);
        if (payload.trim() === '') {
            throw new Error('QR text is empty.');
        }

        var opts = options && typeof options === 'object' ? options : {};
        var targetSize = Math.max(64, toPositiveInt(opts.size, 256));
        var margin = Math.max(0, toPositiveInt(opts.margin, 2));
        var typeNumber = Math.max(0, toPositiveInt(opts.typeNumber, 0));
        var errorCorrection = normalizeErrorCorrection(opts.errorCorrection);

        var qr = window.qrcode(typeNumber, errorCorrection);
        qr.addData(payload);
        qr.make();

        var modules = Math.max(1, toPositiveInt(qr.getModuleCount(), 21));
        var totalModules = modules + (margin * 2);
        var cellSize = Math.max(1, Math.floor(targetSize / totalModules));
        var svg = qr.createSvgTag(cellSize, margin);
        return encodeSvgToDataUrl(svg);
    }

    function renderImage(imageElement, text, options) {
        if (!imageElement || typeof imageElement !== 'object') {
            return '';
        }
        var payload = String(text == null ? '' : text);
        var dataUrl = generateDataUrl(payload, options);
        imageElement.setAttribute('src', dataUrl);
        imageElement.setAttribute('data-qr-rendered', '1');
        return dataUrl;
    }

    function renderAll(selector, options) {
        var query = String(selector || 'img[data-qr-text]');
        var nodes = Array.prototype.slice.call(document.querySelectorAll(query));
        var rendered = 0;
        for (var i = 0; i < nodes.length; i += 1) {
            var node = nodes[i];
            var payload = String(node.getAttribute('data-qr-text') || '');
            if (payload.trim() === '') {
                continue;
            }
            try {
                renderImage(node, payload, options);
                rendered += 1;
            } catch (error) {
                // Continue rendering remaining QR nodes.
            }
        }
        return rendered;
    }

    window.edatsLocalQr = {
        version: '1.0.0',
        generateDataUrl: generateDataUrl,
        renderImage: renderImage,
        renderAll: renderAll
    };
})();
