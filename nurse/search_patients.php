<?php
require '../db.php';

$term = $_GET['term'] ?? '';

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE full_name LIKE :term OR patient_id LIKE :term LIMIT 20");
$stmt->execute([':term' => "%$term%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach ($results as $row) {
    $data[] = [
        'id' => $row['patient_id'],
        'text' => "{$row['full_name']} (ID: {$row['patient_id']})"
    ];
}

echo json_encode(['results' => $data]);
