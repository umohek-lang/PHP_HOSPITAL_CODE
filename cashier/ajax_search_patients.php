<?php
require '../db.php';

$search = $_GET['term'] ?? '';

$stmt = $pdo->prepare("
    SELECT patient_id, full_name
    FROM patients
    WHERE full_name LIKE ?
    ORDER BY full_name
    LIMIT 20
");
$stmt->execute(["%$search%"]);

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'id'   => $row['patient_id'],
        'text' => $row['full_name'] . ' (' . $row['patient_id'] . ')'
    ];
}

echo json_encode(['results' => $results]);
