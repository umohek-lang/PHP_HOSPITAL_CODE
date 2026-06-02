<?php
require '../db.php';

header('Content-Type: application/json');

$search = $_GET['q'] ?? '';
$stmt = $pdo->prepare("SELECT patient_id, full_name, patient_pin, photo, dob, age, gender, address, phone, marital_status 
                       FROM patients 
                       WHERE full_name LIKE :search OR patient_pin LIKE :search");
$stmt->execute([':search' => "%$search%"]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($rows as $row) {
    $results[] = [
        'id' => $row['patient_id'], // used for patient_id select
        'text' => $row['full_name'], // used as the display text
        'full_name' => $row['full_name'],
        'patient_pin' => $row['patient_pin'],
        'photo' => $row['photo'],
        'dob' => $row['dob'],
        'age' => $row['age'],
        'gender' => $row['gender'],
        'address' => $row['address'],
        'phone' => $row['phone'],
        'marital_status' => $row['marital_status']
    ];
}

echo json_encode(['results' => $results]);
