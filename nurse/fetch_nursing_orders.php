<?php
require '../db.php';

// Fetch pending nurse orders with patient info
$stmt = $pdo->prepare("
    SELECT n.*, p.full_name 
    FROM nursing_orders n
    JOIN patients p ON n.patient_id = p.patient_id
    WHERE n.status IN ('pending', 'seen')
    ORDER BY n.ordered_at DESC
");
$stmt->execute();
$nursingOrders = $stmt->fetchAll();
?>

<?php if (empty($nursingOrders)): ?>
    <div class="alert alert-danger">No pending nurse orders.</div>
<?php else: ?>
    <?php foreach ($nursingOrders as $order): ?>
        <div class="order-item border-bottom pb-2 mb-3 small" id="order-<?= $order['id'] ?>">
            <strong><?= htmlspecialchars($order['procedure_name']) ?></strong><br>
            <span class="text-muted">Patient: <?= htmlspecialchars($order['full_name']) ?></span><br>
            <span class="text-muted">Date: <?= htmlspecialchars($order['ordered_at']) ?></span><br>
            <span>Status: 
                <span class="order-status <?= $order['is_paid'] ? 'text-success' : 'text-danger' ?>">
                    <?= $order['is_paid'] ? 'Paid' : 'Not Paid' ?>
                </span>
            </span><br>

            <?php if (!empty($order['notes'])): ?>
                <div class="text-muted"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($order['notes'])) ?></div>
            <?php endif; ?>

            <div class="mt-2">
                <?php if ($order['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-outline-primary mark-seen-btn" data-id="<?= $order['id'] ?>">
                        👁 Mark as Seen
                    </button>
                <?php elseif ($order['status'] === 'seen'): ?>
                    <span class="badge bg-info">Seen</span>
                <?php endif; ?>

                <?php if ($order['status'] !== 'completed'): ?>
                    <button class="btn btn-sm btn-outline-success mark-complete-btn" data-id="<?= $order['id'] ?>">
                        ✅ Mark Completed
                    </button>
                <?php else: ?>
                    <span class="badge bg-success">seen</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
