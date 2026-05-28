<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/dashboard-data.php';

@ini_set('display_errors', '0');
ob_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$emitJson = static function (array $payload, int $statusCode = 200): never {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code($statusCode);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
};

if (empty($_SESSION['user_id'])) {
    $emitJson([
        'ok' => false,
        'message' => 'Authentication required.',
    ], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $emitJson([
        'ok' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$officeId = (int)($_SESSION['office_id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);
$roleKey = app_normalize_role_key((string)($_SESSION['role_name'] ?? ''));
$limit = (int)($_GET['limit'] ?? 50);
$limit = max(5, min($limit, 100));
$scope = strtolower(trim((string)($_GET['scope'] ?? 'queue')));
$actionsRaw = trim((string)($_GET['actions'] ?? ''));
$excludeReturned = trim((string)($_GET['exclude_returned'] ?? '')) === '1';

if ($officeId <= 0) {
    $emitJson([
        'ok' => true,
        'queue_rows' => [],
        'metrics' => [],
        'activity_counters' => [],
        'returned_total' => 0,
        'server_time' => date('c'),
    ]);
}

try {
    $pdo = getDatabaseConnection();
    $queueRows = [];
    $metrics = [];
    $activityCounters = [];
    $artaDistribution = [];
    $workflowTrend = [];

    try {
        if ($scope === 'archive') {
            $queueRows = dashboard_fetch_archive_rows($pdo, $officeId, $limit);
        } elseif ($scope === 'origin') {
            $queueRows = dashboard_fetch_origin_tracker_rows($pdo, $officeId, $limit);
        } elseif ($scope === 'pending_receive') {
            $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, $limit, false, $excludeReturned, $userId, $roleKey);
        } elseif ($scope === 'pending_receive_action') {
            $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, $limit, true, $excludeReturned, $userId, $roleKey);
        } elseif ($scope === 'pending_receive_penro') {
            $queueRows = dashboard_fetch_pending_receive_rows($pdo, $officeId, $limit, true, $excludeReturned, $userId, $roleKey);
        } elseif ($scope === 'for_cenro_action') {
            $queueRows = dashboard_fetch_for_cenro_action_rows($pdo, $officeId, $limit);
        } elseif ($scope === 'office_action') {
            $queueRows = dashboard_fetch_office_action_rows($pdo, $officeId, $limit);
        } elseif ($scope === 'returned_regional') {
            $queueRows = dashboard_fetch_returned_from_regional_rows($pdo, $officeId, $limit);
        } elseif ($scope === 'outbox') {
            $queueRows = dashboard_fetch_outbox_rows($pdo, $officeId, $limit);
        } elseif ($scope === 'approved' || $scope === 'acted') {
            $actionTypes = [];
            if ($actionsRaw !== '') {
                foreach (explode(',', $actionsRaw) as $segment) {
                    $value = trim((string)$segment);
                    if ($value !== '') {
                        $actionTypes[] = $value;
                    }
                }
            }
            if (empty($actionTypes)) {
                $actionTypes = ['Approved', 'Signed'];
            }
            $queueRows = dashboard_fetch_actor_tracker_rows($pdo, $userId, $actionTypes, $limit);
        } elseif ($scope === 'division_staff') {
            $actionTypes = [];
            if ($actionsRaw !== '') {
                foreach (explode(',', $actionsRaw) as $segment) {
                    $value = trim((string)$segment);
                    if ($value !== '') {
                        $actionTypes[] = $value;
                    }
                }
            }
            if (empty($actionTypes)) {
                $actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'];
            }
            $queueRows = dashboard_fetch_division_staff_tracker_rows($pdo, $officeId, $actionTypes, $limit);
        } elseif ($scope === 'ard_division') {
            $actionTypes = [];
            if ($actionsRaw !== '') {
                foreach (explode(',', $actionsRaw) as $segment) {
                    $value = trim((string)$segment);
                    if ($value !== '') {
                        $actionTypes[] = $value;
                    }
                }
            }
            if (empty($actionTypes)) {
                $actionTypes = ['Approved', 'Forwarded', 'Returned', 'Rerouted'];
            }
            $queueRows = dashboard_fetch_ard_division_tracker_rows($pdo, $officeId, $actionTypes, $limit);
        } else {
            $queueRows = dashboard_fetch_queue_rows($pdo, $officeId, $limit, null, $userId, $roleKey);
        }
    } catch (Throwable $exception) {
        $queueRows = [];
    }

    try {
        $metrics = dashboard_fetch_role_metrics($pdo, $officeId, null, $userId, $roleKey);
    } catch (Throwable $exception) {
        $metrics = [];
    }

    try {
        $activityCounters = dashboard_fetch_activity_counters($pdo, $officeId, 30);
    } catch (Throwable $exception) {
        $activityCounters = [];
    }

    try {
        $artaDistribution = dashboard_fetch_arta_distribution($pdo, $officeId);
    } catch (Throwable $exception) {
        $artaDistribution = [];
    }

    try {
        $workflowTrend = dashboard_fetch_workflow_trend($pdo, $officeId, 7);
    } catch (Throwable $exception) {
        $workflowTrend = [];
    }

    $returnedTotal = 0;
    foreach ($queueRows as $queueRow) {
        $status = strtolower(trim((string)($queueRow['status'] ?? '')));
        if (str_contains($status, 'return')) {
            $returnedTotal++;
        }
    }

    if (in_array($scope, ['pending_receive', 'pending_receive_action', 'pending_receive_penro'], true)) {
        $metrics['pending_total'] = count($queueRows);
        $metrics['returned_total'] = $returnedTotal;
    }

    $payload = [
        'ok' => true,
        'queue_rows' => $queueRows,
        'metrics' => $metrics,
        'activity_counters' => $activityCounters,
        'arta_distribution' => $artaDistribution,
        'workflow_trend' => $workflowTrend,
        'returned_total' => $returnedTotal,
        'date_range' => dashboard_effective_date_range(),
        'server_time' => date('c'),
    ];
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($encoded) || $encoded === '') {
        throw new RuntimeException('Unable to encode live dashboard payload.');
    }
    $emitJson($payload);
} catch (Throwable $exception) {
    $emitJson([
        'ok' => false,
        'message' => 'Unable to load live dashboard data right now.',
    ], 500);
}
