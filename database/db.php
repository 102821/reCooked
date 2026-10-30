<?php

$servername = 'localhost';
$database = 'reCooked_DB';
$username = 're1';
$password = 'fmghhkk20';

$options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
];

try {
	$conn = new PDO(
		"mysql:host=$servername;dbname=$database;charset=utf8mb4",
		$username,
		$password,
		$options
	);
} catch (PDOException $exception) {
	http_response_code(500);
	exit('Connectie met de database is mislukt.');
}
