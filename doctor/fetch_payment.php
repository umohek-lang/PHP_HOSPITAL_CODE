<?php
session_start();
require '../db.php';

// Optional: check if user is doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.billing_id, s.full_name, sv.service_name, b.paid_at
        FROM billings b
        JOIN services sv ON b.service_id = sv.service_id
        JOIN patients s ON b.patient_id = s.patient_id
        WHERE b.seen = 0
        ORDER BY b.paid_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'alerts' => $alerts]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
