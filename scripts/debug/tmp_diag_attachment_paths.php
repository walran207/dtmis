<?php
require __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();
$sql = "SELECT d.tracking_id, da.id, da.file_name, da.file_path, da.uploaded_at FROM document_attachments da INNER JOIN documents d ON d.id=da.document_id WHERE d.tracking_id='DENR-XII-2026-0002' ORDER BY da.id DESC";
$stmt = $pdo->query($sql);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
  echo implode("\t", [$r['tracking_id'],$r['id'],$r['file_name'],$r['file_path'],$r['uploaded_at']])."\n";
}
