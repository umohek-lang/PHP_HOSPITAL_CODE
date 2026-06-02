<?php
require_once '../includes/auth.php';
require_once '../db.php';
require_once '../tcpdf/tcpdf.php';

if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    die("Unauthorized");
}

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) die("Patient ID not provided.");

// Fetch patient info
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) die("Patient not found.");

// Fetch consultation
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE patient_id = ? ORDER BY consultation_date DESC LIMIT 1");
$stmt->execute([$patient_id]);
$consult = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$consult) die("No consultation record found.");

// Setup TCPDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);
$pdf->SetTitle('Full Consultation');

// Start HTML
$html = '<h2 align="center">Full Consultation Report</h2>';

// Patient Info
$html .= '<h4>Patient Details</h4><table border="1" cellpadding="4">';
foreach ([
    'Full Name' => 'full_name',
    'Gender' => 'gender',
    'Age' => 'age',
    'Phone' => 'phone',
    'Email' => 'email',
    'Address' => 'address',
    'Patient Type' => 'patient_type',
    'Patient PIN' => 'patient_pin',
    'HMO Name' => 'hmo_name'
] as $label => $key) {
    $html .= "<tr><td><strong>$label</strong></td><td>" . htmlspecialchars($patient[$key]) . "</td></tr>";
}
$html .= '</table><br>';

// Consultation Fields
$fields = [
    'temperature' => 'Temperature (°C)',
    'pulse' => 'Pulse Rate',
    'respiratory_rate' => 'Respiratory Rate',
    'blood_pressure' => 'Blood Pressure',
    'oxygen_saturation' => 'Oxygen Saturation',
    'pain_level' => 'Pain Level',
    'height_cm' => 'Height (cm)',
    'weight_kg' => 'Weight (kg)',
    'bmi' => 'BMI',
    'blood_sugar' => 'Blood Sugar',
    'consciousness_level' => 'Consciousness Level',
    'vitals_time' => 'Time Vitals Taken',
    'symptoms_notes' => 'Symptoms / Notes',
    'chief_complaint' => 'Chief Complaint',
    'physical_exam' => 'Physical Examination',
    'diagnosis' => 'Diagnosis',
    'investigations' => 'Investigations',
    'treatment_plan' => 'Treatment Plan / Prescription',
    'doctor_signature' => "Doctor's Name / Signature",
    'consultation_date' => 'Consultation Date'
];

$html .= '<h4>Doctor\'s Assessment</h4><table border="1" cellpadding="4">';
foreach ($fields as $key => $label) {
    if (isset($consult[$key])) {
        $value = nl2br(htmlspecialchars($consult[$key]));
        $html .= "<tr><td><strong>$label</strong></td><td>$value</td></tr>";
    }
}
$html .= '</table><br>';

// Lab Orders
if (!empty($consult['lab_order'])) {
    $labOrders = json_decode($consult['lab_order'], true);
    if (is_array($labOrders)) {
        $html .= '<h4>Lab Investigations</h4><ul>';
        foreach ($labOrders as $item) {
            $html .= "<li>" . htmlspecialchars($item) . "</li>";
        }
        $html .= '</ul>';
    }
}

// Nursing Procedures
if (!empty($consult['procedure_order'])) {
    $procedures = json_decode($consult['procedure_order'], true);
    if (is_array($procedures)) {
        $html .= '<h4>Nursing Procedures</h4><ul>';
        foreach ($procedures as $item) {
            $html .= "<li>" . htmlspecialchars($item) . "</li>";
        }
        $html .= '</ul>';
    }
}

// Pharmacy Orders
if (!empty($consult['pharmacy_order'])) {
    $pharmacy = json_decode($consult['pharmacy_order'], true);
    $dosages = json_decode($consult['pharmacy_dosage'], true);

    if (is_array($pharmacy) && is_array($dosages)) {
        $html .= '<h4>Pharmacy Orders</h4><ul>';
        for ($i = 0; $i < count($pharmacy); $i++) {
            $drug = htmlspecialchars($pharmacy[$i] ?? '');
            $dose = htmlspecialchars($dosages[$i] ?? '');
            if ($drug) $html .= "<li>$drug ($dose)</li>";
        }
        $html .= '</ul>';
    }
}

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('full_consultation.pdf', 'I');
?>
