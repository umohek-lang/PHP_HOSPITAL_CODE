<?php
require '../includes/auth.php';
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_test_id = $_POST['lab_test_id'];
    $result = $_POST['result'];
    
    // File upload
    $filePath = null;
    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid() . '_' . basename($_FILES['report_file']['name']);
        $targetPath = '../uploads/lab_reports/' . $fileName;

        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $targetPath)) {
            $filePath = $fileName;
        }
    }

    // Update result in DB
    $stmt = $pdo->prepare("UPDATE lab_tests SET result = ?, report_file = ?, status = 'completed' WHERE lab_test_id = ?");
    $stmt->execute([$result, $filePath, $lab_test_id]);

    header("Location: lab_tests.php?msg=success");
    exit;
}
