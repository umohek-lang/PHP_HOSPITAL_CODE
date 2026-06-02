<?php
session_start();
require '../includes/auth.php';
require '../db.php';

checkRole(2); // doctor only

header('Content-Type: application/json');

$patient_id = $_POST['patient_id'] ?? null;
$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid request'];

if (!$patient_id) {
    $response['message'] = 'Patient not specified';
    echo json_encode($response);
    exit;
}

if ($action === 'prescribe_treatment') {
    $treatment_name = trim($_POST['treatment_name']);
    $medicine_id = $_POST['treatment_medicine_id'] ?: null;
    $notes = trim($_POST['treatment_notes']);
    $treatment_date = $_POST['treatment_date'] ?? date('Y-m-d');

    if ($treatment_name) {
        $stmt = $pdo->prepare("INSERT INTO treatments (patient_id, medicine_id, treatment_name, notes, treatment_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$patient_id, $medicine_id, $treatment_name, $notes, $treatment_date]);
        $response = ['status' => 'success', 'message' => 'Treatment prescribed successfully!'];
    } else {
        $response['message'] = 'Treatment name required';
    }

} elseif ($action === 'dispense_medicine') {
    $medicine_id = $_POST['dispense_medicine_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;
    $notes = $_POST['dispense_notes'] ?? '';

    if ($medicine_id) {
        $stmt = $pdo->prepare("INSERT INTO dispensed_medicines (patient_id, medicine_id, quantity, notes, dispensed_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$patient_id, $medicine_id, $quantity, $notes]);
        $response = ['status' => 'success', 'message' => 'Medicine dispensed successfully!'];
    } else {
        $response['message'] = 'Select a medicine';
    }
}

echo json_encode($response);
