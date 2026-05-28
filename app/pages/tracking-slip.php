<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/workflow.php';

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

function format_year_only(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '-';
    }

    try {
        return (new DateTimeImmutable($value))->format('Y');
    } catch (Throwable $exception) {
        return '-';
    }
}

function tracking_find_ored_office(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        "SELECT TOP (1) id, name, level, parent_office_id
         FROM offices
         WHERE UPPER(name) LIKE '%ORED%'
            OR UPPER(name) LIKE '%REGIONAL EXECUTIVE%'
         ORDER BY id ASC"
    );
    $office = $stmt ? $stmt->fetch() : false;
    if ($office) {
        return $office;
    }

    $oredRoleId = workflow_get_role_id_by_key($pdo, 'ORED');
    $oredSignRoleId = workflow_get_role_id_by_key($pdo, 'ORED_SIGN');
    $roleIds = array_values(array_filter([
        $oredRoleId,
        $oredSignRoleId,
    ], static fn ($value): bool => (int)$value > 0));
    if ($roleIds === []) {
        return null;
    }

    $placeholders = [];
    $params = [];
    foreach ($roleIds as $index => $roleId) {
        $key = 'role_id_' . $index;
        $placeholders[] = ':' . $key;
        $params[$key] = (int)$roleId;
    }

    $fallbackStmt = $pdo->prepare(
        'SELECT TOP (1) o.id, o.name, o.level, o.parent_office_id
         FROM offices o
         INNER JOIN users u ON u.office_id = o.id
         WHERE u.role_id IN (' . implode(', ', $placeholders) . ')
         ORDER BY o.id ASC'
    );
    $fallbackStmt->execute($params);
    $office = $fallbackStmt->fetch();

    return $office ?: null;
}

function tracking_resolve_created_destination_office(PDO $pdo, array $document): ?array
{
    $originatingOfficeId = (int)($document['originating_office_id'] ?? 0);
    if ($originatingOfficeId <= 0) {
        return null;
    }

    $originatingOffice = workflow_get_office_context($pdo, $originatingOfficeId);
    if (!$originatingOffice) {
        return null;
    }

    $originatingLevel = workflow_office_level_key((string)($originatingOffice['level'] ?? ''));
    $originatingName = strtoupper(trim((string)($originatingOffice['name'] ?? '')));

    if ($originatingLevel === 'CENRO_ADMIN_RECORD') {
        $cenroRoot = workflow_find_cenro_root_office($pdo, $originatingOfficeId);
        if ($cenroRoot) {
            $cenroOfficer = workflow_find_child_office_by_level($pdo, (int)($cenroRoot['id'] ?? 0), 'CENRO_OFFICER');
            if ($cenroOfficer) {
                return $cenroOfficer;
            }
        }
    }

    if ($originatingLevel === 'PENRO_ADMIN_RECORD') {
        $penroRoot = workflow_find_penro_root_office($pdo, $originatingOfficeId);
        if ($penroRoot) {
            $penroOfficer = workflow_find_child_office_by_level($pdo, (int)($penroRoot['id'] ?? 0), 'PENRO_OFFICER');
            if ($penroOfficer) {
                return $penroOfficer;
            }
        }
    }

    if ($originatingLevel === 'PAMO_ADMIN') {
        $pamoRoot = workflow_find_pamo_root_office($pdo, $originatingOfficeId);
        if ($pamoRoot) {
            $pasuOffice = workflow_find_child_office_by_level($pdo, (int)($pamoRoot['id'] ?? 0), 'PASU_OFFICER');
            if ($pasuOffice) {
                return $pasuOffice;
            }
        }
    }

    if (workflow_name_matches_records_unit($originatingName)) {
        return tracking_find_ored_office($pdo);
    }

    return null;
}

function tracking_resolve_created_destination_label(PDO $pdo, array $document, string $fallbackLabel): string
{
    $destinationOffice = tracking_resolve_created_destination_office($pdo, $document);
    $destinationLabel = trim((string)($destinationOffice['name'] ?? ''));
    if ($destinationLabel !== '') {
        return $destinationLabel;
    }

    return $fallbackLabel;
}

function tracking_status_from_action_type(?string $actionType): string
{
    $normalized = strtoupper(trim((string)$actionType));
    if ($normalized === 'CREATED') {
        return 'Created';
    }
    if (in_array($normalized, ['FORWARDED', 'FORWARD', 'REROUTED', 'REROUTE', 'SENT', 'OVERRIDDEN', 'OVERRIDE'], true)) {
        return 'Forwarded';
    }
    if (in_array($normalized, ['RECEIVED', 'RECIEVED'], true)) {
        return 'Received';
    }
    if ($normalized === 'COMPLETED') {
        return 'Completed';
    }
    if ($normalized === 'RELEASED') {
        return 'Released';
    }
    if ($normalized === 'SIGNED') {
        return 'Signed/Completed';
    }
    if (in_array($normalized, ['RETURNED', 'RETURN'], true)) {
        return 'Returned for Correction';
    }
    return $normalized !== '' ? ucwords(strtolower(str_replace('_', ' ', $normalized))) : '-';
}

function tracking_default_action_text(?string $actionType): string
{
    $normalized = strtoupper(trim((string)$actionType));
    if ($normalized === 'CREATED') {
        return 'Document intake created.';
    }
    if (in_array($normalized, ['FORWARDED', 'FORWARD', 'REROUTED', 'REROUTE', 'SENT', 'OVERRIDDEN', 'OVERRIDE'], true)) {
        return 'Forwarded to next office.';
    }
    if (in_array($normalized, ['RETURNED', 'RETURN'], true)) {
        return 'Returned for correction.';
    }
    if (in_array($normalized, ['RECEIVED', 'RECIEVED'], true)) {
        return 'Document officially received.';
    }
    if ($normalized === 'COMPLETED') {
        return 'Document transaction completed at originating office.';
    }
    if ($normalized === 'SIGNED') {
        return 'Document signed by authorized signatory.';
    }
    if ($normalized === 'RELEASED') {
        return 'Document released to originating office.';
    }

    return 'Document updated.';
}

function tracking_attachment_category(string $fileName): string
{
    $name = strtolower(trim($fileName));
    if ($name === '') {
        return 'attachment';
    }

    $isPreparedResponse = (
        str_contains($name, 'prepared')
        || str_contains($name, 'prepare')
        || str_contains($name, 'response')
        || str_contains($name, 'reply')
    ) && (
        str_contains($name, 'response')
        || str_contains($name, 'prepared')
        || str_contains($name, 'prepare')
    );
    if ($isPreparedResponse) {
        return 'prepared_response';
    }

    if (str_contains($name, 'endorsement') || str_contains($name, 'endorse')) {
        return 'endorsement';
    }

    return 'attachment';
}

function tracking_attachment_category_label(string $category): string
{
    $normalized = strtolower(trim($category));
    if ($normalized === 'prepared_response') {
        return 'Prepared Response';
    }
    if ($normalized === 'endorsement') {
        return 'Endorsement';
    }
    return 'Attachment';
}

function tracking_attachment_preview_type(string $fileName): string
{
    $extension = strtolower((string)pathinfo(trim($fileName), PATHINFO_EXTENSION));
    if ($extension === 'pdf') {
        return 'pdf';
    }
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
        return 'image';
    }
    return 'none';
}

const SUBJECT_CHANGE_REMARK_PREFIX = 'SUBJECT_CHANGE_JSON:';

function tracking_parse_subject_change_remarks(?string $remarks): ?array
{
    $value = trim((string)$remarks);
    if ($value === '' || !str_starts_with($value, SUBJECT_CHANGE_REMARK_PREFIX)) {
        return null;
    }

    $json = substr($value, strlen(SUBJECT_CHANGE_REMARK_PREFIX));
    if ($json === '' || !is_string($json)) {
        return null;
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return null;
    }

    return [
        'from' => trim((string)($decoded['from'] ?? '')),
        'to' => trim((string)($decoded['to'] ?? '')),
    ];
}

function tracking_slip_column_exists(PDO $pdo, string $table, string $column): bool
{
    $safeTable = trim($table);
    $safeColumn = trim($column);
    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }

    return db_column_exists($pdo, $safeTable, $safeColumn);
}

function tracking_slip_arta_category_expr(PDO $pdo): string
{
    if (tracking_slip_column_exists($pdo, 'documents', 'arta_category_override')) {
        return "COALESCE(NULLIF(TRIM(d.arta_category_override), ''), dt.category)";
    }

    return 'dt.category';
}

function tracking_slip_sender_expr(PDO $pdo): string
{
    if (tracking_slip_column_exists($pdo, 'documents', 'sender')) {
        return "COALESCE(NULLIF(TRIM(d.sender), ''), NULLIF(TRIM(d.external_client_name), ''), o.name)";
    }

    return "COALESCE(NULLIF(TRIM(d.external_client_name), ''), o.name)";
}

function tracking_slip_originating_entity_expr(PDO $pdo): string
{
    if (tracking_slip_column_exists($pdo, 'documents', 'originating_entity_name')) {
        return "COALESCE(NULLIF(TRIM(d.originating_entity_name), ''), o.name)";
    }

    return 'o.name';
}

$pdo = getDatabaseConnection();
// Parse URL flags used by this page variant (public view + optional autoprint).
$trackingId = trim((string)($_GET['tracking_id'] ?? ''));
$publicMode = ((string)($_GET['public'] ?? '') === '1');
$autoPrint = ((string)($_GET['autoprint'] ?? '') === '1');
$error = '';
$errorType = '';
$document = null;
$slips = [];
$releaseEvents = [];
$activityEvents = [];
$attachments = [];
$canViewAttachments = !empty($_SESSION['user_id']);
$subjectRowValue = '-';
$newSubjectRowValue = '';
$showNewSubjectRow = false;

if ($trackingId === '' && !$publicMode) {
    // Internal users without an explicit tracking ID default to the latest document.
    $latestStmt = $pdo->query('SELECT TOP (1) tracking_id FROM documents ORDER BY id DESC');
    $trackingId = (string)($latestStmt->fetchColumn() ?: '');
}

if ($trackingId === '') {
    $error = $publicMode
        ? 'Tracking ID is required.'
        : 'No documents found yet. Create a document first to generate a tracking slip.';
    $errorType = $publicMode ? 'missing_tracking' : 'empty_documents';
} else {
    try {
        $artaCategoryExpr = tracking_slip_arta_category_expr($pdo);
        $senderExpr = tracking_slip_sender_expr($pdo);
        $originatingEntityExpr = tracking_slip_originating_entity_expr($pdo);
        // Core document payload for header/meta fields in the printed tracking slip.
        $docStmt = $pdo->prepare(
            'SELECT
                d.id,
                d.tracking_id,
                d.subject,
                d.created_at,
                d.originating_office_id,
                d.source_type,
                d.external_client_name,
                d.client_address,
                ' . $senderExpr . ' AS sender_label,
                dt.name AS document_type,
                ' . $artaCategoryExpr . ' AS arta_category,
                ' . $originatingEntityExpr . ' AS originating_office,
                o.name AS routing_office,
                CONCAT(COALESCE(sender.first_name, \'\'), \' \', COALESCE(sender.last_name, \'\')) AS sender_name,
                (
                    SELECT COUNT(*)
                    FROM document_attachments da
                    WHERE da.document_id = d.id
                ) AS attachment_count
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             LEFT JOIN offices o ON o.id = d.originating_office_id
             LEFT JOIN users sender ON sender.id = (
                SELECT TOP (1) al.user_id
                FROM activity_logs al
                WHERE al.document_id = d.id
                  AND al.action_type = \'Created\'
                ORDER BY al.created_at ASC, al.id ASC
             )
             WHERE d.tracking_id = :tracking_id'
        );
        $docStmt->execute(['tracking_id' => $trackingId]);
        $document = $docStmt->fetch() ?: null;

        if ($document === null) {
            $error = 'Tracking ID not found.';
            $errorType = 'not_found';
        } else {
            $slipStmt = $pdo->prepare(
                'SELECT
                    ts.id,
                    ts.from_office_id,
                    ts.receiving_office_id,
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
            if (!empty($slips)) {
                // Suppress historical duplicate self-receive events to keep timeline readable.
                $normalizedSlips = [];
                $lastInboundByOffice = [];
                foreach ($slips as $slip) {
                    $fromOfficeId = (int)($slip['from_office_id'] ?? 0);
                    $receivingOfficeId = (int)($slip['receiving_office_id'] ?? 0);
                    $receivedAtTs = strtotime((string)($slip['date_time_received'] ?? ''));
                    $isSelfReceive = $fromOfficeId > 0 && $receivingOfficeId > 0 && $fromOfficeId === $receivingOfficeId;

                    if ($isSelfReceive && isset($lastInboundByOffice[$receivingOfficeId])) {
                        $previousTs = (int)$lastInboundByOffice[$receivingOfficeId];
                        if ($receivedAtTs !== false && $previousTs > 0 && ($receivedAtTs - $previousTs) <= 21600) {
                            // Hide legacy duplicate self-receive rows in printed/onscreen timeline.
                            continue;
                        }
                    }

                    $normalizedSlips[] = $slip;
                    if ($receivingOfficeId > 0 && $fromOfficeId > 0 && $fromOfficeId !== $receivingOfficeId && $receivedAtTs !== false) {
                        $lastInboundByOffice[$receivingOfficeId] = $receivedAtTs;
                    }
                }
                $slips = $normalizedSlips;
            }

            $releaseStmt = $pdo->prepare(
                'SELECT
                    al.id,
                    al.created_at,
                    al.action_type,
                    al.remarks,
                    al.destination_office_id,
                    dest.name AS destination_office,
                    CONCAT(COALESCE(actor.first_name, \'\'), \' \', COALESCE(actor.last_name, \'\')) AS actor_name,
                    actor_office.name AS actor_office
                 FROM activity_logs al
                 LEFT JOIN offices dest ON dest.id = al.destination_office_id
                 LEFT JOIN users actor ON actor.id = al.user_id
                 LEFT JOIN offices actor_office ON actor_office.id = actor.office_id
                 WHERE al.document_id = :document_id
                   AND al.action_type IN (\'Forwarded\', \'Rerouted\', \'Sent\', \'Returned\', \'Released\', \'Overridden\', \'Signed\')
                 ORDER BY al.created_at ASC, al.id ASC'
            );
            $releaseStmt->execute(['document_id' => (int)$document['id']]);
            $releaseEvents = $releaseStmt->fetchAll();
            if (!empty($releaseEvents)) {
                // Deduplicate terminal actions so repeated Signed/Released logs show once.
                $dedupedReleaseEvents = [];
                $terminalSignatureIndex = [];
                foreach ($releaseEvents as $event) {
                    $actionType = strtoupper(trim((string)($event['action_type'] ?? '')));
                    if (!in_array($actionType, ['RELEASED', 'SIGNED'], true)) {
                        $dedupedReleaseEvents[] = $event;
                        continue;
                    }

                    $signature = implode('|', [
                        $actionType,
                        strtoupper(trim((string)($event['destination_office'] ?? ''))),
                        strtoupper(trim((string)($event['actor_name'] ?? ''))),
                        trim((string)($event['remarks'] ?? '')),
                    ]);

                    if (!isset($terminalSignatureIndex[$signature])) {
                        $terminalSignatureIndex[$signature] = count($dedupedReleaseEvents);
                        $dedupedReleaseEvents[] = $event;
                        continue;
                    }

                    // Keep only the latest duplicate terminal action per signature.
                    $existingIndex = (int)$terminalSignatureIndex[$signature];
                    $dedupedReleaseEvents[$existingIndex] = $event;
                }

                $releaseEvents = array_values($dedupedReleaseEvents);
            }

            $activityStmt = $pdo->prepare(
                'SELECT
                    al.id,
                    al.created_at,
                    al.action_type,
                    al.action_scope,
                    al.remarks,
                    dest.name AS destination_office,
                    CONCAT(COALESCE(actor.first_name, \'\'), \' \', COALESCE(actor.last_name, \'\')) AS actor_name,
                    actor_office.name AS actor_office
                 FROM activity_logs al
                 LEFT JOIN offices dest ON dest.id = al.destination_office_id
                 LEFT JOIN users actor ON actor.id = al.user_id
                 LEFT JOIN offices actor_office ON actor_office.id = actor.office_id
                 WHERE al.document_id = :document_id
                 ORDER BY al.created_at ASC, al.id ASC'
            );
            $activityStmt->execute(['document_id' => (int)$document['id']]);
            $activityEvents = $activityStmt->fetchAll();

            if ($canViewAttachments) {
                $attachmentStmt = $pdo->prepare(
                    'SELECT
                        da.id,
                        da.file_name,
                        da.uploaded_at,
                        CONCAT(COALESCE(u.first_name, \'\'), \' \', COALESCE(u.last_name, \'\')) AS uploaded_by_name,
                        COALESCE(r.name, \'\') AS uploaded_by_role
                     FROM document_attachments da
                     LEFT JOIN users u ON u.id = da.uploaded_by
                     LEFT JOIN roles r ON r.id = u.role_id
                     WHERE document_id = :document_id
                     ORDER BY da.uploaded_at DESC, da.id DESC'
                );
                $attachmentStmt->execute(['document_id' => (int)$document['id']]);
                $attachments = $attachmentStmt->fetchAll();
            }
        }
    } catch (Throwable $exception) {
        $error = 'Unable to load tracking slip right now.';
        $errorType = 'load_failed';
    }
}

if ($errorType === 'not_found' || $errorType === 'missing_tracking') {
    http_response_code(404);
} elseif ($errorType === 'load_failed') {
    http_response_code(500);
}

if ($document) {
    // Rebuild original and latest subject labels from subject-change audit remarks.
    $currentSubject = trim((string)($document['subject'] ?? ''));
    $subjectRowValue = $currentSubject !== '' ? $currentSubject : '-';
    $latestChangedSubject = $currentSubject;
    $firstChangedSubject = '';

    foreach ($activityEvents as $event) {
        $parsedSubjectChange = tracking_parse_subject_change_remarks((string)($event['remarks'] ?? ''));
        if (!is_array($parsedSubjectChange)) {
            continue;
        }

        $fromSubject = trim((string)($parsedSubjectChange['from'] ?? ''));
        $toSubject = trim((string)($parsedSubjectChange['to'] ?? ''));
        if ($firstChangedSubject === '' && $fromSubject !== '') {
            $firstChangedSubject = $fromSubject;
        }
        if ($toSubject !== '') {
            $latestChangedSubject = $toSubject;
        }
    }

    if ($firstChangedSubject !== '') {
        $subjectRowValue = $firstChangedSubject;
    }

    if (
        $subjectRowValue !== ''
        && $latestChangedSubject !== ''
        && strcasecmp($subjectRowValue, $latestChangedSubject) !== 0
    ) {
        $showNewSubjectRow = true;
        $newSubjectRowValue = $latestChangedSubject;
    }
}

$publicSlipLink = $trackingId === ''
    ? ''
    : app_public_url('tracking-slip.php') . '?tracking_id=' . rawurlencode($trackingId) . '&public=1';
$qrText = $trackingId === ''
    ? ''
    : ($publicSlipLink !== '' ? $publicSlipLink : $trackingId);
$packagePrintUrl = app_url('print-package.php')
    . ($trackingId !== '' ? '?tracking_id=' . rawurlencode($trackingId) . '&autoprint=1' : '');
$hasPrintableSlip = ($document !== null && $error === '');
$errorHeading = 'Tracking slip unavailable';
$errorDescription = $error;
$errorHint = '';

if ($errorType === 'missing_tracking') {
    $errorHeading = 'Tracking ID is required';
    $errorDescription = $publicMode
        ? 'Open this page with a valid tracking ID to view the public tracking slip.'
        : 'Enter a tracking ID above to open a document tracking slip.';
    $errorHint = $publicMode
        ? 'Check the tracking link you received, then try again.'
        : 'You can search from the dashboard and reopen the slip from the document actions.';
} elseif ($errorType === 'empty_documents') {
    $errorHeading = 'No tracking slips yet';
    $errorDescription = 'There are no documents available to display right now.';
    $errorHint = 'Create the first document in DTMIS to generate a tracking slip.';
} elseif ($errorType === 'not_found') {
    $errorHeading = 'Tracking ID not found';
    $errorDescription = $trackingId !== ''
        ? 'We could not find a document that matches tracking ID ' . $trackingId . '.'
        : 'We could not find a document that matches the requested tracking ID.';
    $errorHint = $publicMode
        ? 'Please verify the tracking ID from your notice or email, then try the link again.'
        : 'Check the tracking ID format or open the document from the dashboard list.';
} elseif ($errorType === 'load_failed') {
    $errorHeading = 'Tracking slip temporarily unavailable';
    $errorDescription = 'The page could not load the tracking slip right now.';
    $errorHint = 'Please try again in a moment. If the problem continues, check the server logs.';
}

// Build display rows by pairing each receive leg with its nearest route/release activity.
$timelineRows = [];
$createdEvent = null;
foreach ($activityEvents as $event) {
    if (strcasecmp(trim((string)($event['action_type'] ?? '')), 'Created') === 0) {
        $createdEvent = $event;
        break;
    }
}

if ($document !== null) {
    $createdAt = trim((string)($createdEvent['created_at'] ?? ($document['created_at'] ?? '')));
    $createdBy = trim((string)($createdEvent['actor_name'] ?? ($document['sender_name'] ?? '')));
    $createdOffice = trim((string)($createdEvent['actor_office'] ?? ($document['originating_office'] ?? '')));
    $createdDestination = tracking_resolve_created_destination_label(
        $pdo,
        $document,
        $createdOffice !== '' ? $createdOffice : '-'
    );
    $createdRemarks = trim((string)($createdEvent['remarks'] ?? ''));
    if ($createdRemarks === '') {
        $createdRemarks = tracking_default_action_text('CREATED');
    }

    if ($createdAt !== '') {
        $timelineRows[] = [
            'received' => $createdAt,
            'from' => $createdOffice !== '' ? $createdOffice : '-',
            'released' => null,
            'to' => $createdDestination,
            'status' => tracking_status_from_action_type('CREATED'),
            'action' => $createdRemarks,
            'received_by' => $createdBy,
            'received_by_prefix' => 'Created by',
            'route_action_type' => 'Created',
            'route_log_id' => (int)($createdEvent['id'] ?? 0),
            'route_actor' => $createdBy,
            'route_remarks' => $createdRemarks,
            'route_timestamp' => $createdAt,
            'sort_rank' => 10,
            'row_activity_logs' => [],
            'row_attachments' => [],
            'sort_ts' => $createdAt,
        ];
    }
}

$usedReleaseEventIds = [];
foreach ($slips as $index => $slip) {
    $release = null;
    $releaseKey = null;
    $rowReleaseTimestamp = null;
    $rowReleaseTimestampTs = null;
    $receiveEvent = null;
    $receivedAtTs = strtotime((string)($slip['date_time_received'] ?? ''));
    $receivingOfficeId = (int)($slip['receiving_office_id'] ?? 0);
    $receivingOfficeName = trim((string)($slip['receiving_office'] ?? ''));

    foreach ($releaseEvents as $eventKey => $event) {
        $eventId = (int)($event['id'] ?? 0);
        if ($eventId > 0 && isset($usedReleaseEventIds[$eventId])) {
            continue;
        }

        $eventActionType = strtoupper(trim((string)($event['action_type'] ?? '')));

        $eventDestinationOfficeId = (int)($event['destination_office_id'] ?? 0);
        $eventDestinationOffice = trim((string)($event['destination_office'] ?? ''));
        if ($receivingOfficeId > 0 && $eventDestinationOfficeId > 0 && $eventDestinationOfficeId !== $receivingOfficeId) {
            continue;
        }
        if ($receivingOfficeId > 0 && $eventDestinationOfficeId <= 0 && $receivingOfficeName !== '' && $eventDestinationOffice !== '' && strcasecmp($eventDestinationOffice, $receivingOfficeName) !== 0) {
            continue;
        }

        $eventTs = strtotime((string)($event['created_at'] ?? ''));
        if ($eventTs === false) {
            continue;
        }

        if ($receivedAtTs !== false && $eventTs > ($receivedAtTs + 120)) {
            continue;
        }

        if ($rowReleaseTimestampTs === null || $eventTs >= $rowReleaseTimestampTs) {
            $rowReleaseTimestampTs = $eventTs;
            $rowReleaseTimestamp = (string)($event['created_at'] ?? '');
        }

        if (in_array($eventActionType, ['RELEASED', 'SIGNED'], true)) {
            // Terminal actions are rendered as standalone rows,
            // but we still keep their timestamp for the receive leg release column.
            continue;
        }

        if ($release === null) {
            $release = $event;
            $releaseKey = $eventKey;
            continue;
        }

        $releaseTs = strtotime((string)($release['created_at'] ?? ''));
        if ($releaseTs === false || $eventTs >= $releaseTs) {
            $release = $event;
            $releaseKey = $eventKey;
        }
    }

    if ($receivedAtTs !== false) {
        $receivedByName = trim((string)($slip['received_by'] ?? ''));
        foreach ($activityEvents as $event) {
            $eventActionType = strtoupper(trim((string)($event['action_type'] ?? '')));
            if (!in_array($eventActionType, ['RECEIVED', 'RECIEVED', 'COMPLETED'], true)) {
                continue;
            }

            $eventTs = strtotime((string)($event['created_at'] ?? ''));
            if ($eventTs === false || abs($eventTs - $receivedAtTs) > 120) {
                continue;
            }

            if ($receivingOfficeName !== '') {
                $eventDestinationOffice = trim((string)($event['destination_office'] ?? ''));
                if ($eventDestinationOffice !== '' && strcasecmp($eventDestinationOffice, $receivingOfficeName) !== 0) {
                    continue;
                }
            }

            if ($receivedByName !== '') {
                $eventActor = trim((string)($event['actor_name'] ?? ''));
                if ($eventActor !== '' && strcasecmp($eventActor, $receivedByName) !== 0) {
                    continue;
                }
            }

            $receiveEvent = $event;
            if ($eventActionType === 'COMPLETED') {
                break;
            }
        }
    }

    if ($release !== null) {
        $releaseId = (int)($release['id'] ?? 0);
        if ($releaseId > 0) {
            $usedReleaseEventIds[$releaseId] = true;
        } elseif ($releaseKey !== null) {
            $usedReleaseEventIds['idx_' . (string)$releaseKey] = true;
        }
    }

    $actionTaken = trim((string)($slip['action_required'] ?? ''));
    if ($actionTaken === '' || strcasecmp($actionTaken, 'received') === 0) {
        $actionTaken = trim((string)($release['remarks'] ?? ''));
    }
    if ($actionTaken === '') {
        $actionTaken = '-';
    }
    $statusLabel = workflow_resolve_display_status(
        tracking_status_from_action_type((string)($receiveEvent['action_type'] ?? 'Received'))
    );

    $timelineRows[] = [
        'received' => (string)$slip['date_time_received'],
        'from' => (string)($slip['from_office'] ?: '-'),
        'released' => $release['created_at'] ?? $rowReleaseTimestamp,
        'to' => (string)($slip['receiving_office'] ?: '-'),
        'status' => $statusLabel,
        'action' => $actionTaken,
        'received_by' => trim((string)($slip['received_by'] ?: '')),
        'received_by_prefix' => 'Received by',
        'route_action_type' => trim((string)($release['action_type'] ?? '')),
        'route_log_id' => (int)($release['id'] ?? 0),
        'route_actor' => trim((string)($release['actor_name'] ?? '')),
        'route_remarks' => trim((string)($release['remarks'] ?? '')),
        'route_timestamp' => (string)($release['created_at'] ?? ''),
        'row_activity_logs' => [],
        'row_attachments' => [],
        'sort_ts' => (string)($slip['date_time_received'] ?? ''),
        'sort_rank' => 40,
    ];
}

// Add standalone routing/action rows so the slip explicitly shows forwarded/returned/released milestones.
foreach ($releaseEvents as $event) {
    $actionType = strtoupper(trim((string)($event['action_type'] ?? '')));
    if ($actionType === '') {
        continue;
    }

    $statusLabel = tracking_status_from_action_type($actionType);
    $actionTaken = trim((string)($event['remarks'] ?? ''));
    if ($actionTaken === '') {
        $actionTaken = tracking_default_action_text($actionType);
    }

    $actorOffice = trim((string)($event['actor_office'] ?? ''));
    $destinationOffice = trim((string)($event['destination_office'] ?? ''));
    $actorName = trim((string)($event['actor_name'] ?? ''));

    $timelineRows[] = [
        'received' => null,
        'from' => $actorOffice !== '' ? $actorOffice : '-',
        'released' => (string)($event['created_at'] ?? ''),
        'to' => $destinationOffice !== '' ? $destinationOffice : ($actorOffice !== '' ? $actorOffice : '-'),
        'status' => $statusLabel,
        'action' => $actionTaken,
        'received_by' => $actorName,
        'received_by_prefix' => 'Action by',
        'route_action_type' => (string)($event['action_type'] ?? ''),
        'route_log_id' => (int)($event['id'] ?? 0),
        'route_actor' => $actorName,
        'route_remarks' => (string)($event['remarks'] ?? ''),
        'route_timestamp' => (string)($event['created_at'] ?? ''),
        'row_activity_logs' => [],
        'row_attachments' => [],
        'sort_ts' => (string)($event['created_at'] ?? ''),
        'sort_rank' => 20,
    ];

}

usort($timelineRows, static function (array $left, array $right): int {
    $leftTs = strtotime((string)($left['sort_ts'] ?? ''));
    $rightTs = strtotime((string)($right['sort_ts'] ?? ''));
    if ($leftTs !== false && $rightTs !== false) {
        $compare = $leftTs <=> $rightTs;
        if ($compare !== 0) {
            return $compare;
        }
        return ((int)($left['sort_rank'] ?? 100)) <=> ((int)($right['sort_rank'] ?? 100));
    }
    if ($leftTs !== false) {
        return -1;
    }
    if ($rightTs !== false) {
        return 1;
    }
    return strcmp((string)($left['sort_ts'] ?? ''), (string)($right['sort_ts'] ?? ''));
});

if (!empty($timelineRows)) {
    $dedupedTimelineRows = [];
    $terminalRowSignatureIndex = [];
    foreach ($timelineRows as $row) {
        $status = strtoupper(trim((string)($row['status'] ?? '')));
        $isTerminalActionRow = in_array($status, ['RELEASED', 'SIGNED/COMPLETED'], true)
            && strtoupper(trim((string)($row['received_by_prefix'] ?? ''))) === 'ACTION BY';

        if (!$isTerminalActionRow) {
            $dedupedTimelineRows[] = $row;
            continue;
        }

        $signature = implode('|', [
            $status,
            strtoupper(trim((string)($row['from'] ?? ''))),
            strtoupper(trim((string)($row['to'] ?? ''))),
            trim((string)($row['action'] ?? '')),
            strtoupper(trim((string)($row['received_by'] ?? ''))),
        ]);

        if (!isset($terminalRowSignatureIndex[$signature])) {
            $terminalRowSignatureIndex[$signature] = count($dedupedTimelineRows);
            $dedupedTimelineRows[] = $row;
            continue;
        }

        // Keep the latest duplicate terminal row (rows are sorted ascending by timestamp).
        $existingIndex = (int)$terminalRowSignatureIndex[$signature];
        $dedupedTimelineRows[$existingIndex] = $row;
    }

    $timelineRows = array_values($dedupedTimelineRows);
}

if (!empty($timelineRows)) {
    // Attach detailed activity logs per visible row using a time-window match.
    $filledRowIndexes = [];
    foreach ($timelineRows as $index => $row) {
        $receivedValue = trim((string)($row['received'] ?? ''));
        $actionValue = trim((string)($row['action'] ?? ''));
        if ($receivedValue === '' && $actionValue === '') {
            continue;
        }
        $filledRowIndexes[] = (int)$index;
    }

    if (!empty($filledRowIndexes)) {
        $activityById = [];
        foreach ($activityEvents as $event) {
            $eventId = (int)($event['id'] ?? 0);
            if ($eventId > 0) {
                $activityById[$eventId] = $event;
            }
        }

        foreach ($filledRowIndexes as $position => $rowIndex) {
            $row = $timelineRows[$rowIndex];
            $rowTs = strtotime((string)($row['sort_ts'] ?? $row['received'] ?? ''));
            if ($rowTs === false) {
                $rowTs = strtotime((string)($row['received'] ?? ''));
            }

            $nextTs = null;
            if (isset($filledRowIndexes[$position + 1])) {
                $nextIndex = (int)$filledRowIndexes[$position + 1];
                $nextRow = $timelineRows[$nextIndex] ?? [];
                $parsedNextTs = strtotime((string)($nextRow['sort_ts'] ?? $nextRow['received'] ?? ''));
                if ($parsedNextTs !== false) {
                    $nextTs = $parsedNextTs;
                }
            }

            $windowStart = $rowTs !== false ? max(0, $rowTs - 300) : null;
            $windowEnd = $nextTs !== null ? ($nextTs + 1) : null;

            $rowLogs = [];
            $logSignatures = [];
            $addRowLog = static function (array $event) use (&$rowLogs, &$logSignatures): void {
                $eventId = (int)($event['id'] ?? 0);
                $signature = $eventId > 0
                    ? 'id_' . (string)$eventId
                    : implode('|', [
                        trim((string)($event['created_at'] ?? '')),
                        strtoupper(trim((string)($event['action_type'] ?? ''))),
                        strtoupper(trim((string)($event['actor_name'] ?? ''))),
                        trim((string)($event['remarks'] ?? '')),
                    ]);
                if (isset($logSignatures[$signature])) {
                    return;
                }
                $logSignatures[$signature] = true;
                $rowLogs[] = $event;
            };

            $routeLogId = (int)($row['route_log_id'] ?? 0);
            if ($routeLogId > 0 && isset($activityById[$routeLogId])) {
                $addRowLog($activityById[$routeLogId]);
            }

            foreach ($activityEvents as $event) {
                $eventTs = strtotime((string)($event['created_at'] ?? ''));
                if ($eventTs === false) {
                    continue;
                }
                if ($windowStart !== null && $eventTs < $windowStart) {
                    continue;
                }
                if ($windowEnd !== null && $eventTs >= $windowEnd) {
                    continue;
                }
                $addRowLog($event);
            }

            $receivedAtRaw = trim((string)($row['received'] ?? ''));
            $receivedByName = trim((string)($row['received_by'] ?? ''));
            $receivedAtTs = strtotime($receivedAtRaw);
            if ($receivedAtRaw !== '' && $receivedAtTs !== false) {
                $hasReceiveLog = false;
                foreach ($rowLogs as $event) {
                    $actionType = strtoupper(trim((string)($event['action_type'] ?? '')));
                    if ($actionType !== 'RECEIVED') {
                        continue;
                    }
                    $eventTs = strtotime((string)($event['created_at'] ?? ''));
                    if ($eventTs === false || abs($eventTs - $receivedAtTs) > 120) {
                        continue;
                    }
                    if ($receivedByName !== '') {
                        $eventActor = trim((string)($event['actor_name'] ?? ''));
                        if ($eventActor !== '' && strcasecmp($eventActor, $receivedByName) !== 0) {
                            continue;
                        }
                    }
                    $hasReceiveLog = true;
                    break;
                }

                if (!$hasReceiveLog) {
                    $addRowLog([
                        'id' => 0,
                        'created_at' => $receivedAtRaw,
                        'action_type' => 'Received',
                        'action_scope' => 'CUSTODY',
                        'remarks' => '',
                        'destination_office' => (string)($row['to'] ?? ''),
                        'actor_name' => $receivedByName,
                        'actor_office' => '',
                    ]);
                }
            }

            usort($rowLogs, static function (array $left, array $right): int {
                $leftTs = strtotime((string)($left['created_at'] ?? ''));
                $rightTs = strtotime((string)($right['created_at'] ?? ''));
                if ($leftTs !== false && $rightTs !== false) {
                    if ($leftTs === $rightTs) {
                        return ((int)($right['id'] ?? 0)) <=> ((int)($left['id'] ?? 0));
                    }
                    return $rightTs <=> $leftTs;
                }
                if ($leftTs !== false) {
                    return 1;
                }
                if ($rightTs !== false) {
                    return -1;
                }
                return 0;
            });

            $normalizedLogs = [];
            foreach ($rowLogs as $event) {
                $actionType = trim((string)($event['action_type'] ?? ''));
                if ($actionType === '') {
                    $actionType = trim((string)($event['action_scope'] ?? ''));
                }
                if ($actionType === '') {
                    $actionType = 'Action';
                }

                $logRemarks = trim((string)($event['remarks'] ?? ''));
                $subjectChange = tracking_parse_subject_change_remarks($logRemarks);
                if (is_array($subjectChange)) {
                    $fromSubject = trim((string)($subjectChange['from'] ?? ''));
                    $toSubject = trim((string)($subjectChange['to'] ?? ''));
                    $logRemarks = 'Subject changed'
                        . ($fromSubject !== '' ? ': ' . $fromSubject : '')
                        . ($toSubject !== '' ? ' -> ' . $toSubject : '');
                }

                $normalizedLogs[] = [
                    'time' => (string)($event['created_at'] ?? ''),
                    'action' => $actionType,
                    'by' => trim((string)($event['actor_name'] ?? '')),
                    'office' => trim((string)($event['actor_office'] ?? '')),
                    'to' => trim((string)($event['destination_office'] ?? '')),
                    'remarks' => $logRemarks,
                ];
            }

            $normalizedAttachments = [];
            if ($canViewAttachments && !empty($attachments)) {
                $attachmentWindowStart = $rowTs !== false ? max(0, $rowTs - 1800) : null;
                $attachmentWindowEnd = $nextTs !== null ? ($nextTs + 300) : null;
                $seenAttachmentIds = [];

                foreach ($attachments as $attachment) {
                    $attachmentId = (int)($attachment['id'] ?? 0);
                    if ($attachmentId > 0 && isset($seenAttachmentIds[$attachmentId])) {
                        continue;
                    }

                    $uploadedAtRaw = (string)($attachment['uploaded_at'] ?? '');
                    $uploadedAtTs = strtotime($uploadedAtRaw);
                    if ($uploadedAtTs === false) {
                        continue;
                    }
                    if ($attachmentWindowStart !== null && $uploadedAtTs < $attachmentWindowStart) {
                        continue;
                    }
                    if ($attachmentWindowEnd !== null && $uploadedAtTs >= $attachmentWindowEnd) {
                        continue;
                    }

                    $fileName = trim((string)($attachment['file_name'] ?? 'Attachment'));
                    $category = tracking_attachment_category($fileName);
                    $previewType = tracking_attachment_preview_type($fileName);
                    $previewUrl = $attachmentId > 0
                        ? app_url('actions/attachment-file.php?attachment_id=' . $attachmentId)
                        : '';
                    $normalizedAttachments[] = [
                        'id' => $attachmentId,
                        'file_name' => $fileName !== '' ? $fileName : 'Attachment',
                        'uploaded_at' => $uploadedAtRaw,
                        'uploaded_by' => trim((string)($attachment['uploaded_by_name'] ?? '')),
                        'uploaded_by_role' => trim((string)($attachment['uploaded_by_role'] ?? '')),
                        'category' => $category,
                        'category_label' => tracking_attachment_category_label($category),
                        'preview_type' => $previewType,
                        'preview_url' => $previewUrl,
                    ];

                    if ($attachmentId > 0) {
                        $seenAttachmentIds[$attachmentId] = true;
                    }
                }

                usort($normalizedAttachments, static function (array $left, array $right): int {
                    $leftTs = strtotime((string)($left['uploaded_at'] ?? ''));
                    $rightTs = strtotime((string)($right['uploaded_at'] ?? ''));
                    if ($leftTs !== false && $rightTs !== false) {
                        return $rightTs <=> $leftTs;
                    }
                    if ($leftTs !== false) {
                        return -1;
                    }
                    if ($rightTs !== false) {
                        return 1;
                    }
                    return 0;
                });
            }

            $timelineRows[$rowIndex]['row_activity_logs'] = $normalizedLogs;
            $timelineRows[$rowIndex]['row_attachments'] = $normalizedAttachments;
        }
    }
}

$minimumTimelineRows = 0;
$renderedTimelineRowCount = max(count($timelineRows), $minimumTimelineRows);
if (count($timelineRows) < $minimumTimelineRows) {
    $missing = $minimumTimelineRows - count($timelineRows);
    for ($i = 0; $i < $missing; $i++) {
        $timelineRows[] = [
            'received' => null,
            'from' => '',
            'released' => null,
            'to' => '',
            'status' => '',
            'action' => '',
            'received_by' => '',
            'received_by_prefix' => 'Received by',
            'route_action_type' => '',
            'route_log_id' => 0,
            'route_actor' => '',
            'route_remarks' => '',
            'route_timestamp' => '',
            'row_activity_logs' => [],
            'row_attachments' => [],
            'sort_ts' => '',
        ];
    }
}
$printTargetTimelineRows = 14;
$layoutDefaultFontPercent = 100;
$layoutDefaultLogoScreenPx = 100;
$layoutDefaultLogoPrintPx = 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Slip<?php echo $trackingId !== '' ? ' | ' . e($trackingId) : ''; ?></title>
    <style>
        :root {
            --bg: #d7dbe1;
            --paper: #ffffff;
            --ink: #1c1c1c;
            --line: #1a1a1a;
            --card: #ffffff;
            --card-line: #b6c8df;
            --btn-ghost: #f5f8ff;
            --btn-ghost-text: #314f74;
            --muted: #476382;
            --text-ui: #1c1c1c;
            --tracking-font-scale: <?php echo e(number_format($layoutDefaultFontPercent / 100, 2, '.', '')); ?>;
            --tracking-logo-screen-size: <?php echo e((string)$layoutDefaultLogoScreenPx); ?>px;
            --tracking-logo-print-size: <?php echo e((string)$layoutDefaultLogoPrintPx); ?>px;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #0b1421;
                --paper: #f1f5f9;
                --card: #152233;
                --card-line: #26354a;
                --btn-ghost: rgba(59, 130, 246, 0.12);
                --btn-ghost-text: #60a5fa;
                --muted: #94a3b8;
                --text-ui: #f1f5f9;
            }
        }

        [data-theme="dark"] {
            --bg: #0b1421;
            --paper: #f1f5f9;
            --card: #152233;
            --card-line: #26354a;
            --btn-ghost: rgba(59, 130, 246, 0.12);
            --btn-ghost-text: #60a5fa;
            --muted: #94a3b8;
            --text-ui: #f1f5f9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text-ui);
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            padding: 18px;
        }
        .toolbar {
            max-width: 794px;
            margin: 0 auto 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .toolbar form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .toolbar input[type="text"] {
            height: 36px;
            min-width: 280px;
            border: 1px solid var(--card-line);
            background: var(--card);
            color: var(--text-ui);
            border-radius: 8px;
            padding: 0 10px;
            font-size: 14px;
        }
        .toolbar button,
        .toolbar a {
            height: 36px;
            border: none;
            border-radius: 8px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .toolbar button {
            background: #2f5fb4;
            color: #fff;
        }
        .toolbar .back-btn {
            background: #3d4d63;
            color: #fff;
        }
        .toolbar .print-btn {
            background: #1f7f8f;
        }
        .toolbar .package-btn {
            background: #245a2a;
            color: #fff;
        }
        .toolbar .login-link {
            background: #f5f8ff;
            color: #334c72;
            border: 1px solid #bfd0ea;
        }
        .layout-controls {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-left: auto;
            border: 1px solid var(--card-line);
            background: var(--btn-ghost);
            border-radius: 9px;
            padding: 5px 7px;
        }
        .layout-controls .layout-title {
            font-size: 12px;
            font-weight: 800;
            color: var(--btn-ghost-text);
            margin-right: 2px;
        }
        .layout-controls label {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            color: var(--btn-ghost-text);
            white-space: nowrap;
        }
        .layout-controls input[type="number"] {
            width: 62px;
            height: 28px;
            border: 1px solid var(--card-line);
            border-radius: 6px;
            padding: 0 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-ui);
            background: var(--card);
        }
        .layout-controls .layout-reset-btn {
            height: 28px;
            border: 1px solid var(--card-line);
            border-radius: 6px;
            background: var(--card);
            color: var(--text-ui);
            padding: 0 8px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }
        .flow-details-wrap {
            position: relative;
            display: inline-flex;
        }
        .flow-details-btn {
            background: #6f5d1f;
            color: #fff;
            border: none;
            border-radius: 8px;
            height: 36px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .flow-details-popover {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: min(440px, 92vw);
            max-height: 280px;
            overflow: auto;
            border: 1px solid var(--card-line);
            border-radius: 10px;
            background: var(--card);
            box-shadow: 0 12px 26px rgba(12, 20, 31, 0.2);
            padding: 10px;
            display: none;
            z-index: 20;
        }
        .flow-details-wrap:hover .flow-details-popover,
        .flow-details-wrap:focus-within .flow-details-popover {
            display: grid;
            gap: 8px;
        }
        .flow-detail-item {
            border: 1px solid var(--card-line);
            border-radius: 8px;
            padding: 8px;
            display: grid;
            gap: 2px;
            font-size: 12px;
            color: var(--muted);
            background: var(--bg);
        }
        .flow-detail-item strong {
            font-size: 12px;
            color: var(--text-ui);
        }
        .flow-detail-item em {
            font-style: normal;
            color: var(--muted);
        }

        .slip-viewport {
            width: 100%;
        }

        .slip-paper {
            width: min(794px, 100%);
            margin: 0 auto;
            background: var(--paper);
            color: var(--ink);
            border: 2px solid var(--line);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
            padding: 8px;
        }
        .print-pages {
            display: none;
        }
        .print-page {
            width: 8.5in;
            min-height: 13in;
            margin: 0 auto 12px;
            background: #fff;
            color: #1c1c1c;
            border: 1px solid var(--line);
            box-shadow: none;
            padding: 5mm 5mm 0 5mm;
            box-sizing: border-box;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }
        .print-page:last-child {
            page-break-after: auto;
            break-after: auto;
        }
        .print-page-row-gap {
            display: none;
        }
        .print-page.print-page-continuation .timeline-wrap {
            margin-top: 4mm;
        }
        .print-page.print-page-continuation .print-page-row-gap {
            display: block;
            height: 6mm;
        }

        .slip-header {
            display: grid;
            grid-template-columns: 92px 1fr 158px;
            border: 2px solid var(--line);
            min-height: 118px;
        }
        .logo-cell,
        .agency-cell,
        .qr-cell {
            border-right: 2px solid var(--line);
            padding: 8px;
        }
        .logo-cell {
            border-right: none;
        }
        .qr-cell { border-right: none; }
        .logo-cell {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .logo-cell img {
            width: var(--tracking-logo-screen-size);
            height: var(--tracking-logo-screen-size);
            object-fit: contain;
        }
        .agency-cell h2 {
            margin: 0;
            font-size: 26px;
            line-height: 1.08;
            letter-spacing: 0.1px;
        }
        .agency-cell p {
            margin: 2px 0;
            font-size: 12px;
        }
        .agency-cell .agency-small {
            font-size: 11px;
        }
        .qr-cell {
            display: grid;
            grid-template-rows: 1fr auto;
            gap: 4px;
            justify-items: center;
            align-items: center;
        }
        .qr-cell img {
            width: 132px;
            height: 132px;
            border: 1px solid var(--line);
            background: #fff;
        }
        .qr-label {
            font-size: 10px;
            text-align: center;
            font-weight: 700;
            word-break: break-all;
        }

        .title-strip {
            border: 2px solid var(--line);
            border-top: none;
            text-align: center;
            font-weight: 800;
            letter-spacing: 0.8px;
            font-size: 20px;
            padding: 5px 6px;
        }

        .meta-grid {
            border: 2px solid var(--line);
            border-top: none;
        }
        .meta-row {
            display: grid;
            grid-template-columns: 170px 1fr 86px 80px 74px 140px;
            border-bottom: 2px solid var(--line);
            min-height: 42px;
        }
        .meta-row:last-child {
            border-bottom: none;
        }
        .meta-cell {
            padding: 7px 8px;
            border-right: 2px solid var(--line);
            font-size: 15px;
            display: flex;
            align-items: center;
        }
        .meta-cell:last-child {
            border-right: none;
        }
        .meta-label {
            font-weight: 800;
            background: rgba(255, 255, 255, 0.22);
        }
        .subject-row {
            display: grid;
            grid-template-columns: 170px 1fr;
            border-bottom: 2px solid var(--line);
            min-height: 98px;
        }
        .subject-row .meta-cell {
            align-items: flex-start;
            padding-top: 8px;
        }
        .new-subject-row {
            display: grid;
            grid-template-columns: 170px 1fr;
            border-bottom: 2px solid var(--line);
            min-height: 52px;
        }
        .new-subject-row .meta-cell {
            align-items: flex-start;
            padding-top: 8px;
        }

        .timeline-wrap {
            border: 2px solid var(--line);
            border-top: none;
        }
        .timeline-scroll {
            overflow: auto;
            max-height: 520px;
            border: 2px solid var(--line);
            border-top: none;
        }
        .timeline-scroll .timeline-wrap {
            border: none;
        }
        .timeline-head,
        .timeline-row {
            display: grid;
            grid-template-columns: 140px 116px 140px 116px 82px 1.55fr;
        }
        .timeline-head {
            background: rgba(255, 255, 255, 0.22);
            border-bottom: none;
            font-weight: 700;
            min-height: 40px;
        }
        .timeline-cell {
            border-right: 2px solid var(--line);
            padding: 5px 7px;
            font-size: 13px;
            overflow-wrap: anywhere;
        }
        .timeline-head .timeline-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
        }
        .timeline-cell:last-child {
            border-right: none;
        }
        .timeline-row {
            min-height: 72px;
            border-bottom: none;
            position: relative;
        }
        .timeline-row:last-child {
            border-bottom: none;
        }
        .timeline-row.has-flow-details:hover {
            background: #f9fcff;
        }
        .cell-main {
            font-weight: 700;
            line-height: 1.2;
        }
        .cell-sub {
            font-size: 11px;
            margin-top: 3px;
            color: #2f2f2f;
        }
        .timeline-row.empty .timeline-cell {
            color: transparent;
        }
        .timeline-cell-action {
            position: relative;
            overflow: visible;
        }
        .row-popover-triggers {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .row-popover-trigger {
            position: relative;
        }
        .row-flow-hint {
            font-size: 9px;
            color: #5a6f84;
            font-weight: 600;
            letter-spacing: 0.15px;
            border: 1px solid #c9d7e6;
            border-radius: 999px;
            padding: 2px 7px;
            background: #f2f8ff;
            cursor: default;
            white-space: nowrap;
        }
        .row-preview-hint {
            font-size: 9px;
            color: #5a6f84;
            font-weight: 600;
            letter-spacing: 0.15px;
            border: 1px solid #c9d7e6;
            border-radius: 999px;
            padding: 2px 7px;
            background: #f8f5ff;
            cursor: default;
            white-space: nowrap;
        }
        .row-flow-popover {
            position: absolute;
            top: calc(100% + 2px);
            right: 0;
            left: auto;
            width: min(420px, calc(100vw - 56px));
            max-height: 300px;
            overflow: auto;
            border: 1px solid #c9d3dd;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 12px 26px rgba(12, 20, 31, 0.2);
            padding: 10px;
            display: none;
            z-index: 24;
            font-size: 12px;
            color: #2b425a;
        }
        .row-preview-popover {
            position: absolute;
            top: calc(100% + 2px);
            right: 0;
            width: min(440px, calc(100vw - 56px));
            max-height: 320px;
            overflow: auto;
            border: 1px solid #c9d3dd;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 12px 26px rgba(12, 20, 31, 0.2);
            padding: 10px;
            display: none;
            z-index: 25;
            font-size: 12px;
            color: #2b425a;
        }
        .row-flow-popover::before {
            content: "";
            position: absolute;
            left: -18px;
            right: -18px;
            top: -16px;
            height: 16px;
            background: transparent;
        }
        .row-preview-popover::before {
            content: "";
            position: absolute;
            left: -18px;
            right: -18px;
            top: -16px;
            height: 16px;
            background: transparent;
        }
        .row-flow-popover strong {
            color: #1a3552;
        }
        .row-flow-popover-title {
            font-weight: 800;
            color: #102a45;
            margin-bottom: 6px;
        }
        .row-flow-log-list {
            display: grid;
            gap: 6px;
        }
        .row-flow-log-item {
            border: 1px solid #d6e0ea;
            border-radius: 8px;
            padding: 6px 7px;
            background: #f8fbff;
            display: grid;
            gap: 2px;
        }
        .row-flow-log-item-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
        }
        .row-flow-log-time {
            font-size: 11px;
            color: #3b536d;
            font-weight: 600;
            white-space: nowrap;
        }
        .row-flow-attachment-wrap {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px dashed #d2deea;
            display: grid;
            gap: 5px;
        }
        .row-flow-attachment-empty {
            font-size: 11px;
            color: #516a84;
        }
        .row-flow-attachment-list {
            display: grid;
            gap: 6px;
        }
        .row-flow-attachment-item {
            border: 1px solid #d6e0ea;
            border-radius: 8px;
            padding: 6px 7px;
            background: #fefefe;
            display: grid;
            gap: 2px;
        }
        .row-flow-attachment-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
        }
        .row-flow-attachment-type {
            font-size: 11px;
            font-weight: 700;
            color: #153a61;
            background: #eaf3ff;
            border: 1px solid #cbdff8;
            border-radius: 999px;
            padding: 1px 7px;
        }
        .row-flow-attachment-name {
            font-weight: 700;
            color: #233b55;
            overflow-wrap: anywhere;
        }
        .row-flow-attachment-meta {
            font-size: 11px;
            color: #48617a;
        }
        .row-flow-attachment-preview {
            margin-top: 4px;
            border: 1px solid #d5dfeb;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .row-flow-attachment-preview img {
            display: block;
            width: 100%;
            max-height: 180px;
            object-fit: contain;
            background: #f5f9ff;
        }
        .row-flow-attachment-preview iframe {
            display: block;
            width: 100%;
            height: 180px;
            border: 0;
            background: #f5f9ff;
        }
        .row-flow-attachment-preview-empty {
            font-size: 11px;
            color: #5b7186;
            padding: 6px 7px;
            background: #f8fbff;
            border: 1px dashed #d2deea;
            border-radius: 7px;
        }
        .timeline-row.has-flow-details .row-popover-trigger:hover .row-flow-popover,
        .timeline-row.has-flow-details .row-popover-trigger:focus-within .row-flow-popover,
        .timeline-row.has-flow-details .row-flow-popover:hover {
            display: grid;
            gap: 4px;
        }
        .timeline-row.has-flow-details .row-popover-trigger:hover .row-preview-popover,
        .timeline-row.has-flow-details .row-popover-trigger:focus-within .row-preview-popover,
        .timeline-row.has-flow-details .row-preview-popover:hover {
            display: grid;
            gap: 4px;
        }

        .attachment-vault {
            border: 2px solid var(--line);
            border-top: none;
            padding: 8px;
            background: rgba(255, 255, 255, 0.12);
        }
        .attachment-vault h3 {
            margin: 0 0 6px;
            font-size: 14px;
            letter-spacing: 0.3px;
        }
        .attachment-vault p,
        .attachment-vault li {
            margin: 0;
            font-size: 12px;
        }
        .attachment-vault ul {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 4px;
        }
        .hint {
            margin-top: 8px;
            font-size: 11px;
            font-style: italic;
            opacity: 0.85;
        }
        .error {
            width: min(794px, 100%);
            margin: 0 auto;
            background: #fff3f3;
            border: 1px solid #e4a7a7;
            color: #7a1d1d;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
        }
        .state-card {
            width: min(794px, 100%);
            margin: 0 auto;
            padding: 22px 24px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background:
                radial-gradient(circle at top right, rgba(96, 165, 250, 0.18), transparent 34%),
                linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(15, 23, 42, 0.9));
            box-shadow: 0 22px 55px rgba(15, 23, 42, 0.24);
        }
        .state-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(248, 113, 113, 0.14);
            border: 1px solid rgba(248, 113, 113, 0.28);
            color: #fecaca;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .state-card h1 {
            margin: 14px 0 8px;
            color: #f8fafc;
            font-size: clamp(24px, 4vw, 34px);
            line-height: 1.12;
        }
        .state-card p {
            margin: 0;
            max-width: 62ch;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.65;
        }
        .state-hint {
            margin-top: 12px !important;
            color: #93c5fd !important;
            font-size: 14px !important;
        }
        .state-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }
        .state-actions a,
        .state-actions button {
            height: 40px;
            border-radius: 10px;
            padding: 0 14px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .state-actions button {
            background: #2f5fb4;
            color: #fff;
        }
        .state-actions .state-secondary {
            background: rgba(148, 163, 184, 0.12);
            border-color: rgba(148, 163, 184, 0.35);
            color: #e2e8f0;
        }
        .state-actions .state-ghost {
            background: rgba(191, 219, 254, 0.1);
            border-color: rgba(191, 219, 254, 0.28);
            color: #bfdbfe;
        }

        @media (max-width: 640px) {
            body {
                padding: 12px;
            }
            .toolbar input[type="text"] {
                min-width: 0;
                width: 100%;
            }
            .layout-controls {
                margin-left: 0;
            }
            .state-card {
                padding: 18px;
            }
            .state-actions {
                flex-direction: column;
            }
            .state-actions a,
            .state-actions button {
                width: 100%;
            }
            .slip-viewport {
                overflow-x: auto;
                overflow-y: visible;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 10px;
            }
            .slip-paper {
                width: 794px;
                min-width: 794px;
                margin: 0;
            }
        }

        @page {
            size: 8.5in 13in;
            margin: 5mm 5mm 0 5mm;
        }
        @media print {
            html,
            body {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }
            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body.has-print-pages .slip-paper {
                display: none;
            }
            body.has-print-pages .print-pages {
                display: block;
            }
            .toolbar {
                display: none;
            }
            .row-popover-triggers,
            .row-flow-hint,
            .row-flow-popover,
            .row-preview-hint,
            .row-preview-popover {
                display: none !important;
            }
            .timeline-head {
                min-height: 34px;
            }
            .timeline-cell {
                font-size: 11px;
                padding: 4px 5px;
            }
            .timeline-row {
                min-height: 58px;
            }
            .cell-sub {
                font-size: 9px;
            }
            .slip-paper {
                margin: 0;
                width: auto;
                min-height: auto;
                padding: 5mm 5mm 0 5mm;
                box-shadow: none;
                overflow: visible;
                box-sizing: border-box;
                border-width: 1px;
            }
            .print-page {
                margin: 0;
                width: auto;
                min-height: auto;
            }
            .slip-header,
            .title-strip,
            .meta-grid,
            .timeline-scroll,
            .timeline-wrap,
            .attachment-vault,
            .meta-row,
            .meta-cell,
            .timeline-cell {
                border-width: 1px;
            }
            .timeline-scroll {
                max-height: none;
                overflow: visible;
            }
            .slip-header {
                grid-template-columns: 82px 1fr 108px;
                min-height: 92px;
            }
            .logo-cell,
            .agency-cell,
            .qr-cell {
                padding: 4px;
                border-right-width: 1px;
            }
            .logo-cell {
                border-right: none;
            }
            .logo-cell img {
                width: var(--tracking-logo-print-size);
                height: var(--tracking-logo-print-size);
            }
            .qr-cell img {
                width: 84px;
                height: 84px;
            }
            .qr-label {
                font-size: calc(9px * var(--tracking-font-scale));
            }
            .agency-cell h2 {
                font-size: calc(19px * var(--tracking-font-scale));
                line-height: 1.05;
            }
            .agency-cell p {
                margin: 1px 0;
                font-size: calc(10.6px * var(--tracking-font-scale));
            }
            .agency-cell .agency-small {
                font-size: calc(9.6px * var(--tracking-font-scale));
            }
            .title-strip {
                font-size: calc(16px * var(--tracking-font-scale));
                letter-spacing: 0.3px;
                padding: 2px 4px;
            }
            .meta-row {
                min-height: 26px;
            }
            .meta-cell {
                font-size: calc(11.6px * var(--tracking-font-scale));
                padding: 3px 5px;
            }
            .meta-label {
                font-size: calc(12px * var(--tracking-font-scale));
                font-weight: 800;
            }
            .subject-row {
                min-height: 50px;
            }
            .new-subject-row {
                min-height: 26px;
            }
            .timeline-head {
                min-height: 30px;
            }
            .timeline-cell {
                font-size: calc(10.6px * var(--tracking-font-scale));
                line-height: 1.1;
                padding: 3px 4px;
            }
            .timeline-head .timeline-cell {
                font-size: calc(10.8px * var(--tracking-font-scale));
                font-weight: 800;
            }
            .timeline-row {
                min-height: 48px;
            }
            .timeline-row .timeline-cell:nth-child(2) .cell-main,
            .timeline-row .timeline-cell:nth-child(4) .cell-main,
            .timeline-row .timeline-cell:nth-child(6) .cell-main {
                display: -webkit-box;
                -webkit-line-clamp: 4;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .cell-main {
                font-size: calc(10.8px * var(--tracking-font-scale));
                line-height: 1.1;
            }
            .cell-sub {
                font-size: calc(9.2px * var(--tracking-font-scale));
                line-height: 1.1;
                margin-top: 2px;
            }
            .attachment-vault {
                padding: 4px 5px;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .attachment-vault h3 {
                font-size: calc(11.5px * var(--tracking-font-scale));
                margin: 0 0 2px;
            }
            .attachment-vault p,
            .attachment-vault li {
                font-size: calc(9.5px * var(--tracking-font-scale));
                line-height: 1.15;
            }
            .attachment-vault ul {
                gap: 1px;
                padding-left: 14px;
            }
            .hint {
                display: none;
            }
            .layout-controls {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <?php if ($publicMode): ?>
        <?php if ($hasPrintableSlip): ?>
        <button type="button" class="print-btn" onclick="window.print()">Print Slip</button>
        <?php endif; ?>
        <a class="login-link" href="<?php echo e(app_url('auth/login.php')); ?>">Login for Additional Details</a>
        <?php else: ?>
        <form method="get" action="">
            <button
                type="button"
                class="back-btn"
                onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '<?php echo e(app_url('dashboard.php')); ?>'; }"
            >Back</button>
            <input type="text" name="tracking_id" value="<?php echo e($trackingId); ?>" placeholder="Enter Tracking ID (e.g., DENR-XII-2026-0001)">
            <button type="submit">Load Tracking Slip</button>
        </form>
        <?php if ($hasPrintableSlip): ?>
        <button type="button" class="print-btn" onclick="window.print()">Print Slip</button>
        <a class="package-btn" href="<?php echo e($packagePrintUrl); ?>">Print Full Package</a>
        <?php endif; ?>
        <?php if ($hasPrintableSlip): ?>
        <div
            class="layout-controls"
            id="layoutControls"
            data-default-font="<?php echo e((string)$layoutDefaultFontPercent); ?>"
            data-default-logo-screen="<?php echo e((string)$layoutDefaultLogoScreenPx); ?>"
            data-default-logo-print="<?php echo e((string)$layoutDefaultLogoPrintPx); ?>"
            data-default-print-rows="<?php echo e((string)$printTargetTimelineRows); ?>"
        >
            <span class="layout-title">Adjust</span>
            <label>Font %
                <input type="number" id="fontScaleInput" min="80" max="145" step="1" value="<?php echo e((string)$layoutDefaultFontPercent); ?>">
            </label>
            <label>Logo S
                <input type="number" id="logoScreenSizeInput" min="50" max="130" step="1" value="<?php echo e((string)$layoutDefaultLogoScreenPx); ?>">
            </label>
            <label>Logo P
                <input type="number" id="logoPrintSizeInput" min="40" max="110" step="1" value="<?php echo e((string)$layoutDefaultLogoPrintPx); ?>">
            </label>
            <label>Rows
                <input type="number" id="printRowsInput" min="0" max="14" step="1" value="<?php echo e((string)$printTargetTimelineRows); ?>">
            </label>
            <button type="button" id="resetLayoutBtn" class="layout-reset-btn">Reset</button>
        </div>
        <?php endif; ?>
        <?php if (!empty($releaseEvents)): ?>
        <div class="flow-details-wrap">
            <button type="button" class="flow-details-btn">Routing Flow Details</button>
            <div class="flow-details-popover" role="tooltip" aria-label="Additional routing details">
                <?php foreach ($releaseEvents as $event): ?>
                <div class="flow-detail-item">
                    <strong><?php echo e((string)($event['action_type'] ?? 'Action')); ?></strong>
                    <span><?php echo e(format_dt((string)($event['created_at'] ?? null))); ?></span>
                    <span>To: <?php echo e((string)($event['destination_office'] ?? '-')); ?></span>
                    <span>By: <?php echo e(trim((string)($event['actor_name'] ?? '')) !== '' ? (string)$event['actor_name'] : 'System'); ?></span>
                    <?php if (trim((string)($event['remarks'] ?? '')) !== ''): ?>
                    <em>Remarks: <?php echo e((string)$event['remarks']); ?></em>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!$canViewAttachments): ?>
        <a class="login-link" href="<?php echo e(app_url('auth/login.php')); ?>">Login To View Attachments</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($error !== ''): ?>
    <section class="state-card" aria-live="polite">
        <div class="state-badge">Tracking Slip</div>
        <h1><?php echo e($errorHeading); ?></h1>
        <p><?php echo e($errorDescription); ?></p>
        <?php if ($errorHint !== ''): ?>
        <p class="state-hint"><?php echo e($errorHint); ?></p>
        <?php endif; ?>
        <div class="state-actions">
            <?php if (!$publicMode): ?>
            <button type="button" onclick="var input = document.querySelector('.toolbar input[name=&quot;tracking_id&quot;]'); if (input) { input.focus(); input.select(); }">Try Another Tracking ID</button>
            <a class="state-secondary" href="<?php echo e(app_url('dashboard.php')); ?>">Return to Dashboard</a>
            <?php else: ?>
            <button type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '<?php echo e(app_public_url('tracking-slip.php')); ?>'; }">Go Back</button>
            <a class="state-ghost" href="<?php echo e(app_url('auth/login.php')); ?>">Login to Search Internally</a>
            <?php endif; ?>
        </div>
    </section>
    <?php elseif ($document): ?>
    <div class="slip-viewport" aria-label="Tracking slip viewport">
    <section class="slip-paper" aria-label="Document Action Tracking Slip">
        <div class="slip-header">
            <div class="logo-cell">
                <img src="./assets/trackingslip-logo.png" alt="DENR Logo">
            </div>
            <div class="agency-cell">
                <p class="agency-small">Republic of the Philippines</p>
                <h2>Department of Environment and Natural Resources</h2>
                <p>Department of Environment and Natural Resources Regional Office XII</p>
                <p>Aurora St., City of Koronadal, South Cotabato, 9506</p>
                <p class="agency-small">Tel No. (083) 228-6225</p>
            </div>
            <div class="qr-cell">
                <img
                    id="trackingSlipQrImage"
                    data-qr-text="<?php echo e($qrText); ?>"
                    alt="QR code for <?php echo e((string)$document['tracking_id']); ?>"
                >
                <div class="qr-label"><?php echo e((string)$document['tracking_id']); ?></div>
            </div>
        </div>

        <div class="title-strip">DOCUMENT ACTION TRACKING SLIP</div>

        <div class="meta-grid">
            <div class="meta-row">
                <div class="meta-cell meta-label">Document Number</div>
                <div class="meta-cell"><?php echo e((string)$document['tracking_id']); ?></div>
                <div class="meta-cell meta-label">Year</div>
                <div class="meta-cell"><?php echo e(format_year_only((string)($document['created_at'] ?? null))); ?></div>
                <div class="meta-cell meta-label">Date</div>
                <div class="meta-cell"><?php echo e(format_date_only((string)($document['created_at'] ?? null))); ?></div>
            </div>
            <div class="meta-row" style="grid-template-columns: 170px 1fr;">
                <div class="meta-cell meta-label">Originating Office / Entity</div>
                <div class="meta-cell"><?php echo e((string)($document['originating_office'] ?? '-')); ?></div>
            </div>
            <div class="meta-row" style="grid-template-columns: 170px 1fr;">
                <div class="meta-cell meta-label">Sender</div>
                <div class="meta-cell"><?php echo e(trim((string)($document['sender_label'] ?? '')) !== '' ? (string)$document['sender_label'] : '-'); ?></div>
            </div>
            <div class="meta-row" style="grid-template-columns: 170px 1fr;">
                <div class="meta-cell meta-label">Encoder</div>
                <div class="meta-cell"><?php echo e(trim((string)($document['sender_name'] ?? '')) !== '' ? (string)$document['sender_name'] : 'N/A'); ?></div>
            </div>
            <div class="meta-row" style="grid-template-columns: 170px 1fr;">
                <div class="meta-cell meta-label">Address</div>
                <div class="meta-cell"><?php echo e(
                    trim((string)($document['client_address'] ?? '')) !== ''
                        ? (string)$document['client_address']
                        : (string)($document['originating_office'] ?? 'N/A')
                ); ?></div>
            </div>
            <div class="subject-row">
                <div class="meta-cell meta-label">Subject</div>
                <div class="meta-cell">
                    <?php echo e($subjectRowValue); ?>
                </div>
            </div>
            <?php if ($showNewSubjectRow): ?>
            <div class="new-subject-row">
                <div class="meta-cell meta-label">Response</div>
                <div class="meta-cell">
                    <?php echo e($newSubjectRowValue); ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="meta-row" style="grid-template-columns: 170px 1fr;">
                <div class="meta-cell meta-label">Attachment</div>
                <div class="meta-cell"><?php echo ((int)$document['attachment_count'] > 0) ? 'yes' : 'no'; ?></div>
            </div>
        </div>

        <div class="timeline-scroll">
            <div
                class="timeline-wrap"
                data-print-target-rows="<?php echo e((string)$printTargetTimelineRows); ?>"
                style="--timeline-row-count: <?php echo e((string)$renderedTimelineRowCount); ?>;"
            >
                <div class="timeline-head">
                    <div class="timeline-cell">Date / Time Received</div>
                    <div class="timeline-cell">From</div>
                    <div class="timeline-cell">Date / Time Released</div>
                    <div class="timeline-cell">To</div>
                    <div class="timeline-cell">Status</div>
                    <div class="timeline-cell">Action Required / Taken</div>
                </div>
                <?php foreach ($timelineRows as $row): ?>
                <?php $isEmpty = ($row['received'] === null && trim((string)$row['action']) === ''); ?>
                <?php
                    $displayReceivedAt = $row['received'];
                    if ($displayReceivedAt === null || trim((string)$displayReceivedAt) === '') {
                        $displayReceivedAt = $row['route_timestamp'] ?? $row['released'] ?? null;
                    }
                    $hasFlowDetails = trim((string)($row['route_action_type'] ?? '')) !== ''
                        || trim((string)($row['route_actor'] ?? '')) !== ''
                        || trim((string)($row['route_remarks'] ?? '')) !== ''
                        || trim((string)($row['route_timestamp'] ?? '')) !== ''
                        || !empty($row['row_activity_logs'])
                        || !empty($row['row_attachments']);
                    $rowClass = trim(($isEmpty ? 'empty ' : '') . ($hasFlowDetails ? 'has-flow-details' : ''));
                ?>
                <div class="timeline-row <?php echo e($rowClass); ?>">
                    <div class="timeline-cell">
                        <div class="cell-main"><?php echo e(format_dt($displayReceivedAt)); ?></div>
                        <?php if (!$isEmpty && trim((string)$row['received_by']) !== ''): ?>
                        <div class="cell-sub"><?php echo e((string)($row['received_by_prefix'] ?? 'Received by')); ?>: <?php echo e((string)$row['received_by']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-cell">
                        <div class="cell-main"><?php echo e((string)$row['from']); ?></div>
                    </div>
                    <div class="timeline-cell">
                        <div class="cell-main"><?php echo e(format_dt($row['released'])); ?></div>
                    </div>
                    <div class="timeline-cell">
                        <div class="cell-main"><?php echo e((string)$row['to']); ?></div>
                    </div>
                    <div class="timeline-cell">
                        <div class="cell-main"><?php echo e((string)$row['status']); ?></div>
                    </div>
                    <div class="timeline-cell timeline-cell-action">
                        <div class="cell-main"><?php echo e((string)$row['action']); ?></div>
                        <?php if (!$isEmpty && $hasFlowDetails): ?>
                        <?php $rowLogs = is_array($row['row_activity_logs'] ?? null) ? $row['row_activity_logs'] : []; ?>
                        <?php $rowAttachments = is_array($row['row_attachments'] ?? null) ? $row['row_attachments'] : []; ?>
                        <div class="row-popover-triggers">
                            <div class="row-popover-trigger">
                                <div class="row-flow-hint">Hover for activity logs</div>
                                <div class="row-flow-popover" role="tooltip" aria-label="Row activity logs">
                                    <div class="row-flow-popover-title">Row Activity Logs</div>
                                    <?php if (empty($rowLogs)): ?>
                                    <div><strong>Action:</strong> <?php echo e((string)($row['route_action_type'] !== '' ? $row['route_action_type'] : '-')); ?></div>
                                    <div><strong>At:</strong> <?php echo e(format_dt((string)($row['route_timestamp'] ?? null))); ?></div>
                                    <div><strong>To:</strong> <?php echo e((string)($row['to'] !== '' ? $row['to'] : '-')); ?></div>
                                    <div><strong>By:</strong> <?php echo e((string)($row['route_actor'] !== '' ? $row['route_actor'] : 'System')); ?></div>
                                    <?php if (trim((string)($row['route_remarks'] ?? '')) !== ''): ?>
                                    <div><strong>Remarks:</strong> <?php echo e((string)$row['route_remarks']); ?></div>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <div class="row-flow-log-list">
                                        <?php foreach ($rowLogs as $log): ?>
                                        <div class="row-flow-log-item">
                                            <div class="row-flow-log-item-head">
                                                <strong><?php echo e((string)($log['action'] ?? 'Action')); ?></strong>
                                                <span class="row-flow-log-time"><?php echo e(format_dt((string)($log['time'] ?? null))); ?></span>
                                            </div>
                                            <div><strong>By:</strong> <?php echo e(trim((string)($log['by'] ?? '')) !== '' ? (string)$log['by'] : 'System'); ?></div>
                                            <?php if (trim((string)($log['office'] ?? '')) !== ''): ?>
                                            <div><strong>Office:</strong> <?php echo e((string)$log['office']); ?></div>
                                            <?php endif; ?>
                                            <?php if (trim((string)($log['to'] ?? '')) !== ''): ?>
                                            <div><strong>To:</strong> <?php echo e((string)$log['to']); ?></div>
                                            <?php endif; ?>
                                            <?php if (trim((string)($log['remarks'] ?? '')) !== ''): ?>
                                            <div><strong>Remarks:</strong> <?php echo e((string)$log['remarks']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row-popover-trigger">
                                <div class="row-preview-hint">Hover for attachment preview</div>
                                <div class="row-preview-popover" role="tooltip" aria-label="Row attachment previews">
                                    <div class="row-flow-popover-title">Row Attachments</div>
                                    <?php if ($canViewAttachments): ?>
                                        <?php if (empty($rowAttachments)): ?>
                                        <div class="row-flow-attachment-empty">No matching attachments for this row window.</div>
                                        <?php else: ?>
                                        <div class="row-flow-attachment-list">
                                            <?php foreach ($rowAttachments as $attachment): ?>
                                            <div class="row-flow-attachment-item">
                                                <div class="row-flow-attachment-head">
                                                    <span class="row-flow-attachment-type"><?php echo e((string)($attachment['category_label'] ?? 'Attachment')); ?></span>
                                                    <span class="row-flow-log-time"><?php echo e(format_dt((string)($attachment['uploaded_at'] ?? null))); ?></span>
                                                </div>
                                                <div class="row-flow-attachment-name"><?php echo e((string)($attachment['file_name'] ?? 'Attachment')); ?></div>
                                                <?php if (trim((string)($attachment['uploaded_by'] ?? '')) !== ''): ?>
                                                <div class="row-flow-attachment-meta">
                                                    Uploaded by: <?php echo e((string)$attachment['uploaded_by']); ?>
                                                    <?php if (trim((string)($attachment['uploaded_by_role'] ?? '')) !== ''): ?>
                                                        (<?php echo e((string)$attachment['uploaded_by_role']); ?>)
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                                <?php
                                                    $previewType = strtolower(trim((string)($attachment['preview_type'] ?? 'none')));
                                                    $previewUrl = trim((string)($attachment['preview_url'] ?? ''));
                                                ?>
                                                <?php if ($previewUrl !== '' && $previewType === 'image'): ?>
                                                <div class="row-flow-attachment-preview">
                                                    <img src="<?php echo e($previewUrl); ?>" alt="Attachment preview: <?php echo e((string)($attachment['file_name'] ?? 'Attachment')); ?>" loading="lazy">
                                                </div>
                                                <?php elseif ($previewUrl !== '' && $previewType === 'pdf'): ?>
                                                <div class="row-flow-attachment-preview">
                                                    <iframe src="<?php echo e($previewUrl); ?>#toolbar=0&navpanes=0&scrollbar=0" title="PDF preview: <?php echo e((string)($attachment['file_name'] ?? 'Attachment')); ?>" loading="lazy"></iframe>
                                                </div>
                                                <?php else: ?>
                                                <div class="row-flow-attachment-preview-empty">Preview unavailable for this file type.</div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <div class="row-flow-attachment-empty">Login is required to view row attachments.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="attachment-vault">
            <h3>Attachment Vault</h3>
            <?php if ($canViewAttachments): ?>
                <?php if (empty($attachments)): ?>
                    <p>No attachments found for this document.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($attachments as $attachment): ?>
                        <li>
                            <?php echo e((string)$attachment['file_name']); ?>
                            <span class="cell-sub">(uploaded <?php echo e(format_dt((string)$attachment['uploaded_at'])); ?>)</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <p>Public view enabled: custody timeline is visible. Attachments and internal details require DENR login.</p>
            <?php endif; ?>
            <p class="hint">Tracking Slip rows reflect custody receives and key workflow statuses (Signed/Completed and Released) from backend activity logs.</p>
        </div>
    </section>
    </div>
    <div id="printPages" class="print-pages" aria-hidden="true"></div>
    <?php endif; ?>
    <script src="<?php echo e(app_url('assets/js/vendor/qrcode-generator.js')); ?>"></script>
    <script src="<?php echo e(app_url('assets/js/local-qr.js')); ?>"></script>
    <script>
        (function () {
            const trackingQrImage = document.getElementById('trackingSlipQrImage');
            if (trackingQrImage && window.DTMISLocalQr && typeof window.DTMISLocalQr.renderImage === 'function') {
                const qrPayload = String(trackingQrImage.getAttribute('data-qr-text') || '').trim();
                if (qrPayload !== '') {
                    try {
                        window.DTMISLocalQr.renderImage(trackingQrImage, qrPayload, {
                            size: 420,
                            margin: 2,
                            errorCorrection: 'M',
                            typeNumber: 0
                        });
                    } catch (error) {
                        // Keep page usable even if QR rendering fails.
                    }
                }
            }

            const root = document.documentElement;
            const paper = document.querySelector('.slip-paper');
            const printPages = document.getElementById('printPages');
            const timelineWrap = paper ? paper.querySelector('.timeline-wrap') : null;
            const attachmentVault = paper ? paper.querySelector('.attachment-vault') : null;
            const slipHeader = paper ? paper.querySelector('.slip-header') : null;
            const titleStrip = paper ? paper.querySelector('.title-strip') : null;
            const metaGrid = paper ? paper.querySelector('.meta-grid') : null;
            const timelineHead = paper ? paper.querySelector('.timeline-head') : null;
            const layoutControls = document.getElementById('layoutControls');
            const fontScaleInput = document.getElementById('fontScaleInput');
            const logoScreenSizeInput = document.getElementById('logoScreenSizeInput');
            const logoPrintSizeInput = document.getElementById('logoPrintSizeInput');
            const printRowsInput = document.getElementById('printRowsInput');
            const resetLayoutBtn = document.getElementById('resetLayoutBtn');
            const storageKey = 'DTMIS_tracking_slip_layout_v2';
            if (!paper || !timelineWrap || !attachmentVault || !printPages || !slipHeader || !titleStrip || !metaGrid || !timelineHead) {
                return;
            }

            function clampInt(rawValue, min, max, fallback) {
                const parsed = parseInt(String(rawValue), 10);
                if (!Number.isFinite(parsed)) {
                    return fallback;
                }
                return Math.max(min, Math.min(max, parsed));
            }

            const defaultFontPercent = clampInt(
                layoutControls ? layoutControls.getAttribute('data-default-font') : '',
                80,
                145,
                100
            );
            const defaultLogoScreenPx = clampInt(
                layoutControls ? layoutControls.getAttribute('data-default-logo-screen') : '',
                50,
                130,
                100
            );
            const defaultLogoPrintPx = clampInt(
                layoutControls ? layoutControls.getAttribute('data-default-logo-print') : '',
                40,
                110,
                100
            );
            const defaultPrintRows = clampInt(
                layoutControls ? layoutControls.getAttribute('data-default-print-rows') : '',
                0,
                14,
                14
            );

            function applyLayoutPreferences(preferences, persist) {
                const fontPercent = clampInt(
                    preferences && preferences.fontPercent,
                    80,
                    145,
                    defaultFontPercent
                );
                const logoScreenPx = clampInt(
                    preferences && preferences.logoScreenPx,
                    50,
                    130,
                    defaultLogoScreenPx
                );
                const logoPrintPx = clampInt(
                    preferences && preferences.logoPrintPx,
                    40,
                    110,
                    defaultLogoPrintPx
                );
                const printRows = clampInt(
                    preferences && preferences.printRows,
                    0,
                    14,
                    defaultPrintRows
                );

                root.style.setProperty('--tracking-font-scale', (fontPercent / 100).toFixed(2));
                root.style.setProperty('--tracking-logo-screen-size', logoScreenPx + 'px');
                root.style.setProperty('--tracking-logo-print-size', logoPrintPx + 'px');
                timelineWrap.setAttribute('data-print-target-rows', String(printRows));

                if (fontScaleInput) {
                    fontScaleInput.value = String(fontPercent);
                }
                if (logoScreenSizeInput) {
                    logoScreenSizeInput.value = String(logoScreenPx);
                }
                if (logoPrintSizeInput) {
                    logoPrintSizeInput.value = String(logoPrintPx);
                }
                if (printRowsInput) {
                    printRowsInput.value = String(printRows);
                }

                if (persist) {
                    try {
                        localStorage.setItem(storageKey, JSON.stringify({
                            fontPercent: fontPercent,
                            logoScreenPx: logoScreenPx,
                            logoPrintPx: logoPrintPx,
                            printRows: printRows
                        }));
                    } catch (error) {
                        // Ignore storage failures (private mode/quota).
                    }
                }
            }

            function applySavedPreferencesIfAny() {
                try {
                    const raw = localStorage.getItem(storageKey);
                    if (!raw) {
                        return;
                    }
                    const parsed = JSON.parse(raw);
                    if (!parsed || typeof parsed !== 'object') {
                        return;
                    }
                    applyLayoutPreferences(parsed, false);
                } catch (error) {
                    // Ignore malformed storage payloads.
                }
            }

            function createEmptyTimelineRow() {
                const row = document.createElement('div');
                row.className = 'timeline-row empty auto-fill-row';

                for (let i = 0; i < 6; i += 1) {
                    const cell = document.createElement('div');
                    cell.className = i === 5 ? 'timeline-cell timeline-cell-action' : 'timeline-cell';
                    const main = document.createElement('div');
                    main.className = 'cell-main';
                    main.innerHTML = '&nbsp;';
                    cell.appendChild(main);
                    row.appendChild(cell);
                }

                return row;
            }

            function clearAutoFillRows() {
                const autoRows = timelineWrap.querySelectorAll('.timeline-row.auto-fill-row');
                autoRows.forEach(function (row) {
                    row.remove();
                });
            }

            function appendAutoFillRow() {
                timelineWrap.appendChild(createEmptyTimelineRow());
            }

            function destroyPrintPages() {
                printPages.innerHTML = '';
                printPages.classList.remove('has-pages');
                document.body.classList.remove('has-print-pages');
            }

            function pageHasOverflow(page) {
                return page.scrollHeight > (page.clientHeight + 1);
            }

            function appendPrintPageBase(page) {
                page.appendChild(slipHeader.cloneNode(true));
                page.appendChild(titleStrip.cloneNode(true));
                page.appendChild(metaGrid.cloneNode(true));
            }

            function createPrintTimelinePage(isContinuation) {
                const page = document.createElement('section');
                page.className = 'print-page';
                if (isContinuation) {
                    page.classList.add('print-page-continuation');
                }
                page.setAttribute('aria-hidden', 'true');
                appendPrintPageBase(page);

                if (isContinuation) {
                    const rowGap = document.createElement('div');
                    rowGap.className = 'print-page-row-gap';
                    rowGap.setAttribute('aria-hidden', 'true');
                    page.appendChild(rowGap);
                }

                const pageTimelineWrap = document.createElement('div');
                pageTimelineWrap.className = 'timeline-wrap';
                pageTimelineWrap.appendChild(timelineHead.cloneNode(true));
                page.appendChild(pageTimelineWrap);

                return {
                    page: page,
                    timelineBody: pageTimelineWrap,
                };
            }

            function createPrintAttachmentPage() {
                const page = document.createElement('section');
                page.className = 'print-page';
                page.setAttribute('aria-hidden', 'true');
                appendPrintPageBase(page);
                return page;
            }

            function trimPrintAutoFillRowsToFit(page, timelineBody) {
                if (!page || !timelineBody) {
                    return false;
                }

                let removedAny = false;
                let autoRows = Array.from(timelineBody.querySelectorAll('.timeline-row.auto-fill-row'));
                while (pageHasOverflow(page) && autoRows.length > 0) {
                    const lastAutoRow = autoRows[autoRows.length - 1];
                    lastAutoRow.remove();
                    removedAny = true;
                    autoRows = Array.from(timelineBody.querySelectorAll('.timeline-row.auto-fill-row'));
                }

                return removedAny;
            }

            function buildPrintPages() {
                destroyPrintPages();

                // Keep print filler rows so short timelines still occupy the intended page height.
                const sourceRows = Array.from(timelineWrap.querySelectorAll('.timeline-row'));

                const rowsToRender = sourceRows.length > 0 ? sourceRows : [createEmptyTimelineRow()];
                let currentPage = createPrintTimelinePage(false);
                printPages.appendChild(currentPage.page);

                rowsToRender.forEach(function (sourceRow) {
                    const rowClone = sourceRow.cloneNode(true);
                    currentPage.timelineBody.appendChild(rowClone);

                    const renderedRows = currentPage.timelineBody.querySelectorAll('.timeline-row').length;
                    if (pageHasOverflow(currentPage.page) && renderedRows > 1) {
                        rowClone.remove();
                        currentPage = createPrintTimelinePage(true);
                        printPages.appendChild(currentPage.page);
                        currentPage.timelineBody.appendChild(sourceRow.cloneNode(true));
                    }
                });

                const attachmentClone = attachmentVault.cloneNode(true);
                currentPage.page.appendChild(attachmentClone);
                trimPrintAutoFillRowsToFit(currentPage.page, currentPage.timelineBody);
                if (pageHasOverflow(currentPage.page)) {
                    attachmentClone.remove();
                    const attachmentPage = createPrintAttachmentPage();
                    attachmentPage.appendChild(attachmentClone);
                    printPages.appendChild(attachmentPage);
                }

                if (printPages.children.length > 0) {
                    printPages.classList.add('has-pages');
                    document.body.classList.add('has-print-pages');
                }
            }

            function ensurePrintFillRows() {
                clearAutoFillRows();

                const targetRowsRaw = parseInt(String(timelineWrap.getAttribute('data-print-target-rows') || '14'), 10);
                const targetRows = Number.isFinite(targetRowsRaw) && targetRowsRaw >= 0 ? targetRowsRaw : defaultPrintRows;
                const hardLimitRows = Math.max(targetRows + 2, 8);

                let currentRows = timelineWrap.querySelectorAll('.timeline-row').length;
                while (currentRows < targetRows) {
                    appendAutoFillRow();
                    currentRows += 1;
                }

                if (!window.matchMedia || !window.matchMedia('print').matches) {
                    return;
                }

                let guard = 0;
                while (guard < 24) {
                    const paperRect = paper.getBoundingClientRect();
                    const attachmentRect = attachmentVault.getBoundingClientRect();
                    const hasSpareSpace = attachmentRect.bottom < (paperRect.bottom - 3);
                    const hasOverflow = attachmentRect.bottom > (paperRect.bottom + 1);
                    currentRows = timelineWrap.querySelectorAll('.timeline-row').length;

                    if (hasSpareSpace && currentRows < hardLimitRows) {
                        appendAutoFillRow();
                        guard += 1;
                        continue;
                    }

                    if (hasOverflow) {
                        const autoRows = timelineWrap.querySelectorAll('.timeline-row.auto-fill-row');
                        if (autoRows.length > 0 && currentRows > targetRows) {
                            autoRows[autoRows.length - 1].remove();
                            guard += 1;
                            continue;
                        }
                    }

                    break;
                }
            }

            applyLayoutPreferences({
                fontPercent: defaultFontPercent,
                logoScreenPx: defaultLogoScreenPx,
                logoPrintPx: defaultLogoPrintPx,
                printRows: defaultPrintRows
            }, false);
            applySavedPreferencesIfAny();
            ensurePrintFillRows();

            if (layoutControls) {
                const applyFromInputs = function () {
                    applyLayoutPreferences({
                        fontPercent: fontScaleInput ? fontScaleInput.value : defaultFontPercent,
                        logoScreenPx: logoScreenSizeInput ? logoScreenSizeInput.value : defaultLogoScreenPx,
                        logoPrintPx: logoPrintSizeInput ? logoPrintSizeInput.value : defaultLogoPrintPx,
                        printRows: printRowsInput ? printRowsInput.value : defaultPrintRows
                    }, true);
                    ensurePrintFillRows();
                };

                if (fontScaleInput) {
                    fontScaleInput.addEventListener('change', applyFromInputs);
                }
                if (logoScreenSizeInput) {
                    logoScreenSizeInput.addEventListener('change', applyFromInputs);
                }
                if (logoPrintSizeInput) {
                    logoPrintSizeInput.addEventListener('change', applyFromInputs);
                }
                if (printRowsInput) {
                    printRowsInput.addEventListener('change', applyFromInputs);
                }
                if (resetLayoutBtn) {
                    resetLayoutBtn.addEventListener('click', function () {
                        applyLayoutPreferences({
                            fontPercent: defaultFontPercent,
                            logoScreenPx: defaultLogoScreenPx,
                            logoPrintPx: defaultLogoPrintPx,
                            printRows: defaultPrintRows
                        }, true);
                        ensurePrintFillRows();
                    });
                }
            }

            window.addEventListener('beforeprint', function () {
                ensurePrintFillRows();
                buildPrintPages();
            });
            window.addEventListener('afterprint', function () {
                destroyPrintPages();
                ensurePrintFillRows();
            });

            if (window.matchMedia) {
                const printMedia = window.matchMedia('print');
                const onPrintMediaChange = function (event) {
                    if (event.matches) {
                        ensurePrintFillRows();
                        buildPrintPages();
                    } else {
                        destroyPrintPages();
                        ensurePrintFillRows();
                    }
                };
                if (typeof printMedia.addEventListener === 'function') {
                    printMedia.addEventListener('change', onPrintMediaChange);
                } else if (typeof printMedia.addListener === 'function') {
                    printMedia.addListener(onPrintMediaChange);
                }
            }
        })();

        // Sync theme with parent DTMIS dashboard if available
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
    <?php if ($autoPrint && $document): ?>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
    <?php endif; ?>
</body>
</html>
