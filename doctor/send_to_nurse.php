<?php
require '../db.php';
header('Content-Type: application/json');

// Enable error reporting (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$patient_id = $_POST['patient_id'] ?? null;
$procedures = $_POST['procedure_order'] ?? [];
$nursing_notes = $_POST['nursing_notes'] ?? '';

if (!$patient_id || empty($procedures)) {
    echo json_encode(['status' => 'error', 'message' => 'No patient or procedures selected']);
    exit;
}

// Ensure $procedures is an array
if (!is_array($procedures)) {
    $procedures = [$procedures];
}

try {
    // ✅ FIXED: use ordered_at instead of created_at
    $stmt = $pdo->prepare("
        INSERT INTO nursing_orders 
        (patient_id, procedure_name, notes, ordered_at, requested_by, status) 
        VALUES (?, ?, ?, NOW(), 'Doctor', 'Pending')
    ");

    foreach ($procedures as $procedure) {
        if (trim($procedure) === '') continue;
        $stmt->execute([$patient_id, $procedure, $nursing_notes]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Nursing order sent successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: '.$e->getMessage()]);
}
?>
