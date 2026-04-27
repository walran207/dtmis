<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/document-type-requests.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$action = strtoupper(trim((string)($_POST['action'] ?? '')));
$requestId = (int)($_POST['request_id'] ?? 0);
$requestedName = trim((string)($_POST['requested_name'] ?? ''));
$requestedCategoryRaw = trim((string)($_POST['requested_category'] ?? ''));
$requestedDaysRaw = (int)($_POST['requested_days'] ?? 0);
$justification = trim((string)($_POST['justification'] ?? ''));
$reviewRemarks = trim((string)($_POST['review_remarks'] ?? ''));

try {
    $pdo = getDatabaseConnection();
    if (!dtr_table_exists($pdo)) {
        throw new RuntimeException('Document type request table is missing. Apply latest migration first.');
    }

    $actor = dtr_user_context($pdo, (int)$_SESSION['user_id']);
    $actorRole = (string)($actor['role_name'] ?? '');
    $isRequester = dtr_is_requester_role($actorRole);
    $isReviewer = dtr_is_reviewer_role($actorRole);

    $hasRequestedCategoryInput = $requestedCategoryRaw !== '';
    $hasRequestedDaysInput = $requestedDaysRaw > 0;
    $defaults = dtr_category_defaults($hasRequestedCategoryInput ? $requestedCategoryRaw : 'Simple');
    $requestedCategory = (string)$defaults['category'];
    $requestedColor = (string)$defaults['color'];
    $requestedDays = $hasRequestedDaysInput ? $requestedDaysRaw : (int)$defaults['days'];
    $requestedDays = max(1, min(365, $requestedDays));

    if (in_array($action, ['REQUEST_CREATE', 'REQUEST_UPDATE', 'REQUEST_APPROVE'], true) && $requestedName === '') {
        throw new InvalidArgumentException('Document type name is required.');
    }

    switch ($action) {
        case 'REQUEST_CREATE':
            if (!$isRequester) {
                throw new RuntimeException('Only PASU, CENRO, and PENRO can create document type requests.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO document_type_requests (
                    requested_name, requested_category, requested_days, requested_color, justification,
                    requested_by_user_id, requested_by_office_id, status, created_at, updated_at
                ) VALUES (
                    :requested_name, :requested_category, :requested_days, :requested_color, :justification,
                    :requested_by_user_id, :requested_by_office_id, \'PENDING\', NOW(), NOW()
                )'
            );
            $insert->execute([
                'requested_name' => $requestedName,
                'requested_category' => $requestedCategory,
                'requested_days' => $requestedDays,
                'requested_color' => $requestedColor,
                'justification' => $justification === '' ? null : $justification,
                'requested_by_user_id' => (int)$actor['id'],
                'requested_by_office_id' => (int)$actor['office_id'],
            ]);

            echo json_encode(['ok' => true, 'message' => 'Document type request submitted.']);
            exit;

        case 'REQUEST_UPDATE':
            if ($requestId <= 0) {
                throw new InvalidArgumentException('Request ID is required.');
            }
            if (!$isRequester && !$isReviewer) {
                throw new RuntimeException('You are not allowed to edit requests.');
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT * FROM document_type_requests WHERE id = :id LIMIT 1 FOR UPDATE');
                $stmt->execute(['id' => $requestId]);
                $request = $stmt->fetch();
                if (!$request) {
                    throw new RuntimeException('Request not found.');
                }

                if ($isRequester) {
                    if ((int)$request['requested_by_user_id'] !== (int)$actor['id']) {
                        throw new RuntimeException('You can only edit your own request.');
                    }
                    if (strtoupper((string)$request['status']) !== 'PENDING') {
                        throw new RuntimeException('Only pending requests can be edited.');
                    }
                }
                if ($isReviewer && strtoupper((string)$request['status']) === 'APPROVED') {
                    throw new RuntimeException('Approved requests can no longer be edited.');
                }

                $update = $pdo->prepare(
                    'UPDATE document_type_requests
                     SET requested_name = :requested_name,
                         requested_category = :requested_category,
                         requested_days = :requested_days,
                         requested_color = :requested_color,
                         justification = :justification,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $currentCategoryRaw = trim((string)($request['requested_category'] ?? 'Simple'));
                $resolvedCategoryData = dtr_category_defaults($hasRequestedCategoryInput ? $requestedCategoryRaw : $currentCategoryRaw);
                $resolvedCategory = (string)$resolvedCategoryData['category'];
                $resolvedColor = (string)$resolvedCategoryData['color'];
                $currentDays = (int)($request['requested_days'] ?? 0);
                $resolvedDays = $hasRequestedDaysInput
                    ? $requestedDaysRaw
                    : ($currentDays > 0 ? $currentDays : (int)$resolvedCategoryData['days']);
                $resolvedDays = max(1, min(365, $resolvedDays));

                $update->execute([
                    'requested_name' => $requestedName,
                    'requested_category' => $resolvedCategory,
                    'requested_days' => $resolvedDays,
                    'requested_color' => $resolvedColor,
                    'justification' => $justification === '' ? null : $justification,
                    'id' => $requestId,
                ]);

                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }

            echo json_encode(['ok' => true, 'message' => 'Request updated.']);
            exit;

        case 'REQUEST_DELETE':
            if ($requestId <= 0) {
                throw new InvalidArgumentException('Request ID is required.');
            }
            if (!$isRequester && !$isReviewer) {
                throw new RuntimeException('You are not allowed to delete requests.');
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT * FROM document_type_requests WHERE id = :id LIMIT 1 FOR UPDATE');
                $stmt->execute(['id' => $requestId]);
                $request = $stmt->fetch();
                if (!$request) {
                    throw new RuntimeException('Request not found.');
                }

                if ($isRequester) {
                    if ((int)$request['requested_by_user_id'] !== (int)$actor['id']) {
                        throw new RuntimeException('You can only delete your own request.');
                    }
                    if (strtoupper((string)$request['status']) !== 'PENDING') {
                        throw new RuntimeException('Only pending requests can be deleted by requester.');
                    }
                }

                $delete = $pdo->prepare('DELETE FROM document_type_requests WHERE id = :id');
                $delete->execute(['id' => $requestId]);
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }

            echo json_encode(['ok' => true, 'message' => 'Request deleted.']);
            exit;

        case 'REQUEST_APPROVE':
            if ($requestId <= 0) {
                throw new InvalidArgumentException('Request ID is required.');
            }
            if (!$isReviewer) {
                throw new RuntimeException('Only RECORDS-UNIT can approve requests.');
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT * FROM document_type_requests WHERE id = :id LIMIT 1 FOR UPDATE');
                $stmt->execute(['id' => $requestId]);
                $request = $stmt->fetch();
                if (!$request) {
                    throw new RuntimeException('Request not found.');
                }
                $currentStatus = strtoupper((string)($request['status'] ?? ''));
                if ($currentStatus !== 'PENDING') {
                    throw new RuntimeException('Only pending requests can be approved.');
                }

                $finalName = $requestedName !== '' ? $requestedName : (string)$request['requested_name'];
                $finalCategoryData = dtr_category_defaults($requestedCategoryRaw !== '' ? $requestedCategoryRaw : (string)$request['requested_category']);
                $finalCategory = (string)$finalCategoryData['category'];
                $finalColor = (string)$finalCategoryData['color'];
                $finalDays = $requestedDaysRaw > 0 ? $requestedDaysRaw : (int)($request['requested_days'] ?? (int)$finalCategoryData['days']);
                $finalDays = max(1, min(365, $finalDays));

                $linkedDocumentTypeId = (int)($request['linked_document_type_id'] ?? 0);
                if ($linkedDocumentTypeId > 0) {
                    $updateType = $pdo->prepare(
                        'UPDATE document_types
                         SET name = :name, category = :category, arta_days_limit = :days, indicator_color = :color, is_active = 1
                         WHERE id = :id'
                    );
                    $updateType->execute([
                        'name' => $finalName,
                        'category' => $finalCategory,
                        'days' => $finalDays,
                        'color' => $finalColor,
                        'id' => $linkedDocumentTypeId,
                    ]);
                } else {
                    $existingTypeStmt = $pdo->prepare('SELECT id FROM document_types WHERE LOWER(name) = LOWER(:name) LIMIT 1');
                    $existingTypeStmt->execute(['name' => $finalName]);
                    $existingTypeId = (int)($existingTypeStmt->fetchColumn() ?: 0);
                    if ($existingTypeId > 0) {
                        $linkedDocumentTypeId = $existingTypeId;
                        $updateType = $pdo->prepare(
                            'UPDATE document_types
                             SET category = :category, arta_days_limit = :days, indicator_color = :color, is_active = 1
                             WHERE id = :id'
                        );
                        $updateType->execute([
                            'category' => $finalCategory,
                            'days' => $finalDays,
                            'color' => $finalColor,
                            'id' => $linkedDocumentTypeId,
                        ]);
                    } else {
                        $insertType = $pdo->prepare(
                            'INSERT INTO document_types (
                                name, category, arta_days_limit, indicator_color, is_custom, created_by_role_id, is_active
                             ) VALUES (
                                :name, :category, :days, :color, 0, NULL, 1
                             )'
                        );
                        $insertType->execute([
                            'name' => $finalName,
                            'category' => $finalCategory,
                            'days' => $finalDays,
                            'color' => $finalColor,
                        ]);
                        $linkedDocumentTypeId = (int)$pdo->lastInsertId();
                    }
                }

                $approve = $pdo->prepare(
                    'UPDATE document_type_requests
                     SET requested_name = :requested_name,
                         requested_category = :requested_category,
                         requested_days = :requested_days,
                         requested_color = :requested_color,
                         status = \'APPROVED\',
                         reviewed_by_user_id = :reviewed_by_user_id,
                         reviewed_at = NOW(),
                         review_remarks = :review_remarks,
                         linked_document_type_id = :linked_document_type_id,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $approve->execute([
                    'requested_name' => $finalName,
                    'requested_category' => $finalCategory,
                    'requested_days' => $finalDays,
                    'requested_color' => $finalColor,
                    'reviewed_by_user_id' => (int)$actor['id'],
                    'review_remarks' => $reviewRemarks === '' ? 'Approved by RECORDS-UNIT.' : $reviewRemarks,
                    'linked_document_type_id' => $linkedDocumentTypeId > 0 ? $linkedDocumentTypeId : null,
                    'id' => $requestId,
                ]);

                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }

            echo json_encode(['ok' => true, 'message' => 'Request approved and document type added/updated.']);
            exit;

        case 'REQUEST_REJECT':
            if ($requestId <= 0) {
                throw new InvalidArgumentException('Request ID is required.');
            }
            if (!$isReviewer) {
                throw new RuntimeException('Only RECORDS-UNIT can reject requests.');
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT * FROM document_type_requests WHERE id = :id LIMIT 1 FOR UPDATE');
                $stmt->execute(['id' => $requestId]);
                $request = $stmt->fetch();
                if (!$request) {
                    throw new RuntimeException('Request not found.');
                }

                $currentStatus = strtoupper((string)($request['status'] ?? ''));
                if ($currentStatus !== 'PENDING') {
                    throw new RuntimeException('Only pending requests can be rejected.');
                }

                $reject = $pdo->prepare(
                    'UPDATE document_type_requests
                     SET status = \'REJECTED\',
                         reviewed_by_user_id = :reviewed_by_user_id,
                         reviewed_at = NOW(),
                         review_remarks = :review_remarks,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $reject->execute([
                    'reviewed_by_user_id' => (int)$actor['id'],
                    'review_remarks' => $reviewRemarks === '' ? 'Rejected by RECORDS-UNIT.' : $reviewRemarks,
                    'id' => $requestId,
                ]);

                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }

            echo json_encode(['ok' => true, 'message' => 'Request rejected.']);
            exit;

        default:
            throw new InvalidArgumentException('Unsupported action.');
    }
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (RuntimeException $exception) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to process request right now.']);
}

