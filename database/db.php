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
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password, $options);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $q = 'SELECT * FROM ingredients;';
    $stmt = $conn->prepare($q);
    $stmt->execute();
    $ings = $stmt->fetchAll();
    echo print_r($ings);
    echo "<br>";

    foreach ($ings as  $ing) {
        echo $ing["name"];
        echo "<br>";
    }
} catch (PDOException $e) {
    echo "Connectie met de db is mislukt." . $e->getMessage();
}