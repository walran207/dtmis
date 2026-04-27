<?php
require __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();
$row = $pdo->query("SELECT d.id,d.tracking_id,d.status,d.current_office_id,co.name AS current_office,d.pending_office_id,po.name AS pending_office FROM documents d LEFT JOIN offices co ON co.id=d.current_office_id LEFT JOIN offices po ON po.id=d.pending_office_id WHERE d.tracking_id='DENR-XII-2026-0002' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($row) { echo json_encode($row, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n"; }
$logs = $pdo->query("SELECT id,created_at,action_type,action_scope,remarks,destination_office_id FROM activity_logs WHERE document_id=".(int)$row['id']." ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($logs as $l) { echo implode("\t",[$l['id'],$l['created_at'],$l['action_type'],$l['action_scope'],$l['destination_office_id'],$l['remarks']])."\n"; }
