<?php
require '../db.php';

$term = $_GET['term'] ?? '';

$stmt = $pdo->prepare("SELECT medicine_id, medicine_name, stock FROM medicines WHERE medicine_name LIKE ? AND stock > 0 LIMIT 20");
$stmt->execute(["%$term%"]);
$meds = $stmt->fetchAll();

$results = [];
foreach ($meds as $m) {
    $results[] = [
        'id' => $m['medicine_id'],
        'text' => "{$m['medicine_name']} (Stock: {$m['stock']})"
    ];
}

echo json_encode(['results' => $results]);
