<?php
$digitalSignatureTrackingId = trim((string)($_GET['tracking_id'] ?? ''));
$digitalSignatureDocumentId = (int)($_GET['document_id'] ?? 0);
$digitalSignatureReturnToRaw = trim((string)($_GET['return_to'] ?? 'rd-action-desk.php'));
$digitalSignatureReturnTo = basename($digitalSignatureReturnToRaw);
if ($digitalSignatureReturnTo === '' || !preg_match('/^[A-Za-z0-9._-]+\.php$/', $digitalSignatureReturnTo)) {
    $digitalSignatureReturnTo = 'rd-action-desk.php';
}

$digitalSignatureFrameQuery = ['embedded' => '1'];
if ($digitalSignatureTrackingId !== '') {
    $digitalSignatureFrameQuery['tracking_id'] = $digitalSignatureTrackingId;
}
if ($digitalSignatureDocumentId > 0) {
    $digitalSignatureFrameQuery['document_id'] = (string)$digitalSignatureDocumentId;
}
$digitalSignatureFrameQuery['return_to'] = $digitalSignatureReturnTo;
$digitalSignatureFrameUrl = app_url('softcopy-digital-signature.php') . '?' . http_build_query($digitalSignatureFrameQuery);
?>
<section class="card qr-stamp-card" aria-label="Digital signature workspace">
    <div class="qr-stamp-head">
        <h2>Digital Signature Workspace</h2>
        <p>Upload your signature, place the signature block, and print directly on the selected attachment image canvas.</p>
        <?php if ($digitalSignatureTrackingId !== ''): ?>
        <p><strong>Selected Tracking:</strong> <?php echo e($digitalSignatureTrackingId); ?></p>
        <?php endif; ?>
    </div>

    <div class="qr-stamp-frame-wrap">
        <iframe
            class="qr-stamp-frame"
            src="<?php echo e($digitalSignatureFrameUrl); ?>"
            title="Digital signature workspace"
            loading="lazy"
            referrerpolicy="same-origin"
        ></iframe>
    </div>
</section>

<style>
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
        color: #4b6179;
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .qr-stamp-frame-wrap {
        border: 1px solid #d2dfef;
        border-radius: 12px;
        overflow: hidden;
        background: #f7fbff;
    }

    .qr-stamp-frame {
        width: 100%;
        min-height: 86vh;
        border: 0;
        display: block;
        background: #f7fbff;
    }

    @media (max-width: 900px) {
        .qr-stamp-frame {
            min-height: 75vh;
        }
    }
</style>
