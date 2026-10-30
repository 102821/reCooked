<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    exit('Username and password are required.');
}

if (strlen($username) > 50 || strlen($password) < 8) {
    http_response_code(400);
    exit('Username must be 50 characters or fewer and password must contain at least 8 characters.');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $statement = $conn->prepare(
        'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)'
    );
    $statement->execute([
        'username' => $username,
        'password_hash' => $passwordHash,
    ]);
} catch (PDOException $exception) {
    if ($exception->errorInfo[1] === 1062) {
        http_response_code(409);
        exit('That username is already in use.');
    }

    throw $exception;
}

header('Location: ../user/login.html');
exit;
