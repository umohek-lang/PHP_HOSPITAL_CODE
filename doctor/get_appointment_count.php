<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

// Only allow access if logged-in user is a doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM appointments WHERE seen = 0");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'count' => $result['count']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => $e->getMessage()]);
}
