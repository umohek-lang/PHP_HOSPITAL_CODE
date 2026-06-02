<?php
require '../includes/auth.php';
require '../db.php';

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) {
    echo json_encode(['error' => 'Missing patient ID']);
    exit;
}

// Fetch treatments
$treatments = $pdo->prepare("
    SELECT t.treatment_id, t.treatment_name, m.medicine_name, t.notes, t.treatment_date 
    FROM treatments t 
    LEFT JOIN medicines m ON t.medicine_id = m.medicine_id 
    WHERE t.patient_id = ? 
    ORDER BY t.treatment_date DESC
");
$treatments->execute([$patient_id]);
$treatments = $treatments->fetchAll(PDO::FETCH_ASSOC);

// Fetch prescriptions
$prescriptions = $pdo->prepare("
    SELECT p.prescription_id, m.medicine_name, p.notes, p.prescription_date 
    FROM prescriptions p 
    LEFT JOIN medicines m ON p.medicine_id = m.medicine_id 
    WHERE p.patient_id = ? 
    ORDER BY p.prescription_date DESC
");
$prescriptions->execute([$patient_id]);
$prescriptions = $prescriptions->fetchAll(PDO::FETCH_ASSOC);

// Return both
echo json_encode([
    'treatments' => $treatments,
    'prescriptions' => $prescriptions
]);
exit;
?>