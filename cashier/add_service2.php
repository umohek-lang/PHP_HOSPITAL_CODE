<?php 
include '../db.php'; 
ob_start();

$patient_id = $_GET['patient_id'] ?? null;

// Fetch all services from the database
$services = $pdo->query("SELECT service_name FROM service_roles")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h2 class="mb-4">Add Medical Service</h2>

<form method="post">
    <select name="service_name" class="form-select mb-3" required>
        <option value="">-- Select Service --</option>
        <?php foreach ($services as $service): ?>
            <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars($service) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="number" step="0.01" name="cost" class="form-control mb-3" placeholder="Cost (₦)" required>
    <button class="btn btn-primary" name="add">Add Service</button>
</form>

<?php
if (isset($_POST['add'])) {
    $service_name = $_POST['service_name'];
    $cost = $_POST['cost'];

    // Fetch role_id from the database
    $stmt = $pdo->prepare("SELECT role_id FROM service_roles WHERE service_name = ?");
    $stmt->execute([$service_name]);
    $role_id = $stmt->fetchColumn();

    if ($role_id !== false) {
        $stmt = $pdo->prepare("INSERT INTO bill_services (service_name, cost, role_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$service_name, $cost, $role_id])) {
            echo "<div class='alert alert-success mt-3'>Service Added Successfully. Redirecting to patient billing...</div>";
            header("refresh:3;url=bill_patient3.php?patient_id=" . urlencode($patient_id));
        } else {
            echo "<div class='alert alert-danger mt-3'>Failed to Add Service</div>";
        }
    } else {
        echo "<div class='alert alert-warning mt-3'>Role could not be determined for the selected service.</div>";
    }
}
?>

<?php ob_end_flush(); ?>

</body>
</html>
