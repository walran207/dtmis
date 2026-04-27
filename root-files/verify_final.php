<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
$sql = "SELECT tracking_id, subject, originating_office_id FROM documents WHERE (subject LIKE '%BANGA%' OR subject LIKE '%GLAN%') ORDER BY tracking_id DESC";
$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
