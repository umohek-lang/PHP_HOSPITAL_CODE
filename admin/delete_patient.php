<?php
require '../includes/auth.php';
require '../db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid request: Patient ID is required.");
}

// Optional: Fetch and delete patient photo
$stmt = $pdo->prepare("SELECT photo FROM patients WHERE patient_id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if ($patient && $patient['photo']) {
    $photoPath = "../uploads/" . $patient['photo'];
    if (file_exists($photoPath)) {
        unlink($photoPath);
    }
}

// Delete the patient
$delStmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
$delStmt->execute([$id]);

header("Location: patients.php");
exit;
