<?php
require '../db.php';

header('Content-Type: application/json');

$stmt = $pdo->prepare("
    SELECT o.*, p.full_name 
    FROM lab_orders o
    JOIN patients p ON o.patient_id = p.patient_id
    WHERE o.status = 'pending'
    ORDER BY o.ordered_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($orders);
