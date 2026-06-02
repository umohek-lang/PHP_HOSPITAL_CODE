<?php
require '../includes/auth.php';
require '../db.php';
require __DIR__ . '/vendor/setasign/fpdf/fpdf.php';

$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    die("Patient ID not provided.");
}

$stmt = $pdo->prepare("SELECT * FROM medical_historys WHERE id = ?");
$stmt->execute([$patient_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Record not found.");
}

$surgical_history = json_decode($row['surgical_history'], true);
$medications = json_decode($row['medications'], true);
$social_history = json_decode($row['social_history'], true);
$immunization = json_decode($row['immunization'], true);
$ros = json_decode($row['ros'], true);
$obstetric = json_decode($row['obstetric'], true);
$physical_exam = json_decode($row['physical_exam'], true);

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Header
$pdf->SetFillColor(0, 102, 204);
$pdf->SetTextColor(255);
$pdf->Cell(0, 12, 'PATIENT MEDICAL HISTORY REPORT', 0, 1, 'C', true);
$pdf->Ln(5);

// Patient Info
$pdf->SetTextColor(0);
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 10, 'Patient Information', 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->MultiCell(0, 8,
    "Full Name: " . $row['full_name'] . "\n" .
    "Gender: " . $row['gender'] . "    Age: " . $row['age'] . "\n" .
    "Phone: " . $row['phone'] . "\n" .
    "Address: " . $row['address'] . "\n" .
    "Visit Date: " . date("F j, Y", strtotime($row['visit_date'])),
    0, 'L', true
);
$pdf->Ln(3);

// Section Helper
function sectionHeader($pdf, $title) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(0, 8, "  " . strtoupper($title), 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Ln(1);
}

// Surgical History
sectionHeader($pdf, 'Surgical History');
if (!empty($surgical_history)) {
    foreach ($surgical_history as $item) {
        $pdf->MultiCell(0, 7, " - {$item['date']} | {$item['name']} | Complications: {$item['complications']}");
    }
} else {
    $pdf->Cell(0, 7, 'No surgical history recorded.', 0, 1);
}
$pdf->Ln(2);

// Medications
sectionHeader($pdf, 'Medications');
if (!empty($medications)) {
    foreach ($medications as $med) {
        $pdf->MultiCell(0, 7, " - {$med['name']} | Dosage: {$med['dosage']} | Frequency: {$med['frequency']} | Duration: {$med['duration']} | Indication: {$med['indication']}");
    }
} else {
    $pdf->Cell(0, 7, 'No medications listed.', 0, 1);
}
$pdf->Ln(2);

// Social History
sectionHeader($pdf, 'Social History');
if (!empty($social_history)) {
    foreach ($social_history as $key => $value) {
        $pdf->Cell(0, 7, ucwords(str_replace("_", " ", $key)) . ": " . $value, 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'No social history recorded.', 0, 1);
}
$pdf->Ln(2);

// Immunization
sectionHeader($pdf, 'Immunization');
if (!empty($immunization)) {
    foreach ($immunization as $key => $value) {
        $pdf->Cell(0, 7, ucwords(str_replace("_", " ", $key)) . ": " . $value, 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'No immunization records.', 0, 1);
}
$pdf->Ln(2);

// Review of Systems (ROS)
sectionHeader($pdf, 'Review of Systems (ROS)');
if (!empty($ros)) {
    foreach ($ros as $key => $value) {
        $pdf->Cell(0, 7, ucwords(str_replace("_", " ", $key)) . ": " . $value, 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'No ROS information recorded.', 0, 1);
}
$pdf->Ln(2);

// Obstetric History
sectionHeader($pdf, 'Obstetric History');
if (!empty($obstetric)) {
    foreach ($obstetric as $key => $value) {
        $pdf->Cell(0, 7, ucwords(str_replace("_", " ", $key)) . ": " . $value, 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'No obstetric history.', 0, 1);
}
$pdf->Ln(2);

// Physical Exam
sectionHeader($pdf, 'Physical Exam');
if (!empty($physical_exam)) {
    foreach ($physical_exam as $key => $value) {
        $pdf->Cell(0, 7, strtoupper($key) . ": " . $value, 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'No physical exam notes.', 0, 1);
}

$pdf->Ln(5);

// Footer
$pdf->SetY(-40); // Slightly lower than before
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 8, 'Generated on ' . date('F j, Y \a\t g:i A'), 0, 1, 'L');
$pdf->Cell(0, 8, 'Confidential Patient Record', 0, 1, 'L');

// Output
$pdf->Output('I', 'Medical_History_' . str_replace(' ', '_', $row['full_name']) . '.pdf');
