<?php
include '../db.php';

/* ===============================
   1. GET PATIENT
================================ */
$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) {
    die("No patient selected.");
}

$patient_stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE patient_id=?");
$patient_stmt->execute([$patient_id]);
$patient = $patient_stmt->fetch();
if (!$patient) {
    die("Invalid patient.");
}

/* ===============================
   2. DATE FILTER
================================ */
$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$bills = [];

if ($from && $to) {
    $stmt = $pdo->prepare("
        SELECT * FROM hos_bills
        WHERE patient_id = ?
          AND paid = 1
          AND DATE(created_at) BETWEEN ? AND ?
        ORDER BY invoice_no DESC, id
    ");
    $stmt->execute([$patient_id, $from, $to]);
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===============================
   3. GROUP BY INVOICE
================================ */
$invoices = [];
foreach ($bills as $b) {
    $invoices[$b['invoice_no']][] = $b;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Paid Bills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h3>
    Paid Bills for <?= htmlspecialchars($patient['full_name']) ?>
    (ID: <?= $patient['patient_id'] ?>)
</h3>

<!-- ===============================
     4. DATE FILTER FORM
================================ -->
<form method="get" class="row g-3 mb-4">
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">

    <div class="col-md-4">
        <label class="form-label">From Date</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">To Date</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>" required>
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary w-100">View Paid Bills</button>
    </div>
</form>

<!-- ===============================
     5. DISPLAY RESULTS
================================ -->
<?php if ($from && $to): ?>

    <?php if (empty($invoices)): ?>
        <div class="alert alert-warning">
            No paid bills found between <?= $from ?> and <?= $to ?>.
        </div>
    <?php else: ?>

        <?php foreach ($invoices as $inv_no => $items): 
            $total = array_sum(array_column($items, 'total'));
        ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <strong>Invoice No:</strong> <?= $inv_no ?>
                    <span class="float-end">PAID ✅</span>
                </div>

                <div class="card-body">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Service</th>
                                <th>Source</th>
                                <th>Qty</th>
                                <th>Unit Cost (₦)</th>
                                <th>Total (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['service_name']) ?></td>
                                    <td><?= ucfirst($b['source_table']) ?></td>
                                    <td><?= $b['quantity'] ?></td>
                                    <td class="text-end"><?= number_format($b['cost'],2) ?></td>
                                    <td class="text-end"><?= number_format($b['total'],2) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr class="table-success">
                                <td colspan="4" class="text-end"><strong>Invoice Total</strong></td>
                                <td class="text-end"><strong><?= number_format($total,2) ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
<?php endif; ?>

</body>
</html>
