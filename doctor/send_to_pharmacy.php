<?php
require '../includes/auth.php';
require '../db.php';
header('Content-Type: application/json');

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? null;
    $pharmacy_order = trim($_POST['pharmacy_order'] ?? '');
    $pharmacy_dosage = trim($_POST['pharmacy_dosage'] ?? '');

    if ($patient_id && !empty($pharmacy_order)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO pharmacy_orders 
                (patient_id, medicine_name, dosage, status, ordered_at, requested_by) 
                VALUES (?, ?, ?, 'Pending', NOW(), 'Doctor')
            ");
            $stmt->execute([$patient_id, $pharmacy_order, $pharmacy_dosage]);

            echo json_encode(['status' => 'success', 'message' => 'Pharmacy order sent successfully!']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: '.$e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing patient ID or pharmacy order.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
