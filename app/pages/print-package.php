<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

session_start();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_dt(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '-';
    }

    try {
        return (new DateTimeImmutable($value))->format('M d, Y h:i A');
    } catch (Throwable $exception) {
        return $value;
    }
}

function format_date_only(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '-';
    }

    try {
        return (new DateTimeImmutable($value))->format('m/d/Y');
    } catch (Throwable $exception) {
        return '-';
    }
}

function attachment_public_path(array $attachment): ?string
{
    $attachmentId = (int)($attachment['id'] ?? 0);
    if ($attachmentId > 0) {
        return app_url('actions/attachment-file.php?attachment_id=' . $attachmentId);
    }

    $filePath = trim((string)($attachment['file_path'] ?? ''));
    if ($filePath === '') {
        return null;
    }

    if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
        return $filePath;
    }

    if (str_starts_with($filePath, '/')) {
        return $filePath;
    }

    return app_url(ltrim($filePath, '/'));
}

function attachment_is_image(array $attachment): bool
{
    $name = strtolower(trim((string)($attachment['file_name'] ?? '')));
    $path = strtolower(trim((string)($attachment['file_path'] ?? '')));
    $candidate = $name !== '' ? $name : $path;
    if ($candidate === '') {
        return false;
    }

    $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

function print_package_column_exists(PDO $pdo, string $table, string $column): bool
{
    $safeTable = trim($table);
    $safeColumn = trim($column);
    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }

    return db_column_exists($pdo, $safeTable, $safeColumn);
}

function print_package_arta_category_expr(PDO $pdo): string
{
    if (print_package_column_exists($pdo, 'documents', 'arta_category_override')) {
        return "COALESCE(NULLIF(TRIM(d.arta_category_override), ''), dt.category)";
    }

    return 'dt.category';
}

function print_package_arta_days_expr(PDO $pdo): string
{
    if (print_package_column_exists($pdo, 'documents', 'arta_days_limit_override')) {
        return 'COALESCE(NULLIF(d.arta_days_limit_override, 0), dt.arta_days_limit, 3)';
    }

    return 'COALESCE(dt.arta_days_limit, 3)';
}

function print_package_originating_entity_expr(PDO $pdo): string
{
    if (db_column_exists($pdo, 'documents', 'originating_entity_name')) {
        return "COALESCE(NULLIF(TRIM(d.originating_entity_name), ''), o.name)";
    }

    return 'o.name';
}

$pdo = getDatabaseConnection();
$trackingId = trim((string)($_GET['tracking_id'] ?? ''));
$autoPrint = ((string)($_GET['autoprint'] ?? '') === '1');
$canViewAttachments = !empty($_SESSION['user_id']);

$error = '';
$document = null;
$timelineRows = [];
$attachments = [];
$latestAttachment = null;
$keyAttachments = [];
$imageAttachments = [];
$stampToolUrl = '';
$generatedAt = (new DateTimeImmutable('now'))->format('M d, Y h:i A');

if ($trackingId === '') {
    $latestStmt = $pdo->query('SELECT TOP (1) tracking_id FROM documents ORDER BY id DESC');
    $trackingId = (string)($latestStmt->fetchColumn() ?: '');
}

if ($trackingId === '') {
    $error = 'No documents found yet. Create a document first to generate a printable package.';
} else {
    try {
        $artaCategoryExpr = print_package_arta_category_expr($pdo);
        $artaDaysExpr = print_package_arta_days_expr($pdo);
        $originatingEntityExpr = print_package_originating_entity_expr($pdo);
        $docStmt = $pdo->prepare(
            'SELECT
                d.id,
                d.tracking_id,
                d.subject,
                d.status,
                d.created_at,
                d.source_type,
                d.external_client_name,
                dt.name AS document_type,
                ' . $artaCategoryExpr . ' AS arta_category,
                ' . $artaDaysExpr . ' AS arta_days_limit,
                ' . $originatingEntityExpr . ' AS originating_office,
                o.name AS routing_office,
                (
                    SELECT TOP (1) CONCAT(COALESCE(u.first_name, \'\'), \' \', COALESCE(u.last_name, \'\'))
                    FROM users u
                    WHERE u.id = d.created_by_user_id
                ) AS created_by_name,
                (
                    SELECT COUNT(*)
                    FROM document_attachments da
                    WHERE da.document_id = d.id
                ) AS attachment_count
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             LEFT JOIN offices o ON o.id = d.originating_office_id
             WHERE d.tracking_id = :tracking_id'
        );
        $docStmt->execute(['tracking_id' => $trackingId]);
        $document = $docStmt->fetch();

        if (!$document) {
            $error = 'Tracking ID not found.';
        } else {
            $slipStmt = $pdo->prepare(
                'SELECT
                    ts.id,
                    ts.date_time_received,
                    ts.action_required,
                    fo.name AS from_office,
                    ro.name AS receiving_office,
                    CONCAT(COALESCE(u.first_name, \'\'), \' \', COALESCE(u.last_name, \'\')) AS received_by
                 FROM tracking_slips ts
                 LEFT JOIN offices fo ON fo.id = ts.from_office_id
                 LEFT JOIN offices ro ON ro.id = ts.receiving_office_id
                 LEFT JOIN users u ON u.id = ts.received_by
                 WHERE ts.document_id = :document_id
                 ORDER BY ts.date_time_received ASC, ts.id ASC'
            );
            $slipStmt->execute(['document_id' => (int)$document['id']]);
            $slips = $slipStmt->fetchAll();

            $releaseStmt = $pdo->prepare(
                'SELECT
                    al.id,
                    al.created_at,
                    al.action_type,
                    al.remarks,
                    dest.name AS destination_office
                 FROM activity_logs al
                 LEFT JOIN offices dest ON dest.id = al.destination_office_id
                 WHERE al.document_id = :document_id
                   AND al.action_type IN (\'Forwarded\', \'Rerouted\', \'Sent\', \'Returned\', \'Released\', \'Overridden\')
                 ORDER BY al.created_at ASC, al.id ASC'
            );
            $releaseStmt->execute(['document_id' => (int)$document['id']]);
            $releaseEvents = $releaseStmt->fetchAll();

            foreach ($slips as $index => $slip) {
                $release = $releaseEvents[$index] ?? null;
                $actionTaken = trim((string)($slip['action_required'] ?? ''));
                if ($actionTaken === '' || strcasecmp($actionTaken, 'received') === 0) {
                    $actionTaken = trim((string)($release['remarks'] ?? ''));
                }
                if ($actionTaken === '') {
                    $actionTaken = '-';
                }

                $timelineRows[] = [
                    'received' => (string)$slip['date_time_received'],
                    'from' => (string)($slip['from_office'] ?: '-'),
                    'released' => $release['created_at'] ?? null,
                    'to' => (string)($release['destination_office'] ?? ($slip['receiving_office'] ?: '-')),
                    'status' => 'Received',
                    'action' => $actionTaken,
                    'received_by' => trim((string)($slip['received_by'] ?? '')),
                ];
            }

            if ($canViewAttachments) {
                $attachmentStmt = $pdo->prepare(
                    'SELECT
                        da.id,
                        da.file_name,
                        da.file_path,
                        da.version_number,
                        da.is_internal_only,
                        da.uploaded_at,
                        CONCAT(COALESCE(u.first_name, \'\'), \' \', COALESCE(u.last_name, \'\')) AS uploaded_by_name
                     FROM document_attachments da
                     LEFT JOIN users u ON u.id = da.uploaded_by
                     WHERE da.document_id = :document_id
                     ORDER BY da.version_number DESC, da.uploaded_at DESC, da.id DESC'
                );
                $attachmentStmt->execute(['document_id' => (int)$document['id']]);
                $attachments = $attachmentStmt->fetchAll() ?: [];

                if (!empty($attachments)) {
                    $latestAttachment = $attachments[0];
                    $keyAttachments = array_slice($attachments, 0, 8);
                    $imageAttachments = array_values(array_filter(
                        $attachments,
                        static fn(array $attachment): bool => attachment_is_image($attachment)
                    ));
                }
            } else {
                $attachments = [];
                $latestAttachment = null;
                $keyAttachments = [];
                $imageAttachments = [];
            }
        }
    } catch (Throwable $exception) {
        $error = 'Unable to generate printable package right now.';
    }
}

$publicTrackingSlipUrl = $trackingId === ''
    ? ''
    : app_public_url('tracking-slip.php') . '?tracking_id=' . rawurlencode($trackingId) . '&public=1';
$qrText = $trackingId === ''
    ? ''
    : ($publicTrackingSlipUrl !== '' ? $publicTrackingSlipUrl : $trackingId);
$slipUrl = app_url('tracking-slip.php') . ($trackingId !== '' ? '?tracking_id=' . rawurlencode($trackingId) : '');
$backFallbackUrl = $slipUrl !== '' ? $slipUrl : app_url('dashboard.php');
if ($trackingId !== '' && !empty($imageAttachments)) {
    $stampToolUrl = app_url('softcopy-qr-stamp.php') . '?tracking_id=' . rawurlencode($trackingId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Package<?php echo $trackingId !== '' ? ' | ' . e($trackingId) : ''; ?></title>
    <style>
        :root {
            --ink: #1b2330;
            --line: #182a44;
            --soft-line: #ccd7e6;
            --paper: #ffffff;
            --accent: #1e7b83;
            --accent-2: #245a2a;
            --bg: #edf2f7;
            --text-ui: #1b2330;
            --paper-border: #b9c9dd;
            --card-line: #b7c7da;
            --shadow: 0 8px 24px rgba(19, 42, 74, 0.12);
        }

        [data-theme="dark"] {
            --bg: #0b1421;
            --paper: #f1f5f9;
            --text-ui: #f1f5f9;
            --card-line: #26354a;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
            padding: 18px;
        }
        .toolbar {
            width: min(1060px, 100%);
            margin: 0 auto 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .toolbar form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .toolbar input[type="text"] {
            height: 38px;
            min-width: 290px;
            border: 1px solid var(--card-line);
            border-radius: 8px;
            padding: 0 10px;
            font-size: 14px;
            background: var(--paper);
            color: var(--ink);
        }
        .toolbar button,
        .toolbar a {
            height: 38px;
            border: none;
            border-radius: 8px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .toolbar .load-btn { background: #2f5fb4; color: #fff; }
        .toolbar .back-btn { background: #e6edf7; color: #1d3557; border: 1px solid #c3d2e6; }
        .toolbar .print-btn { background: var(--accent-2); color: #fff; }
        .toolbar .slip-btn {
            background: #f3f8ff;
            color: #2f5fb4;
            border: 1px solid #bed0e8;
        }
        .toolbar .login-btn {
            background: #fff6f6;
            color: #8a2f2f;
            border: 1px solid #e7b9b9;
        }
        
        /* Theme toggle from tracking-slip style */
        .theme-toggle {
            height: 38px;
            width: 38px;
            border-radius: 8px;
            border: 1px solid var(--card-line);
            background: var(--paper);
            color: var(--ink);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
        }
        .theme-icon { display: none; }
        [data-theme="dark"] .dark-icon { display: block; }
        :root:not([data-theme="dark"]) .light-icon { display: block; }

        .error {
            width: min(1060px, 100%);
            margin: 0 auto;
            background: #fff3f3;
            border: 1px solid #e8b0b0;
            color: #8b2626;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 13px;
        }

        .package {
            width: min(1060px, 100%);
            margin: 0 auto;
            background: var(--paper);
            border: 1.5px solid var(--paper-border);
            box-shadow: var(--shadow);
            padding: 14px;
            color: var(--ink);
        }
        .header {
            border: 2px solid var(--line);
            display: grid;
            grid-template-columns: 96px 1fr 170px;
            gap: 10px;
            align-items: center;
            padding: 10px;
        }
        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-wrap img {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }
        .agency h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.12;
            color: var(--ink);
        }
        .agency p {
            margin: 2px 0;
            font-size: 12px;
            color: var(--ink);
        }
        .qr-wrap {
            display: grid;
            justify-items: center;
            gap: 4px;
        }
        .qr-wrap img {
            width: 130px;
            height: 130px;
            border: 1px solid var(--line);
            background: #fff;
        }
        .qr-wrap span {
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            word-break: break-all;
            color: var(--line);
        }

        .package-title {
            margin-top: 10px;
            border: 2px solid var(--line);
            border-top: none;
            padding: 8px 10px;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: center;
            color: var(--ink);
        }
        .meta-grid {
            border: 2px solid var(--line);
            border-top: none;
            display: grid;
            grid-template-columns: 180px 1fr 180px 1fr;
        }
        .meta-cell {
            border-right: 1px solid var(--soft-line);
            border-bottom: 1px solid var(--soft-line);
            padding: 8px 9px;
            font-size: 13px;
            color: var(--ink);
        }
        .meta-cell.label {
            font-weight: 700;
            background: #f6f9fd;
        }
        .meta-grid .meta-cell:nth-child(4n) { border-right: none; }

        .section {
            margin-top: 12px;
            border: 1.5px solid #c9d6e7;
        }
        .section h2 {
            margin: 0;
            padding: 10px;
            background: #eef5fc;
            border-bottom: 1px solid #c9d6e7;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--ink);
        }
        .section-body {
            padding: 10px;
        }
        .subtle {
            margin: 0 0 8px;
            font-size: 12px;
            color: #4a5f79;
        }
        .timeline-table,
        .attachments-table {
            width: 100%;
            border-collapse: collapse;
        }
        .timeline-table th,
        .timeline-table td,
        .attachments-table th,
        .attachments-table td {
            border: 1px solid #c8d5e6;
            padding: 7px 8px;
            font-size: 12px;
            vertical-align: top;
            color: var(--ink);
        }
        .timeline-table th,
        .attachments-table th {
            background: #f4f8fc;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.04em;
            color: #334f70;
        }
        .latest-card {
            border: 1px solid #c8d5e6;
            border-radius: 8px;
            padding: 10px;
            background: #fbfdff;
            display: grid;
            gap: 6px;
            margin-bottom: 10px;
        }
        .latest-row {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 8px;
            font-size: 12px;
        }
        .latest-row .k {
            font-weight: 700;
            color: #375474;
        }
        .attachment-path {
            word-break: break-all;
            color: #3f5f86;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #20466e;
            background: #e8f3ff;
            border: 1px solid #c0d7f0;
        }
        .page-break {
            page-break-before: always;
            break-before: page;
        }

        @page {
            size: legal portrait;
            margin: 0.35in;
        }
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                color: #000 !important;
            }
            .toolbar {
                display: none !important;
            }
            .package {
                width: 100% !important;
                margin: 0 !important;
                border: 1.5px solid #000 !important;
                box-shadow: none !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .section {
                page-break-inside: avoid;
                border-color: #000 !important;
            }
            .section h2 { border-color: #000 !important; background: #eee !important; color: #000 !important; }
            .header, .package-title, .meta-grid { border-color: #000 !important; }
            .meta-cell { border-color: #ddd !important; color: #000 !important; }
            .meta-cell.label { background: #eee !important; }
            .timeline-table th, .timeline-table td, .attachments-table th, .attachments-table td { border-color: #ddd !important; }
            a {
                color: inherit;
                text-decoration: none;
            }
        }
    </style>
    <script>
        (function() {
            const theme = localStorage.getItem('DTMIS_theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
        
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') || 'light';
            const next = current === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', next);
            localStorage.setItem('DTMIS_theme', next);
        }
    </script>
</head>
<body>
    <div class="toolbar">
        <button
            type="button"
            class="back-btn"
            onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '<?php echo e($backFallbackUrl); ?>'; }"
        >Back</button>
        <form method="get" action="">
            <input type="text" name="tracking_id" value="<?php echo e($trackingId); ?>" placeholder="Enter Tracking ID (e.g., DENR-XII-2026-0001)">
            <button type="submit" class="load-btn">Load Package</button>
        </form>
        <button type="button" class="print-btn" onclick="window.print()">Print Full Package</button>
        <a class="slip-btn" href="<?php echo e($slipUrl); ?>">View Tracking Slip</a>
        <?php if ($stampToolUrl !== '' && $canViewAttachments): ?>
        <a class="slip-btn" href="<?php echo e($stampToolUrl); ?>">Soft Copy QR Stamping</a>
        <?php endif; ?>
        <button type="button" class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
            <span class="theme-icon light-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            </span>
            <span class="theme-icon dark-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </span>
        </button>
        <?php if (!$canViewAttachments): ?>
        <a class="login-btn" href="<?php echo e(app_url('auth/login.php')); ?>">Login To Include Attachment Details</a>
        <?php endif; ?>
    </div>

    <?php if ($error !== ''): ?>
    <div class="error"><?php echo e($error); ?></div>
    <?php elseif ($document): ?>
    <section class="package">
        <div class="header">
            <div class="logo-wrap">
                <img src="./assets/Logo.png" alt="DENR Logo">
            </div>
            <div class="agency">
                <p>Republic of the Philippines</p>
                <h1>Department of Environment and Natural Resources</h1>
                <p>DENR Regional Office XII | Koronadal City, South Cotabato</p>
                <p>One-Click Printable Package</p>
            </div>
            <div class="qr-wrap">
                <img
                    id="printPackageQrImage"
                    data-qr-text="<?php echo e($qrText); ?>"
                    alt="QR code for <?php echo e((string)$document['tracking_id']); ?>"
                >
                <span><?php echo e((string)$document['tracking_id']); ?></span>
            </div>
        </div>

        <div class="package-title">Tracking Slip + Latest Document + Key Attachments</div>

        <div class="meta-grid">
            <div class="meta-cell label">Tracking ID</div>
            <div class="meta-cell"><?php echo e((string)$document['tracking_id']); ?></div>
            <div class="meta-cell label">Generated</div>
            <div class="meta-cell"><?php echo e($generatedAt); ?></div>

            <div class="meta-cell label">Subject</div>
            <div class="meta-cell"><?php echo e((string)$document['subject']); ?></div>
            <div class="meta-cell label">Status</div>
            <div class="meta-cell"><?php echo e((string)$document['status']); ?></div>

            <div class="meta-cell label">Originating Office / Entity</div>
            <div class="meta-cell"><?php echo e((string)($document['originating_office'] ?? '-')); ?></div>
            <div class="meta-cell label">Created Date</div>
            <div class="meta-cell"><?php echo e(format_date_only((string)($document['created_at'] ?? null))); ?></div>

            <div class="meta-cell label">Document Type</div>
            <div class="meta-cell"><?php echo e((string)($document['document_type'] ?? '-')); ?></div>
            <div class="meta-cell label">ARTA Category</div>
            <div class="meta-cell"><?php echo e((string)($document['arta_category'] ?? '-')); ?></div>

            <div class="meta-cell label">Source</div>
            <div class="meta-cell"><?php echo e((string)($document['source_type'] ?? 'INTERNAL')); ?></div>
            <div class="meta-cell label">Attachment Count</div>
            <div class="meta-cell"><?php echo e((string)((int)($document['attachment_count'] ?? 0))); ?></div>
        </div>

        <section class="section">
            <h2>A. Official Tracking Slip (Custody Timeline)</h2>
            <div class="section-body">
                <p class="subtle">Only received custody events are considered official slip entries. Sent/forwarded actions remain in backend activity logs.</p>
                <table class="timeline-table">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Date / Time Received</th>
                            <th style="width: 150px;">From</th>
                            <th style="width: 170px;">Date / Time Released</th>
                            <th style="width: 150px;">To</th>
                            <th style="width: 100px;">Status</th>
                            <th>Action Required / Taken</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($timelineRows)): ?>
                        <tr>
                            <td colspan="6">No custody receive events found yet for this tracking ID.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($timelineRows as $row): ?>
                            <tr>
                                <td>
                                    <?php echo e(format_dt($row['received'])); ?><br>
                                    <?php if (trim((string)$row['received_by']) !== ''): ?>
                                    <small>Received by: <?php echo e((string)$row['received_by']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e((string)$row['from']); ?></td>
                                <td><?php echo e(format_dt($row['released'])); ?></td>
                                <td><?php echo e((string)$row['to']); ?></td>
                                <td><?php echo e((string)$row['status']); ?></td>
                                <td><?php echo e((string)$row['action']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section page-break">
            <h2>B. Latest Document Version</h2>
            <div class="section-body">
                <?php if ($latestAttachment): ?>
                <?php
                    $latestPath = attachment_public_path($latestAttachment);
                    $latestFileName = (string)($latestAttachment['file_name'] ?? '-');
                    $latestIsImage = attachment_is_image($latestAttachment);
                    $latestStampUrl = $latestIsImage
                        ? app_url('softcopy-qr-stamp.php') . '?tracking_id=' . rawurlencode((string)$document['tracking_id']) . '&attachment_id=' . (int)($latestAttachment['id'] ?? 0)
                        : '';
                ?>
                <div class="latest-card">
                    <div class="latest-row"><span class="k">File Name</span><span><?php echo e($latestFileName); ?></span></div>
                    <div class="latest-row"><span class="k">Version</span><span>V<?php echo e((string)($latestAttachment['version_number'] ?? '1')); ?></span></div>
                    <div class="latest-row"><span class="k">Uploaded At</span><span><?php echo e(format_dt((string)($latestAttachment['uploaded_at'] ?? null))); ?></span></div>
                    <?php if ($canViewAttachments): ?>
                    <div class="latest-row"><span class="k">Uploaded By</span><span><?php echo e(trim((string)($latestAttachment['uploaded_by_name'] ?? '')) !== '' ? (string)$latestAttachment['uploaded_by_name'] : 'N/A'); ?></span></div>
                    <div class="latest-row"><span class="k">Visibility</span><span>
                        <?php if ((int)($latestAttachment['is_internal_only'] ?? 0) === 1): ?>
                        <span class="badge">Internal Only</span>
                        <?php else: ?>
                        <span class="badge">Public Attachment</span>
                        <?php endif; ?>
                    </span></div>
                    <?php endif; ?>
                    <div class="latest-row"><span class="k">File Path</span><span class="attachment-path"><?php echo e((string)($latestAttachment['file_path'] ?? '-')); ?></span></div>
                    <?php if ($latestPath !== null): ?>
                    <div class="latest-row"><span class="k">Open File</span><span><a href="<?php echo e($latestPath); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($latestPath); ?></a></span></div>
                    <?php endif; ?>
                    <?php if ($latestStampUrl !== ''): ?>
                    <div class="latest-row"><span class="k">QR Stamping</span><span><a href="<?php echo e($latestStampUrl); ?>">Open Soft Copy QR Stamping Tool</a></span></div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p class="subtle">No document file is attached yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="section">
            <h2>C. Key Attachments</h2>
            <div class="section-body">
                <?php if (!$canViewAttachments): ?>
                <p class="subtle">Public mode: attachment details are hidden. Login is required for the full package attachment list.</p>
                <?php endif; ?>

                <table class="attachments-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>File Name</th>
                            <th style="width: 70px;">Version</th>
                            <th style="width: 170px;">Uploaded At</th>
                            <?php if ($canViewAttachments): ?>
                            <th style="width: 120px;">Visibility</th>
                            <?php endif; ?>
                            <th>Path / Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($keyAttachments)): ?>
                        <tr>
                            <td colspan="<?php echo $canViewAttachments ? '6' : '5'; ?>">No attachments available for this package.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($keyAttachments as $index => $attachment): ?>
                            <?php $attachmentPath = attachment_public_path($attachment); ?>
                            <tr>
                                <td><?php echo e((string)($index + 1)); ?></td>
                                <td><?php echo e((string)($attachment['file_name'] ?? '-')); ?></td>
                                <td>V<?php echo e((string)($attachment['version_number'] ?? '1')); ?></td>
                                <td><?php echo e(format_dt((string)($attachment['uploaded_at'] ?? null))); ?></td>
                                <?php if ($canViewAttachments): ?>
                                <td>
                                    <?php if ((int)($attachment['is_internal_only'] ?? 0) === 1): ?>
                                    Internal Only
                                    <?php else: ?>
                                    Public
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="attachment-path">
                                    <?php if ($attachmentPath !== null): ?>
                                    <a href="<?php echo e($attachmentPath); ?>" target="_blank" rel="noopener noreferrer"><?php echo e((string)($attachment['file_path'] ?? '-')); ?></a>
                                    <?php else: ?>
                                    <?php echo e((string)($attachment['file_path'] ?? '-')); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
    <?php endif; ?>

    <script src="<?php echo e(app_url('assets/js/vendor/qrcode-generator.js')); ?>"></script>
    <script src="<?php echo e(app_url('assets/js/local-qr.js')); ?>"></script>
    <script>
        (function () {
            const printQrImage = document.getElementById('printPackageQrImage');
            if (!printQrImage || !window.DTMISLocalQr || typeof window.DTMISLocalQr.renderImage !== 'function') {
                return;
            }

            const qrPayload = String(printQrImage.getAttribute('data-qr-text') || '').trim();
            if (qrPayload === '') {
                return;
            }

            try {
                window.DTMISLocalQr.renderImage(printQrImage, qrPayload, {
                    size: 360,
                    margin: 2,
                    errorCorrection: 'M',
                    typeNumber: 0
                });
            } catch (error) {
                // Keep print package usable even if QR rendering fails.
            }
        })();
    </script>

    <?php if ($autoPrint && $document): ?>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
    <?php endif; ?>
</body>
</html>
