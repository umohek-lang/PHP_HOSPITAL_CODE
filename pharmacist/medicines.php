<?php 
require '../includes/auth.php';
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medicine_name = $_POST['medicine_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $expiry_date = $_POST['expiry_date'] ?? '';
    $price = $_POST['price'] ?? 0.00;

    $stmt = $pdo->prepare("INSERT INTO medicines (medicine_name, description, stock, expiry_date, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$medicine_name, $description, $stock, $expiry_date, $price]);

    $success = "Medicine added successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Medicine</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5.3.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
        }

        .card {
            display: flex;
            flex-direction: row;
            min-height: 300px;
            opacity: 0;
            transform: translateY(50px);
            animation: slideFadeIn 0.6s ease-out forwards;
        }

        @keyframes slideFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-body {
            flex: 1;
            padding: 2rem;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg">
                <div class="card-body">
                    <h4 class="card-title mb-4 text-center">Add New Medicine</h4>

                    <?php if (!empty($success)) : ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <div class="mt-3">
                                <a href="view_medicines.php" class="btn btn-outline-primary btn-sm">View All Medicines</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name:</label>
                                <input type="text" name="medicine_name" required class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Quantity:</label>
                                <input type="number" name="stock" required class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date:</label>
                                <input type="date" name="expiry_date" required class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (₦):</label>
                                <input type="number" name="price" step="0.01" required class="form-control">
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Description:</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Add Medicine</button>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
