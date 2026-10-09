<?php


$servername = "localhost";

$username = "re1";

$password = "reabcd12aa";

$database = "reCooked_DB";


$options = [

    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES => false,

];

try {

    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password, $options);


    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";

} catch (PDOException $e) {

    echo "Connection failed: " . $e->getMessage();

}