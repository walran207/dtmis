<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$trackingId = trim((string)($_GET['tracking_id'] ?? ''));
$isEmbedded = ((string)($_GET['embedded'] ?? '') === '1');
$printPackagePath = app_url('print-package.php') . ($trackingId !== '' ? '?tracking_id=' . rawurlencode($trackingId) : '');
$publicTrackingSlipUrl = $trackingId !== ''
    ? app_url('tracking-slip.php') . '?tracking_id=' . rawurlencode($trackingId) . '&public=1'
    : '';
$qrText = $publicTrackingSlipUrl !== '' ? $publicTrackingSlipUrl : ($trackingId !== '' ? $trackingId : 'QR-STAMP');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soft Copy QR Stamping<?php echo $trackingId !== '' ? ' | ' . e($trackingId) : ''; ?></title>
    <script src="<?php echo e(app_url('assets/js/vendor/qrcode-generator.js')); ?>"></script>
    <script src="<?php echo e(app_url('assets/js/local-qr.js')); ?>"></script>
    <style>
        :root {
            --bg: #eff4fa;
            --card: #ffffff;
            --line: #cedaea;
            --text: #23374a;
            --muted: #60758c;
            --accent: #2f7de1;
            --accent-soft: #ecf4ff;
            --paper: #ffffff;
            --stage-bg: #f8fbff;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #0b1421;
                --card: #152233;
                --line: #26354a;
                --text: #f1f5f9;
                --muted: #94a3b8;
                --accent: #3b82f6;
                --accent-soft: rgba(59, 130, 246, 0.12);
                --paper: #f1f5f9;
                --stage-bg: #0f1a2a;
            }
        }

        [data-theme="dark"] {
            --bg: #0b1421;
            --card: #152233;
            --line: #26354a;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #3b82f6;
            --accent-soft: rgba(59, 130, 246, 0.12);
            --paper: #f1f5f9;
            --stage-bg: #0f1a2a;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 16px;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        }

        body.is-embedded {
            padding: 0;
            background: transparent;
        }

        .shell {
            width: min(1220px, 100%);
            margin: 0 auto;
            display: grid;
            gap: 12px;
        }

        .shell.embedded {
            width: 100%;
            margin: 0;
        }

        .bar, .panel {
            border: 1px solid var(--line);
            background: var(--card);
            border-radius: 12px;
            padding: 12px;
        }

        .bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
        }

        .bar .left,
        .bar .right {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .btn, .select, .input {
            height: 36px;
            border-radius: 9px;
            border: 1px solid var(--line);
            background: var(--card);
            color: var(--text);
            padding: 0 10px;
            font-size: 13px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            background: var(--card);
            color: var(--text);
            font-weight: 700;
        }

        .btn.ghost {
            background: var(--accent-soft);
            color: #2a5f9a;
            border-color: #b9cde8;
        }

        .hint {
            margin: 0;
            font-size: 12px;
            color: var(--muted);
            margin-bottom : 5px;
        }

        .mode {
            display: inline-flex;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            height: 36px;
        }

        .mode button {
            border: none;
            background: var(--card);
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
            padding: 0 12px;
            cursor: pointer;
        }

        .mode button.is-active {
            background: var(--accent-soft);
            color: #1f5d9d;
        }

        .stage-wrap {
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--stage-bg);
            padding: 12px;
            display: flex;
            justify-content: center;
        }

        .stamp-stage {
            position: relative;
            width: 595px;
            height: 842px;
            border: 1px solid var(--line);
            background: var(--paper);
            user-select: none;
            touch-action: none;
            box-shadow: 0 8px 22px rgba(26, 48, 71, 0.14);
        }

        .grid-layer {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
            background-image:
                linear-gradient(to right, rgba(32, 78, 129, 0.15) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(32, 78, 129, 0.15) 1px, transparent 1px);
            background-size: var(--grid-size, 24px) var(--grid-size, 24px);
        }

        .stamp-stage.grid-mode .grid-layer {
            opacity: 1;
        }

        .qr-handle {
            position: absolute;
            width: 108px;
            height: 108px;
            border: none;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 24px rgba(20, 40, 65, 0.25);
            cursor: grab;
            touch-action: none;
            display: grid;
            place-items: center;
            padding: 4px;
        }

        .qr-handle:active {
            cursor: grabbing;
        }

        .qr-handle.is-resizing {
            cursor: nwse-resize;
        }

        .qr-handle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
        }

        .qr-resize-handle {
            position: absolute;
            z-index: 3;
            border: none;
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .qr-resize-handle::before {
            content: "";
            display: block;
            width: 10px;
            height: 10px;
            background: transparent;
            border: none;
            box-shadow: none;
            border-radius: 2px;
            opacity: 0;
        }

        .qr-resize-handle.handle-n,
        .qr-resize-handle.handle-s {
            left: 12px;
            right: 12px;
            height: 10px;
            cursor: ns-resize;
        }

        .qr-resize-handle.handle-e,
        .qr-resize-handle.handle-w {
            top: 12px;
            bottom: 12px;
            width: 10px;
            cursor: ew-resize;
        }

        .qr-resize-handle.handle-n { top: -5px; }
        .qr-resize-handle.handle-s { bottom: -5px; }
        .qr-resize-handle.handle-e { right: -5px; }
        .qr-resize-handle.handle-w { left: -5px; }

        .qr-resize-handle.handle-n::before,
        .qr-resize-handle.handle-s::before {
            margin: 0 auto;
        }

        .qr-resize-handle.handle-e::before,
        .qr-resize-handle.handle-w::before {
            margin: auto 0;
        }

        .qr-resize-handle.handle-ne,
        .qr-resize-handle.handle-nw,
        .qr-resize-handle.handle-se,
        .qr-resize-handle.handle-sw {
            width: 14px;
            height: 14px;
        }

        .qr-resize-handle.handle-ne { top: -7px; right: -7px; cursor: nesw-resize; }
        .qr-resize-handle.handle-nw { top: -7px; left: -7px; cursor: nwse-resize; }
        .qr-resize-handle.handle-se { bottom: -7px; right: -7px; cursor: nwse-resize; }
        .qr-resize-handle.handle-sw { bottom: -7px; left: -7px; cursor: nesw-resize; }

        .status-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
            color: var(--muted);
        }

        .status-pill {
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--text);
            padding: 3px 10px;
            font-weight: 700;
        }

        .flash {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
        }

        .flash.warn { color: #a15a06; }

        .shell.embedded .panel {
            border-radius: 0;
            border-left: none;
            border-right: none;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .stamp-stage {
                transform-origin: top center;
            }
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .shell > .bar,
            .panel > .bar,
            .panel > .hint,
            .status-row {
                display: none !important;
            }
            .panel {
                border: none;
                border-radius: 0;
                padding: 0;
                background: #fff;
            }
            .stage-wrap {
                border: none;
                border-radius: 0;
                background: #fff;
                padding: 0;
            }
            .stamp-stage {
                border: none;
                box-shadow: none;
            }
            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body class="<?php echo $isEmbedded ? 'is-embedded' : ''; ?>">
    <div class="shell<?php echo $isEmbedded ? ' embedded' : ''; ?>">
        <?php if (!$isEmbedded): ?>
        <div class="bar">
            <div class="left">
                <a class="btn ghost" href="<?php echo e($printPackagePath); ?>">Back to Print Package</a>
                <span class="status-pill">Tracking ID: <?php echo e($trackingId !== '' ? $trackingId : 'N/A'); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel">
            <div class="bar" style="padding:0; border:none; background:transparent;">
                <div class="left">
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Paper Size</span>
                        <select class="select" id="paperSizeSelect">
                            <option value="A4">A4 (8.27 x 11.69)</option>
                            <option value="LETTER">Letter (8.5 x 11)</option>
                            <option value="CUSTOM_85X13">Custom (8.5 x 13)</option>
                        </select>
                    </label>

                    <div class="mode" aria-label="QR placement mode">
                        <button type="button" id="modeFreeBtn">Free-Drag</button>
                        <button type="button" id="modeGridBtn">Grid-Snap</button>
                    </div>

                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Grid (px)</span>
                        <input type="number" id="gridSizeInput" class="input" min="8" max="80" step="1" value="24" style="width:76px;">
                    </label>

                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">QR Size</span>
                        <input type="range" id="qrSizeRangeInput" min="48" max="320" step="1" value="108" style="width:130px;">
                        <input type="number" id="qrSizeNumberInput" class="input" min="48" max="320" step="1" value="108" style="width:80px;">
                    </label>
                </div>
                <div class="right">
                    <button type="button" id="printSoftCopyBtn" class="btn">Print QR Stamp</button>
                    <button type="button" id="resetQrPositionBtn" class="btn">Center QR</button>
                </div>
            </div>

            <p class="hint" style="margin-top:6px;">
                QR stamping now uses a blank paper preview (not uploaded files). Printing is set to no margins.
            </p>

                <div class="stage-wrap">
                <div id="stampStage" class="stamp-stage">
                    <div id="gridLayer" class="grid-layer"></div>
                    <div id="qrHandle" class="qr-handle" aria-label="Drag QR code">
                        <img id="qrImagePreview" data-qr-text="<?php echo e($qrText); ?>" alt="QR code">
                        <button type="button" class="qr-resize-handle handle-n" data-resize-dir="n" aria-label="Resize top edge"></button>
                        <button type="button" class="qr-resize-handle handle-s" data-resize-dir="s" aria-label="Resize bottom edge"></button>
                        <button type="button" class="qr-resize-handle handle-e" data-resize-dir="e" aria-label="Resize right edge"></button>
                        <button type="button" class="qr-resize-handle handle-w" data-resize-dir="w" aria-label="Resize left edge"></button>
                        <button type="button" class="qr-resize-handle handle-ne" data-resize-dir="ne" aria-label="Resize top right corner"></button>
                        <button type="button" class="qr-resize-handle handle-nw" data-resize-dir="nw" aria-label="Resize top left corner"></button>
                        <button type="button" class="qr-resize-handle handle-se" data-resize-dir="se" aria-label="Resize bottom right corner"></button>
                        <button type="button" class="qr-resize-handle handle-sw" data-resize-dir="sw" aria-label="Resize bottom left corner"></button>
                    </div>
                </div>
            </div>

            <div class="status-row">
                <span class="status-pill">Paper: <strong id="paperLabel">A4</strong></span>
                <span class="status-pill">Mode: <strong id="currentModeLabel">Grid-Snap</strong></span>
                <span class="status-pill">Size: <strong id="currentSizeLabel">108</strong>px</span>
                <span class="status-pill">X: <strong id="currentXLabel">50.00</strong>%</span>
                <span class="status-pill">Y: <strong id="currentYLabel">50.00</strong>%</span>
                <p id="flashMessage" class="flash"></p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modeFreeBtn = document.getElementById('modeFreeBtn');
            const modeGridBtn = document.getElementById('modeGridBtn');
            const currentModeLabel = document.getElementById('currentModeLabel');
            const currentSizeLabel = document.getElementById('currentSizeLabel');
            const currentXLabel = document.getElementById('currentXLabel');
            const currentYLabel = document.getElementById('currentYLabel');
            const flashMessage = document.getElementById('flashMessage');
            const stage = document.getElementById('stampStage');
            const handle = document.getElementById('qrHandle');
            const gridLayer = document.getElementById('gridLayer');
            const gridSizeInput = document.getElementById('gridSizeInput');
            const qrSizeRangeInput = document.getElementById('qrSizeRangeInput');
            const qrSizeNumberInput = document.getElementById('qrSizeNumberInput');
            const resetBtn = document.getElementById('resetQrPositionBtn');
            const printBtn = document.getElementById('printSoftCopyBtn');
            const paperSizeSelect = document.getElementById('paperSizeSelect');
            const paperLabel = document.getElementById('paperLabel');
            const qrImagePreview = document.getElementById('qrImagePreview');
            const qrResizeHandles = Array.from(handle.querySelectorAll('[data-resize-dir]'));

            const qrText = <?php echo json_encode($qrText); ?>;
            const safeTrackingId = <?php echo json_encode($trackingId !== '' ? $trackingId : 'QR-STAMP'); ?>;
            let qrSrc = '';

            const paperPresets = {
                A4: {
                    label: 'A4',
                    widthIn: 8.27,
                    heightIn: 11.69,
                    printSize: 'A4'
                },
                LETTER: {
                    label: 'Letter (8.5 x 11)',
                    widthIn: 8.5,
                    heightIn: 11,
                    printSize: 'Letter'
                },
                CUSTOM_85X13: {
                    label: 'Custom (8.5 x 13)',
                    widthIn: 8.5,
                    heightIn: 13,
                    printSize: '8.5in 13in'
                }
            };

            let mode = 'Grid-Snap';
            let xPct = 50;
            let yPct = 50;
            let pointerActive = false;
            let pointerId = null;
            let dragOffsetX = 0;
            let dragOffsetY = 0;
            let resizeState = null;
            let selectedPaper = 'A4';
            let qrSizePx = 108;

            const MIN_QR_SIZE = 48;
            const MAX_QR_SIZE = 320;

            function clamp(value, min, max) {
                return Math.min(max, Math.max(min, value));
            }

            function stageAvailableWidth() {
                const available = stage.clientWidth - handle.offsetWidth;
                return Math.max(0, available);
            }

            function stageAvailableHeight() {
                const available = stage.clientHeight - handle.offsetHeight;
                return Math.max(0, available);
            }

            function pctToPixelsX(percent) {
                return (clamp(percent, 0, 100) / 100) * stageAvailableWidth();
            }

            function pctToPixelsY(percent) {
                return (clamp(percent, 0, 100) / 100) * stageAvailableHeight();
            }

            function pixelsToPctX(px) {
                const available = stageAvailableWidth();
                if (available <= 0) {
                    return 0;
                }
                return (px / available) * 100;
            }

            function pixelsToPctY(py) {
                const available = stageAvailableHeight();
                if (available <= 0) {
                    return 0;
                }
                return (py / available) * 100;
            }

            function currentGridSize() {
                const parsed = parseInt(String(gridSizeInput ? gridSizeInput.value : '24'), 10);
                if (!Number.isFinite(parsed)) {
                    return 24;
                }
                return clamp(parsed, 8, 80);
            }

            function normalizeQrSize(value) {
                const parsed = parseInt(String(value ?? ''), 10);
                if (!Number.isFinite(parsed)) {
                    return qrSizePx;
                }
                return clamp(parsed, MIN_QR_SIZE, MAX_QR_SIZE);
            }

            function snapToGrid(px, py) {
                const grid = currentGridSize();
                const snappedX = Math.round(px / grid) * grid;
                const snappedY = Math.round(py / grid) * grid;
                return {
                    x: clamp(snappedX, 0, stageAvailableWidth()),
                    y: clamp(snappedY, 0, stageAvailableHeight())
                };
            }

            function updateModeUi() {
                const isGrid = mode === 'Grid-Snap';
                stage.classList.toggle('grid-mode', isGrid);
                if (gridLayer) {
                    gridLayer.style.setProperty('--grid-size', String(currentGridSize()) + 'px');
                }
                if (modeFreeBtn) {
                    modeFreeBtn.classList.toggle('is-active', !isGrid);
                }
                if (modeGridBtn) {
                    modeGridBtn.classList.toggle('is-active', isGrid);
                }
                if (currentModeLabel) {
                    currentModeLabel.textContent = mode;
                }
            }

            function updateStatusUi() {
                if (currentSizeLabel) {
                    currentSizeLabel.textContent = String(qrSizePx);
                }
                if (currentXLabel) {
                    currentXLabel.textContent = Number(xPct).toFixed(2);
                }
                if (currentYLabel) {
                    currentYLabel.textContent = Number(yPct).toFixed(2);
                }
            }

            function syncQrSizeInputs() {
                if (qrSizeRangeInput) {
                    qrSizeRangeInput.value = String(qrSizePx);
                }
                if (qrSizeNumberInput) {
                    qrSizeNumberInput.value = String(qrSizePx);
                }
            }

            function applyQrSize(nextSize) {
                qrSizePx = normalizeQrSize(nextSize);
                handle.style.width = String(qrSizePx) + 'px';
                handle.style.height = String(qrSizePx) + 'px';
                syncQrSizeInputs();
                placeHandleFromState(mode === 'Grid-Snap');
            }

            function getResizeDeltaForDirection(direction, deltaX, deltaY) {
                if (direction === 'e') {
                    return deltaX;
                }
                if (direction === 's') {
                    return deltaY;
                }
                if (direction === 'w') {
                    return -deltaX;
                }
                if (direction === 'n') {
                    return -deltaY;
                }
                if (direction === 'se') {
                    return Math.abs(deltaX) >= Math.abs(deltaY) ? deltaX : deltaY;
                }
                if (direction === 'nw') {
                    return Math.abs(deltaX) >= Math.abs(deltaY) ? -deltaX : -deltaY;
                }
                if (direction === 'ne') {
                    return Math.abs(deltaX) >= Math.abs(deltaY) ? deltaX : -deltaY;
                }
                if (direction === 'sw') {
                    return Math.abs(deltaX) >= Math.abs(deltaY) ? -deltaX : deltaY;
                }
                return 0;
            }

            function getResizeMaxSizeForDirection(direction, startLeft, startTop, startRight, startBottom) {
                const stageWidth = Math.max(1, stage.clientWidth);
                const stageHeight = Math.max(1, stage.clientHeight);
                let maxSize = MAX_QR_SIZE;

                if (direction === 'e' || direction === 's' || direction === 'se') {
                    maxSize = Math.min(stageWidth - startLeft, stageHeight - startTop);
                } else if (direction === 'w' || direction === 'sw') {
                    maxSize = Math.min(startRight, stageHeight - startTop);
                } else if (direction === 'n' || direction === 'ne') {
                    maxSize = Math.min(stageWidth - startLeft, startBottom);
                } else if (direction === 'nw') {
                    maxSize = Math.min(startRight, startBottom);
                }

                return clamp(maxSize, MIN_QR_SIZE, MAX_QR_SIZE);
            }

            function placeHandleFromState(snapForGrid) {
                let left = pctToPixelsX(xPct);
                let top = pctToPixelsY(yPct);

                if (mode === 'Grid-Snap' && snapForGrid) {
                    const snapped = snapToGrid(left, top);
                    left = snapped.x;
                    top = snapped.y;
                    xPct = clamp(pixelsToPctX(left), 0, 100);
                    yPct = clamp(pixelsToPctY(top), 0, 100);
                }

                handle.style.left = left + 'px';
                handle.style.top = top + 'px';
                updateStatusUi();
            }

            function showFlash(message) {
                if (!flashMessage) {
                    return;
                }
                flashMessage.textContent = String(message || '');
                flashMessage.className = 'flash warn';
            }

            function clearFlash() {
                if (!flashMessage) {
                    return;
                }
                flashMessage.textContent = '';
                flashMessage.className = 'flash';
            }

            function buildQrSource() {
                if (!window.edatsLocalQr || typeof window.edatsLocalQr.generateDataUrl !== 'function') {
                    throw new Error('Local QR generator is not available.');
                }
                return window.edatsLocalQr.generateDataUrl(qrText, {
                    size: 512,
                    margin: 2,
                    errorCorrection: 'M',
                    typeNumber: 0
                });
            }

            function renderQrPreview() {
                try {
                    qrSrc = buildQrSource();
                    if (qrImagePreview) {
                        qrImagePreview.setAttribute('src', qrSrc);
                    }
                } catch (error) {
                    qrSrc = '';
                    showFlash('Unable to generate QR locally. Please refresh the page.');
                }
            }

            function switchMode(nextMode) {
                if (nextMode !== 'Free-Drag' && nextMode !== 'Grid-Snap') {
                    return;
                }
                mode = nextMode;
                updateModeUi();
                placeHandleFromState(true);
                clearFlash();
            }

            function setPositionFromPointer(clientX, clientY) {
                const rect = stage.getBoundingClientRect();
                let left = clientX - rect.left - dragOffsetX;
                let top = clientY - rect.top - dragOffsetY;

                left = clamp(left, 0, stageAvailableWidth());
                top = clamp(top, 0, stageAvailableHeight());

                if (mode === 'Grid-Snap') {
                    const snapped = snapToGrid(left, top);
                    left = snapped.x;
                    top = snapped.y;
                }

                xPct = clamp(pixelsToPctX(left), 0, 100);
                yPct = clamp(pixelsToPctY(top), 0, 100);
                placeHandleFromState(false);
            }

            function beginDrag(event) {
                if (resizeState) {
                    return;
                }
                if (event.target && event.target.closest('[data-resize-dir]')) {
                    return;
                }
                if (event.button !== undefined && event.button !== 0) {
                    return;
                }
                pointerActive = true;
                pointerId = Number.isFinite(event.pointerId) ? event.pointerId : null;
                const rect = handle.getBoundingClientRect();
                dragOffsetX = event.clientX - rect.left;
                dragOffsetY = event.clientY - rect.top;
                if (typeof handle.setPointerCapture === 'function' && Number.isFinite(event.pointerId)) {
                    handle.setPointerCapture(event.pointerId);
                }
                event.preventDefault();
            }

            function moveDrag(event) {
                if (!pointerActive || resizeState) {
                    return;
                }
                if (pointerId !== null && Number.isFinite(event.pointerId) && event.pointerId !== pointerId) {
                    return;
                }
                setPositionFromPointer(event.clientX, event.clientY);
                event.preventDefault();
            }

            function endDrag(event) {
                if (!pointerActive) {
                    return;
                }
                if (pointerId !== null && Number.isFinite(event.pointerId) && event.pointerId !== pointerId) {
                    return;
                }
                pointerActive = false;
                pointerId = null;
                if (typeof handle.releasePointerCapture === 'function' && Number.isFinite(event.pointerId)) {
                    try {
                        handle.releasePointerCapture(event.pointerId);
                    } catch (error) {
                        // Ignore release errors.
                    }
                }
            }

            function beginResize(event) {
                if (event.button !== undefined && event.button !== 0) {
                    return;
                }
                const resizeHandle = event.currentTarget;
                if (!resizeHandle) {
                    return;
                }
                const direction = String(resizeHandle.getAttribute('data-resize-dir') || '').toLowerCase();
                if (!direction) {
                    return;
                }

                const stageRect = stage.getBoundingClientRect();
                const handleRect = handle.getBoundingClientRect();
                const startLeft = handleRect.left - stageRect.left;
                const startTop = handleRect.top - stageRect.top;
                const startSize = Math.round(handleRect.width);
                const startRight = startLeft + startSize;
                const startBottom = startTop + startSize;
                const maxSize = getResizeMaxSizeForDirection(direction, startLeft, startTop, startRight, startBottom);

                pointerActive = false;
                pointerId = null;
                resizeState = {
                    pointerId: Number.isFinite(event.pointerId) ? event.pointerId : null,
                    handle: resizeHandle,
                    direction: direction,
                    startPointerX: event.clientX,
                    startPointerY: event.clientY,
                    startLeft: startLeft,
                    startTop: startTop,
                    startRight: startRight,
                    startBottom: startBottom,
                    startSize: startSize,
                    maxSize: Math.max(startSize, maxSize)
                };

                handle.classList.add('is-resizing');
                if (typeof resizeHandle.setPointerCapture === 'function' && Number.isFinite(event.pointerId)) {
                    resizeHandle.setPointerCapture(event.pointerId);
                }
                event.preventDefault();
                event.stopPropagation();
            }

            function moveResize(event) {
                if (!resizeState) {
                    return;
                }
                if (resizeState.pointerId !== null && Number.isFinite(event.pointerId) && event.pointerId !== resizeState.pointerId) {
                    return;
                }

                const deltaX = event.clientX - resizeState.startPointerX;
                const deltaY = event.clientY - resizeState.startPointerY;
                const sizeDelta = getResizeDeltaForDirection(resizeState.direction, deltaX, deltaY);
                let nextSize = resizeState.startSize + sizeDelta;
                nextSize = clamp(nextSize, MIN_QR_SIZE, resizeState.maxSize);
                qrSizePx = normalizeQrSize(nextSize);

                let nextLeft = resizeState.startLeft;
                let nextTop = resizeState.startTop;

                if (resizeState.direction.indexOf('w') !== -1) {
                    nextLeft = resizeState.startRight - qrSizePx;
                }
                if (resizeState.direction.indexOf('n') !== -1) {
                    nextTop = resizeState.startBottom - qrSizePx;
                }

                nextLeft = clamp(nextLeft, 0, Math.max(0, stage.clientWidth - qrSizePx));
                nextTop = clamp(nextTop, 0, Math.max(0, stage.clientHeight - qrSizePx));

                handle.style.width = String(qrSizePx) + 'px';
                handle.style.height = String(qrSizePx) + 'px';
                handle.style.left = Math.round(nextLeft) + 'px';
                handle.style.top = Math.round(nextTop) + 'px';

                xPct = clamp(pixelsToPctX(nextLeft), 0, 100);
                yPct = clamp(pixelsToPctY(nextTop), 0, 100);
                syncQrSizeInputs();
                updateStatusUi();
                event.preventDefault();
            }

            function endResize(event) {
                if (!resizeState) {
                    return;
                }
                if (resizeState.pointerId !== null && Number.isFinite(event.pointerId) && event.pointerId !== resizeState.pointerId) {
                    return;
                }

                const activeHandle = resizeState.handle;
                if (activeHandle && typeof activeHandle.releasePointerCapture === 'function' && Number.isFinite(event.pointerId)) {
                    try {
                        activeHandle.releasePointerCapture(event.pointerId);
                    } catch (error) {
                        // Ignore release errors.
                    }
                }

                resizeState = null;
                handle.classList.remove('is-resizing');
                updateStatusUi();
            }

            function applyPaperSize(paperKey) {
                const preset = paperPresets[paperKey] || paperPresets.A4;
                selectedPaper = paperPresets[paperKey] ? paperKey : 'A4';
                const previewScale = 72;
                const widthPx = Math.round(preset.widthIn * previewScale);
                const heightPx = Math.round(preset.heightIn * previewScale);

                stage.style.width = String(widthPx) + 'px';
                stage.style.height = String(heightPx) + 'px';

                if (paperSizeSelect) {
                    paperSizeSelect.value = selectedPaper;
                }
                if (paperLabel) {
                    paperLabel.textContent = preset.label;
                }

                placeHandleFromState(mode === 'Grid-Snap');
            }

            function printStamp() {
                const preset = paperPresets[selectedPaper] || paperPresets.A4;
                const stageWidth = Math.max(stage ? stage.clientWidth : 0, 1);
                const stageHeight = Math.max(stage ? stage.clientHeight : 0, 1);
                const leftPx = pctToPixelsX(xPct);
                const topPx = pctToPixelsY(yPct);
                const safeX = Number((leftPx / stageWidth) * 100).toFixed(4);
                const safeY = Number((topPx / stageHeight) * 100).toFixed(4);
                const safeQrW = Number((handle.offsetWidth / stageWidth) * 100).toFixed(4);
                const safeQrH = Number((handle.offsetHeight / stageHeight) * 100).toFixed(4);

                const printWindow = window.open(
                    'about:blank',
                    '_blank',
                    'popup=yes,width=1100,height=850,resizable=yes,scrollbars=yes'
                );
                if (!printWindow) {
                    showFlash('Popup blocked. Please allow popups for this site to print QR stamp.');
                    return;
                }
                if (qrSrc === '') {
                    showFlash('QR source is not ready yet. Please wait a moment and try again.');
                    return;
                }

                const printHtml = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QR Stamp Print - ${safeTrackingId}</title>
  <style>
    @page { size: ${preset.printSize}; margin: 0; }
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
      background: #fff;
    }
    .page {
      position: relative;
      width: 100%;
      height: 100%;
      background: #fff;
      overflow: hidden;
      box-sizing: border-box;
    }
    .qr {
      position: absolute;
      left: ${safeX}%;
      top: ${safeY}%;
      width: ${safeQrW}%;
      height: ${safeQrH}%;
      border: none;
      box-sizing: border-box;
      background: #fff;
    }
    @media screen {
      body {
        display: flex;
        align-items: flex-start;
        justify-content: center;
      }
      .page {
        width: ${preset.widthIn}in;
        height: ${preset.heightIn}in;
        box-shadow: 0 8px 22px rgba(26, 48, 71, 0.18);
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <img id="qrImage" class="qr" src="${qrSrc}" alt="QR code">
  </div>
  <script>
    (function () {
      const qr = document.getElementById('qrImage');
      const triggerPrint = function () {
        window.focus();
        setTimeout(function () { window.print(); }, 80);
      };

      if (qr && !qr.complete) {
        qr.addEventListener('load', triggerPrint, { once: true });
        qr.addEventListener('error', triggerPrint, { once: true });
      } else {
        triggerPrint();
      }

      window.addEventListener('afterprint', function () {
        setTimeout(function () { window.close(); }, 0);
      });
    })();
  <\/script>
</body>
</html>`;

                try {
                    printWindow.document.open();
                    printWindow.document.write(printHtml);
                    printWindow.document.close();
                } catch (error) {
                    showFlash('Unable to render print preview popup. Please try again.');
                }
            }

            if (modeFreeBtn) {
                modeFreeBtn.addEventListener('click', function () {
                    switchMode('Free-Drag');
                });
            }

            if (modeGridBtn) {
                modeGridBtn.addEventListener('click', function () {
                    switchMode('Grid-Snap');
                });
            }

            if (gridSizeInput) {
                gridSizeInput.addEventListener('change', function () {
                    gridSizeInput.value = String(currentGridSize());
                    updateModeUi();
                    placeHandleFromState(mode === 'Grid-Snap');
                });
            }

            if (qrSizeRangeInput) {
                qrSizeRangeInput.addEventListener('input', function () {
                    applyQrSize(qrSizeRangeInput.value);
                    clearFlash();
                });
            }

            if (qrSizeNumberInput) {
                qrSizeNumberInput.addEventListener('input', function () {
                    applyQrSize(qrSizeNumberInput.value);
                    clearFlash();
                });
                qrSizeNumberInput.addEventListener('change', function () {
                    applyQrSize(qrSizeNumberInput.value);
                    clearFlash();
                });
            }

            if (paperSizeSelect) {
                paperSizeSelect.addEventListener('change', function () {
                    applyPaperSize(String(paperSizeSelect.value || 'A4').toUpperCase());
                    clearFlash();
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    xPct = 50;
                    yPct = 50;
                    placeHandleFromState(mode === 'Grid-Snap');
                    clearFlash();
                });
            }

            if (printBtn) {
                printBtn.addEventListener('click', function () {
                    printStamp();
                });
            }

            handle.addEventListener('pointerdown', beginDrag);
            qrResizeHandles.forEach(function (resizeHandle) {
                resizeHandle.addEventListener('pointerdown', beginResize);
            });
            window.addEventListener('pointermove', function (event) {
                moveResize(event);
                moveDrag(event);
            });
            window.addEventListener('pointerup', function (event) {
                endResize(event);
                endDrag(event);
            });
            window.addEventListener('pointercancel', function (event) {
                endResize(event);
                endDrag(event);
            });

            window.addEventListener('resize', function () {
                placeHandleFromState(mode === 'Grid-Snap');
            });

            updateModeUi();
            applyPaperSize('A4');
            applyQrSize(qrSizePx);
            renderQrPreview();
            placeHandleFromState(true);
        })();

        // Sync theme with parent edats dashboard if available
        (function() {
            function syncTheme() {
                try {
                    const theme = window.parent.document.documentElement.getAttribute('data-theme') 
                         || window.parent.document.body.getAttribute('data-theme');
                    if (theme) {
                        document.documentElement.setAttribute('data-theme', theme);
                    }
                } catch(e) {}
            }
            syncTheme();
            const observer = new MutationObserver(syncTheme);
            try {
                observer.observe(window.parent.document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
                observer.observe(window.parent.document.body, { attributes: true, attributeFilter: ['data-theme'] });
            } catch(e) {}
        })();
    </script>
</body>
</html>
