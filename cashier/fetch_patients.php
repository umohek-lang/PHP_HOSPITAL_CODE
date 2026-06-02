<?php
require '../db.php';

$term = $_GET['term'] ?? '';
$term = "%$term%";

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE full_name LIKE ? OR patient_id LIKE ?");
$stmt->execute([$term, $term]);

$results = [];
while ($row = $stmt->fetch()) {
    $results[] = [
        "id" => $row['patient_id'],
        "text" => "{$row['full_name']} (ID: {$row['patient_id']})"
    ];
}

echo json_encode(['results' => $results]);
