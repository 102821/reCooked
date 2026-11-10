<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.html?error=login-required');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

require_once __DIR__ . '/../database/db.php';

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$ingredientLines = preg_split('/\r\n|\r|\n/', (string) ($_POST['ingredients'] ?? ''));
$ingredients = array_values(array_filter(
    array_map('trim', $ingredientLines ?: []),
    static fn (string $ingredient): bool => $ingredient !== ''
));
$category = (string) ($_POST['category'] ?? '');
$difficulty = (string) ($_POST['difficulty'] ?? '');
$prepTime = filter_input(INPUT_POST, 'prep_time', FILTER_VALIDATE_INT);
$imagePath = trim((string) ($_POST['image_path'] ?? ''));
$allowedCategories = ['pasta', 'desserts', 'other'];
$allowedDifficulties = ['Easy', 'Medium', 'Hard'];

if (
    $title === '' || strlen($title) > 255 || $description === '' || $ingredients === [] ||
    !in_array($category, $allowedCategories, true) || !in_array($difficulty, $allowedDifficulties, true) ||
    $prepTime === false || $prepTime === null || $prepTime < 1 || $prepTime > 1440 ||
    strlen($imagePath) > 255 || $imagePath === '' || str_starts_with($imagePath, '/') ||
    str_contains($imagePath, '\\') || str_contains($imagePath, ':') ||
    preg_match('/[\x00-\x1F]/', $imagePath) === 1 ||
    preg_match('#(^|/)\.\.?(/|$)#', $imagePath) === 1
) {
    http_response_code(400);
    exit('Please complete all recipe fields correctly.');
}

foreach ($ingredients as $ingredient) {
    if (strlen($ingredient) > 100) {
        http_response_code(400);
        exit('Each ingredient must be 100 characters or fewer.');
    }
}

$projectRoot = realpath(__DIR__ . '/..');
$resolvedImagePath = $projectRoot === false
    ? false
    : realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imagePath));

$imageInfo = $resolvedImagePath !== false && is_file($resolvedImagePath)
    ? getimagesize($resolvedImagePath)
    : false;

if (
    $projectRoot === false || $resolvedImagePath === false ||
    !str_starts_with($resolvedImagePath, $projectRoot . DIRECTORY_SEPARATOR) ||
    $imageInfo === false ||
    !in_array($imageInfo['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
) {
    http_response_code(400);
    exit('Enter the path to an existing JPG, PNG, or WEBP image inside the project.');
}

try {
    $conn->beginTransaction();

    $categoryNames = [
        'pasta' => 'Pasta',
        'desserts' => 'Desserts',
        'other' => 'Other recipes',
    ];
    $categoryStatement = $conn->prepare(
        'INSERT INTO categories (name, slug) VALUES (:name, :slug)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $categoryStatement->execute([
        'name' => $categoryNames[$category],
        'slug' => $category,
    ]);
    $categoryId = (int) $conn->lastInsertId();

    $difficultyValues = [
        'Easy' => 'makkelijk',
        'Medium' => 'gemiddeld',
        'Hard' => 'moeilijk',
    ];
    $recipeStatement = $conn->prepare(
        'INSERT INTO recipes (user_id, category_id, title, description, prep_time_minutes, difficulty, image_path)
         VALUES (:user_id, :category_id, :title, :description, :prep_time, :difficulty, :image_path)'
    );
    $recipeStatement->execute([
        'user_id' => $_SESSION['user_id'],
        'category_id' => $categoryId,
        'title' => $title,
        'description' => $description,
        'prep_time' => $prepTime,
        'difficulty' => $difficultyValues[$difficulty],
        'image_path' => $imagePath,
    ]);
    $recipeId = (int) $conn->lastInsertId();

    $ingredientStatement = $conn->prepare(
        'INSERT INTO ingredients (name) VALUES (:name)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $recipeIngredientStatement = $conn->prepare(
        'INSERT INTO recipe_ingredients (recipe_id, ingredient_id) VALUES (:recipe_id, :ingredient_id)'
    );

    foreach ($ingredients as $ingredient) {
        $ingredientStatement->execute(['name' => $ingredient]);
        $ingredientId = (int) $conn->lastInsertId();
        $recipeIngredientStatement->execute([
            'recipe_id' => $recipeId,
            'ingredient_id' => $ingredientId,
        ]);
    }

    $conn->commit();
} catch (PDOException $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Recipe save failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The recipe could not be saved.');
}

$overviewPages = [
    'pasta' => 'overzichtspagina/overzichtspagina1.html',
    'desserts' => 'overzichtspagina/overzichtspagina2.html',
    'other' => 'overzichtspagina/overzichtspagina3.html',
];

header('Location: ../' . $overviewPages[$category] . '?created=1');
exit;