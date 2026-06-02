<?php
require '../includes/auth.php';
require '../db.php';
session_start();

$doctor_id = $_SESSION['user']['user_id'];
$patient_id = $_POST['patient_id'] ?? null;
$action_type = $_POST['action_type'] ?? 'save'; // default action

if (!$patient_id) {
    die("Patient ID is missing.");
}

try {
    // ---------------------------
    // 1. Insert Consultation
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
        ':bp'                  => $_POST['blood_pressure'] ?? '',
        ':temperature'         => $_POST['temperature'] ?? '',
        ':pulse'               => $_POST['pulse'] ?? '',
        ':respiratory_rate'    => $_POST['respiratory_rate'] ?? '',
        ':oxygen_saturation'   => $_POST['oxygen_saturation'] ?? '',
        ':pain_level'          => $_POST['pain_level'] ?? '',
        ':height_cm'           => $_POST['height_cm'] ?? '',
        ':weight_kg'           => $_POST['weight_kg'] ?? '',
        ':bmi'                 => $_POST['bmi'] ?? '',
        ':blood_sugar'         => $_POST['blood_sugar'] ?? '',
        ':consciousness_level' => $_POST['consciousness_level'] ?? '',
        ':vitals_time'         => $_POST['vitals_time'] ?? '',
        ':symptoms_notes'      => $_POST['symptoms_notes'] ?? '',
        ':chief_complaint'     => $_POST['chief_complaint'] ?? '',
        ':physical_exam'       => $_POST['physical_exam'] ?? '',
        ':diagnosis'           => $_POST['diagnosis'] ?? '',
        ':investigations'      => $_POST['investigations'] ?? '',
        ':treatment_plan'      => $_POST['treatment_plan'] ?? '',
        ':doctor_signature'    => $_POST['doctor_signature'] ?? '',
        ':consultation_date'   => $_POST['consultation_date'] ?? date('Y-m-d')
    ]);

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
    // 3. Handle X-ray Upload
    // ---------------------------
    if (isset($_FILES['xray_file']) && $_FILES['xray_file']['error'] === UPLOAD_ERR_OK) {
        $xrayTmp = $_FILES['xray_file']['tmp_name'];
        $xrayName = uniqid('xray_') . '_' . basename($_FILES['xray_file']['name']);
        $uploadDir = '../uploads/xrays/';
        $xrayPath = $uploadDir . $xrayName;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        move_uploaded_file($xrayTmp, $xrayPath);

        // Save X-ray path to the **latest consultation**
        $stmt = $pdo->prepare("
            UPDATE consultations 
            SET xray_file = ? 
            WHERE patient_id = ? 
            ORDER BY consultation_id DESC LIMIT 1
        ");
        $stmt->execute([$xrayPath, $patient_id]);
    }

    // ---------------------------
    // 4. Redirect based on action_type
    // ---------------------------
    switch ($action_type) {
        case 'send_to_lab':
            header("Location: consultation.php?patient_id=$patient_id");
            break;
        case 'send_to_nurse':
            header("Location: consultation.php?patient_id=$patient_id");
            break;
        case 'send_to_pharmacy':
            header("Location: consultation.php?patient_id=$patient_id");
            break;
        default: // save consultation only
            header("Location: consultation_list.php?patient_id=$patient_id&msg=saved");
    }
    exit;

} catch (PDOException $e) {
    echo "<pre>";
    print_r($stmt->debugDumpParams());
    echo "</pre>";
    echo "Database Error: " . $e->getMessage();
}
?>
