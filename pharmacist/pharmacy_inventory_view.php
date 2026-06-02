<?php
require '../includes/auth.php';
require '../db.php';

$sql = "SELECT * FROM pharmacy_inventory";
$stmt = $pdo->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacy Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Pharmacy Inventory</h4>
        </div>
        <div class="card-body">
            <?php if (count($items) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Quantity</th>
                                <th>Expiration Date</th>
                                <th>Price (₦)</th>
                                <th>Supplier</th>
                                <th>Batch Number</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= $item['expiration_date'] ?></td>
                                    <td>₦<?= number_format($item['price'], 2) ?></td>
                                    <td><?= htmlspecialchars($item['supplier']) ?></td>
                                    <td><?= htmlspecialchars($item['batch_number']) ?></td>
                                    <td>
                                        <a href="pharmacy_inventory_update.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Update</a>
                                        <a href="pharmacy_inventory_delete.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No items in inventory.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
