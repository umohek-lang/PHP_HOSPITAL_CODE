<?php
require '../db.php'; // adjust path as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $type = $_POST['type'] ?? null;

    if ($id && in_array($type, ['lab', 'nursing', 'pharmacy'])) {
        $table = $type . '_orders';
        $stmt = $pdo->prepare("UPDATE $table SET is_seen_by_doctor = 1 WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error']);
