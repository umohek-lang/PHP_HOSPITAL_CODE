<?php 
session_start();
require '../db.php';

header('Content-Type: application/json');

// Only allow access if logged-in user is a doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               p.patient_pin, 
               p.full_name AS patient_name, 
               p.phone AS phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.seen = 0
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'appointments' => $appointments]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
