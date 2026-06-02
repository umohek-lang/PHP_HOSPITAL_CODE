<?php 
require '../includes/auth.php';
require '../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid ID.";
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM pharmacy_inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo "Item not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $quantity = $_POST['quantity'];
    $expiration_date = $_POST['expiration_date'];
    $price = $_POST['price'];
    $supplier = $_POST['supplier'];
    $batch_number = $_POST['batch_number'];

    $updateStmt = $pdo->prepare("UPDATE pharmacy_inventory SET name=?, quantity=?, expiration_date=?, price=?, supplier=?, batch_number=? WHERE id=?");
    $updateStmt->execute([$name, $quantity, $expiration_date, $price, $supplier, $batch_number, $id]);

    header("Location: pharmacy_inventory_view.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-sm p-3 mx-auto" style="max-width: 500px;">
        <div class="card-header bg-warning py-2">
            <h5 class="mb-0 text-center">Update Inventory Item</h5>
        </div>
        <div class="card-body p-2">
            <form method="POST">
                <div class="mb-2">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control form-control-sm" value="<?= $item['quantity'] ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Expiration Date</label>
                    <input type="date" name="expiration_date" class="form-control form-control-sm" value="<?= $item['expiration_date'] ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Price (₦)</label>
                    <input type="number" step="0.01" name="price" class="form-control form-control-sm" value="<?= $item['price'] ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Supplier</label>
                    <input type="text" name="supplier" class="form-control form-control-sm" value="<?= htmlspecialchars($item['supplier']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Batch Number</label>
                    <input type="text" name="batch_number" class="form-control form-control-sm" value="<?= htmlspecialchars($item['batch_number']) ?>" required>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success btn-sm">Update</button>
                    <a href="pharmacy_inventory_view.php" class="btn btn-secondary btn-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
