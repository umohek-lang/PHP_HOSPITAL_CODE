<?php
require '../db.php';
require '../tcpdf/tcpdf.php'; // TCPDF

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_test_id = $_POST['lab_test_id'];
    $result = $_POST['result'];

    // Fetch test info
    $stmt = $pdo->prepare("
        SELECT l.*, p.full_name, l.test_name, l.test_date 
        FROM lab_tests l 
        JOIN patients p ON l.patient_id = p.patient_id 
        WHERE l.lab_test_id = ?
    ");
    $stmt->execute([$lab_test_id]);
    $test = $stmt->fetch();

    if (!$test) {
        die("❌ Lab test not found.");
    }

    // Handle file upload
    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === 0) {
        $fileTmp = $_FILES['report_file']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['report_file']['name']);
        $targetDir = '../uploads/reports/';
        $targetFile = $targetDir . $fileName;

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($fileTmp, $targetFile)) {
            // Update DB
            $stmt = $pdo->prepare("
                UPDATE lab_tests 
                SET result = ?, report_file = ?, status = 'completed' 
                WHERE lab_test_id = ?
            ");
            $stmt->execute([$result, $fileName, $lab_test_id]);

            // Generate PDF
            $pdf = new TCPDF();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $html = "
                <h2>Lab Test Result</h2>
                <strong>Patient Name:</strong> {$test['full_name']}<br>
                <strong>Test Name:</strong> {$test['test_name']}<br>
                <strong>Date:</strong> {$test['test_date']}<br><br>
                <strong>Result:</strong><br><pre>{$result}</pre>
            ";
            $pdf->writeHTML($html);
            $pdfPath = $targetDir . 'lab_result_' . $lab_test_id . '.pdf';
            $pdf->Output($pdfPath, 'F');

            // Redirect to view_lab_test.php after success so Print/Email becomes visible
header("Location: view_lab_test.php?success=1&msg=Result saved. You can now Print or Email the result.");
exit;

        } else {
            echo "❌ Failed to upload the report file.";
        }
    } else {
        echo "❌ No valid report file uploaded.";
    }
}
?>
