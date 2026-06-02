<?php
require '../db.php'; // PDO connection in $pdo

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name'] ?? '');
    $role_id = trim($_POST['role_id'] ?? '');
    $cost = floatval($_POST['cost'] ?? 0);

    if ($service_name !== '' && $role_id !== '') {
        // Check if the service already exists
        $stmt = $pdo->prepare("SELECT * FROM service_roles WHERE service_name = ?");
        $stmt->execute([$service_name]);
        $existingService = $stmt->fetch();

        if ($existingService) {
            // Update the cost and role_id
            $update = $pdo->prepare("UPDATE service_roles SET cost = ?, role_id = ? WHERE service_name = ?");
            $update->execute([$cost, $role_id, $service_name]);
            $message = "✅ Service updated successfully.";
        } else {
            // Insert new service
            $insert = $pdo->prepare("INSERT INTO service_roles (service_name, role_id, cost) VALUES (?, ?, ?)");
            $insert->execute([$service_name, $role_id, $cost]);
            $message = "✅ New service added.";
        }

        // If role is 2 (Doctor), 3 (Nurse), 5 (Pharmacy), or 6 (Lab Technician), also insert/update into medicines table
        if (in_array($role_id, [2, 3, 5, 6])) {
            // Check if medicine already exists
            $checkMedicine = $pdo->prepare("SELECT * FROM medicines WHERE medicine_name = ?");
            $checkMedicine->execute([$service_name]);
            $existingMedicine = $checkMedicine->fetch();

            if ($existingMedicine) {
                // Update price if medicine already exists
                $updateMed = $pdo->prepare("UPDATE medicines SET price = ? WHERE medicine_name = ?");
                $updateMed->execute([$cost, $service_name]);
            } else {
                // Insert new medicine with default values
                $insertMed = $pdo->prepare("
                    INSERT INTO medicines (medicine_name, description, stock, expiry_date, price) 
                    VALUES (?, '', 0, NULL, ?)
                ");
                $insertMed->execute([$service_name, $cost]);
            }
        }

    } else {
        $message = "❌ Please fill in all fields.";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <!--<h2 class="mb-4">Add or Update Service</h2>-->
 <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add or Update Service</h2>
        <a href="manage_service.php" class="btn btn-dark">
            📋 Manage Services
        </a>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="mb-3">
            <label for="service_name" class="form-label">Service Name</label>
            <input type="text" class="form-control" id="service_name" name="service_name" required>
        </div>

        <div class="mb-3">
            <label for="role_id" class="form-label">
                DEPARTMENT (Note: 2 = Doctor, 3 = Nurse, 5 = Pharmacy, 6 = Lab Technician)
            </label>
            <input type="number" class="form-control" id="role_id" name="role_id" required>
        </div>

        <div class="mb-3">
            <label for="cost" class="form-label">Cost (₦)</label>
            <input type="number" class="form-control" id="cost" name="cost" step="0.01" required>
        </div>

        <button type="submit" class="btn btn-primary">Save Service</button>
    </form>
</div>

</body>
</html>
