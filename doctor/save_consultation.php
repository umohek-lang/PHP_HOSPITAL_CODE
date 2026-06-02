<?php

require '../includes/auth.php';
require '../db.php';
session_start();

if (!isset($_SESSION['user']['user_id'])) {
    die("Error: Doctor not logged in.");
}
$doctor_id = $_SESSION['user']['user_id'];
$patient_id = $_POST['patient_id'] ?? null;
$action_type = $_POST['action_type'] ?? 'save'; // default action

if (!$patient_id) {
    die("Patient ID is missing.");
}

try {
    
    
    
    // ---------------------------
    // 1. Fetch latest nurse vitals (if any)
    // ---------------------------
    $vitalStmt = $pdo->prepare("
        SELECT *
        FROM vital_signs
        WHERE patient_id = ?
        ORDER BY recorded_at DESC
        LIMIT 1
    ");
    $vitalStmt->execute([$patient_id]);
    $nurseVitals = $vitalStmt->fetch(PDO::FETCH_ASSOC);

    // ---------------------------
    // 2. Use nurse vitals if exist, else use doctor's input
    // ---------------------------
    $bp        = $nurseVitals['blood_pressure'] ?? $_POST['blood_pressure'] ?? '';
    $pulse     = $nurseVitals['pulse_rate'] ?? $_POST['pulse'] ?? '';
    $temp      = $nurseVitals['temperature'] ?? $_POST['temperature'] ?? '';
    $resp      = $nurseVitals['respiration_rate'] ?? $_POST['respiratory_rate'] ?? '';
    $oxygen    = $nurseVitals['oxygen_saturation'] ?? $_POST['oxygen_saturation'] ?? '';
    $pain      = $nurseVitals['pain_level'] ?? $_POST['pain_level'] ?? '';
    $height    = $nurseVitals['height_cm'] ?? $_POST['height_cm'] ?? '';
    $weight    = $nurseVitals['weight_kg'] ?? $_POST['weight_kg'] ?? '';
    $bmi       = $nurseVitals['bmi'] ?? $_POST['bmi'] ?? '';
    $sugar     = $nurseVitals['blood_sugar'] ?? $_POST['blood_sugar'] ?? '';
    $consiousness_level     = $nurseVitals['consciousness_level'] ??  $_POST['consciousness_level'] ?? '';
    $notes     = $nurseVitals['symptoms_notes'] ?? $_POST['symptoms_notes'] ?? '';

    // ---------------------------
    // 3. Insert consultation
    // ---------------------------
    $stmt = $pdo->prepare("
        INSERT INTO consultations (
            patient_id, bp, temperature, pulse, respiratory_rate,
            oxygen_saturation, pain_level, height_cm, weight_kg, bmi, blood_sugar,
            consciousness_level, vitals_time, symptoms_notes,
            chief_complaint, physical_exam, diagnosis, investigations,
            treatment_plan, doctor_signature, consultation_date
        ) VALUES (
            :patient_id, :bp, :temperature, :pulse, :respiratory_rate,
            :oxygen_saturation, :pain_level, :height_cm, :weight_kg, :bmi, :blood_sugar,
            :consciousness_level, :vitals_time, :symptoms_notes,
            :chief_complaint, :physical_exam, :diagnosis, :investigations,
            :treatment_plan, :doctor_signature, :consultation_date
        )
    ");

    $stmt->execute([
        ':patient_id'          => $patient_id,
        ':bp'                  => $bp,
        ':temperature'         => $temp,
        ':pulse'               => $pulse,
        ':respiratory_rate'    => $resp,
        ':oxygen_saturation'   => $oxygen,
        ':pain_level'          => $pain,
        ':height_cm'           => $height,
        ':weight_kg'           => $weight,
        ':bmi'                 => $bmi,
        ':blood_sugar'         => $sugar,
        ':consciousness_level' => $_POST['consciousness_level'] ?? '',
        ':vitals_time'         => $_POST['vitals_time'] ?? '',
        ':symptoms_notes'      => $notes,
        ':chief_complaint'     => $_POST['chief_complaint'] ?? '',
        ':physical_exam'       => $_POST['physical_exam'] ?? '',
        ':diagnosis'           => $_POST['diagnosis'] ?? '',
        ':investigations'      => $_POST['investigations'] ?? '',
        ':treatment_plan'      => $_POST['treatment_plan'] ?? '',
        ':doctor_signature'    => $_POST['doctor_signature'] ?? '',
        ':consultation_date'   => $_POST['consultation_date'] ?? date('Y-m-d')
    ]);

    $consultation_id = $pdo->lastInsertId(); // Get inserted consultation ID


// ---------------------------
    // 2. Handle Orders (Lab, Nursing, Pharmacy)
    // ---------------------------
    if (!empty($_POST['lab_order'])) {
        foreach ($_POST['lab_order'] as $lab) {
            $stmt = $pdo->prepare("
                INSERT INTO patient_orders 
                (patient_id, service_type, details, status, created_by)
                VALUES (?, 'lab', ?, 'pending', ?)
            ");
            $stmt->execute([$patient_id, $lab, $doctor_id]);
        }
    }

    if (!empty($_POST['procedure_order'])) {
        foreach ($_POST['procedure_order'] as $procedure) {
            $stmt = $pdo->prepare("
                INSERT INTO patient_orders 
                (patient_id, service_type, details, status, created_by)
                VALUES (?, 'procedure', ?, 'pending', ?)
            ");
            $stmt->execute([$patient_id, $procedure, $doctor_id]);
        }
    }

    if (!empty($_POST['pharmacy_order'])) {
        foreach ($_POST['pharmacy_order'] as $index => $drug) {
            if (!empty($drug)) {
                $dosage = $_POST['pharmacy_dosage'][$index] ?? '';
                $details = $drug . ' - ' . $dosage;
                $stmt = $pdo->prepare("
                    INSERT INTO patient_orders 
                    (patient_id, service_type, details, status, created_by)
                    VALUES (?, 'pharmacy', ?, 'pending', ?)
                ");
                $stmt->execute([$patient_id, $details, $doctor_id]);
            }
        }
    }



    // ---------------------------
    // 6. Redirect based on action_type
    // ---------------------------
    switch ($action_type) {
        case 'send_to_lab':
        case 'send_to_nurse':
        case 'send_to_pharmacy':
            header("Location: consultation.php?patient_id=$patient_id");
            break;
        default:
            header("Location: consultation_list.php?patient_id=$patient_id&msg=saved");
    }
    exit;

} catch (PDOException $e) {
    echo "<pre>";
    if (isset($stmt)) print_r($stmt->debugDumpParams());
    echo "</pre>";
    echo "Database Error: " . $e->getMessage();
}
?>
