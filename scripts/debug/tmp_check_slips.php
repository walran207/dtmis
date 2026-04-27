<?php
require __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();
$rows = $pdo->query("SELECT ts.id, ts.document_id, d.tracking_id, ts.from_office_id, fo.name AS from_name, ts.receiving_office_id, ro.name AS recv_name, ts.received_by, ts.date_time_received, ts.action_required, ts.receive_method FROM tracking_slips ts LEFT JOIN documents d ON d.id=ts.document_id LEFT JOIN offices fo ON fo.id=ts.from_office_id LEFT JOIN offices ro ON ro.id=ts.receiving_office_id ORDER BY ts.document_id, ts.date_time_received, ts.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
  echo implode("\t", [
    $r['id'],$r['document_id'],$r['tracking_id'],$r['from_office_id'].':'.($r['from_name']??''),$r['receiving_office_id'].':'.($r['recv_name']??''),$r['received_by'],$r['date_time_received'],$r['receive_method'],$r['action_required']
  ]) . "\n";
}
