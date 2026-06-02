<?php
require '../includes/auth.php';
require '../db.php';

// Ensure only pharmacist can access
checkRole(5); // Role 5 = Pharmacist

$stmt = $pdo->prepare("
    SELECT po.*, p.full_name 
    FROM pharmacy_orders po
    JOIN patients p ON po.patient_id = p.patient_id
    WHERE po.status = 'pending'
    ORDER BY po.ordered_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

if ($orders):
    foreach ($orders as $order): ?>
        <div class="border-bottom pb-2 mb-2 small">
            <strong><?= htmlspecialchars($order['medicine_name'] ?? '') ?> (<?= htmlspecialchars($order['dosage']?? '') ?>)</strong><br>
            <span class="text-muted">Patient: <?= htmlspecialchars($order['full_name']?? '') ?></span><br>
            <span class="text-muted">Ordered At: <?= htmlspecialchars($order['ordered_at']?? '') ?></span><br>
            
            <div class="mt-1 d-flex gap-2">
                <form method="POST" action="mark_pharmacy_order_as_complete.php">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-success">Mark as Completed</button>
                </form>
                <a href="dispensed_medicines.php" class="btn btn-sm btn-outline-primary">Dispensed</a>
            </div>
        </div>
    <?php endforeach;
else: ?>
    <div class="text-muted text-center">No pending pharmacy orders.</div>
<?php endif; ?>
