<?php
require_once '../tcpdf/tcpdf.php';
require '../db.php';

if (!isset($_GET['id'])) {
    die('Missing test ID');
}

$testId = $_GET['id'];

// Fetch test with patient name
$stmt = $pdo->prepare("
    SELECT l.*, p.full_name 
    FROM lab_tests l 
    LEFT JOIN patients p ON l.patient_id = p.patient_id 
    WHERE lab_test_id = ?
");
$stmt->execute([$testId]);
$test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test) {
    die('Test not found');
}

// Create PDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);

// Output test info
$html = '
<h2 style="text-align:center;">Lab Test Report</h2>
<table border="1" cellpadding="5">
    <tr><th width="30%">Test ID</th><td>' . htmlspecialchars($test['lab_test_id'] ?? '') . '</td></tr>
    <tr><th>Patient ID</th><td>' . htmlspecialchars($test['patient_id'] ?? '') . '</td></tr>
    <tr><th>Patient Name</th><td>' . htmlspecialchars($test['full_name'] ?? '') . '</td></tr>
    <tr><th>Test Name</th><td>' . htmlspecialchars($test['test_name'] ?? '') . '</td></tr>
    <tr><th>Test Date</th><td>' . htmlspecialchars($test['test_date'] ?? '' ) . '</td></tr>
    <tr><th>Result</th><td>' . nl2br(htmlspecialchars($test['result']?? '')) . '</td></tr>
    <tr><th>Status</th><td>' . htmlspecialchars($test['status'] ?? '') . '</td></tr>
    <tr><th>Requested By</th><td>' . htmlspecialchars($test['requested_by']?? '') . '</td></tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');

// Handle Report File
$reportFile = $test['report_file'];
if (!empty($reportFile) && file_exists($reportFile)) {
    $ext = strtolower(pathinfo($reportFile, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        // Embed image
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML('<h4>Attached Report Image:</h4>', true, false, true, false, '');
        $pdf->Image($reportFile, 15, 40, 180); // X, Y, Width
    } elseif ($ext === 'pdf') {
        // PDF file cannot be embedded directly
        $pdf->AddPage();
        $pdf->writeHTML('
            <h4>Attached Report File:</h4>
            <p>The attached report is a separate PDF file. You can view or download it <a href="' . $reportFile . '">here</a>.</p>
        ', true, false, true, false, '');
    }
}

$pdf->Output('lab_test_' . $testId . '.pdf', 'I');


