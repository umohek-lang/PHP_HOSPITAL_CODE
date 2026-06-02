<?php
require '../db.php';

$search = $_GET['q'] ?? '';

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE full_name LIKE ? OR patient_id LIKE ? LIMIT 10");
$searchTerm = "%$search%";
$stmt->execute([$searchTerm, $searchTerm]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];

foreach ($results as $row) {
    $data[] = [
        'id' => $row['patient_id'],
        'text' => $row['patient_id'] . ' - ' . $row['full_name']
    ];
}

echo json_encode(['results' => $data]);
