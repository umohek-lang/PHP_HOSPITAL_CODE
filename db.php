<?php
$host = 'localhost';
$db   = 'ablehand';
// $db   = 'slkqfqli_ablehand';
$user = 'root';
// $user = 'slkqfqli_ablehanduser';
$pass = '';
// $pass = 'ablehanduser';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}
?>
