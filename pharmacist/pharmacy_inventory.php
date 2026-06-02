<?php
require '../includes/auth.php';
require '../db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $expiration_date = $_POST['expiration_date'];
    $price = $_POST['price'];
    $supplier = $_POST['supplier'];
    $batch_number = $_POST['batch_number'];

    $sql = "INSERT INTO pharmacy_inventory (name, quantity, expiration_date, price, supplier, batch_number)
            VALUES (:name, :quantity, :expiration_date, :price, :supplier, :batch_number)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':quantity' => $quantity,
        ':expiration_date' => $expiration_date,
        ':price' => $price,
        ':supplier' => $supplier,
        ':batch_number' => $batch_number
    ]);
    $message = "Inventory item added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Inventory Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: lightblue; /* Light blue background */
        }
        .animated-card {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease-in-out;
        }

        .animated-card.show {
            opacity: 1;
            transform: translateY(0);
        }

        .form-label {
            transition: color 0.3s ease;
        }

        .form-control:focus + .form-label {
            color: #0d6efd;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg p-4 rounded-4 animated-card w-100" id="formCard" style="max-width: 900px;">
        <h5 class="text-center mb-3">Add Inventory Item</h5>

        <?php if ($message): ?>
            <div class="alert alert-success text-center py-2"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Drug Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Expiration Date</label>
                    <input type="date" name="expiration_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Price (₦)</label>
                    <input type="number" name="price" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Supplier</label>
                    <input type="text" name="supplier" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Batch Number</label>
                    <input type="text" name="batch_number" class="form-control" required>
                </div>
            </div>

            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary btn-sm">Add Item</button>
            </div>
            <div class="mt-2 text-center">
                <a href="pharmacy_inventory_view.php" class="btn btn-outline-primary btn-sm">View All Medicines</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Simple animation on page load
    window.addEventListener('load', () => {
        document.getElementById('formCard').classList.add('show');
    });
</script>

</body>
</html>
