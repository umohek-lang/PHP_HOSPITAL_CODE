<?php
require '../includes/auth.php';
require '../db.php';

// Initialize filter values
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$supplier = $_GET['supplier'] ?? '';
$expired_only = isset($_GET['expired_only']) && $_GET['expired_only'] === '1';

// Build SQL query dynamically
$query = "SELECT * FROM pharmacy_inventory WHERE 1=1";
$params = [];

if (!empty($start_date)) {
    $query .= " AND expiration_date >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $query .= " AND expiration_date <= ?";
    $params[] = $end_date;
}

if (!empty($supplier)) {
    $query .= " AND supplier LIKE ?";
    $params[] = "%$supplier%";
}

if ($expired_only) {
    $query .= " AND expiration_date < CURDATE()";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total value
$total_value = 0;
foreach ($items as $item) {
    $total_value += $item['quantity'] * $item['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacy Inventory Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
        body {
            background-color: lightblue;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="text-center mb-4">
        <h2>Pharmacy Inventory Report</h2>
        <p class="text-muted"><?= date("F j, Y") ?></p>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 align-items-end no-print mb-4">
        <div class="col-md-3">
            <label class="form-label">Start Expiration Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">End Expiration Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Supplier</label>
            <input type="text" name="supplier" value="<?= htmlspecialchars($supplier) ?>" class="form-control" placeholder="Supplier name">
        </div>
        <div class="col-md-2 form-check pt-2">
            <input class="form-check-input" type="checkbox" name="expired_only" value="1" <?= $expired_only ? 'checked' : '' ?>>
            <label class="form-check-label">Only Expired</label>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- Action Buttons -->
    <div class="mb-3 no-print">
        <button onclick="window.print()" class="btn btn-success">Print Report</button>
        <a href="pharmacy_inventory.php" class="btn btn-secondary">Back to Inventory</a>

        <!-- Export to Excel -->
        <form method="POST" action="export_excel.php" class="d-inline">
            <input type="hidden" name="query" value="<?= base64_encode(serialize($items)) ?>">
            <button type="submit" class="btn btn-outline-success">Export to Excel</button>
        </form>

        <!-- Export to PDF -->
        <form method="POST" action="export_pdf.php" class="d-inline ms-2">
            <input type="hidden" name="query" value="<?= base64_encode(serialize($items)) ?>">
            <button type="submit" class="btn btn-outline-danger">Export to PDF</button>
        </form>
    </div>

    <!-- Report Table -->
    <?php if (count($items) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Expiration Date</th>
                        <th>Price (₦)</th>
                        <th>Supplier</th>
                        <th>Batch Number</th>
                        <th>Total Value (₦)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $index = 1; ?>
                    <?php foreach ($items as $item): ?>
                        <tr class="<?= (strtotime($item['expiration_date']) < time()) ? 'table-danger' : '' ?>">
                            <td><?= $index++ ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= $item['expiration_date'] ?></td>
                            <td>₦<?= number_format($item['price'], 2) ?></td>
                            <td><?= htmlspecialchars($item['supplier']) ?></td>
                            <td><?= htmlspecialchars($item['batch_number']) ?></td>
                            <td>₦<?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="7" class="text-end">Total Inventory Value:</th>
                        <th>₦<?= number_format($total_value, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center text-muted">No matching records found.</p>
    <?php endif; ?>
</div>

</body>
</html>
