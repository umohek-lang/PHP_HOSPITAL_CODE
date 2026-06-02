<?php
require '../db.php';
header('Content-Type: application/json');

$patient_id = $_POST['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['status' => 'error', 'message' => 'Patient ID missing']);
    exit;
}

try {
    // ✅ Mark all completed lab results for this patient as seen
    $stmt = $pdo->prepare("
        UPDATE lab_tests 
        SET is_seen_by_doctor = 1 
        WHERE patient_id = ? 
          AND (status = 'completed' OR status = 'Completed')
    ");
    $stmt->execute([$patient_id]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
