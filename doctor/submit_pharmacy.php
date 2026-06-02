<?php
require '../db.php';
require '../includes/auth.php'; // Optional access restriction

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine = trim($_POST['pharmacy_order'] ?? '');
    $dosages = trim($_POST['pharmacy_dosage'] ?? '');
    $patient_id = $_POST['patient_id'] ?? null;
    $requested_by = $_SESSION['user']['name'] ?? 'Doctor';

    if ($patient_id) {
        $stmt = $pdo->prepare("
            INSERT INTO pharmacy_orders (patient_id, medicine_name, dosage, requested_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$patient_id, $medicine, $dosages, $requested_by]);
        echo 'success';
    } else {
        http_response_code(400);
        echo 'Invalid input';
    }
}
?>
