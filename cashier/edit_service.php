<?php
require '../db.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid service.");

$stmt = $pdo->prepare("SELECT * FROM service_roles WHERE id=?");
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$service) die("Service not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name']);
    $role_id = $_POST['role_id'];
    $cost = $_POST['cost'];

    $update = $pdo->prepare("
        UPDATE service_roles 
        SET service_name=?, role_id=?, cost=? 
        WHERE id=?
    ");
    $update->execute([$service_name, $role_id, $cost, $id]);

    /* Sync medicines table */
    if (in_array($role_id, [2,3,5,6])) {
        $pdo->prepare("
            INSERT INTO medicines (medicine_name, price)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE price=VALUES(price)
        ")->execute([$service_name, $cost]);
    }

    header("Location: manage_services.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h3>Edit Service</h3>

<form method="post">
    <div class="mb-3">
        <label class="form-label">Service Name</label>
        <input type="text" name="service_name" class="form-control"
               value="<?= htmlspecialchars($service['service_name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Department</label>
        <input type="number" name="role_id" class="form-control"
               value="<?= $service['role_id'] ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Cost (₦)</label>
        <input type="number" name="cost" step="0.01" class="form-control"
               value="<?= $service['cost'] ?>" required>
    </div>

    <button class="btn btn-success">💾 Update</button>
    <a href="manage_services.php" class="btn btn-secondary">Cancel</a>
</form>

</body>
</html>
