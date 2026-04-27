<?php
session_start();
echo json_encode(['office_id' => $_SESSION['office_id'] ?? null, 'user_id' => $_SESSION['user_id'] ?? null, 'role' => $_SESSION['role_name'] ?? null]);
