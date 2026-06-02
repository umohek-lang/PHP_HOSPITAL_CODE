<?php
require '../db.php';

try {
    $stmt = $pdo->query("SELECT * FROM lab_tests LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Connected successfully. Sample row: ";
    print_r($row);
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
