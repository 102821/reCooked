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
$category = (string) ($_POST['category'] ?? '');
$difficulty = (string) ($_POST['difficulty'] ?? '');
$prepTime = filter_input(INPUT_POST, 'prep_time', FILTER_VALIDATE_INT);
$allowedCategories = ['pasta', 'desserts', 'other'];
$allowedDifficulties = ['Easy', 'Medium', 'Hard'];

if (
    $title === '' || strlen($title) > 100 || $description === '' || $ingredients === '' ||
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
    $statement = $conn->prepare(
        'INSERT INTO recipes (user_id, title, description, ingredients, category, difficulty, prep_time, image_path)
         VALUES (:user_id, :title, :description, :ingredients, :category, :difficulty, :prep_time, :image_path)'
    );
    $statement->execute([
        'user_id' => $_SESSION['user_id'],
        'title' => $title,
        'description' => $description,
        'ingredients' => $ingredients,
        'category' => $category,
        'difficulty' => $difficulty,
        'prep_time' => $prepTime,
        'image_path' => 'uploads/recipes/' . $fileName,
    ]);
} catch (PDOException $exception) {
    unlink($destination);
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