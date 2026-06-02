<?php
require '../db.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$patient_id = $_GET['patient_id'] ?? '';

$table = $type . '_orders';

if (!in_array($type, ['lab', 'nursing', 'pharmacy']) || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$stmt = $pdo->prepare("UPDATE $table SET is_seen_by_doctor = 1 WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['status' => 'success', 'id' => $id, 'type' => $type]);
?>