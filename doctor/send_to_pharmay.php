<?php
require '../includes/auth.php';
require '../db.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_id = $_POST['patient_id'] ?? null;
    $pharmacy_order = trim($_POST['pharmacy_order'] ?? '');
    $pharmacy_dosage = trim($_POST['pharmacy_dosage'] ?? '');

    if (!$patient_id || empty($pharmacy_order)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing patient ID or pharmacy order.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Prevent double submission (same patient + same medicine within 1 min)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pharmacy_orders WHERE patient_id = ? AND medicine_name = ? AND ordered_at >= (NOW() - INTERVAL 1 MINUTE)");
        $stmt->execute([$patient_id, $pharmacy_order]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Duplicate submission detected. Please wait before resubmitting.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO pharmacy_orders 
            (patient_id, medicine_name, dosage, status, ordered_at, requested_by, is_sent_to_cashier) 
            VALUES (?, ?, ?, 'Pending', NOW(), 'Doctor', 1)");
        $stmt->execute([$patient_id, $pharmacy_order, $pharmacy_dosage]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pharmacy order sent successfully. Cashier notified.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: '.$e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
