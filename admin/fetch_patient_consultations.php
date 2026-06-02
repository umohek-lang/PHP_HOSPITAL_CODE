<?php
include "db.php";
require '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    echo json_encode(["error" => "Patient ID missing"]);
    exit;
}

$patient_id = $_GET['patient_id'];

// Fetch consultations with correct column names
$stmt = $pdo->prepare("
    SELECT consultation_id, blood_pressure, temperature, pulse_rate, respiration_rate,
           oxygen_saturation, pain_level, height_cm, weight_kg, bmi, blood_sugar,
           consciousness_level, vitals_time, symptoms_notes,
           chief_complaint, physical_exam, diagnosis, investigations,
           treatment_plan, doctor_signature, consultation_date
    FROM consultations
    WHERE patient_id = ?
    ORDER BY consultation_date DESC, consultation_id DESC
");
$stmt->execute([$patient_id]);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return as JSON
echo json_encode([
    "consultations" => $consultations
]);
?>

