<?php
require '../db.php';

$term = $_GET['term'] ?? '';

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE full_name LIKE ? OR patient_id LIKE ? LIMIT 20");
$stmt->execute(["%$term%", "%$term%"]);
$patients = $stmt->fetchAll();

$results = [];
foreach ($patients as $p) {
    $results[] = [
        'id' => $p['patient_id'],
        'text' => "{$p['patient_id']} - {$p['full_name']}"
    ];
}

echo json_encode(['results' => $results]);
