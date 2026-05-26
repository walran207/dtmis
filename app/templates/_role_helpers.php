<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/dashboard-data.php';
require_once dirname(__DIR__, 2) . '/config/workflow.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['user_id'])) {
    app_redirect('auth/login.php');
}

if (!isset($roleBasePath) || !is_string($roleBasePath) || !is_dir($roleBasePath)) {
    throw new RuntimeException('Role base path is required.');
}

$requiredFolder = strtoupper(basename(str_replace('\\', '/', $roleBasePath)));
app_require_role_folder_access($requiredFolder);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function role_status_is_pending_receive_state(string $status): bool
{
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return false;
    }

    return str_contains($normalized, 'pending receive')
        || str_contains($normalized, 'pending received')
        || str_contains($normalized, 'pending recieved')
        || str_contains($normalized, 'awaiting receive')
        || str_contains($normalized, 'for receive')
        || str_contains($normalized, 'in transit')
        || str_contains($normalized, 'routed')
        || str_contains($normalized, 'assigned');
}

function role_status_has_received_custody(string $status): bool
{
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return false;
    }

    if (role_status_is_pending_receive_state($normalized)) {
        return false;
    }

    return str_contains($normalized, 'received') || str_contains($normalized, 'recieved');
}

function role_status_allows_receive(string $status): bool
{
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return true;
    }

    $alreadyReceived = role_status_has_received_custody($normalized);
    if ($alreadyReceived) {
        return false;
    }

    $isReleasedIncoming = str_contains($normalized, 'released') || str_contains($normalized, 'release');
    if ($isReleasedIncoming) {
        return true;
    }

    $terminalKeywords = ['signed', 'completed', 'cancelled', 'canceled', 'closed'];
    foreach ($terminalKeywords as $keyword) {
        if (str_contains($normalized, $keyword)) {
            return false;
        }
    }

    return true;
}

function role_hide_reroute_quick_action(): bool
{
    global $roleName;
    $roleKey = app_role_behavior_key((string)($roleName ?? ''));
    return $roleKey === 'RECORDS_UNIT';
}

function role_status_is_terminal(string $status): bool
{
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
        return false;
    }

    $terminalKeywords = ['completed', 'released', 'closed', 'resolved', 'done', 'signed', 'cancelled', 'canceled'];
    foreach ($terminalKeywords as $keyword) {
        if (str_contains($normalized, $keyword)) {
            return true;
        }
    }

    return false;
}

function role_queue_can_manage_intake(array $queueRow): bool
{
    global $isIntakePage, $roleName;

    if (!(bool)($isIntakePage ?? false)) {
        return false;
    }

    $status = strtolower(trim((string)($queueRow['status'] ?? '')));
    if (role_status_is_terminal($status)) {
        return false;
    }

    $pendingOfficeId = (int)($queueRow['pending_office_id'] ?? 0);
    $rowCurrentOfficeId = (int)($queueRow['current_office_id'] ?? 0);
    $currentOfficeId = (int)($_SESSION['office_id'] ?? 0);
    $rowIsInMyCustody = true;
    if ($pendingOfficeId > 0) {
        $rowIsInMyCustody = $currentOfficeId > 0 && $pendingOfficeId === $currentOfficeId;
    } elseif ($rowCurrentOfficeId > 0) {
        $rowIsInMyCustody = $currentOfficeId > 0 && $rowCurrentOfficeId === $currentOfficeId;
    }
    if ($pendingOfficeId > 0 && !$rowIsInMyCustody) {
        return false;
    }
    $hasReceivedCustody = role_status_has_received_custody($status)
        || (
            $pendingOfficeId <= 0
            && $rowCurrentOfficeId > 0
            && $currentOfficeId > 0
            && $rowCurrentOfficeId === $currentOfficeId
        );
    if (str_contains($status, 'forward') || str_contains($status, 'route') || str_starts_with($status, 'assigned to ')) {
        return false;
    }

    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    $createdByUserId = (int)($queueRow['created_by_user_id'] ?? 0);
    $isCreatorDraft = $currentUserId > 0
        && $createdByUserId > 0
        && $createdByUserId === $currentUserId
        && $status === 'created';
    if ($isCreatorDraft) {
        return true;
    }

    $hasReturnAction = (int)($queueRow['has_return_action'] ?? 0) > 0;
    $isCorrectionCycle = $hasReturnAction
        && $rowIsInMyCustody
        && $hasReceivedCustody
        && (
            str_contains($status, 'received')
            || str_contains($status, 'recieved')
            || str_contains($status, 'return')
            || str_contains($status, 'approved')
        );
    if (!$isCorrectionCycle) {
        return false;
    }

    $roleKey = app_normalize_role_key((string)($roleName ?? ($_SESSION['role_name'] ?? '')));
    return in_array($roleKey, ['RECORDS_UNIT', 'CENRO_ADMIN_RECORD', 'PENRO_ADMIN_RECORD', 'PAMO_ADMIN'], true);
}

function role_queue_row_is_intake_draft(array $queueRow, int $currentUserId): bool
{
    if ($currentUserId <= 0) {
        return false;
    }

    $createdByUserId = (int)($queueRow['created_by_user_id'] ?? 0);
    if ($createdByUserId <= 0 || $createdByUserId !== $currentUserId) {
        return false;
    }

    $status = strtolower(trim((string)($queueRow['status'] ?? '')));
    if (role_status_is_terminal($status)) {
        return false;
    }

    $pendingOfficeId = (int)($queueRow['pending_office_id'] ?? 0);
    if ($pendingOfficeId > 0) {
        return false;
    }

    if (str_contains($status, 'forward') || str_contains($status, 'route')) {
        return false;
    }

    if ($status === '' || $status === 'created') {
        return true;
    }

    return str_contains($status, 'draft')
        || str_contains($status, 'encoded')
        || str_contains($status, 'submitted');
}

function role_table_default_actions(array $queueRow): string
{
    global $roleName;

    if (role_queue_can_manage_intake($queueRow)) {
        return 'Forward | Edit | Delete';
    }

    $roleKeyRaw = app_normalize_role_key((string)($roleName ?? ''));
    $roleKey = app_role_behavior_key((string)($roleName ?? ''));
    $isRegionalOredSigner = $roleKeyRaw === 'ORED_SIGN';
    $isCenroAdminRecord = $roleKeyRaw === 'CENRO_ADMIN_RECORD';
    $isPenroAdminRecord = $roleKeyRaw === 'PENRO_ADMIN_RECORD';
    $isPamoAdminRecord = $roleKeyRaw === 'PAMO_ADMIN';
    $isInternalAdminRecord = $isCenroAdminRecord || $isPenroAdminRecord || $isPamoAdminRecord;
    $isCenroOfficer = in_array($roleKeyRaw, ['CENRO_OFFICER', 'PENRO_OFFICER', 'PASU_OFFICER'], true);
    $status = strtolower(trim((string)($queueRow['status'] ?? '')));
    $pendingOfficeId = (int)($queueRow['pending_office_id'] ?? 0);
    $rowCurrentOfficeId = (int)($queueRow['current_office_id'] ?? 0);
    $currentOfficeId = (int)($_SESSION['office_id'] ?? 0);
    $hasReceivedCustody = role_status_has_received_custody($status)
        || (
            $pendingOfficeId <= 0
            && $rowCurrentOfficeId > 0
            && $currentOfficeId > 0
            && $rowCurrentOfficeId === $currentOfficeId
        );
    $actions = ['View Tracking Slip'];
    if (role_status_allows_receive($status)) {
        $actions[] = 'Receive';
    }
    if ($isRegionalOredSigner) {
        $actions[] = 'Sign';
        $actions[] = 'Undo Sign';
    }
    $requiresReceiveBeforeForward = $roleKeyRaw === 'CENRO_ADMIN_RECORD';
    if (!$requiresReceiveBeforeForward || $hasReceivedCustody) {
        $actions[] = 'Forward';
    }
    if (in_array($roleKey, ['ARD_TS', 'ARD_MS'], true)) {
        $actions[] = 'Send Back to ORED';
    }
    if ($roleKey === 'ORED' || str_contains($status, 'return')) {
        $actions[] = 'Return';
    }
    if ($roleKey === 'RECORDS_UNIT' && !$isInternalAdminRecord) {
        $actions[] = 'Release';
    }
    if ($isInternalAdminRecord && $hasReceivedCustody) {
        $actions[] = 'Release';
    }
    if ($isRegionalOredSigner && !$isCenroOfficer) {
        $actions[] = 'Override';
    }
    if (!role_hide_reroute_quick_action()) {
        $actions[] = 'Reroute';
    }
    $actions[] = 'Print Package';
    if (role_queue_can_manage_intake($queueRow)) {
        $actions[] = 'Edit';
        $actions[] = 'Delete';
    }

    return implode(' | ', array_values(array_unique($actions)));
}

function role_queue_actor_action_label(array $queueRow): string
{
    $actionType = trim((string)($queueRow['actor_action_type'] ?? ''));
    $actionRemarks = trim((string)($queueRow['actor_action_remarks'] ?? ''));
    if ($actionType === '') {
        return '-';
    }
    if ($actionRemarks === '' || strcasecmp($actionRemarks, $actionType) === 0) {
        return $actionType;
    }

    $normalizedRemarks = preg_replace('/\s+/', ' ', $actionRemarks);
    if ($normalizedRemarks === null || $normalizedRemarks === '') {
        return $actionType;
    }
    if (strlen($normalizedRemarks) > 64) {
        $normalizedRemarks = substr($normalizedRemarks, 0, 61) . '...';
    }

    return $actionType . ' | ' . $normalizedRemarks;
}

function role_queue_intake_indicator_label(array $queueRow): string
{
    $status = strtolower(trim((string)($queueRow['status'] ?? '')));
    $complexity = strtolower(trim((string)($queueRow['arta_category'] ?? '')));
    $subject = strtolower(trim((string)($queueRow['subject'] ?? '')));
    $remarks = strtolower(trim((string)($queueRow['last_remarks'] ?? $queueRow['actor_action_remarks'] ?? '')));
    $deadlineBucket = strtolower(trim((string)($queueRow['deadline_bucket'] ?? '')));
    $classification = '';

    if (str_contains($remarks, 'color classification:')) {
        if (str_contains($remarks, 'green') || str_contains($remarks, 'released')) {
            $classification = 'green';
        } elseif (str_contains($remarks, 'yellow') || str_contains($remarks, 'urgent')) {
            $classification = 'yellow';
        } elseif (str_contains($remarks, 'blue') || str_contains($remarks, 'complex') || str_contains($remarks, 'highly technical')) {
            $classification = 'blue';
        } elseif (str_contains($remarks, 'pink') || str_contains($remarks, 'simple')) {
            $classification = 'pink';
        }
    }

    if ($classification === 'green') {
        return 'Green - Released';
    }

    if (str_contains($status, 'released')) {
        return 'Green - Released';
    }

    if (
        str_contains($status, 'urgent')
        || str_contains($subject, 'urgent')
        || str_contains($remarks, 'urgent')
        || str_contains($deadlineBucket, 'overdue')
        || str_contains($deadlineBucket, 'at-risk')
        || str_contains($deadlineBucket, 'at risk')
    ) {
        return 'Yellow - Urgent';
    }
    if ($classification === 'yellow') {
        return 'Yellow - Urgent';
    }

    if (
        str_contains($complexity, 'complex')
        || str_contains($complexity, 'highly technical')
        || str_contains($complexity, 'highly_technical')
        || str_contains($complexity, 'highly-technical')
    ) {
        return 'Blue - Complex/Highly Technical';
    }
    if ($classification === 'blue') {
        return 'Blue - Complex/Highly Technical';
    }

    if (str_contains($complexity, 'simple')) {
        return 'Pink - Simple';
    }
    if ($classification === 'pink') {
        return 'Pink - Simple';
    }

    return 'Pink - Simple';
}

function role_table_rows_from_queue(array $queueRows, array $tableColumns): array
{
    $rows = [];
    foreach ($queueRows as $queueRow) {
        $cells = [];
        foreach ($tableColumns as $column) {
            $columnKey = strtolower(trim((string)$column));
            $value = '-';
            if ($columnKey === 'indicator' || str_contains($columnKey, 'indicator')) {
                $value = role_queue_intake_indicator_label($queueRow);
            } elseif (str_contains($columnKey, 'tracking')) {
                $value = (string)($queueRow['tracking_id'] ?? '-');
            } elseif (str_contains($columnKey, 'source')) {
                $sourceType = strtoupper(trim((string)($queueRow['source_type'] ?? 'INTERNAL')));
                $value = $sourceType === 'EXTERNAL' ? 'Client' : 'Current Office';
            } elseif (str_contains($columnKey, 'subject') && str_contains($columnKey, 'complexity')) {
                $subject = trim((string)($queueRow['subject'] ?? ''));
                $complexity = trim((string)($queueRow['arta_category'] ?? ''));
                if ($subject === '' && ($complexity === '' || $complexity === '-')) {
                    $value = '-';
                } elseif ($subject === '') {
                    $value = $complexity;
                } elseif ($complexity === '' || $complexity === '-') {
                    $value = $subject;
                } else {
                    $value = $subject . ' (' . $complexity . ')';
                }
            } elseif (str_contains($columnKey, 'subject')) {
                $value = (string)($queueRow['subject'] ?? '-');
            } elseif (str_contains($columnKey, 'document type')) {
                $value = (string)($queueRow['document_type'] ?? '-');
            } elseif ($columnKey === 'arta' || str_contains($columnKey, 'arta')) {
                $value = (string)($queueRow['arta_category'] ?? '-');
            } elseif (str_contains($columnKey, 'from') || str_contains($columnKey, 'origin')) {
                $value = (string)($queueRow['origin_office'] ?? '-');
            } elseif ($columnKey === 'to' || str_starts_with($columnKey, 'to ')) {
                $value = (string)($queueRow['current_holder'] ?? '-');
            } elseif (str_contains($columnKey, 'reroute to') || str_contains($columnKey, 'reoute to')) {
                $rerouteTo = trim((string)($queueRow['reroute_to'] ?? ''));
                if ($rerouteTo === '') {
                    $rerouteTo = trim((string)($queueRow['current_holder'] ?? '-'));
                }
                $value = $rerouteTo !== '' ? $rerouteTo : '-';
            } elseif (str_contains($columnKey, 'remarks')) {
                $lastRemarks = trim((string)($queueRow['last_remarks'] ?? ''));
                if ($lastRemarks === '') {
                    $lastRemarks = trim((string)($queueRow['actor_action_remarks'] ?? ''));
                }
                $lastRemarks = workflow_strip_custom_others_document_type_from_remarks($lastRemarks);
                $value = $lastRemarks !== '' ? $lastRemarks : '-';
            } elseif (str_contains($columnKey, 'current holder')) {
                $value = (string)($queueRow['current_holder'] ?? '-');
            } elseif (str_contains($columnKey, 'reason')) {
                $value = (string)($queueRow['subject'] ?? '-');
            } elseif (str_contains($columnKey, 'requested by') || str_contains($columnKey, 'requested')) {
                $value = (string)($queueRow['current_holder'] ?? '-');
            } elseif (
                str_contains($columnKey, 'last action by me')
                || str_contains($columnKey, 'my action')
            ) {
                $value = role_queue_actor_action_label($queueRow);
            } elseif (
                str_contains($columnKey, 'last action time')
                || $columnKey === 'last action'
                || str_contains($columnKey, 'last update')
            ) {
                $value = (string)($queueRow['date_received'] ?? '-');
            } elseif (str_contains($columnKey, 'sender')) {
                $value = (string)($queueRow['sender'] ?? '-');
            } elseif (str_contains($columnKey, 'date created') || str_contains($columnKey, 'created date') || str_contains($columnKey, 'created at')) {
                $value = (string)($queueRow['date_created'] ?? '-');
            } elseif (str_contains($columnKey, 'date')) {
                $value = (string)($queueRow['date_received'] ?? '-');
            } elseif (str_contains($columnKey, 'time remaining')) {
                $value = (string)($queueRow['time_remaining'] ?? '-');
            } elseif (str_contains($columnKey, 'status')) {
                $value = (string)($queueRow['status'] ?? '-');
            } elseif (str_contains($columnKey, 'quick action') || $columnKey === 'action' || $columnKey === 'actions') {
                $value = role_table_default_actions($queueRow);
            }
            $cells[] = $value;
        }

        $rows[] = [
            'value' => $cells,
            'meta' => [
                'document_id' => (string)($queueRow['document_id'] ?? 0),
                'row_version' => (string)max(1, (int)($queueRow['row_version'] ?? 1)),
                'tracking_id' => (string)($queueRow['tracking_id'] ?? ''),
                'date_received_raw' => (string)($queueRow['date_received_raw'] ?? ''),
                'date_created_raw' => (string)($queueRow['date_created_raw'] ?? ''),
                'deadline_at_raw' => (string)($queueRow['deadline_at_raw'] ?? ''),
                'deadline_bucket' => (string)($queueRow['deadline_bucket'] ?? ''),
                'status_raw' => (string)($queueRow['status_raw'] ?? $queueRow['status'] ?? ''),
                'has_section_receive' => (int)($queueRow['has_section_receive'] ?? 0) > 0 ? '1' : '0',
                'has_signed_action' => (int)($queueRow['has_signed_action'] ?? 0) > 0 ? '1' : '0',
                'has_return_action' => (int)($queueRow['has_return_action'] ?? 0) > 0 ? '1' : '0',
                'created_by_user_id' => (string)((int)($queueRow['created_by_user_id'] ?? 0)),
                'origin_office_id' => (string)((int)($queueRow['origin_office_id'] ?? 0)),
                'current_office_id' => (string)((int)($queueRow['current_office_id'] ?? 0)),
                'pending_office_id' => (string)((int)($queueRow['pending_office_id'] ?? 0)),
                'current_office_level' => (string)($queueRow['current_office_level'] ?? ''),
                'pending_office_level' => (string)($queueRow['pending_office_level'] ?? ''),
                'current_holder' => (string)($queueRow['current_holder'] ?? ''),
            ],
        ];
    }

    return $rows;
}

function role_table_indicator_column_index(array $tableColumns): int
{
    foreach ($tableColumns as $index => $column) {
        $columnKey = strtolower(trim((string)$column));
        if ($columnKey === 'indicator' || str_contains($columnKey, 'indicator')) {
            return (int)$index;
        }
    }

    return -1;
}

function role_table_tracking_column_index(array $tableColumns): int
{
    foreach ($tableColumns as $index => $column) {
        $columnKey = strtolower(trim((string)$column));
        if (str_contains($columnKey, 'tracking')) {
            return (int)$index;
        }
    }

    return -1;
}

function role_table_with_indicator_column(array $tableColumns): array
{
    if (role_table_indicator_column_index($tableColumns) >= 0) {
        return array_values($tableColumns);
    }

    $trackingColumnIndex = role_table_tracking_column_index($tableColumns);
    if ($trackingColumnIndex < 0) {
        array_unshift($tableColumns, 'Indicator');
        return array_values($tableColumns);
    }

    array_splice($tableColumns, $trackingColumnIndex, 0, ['Indicator']);
    return array_values($tableColumns);
}

function role_page_uses_queue_indicator(string $activeMenu = '', string $scriptName = ''): bool
{
    $normalizedMenu = strtolower(trim($activeMenu));
    if ($normalizedMenu === 'audit_logs') {
        return true;
    }

    $normalizedScript = strtolower(trim($scriptName));
    if ($normalizedScript === '') {
        return false;
    }

    return in_array($normalizedScript, [
        'ard-ms-action.php',
        'ard-ts-action.php',
        'audit-logs.php',
        'for-chief-action.php',
        'for-staff-action.php',
        'pacdo-action.php',
        'rd-action-desk.php',
    ], true);
}

function role_fallback_indicator_label(array $rowMeta = []): string
{
    $status = strtolower(trim((string)($rowMeta['status_raw'] ?? $rowMeta['status'] ?? '')));
    $deadlineBucket = strtolower(trim((string)($rowMeta['deadline_bucket'] ?? '')));

    if (str_contains($status, 'released')) {
        return 'Green - Released';
    }

    if (
        str_contains($status, 'urgent')
        || str_contains($deadlineBucket, 'overdue')
        || str_contains($deadlineBucket, 'at-risk')
        || str_contains($deadlineBucket, 'at risk')
    ) {
        return 'Yellow - Urgent';
    }

    return 'Pink - Simple';
}

function role_has_date_column(array $tableColumns): bool
{
    foreach ($tableColumns as $column) {
        $columnKey = strtolower(trim((string)$column));
        if (
            $columnKey !== ''
            && (
                str_contains($columnKey, 'date created')
                || str_contains($columnKey, 'created date')
                || str_contains($columnKey, 'created at')
                || (str_contains($columnKey, 'created') && str_contains($columnKey, 'date'))
            )
        ) {
            return true;
        }
    }

    return false;
}

function role_has_quick_action_column(array $tableColumns): bool
{
    foreach ($tableColumns as $column) {
        $columnKey = strtolower(trim((string)$column));
        if (
            $columnKey !== ''
            && (
                str_contains($columnKey, 'quick action')
                || $columnKey === 'action'
                || $columnKey === 'actions'
            )
        ) {
            return true;
        }
    }

    return false;
}

function role_queue_row_is_completed(array $queueRow): bool
{
    $status = strtolower(trim((string)($queueRow['status'] ?? '')));
    $terminalKeywords = ['completed', 'released', 'closed', 'resolved', 'done', 'signed', 'cancelled', 'canceled'];
    foreach ($terminalKeywords as $keyword) {
        if ($status !== '' && str_contains($status, $keyword)) {
            return true;
        }
    }

    $hasSignedAction = (int)($queueRow['has_signed_action'] ?? 0) > 0;
    if ($hasSignedAction && $status !== '' && (str_contains($status, 'received') || str_contains($status, 'recieved'))) {
        return true;
    }

    return false;
}

function role_queue_row_is_strictly_completed(array $queueRow): bool
{
    $status = strtolower(trim((string)($queueRow['status'] ?? '')));
    if ($status === '') {
        return false;
    }

    return str_contains($status, 'completed');
}

function role_queue_status_counters(array $queueRows, int $currentOfficeId): array
{
    $counters = [
        'pending_total' => 0,
        'intake_draft_total' => 0,
        'pending_receive_total' => 0,
        'pending_approval_total' => 0,
        'pending_sign_total' => 0,
        'pending_release_total' => 0,
        'pending_forward_total' => 0,
        'completed_total' => 0,
    ];

    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    foreach ($queueRows as $queueRow) {
        $status = strtolower(trim((string)($queueRow['status'] ?? '')));
        $pendingOfficeId = (int)($queueRow['pending_office_id'] ?? 0);
        $rowCurrentOfficeId = (int)($queueRow['current_office_id'] ?? 0);
        $isTerminal = role_status_is_terminal($status);
        $hasReceived = role_status_has_received_custody($status);
        $hasSignedAction = (int)($queueRow['has_signed_action'] ?? 0) > 0;
        $isSignedCompleted = str_contains($status, 'signed') || str_contains($status, 'completed');
        $isReleased = str_contains($status, 'released') || str_contains($status, 'release');
        $isAwaitingSignature = str_contains($status, 'signature')
            || str_contains($status, 'pending sign')
            || str_contains($status, 'for sign')
            || str_contains($status, 'approved');
        // "Pending Forward" means the document already passed action stage
        // (approved/signed/etc.) but has not been forwarded/routed yet.
        $isApprovedForForwardStage = str_contains($status, 'approved')
            || str_contains($status, 'signed')
            || str_contains($status, 'verified')
            || str_contains($status, 'endorsed')
            || str_contains($status, 'checked')
            || str_contains($status, 'validated');
        $isForwarded = str_contains($status, 'forward') || str_contains($status, 'route') || str_starts_with($status, 'assigned to ');

        if ($isTerminal) {
            $counters['completed_total'] += 1;
            continue;
        }

        $isIntakeDraft = role_queue_row_is_intake_draft($queueRow, $currentUserId);
        if ($isIntakeDraft) {
            $counters['intake_draft_total'] += 1;
        }

        $isManagedByCurrentOffice = true;
        if ($pendingOfficeId > 0) {
            $isManagedByCurrentOffice = $currentOfficeId > 0 && $pendingOfficeId === $currentOfficeId;
        } elseif ($rowCurrentOfficeId > 0) {
            $isManagedByCurrentOffice = $currentOfficeId > 0 && $rowCurrentOfficeId === $currentOfficeId;
        }

        if (!$hasReceived) {
            $awaitingReceiveStatus = $status === ''
                || role_status_is_pending_receive_state($status)
                || str_contains($status, 'forward')
                || str_contains($status, 'pending');
            $canReceiveInScope = $isManagedByCurrentOffice && role_status_allows_receive($status);
            if ($canReceiveInScope && $awaitingReceiveStatus) {
                $counters['pending_receive_total'] += 1;
            }
        }

        // "Pending Approval" means already received by this office and not yet approved/signed/actioned.
        if ($isManagedByCurrentOffice && !$isIntakeDraft && $hasReceived && !$isApprovedForForwardStage && !$hasSignedAction && !$isForwarded) {
            $counters['pending_approval_total'] += 1;
        }

        if ($isManagedByCurrentOffice && $isAwaitingSignature && !$isSignedCompleted && !$isForwarded) {
            $counters['pending_sign_total'] += 1;
        }

        // "Pending Released" for Records Unit means already signed by ORED and
        // now received in RU custody, awaiting release back to origin.
        if ($isManagedByCurrentOffice && $hasReceived && $hasSignedAction && !$isReleased && !$isForwarded) {
            $counters['pending_release_total'] += 1;
        }

        // "Pending Forward" means approved/actioned but not yet forwarded/routed out.
        if ($isManagedByCurrentOffice && $isApprovedForForwardStage && !$isForwarded) {
            $counters['pending_forward_total'] += 1;
        }

        $counters['pending_total'] += 1;
    }

    return $counters;
}

function role_sanitize_sticky_actions(array $stickyActions, bool $isQueueLikePage): array
{
    $sanitized = [];
    $seen = [];
    $queueRedundantTokens = [
        'receive', 'approve', 'pending', 'forward', 'reroute', 'return', 'release', 'override', 'sign', 'view tracking slip', 'print slip', 'print package', 'search', 'filter'
    ];

    foreach ($stickyActions as $action) {
        $label = trim((string)$action);
        if ($label === '') continue;
        $normalized = strtolower(trim((string)preg_replace('/\s+/', ' ', $label)));
        if ($normalized === '' || isset($seen[$normalized])) continue;
        $isRedundant = false;
        if ($isQueueLikePage) {
            foreach ($queueRedundantTokens as $token) {
                if (str_contains($normalized, $token)) {
                    $isRedundant = true;
                    break;
                }
            }
        }
        if ($isRedundant) continue;
        $seen[$normalized] = true;
        $sanitized[] = $label;
    }
    return $sanitized;
}

function role_sticky_action_tooltip(string $label): string
{
    $normalized = strtolower(trim((string)preg_replace('/\s+/', ' ', $label)));
    if ($normalized === '') return 'Run this quick action.';
    if (str_contains($normalized, 'save draft')) return 'Save the current intake form values locally in this browser.';
    if (str_contains($normalized, 'submit intake')) return 'Submit the intake form and generate a tracking record.';
    if (str_contains($normalized, 'export')) return 'Export currently visible table rows to CSV.';
    if (str_contains($normalized, 'search')) return 'Focus the search bar for faster queue filtering.';
    if (str_contains($normalized, 'generate')) return 'Generate the requested output from current data.';
    return 'Run quick action: ' . trim($label);
}

function role_sanitize_page_actions(array $pageActions, bool $isQueueLikePage): array
{
    $sanitized = [];
    $seen = [];
    foreach ($pageActions as $action) {
        $label = trim((string)$action);
        if ($label === '') continue;
        $normalized = strtolower(trim((string)preg_replace('/\s+/', ' ', $label)));
        if ($normalized === '' || isset($seen[$normalized])) continue;
        if ($isQueueLikePage && $normalized === 'pending') continue;
        $seen[$normalized] = true;
        $sanitized[] = $label;
    }
    return $sanitized;
}

function role_metric_value_for_label(string $label, array $metrics, array $activityCounters, int $returnedTotal, array $queueCounters = []): ?int
{
    $labelKey = strtolower(trim($label));
    if ($labelKey === '') {
        return null;
    }

    if (str_contains($labelKey, 'encoded today') || str_contains($labelKey, 'created today')) {
        return (int)($metrics['created_today'] ?? 0);
    }
    if (str_contains($labelKey, 'external intake')) {
        return (int)($metrics['external_today'] ?? 0);
    }
    if (str_contains($labelKey, 'for clearance')) {
        return (int)($metrics['for_clearance_total'] ?? 0);
    }
    if (str_contains($labelKey, 'for endorsement')) {
        return (int)($metrics['for_endorsement_total'] ?? 0);
    }
    if (
        str_contains($labelKey, 'for signature')
        || preg_match('/\brd\b/', $labelKey) === 1
        || str_contains($labelKey, 'my signature')
    ) {
        return (int)($metrics['for_signature_total'] ?? 0);
    }
    if (str_contains($labelKey, 'site validation') || str_contains($labelKey, 'validation')) {
        return (int)($metrics['for_validation_total'] ?? 0);
    }
    if (str_contains($labelKey, 'completed this week')) {
        return (int)($metrics['completed_week'] ?? 0);
    }
    if (str_contains($labelKey, 'completed today')) {
        return (int)($metrics['completed_today'] ?? $metrics['completed_total'] ?? 0);
    }
    if (str_contains($labelKey, 'ongoing docs') || str_contains($labelKey, 'ongoing')) {
        if (array_key_exists('pending_total', $queueCounters)) {
            return (int)$queueCounters['pending_total'];
        }
        return (int)($metrics['pending_total'] ?? 0);
    }
    if (str_contains($labelKey, 'intake draft')) {
        if (array_key_exists('intake_draft_total', $queueCounters)) {
            return (int)$queueCounters['intake_draft_total'];
        }
        return (int)($metrics['created_today'] ?? 0);
    }

    if ($labelKey === 'completed') {
        return (int)($metrics['completed_total'] ?? 0);
    }
    if (str_contains($labelKey, 'pending released')) {
        $metricPendingRelease = (int)($metrics['pending_release_total'] ?? 0);
        $metricPendingForward = (int)($metrics['pending_forward_total'] ?? 0);
        if (array_key_exists('pending_release_total', $queueCounters)) {
            return max((int)$queueCounters['pending_release_total'], $metricPendingRelease, $metricPendingForward);
        }
        if (array_key_exists('pending_forward_total', $queueCounters)) {
            return max((int)$queueCounters['pending_forward_total'], $metricPendingForward);
        }
        return max($metricPendingRelease, $metricPendingForward);
    }
    if (str_contains($labelKey, 'released')) {
        return (int)($metrics['released_total'] ?? 0);
    }
    if (str_contains($labelKey, 'due soon / overdue')) {
        return (int)($metrics['due_soon_total'] ?? 0) + (int)($metrics['overdue_total'] ?? 0);
    }
    if (str_contains($labelKey, 'due today / due soon') || str_contains($labelKey, 'due soon / due today')) {
        return (int)($metrics['due_today_total'] ?? 0) + (int)($metrics['due_soon_total'] ?? 0);
    }
    if (str_contains($labelKey, 'due soon')) {
        return (int)($metrics['due_soon_total'] ?? 0);
    }
    if (str_contains($labelKey, 'overdue') || str_contains($labelKey, 'at-risk') || str_contains($labelKey, 'at risk') || str_contains($labelKey, 'violations')) {
        return (int)($metrics['overdue_total'] ?? 0);
    }
    if (str_contains($labelKey, 'pending approval')) {
        if (array_key_exists('pending_approval_total', $queueCounters)) {
            return (int)$queueCounters['pending_approval_total'];
        }
        return (int)($metrics['pending_approval_total'] ?? 0);
    }
    if (str_contains($labelKey, 'pending sign')) {
        $metricPendingSign = (int)($metrics['pending_sign_total'] ?? 0);
        if (array_key_exists('pending_sign_total', $queueCounters)) {
            return max((int)$queueCounters['pending_sign_total'], $metricPendingSign);
        }
        return $metricPendingSign;
    }
    if (str_contains($labelKey, 'pending forward')) {
        $metricPendingForward = (int)($metrics['pending_forward_total'] ?? 0);
        if (array_key_exists('pending_forward_total', $queueCounters)) {
            return max((int)$queueCounters['pending_forward_total'], $metricPendingForward);
        }
        return $metricPendingForward;
    }
    if (
        str_contains($labelKey, 'pending receive')
        || str_contains($labelKey, 'pending received')
        || str_contains($labelKey, 'pending recieved')
    ) {
        if (array_key_exists('pending_receive_total', $queueCounters)) {
            return (int)$queueCounters['pending_receive_total'];
        }
        return (int)($metrics['pending_total'] ?? 0);
    }
    if (str_contains($labelKey, 'pending')) {
        if (array_key_exists('pending_total', $queueCounters)) {
            return (int)$queueCounters['pending_total'];
        }
        return (int)($metrics['pending_total'] ?? 0);
    }
    if (str_contains($labelKey, 'returned')) {
        return (int)($metrics['returned_total'] ?? $returnedTotal);
    }

    if ($labelKey === 'received' || str_contains($labelKey, 'received events')) {
        return (int)($activityCounters['received_events'] ?? 0);
    }
    if ($labelKey === 'approved' || str_contains($labelKey, 'approved events')) {
        return (int)($activityCounters['approved_events'] ?? 0);
    }
    if ($labelKey === 'forwarded' || str_contains($labelKey, 'forwarded events')) {
        return (int)($activityCounters['forwarded_events'] ?? 0);
    }
    if (str_contains($labelKey, 'reroute events')) {
        return (int)($activityCounters['reroute_events'] ?? 0);
    }

    return null;
}

function role_name_matches_records_unit(string $name): bool
{
    $normalized = strtoupper(trim($name));
    if ($normalized === '') return false;
    return str_contains($normalized, 'PACDO')
        || str_contains($normalized, 'RECORDS-UNIT')
        || str_contains($normalized, 'RECORDS UNIT')
        || str_contains($normalized, 'RECORDS_UNIT')
        || str_contains($normalized, 'RECORDSUNIT');
}

function role_assignment_tags_from_office(array $officeContext): array
{
    $officeName = trim((string)($officeContext['name'] ?? ''));
    $officeLevel = strtoupper(trim((string)($officeContext['level'] ?? '')));
    $parentName = trim((string)($officeContext['parent_name'] ?? ''));

    if ($officeName === '') {
        return ['primary' => '', 'secondary' => '', 'caption' => ''];
    }

    $primary = 'Office: ' . $officeName;
    $secondary = '';

    if ($officeLevel === 'CENRO_ADMIN_RECORD') {
        $primary = 'CENRO Admin Records: ' . $officeName;
        if ($parentName !== '') $secondary = 'CENRO Officer: ' . $parentName;
    } elseif ($officeLevel === 'PAMO_ADMIN') {
        $primary = 'PAMO Admin: ' . $officeName;
        if ($parentName !== '') $secondary = 'PASU: ' . $parentName;
    } elseif ($officeLevel === 'PENRO_ADMIN_RECORD') {
        $primary = 'PENRO Admin Records: ' . $officeName;
        if ($parentName !== '') $secondary = 'PENRO Officer: ' . $parentName;
    } elseif ($officeLevel === 'CENRO_OFFICER') {
        $primary = 'CENRO Officer: ' . $officeName;
        if ($parentName !== '') $secondary = 'CENRO: ' . $parentName;
    } elseif ($officeLevel === 'PASU_OFFICER') {
        $primary = 'PASU: ' . $officeName;
        if ($parentName !== '') $secondary = 'PAMO: ' . $parentName;
    } elseif ($officeLevel === 'PENRO_OFFICER') {
        $primary = 'PENRO Officer: ' . $officeName;
        if ($parentName !== '') $secondary = 'PENRO: ' . $parentName;
    } elseif ($officeLevel === 'CENRO_SECTION') {
        $primary = 'CENRO Section: ' . $officeName;
        if ($parentName !== '') $secondary = 'CENRO Officer: ' . $parentName;
    } elseif ($officeLevel === 'PENRO_DIVISION') {
        $primary = 'PENRO Division: ' . $officeName;
        if ($parentName !== '') $secondary = 'PENRO Officer: ' . $parentName;
    } elseif ($officeLevel === 'CENRO_UNIT') {
        $primary = 'CENRO Unit: ' . $officeName;
        if ($parentName !== '') $secondary = 'CENRO Section: ' . $parentName;
    } elseif ($officeLevel === 'PAMO_UNIT') {
        $primary = 'PAMO Unit: ' . $officeName;
        if ($parentName !== '') $secondary = 'PASU: ' . $parentName;
    } elseif ($officeLevel === 'PENRO_SECTION') {
        $primary = 'PENRO Section: ' . $officeName;
        if ($parentName !== '') $secondary = 'PENRO Division: ' . $parentName;
    } elseif ($officeLevel === 'SECTION') {
        $primary = 'Section: ' . $officeName;
        if ($parentName !== '') $secondary = 'Division: ' . $parentName;
    } elseif ($officeLevel === 'DIVISION') {
        $primary = 'Division: ' . $officeName;
    } elseif ($officeLevel === 'PROVINCIAL') {
        $primary = 'PENRO: ' . $officeName;
    } elseif ($officeLevel === 'COMMUNITY') {
        $primary = 'CENRO: ' . $officeName;
        if ($parentName !== '') $secondary = 'PENRO: ' . $parentName;
    } elseif ($officeLevel === 'PROTECTED AREA') {
        $primary = 'PASU: ' . $officeName;
        if ($parentName !== '') $secondary = 'PENRO: ' . $parentName;
    } elseif ($officeLevel === 'REGIONAL') {
        if (role_name_matches_records_unit($officeName)) {
            $primary = 'Regional: RECORDS-UNIT';
        } elseif (stripos($officeName, 'ORED') !== false) {
            $primary = 'Regional: ORED';
        } else {
            $primary = 'Regional: ' . $officeName;
        }
    }

    $caption = $secondary !== '' ? ($secondary . ' | ' . $primary) : $primary;
    return ['primary' => $primary, 'secondary' => $secondary, 'caption' => $caption];
}

function role_find_records_unit_route_office(PDO $pdo): ?array
{
    $stmt = $pdo->query("SELECT id, name, level FROM offices WHERE UPPER(name) LIKE '%PACDO%' OR UPPER(name) LIKE '%RECORDS-UNIT%' OR UPPER(name) LIKE '%RECORDS UNIT%' OR UPPER(name) LIKE '%RECORDS_UNIT%' ORDER BY id ASC");
    $office = $stmt ? $stmt->fetch() : false;
    if ($office) {
        return ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')];
    }
    $fallbackStmt = $pdo->query("SELECT o.id, o.name, o.level FROM offices o INNER JOIN users u ON u.office_id = o.id INNER JOIN roles r ON r.id = u.role_id WHERE UPPER(REPLACE(REPLACE(r.name, '_', ' '), '-', ' ')) IN ('PACDO', 'RECORDS UNIT') ORDER BY o.id ASC");
    $office = $fallbackStmt ? $fallbackStmt->fetch() : false;
    if (!$office) return null;
    return ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')];
}

function role_find_pacdo_route_office(PDO $pdo): ?array
{
    return role_find_records_unit_route_office($pdo);
}

function role_find_ored_route_office(PDO $pdo): ?array
{
    $stmt = $pdo->query("SELECT id, name, level FROM offices WHERE UPPER(name) LIKE '%ORED%' OR UPPER(name) LIKE '%REGIONAL EXECUTIVE%' ORDER BY id ASC");
    $office = $stmt ? $stmt->fetch() : false;
    if ($office) {
        return ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')];
    }
    $fallbackStmt = $pdo->query("SELECT o.id, o.name, o.level FROM offices o INNER JOIN users u ON u.office_id = o.id INNER JOIN roles r ON r.id = u.role_id WHERE UPPER(r.name) IN ('ORED', 'ORED_SIGN') ORDER BY o.id ASC");
    $office = $fallbackStmt ? $fallbackStmt->fetch() : false;
    if (!$office) return null;
    return ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')];
}

function role_ard_track_from_role_key(string $roleKey): ?string
{
    $normalized = app_normalize_role_key($roleKey);
    if ($normalized === 'ARD_TS') return 'TS';
    if ($normalized === 'ARD_MS') return 'MS';
    return null;
}

function role_office_name_key(string $name): string
{
    $normalized = strtoupper(trim($name));
    $normalized = preg_replace('/[^A-Z0-9]+/', '', $normalized) ?? '';
    return $normalized;
}

function role_division_track_from_name(string $officeName): ?string
{
    static $tsDivisionKeys = null;
    static $msDivisionKeys = null;
    if (!is_array($tsDivisionKeys) || !is_array($msDivisionKeys)) {
        $tsDivisionKeys = array_map('role_office_name_key', ['Licenses, Patents & Deeds Division', 'Surveys & Mapping Division', 'Conservation & Devt. Division', 'Enforcement Division']);
        $msDivisionKeys = array_map('role_office_name_key', ['Legal Division', 'Planning and Mgt. Division', 'Planning and Management Division (PMD)', 'Administrative Division', 'Finance Division']);
    }
    $key = role_office_name_key($officeName);
    if ($key === '') return null;
    if (in_array($key, $tsDivisionKeys, true)) return 'TS';
    if (in_array($key, $msDivisionKeys, true)) return 'MS';
    return null;
}

function role_ard_track_from_office_name(string $officeName): ?string
{
    $name = strtoupper(trim($officeName));
    $key = role_office_name_key($officeName);
    if (str_contains($key, 'ARDTS') || (str_contains($name, 'ASSISTANT REGIONAL DIRECTOR') && str_contains($name, 'TECHNICAL')) || str_contains($name, 'TECHNICAL SERVICES')) return 'TS';
    if (str_contains($key, 'ARDMS') || (str_contains($name, 'ASSISTANT REGIONAL DIRECTOR') && str_contains($name, 'MANAGEMENT')) || str_contains($name, 'MANAGEMENT SERVICES')) return 'MS';
    return null;
}

function role_find_ard_route_office_by_track(PDO $pdo, string $track): ?array
{
    $normalizedTrack = strtoupper(trim($track));
    if (!in_array($normalizedTrack, ['TS', 'MS'], true)) return null;
    $roleName = $normalizedTrack === 'TS' ? 'ARD_TS' : 'ARD_MS';
    $stmt = $pdo->prepare("SELECT o.id, o.name, o.level FROM offices o INNER JOIN users u ON u.office_id = o.id INNER JOIN roles r ON r.id = u.role_id WHERE UPPER(r.name) = :role_name ORDER BY o.id ASC");
    $stmt->execute(['role_name' => $roleName]);
    $office = $stmt->fetch();
    if (!$office) {
        $likeNeedle = $normalizedTrack === 'TS' ? '%TECHNICAL%' : '%MANAGEMENT%';
        $fallback = $pdo->prepare("SELECT id, name, level FROM offices WHERE UPPER(name) LIKE :needle OR UPPER(name) LIKE :ard_short ORDER BY id ASC");
        $fallback->execute(['needle' => $likeNeedle, 'ard_short' => '%ARD ' . $normalizedTrack . '%']);
        $office = $fallback->fetch();
    }
    if (!$office) return null;
    return ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')];
}

function role_find_parent_ard_route_office(PDO $pdo, int $divisionOfficeId): ?array
{
    if ($divisionOfficeId <= 0) return null;
    $stmt = $pdo->prepare("SELECT parent.id, parent.name, parent.level FROM offices child INNER JOIN offices parent ON parent.id = child.parent_office_id WHERE child.id = :division_office_id AND UPPER(COALESCE(child.level, '')) = 'DIVISION'");
    $stmt->execute(['division_office_id' => $divisionOfficeId]);
    $parentOffice = $stmt->fetch();
    if ($parentOffice && role_ard_track_from_office_name((string)($parentOffice['name'] ?? '')) !== null) {
        return ['id' => (int)($parentOffice['id'] ?? 0), 'name' => (string)($parentOffice['name'] ?? ''), 'level' => (string)($parentOffice['level'] ?? '')];
    }
    $divisionStmt = $pdo->prepare('SELECT TOP (1) name FROM offices WHERE id = :id');
    $divisionStmt->execute(['id' => $divisionOfficeId]);
    $divisionName = (string)($divisionStmt->fetchColumn() ?: '');
    $track = role_division_track_from_name($divisionName);
    if ($track === null) return null;
    return role_find_ard_route_office_by_track($pdo, $track);
}

function role_find_division_route_offices(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, name, level FROM offices WHERE UPPER(COALESCE(level, '')) = 'DIVISION' ORDER BY name ASC, id ASC");
    $rows = $stmt ? ($stmt->fetchAll() ?: []) : [];
    if (!empty($rows)) {
        $offices = [];
        foreach ($rows as $office) {
            $offices[] = ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')];
        }
        return $offices;
    }
    $fallbackStmt = $pdo->query("SELECT DISTINCT o.id, o.name, o.level FROM offices o INNER JOIN users u ON u.office_id = o.id INNER JOIN roles r ON r.id = u.role_id WHERE UPPER(r.name) = 'DIVISION_CHIEF' ORDER BY o.name ASC, o.id ASC");
    $rows = $fallbackStmt ? ($fallbackStmt->fetchAll() ?: []) : [];
    if (empty($rows)) return [];
    $offices = [];
    foreach ($rows as $office) {
        $offices[] = ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')];
    }
    return $offices;
}

function role_find_division_route_office(PDO $pdo): ?array
{
    $offices = role_find_division_route_offices($pdo);
    return empty($offices) ? null : $offices[0];
}

function role_find_section_route_offices(PDO $pdo, int $divisionOfficeId = 0, bool $allowGlobalFallback = true): array
{
    $rows = [];
    if ($divisionOfficeId > 0) {
        $stmt = $pdo->prepare("SELECT id, name, level, parent_office_id FROM offices WHERE parent_office_id = :parent_id AND UPPER(COALESCE(level, '')) = 'SECTION' ORDER BY name ASC, id ASC");
        $stmt->execute(['parent_id' => $divisionOfficeId]);
        $rows = $stmt->fetchAll() ?: [];
    }
    if (empty($rows) && $allowGlobalFallback) {
        $stmt = $pdo->query("SELECT id, name, level, parent_office_id FROM offices WHERE UPPER(COALESCE(level, '')) = 'SECTION' ORDER BY name ASC, id ASC");
        $rows = $stmt ? ($stmt->fetchAll() ?: []) : [];
    }
    $offices = [];
    foreach ($rows as $office) {
        $offices[] = ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? ''), 'parent_office_id' => (int)($office['parent_office_id'] ?? 0)];
    }
    return $offices;
}

function role_find_section_staff_route_offices(PDO $pdo, int $divisionOfficeId = 0): array
{
    $params = [];
    $where = "UPPER(r.name) = 'SECTION_STAFF'";

    if ($divisionOfficeId > 0) {
        $where .= ' AND (o.id = :division_office_id OR o.parent_office_id = :division_office_id)';
        $params['division_office_id'] = $divisionOfficeId;
    }

    $sql = 'SELECT DISTINCT
                o.id,
                o.name,
                o.level,
                o.parent_office_id
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            INNER JOIN offices o ON o.id = u.office_id
            WHERE ' . $where . '
            ORDER BY
                CASE
                    WHEN o.id = :division_sort_id THEN 0
                    ELSE 1
                END,
                o.name ASC,
                o.id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, ['division_sort_id' => $divisionOfficeId]));
    $rows = $stmt->fetchAll() ?: [];
    if (empty($rows)) {
        return [];
    }

    $offices = [];
    foreach ($rows as $office) {
        $offices[] = [
            'id' => (int)($office['id'] ?? 0),
            'name' => (string)($office['name'] ?? ''),
            'level' => (string)($office['level'] ?? ''),
            'parent_office_id' => (int)($office['parent_office_id'] ?? 0),
            'has_section_staff' => '1',
        ];
    }

    return $offices;
}

function role_find_parent_division_route_office(PDO $pdo, int $sectionOfficeId): ?array
{
    if ($sectionOfficeId <= 0) return null;
    $stmt = $pdo->prepare("SELECT parent.id, parent.name, parent.level FROM offices child INNER JOIN offices parent ON parent.id = child.parent_office_id WHERE child.id = :section_office_id AND UPPER(COALESCE(parent.level, '')) IN ('DIVISION', 'CENRO_SECTION', 'PENRO_DIVISION', 'PASU_OFFICER')");
    $stmt->execute(['section_office_id' => $sectionOfficeId]);
    $office = $stmt->fetch();
    return $office ? ['id' => (int)($office['id'] ?? 0), 'name' => (string)($office['name'] ?? ''), 'level' => (string)($office['level'] ?? '')] : null;
}
