<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'], $_SESSION['username'])) {
    echo json_encode([
        'authenticated' => true,
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'] ?? 'user',
    ]);
    exit;
}

echo json_encode(['authenticated' => false]);