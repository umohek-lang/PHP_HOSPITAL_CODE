<?php
require '../auth.php';
require '../db.php';

// Fetch all medicines
$medicines = $pdo->query("SELECT * FROM medicines ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Medicines</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5.3.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            animation: fadeInSlide 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes fadeInSlide {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card shadow p-4">
        <h3 class="text-center mb-4">Medicine List</h3>

        <?php if (count($medicines) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Stock</th>
                            <th>Expiry Date</th>
                            <th>Price (₦)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $med): ?>
                            <tr>
                                <td><?= htmlspecialchars($med['medicine_id']) ?></td>
                                <td><?= htmlspecialchars($med['name']) ?></td>
                                <td><?= htmlspecialchars($med['description']) ?></td>
                                <td><?= htmlspecialchars($med['stock']) ?></td>
                                <td><?= htmlspecialchars($med['expiry_date']) ?></td>
                                <td><?= number_format($med['price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">No medicines available.</div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="medicines.php" class="btn btn-success">
                Add New Medicine
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
