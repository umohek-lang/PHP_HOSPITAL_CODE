<?php
session_start();
require '../db.php';

// Redirect if not cashier
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 4) {
    header("Location: ../login.php");
    exit;
}

// Fetch today's appointments
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.appointment_id, p.full_name, p.patient_pin, a.appointment_time
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.appointment_date = ? 
    ORDER BY a.appointment_time
");
$stmt->execute([$today]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent payments
$payStmt = $pdo->query("
    SELECT r.receipt_id, p.full_name, r.amount_paid, r.payment_date
    FROM receipts r
    JOIN patients p ON r.patient_id = p.patient_id
    ORDER BY r.payment_date DESC LIMIT 5
");
$recentPayments = $payStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cashier Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h3 class="mb-4 text-primary">🏥 Hospital Cashier Dashboard</h3>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5>Today's Appointments</h5>
                    <h2 class="text-success"><?= count($appointments) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5>Recent Payments</h5>
                    <h2 class="text-info"><?= count($recentPayments) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment List -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-secondary text-white">Today's Appointments</div>
        <div class="card-body p-0">
            <table class="table table-striped table-sm mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient Name</th>
                        <th>PIN</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['appointment_time']) ?></td>
                        <td><?= htmlspecialchars($a['full_name']) ?></td>
                        <td><?= htmlspecialchars($a['patient_pin']) ?></td>
                        <td><a href="create_invoice.php?appointment_id=<?= $a['appointment_id'] ?>" class="btn btn-sm btn-primary">Bill</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($appointments)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No appointments today.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">Recent Payments</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Amount Paid</th>
                        <th>Receipt ID</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentPayments as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['payment_date']) ?></td>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td>₦<?= number_format($r['amount_paid'], 2) ?></td>
                        <td>#<?= $r['receipt_id'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentPayments)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No recent payments.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
