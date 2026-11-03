<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

header('Content-Type: application/json; charset=utf-8');

$category = (string) ($_GET['category'] ?? '');
$allowedCategories = ['pasta', 'desserts', 'other'];

if (isset($_GET['id']) || isset($_GET['title'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $title = trim((string) ($_GET['title'] ?? ''));
    $recipeCategory = (string) ($_GET['recipe_category'] ?? '');

    if (($id === false || $id === null || $id < 1) && $title === '') {
        http_response_code(400);
        echo json_encode(['error' => 'A recipe ID or title is required.']);
        exit;
    }

    $query = 'SELECT recipes.id, recipes.title, recipes.description, recipes.ingredients,
                     recipes.category, recipes.difficulty, recipes.prep_time, recipes.image_path,
                     users.username
              FROM recipes
              INNER JOIN users ON users.id = recipes.user_id
              WHERE ';
    $parameters = [];

    if ($id !== false && $id !== null && $id > 0) {
        $query .= 'recipes.id = :id';
        $parameters['id'] = $id;
    } else {
        $query .= 'recipes.title = :title AND recipes.category = :category';
        $parameters['title'] = $title;
        $parameters['category'] = $recipeCategory;
    }

    $statement = $conn->prepare($query);
    $statement->execute($parameters);
    $recipe = $statement->fetch();

    if ($recipe === false) {
        http_response_code(404);
        echo json_encode(['error' => 'Recipe not found.']);
        exit;
    }

    echo json_encode($recipe);
    exit;
}

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