<?php
require '../db.php';

$patient_id = $_GET['patient_id'] ?? null;
$type = $_GET['type'] ?? null;

if ($patient_id && in_array($type, ['lab', 'nursing', 'pharmacy'])) {
    $table = $type . '_orders';

    $stmt = $pdo->prepare("SELECT id, is_paid FROM $table WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $results]);
    exit;
}

echo json_encode(['status' => 'error']);
