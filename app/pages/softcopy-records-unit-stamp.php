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
$isAutoPrint = ((string)($_GET['autoprint'] ?? '') === '1');
$stampType = strtolower(trim((string)($_GET['stamp_type'] ?? 'received')));
if (!in_array($stampType, ['received', 'released'], true)) {
    $stampType = 'received';
}

$stampLabel = $stampType === 'released' ? 'RELEASED' : 'RECEIVED';
$sessionRoleName = (string)($_SESSION['role_name'] ?? 'RECORDS_UNIT');
$sessionRoleKey = app_normalize_role_key($sessionRoleName);
$sessionRoleFolder = app_role_folder_from_role($sessionRoleName) ?? app_role_folder_from_role('RECORDS_UNIT') ?? 'RECORS-UNIT';
$stampOfficeLine = match ($sessionRoleKey) {
    'CENRO_ADMIN_RECORD' => 'ADMIN-CENRO ADMIN RECORD',
    'PENRO_ADMIN_RECORD' => 'ADMIN-PENRO ADMIN RECORD',
    'PAMO_ADMIN' => 'ADMIN-PAMO ADMIN',
    default => 'ADMIN-RECORDS-UNIT',
};
$stampWorkspaceTitle = match ($sessionRoleKey) {
    'CENRO_ADMIN_RECORD' => 'CENRO Admin Record',
    'PENRO_ADMIN_RECORD' => 'PENRO Admin Record',
    'PAMO_ADMIN' => 'PAMO Admin',
    default => 'Records Unit',
};
$sessionFullName = trim((string)(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')));
$signerDefault = $sessionFullName !== '' ? $sessionFullName : $stampOfficeLine;
$receivedByDefault = $sessionFullName !== '' ? $sessionFullName : 'Fullname';
$theme = strtolower(trim((string)($_GET['theme'] ?? 'light')));
if (!in_array($theme, ['light', 'dark'], true)) {
    $theme = 'light';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo e($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($stampWorkspaceTitle); ?> <?php echo e($stampLabel); ?> Stamp<?php echo $trackingId !== '' ? ' | ' . e($trackingId) : ''; ?></title>
    <style>
        :root {
            --bg: #eff4fa;
            --card: #ffffff;
            --line: #cedaea;
            --text: #23374a;
            --muted: #60758c;
            --accent-soft: #ecf4ff;
            --stamp-blue: #0f3e9c;
            --control-bg: #ffffff;
            --control-line: #c3d3e6;
            --control-text: #23374a;
            --panel-soft: #f8fbff;
            --panel-soft-line: #dae4ef;
            --mode-tab-bg: #ffffff;
            --mode-tab-text: #355270;
            --mode-tab-active-bg: var(--accent-soft);
            --mode-tab-active-text: #1f5d9d;
            --paper-bg: #ffffff;
            --paper-line: #c4d3e5;
            --stamp-block-bg: rgba(255, 255, 255, 0.98);
        }

        :root[data-theme="dark"],
        html[data-theme="dark"],
        body[data-theme="dark"] {
            --bg: #0f1722;
            --card: #162232;
            --line: #334b66;
            --text: #e6eef8;
            --muted: #a6b8cc;
            --accent-soft: #1f3348;
            --stamp-blue: #0f3e9c;
            --control-bg: #132235;
            --control-line: #3b5570;
            --control-text: #e6eef8;
            --panel-soft: #0f1b2a;
            --panel-soft-line: #334b66;
            --mode-tab-bg: #132235;
            --mode-tab-text: #bbcee3;
            --mode-tab-active-bg: #26415b;
            --mode-tab-active-text: #e6eef8;
            --paper-bg: #ffffff;
            --paper-line: #a8b9ca;
            --stamp-block-bg: rgba(255, 255, 255, 0.98);
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
            border: 1px solid var(--control-line);
            padding: 0 10px;
            font-size: 13px;
            background: var(--control-bg);
            color: var(--control-text);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            color: var(--text);
            font-weight: 700;
        }

        .btn.ghost {
            background: color-mix(in srgb, var(--stamp-blue) 11%, var(--control-bg));
            color: color-mix(in srgb, var(--stamp-blue) 56%, var(--control-text));
            border-color: color-mix(in srgb, var(--stamp-blue) 25%, var(--control-line));
        }

        .hint {
            margin: 0;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 5px;
        }

        .mode {
            display: inline-flex;
            border: 1px solid var(--control-line);
            border-radius: 10px;
            overflow: hidden;
            height: 36px;
        }

        .mode button {
            border: none;
            background: var(--mode-tab-bg);
            color: var(--mode-tab-text);
            font-size: 12px;
            font-weight: 700;
            padding: 0 12px;
            cursor: pointer;
        }

        .mode button.is-active {
            background: var(--mode-tab-active-bg);
            color: var(--mode-tab-active-text);
        }

        .stage-wrap {
            overflow: auto;
            border: 1px solid var(--panel-soft-line);
            border-radius: 10px;
            background: var(--panel-soft);
            padding: 12px;
            display: flex;
            justify-content: center;
        }

        .stamp-stage {
            position: relative;
            width: 595px;
            height: 842px;
            border: 1px solid var(--paper-line);
            background: var(--paper-bg);
            user-select: none;
            touch-action: none;
            box-shadow: 0 8px 22px rgba(26, 48, 71, 0.14);
        }

        .stamp-canvas-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
            z-index: 0;
            background: #fff;
        }

        .stamp-canvas-empty {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            color: #4b6178;
            font-size: 13px;
            z-index: 0;
            background: #fff;
        }

        .grid-layer {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
            z-index: 1;
            background-image:
                linear-gradient(to right, rgba(32, 78, 129, 0.15) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(32, 78, 129, 0.15) 1px, transparent 1px);
            background-size: var(--grid-size, 24px) var(--grid-size, 24px);
        }

        .stamp-stage.grid-mode .grid-layer {
            opacity: 1;
        }

        .stamp-block {
            position: absolute;
            z-index: 2;
            width: 360px;
            height: 260px;
            min-height: 120px;
            border: 4px solid var(--stamp-blue);
            border-radius: 2px;
            color: var(--stamp-blue);
            background: var(--stamp-block-bg);
            box-shadow: 0 6px 16px rgba(26, 48, 71, 0.22);
            cursor: grab;
            touch-action: none;
            padding: 10px 12px 12px;
            font-family: "Times New Roman", Georgia, serif;
            overflow: hidden;
            font-size: calc(16px * var(--fs-scale, 1));
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
        }

        .stamp-block:active {
            cursor: grabbing;
        }

        .stamp-header {
            text-align: center;
            line-height: 0.95;
            margin-bottom: 0;
        }

        .stamp-top-line {
            font-size: 1.02em;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            display: block;
        }

        .stamp-main-line {
            font-size: 2.45em;
            font-weight: 700;
            letter-spacing: 0.01em;
            text-transform: uppercase;
            display: block;
        }

        .stamp-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: calc(26px * var(--fs-scale, 1));
            margin-top: 0;
            font-size: 0.78em;
            font-weight: 700;
        }

        .stamp-label,
        .stamp-value,
        .stamp-sign {
            padding: 0;
        }

        .stamp-label {
            min-width: 0;
            font-size: 0.9em;
            text-align: center;
        }

        .stamp-value {
            flex: 0 1 auto;
            text-align: center;
            font-size: 1.08em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .stamp-sign {
            flex: 0 1 auto;
            text-align: center;
            min-height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Brush Script MT", "Segoe Script", cursive;
            font-size: 2.2em;
            line-height: 1;
        }

        .stamp-sign img {
            max-width: 100%;
            max-height: 44px;
            object-fit: contain;
            display: none;
        }

        .resize-handle {
            position: absolute;
            z-index: 3;
            border: none;
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .resize-handle::before {
            content: "";
            display: block;
            width: 10px;
            height: 10px;
            background: var(--stamp-blue);
            border: 1px solid #ffffff;
            box-shadow: 0 1px 3px rgba(14, 28, 41, 0.45);
            border-radius: 2px;
            opacity: 0.95;
        }

        .resize-handle.handle-n,
        .resize-handle.handle-s {
            left: 12px;
            right: 12px;
            height: 10px;
            cursor: ns-resize;
        }

        .resize-handle.handle-e,
        .resize-handle.handle-w {
            top: 12px;
            bottom: 12px;
            width: 10px;
            cursor: ew-resize;
        }

        .resize-handle.handle-n { top: -5px; }
        .resize-handle.handle-s { bottom: -5px; }
        .resize-handle.handle-e { right: -5px; }
        .resize-handle.handle-w { left: -5px; }

        .resize-handle.handle-n::before,
        .resize-handle.handle-s::before {
            margin: 0 auto;
        }

        .resize-handle.handle-e::before,
        .resize-handle.handle-w::before {
            margin: auto 0;
        }

        .resize-handle.handle-ne,
        .resize-handle.handle-nw,
        .resize-handle.handle-se,
        .resize-handle.handle-sw {
            width: 14px;
            height: 14px;
        }

        .resize-handle.handle-ne { top: -7px; right: -7px; cursor: nesw-resize; }
        .resize-handle.handle-nw { top: -7px; left: -7px; cursor: nwse-resize; }
        .resize-handle.handle-se { bottom: -7px; right: -7px; cursor: nwse-resize; }
        .resize-handle.handle-sw { bottom: -7px; left: -7px; cursor: nesw-resize; }

        .status-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
            color: #476382;
        }

        .status-pill {
            border: 1px solid #c5d6eb;
            border-radius: 999px;
            background: #f5f9ff;
            color: #2d547c;
            padding: 3px 10px;
            font-weight: 700;
        }

        .flash {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
        }

        .shell.embedded .panel {
            border-radius: 0;
            border-left: none;
            border-right: none;
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
            .stamp-block {
                box-shadow: none;
            }
            .resize-handle {
                display: none !important;
            }
            .grid-layer {
                display: none !important;
            }
            @page {
                margin: 0;
                size: A4;
            }
        }
    </style>
</head>
<body class="<?php echo $isEmbedded ? 'is-embedded' : ''; ?>" data-theme="<?php echo e($theme); ?>">
    <div class="shell<?php echo $isEmbedded ? ' embedded' : ''; ?>">
        <?php if (!$isEmbedded): ?>
        <div class="bar">
            <div class="left">
                <a class="btn ghost" href="<?php echo e(app_url($sessionRoleFolder . '/action-stamp.php')); ?>">Back to Action Stamp Workspace</a>
                <span class="status-pill">Tracking ID: <?php echo e($trackingId !== '' ? $trackingId : 'N/A'); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel">
            <div class="bar" style="padding:0; border:none; background:transparent;">
                <div class="left">
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Stamp Type</span>
                        <select class="select" id="stampTypeSelect">
                            <option value="received" <?php echo $stampType === 'received' ? 'selected' : ''; ?>>RECEIVED</option>
                            <option value="released" <?php echo $stampType === 'released' ? 'selected' : ''; ?>>RELEASED</option>
                        </select>
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Paper Size</span>
                        <select class="select" id="paperSizeSelect">
                            <option value="A4">A4 (8.27 x 11.69)</option>
                            <option value="LETTER">Letter (8.5 x 11)</option>
                            <option value="CUSTOM_85X13">Custom (8.5 x 13)</option>
                        </select>
                    </label>
                    <div class="mode" aria-label="Stamp placement mode">
                        <button type="button" id="modeFreeBtn">Free-Drag</button>
                        <button type="button" id="modeGridBtn">Grid-Snap</button>
                    </div>
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Grid (px)</span>
                        <input type="number" id="gridSizeInput" class="input" min="8" max="80" step="1" value="24" style="width:76px;">
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Canvas Image</span>
                        <select class="select" id="canvasImageSelect" style="min-width:250px;">
                            <option value="__BLANK__">Blank canvas</option>
                            <option value="">Auto-select latest image</option>
                        </select>
                    </label>
                </div>
                <div class="right">
                    <button type="button" id="printSoftCopyBtn" class="btn">Print Stamp</button>
                    <button type="button" id="resetStampPositionBtn" class="btn">Center Stamp</button>
                </div>
            </div>

            <div class="bar" style="margin-top:8px;">
                <div class="left">
                    <span class="hint">Date is realtime and auto-updated every second.</span>
                    <label style="display:inline-flex; align-items:center; gap:6px; min-width:280px;">
                        <span class="hint">Sign Text</span>
                        <input type="text" id="stampSignInput" class="input" value="<?php echo e($signerDefault); ?>" placeholder="Type signer name" style="min-width:280px;">
                    </label>
                </div>
                <div class="right">
                    <input id="stampSignatureImageInput" class="input" type="file" accept="image/png,image/jpeg,image/jpg,image/bmp,image/gif">
                    <button type="button" id="removeSignatureBtn" class="btn ghost">Remove Signature Image</button>
                </div>
            </div>

            <p class="hint" style="margin-top:6px;">
                Dynamic stamp: realtime date, editable signature, drag freely, resize directly on image canvas, and print.
                Signed workflows now auto-select the latest digitally signed image canvas for admin stamping.
            </p>

            <div class="stage-wrap">
                <div id="stampStage" class="stamp-stage">
                    <img id="stampCanvasImage" class="stamp-canvas-image" alt="Workflow image canvas preview">
                    <div id="stampCanvasEmpty" class="stamp-canvas-empty">Loading workflow image canvas...</div>
                    <div id="gridLayer" class="grid-layer"></div>
                    <div id="stampBlock" class="stamp-block" aria-label="Drag stamp">
                        <div class="stamp-header">
                            <span class="stamp-top-line">DENR XII</span>
                            <span class="stamp-top-line"><?php echo e($stampOfficeLine); ?></span>
                            <span id="stampMainLine" class="stamp-main-line"><?php echo e($stampLabel); ?></span>
                        </div>
                        <div class="stamp-row">
                            <span class="stamp-label">Date:</span>
                            <span id="stampDateValue" class="stamp-value">-</span>
                        </div>
                        <div class="stamp-row">
                            <span class="stamp-label">recieved by :</span>
                            <span id="stampReceivedByValue" class="stamp-value"><?php echo e($receivedByDefault); ?></span>
                        </div>
                        <div class="stamp-row">
                            <span class="stamp-label">Sign:</span>
                            <span id="stampSignValue" class="stamp-sign">
                                <img id="stampSignaturePreview" alt="Signature preview">
                                <span id="stampSignText"></span>
                            </span>
                        </div>
                        <button type="button" class="resize-handle handle-n" data-handle="n" aria-label="Resize north"></button>
                        <button type="button" class="resize-handle handle-s" data-handle="s" aria-label="Resize south"></button>
                        <button type="button" class="resize-handle handle-e" data-handle="e" aria-label="Resize east"></button>
                        <button type="button" class="resize-handle handle-w" data-handle="w" aria-label="Resize west"></button>
                        <button type="button" class="resize-handle handle-ne" data-handle="ne" aria-label="Resize north east"></button>
                        <button type="button" class="resize-handle handle-nw" data-handle="nw" aria-label="Resize north west"></button>
                        <button type="button" class="resize-handle handle-se" data-handle="se" aria-label="Resize south east"></button>
                        <button type="button" class="resize-handle handle-sw" data-handle="sw" aria-label="Resize south west"></button>
                    </div>
                </div>
            </div>

            <div class="status-row">
                <span class="status-pill">Paper: <strong id="paperLabel">A4</strong></span>
                <span class="status-pill">Mode: <strong id="currentModeLabel">Grid-Snap</strong></span>
                <span class="status-pill">Size: <strong id="currentSizeLabel">360 x 260</strong>px</span>
                <span class="status-pill">X: <strong id="currentXLabel">50.00</strong>%</span>
                <span class="status-pill">Y: <strong id="currentYLabel">50.00</strong>%</span>
                <p id="flashMessage" class="flash"></p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const stage = document.getElementById('stampStage');
            const block = document.getElementById('stampBlock');
            const gridLayer = document.getElementById('gridLayer');
            const stampMainLine = document.getElementById('stampMainLine');
            const stampTypeSelect = document.getElementById('stampTypeSelect');
            const paperSizeSelect = document.getElementById('paperSizeSelect');
            const modeFreeBtn = document.getElementById('modeFreeBtn');
            const modeGridBtn = document.getElementById('modeGridBtn');
            const gridSizeInput = document.getElementById('gridSizeInput');
            const canvasImageSelect = document.getElementById('canvasImageSelect');
            const printSoftCopyBtn = document.getElementById('printSoftCopyBtn');
            const resetStampPositionBtn = document.getElementById('resetStampPositionBtn');
            const stampSignInput = document.getElementById('stampSignInput');
            const stampSignatureImageInput = document.getElementById('stampSignatureImageInput');
            const removeSignatureBtn = document.getElementById('removeSignatureBtn');
            const stampDateValue = document.getElementById('stampDateValue');
            const stampReceivedByValue = document.getElementById('stampReceivedByValue');
            const stampSignText = document.getElementById('stampSignText');
            const stampSignaturePreview = document.getElementById('stampSignaturePreview');
            const stampCanvasImage = document.getElementById('stampCanvasImage');
            const stampCanvasEmpty = document.getElementById('stampCanvasEmpty');
            const paperLabel = document.getElementById('paperLabel');
            const currentModeLabel = document.getElementById('currentModeLabel');
            const currentSizeLabel = document.getElementById('currentSizeLabel');
            const currentXLabel = document.getElementById('currentXLabel');
            const currentYLabel = document.getElementById('currentYLabel');
            const flashMessage = document.getElementById('flashMessage');
            const resizeHandles = Array.from(document.querySelectorAll('.resize-handle'));
            const stampTrackingId = <?php echo json_encode($trackingId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const documentDetailsPath = <?php echo json_encode(app_url('actions/document-details.php'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const sessionRoleKey = <?php echo json_encode($sessionRoleKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const CANVAS_BLANK_VALUE = '__BLANK__';

            if (!stage || !block || !gridLayer) {
                return;
            }

            const paperSizes = {
                A4: { width: 595, height: 842, label: 'A4' },
                LETTER: { width: 612, height: 792, label: 'Letter' },
                CUSTOM_85X13: { width: 612, height: 936, label: '8.5 x 13' },
            };

            const state = {
                mode: 'grid',
                gridSize: 24,
                blockWidth: 360,
                blockHeight: 260,
                x: 0,
                y: 0,
                dragging: false,
                resizing: false,
                resizeHandle: '',
                activePointerId: null,
                pointerOffsetX: 0,
                pointerOffsetY: 0,
                stageRect: null,
                signatureDataUrl: '',
                resizeStartX: 0,
                resizeStartY: 0,
                resizeStartWidth: 360,
                resizeStartHeight: 260,
                resizeStartLeft: 0,
                resizeStartTop: 0,
                currentDateIso: '',
                receivedBy: <?php echo json_encode($receivedByDefault, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                canvasImageOptions: [],
                canvasSelectionValue: CANVAS_BLANK_VALUE,
                autoCanvasPreference: 'blank',
            };

            function clamp(value, min, max) {
                return Math.max(min, Math.min(max, value));
            }

            function flash(message) {
                if (!flashMessage) {
                    return;
                }
                flashMessage.textContent = message;
                window.setTimeout(function () {
                    if (flashMessage.textContent === message) {
                        flashMessage.textContent = '';
                    }
                }, 1200);
            }

            function applyCanvasImage(url, emptyMessage) {
                const safeUrl = String(url || '').trim();
                if (stampCanvasImage) {
                    if (safeUrl !== '') {
                        stampCanvasImage.src = safeUrl;
                        stampCanvasImage.style.display = 'block';
                    } else {
                        stampCanvasImage.removeAttribute('src');
                        stampCanvasImage.style.display = 'none';
                    }
                }
                if (stampCanvasEmpty) {
                    const hasImage = safeUrl !== '';
                    stampCanvasEmpty.style.display = hasImage ? 'none' : 'flex';
                    if (!hasImage && String(emptyMessage || '').trim() !== '') {
                        stampCanvasEmpty.textContent = String(emptyMessage || '');
                    }
                }
            }

            function normalizeRoleKey(value) {
                return String(value || '')
                    .trim()
                    .toUpperCase()
                    .replace(/[^A-Z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
            }

            function isDigitallySignedAttachment(attachment) {
                if (!attachment || typeof attachment !== 'object') {
                    return false;
                }
                const fileName = String(attachment.file_name || '').toLowerCase();
                const filePath = String(attachment.file_path || '').toLowerCase();
                if (fileName.indexOf('[digitally signed]') === 0) {
                    return true;
                }
                if (filePath.indexOf('_signed_') !== -1) {
                    return true;
                }
                return false;
            }

            function extractCanvasImageOptions(attachments) {
                const list = Array.isArray(attachments) ? attachments : [];
                const imageOptions = [];
                for (let index = 0; index < list.length; index += 1) {
                    const attachment = list[index];
                    if (!attachment || typeof attachment !== 'object') {
                        continue;
                    }
                    const previewType = String(attachment.preview_type || '').toLowerCase();
                    const fileUrl = String(attachment.file_url || '').trim();
                    if (previewType !== 'image' || fileUrl === '') {
                        continue;
                    }
                    const optionId = String(attachment.id || '').trim();
                    const optionFileName = String(attachment.file_name || 'Attachment').trim() || 'Attachment';
                    const optionUploadedAt = String(attachment.uploaded_at || '').trim();
                    const roleKey = normalizeRoleKey(attachment.uploaded_by_role_key || attachment.uploaded_by_role || '');
                    const isSigned = isDigitallySignedAttachment(attachment);
                    imageOptions.push({
                        id: optionId,
                        file_name: optionFileName,
                        file_url: fileUrl,
                        uploaded_at: optionUploadedAt,
                        uploaded_by_role_key: roleKey,
                        is_digitally_signed: isSigned,
                    });
                }
                return imageOptions;
            }

            function detectSignedWorkflowCanvas(imageOptions) {
                const options = Array.isArray(imageOptions) ? imageOptions : [];
                return options.some(function (option) {
                    if (!option || typeof option !== 'object') {
                        return false;
                    }
                    if (option.is_digitally_signed) {
                        return true;
                    }
                    const uploaderRoleKey = normalizeRoleKey(option.uploaded_by_role_key || '');
                    return (uploaderRoleKey === 'ORED'
                        || uploaderRoleKey === 'CENRO_OFFICER'
                        || uploaderRoleKey === 'PENRO_OFFICER'
                        || uploaderRoleKey === 'PASU_OFFICER')
                        && String(option.file_name || '').toLowerCase().indexOf('[digitally signed]') === 0;
                });
            }

            function selectAutoCanvasImageOption(imageOptions, preference) {
                const options = Array.isArray(imageOptions) ? imageOptions : [];
                if (options.length === 0) {
                    return null;
                }
                const mode = String(preference || '').toLowerCase();
                if (mode === 'signed') {
                    const signedOption = options.find(function (option) {
                        return !!option.is_digitally_signed;
                    });
                    if (signedOption) {
                        return signedOption;
                    }
                }
                if (mode === 'original') {
                    const originalOption = options.find(function (option) {
                        return !option.is_digitally_signed;
                    });
                    if (originalOption) {
                        return originalOption;
                    }
                }
                return options[0];
            }

            function buildCanvasImageOptionLabel(option) {
                if (!option || typeof option !== 'object') {
                    return 'Attachment';
                }
                const signedSuffix = option.is_digitally_signed ? ' [Digitally Signed]' : '';
                const uploadedAt = String(option.uploaded_at || '').trim();
                if (uploadedAt !== '') {
                    return String(option.file_name || 'Attachment') + signedSuffix + ' (' + uploadedAt + ')';
                }
                return String(option.file_name || 'Attachment') + signedSuffix;
            }

            function normalizeCanvasSelectionValue(value) {
                if (value === CANVAS_BLANK_VALUE) {
                    return CANVAS_BLANK_VALUE;
                }
                if (value === '') {
                    return '';
                }
                const normalized = String(value || '').trim();
                if (normalized === '') {
                    return CANVAS_BLANK_VALUE;
                }
                return normalized === CANVAS_BLANK_VALUE ? CANVAS_BLANK_VALUE : normalized;
            }

            function populateCanvasImageSelect(imageOptions, selectedValue) {
                if (!canvasImageSelect) {
                    return;
                }
                canvasImageSelect.innerHTML = '';

                const blankOption = document.createElement('option');
                blankOption.value = CANVAS_BLANK_VALUE;
                blankOption.textContent = 'Blank canvas';
                canvasImageSelect.appendChild(blankOption);

                const autoOption = document.createElement('option');
                autoOption.value = '';
                autoOption.textContent = 'Auto-select latest image';
                canvasImageSelect.appendChild(autoOption);

                const options = Array.isArray(imageOptions) ? imageOptions : [];
                options.forEach(function (option) {
                    const element = document.createElement('option');
                    element.value = String(option.id || '');
                    element.textContent = buildCanvasImageOptionLabel(option);
                    canvasImageSelect.appendChild(element);
                });

                canvasImageSelect.value = normalizeCanvasSelectionValue(selectedValue);
            }

            function applySelectedCanvasValue(selectionValue, shouldFlashSelection) {
                const selectedValue = normalizeCanvasSelectionValue(selectionValue);
                const options = Array.isArray(state.canvasImageOptions) ? state.canvasImageOptions : [];
                const autoPreference = String(state.autoCanvasPreference || 'blank').toLowerCase();

                if (selectedValue === CANVAS_BLANK_VALUE) {
                    applyCanvasImage('', 'Blank paper canvas selected.');
                    if (shouldFlashSelection) {
                        flash('Blank canvas selected.');
                    }
                    return;
                }

                if (selectedValue === '') {
                    const autoOption = selectAutoCanvasImageOption(options, autoPreference);
                    if (autoOption) {
                        applyCanvasImage(autoOption.file_url, '');
                        if (shouldFlashSelection) {
                            flash(autoPreference === 'signed'
                                ? 'Auto-selected latest digitally signed image canvas.'
                                : 'Auto-selected latest image canvas.');
                        }
                        return;
                    }
                    applyCanvasImage('', 'No image attachment found for this workflow yet.');
                    if (shouldFlashSelection) {
                        flash('No image canvas found. Using blank paper.');
                    }
                    return;
                }

                const selectedOption = options.find(function (option) {
                    return String(option.id || '') === selectedValue;
                });
                if (!selectedOption) {
                    applyCanvasImage('', 'Selected image is unavailable. Showing blank paper.');
                    if (shouldFlashSelection) {
                        flash('Selected canvas image is unavailable.');
                    }
                    return;
                }
                applyCanvasImage(String(selectedOption.file_url || ''), '');
                if (shouldFlashSelection) {
                    flash('Selected canvas image applied.');
                }
            }

            async function loadWorkflowImageCanvas() {
                if (!stampCanvasImage && !stampCanvasEmpty) {
                    return;
                }
                if (String(stampTrackingId || '').trim() === '') {
                    state.canvasImageOptions = [];
                    state.autoCanvasPreference = 'blank';
                    state.canvasSelectionValue = CANVAS_BLANK_VALUE;
                    populateCanvasImageSelect([], state.canvasSelectionValue);
                    applyCanvasImage('', 'No tracking ID provided.');
                    return;
                }

                try {
                    const requestUrl = new URL(String(documentDetailsPath || ''), window.location.origin);
                    requestUrl.searchParams.set('tracking_id', String(stampTrackingId || '').trim());
                    requestUrl.searchParams.set('t', String(Date.now()));
                    const response = await fetch(requestUrl.toString(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json().catch(function () {
                        return { ok: false, message: 'Unexpected server response.' };
                    });
                    if (!response.ok || !payload || payload.ok !== true) {
                        throw new Error(payload && payload.message ? payload.message : 'Unable to load workflow image canvas.');
                    }
                    const imageOptions = extractCanvasImageOptions(payload.attachments || []);
                    state.canvasImageOptions = imageOptions;
                    const hasSignedWorkflowCanvas = detectSignedWorkflowCanvas(imageOptions);
                    const isAdminRecordRole = sessionRoleKey === 'RECORDS_UNIT'
                        || sessionRoleKey === 'CENRO_ADMIN_RECORD'
                        || sessionRoleKey === 'PENRO_ADMIN_RECORD'
                        || sessionRoleKey === 'PAMO_ADMIN';
                    state.autoCanvasPreference = (hasSignedWorkflowCanvas && isAdminRecordRole) ? 'signed' : 'blank';
                    state.canvasSelectionValue = state.autoCanvasPreference === 'signed' ? '' : CANVAS_BLANK_VALUE;
                    populateCanvasImageSelect(imageOptions, state.canvasSelectionValue);
                    applySelectedCanvasValue(state.canvasSelectionValue, false);
                    if (state.autoCanvasPreference === 'signed') {
                        flash('Signed workflow detected. Latest digitally signed image canvas auto-selected.');
                    } else {
                        flash('Incoming workflow detected. Canvas defaults to blank.');
                    }
                } catch (error) {
                    applyCanvasImage('', 'Unable to load workflow image canvas right now.');
                    state.canvasImageOptions = [];
                    state.autoCanvasPreference = 'blank';
                    state.canvasSelectionValue = CANVAS_BLANK_VALUE;
                    populateCanvasImageSelect([], state.canvasSelectionValue);
                }
            }

            function pad2(value) {
                return String(value).padStart(2, '0');
            }

            function syncRealtimeDateTime() {
                const now = new Date();
                const dateValue = now.getFullYear() + '-' + pad2(now.getMonth() + 1) + '-' + pad2(now.getDate());
                state.currentDateIso = dateValue;
                syncStampText();
            }

            function formatDateLabel(value) {
                if (!value) {
                    return '-';
                }
                const dt = new Date(value + 'T00:00:00');
                if (Number.isNaN(dt.getTime())) {
                    return value;
                }
                const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                return pad2(dt.getDate()) + ' ' + monthNames[dt.getMonth()] + ' ' + dt.getFullYear();
            }

            function syncStampText() {
                const typeValue = String(stampTypeSelect.value || 'received').toUpperCase();
                stampMainLine.textContent = typeValue;
                stampDateValue.textContent = state.currentDateIso !== '' ? formatDateLabel(state.currentDateIso) : '-';
                stampReceivedByValue.textContent = String(state.receivedBy || '').trim() || 'Fullname';
                stampSignText.textContent = String(stampSignInput.value || '').trim();
                const hasImage = state.signatureDataUrl !== '';
                stampSignaturePreview.style.display = hasImage ? 'block' : 'none';
                stampSignText.style.display = hasImage ? 'none' : 'inline';
            }

            function syncGrid() {
                const safeGrid = clamp(parseInt(String(gridSizeInput.value || state.gridSize), 10) || state.gridSize, 8, 80);
                state.gridSize = safeGrid;
                gridSizeInput.value = String(safeGrid);
                stage.style.setProperty('--grid-size', safeGrid + 'px');
            }

            function updateBlockSize() {
                state.blockWidth = clamp(state.blockWidth, 220, 560);
                state.blockHeight = clamp(state.blockHeight, 120, 720);
                block.style.width = state.blockWidth + 'px';
                block.style.height = state.blockHeight + 'px';
                const scale = clamp(
                    Math.min(state.blockWidth / 360, state.blockHeight / 260),
                    0.5,
                    2.2
                );
                block.style.setProperty('--fs-scale', String(scale));
                if (currentSizeLabel) {
                    currentSizeLabel.textContent = String(state.blockWidth) + ' x ' + String(state.blockHeight);
                }
            }

            function setMode(nextMode) {
                state.mode = nextMode === 'free' ? 'free' : 'grid';
                stage.classList.toggle('grid-mode', state.mode === 'grid');
                modeFreeBtn.classList.toggle('is-active', state.mode === 'free');
                modeGridBtn.classList.toggle('is-active', state.mode === 'grid');
                if (currentModeLabel) {
                    currentModeLabel.textContent = state.mode === 'grid' ? 'Grid-Snap' : 'Free-Drag';
                }
            }

            function snap(value) {
                if (state.mode !== 'grid') {
                    return value;
                }
                return Math.round(value / state.gridSize) * state.gridSize;
            }

            function getMaxCoords() {
                const stageWidth = stage.clientWidth;
                const stageHeight = stage.clientHeight;
                return {
                    maxX: Math.max(0, stageWidth - state.blockWidth),
                    maxY: Math.max(0, stageHeight - state.blockHeight),
                };
            }

            function setPosition(x, y) {
                const limits = getMaxCoords();
                state.x = clamp(snap(x), 0, limits.maxX);
                state.y = clamp(snap(y), 0, limits.maxY);
                block.style.transform = 'translate(' + state.x + 'px, ' + state.y + 'px)';

                if (currentXLabel) {
                    currentXLabel.textContent = limits.maxX <= 0 ? '0.00' : ((state.x / limits.maxX) * 100).toFixed(2);
                }
                if (currentYLabel) {
                    currentYLabel.textContent = limits.maxY <= 0 ? '0.00' : ((state.y / limits.maxY) * 100).toFixed(2);
                }
            }

            function centerStamp() {
                const limits = getMaxCoords();
                setPosition(limits.maxX / 2, limits.maxY / 2);
                flash('Stamp centered.');
            }

            function applyPaperSize(value) {
                const key = Object.prototype.hasOwnProperty.call(paperSizes, value) ? value : 'A4';
                const size = paperSizes[key];
                stage.style.width = size.width + 'px';
                stage.style.height = size.height + 'px';
                if (paperLabel) {
                    paperLabel.textContent = size.label;
                }
                paperSizeSelect.value = key;
                window.requestAnimationFrame(function () {
                    centerStamp();
                });
            }

            function beginDrag(pointerEvent) {
                if (state.resizing) {
                    return;
                }
                const rect = stage.getBoundingClientRect();
                state.stageRect = rect;
                state.dragging = true;
                state.activePointerId = pointerEvent.pointerId;
                state.pointerOffsetX = pointerEvent.clientX - rect.left - state.x;
                state.pointerOffsetY = pointerEvent.clientY - rect.top - state.y;
                block.setPointerCapture(pointerEvent.pointerId);
            }

            function moveDrag(pointerEvent) {
                if (!state.dragging || !state.stageRect || pointerEvent.pointerId !== state.activePointerId) {
                    return;
                }
                const nextX = pointerEvent.clientX - state.stageRect.left - state.pointerOffsetX;
                const nextY = pointerEvent.clientY - state.stageRect.top - state.pointerOffsetY;
                setPosition(nextX, nextY);
            }

            function endDrag(pointerEvent) {
                if (!state.dragging || pointerEvent.pointerId !== state.activePointerId) {
                    return;
                }
                state.dragging = false;
                state.activePointerId = null;
                if (pointerEvent && typeof pointerEvent.pointerId === 'number') {
                    try {
                        block.releasePointerCapture(pointerEvent.pointerId);
                    } catch (error) {
                        // No-op.
                    }
                }
            }

            function beginResize(pointerEvent, handle) {
                if (state.dragging) {
                    return;
                }
                const rect = stage.getBoundingClientRect();
                state.stageRect = rect;
                state.resizing = true;
                state.resizeHandle = handle;
                state.activePointerId = pointerEvent.pointerId;
                state.resizeStartX = pointerEvent.clientX;
                state.resizeStartY = pointerEvent.clientY;
                state.resizeStartWidth = state.blockWidth;
                state.resizeStartHeight = state.blockHeight;
                state.resizeStartLeft = state.x;
                state.resizeStartTop = state.y;
            }

            function moveResize(pointerEvent) {
                if (!state.resizing || !state.stageRect || pointerEvent.pointerId !== state.activePointerId) {
                    return;
                }

                const dx = pointerEvent.clientX - state.resizeStartX;
                const dy = pointerEvent.clientY - state.resizeStartY;
                let nextWidth = state.resizeStartWidth;
                let nextHeight = state.resizeStartHeight;
                let nextLeft = state.resizeStartLeft;
                let nextTop = state.resizeStartTop;
                const handle = state.resizeHandle;

                if (handle.indexOf('e') !== -1) {
                    nextWidth = state.resizeStartWidth + dx;
                }
                if (handle.indexOf('s') !== -1) {
                    nextHeight = state.resizeStartHeight + dy;
                }
                if (handle.indexOf('w') !== -1) {
                    nextWidth = state.resizeStartWidth - dx;
                    nextLeft = state.resizeStartLeft + dx;
                }
                if (handle.indexOf('n') !== -1) {
                    nextHeight = state.resizeStartHeight - dy;
                    nextTop = state.resizeStartTop + dy;
                }

                nextWidth = clamp(nextWidth, 220, 560);
                nextHeight = clamp(nextHeight, 120, 720);

                if (handle.indexOf('w') !== -1) {
                    nextLeft = state.resizeStartLeft + (state.resizeStartWidth - nextWidth);
                }
                if (handle.indexOf('n') !== -1) {
                    nextTop = state.resizeStartTop + (state.resizeStartHeight - nextHeight);
                }

                if (state.mode === 'grid') {
                    let left = nextLeft;
                    let top = nextTop;
                    let right = left + nextWidth;
                    let bottom = top + nextHeight;
                    if (handle.indexOf('w') !== -1) {
                        left = snap(left);
                    }
                    if (handle.indexOf('e') !== -1) {
                        right = snap(right);
                    }
                    if (handle.indexOf('n') !== -1) {
                        top = snap(top);
                    }
                    if (handle.indexOf('s') !== -1) {
                        bottom = snap(bottom);
                    }
                    nextWidth = clamp(right - left, 220, 560);
                    nextHeight = clamp(bottom - top, 120, 720);
                    nextLeft = left;
                    nextTop = top;
                }

                const maxLeft = Math.max(0, stage.clientWidth - nextWidth);
                const maxTop = Math.max(0, stage.clientHeight - nextHeight);
                nextLeft = clamp(nextLeft, 0, maxLeft);
                nextTop = clamp(nextTop, 0, maxTop);

                state.blockWidth = nextWidth;
                state.blockHeight = nextHeight;
                updateBlockSize();
                setPosition(nextLeft, nextTop);
            }

            function endResize(pointerEvent) {
                if (!state.resizing || pointerEvent.pointerId !== state.activePointerId) {
                    return;
                }
                state.resizing = false;
                state.resizeHandle = '';
                state.activePointerId = null;
            }

            function onSignatureFilePicked(file) {
                if (!file) {
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (event) {
                    state.signatureDataUrl = String(event.target && event.target.result ? event.target.result : '');
                    stampSignaturePreview.src = state.signatureDataUrl;
                    syncStampText();
                };
                reader.readAsDataURL(file);
            }

            stampTypeSelect.addEventListener('change', function () {
                syncStampText();
            });

            stampSignInput.addEventListener('input', syncStampText);

            modeFreeBtn.addEventListener('click', function () {
                setMode('free');
            });

            modeGridBtn.addEventListener('click', function () {
                setMode('grid');
            });

            gridSizeInput.addEventListener('change', function () {
                syncGrid();
                setPosition(state.x, state.y);
            });

            paperSizeSelect.addEventListener('change', function () {
                applyPaperSize(String(paperSizeSelect.value || 'A4'));
            });

            if (canvasImageSelect) {
                canvasImageSelect.addEventListener('change', function () {
                    state.canvasSelectionValue = normalizeCanvasSelectionValue(canvasImageSelect.value);
                    applySelectedCanvasValue(state.canvasSelectionValue, true);
                });
            }

            printSoftCopyBtn.addEventListener('click', function () {
                window.print();
            });

            resetStampPositionBtn.addEventListener('click', function () {
                centerStamp();
            });

            stampSignatureImageInput.addEventListener('change', function () {
                const file = stampSignatureImageInput.files && stampSignatureImageInput.files[0]
                    ? stampSignatureImageInput.files[0]
                    : null;
                onSignatureFilePicked(file);
            });

            removeSignatureBtn.addEventListener('click', function () {
                state.signatureDataUrl = '';
                stampSignaturePreview.removeAttribute('src');
                stampSignatureImageInput.value = '';
                syncStampText();
            });

            block.addEventListener('pointerdown', function (event) {
                if (event.target && event.target.closest('.resize-handle')) {
                    return;
                }
                event.preventDefault();
                beginDrag(event);
            });

            block.addEventListener('pointermove', function (event) {
                if (!state.dragging) {
                    return;
                }
                event.preventDefault();
                moveDrag(event);
            });

            block.addEventListener('pointerup', function (event) {
                endDrag(event);
            });

            block.addEventListener('pointercancel', function (event) {
                endDrag(event);
            });

            resizeHandles.forEach(function (handleNode) {
                handleNode.addEventListener('pointerdown', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    const handle = String(handleNode.dataset.handle || '').toLowerCase();
                    beginResize(event, handle);
                    try {
                        handleNode.setPointerCapture(event.pointerId);
                    } catch (error) {
                        // No-op.
                    }
                });
                handleNode.addEventListener('pointermove', function (event) {
                    moveResize(event);
                });
                handleNode.addEventListener('pointerup', function (event) {
                    endResize(event);
                    try {
                        handleNode.releasePointerCapture(event.pointerId);
                    } catch (error) {
                        // No-op.
                    }
                });
                handleNode.addEventListener('pointercancel', function (event) {
                    endResize(event);
                    try {
                        handleNode.releasePointerCapture(event.pointerId);
                    } catch (error) {
                        // No-op.
                    }
                });
            });

            syncRealtimeDateTime();
            syncGrid();
            setMode('grid');
            updateBlockSize();
            syncStampText();
            applyPaperSize('A4');
            loadWorkflowImageCanvas();
            window.setInterval(syncRealtimeDateTime, 1000);

            <?php if ($isAutoPrint): ?>
            window.setTimeout(function () {
                window.print();
            }, 250);
            <?php endif; ?>
        })();
    </script>
</body>
</html>
