<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/recipe_permissions.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

$category = (string) ($_GET['category'] ?? '');
$allowedCategories = ['pasta', 'desserts', 'other'];
$difficultyExpression = "CASE recipes.difficulty
    WHEN 'makkelijk' THEN 'Easy'
    WHEN 'gemiddeld' THEN 'Medium'
    WHEN 'moeilijk' THEN 'Hard'
    ELSE recipes.difficulty
END";

if (isset($_GET['id']) || isset($_GET['title'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $title = trim((string) ($_GET['title'] ?? ''));
    $recipeCategory = (string) ($_GET['recipe_category'] ?? '');

    if (($id === false || $id === null || $id < 1) && $title === '') {
        http_response_code(400);
        echo json_encode(['error' => 'A recipe ID or title is required.']);
        exit;
    }

    $query = 'SELECT recipes.id, recipes.user_id, recipes.title, recipes.description,
                     recipes.prep_time_minutes AS prep_time, recipes.image_path,
                     categories.slug AS category, ' . $difficultyExpression . ' AS difficulty
              FROM recipes
              INNER JOIN categories ON categories.id = recipes.category_id
              WHERE ';
    $parameters = [];

    if ($id !== false && $id !== null && $id > 0) {
        $query .= 'recipes.id = :id';
        $parameters['id'] = $id;
    } else {
        $query .= 'recipes.title = :title AND categories.slug = :category';
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

    $recipe['can_manage'] = false;
    if (isset($_SESSION['user_id'])) {
        $ownerId = $recipe['user_id'] === null ? null : (int) $recipe['user_id'];
        $recipe['can_manage'] = can_manage_recipe($conn, (int) $_SESSION['user_id'], $ownerId);
    }

    $ingredientsStatement = $conn->prepare(
        'SELECT ingredients.name, recipe_ingredients.quantity, recipe_ingredients.unit
         FROM recipe_ingredients
         INNER JOIN ingredients ON ingredients.id = recipe_ingredients.ingredient_id
         WHERE recipe_ingredients.recipe_id = :recipe_id
         ORDER BY ingredients.name'
    );
    $ingredientsStatement->execute(['recipe_id' => $recipe['id']]);
    $ingredientLines = [];
    $recipe['ingredient_names'] = [];
    foreach ($ingredientsStatement->fetchAll() as $ingredient) {
        $recipe['ingredient_names'][] = $ingredient['name'];
        $amount = '';
        if ($ingredient['quantity'] !== null) {
            $amount = rtrim(rtrim(number_format((float) $ingredient['quantity'], 2, '.', ''), '0'), '.');
            if ($ingredient['unit'] !== null && $ingredient['unit'] !== '') {
                $amount .= ' ' . $ingredient['unit'];
            }
            $amount .= ' ';
        }
        $ingredientLines[] = $amount . $ingredient['name'];
    }
    $recipe['ingredients'] = implode("\n", $ingredientLines);
    unset($recipe['user_id']);

    $stepsStatement = $conn->prepare(
        'SELECT step_number, description
         FROM recipe_steps
         WHERE recipe_id = :recipe_id
         ORDER BY step_number'
    );
    $stepsStatement->execute(['recipe_id' => $recipe['id']]);
    $recipe['steps'] = $stepsStatement->fetchAll();

    echo json_encode($recipe);
    exit;
}

if (!in_array($category, $allowedCategories, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid recipe category.']);
    exit;
}

$query = 'SELECT recipes.id, recipes.title, ' . $difficultyExpression . ' AS difficulty,
                 recipes.prep_time_minutes AS prep_time, recipes.image_path
          FROM recipes
          INNER JOIN categories ON categories.id = recipes.category_id
          WHERE ';
$parameters = [];

if ($category === 'other') {
    $query .= "categories.slug NOT IN ('pasta', 'desserts')";
} else {
    $query .= 'categories.slug = :category';
    $parameters['category'] = $category;
}

$query .= ' ORDER BY recipes.created_at DESC';
$statement = $conn->prepare($query);
$statement->execute($parameters);

echo json_encode($statement->fetchAll());
