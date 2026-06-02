<?php
require '../db.php';

$searchTerm = $_GET['term'] ?? '';

$stmt = $pdo->prepare("SELECT medicine_name FROM pharmacy_medicines WHERE medicine_name LIKE ? LIMIT 20");
$stmt->execute(["%$searchTerm%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for Select2
$data = array_map(function($row) {
    return [
        'id' => $row['medicine_name'],
        'text' => $row['medicine_name']
    ];
}, $results);

echo json_encode(['results' => $data]);
?>
