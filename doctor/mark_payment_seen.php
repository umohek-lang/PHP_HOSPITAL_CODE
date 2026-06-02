<?php
require '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['billing_id'])) {
    $billingId = $_POST['billing_id'];

    // Mark the payment as seen
    $stmt = $pdo->prepare("UPDATE billings SET seen = 1 WHERE billing_id = ?");
    if ($stmt->execute([$billingId])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'fail']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
