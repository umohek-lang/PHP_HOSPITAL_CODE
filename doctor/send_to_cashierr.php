<?php
require '../includes/auth.php';
require '../db.php';

header('Content-Type: application/json');

// ✅ Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// ✅ Get POST data
$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? '';

// ✅ Validate inputs
if (!in_array($type, ['lab', 'nursing', 'pharmacy']) || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid type or ID']);
    exit;
}

$table = $type . '_orders';

// ✅ Update the database
try {
    $stmt = $pdo->prepare("UPDATE `$table` SET is_sent_to_cashier = 1 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'Order sent to cashier']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}





