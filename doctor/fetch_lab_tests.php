<?php
require '../db.php';

header('Content-Type: application/json');

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['status' => 'error', 'message' => 'Patient ID not provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT lt.*, u.full_name AS requested_by
        FROM lab_tests lt
        LEFT JOIN users u ON lt.requested_by = u.user_id
        WHERE lt.patient_id = ?
        ORDER BY lt.test_date DESC
    ");
    $stmt->execute([$patient_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
