<?php
require '../db.php';

$q = $_GET['q'] ?? '';

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients 
                       WHERE full_name LIKE ? OR patient_id LIKE ?
                       ORDER BY full_name ASC LIMIT 20");
$stmt->execute(["%$q%", "%$q%"]);

$results = [];
foreach ($stmt as $row) {
    $results[] = [
        "id" => $row['patient_id'],
        "text" => $row['full_name'] . " (ID: {$row['patient_id']})"
    ];
}

echo json_encode(["results" => $results]);
