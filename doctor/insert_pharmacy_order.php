<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? null;
    $requested_by = $_POST['requested_by'] ?? 'SYSTEM';
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($patient_id && $medicine_name) {
        $stmt = $pdo->prepare("INSERT INTO pharmacy_orders (patient_id, requested_by, medicine_name, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$patient_id, $requested_by, $medicine_name, $notes]);

        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit;
    } else {
        echo "Missing required fields.";
    }
}
?>
