<?php
require '../db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("
    SELECT id, patient_id, procedure_name, notes, ordered_at
    FROM nursing_orders
    WHERE status = 'pending'
");

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($orders)) {
    echo "No previous nursing orders found.";
    exit;
}

$insert = $pdo->prepare("
    INSERT INTO treatments (patient_id, medicine_id, treatment_name, notes, treatment_date, created_at)
    VALUES (?, NULL, ?, ?, ?, NOW())
");

$count = 0;
foreach ($orders as $order) {
    $insert->execute([
        $order['patient_id'],
        $order['procedure_name'],
        $order['notes'],
        $order['ordered_at']
    ]);
    $count++;
}

echo "$count old nursing orders migrated successfully.";
?>
