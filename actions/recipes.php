<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

header('Content-Type: application/json; charset=utf-8');

$category = (string) ($_GET['category'] ?? '');
$allowedCategories = ['pasta', 'desserts', 'other'];

if (!in_array($category, $allowedCategories, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid recipe category.']);
    exit;
}

$statement = $conn->prepare(
    'SELECT recipes.id, recipes.title, recipes.description, recipes.difficulty, recipes.prep_time,
            recipes.image_path, users.username
     FROM recipes
     INNER JOIN users ON users.id = recipes.user_id
     WHERE recipes.category = :category
     ORDER BY recipes.created_at DESC'
);
$statement->execute(['category' => $category]);

echo json_encode($statement->fetchAll());