<?php
require '../db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $stmt = $pdo->prepare("UPDATE patient_orders 
        SET status = 'completed', completed_by = ?, completed_at = NOW() 
        WHERE order_id = ?");
    $stmt->execute([$_SESSION['user']['user_id'], $_POST['order_id']]);
    header("Location: " . $_SERVER['HTTP_REFERER']);
}
