<?php
session_start();
require '../db.php';
require '../includes/auth.php';

// Allow Doctors (role_id = 2) and MD (role_id = 9)
checkRole(2);

// Validate report_id
if (!isset($_GET['report_id']) || !is_numeric($_GET['report_id'])) {
    header('Location: view_nurse_reports.php?error=invalid');
    exit;
}

$report_id = (int)$_GET['report_id'];
$doctor_name = $_SESSION['user']['full_name'];

// Update nurse report as reviewed
$stmt = $pdo->prepare("
    UPDATE nurse_reports 
    SET reviewed_by = ?, reviewed_at = NOW() 
    WHERE report_id = ?
");
$updated = $stmt->execute([$doctor_name, $report_id]);

if ($updated) {
    header('Location: view_nurse_reports.php?success=1');
    exit;
} else {
    header('Location: view_nurse_reports.php?error=update_failed');
    exit;
}
?>
