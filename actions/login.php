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
	header('Location: ../user/login.html?error=missing');
	exit;
}

try {
	$statement = $conn->prepare(
		'SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1'
	);
	$statement->execute(['username' => $username]);
	$user = $statement->fetch();
} catch (PDOException $exception) {
	http_response_code(500);
	exit('Unable to log in right now.');
}

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
	header('Location: ../user/login.html?error=invalid');
	exit;
}

session_start();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = (string) $user['username'];

header('Location: ../homepage.html');
exit;
