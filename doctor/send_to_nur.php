<?php
require '../includes/auth.php';
require '../db.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$patient_id = $_POST['patient_id'] ?? null;
$procedures = $_POST['procedure_order'] ?? [];
$nursing_notes = trim($_POST['nursing_notes'] ?? '');

if (!$patient_id || empty($procedures)) {
    echo json_encode(['status' => 'error', 'message' => 'No patient or procedures selected']);
    exit;
}

if (!is_array($procedures)) $procedures = [$procedures];

try {
    $pdo->beginTransaction();

    // Prevent double submission (same patient + same procedure within 1 min)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM nursing_orders WHERE patient_id = ? AND ordered_at >= (NOW() - INTERVAL 1 MINUTE)");
    $stmt->execute([$patient_id]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Duplicate submission detected. Please wait before resubmitting.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO nursing_orders 
        (patient_id, procedure_name, notes, ordered_at, requested_by, status, is_sent_to_cashier) 
        VALUES (?, ?, ?, NOW(), 'Doctor', 'Pending', 1)");

    foreach ($procedures as $procedure) {
        if (trim($procedure) === '') continue;
        $stmt->execute([$patient_id, $procedure, $nursing_notes]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Nursing order sent successfully. Cashier notified.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: '.$e->getMessage()]);
}
?>
