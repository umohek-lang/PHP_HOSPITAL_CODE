<?php
require '../includes/auth.php';
require '../db.php';

header('Content-Type: application/json');
file_put_contents("debug_lab.txt", print_r($_POST, true));


// Ensure doctor is logged in
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_id = $_POST['patient_id'] ?? null;
    $chief = trim($_POST['chief_complaint'] ?? '');
    $exam = trim($_POST['physical_exam'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $investigations = trim($_POST['investigations'] ?? '');
    $lab_order = $_POST['lab_order'] ?? []; // array of test names
    $lab_notes = trim($_POST['lab_notes'] ?? '');

    if (!$patient_id) {
        echo json_encode(['status' => 'error', 'message' => 'Patient not found']);
        exit;
    }

    if (!is_array($lab_order)) {
        $lab_order = [$lab_order]; // ensure it's an array
    }

    try {
        $pdo->beginTransaction();

        // 1️⃣ Insert consultation entry
        $stmt = $pdo->prepare("INSERT INTO consultations 
            (patient_id, chief_complaint, physical_exam, diagnosis, investigations, doctor_name, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$patient_id, $chief, $exam, $diagnosis, $investigations, $_SESSION['user']['full_name']]);

        // 2️⃣ Insert lab orders if any
        if (!empty($lab_order)) {
            $stmt = $pdo->prepare("INSERT INTO lab_orders 
                (patient_id, test_name, status, ordered_at, requested_by, is_sent_to_cashier, is_seen_by_doctor, is_paid, lab_notes) 
                VALUES (?, ?, 'pending', NOW(), ?, 0, 0, 0, ?)"
            );

            foreach ($lab_order as $test) {
                if (!empty($test)) {
                    $stmt->execute([$patient_id, $test, $_SESSION['user']['full_name'], $lab_notes]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Consultation and lab orders saved successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to save: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
