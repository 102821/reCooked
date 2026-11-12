<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

try {
    $ingredients = $conn->query('SELECT name FROM ingredients ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($ingredients);
} catch (PDOException $exception) {
    error_log('Ingredient list could not be loaded: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'The ingredient list could not be loaded.']);
}