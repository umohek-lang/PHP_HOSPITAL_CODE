<?php
require '../db.php';
header('Content-Type: application/json');

// Case 1: JSON POST (e.g., from fetch sending application/json)
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['appointment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE appointments SET seen = 1 WHERE appointment_id = ?");
        $stmt->execute([$data['appointment_id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Case 2: Normal POST for billing alerts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['billing_id'])) {
    try {
        $billing_id = $_POST['billing_id'];
        $stmt = $pdo->prepare("UPDATE billings SET alert_seen = 1 WHERE billing_id = ?");
        $stmt->execute([$billing_id]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fallback
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
