<?php
if (!isset($pageTitle) || trim((string)$pageTitle) === '') {
    $pageTitle = 'Dashboard | DENR Region XII eDATS';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="./dashboard.css">
</head>
<body>
    <div class="dashboard-shell">
