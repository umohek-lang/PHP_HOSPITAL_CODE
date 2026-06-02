<?php
require '../db.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$valid = ['lab', 'procedure', 'pharmacy'];

if (!in_array($type, $valid)) {
    echo json_encode(['new_orders' => 0]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM patient_orders WHERE service_type = ? AND status = 'pending'");
$stmt->execute([$type]);
$count = $stmt->fetchColumn();

echo json_encode(['new_orders' => (int)$count]);
