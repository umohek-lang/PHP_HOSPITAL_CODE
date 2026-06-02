<?php include '../db.php'; ?>
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
        <option value="Consultation">Consultation (Doctor)</option>
        <option value="Lab">Lab Test (Lab Technician)</option>
        <option value="Drugs">Drugs (Pharmacist)</option>
        <option value="Nursing">Nursing Care (Nurse)</option>
    </select>

    <input type="number" step="0.01" name="cost" class="form-control mb-3" placeholder="Cost (₦)" required>

    <button class="btn btn-primary" name="add">Add Service</button>
</form>

<?php
if (isset($_POST['add'])) {
    $service_name = $_POST['service_name'];
    $cost = $_POST['cost'];

    // Map services to role_id
    $role_map = [
        'Consultation' => 2, // Doctor
        'Nursing' => 3,      // Nurse
        'Drugs' => 5,        // Pharmacist
        'Lab' => 6           // Lab Technician
    ];

    $role_id = $role_map[$service_name] ?? null;

    if ($role_id !== null) {
        $stmt = $pdo->prepare("INSERT INTO bill_services (service_name, cost, role_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$service_name, $cost, $role_id])) {
            echo "<div class='alert alert-success mt-3'>Service Added Successfully. Redirecting to patient billing...</div>";
            header("refresh:3;url=bill_patient.php");
        } else {
            echo "<div class='alert alert-danger mt-3'>Failed to Add Service</div>";
        }
    } else {
        echo "<div class='alert alert-warning mt-3'>Invalid service selected.</div>";
    }
}
?>

</body>
</html>
