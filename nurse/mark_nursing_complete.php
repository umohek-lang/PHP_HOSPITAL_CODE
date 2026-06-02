<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $id = $_POST['order_id'];

    $stmt = $pdo->prepare("UPDATE nursing_orders SET status = 'completed' WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
