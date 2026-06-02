<?php
require '../db.php';
header('Content-Type: application/json');

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['status' => 'error', 'count' => 0]);
    exit;
}

try {
    // ✅ Count unseen results for this patient
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM lab_tests 
        WHERE patient_id = ? 
          AND is_seen_by_doctor = 0
          AND (status = 'completed' OR status = 'Completed')
    ");
    $stmt->execute([$patient_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'count' => (int)$count]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'count' => 0, 'message' => $e->getMessage()]);
}
?>
