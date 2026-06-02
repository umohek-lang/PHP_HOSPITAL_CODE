<?php
require '../includes/auth.php';
require '../db.php';

$stmt = $pdo->prepare("
    SELECT o.*, p.full_name 
    FROM patient_orders o
    JOIN patients p ON o.patient_id = p.patient_id
    WHERE o.service_type = 'procedure' AND o.status = 'pending'
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

foreach ($orders as $order): ?>
    <div class="card mb-2">
        <div class="card-body">
            <strong>Test: <?= htmlspecialchars($order['details']) ?></strong><br>
            <small>Patient: <?= htmlspecialchars($order['full_name']) ?></small><br>
            <small>Date: <?= $order['created_at'] ?></small>

            <form method="POST" action="mark_order_complete.php" class="mt-2">
                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                <button class="btn btn-sm btn-success">Mark as Completed</button>
            </form>
        </div>
    </div>
<?php endforeach;
