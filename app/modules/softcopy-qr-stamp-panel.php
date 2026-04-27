<?php
$qrStampTrackingId = trim((string)($_GET['tracking_id'] ?? ''));
$qrStampFrameUrl = app_url('softcopy-qr-stamp.php') . '?embedded=1';
if ($qrStampTrackingId !== '') {
    $qrStampFrameUrl .= '&tracking_id=' . rawurlencode($qrStampTrackingId);
}
?>
<section class="card qr-stamp-card" aria-label="Soft copy QR stamp workspace">
    <div class="qr-stamp-head">
        <h2>Soft Copy QR Stamp</h2>
        <p>Drag, resize, and print QR on a blank page (A4, Letter, or 8.5 x 13).</p>
    </div>

    <form method="get" action="" class="qr-stamp-toolbar" aria-label="QR stamp options">
        <label class="qr-stamp-field">
            <span>Tracking ID (optional)</span>
            <input type="text" name="tracking_id" value="<?php echo e($qrStampTrackingId); ?>" placeholder="Example: R12-PE-2026-0001">
        </label>
        <div class="qr-stamp-actions">
            <button type="submit" class="qr-stamp-btn">Load Tracking QR</button>
            <a href="" class="qr-stamp-btn ghost">Clear</a>
        </div>
    </form>

    <div class="qr-stamp-frame-wrap">
        <iframe
            class="qr-stamp-frame"
            src="<?php echo e($qrStampFrameUrl); ?>"
            title="QR stamp workspace"
            loading="lazy"
            referrerpolicy="same-origin"
        ></iframe>
    </div>
</section>

<style>
    :root {
        --qr-bg: #f7fbff;
        --qr-line: #d2dfef;
        --qr-text: #4b6179;
        --qr-card: #ffffff;
    }

    [data-theme="dark"] {
        --qr-bg: #0b1421;
        --qr-line: #26354a;
        --qr-text: #94a3b8;
        --qr-card: #152233;
    }

    .qr-stamp-card {
        display: grid;
        gap: 14px;
        padding: 16px;
    }

    .qr-stamp-head {
        padding: 12px 14px 8px;
    }

    .qr-stamp-head h2 {
        margin: 0;
        font-size: 1.12rem;
        line-height: 1.3;
    }

    .qr-stamp-head p {
        margin: 8px 0 0;
        color: var(--qr-text);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .qr-stamp-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }

    .qr-stamp-field {
        display: grid;
        gap: 6px;
        min-width: 280px;
        flex: 1 1 360px;
    }

    .qr-stamp-field span {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--qr-text);
    }

    .qr-stamp-field input {
        width: 100%;
        border: 1px solid var(--qr-line);
        background: var(--qr-card);
        color: var(--text);
        border-radius: 10px;
        height: 40px;
        padding: 0 12px;
        font-size: 0.9rem;
    }

    .qr-stamp-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .qr-stamp-btn {
        border: 1px solid #1f6ecb;
        background: #1f6ecb;
        color: #fff;
        border-radius: 10px;
        padding: 10px 14px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.84rem;
        cursor: pointer;
    }

    .qr-stamp-btn.ghost {
        background: rgba(31, 110, 203, 0.08);
        color: #3b82f6;
        border-color: var(--qr-line);
    }

    [data-theme="dark"] .qr-stamp-btn.ghost {
        background: rgba(59, 130, 246, 0.12);
        color: #60a5fa;
    }

    .qr-stamp-frame-wrap {
        border: 1px solid var(--qr-line);
        border-radius: 12px;
        overflow: hidden;
        background: var(--qr-bg);
    }

    .qr-stamp-frame {
        width: 100%;
        min-height: 86vh;
        border: 0;
        display: block;
        background: var(--qr-bg);
    }

    @media (max-width: 900px) {
        .qr-stamp-frame {
            min-height: 75vh;
        }
    }
</style>
