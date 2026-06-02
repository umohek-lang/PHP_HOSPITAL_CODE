<?php
require '../includes/auth.php';
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $stmt = $pdo->prepare("UPDATE patient_orders SET status = 'completed' WHERE order_id = ?");
    $stmt->execute([$order_id]);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
