<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/super-admin.php';
require_once dirname(__DIR__, 2) . '/config/document-recycle-bin.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if (!super_admin_has_access()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Super Admin access required.']);
    exit;
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

function super_admin_documents_summary(array $documents): array
{
    $summary = [
        'documents_total' => 0,
        'documents_active' => 0,
        'documents_completed' => 0,
        'documents_overdue' => 0,
        'attachments_total' => 0,
    ];

    foreach ($documents as $document) {
        if (!is_array($document)) {
            continue;
        }

        $summary['documents_total'] += 1;
        $summary['attachments_total'] += max(0, (int)($document['attachment_count'] ?? 0));

        $statusCategory = trim((string)($document['status_category'] ?? 'active'));
        if ($statusCategory === 'completed') {
            $summary['documents_completed'] += 1;
            continue;
        }

        $summary['documents_active'] += 1;
        if ($statusCategory === 'overdue') {
            $summary['documents_overdue'] += 1;
        }
    }

    return $summary;
}

function super_admin_documents_payload(PDO $pdo): array
{
    $documents = super_admin_fetch_document_management_documents($pdo, 800);

    return [
        'ok' => true,
        'documents' => $documents,
        'summary' => super_admin_documents_summary($documents),
        'server_time' => date('c'),
    ];
}

try {
    $pdo = getDatabaseConnection();

    if ($method === 'GET') {
        echo json_encode(super_admin_documents_payload($pdo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token. Refresh and try again.']);
        exit;
    }

    $action = strtoupper(trim((string)($_POST['action'] ?? '')));
    if ($action !== 'DELETE_DOCUMENT') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Unsupported action.']);
        exit;
    }

    $documentId = (int)($_POST['document_id'] ?? 0);
    if ($documentId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'A valid document is required.']);
        exit;
    }

    $documentStmt = $pdo->prepare(
        'SELECT TOP (1) id, tracking_id, subject
         FROM documents
         WHERE id = :document_id'
    );
    $documentStmt->execute(['document_id' => $documentId]);
    $document = $documentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$document) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Document not found.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        document_recycle_bin_archive_document($pdo, $documentId, (int)$_SESSION['user_id']);

        foreach (['document_attachments', 'tracking_slips', 'activity_logs'] as $tableName) {
            if (!db_table_exists($pdo, $tableName)) {
                continue;
            }

            $deleteStmt = $pdo->prepare(
                'DELETE FROM ' . $tableName . '
                 WHERE document_id = :document_id'
            );
            $deleteStmt->execute(['document_id' => $documentId]);
        }

        $deleteDocumentStmt = $pdo->prepare(
            'DELETE FROM documents
             WHERE id = :document_id'
        );
        $deleteDocumentStmt->execute(['document_id' => $documentId]);
        if ($deleteDocumentStmt->rowCount() < 1) {
            throw new RuntimeException('Unable to move document to recycle bin.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = trim((string)$exception->getMessage());
        if ($message === '') {
            $message = 'Unable to move document to recycle bin right now.';
        }

        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $payload = super_admin_documents_payload($pdo);
    $payload['message'] = 'Document moved to recycle bin: ' . trim((string)($document['tracking_id'] ?? ''));
    $payload['deleted_document'] = [
        'document_id' => $documentId,
        'tracking_id' => trim((string)($document['tracking_id'] ?? '')),
        'subject' => trim((string)($document['subject'] ?? '')),
    ];

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to process Super Admin document request right now.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
