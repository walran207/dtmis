<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

$sessionRoleName = (string)($_SESSION['role_name'] ?? '');
$roleKey = app_normalize_role_key($sessionRoleName);
if (!in_array($roleKey, ['ORED', 'CENRO_OFFICER'], true)) {
    http_response_code(403);
    echo 'Digital signature workspace is available for ORED and CENRO Officer only.';
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$isEmbedded = ((string)($_GET['embedded'] ?? '') === '1');
$trackingId = strtoupper(trim((string)($_GET['tracking_id'] ?? '')));
$documentId = (int)($_GET['document_id'] ?? 0);
$returnToRaw = trim((string)($_GET['return_to'] ?? 'rd-action-desk.php'));
$returnTo = basename($returnToRaw);
if ($returnTo === '' || !preg_match('/^[A-Za-z0-9._-]+\.php$/', $returnTo)) {
    $returnTo = 'rd-action-desk.php';
}
$roleFolder = app_role_folder_from_role($sessionRoleName) ?? 'ORED';
$returnUrl = app_url($roleFolder . '/' . $returnTo);
$returnButtonLabel = $roleKey === 'CENRO_OFFICER'
    ? 'Back to CENRO OFFICER Action Desk'
    : 'Back to RD Action Desk';
$apiUrl = app_url('actions/digital-signature-profile.php');
$documentDetailsUrl = app_url('actions/document-details.php');
$documentActionUrl = app_url('actions/document-action.php');
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$offlinePolicy = app_offline_policy_for_role((string)($_SESSION['role_name'] ?? ''));
$offlineSyncLogUrl = app_url('actions/offline-sync-log.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signature Workspace</title>
    <script>
        window.__EDATS_OFFLINE_POLICY = <?php echo json_encode($offlinePolicy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.__EDATS_OFFLINE_SYNC_LOG_URL = <?php echo json_encode($offlineSyncLogUrl); ?>;
    </script>
    <script src="<?php echo e(app_url('assets/js/offline-read-cache.js')); ?>"></script>
    <script src="<?php echo e(app_url('assets/js/offline-outbox.js')); ?>"></script>
    <style>
        :root {
            --bg: #eff4fa;
            --card: #ffffff;
            --line: #cedaea;
            --text: #23374a;
            --muted: #60758c;
            --accent: #2f7de1;
            --accent-soft: #ecf4ff;
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

        .panel {
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
            border: 1px solid #c3d3e6;
            padding: 0 10px;
            font-size: 13px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            background: #fff;
            color: var(--text);
            font-weight: 700;
        }

        .btn.primary {
            border-color: #1f6ecb;
            background: #1f6ecb;
            color: #fff;
        }

        .btn.ghost {
            background: var(--accent-soft);
            color: #2a5f9a;
            border-color: #b9cde8;
        }

        .btn.is-disabled,
        .btn[aria-disabled="true"] {
            opacity: 0.55;
            pointer-events: none;
        }

        .hint {
            margin: 0;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 5px;
        }

        .mode {
            display: inline-flex;
            border: 1px solid #c3d3e6;
            border-radius: 10px;
            overflow: hidden;
            height: 36px;
        }

        .mode button {
            border: none;
            background: #fff;
            color: #355270;
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
            border: 1px solid #dae4ef;
            border-radius: 10px;
            background: #f8fbff;
            padding: 12px;
            display: flex;
            justify-content: center;
        }

        .signature-stage {
            position: relative;
            width: 595px;
            height: 842px;
            border: 1px solid #c4d3e5;
            background: #fff;
            overflow: hidden;
            user-select: none;
            touch-action: none;
            box-shadow: 0 8px 22px rgba(26, 48, 71, 0.14);
        }

        .doc-canvas-layer {
            position: absolute;
            inset: 0;
            z-index: 0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .doc-canvas-layer img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: none;
        }

        .doc-canvas-blank {
            font-size: 13px;
            color: #6b8198;
            text-align: center;
            padding: 8px 12px;
        }

        .grid-layer {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            opacity: 0;
            transition: opacity 0.15s ease;
            background-image:
                linear-gradient(to right, rgba(32, 78, 129, 0.15) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(32, 78, 129, 0.15) 1px, transparent 1px);
            background-size: var(--grid-size, 24px) var(--grid-size, 24px);
        }

        .signature-stage.grid-mode .grid-layer {
            opacity: 1;
        }

        .signature-block {
            position: absolute;
            z-index: 2;
            border: 1px solid rgba(34, 53, 72, 0.55);
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 6px 16px rgba(26, 48, 71, 0.22);
            border-radius: 6px;
            cursor: grab;
            touch-action: none;
            display: grid;
            grid-template-columns: 40% 1fr;
            gap: 8px;
            padding: 8px;
            overflow: hidden;
            min-width: 240px;
            min-height: 120px;
        }

        .signature-block:active {
            cursor: grabbing;
        }

        .signature-stage.canvas-signed .signature-block {
            display: none;
        }

        .signature-block.is-resizing {
            cursor: nwse-resize;
        }

        .sig-left {
            border: 1px dashed #b7c9df;
            background: rgba(242, 247, 255, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 4px;
        }

        .sig-left img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: none;
        }

        .sig-empty {
            font-size: 11px;
            color: #6583a1;
            text-align: center;
            line-height: 1.35;
            padding: 6px;
        }

        .sig-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
            color: #111;
        }

        .sig-line.title { font-size: 14px; }
        .sig-line.name { font-size: 17px; line-height: 1.2; }
        .sig-line.meta { font-size: 14px; }

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
            background: #2f7de1;
            border: 1px solid #ffffff;
            box-shadow: 0 1px 3px rgba(14, 28, 41, 0.45);
            border-radius: 2px;
            opacity: 0.94;
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

        .flash.warn { color: #a15a06; }
        .flash.ok { color: #0d7a45; }

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
            .signature-stage {
                border: none;
                box-shadow: none;
                margin: 0 auto;
            }
            .signature-block {
                border: none !important;
                border-radius: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
            }
            .sig-left {
                border: none !important;
                background: transparent !important;
            }
            .resize-handle {
                display: none !important;
            }
            .grid-layer {
                display: none !important;
            }
            .signature-stage.canvas-signed .signature-block { display: none !important; }
            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body class="<?php echo $isEmbedded ? 'is-embedded' : ''; ?>" data-role-key="<?php echo e($roleKey); ?>">
    <div class="shell<?php echo $isEmbedded ? ' embedded' : ''; ?>">
        <div class="panel">
            <div class="bar" style="margin-bottom:8px;">
                <div class="left">
                    <?php if ($trackingId !== ''): ?>
                    <span class="status-pill">Tracking ID: <strong><?php echo e($trackingId); ?></strong></span>
                    <?php endif; ?>
                    <?php if ($documentId > 0): ?>
                    <span class="status-pill">Document ID: <strong><?php echo e((string)$documentId); ?></strong></span>
                    <?php endif; ?>
                </div>
                <div class="right">
                    <button type="button" id="applySignBtn" class="btn primary" <?php echo $documentId <= 0 ? 'disabled' : ''; ?>>Apply Sign</button>
                    <button type="button" id="undoSignBtn" class="btn" <?php echo $documentId <= 0 ? 'disabled' : ''; ?>>Undo Sign</button>
                    <button type="button" id="applySignPrintBtn" class="btn" <?php echo $documentId <= 0 ? 'disabled' : ''; ?>>Apply Sign + Print Doc</button>
                    <button type="button" id="printCanvasBtn" class="btn ghost">Print Current Doc</button>
                    <a class="btn ghost" href="<?php echo e($returnUrl); ?>" target="_top"><?php echo e($returnButtonLabel); ?></a>
                </div>
            </div>

            <div class="bar">
                <div class="left">
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Paper Size</span>
                        <select class="select" id="paperSizeSelect">
                            <option value="A4">A4 (8.27 x 11.69)</option>
                            <option value="LETTER">Letter (8.5 x 11)</option>
                            <option value="CUSTOM_85X13">Custom (8.5 x 13)</option>
                        </select>
                    </label>
                    <div class="mode" aria-label="Signature placement mode">
                        <button type="button" id="modeFreeBtn">Free-Drag</button>
                        <button type="button" id="modeGridBtn">Grid-Snap</button>
                    </div>
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Grid (px)</span>
                        <input type="number" id="gridSizeInput" class="input" min="8" max="80" step="1" value="24" style="width:76px;">
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Block Width</span>
                        <input type="range" id="blockWidthRangeInput" min="20" max="90" step="1" value="42" style="width:120px;">
                        <input type="number" id="blockWidthNumberInput" class="input" min="20" max="90" step="1" value="42" style="width:72px;">
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Block Height</span>
                        <input type="range" id="blockHeightRangeInput" min="12" max="80" step="1" value="14" style="width:120px;">
                        <input type="number" id="blockHeightNumberInput" class="input" min="12" max="80" step="1" value="14" style="width:72px;">
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:6px;">
                        <span class="hint">Canvas Image</span>
                        <select class="select" id="canvasAttachmentSelect" style="min-width:220px;">
                            <option value="">Auto-select latest image</option>
                        </select>
                    </label>
                </div>
                <div class="right">
                    <input id="signatureImageInput" class="input" type="file" accept="image/png,image/jpeg,image/jpg,image/bmp,image/gif">
                    <button type="button" id="removeImageBtn" class="btn ghost">Remove Signature</button>
                    <button type="button" id="resetSignaturePositionBtn" class="btn">Reset Position</button>
                    <button type="button" id="saveProfileBtn" class="btn primary">Save Profile</button>
                </div>
            </div>

            <p class="hint" style="margin-top:6px;">Canvas uses the latest image attachment (prefers Division/Section prepared response). Drag block to move and drag edge/corner handles to resize width/height interactively.</p>

            <div class="stage-wrap">
                <div id="signatureStage" class="signature-stage">
                    <div id="documentCanvasLayer" class="doc-canvas-layer">
                        <img id="documentCanvasImage" alt="Document canvas preview">
                        <div id="blankCanvasLabel" class="doc-canvas-blank">Blank paper (no image attachment found)</div>
                    </div>
                    <div id="gridLayer" class="grid-layer"></div>
                    <div id="signatureBlock" class="signature-block">
                        <div class="sig-left">
                            <img id="signaturePreviewImage" alt="Digital signature preview">
                            <div id="signatureEmptyState" class="sig-empty">Upload<br>signature image</div>
                        </div>
                        <div class="sig-text">
                            <div class="sig-line title">Digitally signed by</div>
                            <div id="signerNameLabel" class="sig-line name">Regional Director</div>
                            <div id="signatureDateLabel" class="sig-line meta">Date: 2026.03.23</div>
                            <div id="signatureTimeLabel" class="sig-line meta">15:53:43 +08'00'</div>
                        </div>
                        <button type="button" class="resize-handle handle-n" data-resize-dir="n" aria-label="Resize top edge"></button>
                        <button type="button" class="resize-handle handle-s" data-resize-dir="s" aria-label="Resize bottom edge"></button>
                        <button type="button" class="resize-handle handle-e" data-resize-dir="e" aria-label="Resize right edge"></button>
                        <button type="button" class="resize-handle handle-w" data-resize-dir="w" aria-label="Resize left edge"></button>
                        <button type="button" class="resize-handle handle-ne" data-resize-dir="ne" aria-label="Resize top right corner"></button>
                        <button type="button" class="resize-handle handle-nw" data-resize-dir="nw" aria-label="Resize top left corner"></button>
                        <button type="button" class="resize-handle handle-se" data-resize-dir="se" aria-label="Resize bottom right corner"></button>
                        <button type="button" class="resize-handle handle-sw" data-resize-dir="sw" aria-label="Resize bottom left corner"></button>
                    </div>
                </div>
            </div>

            <div class="status-row">
                <span class="status-pill">Paper: <strong id="paperLabel">A4</strong></span>
                <span class="status-pill">Mode: <strong id="currentModeLabel">Grid-Snap</strong></span>
                <span class="status-pill">Width: <strong id="currentWidthLabel">42</strong>%</span>
                <span class="status-pill">Height: <strong id="currentHeightLabel">14.25</strong>%</span>
                <span class="status-pill">X: <strong id="currentXLabel">58.00</strong>%</span>
                <span class="status-pill">Y: <strong id="currentYLabel">74.00</strong>%</span>
                <p id="flashMessage" class="flash"></p>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const apiUrl = <?php echo json_encode($apiUrl); ?>;
            const documentDetailsUrl = <?php echo json_encode($documentDetailsUrl); ?>;
            const documentActionUrl = <?php echo json_encode($documentActionUrl); ?>;
            const csrfToken = <?php echo json_encode($csrfToken); ?>;
            const currentUserId = <?php echo json_encode($currentUserId); ?>;
            if (Number.isFinite(currentUserId) && currentUserId > 0) {
                window.__EDATS_CACHE_SCOPE = 'user:' + String(currentUserId);
            }
            const selectedTrackingId = <?php echo json_encode($trackingId); ?>;
            const selectedDocumentId = <?php echo json_encode($documentId); ?>;

            const stage = document.getElementById('signatureStage');
            const block = document.getElementById('signatureBlock');
            const gridLayer = document.getElementById('gridLayer');
            const documentCanvasImage = document.getElementById('documentCanvasImage');
            const blankCanvasLabel = document.getElementById('blankCanvasLabel');

            const modeFreeBtn = document.getElementById('modeFreeBtn');
            const modeGridBtn = document.getElementById('modeGridBtn');
            const paperSizeSelect = document.getElementById('paperSizeSelect');
            const gridSizeInput = document.getElementById('gridSizeInput');
            const blockWidthRangeInput = document.getElementById('blockWidthRangeInput');
            const blockWidthNumberInput = document.getElementById('blockWidthNumberInput');
            const blockHeightRangeInput = document.getElementById('blockHeightRangeInput');
            const blockHeightNumberInput = document.getElementById('blockHeightNumberInput');
            const canvasAttachmentSelect = document.getElementById('canvasAttachmentSelect');
            const signatureImageInput = document.getElementById('signatureImageInput');
            const removeImageBtn = document.getElementById('removeImageBtn');
            const resetPositionBtn = document.getElementById('resetSignaturePositionBtn');
            const saveProfileBtn = document.getElementById('saveProfileBtn');
            const applySignBtn = document.getElementById('applySignBtn');
            const undoSignBtn = document.getElementById('undoSignBtn');
            const applySignPrintBtn = document.getElementById('applySignPrintBtn');
            const printCanvasBtn = document.getElementById('printCanvasBtn');

            const signaturePreviewImage = document.getElementById('signaturePreviewImage');
            const signatureEmptyState = document.getElementById('signatureEmptyState');
            const signerNameLabel = document.getElementById('signerNameLabel');
            const signatureDateLabel = document.getElementById('signatureDateLabel');
            const signatureTimeLabel = document.getElementById('signatureTimeLabel');

            const paperLabel = document.getElementById('paperLabel');
            const currentModeLabel = document.getElementById('currentModeLabel');
            const currentWidthLabel = document.getElementById('currentWidthLabel');
            const currentHeightLabel = document.getElementById('currentHeightLabel');
            const currentXLabel = document.getElementById('currentXLabel');
            const currentYLabel = document.getElementById('currentYLabel');
            const flashMessage = document.getElementById('flashMessage');
            const resizeHandles = Array.from(block.querySelectorAll('[data-resize-dir]'));

            const paperPresets = {
                A4: { widthIn: 8.27, heightIn: 11.69, label: 'A4' },
                LETTER: { widthIn: 8.5, heightIn: 11, label: 'Letter' },
                CUSTOM_85X13: { widthIn: 8.5, heightIn: 13, label: 'Custom (8.5 x 13)' }
            };

            let mode = 'Grid-Snap';
            let paperSize = 'A4';
            let xPct = 58;
            let yPct = 74;
            let blockWidthPct = 42;
            let blockHeightPct = 14.25;
            let signerName = 'Regional Director';
            let pendingUploadFile = null;
            let removeSignatureImage = false;
            let serverSignatureImageUrl = '';
            let canvasImageOptions = [];
            let selectedCanvasAttachmentId = '';
            let activeCanvasIsDigitallySigned = false;

            let pointerActive = false;
            let pointerId = null;
            let dragOffsetX = 0;
            let dragOffsetY = 0;
            let resizeState = null;

            let dynamicPrintStyleTag = document.getElementById('dynamicPrintPaperStyle');
            if (!dynamicPrintStyleTag) {
                dynamicPrintStyleTag = document.createElement('style');
                dynamicPrintStyleTag.id = 'dynamicPrintPaperStyle';
                document.head.appendChild(dynamicPrintStyleTag);
            }

            function clamp(value, min, max) {
                return Math.min(max, Math.max(min, value));
            }

            function showFlash(message, type) {
                flashMessage.textContent = message || '';
                flashMessage.classList.remove('warn', 'ok');
                if (type === 'warn') {
                    flashMessage.classList.add('warn');
                } else if (type === 'ok') {
                    flashMessage.classList.add('ok');
                }
            }

            function applyPaperSize() {
                const selected = paperPresets[paperSize] || paperPresets.A4;
                const widthPx = Math.round(selected.widthIn * 96);
                const heightPx = Math.round(selected.heightIn * 96);
                stage.style.width = widthPx + 'px';
                stage.style.height = heightPx + 'px';
                if (dynamicPrintStyleTag) {
                    dynamicPrintStyleTag.textContent = '@media print { @page { size: '
                        + selected.widthIn + 'in ' + selected.heightIn + 'in; margin: 0; } }';
                }
                paperLabel.textContent = selected.label;
            }

            function applyModeUI() {
                modeFreeBtn.classList.toggle('is-active', mode === 'Free-Drag');
                modeGridBtn.classList.toggle('is-active', mode === 'Grid-Snap');
                stage.classList.toggle('grid-mode', mode === 'Grid-Snap');
                currentModeLabel.textContent = mode;
            }

            function getBlockWidthPx() {
                const minWidth = 240;
                const maxWidth = Math.max(minWidth, stage.clientWidth - 24);
                return clamp(Math.round(stage.clientWidth * (blockWidthPct / 100)), minWidth, maxWidth);
            }

            function getBlockHeightPx() {
                const minHeight = 120;
                const maxHeight = Math.max(minHeight, stage.clientHeight - 24);
                return clamp(Math.round(stage.clientHeight * (blockHeightPct / 100)), minHeight, maxHeight);
            }

            function applyBlockSize() {
                const blockWidthPx = getBlockWidthPx();
                const blockHeightPx = getBlockHeightPx();
                block.style.width = blockWidthPx + 'px';
                block.style.height = blockHeightPx + 'px';
            }

            function stageAvailableWidth() {
                return Math.max(0, stage.clientWidth - block.offsetWidth);
            }

            function stageAvailableHeight() {
                return Math.max(0, stage.clientHeight - block.offsetHeight);
            }

            function syncPositionFromPercent() {
                const left = Math.round(stageAvailableWidth() * (xPct / 100));
                const top = Math.round(stageAvailableHeight() * (yPct / 100));
                block.style.left = left + 'px';
                block.style.top = top + 'px';
            }

            function syncPercentFromPixels(left, top) {
                const width = stageAvailableWidth();
                const height = stageAvailableHeight();
                xPct = width <= 0 ? 0 : clamp((left / width) * 100, 0, 100);
                yPct = height <= 0 ? 0 : clamp((top / height) * 100, 0, 100);
            }

            function syncSizePercentFromPixels(widthPx, heightPx) {
                const stageWidth = Math.max(1, stage.clientWidth);
                const stageHeight = Math.max(1, stage.clientHeight);
                blockWidthPct = clamp((widthPx / stageWidth) * 100, 20, 90);
                blockHeightPct = clamp((heightPx / stageHeight) * 100, 12, 80);
            }

            function snap(value, step) {
                return Math.round(value / step) * step;
            }

            function updateStatus() {
                currentWidthLabel.textContent = blockWidthPct.toFixed(2);
                currentHeightLabel.textContent = blockHeightPct.toFixed(2);
                currentXLabel.textContent = xPct.toFixed(2);
                currentYLabel.textContent = yPct.toFixed(2);
                blockWidthRangeInput.value = Math.round(blockWidthPct).toString();
                blockWidthNumberInput.value = Math.round(blockWidthPct).toString();
                blockHeightRangeInput.value = Math.round(blockHeightPct).toString();
                blockHeightNumberInput.value = Math.round(blockHeightPct).toString();
            }

            function getCanvasImageMetrics() {
                const stageWidth = Math.max(1, stage.clientWidth);
                const stageHeight = Math.max(1, stage.clientHeight);
                const hasImage = !!(documentCanvasImage && documentCanvasImage.getAttribute('src'));
                const naturalWidth = hasImage ? Number(documentCanvasImage.naturalWidth || 0) : 0;
                const naturalHeight = hasImage ? Number(documentCanvasImage.naturalHeight || 0) : 0;

                if (!(hasImage && naturalWidth > 0 && naturalHeight > 0)) {
                    return {
                        hasImage: false,
                        stageWidth: stageWidth,
                        stageHeight: stageHeight,
                        displayWidth: stageWidth,
                        displayHeight: stageHeight,
                        offsetX: 0,
                        offsetY: 0,
                        naturalWidth: stageWidth,
                        naturalHeight: stageHeight
                    };
                }

                const imageAspect = naturalWidth / naturalHeight;
                const stageAspect = stageWidth / stageHeight;
                let displayWidth = stageWidth;
                let displayHeight = stageHeight;
                let offsetX = 0;
                let offsetY = 0;

                if (imageAspect > stageAspect) {
                    displayWidth = stageWidth;
                    displayHeight = stageWidth / imageAspect;
                    offsetY = (stageHeight - displayHeight) / 2;
                } else {
                    displayHeight = stageHeight;
                    displayWidth = stageHeight * imageAspect;
                    offsetX = (stageWidth - displayWidth) / 2;
                }

                return {
                    hasImage: true,
                    stageWidth: stageWidth,
                    stageHeight: stageHeight,
                    displayWidth: Math.max(1, displayWidth),
                    displayHeight: Math.max(1, displayHeight),
                    offsetX: offsetX,
                    offsetY: offsetY,
                    naturalWidth: naturalWidth,
                    naturalHeight: naturalHeight
                };
            }

            function buildServerSignPlacementPayload() {
                const metrics = getCanvasImageMetrics();
                const stageWidth = Math.max(1, stage.clientWidth);
                const stageHeight = Math.max(1, stage.clientHeight);
                const leftStage = Number(block.offsetLeft || 0);
                const topStage = Number(block.offsetTop || 0);
                const widthStage = Math.max(1, Number(block.offsetWidth || 1));
                const heightStage = Math.max(1, Number(block.offsetHeight || 1));

                if (!metrics.hasImage) {
                    return {
                        mode: String(mode || 'Grid-Snap'),
                        paper_size: String(paperSize || 'A4'),
                        x_pct: clamp((leftStage / Math.max(1, stageWidth - widthStage)) * 100, 0, 100),
                        y_pct: clamp((topStage / Math.max(1, stageHeight - heightStage)) * 100, 0, 100),
                        block_width_pct: clamp((widthStage / stageWidth) * 100, 20, 90),
                        block_height_pct: clamp((heightStage / stageHeight) * 100, 12, 80)
                    };
                }

                const relLeft = clamp((leftStage - metrics.offsetX) / metrics.displayWidth, 0, 1);
                const relTop = clamp((topStage - metrics.offsetY) / metrics.displayHeight, 0, 1);
                const relWidth = clamp(widthStage / metrics.displayWidth, 0.01, 1);
                const relHeight = clamp(heightStage / metrics.displayHeight, 0.01, 1);

                const sourceW = Math.max(1, metrics.naturalWidth);
                const sourceH = Math.max(1, metrics.naturalHeight);

                const requestedWidthPct = clamp(relWidth * 100, 20, 90);
                const requestedHeightPct = clamp(relHeight * 100, 12, 80);

                const padding = Math.max(Math.floor(sourceW * 0.02), 20);

                let blockW = Math.round(sourceW * (requestedWidthPct / 100));
                let blockH = Math.round(sourceH * (requestedHeightPct / 100));
                blockW = clamp(blockW, 240, Math.max(240, sourceW - (padding * 2)));
                blockH = clamp(blockH, 120, Math.max(120, sourceH - (padding * 2)));

                const desiredX = clamp(relLeft * sourceW, 0, sourceW);
                const desiredY = clamp(relTop * sourceH, 0, sourceH);
                const clampedX = clamp(desiredX, padding, Math.max(padding, sourceW - padding - blockW));
                const clampedY = clamp(desiredY, padding, Math.max(padding, sourceH - padding - blockH));
                const availableX = Math.max(0, sourceW - blockW - (padding * 2));
                const availableY = Math.max(0, sourceH - blockH - (padding * 2));

                const xPercent = availableX <= 0 ? 0 : clamp(((clampedX - padding) / availableX) * 100, 0, 100);
                const yPercent = availableY <= 0 ? 0 : clamp(((clampedY - padding) / availableY) * 100, 0, 100);

                return {
                    mode: String(mode || 'Grid-Snap'),
                    paper_size: String(paperSize || 'A4'),
                    x_pct: xPercent,
                    y_pct: yPercent,
                    block_width_pct: requestedWidthPct,
                    block_height_pct: requestedHeightPct
                };
            }

            function applySignatureImage(url) {
                if (url && typeof url === 'string') {
                    signaturePreviewImage.src = url;
                    signaturePreviewImage.style.display = 'block';
                    signatureEmptyState.style.display = 'none';
                } else {
                    signaturePreviewImage.removeAttribute('src');
                    signaturePreviewImage.style.display = 'none';
                    signatureEmptyState.style.display = 'block';
                }
            }

            function normalizeRoleKey(value) {
                return String(value || '')
                    .trim()
                    .toUpperCase()
                    .replace(/[^A-Z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
            }

            function isDigitallySignedAttachmentName(fileName) {
                return String(fileName || '').toLowerCase().indexOf('[digitally signed]') === 0;
            }

            function normalizeAttachmentNameForMatch(fileName) {
                return String(fileName || '')
                    .trim()
                    .toLowerCase()
                    .replace(/\s+/g, ' ');
            }

            function setCanvasSignedState(isSigned) {
                activeCanvasIsDigitallySigned = !!isSigned;
                if (stage) {
                    stage.classList.toggle('canvas-signed', activeCanvasIsDigitallySigned);
                }
            }

            function applyCanvasImage(url, isDigitallySigned) {
                if (url && typeof url === 'string') {
                    documentCanvasImage.src = url;
                    documentCanvasImage.style.display = 'block';
                    blankCanvasLabel.style.display = 'none';
                } else {
                    documentCanvasImage.removeAttribute('src');
                    documentCanvasImage.style.display = 'none';
                    blankCanvasLabel.style.display = 'block';
                }
                setCanvasSignedState(!!isDigitallySigned);
            }

            function populateCanvasAttachmentSelect(options, selectedId) {
                if (!canvasAttachmentSelect) {
                    return;
                }
                canvasAttachmentSelect.innerHTML = '';
                const autoOption = document.createElement('option');
                autoOption.value = '';
                autoOption.textContent = 'Auto-select latest image';
                canvasAttachmentSelect.appendChild(autoOption);

                options.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = String(item.id || '');
                    option.textContent = String(item.file_name || ('Attachment ' + String(item.id || '')));
                    canvasAttachmentSelect.appendChild(option);
                });

                canvasAttachmentSelect.value = selectedId || '';
            }

            function selectPreferredCanvasAttachment(options, preferSigned) {
                if (!Array.isArray(options) || options.length === 0) {
                    return null;
                }

                const signed = options.filter(function (item) {
                    return String(item.file_name || '').toLowerCase().indexOf('[digitally signed]') !== -1;
                });
                const nonSigned = options.filter(function (item) {
                    return String(item.file_name || '').toLowerCase().indexOf('[digitally signed]') === -1;
                });

                const preparedNonSigned = nonSigned.filter(function (item) {
                    return !!item.is_prepared_response;
                });
                const preparedSigned = signed.filter(function (item) {
                    return !!item.is_prepared_response;
                });

                if (preferSigned && preparedSigned.length > 0) {
                    return preparedSigned[0];
                }
                if (preferSigned && signed.length > 0) {
                    return signed[0];
                }
                if (preparedNonSigned.length > 0) {
                    return preparedNonSigned[0];
                }

                const preferredRoles = ['DIVISION_CHIEF', 'SECTION_STAFF'];
                const rolePreferred = nonSigned.filter(function (item) {
                    return preferredRoles.indexOf(normalizeRoleKey(item.uploaded_by_role_key || item.uploaded_by_role || '')) !== -1;
                });
                if (rolePreferred.length > 0) {
                    return rolePreferred[0];
                }
                if (nonSigned.length > 0) {
                    return nonSigned[0];
                }
                if (signed.length > 0) {
                    return signed[0];
                }
                return options[0];
            }

            async function loadDocumentCanvas(preferSigned) {
                if ((!Number.isFinite(selectedDocumentId) || selectedDocumentId <= 0) && selectedTrackingId === '') {
                    canvasImageOptions = [];
                    selectedCanvasAttachmentId = '';
                    populateCanvasAttachmentSelect([], '');
                    applyCanvasImage('', false);
                    return;
                }

                try {
                    const requestUrl = new URL(String(documentDetailsUrl || ''), window.location.origin);
                    if (Number.isFinite(selectedDocumentId) && selectedDocumentId > 0) {
                        requestUrl.searchParams.set('document_id', String(selectedDocumentId));
                    } else if (selectedTrackingId !== '') {
                        requestUrl.searchParams.set('tracking_id', selectedTrackingId);
                    }

                    const response = await fetch(requestUrl.toString(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload || !payload.ok) {
                        throw new Error(payload && payload.message ? payload.message : 'Unable to load document attachments.');
                    }

                    const attachments = Array.isArray(payload.attachments) ? payload.attachments : [];
                    const imageAttachments = attachments.filter(function (attachment) {
                        const previewType = String(attachment.preview_type || '').toLowerCase();
                        const fileUrl = String(attachment.file_url || '');
                        return previewType === 'image' && fileUrl !== '';
                    }).map(function (attachment) {
                        return {
                            id: String(attachment.id || ''),
                            file_name: String(attachment.file_name || 'Attachment'),
                            file_url: String(attachment.file_url || ''),
                            uploaded_by_role_key: String(attachment.uploaded_by_role_key || ''),
                            uploaded_by_role: String(attachment.uploaded_by_role || ''),
                            is_prepared_response: !!attachment.is_prepared_response,
                            is_digitally_signed: isDigitallySignedAttachmentName(attachment.file_name || '')
                        };
                    });

                    const preparedSourceNames = {};
                    imageAttachments.forEach(function (item) {
                        const rawName = String(item.file_name || '');
                        const normalizedName = normalizeAttachmentNameForMatch(rawName);
                        if (normalizedName.indexOf('[digitally signed]') !== -1) {
                            return;
                        }
                        if (!item.is_prepared_response) {
                            return;
                        }
                        preparedSourceNames[normalizedName] = true;
                    });

                    canvasImageOptions = imageAttachments.map(function (item) {
                        const rawName = String(item.file_name || '');
                        const normalizedRawName = normalizeAttachmentNameForMatch(rawName);
                        const signedPrefix = '[digitally signed] ';
                        const isSigned = normalizedRawName.indexOf(signedPrefix) === 0;
                        let signedSource = '';
                        if (isSigned) {
                            signedSource = normalizeAttachmentNameForMatch(rawName.substring(signedPrefix.length));
                        }
                        const uploaderRoleKey = normalizeRoleKey(item.uploaded_by_role_key || item.uploaded_by_role || '');
                        const isPrepared = !!item.is_prepared_response
                            || uploaderRoleKey === 'DIVISION_CHIEF'
                            || uploaderRoleKey === 'SECTION_STAFF'
                            || (signedSource !== '' && !!preparedSourceNames[signedSource])
                            || (isSigned && (uploaderRoleKey === 'ORED' || uploaderRoleKey === 'CENRO_OFFICER'));
                        return {
                            id: item.id,
                            file_name: item.file_name,
                            file_url: item.file_url,
                            uploaded_by_role_key: item.uploaded_by_role_key,
                            uploaded_by_role: item.uploaded_by_role,
                            is_prepared_response: isPrepared,
                            is_digitally_signed: !!item.is_digitally_signed
                        };
                    });

                    const hasPreparedImages = canvasImageOptions.some(function (item) {
                        return !!item.is_prepared_response;
                    });
                    if (hasPreparedImages) {
                        canvasImageOptions = canvasImageOptions.filter(function (item) {
                            return !!item.is_prepared_response;
                        });
                    } else {
                        canvasImageOptions = [];
                    }

                    const preferred = selectPreferredCanvasAttachment(canvasImageOptions, !!preferSigned);
                    selectedCanvasAttachmentId = preferred ? String(preferred.id || '') : '';
                    populateCanvasAttachmentSelect(canvasImageOptions, selectedCanvasAttachmentId);
                    applyCanvasImage(
                        preferred ? preferred.file_url : '',
                        preferred ? !!preferred.is_digitally_signed : false
                    );
                    if (hasPreparedImages) {
                        if (preferred && preferred.is_digitally_signed) {
                            showFlash('Digitally signed output loaded. Overlay editor is hidden to avoid duplicate stamp preview.', '');
                        } else {
                            showFlash('Prepared of response attachment loaded as signature canvas.', '');
                        }
                    } else {
                        showFlash('No prepared of response image attachment found. Canvas is blank paper.', 'warn');
                    }
                } catch (error) {
                    applyCanvasImage('', false);
                    showFlash(error && error.message ? error.message : 'Unable to load image attachment canvas.', 'warn');
                }
            }

            function refreshDateTimePreview() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hh = String(now.getHours()).padStart(2, '0');
                const mm = String(now.getMinutes()).padStart(2, '0');
                const ss = String(now.getSeconds()).padStart(2, '0');
                signatureDateLabel.textContent = 'Date: ' + year + '.' + month + '.' + day;
                signatureTimeLabel.textContent = hh + ':' + mm + ':' + ss + " +08'00'";
            }

            function applyLayout() {
                xPct = Number.isFinite(xPct) ? clamp(xPct, 0, 100) : 58;
                yPct = Number.isFinite(yPct) ? clamp(yPct, 0, 100) : 74;
                blockWidthPct = Number.isFinite(blockWidthPct) ? clamp(blockWidthPct, 20, 90) : 42;
                blockHeightPct = Number.isFinite(blockHeightPct) ? clamp(blockHeightPct, 12, 80) : 14.25;
                if (paperSizeSelect.value !== paperSize && paperPresets[paperSize]) {
                    paperSizeSelect.value = paperSize;
                }
                applyPaperSize();
                applyModeUI();
                gridLayer.style.setProperty('--grid-size', clamp(parseInt(gridSizeInput.value || '24', 10), 8, 80) + 'px');
                applyBlockSize();
                syncPositionFromPercent();
                updateStatus();
                signerNameLabel.textContent = signerName;
                refreshDateTimePreview();
            }

            function beginDrag(evt) {
                if (resizeState) {
                    return;
                }
                if (evt.target && evt.target.closest('[data-resize-dir]')) {
                    return;
                }
                if (evt.button !== undefined && evt.button !== 0) {
                    return;
                }
                evt.preventDefault();
                pointerActive = true;
                pointerId = Number.isFinite(evt.pointerId) ? evt.pointerId : null;
                const rect = block.getBoundingClientRect();
                dragOffsetX = evt.clientX - rect.left;
                dragOffsetY = evt.clientY - rect.top;
                if (typeof block.setPointerCapture === 'function') {
                    block.setPointerCapture(evt.pointerId);
                }
            }

            function moveDrag(evt) {
                if (!pointerActive) {
                    return;
                }
                if (pointerId !== null && Number.isFinite(evt.pointerId) && evt.pointerId !== pointerId) {
                    return;
                }
                const stageRect = stage.getBoundingClientRect();
                let left = evt.clientX - stageRect.left - dragOffsetX;
                let top = evt.clientY - stageRect.top - dragOffsetY;

                left = clamp(left, 0, stageAvailableWidth());
                top = clamp(top, 0, stageAvailableHeight());

                if (mode === 'Grid-Snap') {
                    const gridSize = clamp(parseInt(gridSizeInput.value || '24', 10), 8, 80);
                    left = clamp(snap(left, gridSize), 0, stageAvailableWidth());
                    top = clamp(snap(top, gridSize), 0, stageAvailableHeight());
                }

                block.style.left = Math.round(left) + 'px';
                block.style.top = Math.round(top) + 'px';
                syncPercentFromPixels(left, top);
                updateStatus();
            }

            function endDrag(evt) {
                if (!pointerActive) {
                    return;
                }
                if (pointerId !== null && Number.isFinite(evt.pointerId) && evt.pointerId !== pointerId) {
                    return;
                }
                pointerActive = false;
                pointerId = null;
                if (typeof block.releasePointerCapture === 'function') {
                    try {
                        block.releasePointerCapture(evt.pointerId);
                    } catch (error) {
                        // ignore
                    }
                }
            }

            function beginResize(evt) {
                if (evt.button !== undefined && evt.button !== 0) {
                    return;
                }
                const handle = evt.currentTarget;
                if (!handle) {
                    return;
                }
                const direction = String(handle.getAttribute('data-resize-dir') || '').toLowerCase();
                if (direction === '') {
                    return;
                }

                evt.preventDefault();
                evt.stopPropagation();
                pointerActive = false;
                pointerId = null;

                const stageRect = stage.getBoundingClientRect();
                const blockRect = block.getBoundingClientRect();
                const left = blockRect.left - stageRect.left;
                const top = blockRect.top - stageRect.top;

                resizeState = {
                    pointerId: Number.isFinite(evt.pointerId) ? evt.pointerId : null,
                    handle: handle,
                    direction: direction,
                    startPointerX: evt.clientX,
                    startPointerY: evt.clientY,
                    startLeft: left,
                    startTop: top,
                    startRight: left + blockRect.width,
                    startBottom: top + blockRect.height,
                    stageWidth: stage.clientWidth,
                    stageHeight: stage.clientHeight
                };
                block.classList.add('is-resizing');
                if (typeof handle.setPointerCapture === 'function' && Number.isFinite(evt.pointerId)) {
                    handle.setPointerCapture(evt.pointerId);
                }
            }

            function applyResizeEdgeConstraints(left, right, top, bottom, direction, stageWidth, stageHeight) {
                const minWidth = 240;
                const minHeight = 120;

                left = clamp(left, 0, stageWidth);
                right = clamp(right, 0, stageWidth);
                top = clamp(top, 0, stageHeight);
                bottom = clamp(bottom, 0, stageHeight);

                if (right - left < minWidth) {
                    if (direction.indexOf('w') !== -1 && direction.indexOf('e') === -1) {
                        left = right - minWidth;
                    } else {
                        right = left + minWidth;
                    }
                }
                if (bottom - top < minHeight) {
                    if (direction.indexOf('n') !== -1 && direction.indexOf('s') === -1) {
                        top = bottom - minHeight;
                    } else {
                        bottom = top + minHeight;
                    }
                }

                if (left < 0) {
                    left = 0;
                    right = Math.min(stageWidth, left + Math.max(minWidth, right - left));
                }
                if (top < 0) {
                    top = 0;
                    bottom = Math.min(stageHeight, top + Math.max(minHeight, bottom - top));
                }
                if (right > stageWidth) {
                    right = stageWidth;
                    left = Math.max(0, right - Math.max(minWidth, right - left));
                }
                if (bottom > stageHeight) {
                    bottom = stageHeight;
                    top = Math.max(0, bottom - Math.max(minHeight, bottom - top));
                }

                if (right - left < minWidth) {
                    right = clamp(left + minWidth, 0, stageWidth);
                    left = clamp(right - minWidth, 0, stageWidth);
                }
                if (bottom - top < minHeight) {
                    bottom = clamp(top + minHeight, 0, stageHeight);
                    top = clamp(bottom - minHeight, 0, stageHeight);
                }

                return { left: left, right: right, top: top, bottom: bottom };
            }

            function moveResize(evt) {
                if (!resizeState) {
                    return;
                }
                if (resizeState.pointerId !== null && Number.isFinite(evt.pointerId) && evt.pointerId !== resizeState.pointerId) {
                    return;
                }

                const direction = resizeState.direction;
                const deltaX = evt.clientX - resizeState.startPointerX;
                const deltaY = evt.clientY - resizeState.startPointerY;
                let left = resizeState.startLeft;
                let right = resizeState.startRight;
                let top = resizeState.startTop;
                let bottom = resizeState.startBottom;

                if (direction.indexOf('w') !== -1) {
                    left = resizeState.startLeft + deltaX;
                }
                if (direction.indexOf('e') !== -1) {
                    right = resizeState.startRight + deltaX;
                }
                if (direction.indexOf('n') !== -1) {
                    top = resizeState.startTop + deltaY;
                }
                if (direction.indexOf('s') !== -1) {
                    bottom = resizeState.startBottom + deltaY;
                }

                if (mode === 'Grid-Snap') {
                    const gridSize = clamp(parseInt(gridSizeInput.value || '24', 10), 8, 80);
                    left = snap(left, gridSize);
                    right = snap(right, gridSize);
                    top = snap(top, gridSize);
                    bottom = snap(bottom, gridSize);
                }

                const normalized = applyResizeEdgeConstraints(
                    left,
                    right,
                    top,
                    bottom,
                    direction,
                    resizeState.stageWidth,
                    resizeState.stageHeight
                );
                left = normalized.left;
                right = normalized.right;
                top = normalized.top;
                bottom = normalized.bottom;

                const width = Math.max(240, right - left);
                const height = Math.max(120, bottom - top);

                block.style.left = Math.round(left) + 'px';
                block.style.top = Math.round(top) + 'px';
                block.style.width = Math.round(width) + 'px';
                block.style.height = Math.round(height) + 'px';
                syncPercentFromPixels(left, top);
                syncSizePercentFromPixels(width, height);
                updateStatus();
            }

            function endResize(evt) {
                if (!resizeState) {
                    return;
                }
                if (resizeState.pointerId !== null && Number.isFinite(evt.pointerId) && evt.pointerId !== resizeState.pointerId) {
                    return;
                }

                const activeHandle = resizeState.handle;
                if (activeHandle && typeof activeHandle.releasePointerCapture === 'function' && Number.isFinite(evt.pointerId)) {
                    try {
                        activeHandle.releasePointerCapture(evt.pointerId);
                    } catch (error) {
                        // ignore
                    }
                }
                resizeState = null;
                block.classList.remove('is-resizing');
                updateStatus();
            }

            async function loadProfile() {
                try {
                    const response = await fetch(apiUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload || !payload.ok) {
                        throw new Error(payload && payload.message ? payload.message : 'Unable to load profile.');
                    }

                    const profile = payload.profile || {};
                    mode = profile.mode === 'Free-Drag' ? 'Free-Drag' : 'Grid-Snap';
                    paperSize = paperPresets[profile.paper_size] ? profile.paper_size : 'A4';
                    xPct = Number(profile.x_pct);
                    yPct = Number(profile.y_pct);
                    blockWidthPct = Number(profile.block_width_pct);
                    blockHeightPct = Number(profile.block_height_pct);
                    signerName = (payload.signer_name || 'Regional Director').toString().trim() || 'Regional Director';
                    serverSignatureImageUrl = (payload.signature_image_url || '').toString();
                    applySignatureImage(serverSignatureImageUrl);
                    removeSignatureImage = false;
                    pendingUploadFile = null;
                    signatureImageInput.value = '';
                    applyLayout();
                    showFlash('Profile loaded.', 'ok');
                } catch (error) {
                    applyLayout();
                    showFlash(error && error.message ? error.message : 'Unable to load profile.', 'warn');
                }
            }

            async function saveProfile() {
                const form = new FormData();
                form.append('csrf_token', csrfToken);
                form.append('mode', mode);
                form.append('paper_size', paperSize);
                form.append('x_pct', xPct.toFixed(2));
                form.append('y_pct', yPct.toFixed(2));
                form.append('block_width_pct', blockWidthPct.toFixed(2));
                form.append('block_height_pct', blockHeightPct.toFixed(2));
                if (pendingUploadFile) {
                    form.append('signature_image', pendingUploadFile);
                }
                if (removeSignatureImage) {
                    form.append('remove_signature_image', '1');
                }

                saveProfileBtn.disabled = true;
                showFlash('Saving profile...', '');
                try {
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload || !payload.ok) {
                        throw new Error(payload && payload.message ? payload.message : 'Unable to save profile.');
                    }

                    const profile = payload.profile || {};
                    mode = profile.mode === 'Free-Drag' ? 'Free-Drag' : 'Grid-Snap';
                    paperSize = paperPresets[profile.paper_size] ? profile.paper_size : 'A4';
                    xPct = Number(profile.x_pct);
                    yPct = Number(profile.y_pct);
                    blockWidthPct = Number(profile.block_width_pct);
                    blockHeightPct = Number(profile.block_height_pct);
                    signerName = (payload.signer_name || signerName).toString();
                    serverSignatureImageUrl = (payload.signature_image_url || '').toString();
                    applySignatureImage(serverSignatureImageUrl);
                    pendingUploadFile = null;
                    removeSignatureImage = false;
                    signatureImageInput.value = '';
                    applyLayout();
                    showFlash(payload.message || 'Profile saved.', 'ok');
                } catch (error) {
                    showFlash(error && error.message ? error.message : 'Unable to save profile.', 'warn');
                } finally {
                    saveProfileBtn.disabled = false;
                }
            }

            function setSignActionButtonsDisabled(disabled) {
                const nextState = !!disabled;
                if (applySignBtn) {
                    applySignBtn.disabled = nextState;
                }
                if (undoSignBtn) {
                    undoSignBtn.disabled = nextState;
                }
                if (applySignPrintBtn) {
                    applySignPrintBtn.disabled = nextState;
                }
            }

            async function sendSignWorkflowAction(actionName) {
                if (!Number.isFinite(selectedDocumentId) || selectedDocumentId <= 0) {
                    showFlash('Missing document context. Open this page from the Sign quick action in your action desk.', 'warn');
                    return null;
                }

                const form = new FormData();
                form.append('csrf_token', csrfToken);
                form.append('action', String(actionName || '').toUpperCase());
                form.append('document_id', String(selectedDocumentId));
                if (selectedTrackingId !== '') {
                    form.append('tracking_id', String(selectedTrackingId));
                }
                if (String(actionName || '').toUpperCase() === 'SIGN') {
                    const serverPlacement = buildServerSignPlacementPayload();
                    form.append('mode', String(serverPlacement.mode || 'Grid-Snap'));
                    form.append('paper_size', String(serverPlacement.paper_size || 'A4'));
                    form.append('x_pct', Number(serverPlacement.x_pct).toFixed(2));
                    form.append('y_pct', Number(serverPlacement.y_pct).toFixed(2));
                    form.append('block_width_pct', Number(serverPlacement.block_width_pct).toFixed(2));
                    form.append('block_height_pct', Number(serverPlacement.block_height_pct).toFixed(2));
                }

                setSignActionButtonsDisabled(true);

                try {
                    const response = await fetch(documentActionUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload || !payload.ok) {
                        throw new Error(payload && payload.message ? payload.message : 'Unable to process sign action.');
                    }
                    return payload;
                } catch (error) {
                    showFlash(error && error.message ? error.message : 'Unable to process sign action.', 'warn');
                    return null;
                } finally {
                    setSignActionButtonsDisabled(false);
                }
            }

            async function applySignAction(shouldPrintAfter) {
                showFlash('Applying sign action...', '');
                const payload = await sendSignWorkflowAction('SIGN');
                if (!payload) {
                    return;
                }

                showFlash(payload.message || 'Document signed.', 'ok');
                await loadDocumentCanvas(true);
                if (shouldPrintAfter) {
                    window.setTimeout(function () {
                        window.print();
                    }, 150);
                }
            }

            async function applyUndoSignAction() {
                showFlash('Undoing sign action...', '');
                const payload = await sendSignWorkflowAction('UNSIGN');
                if (!payload) {
                    return;
                }

                showFlash(payload.message || 'Sign has been undone.', 'ok');
                await loadDocumentCanvas(false);
            }

            modeFreeBtn.addEventListener('click', function () {
                mode = 'Free-Drag';
                applyModeUI();
                updateStatus();
            });

            modeGridBtn.addEventListener('click', function () {
                mode = 'Grid-Snap';
                applyModeUI();
                updateStatus();
            });

            paperSizeSelect.addEventListener('change', function () {
                paperSize = paperPresets[paperSizeSelect.value] ? paperSizeSelect.value : 'A4';
                applyLayout();
            });

            gridSizeInput.addEventListener('input', function () {
                gridLayer.style.setProperty('--grid-size', clamp(parseInt(gridSizeInput.value || '24', 10), 8, 80) + 'px');
            });

            function setWidthPercent(rawValue) {
                const nextValue = clamp(Number(rawValue), 20, 90);
                if (!Number.isFinite(nextValue)) {
                    return;
                }
                blockWidthPct = nextValue;
                applyLayout();
            }

            function setHeightPercent(rawValue) {
                const nextValue = clamp(Number(rawValue), 12, 80);
                if (!Number.isFinite(nextValue)) {
                    return;
                }
                blockHeightPct = nextValue;
                applyLayout();
            }

            blockWidthRangeInput.addEventListener('input', function () {
                setWidthPercent(blockWidthRangeInput.value);
            });

            blockWidthNumberInput.addEventListener('change', function () {
                setWidthPercent(blockWidthNumberInput.value);
            });

            blockHeightRangeInput.addEventListener('input', function () {
                setHeightPercent(blockHeightRangeInput.value);
            });

            blockHeightNumberInput.addEventListener('change', function () {
                setHeightPercent(blockHeightNumberInput.value);
            });

            if (canvasAttachmentSelect) {
                canvasAttachmentSelect.addEventListener('change', function () {
                    selectedCanvasAttachmentId = String(canvasAttachmentSelect.value || '');
                    if (selectedCanvasAttachmentId === '') {
                        const preferred = selectPreferredCanvasAttachment(canvasImageOptions, false);
                        applyCanvasImage(
                            preferred ? preferred.file_url : '',
                            preferred ? !!preferred.is_digitally_signed : false
                        );
                        if (preferred && preferred.is_digitally_signed) {
                            showFlash('Digitally signed output loaded. Overlay editor is hidden to avoid duplicate stamp preview.', '');
                        }
                        return;
                    }
                    const selected = canvasImageOptions.find(function (item) {
                        return String(item.id || '') === selectedCanvasAttachmentId;
                    });
                    applyCanvasImage(
                        selected ? selected.file_url : '',
                        selected ? !!selected.is_digitally_signed : false
                    );
                    if (selected && selected.is_digitally_signed) {
                        showFlash('Digitally signed output loaded. Overlay editor is hidden to avoid duplicate stamp preview.', '');
                    }
                });
            }

            signatureImageInput.addEventListener('change', function () {
                pendingUploadFile = signatureImageInput.files && signatureImageInput.files[0] ? signatureImageInput.files[0] : null;
                if (!pendingUploadFile) {
                    if (!removeSignatureImage) {
                        applySignatureImage(serverSignatureImageUrl);
                    }
                    return;
                }
                removeSignatureImage = false;
                const reader = new FileReader();
                reader.onload = function () {
                    if (typeof reader.result === 'string') {
                        applySignatureImage(reader.result);
                    }
                };
                reader.readAsDataURL(pendingUploadFile);
                showFlash('Selected signature image. Click Save Profile to apply.', '');
            });

            removeImageBtn.addEventListener('click', function () {
                pendingUploadFile = null;
                signatureImageInput.value = '';
                removeSignatureImage = true;
                applySignatureImage('');
                showFlash('Signature image marked for removal. Click Save Profile to apply.', 'warn');
            });

            resetPositionBtn.addEventListener('click', function () {
                xPct = 58;
                yPct = 74;
                blockWidthPct = 42;
                blockHeightPct = 14.25;
                applyLayout();
            });

            saveProfileBtn.addEventListener('click', saveProfile);
            if (applySignBtn) {
                applySignBtn.addEventListener('click', function () {
                    applySignAction(false);
                });
            }
            if (undoSignBtn) {
                undoSignBtn.addEventListener('click', function () {
                    applyUndoSignAction();
                });
            }
            if (applySignPrintBtn) {
                applySignPrintBtn.addEventListener('click', function () {
                    applySignAction(true);
                });
            }
            if (printCanvasBtn) {
                printCanvasBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            resizeHandles.forEach(function (handle) {
                handle.addEventListener('pointerdown', beginResize);
            });
            block.addEventListener('pointerdown', beginDrag);
            window.addEventListener('pointermove', function (evt) {
                moveResize(evt);
                moveDrag(evt);
            });
            window.addEventListener('pointerup', function (evt) {
                endResize(evt);
                endDrag(evt);
            });
            window.addEventListener('pointercancel', function (evt) {
                endResize(evt);
                endDrag(evt);
            });
            window.addEventListener('resize', applyLayout);

            setInterval(refreshDateTimePreview, 1000);
            paperSizeSelect.value = paperSize;
            applyLayout();
            loadProfile()
                .then(function () {
                    paperSizeSelect.value = paperSize;
                    return loadDocumentCanvas(false);
                })
                .catch(function () {
                    return loadDocumentCanvas(false);
                });
        })();
    </script>
</body>
</html>
