<?php include '../db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Bill Patient</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="container mt-5">

<h2>Generate Bill</h2>
<form method="post" id="billingForm">
    <label for="patient_id" class="form-label">Select Patient</label>
    <select name="patient_id" id="patient_id" class="form-control mb-3" required>
        <?php
        if (!empty($_POST['patient_id'])) {
            $selected_patient_id = $_POST['patient_id'];
            $stmt = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id = ?");
            $stmt->execute([$selected_patient_id]);
            $row = $stmt->fetch();
            echo "<option value='{$selected_patient_id}' selected>{$row['full_name']} (ID: {$selected_patient_id})</option>";
        }
        ?>
    </select>

    <?php if (!empty($_POST['patient_id'])): ?>
    <label for="service_id" class="form-label">Select Service</label>
    <select name="service_id" class="form-control mb-2" required>
        <option value="">Select Service</option>
        <?php
        $selected_patient_id = $_POST['patient_id'];
        $billed = $pdo->prepare("SELECT service_id FROM hos_bills WHERE patient_id = ?");
        $billed->execute([$selected_patient_id]);
        $existing_services = $billed->fetchAll(PDO::FETCH_COLUMN);

        if (count($existing_services) > 0) {
            $placeholders = rtrim(str_repeat('?,', count($existing_services)), ',');
            $query = "SELECT * FROM bill_services WHERE id NOT IN ($placeholders)";
            $services = $pdo->prepare($query);
            $services->execute($existing_services);
        } else {
            $services = $pdo->query("SELECT * FROM bill_services");
        }

        while ($row = $services->fetch()) {
            echo "<option value='{$row['id']}'>{$row['service_name']} (₦{$row['cost']})</option>";
        }
        ?>
    </select>

    <input type="number" name="quantity" class="form-control mb-2" placeholder="Quantity" required>
    <button class="btn btn-warning" name="bill">Generate Bill</button>
    <?php endif; ?>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#patient_id').select2({
        placeholder: 'Search by name or ID...',
        ajax: {
            url: 'search_patients.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return data;
            },
            cache: true
        }
    });

    $('#patient_id').on('change', function() {
        $('#billingForm').submit();
    });
});
</script>

<?php
if (isset($_POST['bill'])) {
    $patient_id = $_POST['patient_id'];
    $service_id = $_POST['service_id'];
    $quantity = $_POST['quantity'];

    $serviceStmt = $pdo->prepare("SELECT service_name, cost FROM bill_services WHERE id = ?");
    $serviceStmt->execute([$service_id]);
    $service = $serviceStmt->fetch();

    if (!$service) {
        echo "<div class='alert alert-danger mt-3'>Invalid service selected.</div>";
        exit;
    }

    $service_name = $service['service_name'];
    $service_cost = $service['cost'];
    $total_cost = $service_cost * $quantity;

    $checkStmt = $pdo->prepare("SELECT quantity FROM hos_bills WHERE patient_id = ? AND service_id = ?");
    $checkStmt->execute([$patient_id, $service_id]);

    if ($checkStmt->rowCount() > 0) {
        $existing = $checkStmt->fetch();
        $new_quantity = $existing['quantity'] + $quantity;
        $updateStmt = $pdo->prepare("UPDATE hos_bills SET quantity = ? WHERE patient_id = ? AND service_id = ?");
        $updateStmt->execute([$new_quantity, $patient_id, $service_id]);

        echo "<div class='alert alert-success mt-3'>
            ✔ Existing Service Updated for Patient: <strong>$service_name</strong><br>
            New Quantity: <strong>$new_quantity</strong><br>
            Cost per Unit: ₦<strong>$service_cost</strong><br>
            Total: ₦<strong>" . ($service_cost * $new_quantity) . "</strong><br>
            <a href='view_bill.php?patient_id=$patient_id' target='_blank' class='btn btn-primary mt-3'>🔍 View Bill</a>
        </div>";

    } else {
        $insertStmt = $pdo->prepare("INSERT INTO hos_bills (patient_id, service_id, quantity) VALUES (?, ?, ?)");
        if ($insertStmt->execute([$patient_id, $service_id, $quantity])) {
            echo "<div class='alert alert-success mt-3'>
                ✔ New Service Added for Patient: <strong>$service_name</strong><br>
                Quantity: <strong>$quantity</strong><br>
                Cost per Unit: ₦<strong>$service_cost</strong><br>
                Total: ₦<strong>$total_cost</strong><br>
                <a href='view_bill1.php?patient_id=$patient_id' target='_blank' class='btn btn-primary mt-3'>🔍 View Bill</a>
            </div>";
        } else {
            echo "<div class='alert alert-danger mt-3'>Failed to insert bill. Try again.</div>";
        }
    }
}
?>

</body>
</html>
