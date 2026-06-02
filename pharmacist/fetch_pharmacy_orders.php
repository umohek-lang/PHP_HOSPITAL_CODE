<?php
require '../db.php';

$stmt = $pdo->prepare("
    SELECT o.*, p.full_name 
    FROM patient_orders o
    JOIN patients p ON o.patient_id = p.patient_id
    WHERE o.service_type = 'pharmacy' AND o.status = 'pending'
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

foreach ($orders as $order): ?>
    <div class="border-bottom pb-2 mb-2 small">
        <strong><?= htmlspecialchars($order['details']) ?></strong><br>
        <span class="text-muted">Patient: <?= htmlspecialchars($order['full_name']) ?></span><br>
        <span class="text-muted">Date: <?= $order['created_at'] ?></span><br>
        <form method="POST" action="mark_order_complete.php" class="mt-1">
            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
            <button class="btn btn-sm btn-outline-success">Mark as Completed</button>
        </form>
    </div>
<?php endforeach; ?>
