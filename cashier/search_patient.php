<?php
require '../db.php';

$search = $_POST['search'] ?? '';

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE full_name LIKE ? LIMIT 20");
$stmt->execute(['%' . $search . '%']);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($patients);
