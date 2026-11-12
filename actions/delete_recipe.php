<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$recipeId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($recipeId === false || $recipeId === null || $recipeId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid recipe ID is required.']);
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/recipe_permissions.php';

try {
    $conn->beginTransaction();
    $recipeStatement = $conn->prepare(
        'SELECT user_id, image_path FROM recipes WHERE id = :id FOR UPDATE'
    );
    $recipeStatement->execute(['id' => $recipeId]);
    $recipe = $recipeStatement->fetch();
    if ($recipe === false) {
        $conn->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Recipe not found.']);
        exit;
    }

    $ownerId = $recipe['user_id'] === null ? null : (int) $recipe['user_id'];
    if (!can_manage_recipe($conn, (int) $_SESSION['user_id'], $ownerId)) {
        $conn->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'You are not allowed to delete this recipe.']);
        exit;
    }

    $deleteStatement = $conn->prepare('DELETE FROM recipes WHERE id = :id');
    $deleteStatement->execute(['id' => $recipeId]);
    $conn->commit();
} catch (PDOException $exception) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Recipe deletion failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'The recipe could not be deleted.']);
    exit;
}

$imagePath = (string) ($recipe['image_path'] ?? '');
if (str_starts_with($imagePath, 'uploads/recipes/')) {
    $imageFile = __DIR__ . '/../' . $imagePath;
    $uploadDirectory = realpath(__DIR__ . '/../uploads/recipes');
    $resolvedImage = realpath($imageFile);
    if (
        $uploadDirectory !== false && $resolvedImage !== false
        && str_starts_with($resolvedImage, $uploadDirectory . DIRECTORY_SEPARATOR)
        && !unlink($resolvedImage)
    ) {
        error_log('Could not remove deleted recipe image: ' . $resolvedImage);
    }
}

echo json_encode(['deleted' => true]);
