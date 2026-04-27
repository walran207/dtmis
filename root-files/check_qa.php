<?php
require __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
$sql = "SELECT d.tracking_id, d.status, o.name as current_office, o.level as current_level, p_off.name as pending_office, cu.email as holder_email
        FROM documents d
        LEFT JOIN offices o ON o.id = d.current_office_id
        LEFT JOIN offices p_off ON p_off.id = d.pending_office_id
        LEFT JOIN users cu ON cu.id = d.current_holder_user_id
        WHERE d.tracking_id = 'DENR-XII-2026-0001'
        LIMIT 1";
$stmt = $pdo->query($sql);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
