<?php
require '../includes/auth.php'; // Ensure logged in
require '../db.php';             // Connect to DB

// Only allow doctors (role_id = 2)
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit();
}

// Get patient_id from URL
$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo "Invalid request: patient ID is missing.";
    exit;
}

// Get latest consultation for that patient
$stmt = $pdo->prepare("SELECT consultation_id FROM consultations WHERE patient_id = ? ORDER BY consultation_date DESC LIMIT 1");
$stmt->execute([$patient_id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consultation) {
    echo "No consultation found for this patient.";
    exit;
}

// Delete the consultation
$delete = $pdo->prepare("DELETE FROM consultations WHERE consultation_id = ?");
$success = $delete->execute([$consultation['consultation_id']]);

if ($success) {
    echo "<div class='alert alert-success text-center'>Latest consultation deleted successfully for patient ID $patient_id.</div>";
} else {
    echo "<div class='alert alert-danger text-center'>Failed to delete consultation.</div>";
}
?>
<a href="consultation_list.php?patient_id=<?= $patient_id ?>" class="btn btn-primary mt-3">🔙 Back to Patient Consultations</a>
