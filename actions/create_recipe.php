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
$ingredients = trim((string) ($_POST['ingredients'] ?? ''));
$ingredientNames = array_values(array_unique(array_filter(
    array_map('trim', preg_split('/\R/u', $ingredients) ?: []),
    static fn (string $ingredient): bool => $ingredient !== ''
)));
$submittedSteps = $_POST['steps'] ?? [];
$steps = is_array($submittedSteps)
    ? array_values(array_filter(
        array_map(static fn ($step): string => is_string($step) ? trim($step) : '', $submittedSteps),
        static fn (string $step): bool => $step !== ''
    ))
    : [];
$category = (string) ($_POST['category'] ?? '');
$difficulty = (string) ($_POST['difficulty'] ?? '');
$prepTime = filter_input(INPUT_POST, 'prep_time', FILTER_VALIDATE_INT);
$allowedCategories = ['pasta', 'desserts', 'other'];
$allowedDifficulties = ['Easy', 'Medium', 'Hard'];

if (
    $title === '' || strlen($title) > 100 || $description === '' || $ingredientNames === [] || $steps === [] ||
    !in_array($category, $allowedCategories, true) || !in_array($difficulty, $allowedDifficulties, true) ||
    $prepTime === false || $prepTime === null || $prepTime < 1 || $prepTime > 1440
) {
    http_response_code(400);
    exit('Please complete all recipe fields correctly.');
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('Please upload a recipe image.');
}

$imageInfo = getimagesize($_FILES['image']['tmp_name']);
$allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$extension = $imageInfo ? ($allowedMimeTypes[$imageInfo['mime']] ?? null) : null;

if ($extension === null || $_FILES['image']['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    exit('Use a JPG, PNG, or WEBP image up to 5 MB.');
}

try {
    $categoryStatement = $conn->prepare('SELECT id FROM categories WHERE slug = :slug');
    $categoryStatement->execute(['slug' => $category]);
    $categoryId = $categoryStatement->fetchColumn();
} catch (PDOException $exception) {
    error_log('Recipe category lookup failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The recipe could not be saved.');
}

if ($categoryId === false) {
    error_log('Recipe category is missing from the categories table: ' . $category);
    http_response_code(500);
    exit('The recipe could not be saved.');
}

$uploadDirectory = __DIR__ . '/../uploads/recipes';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
    http_response_code(500);
    exit('The image could not be saved.');
}

$fileName = bin2hex(random_bytes(16)) . '.' . $extension;
$destination = $uploadDirectory . '/' . $fileName;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
    http_response_code(500);
    exit('The image could not be saved.');
}

try {
    $conn->beginTransaction();
    $statement = $conn->prepare(
        'INSERT INTO recipes
            (user_id, category_id, title, description, ingredients, category, difficulty,
             prep_time_minutes, prep_time, image_path)
         VALUES
            (:user_id, :category_id, :title, :description, :ingredients, :category, :difficulty,
             :prep_time_minutes, :prep_time, :image_path)'
    );
    $statement->execute([
        'user_id' => $_SESSION['user_id'],
        'category_id' => $categoryId,
        'title' => $title,
        'description' => $description,
        'ingredients' => $ingredients,
        'category' => $category,
        'difficulty' => $difficulty,
        'prep_time_minutes' => $prepTime,
        'prep_time' => $prepTime,
        'image_path' => 'uploads/recipes/' . $fileName,
    ]);

    $recipeId = $conn->lastInsertId();
    $findIngredient = $conn->prepare('SELECT id FROM ingredients WHERE name = :name');
    $createIngredient = $conn->prepare('INSERT INTO ingredients (name) VALUES (:name)');
    $linkIngredient = $conn->prepare(
        'INSERT INTO recipe_ingredients (recipe_id, ingredient_id)
         VALUES (:recipe_id, :ingredient_id)'
    );
    $linkedIngredients = [];

    foreach ($ingredientNames as $ingredientName) {
        $findIngredient->execute(['name' => $ingredientName]);
        $ingredientId = $findIngredient->fetchColumn();
        if ($ingredientId === false) {
            $createIngredient->execute(['name' => $ingredientName]);
            $ingredientId = $conn->lastInsertId();
        }

        if (!isset($linkedIngredients[$ingredientId])) {
            $linkIngredient->execute([
                'recipe_id' => $recipeId,
                'ingredient_id' => $ingredientId,
            ]);
            $linkedIngredients[$ingredientId] = true;
        }
    }

    $stepStatement = $conn->prepare(
        'INSERT INTO recipe_steps (recipe_id, step_number, description)
         VALUES (:recipe_id, :step_number, :description)'
    );
    foreach ($steps as $index => $step) {
        $stepStatement->execute([
            'recipe_id' => $recipeId,
            'step_number' => $index + 1,
            'description' => $step,
        ]);
    }
    $conn->commit();
} catch (PDOException $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    if (is_file($destination) && !unlink($destination)) {
        error_log('Could not remove recipe image after a failed save: ' . $destination);
    }

    if (($exception->errorInfo[1] ?? null) === 1062) {
        http_response_code(409);
        exit('A recipe with this name already exists in this category.');
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