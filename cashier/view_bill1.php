<?php include '../db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>View Bill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="container mt-5">

<h2>Patient Bill</h2>
<form method="get" class="mb-4">
    <label for="patientSelect" class="form-label">Select Patient</label>
    <select id="patientSelect" name="patient_id" class="form-control" required style="width: 100%;">
        <?php
        if (isset($_GET['patient_id'])) {
            $stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE patient_id = ?");
            $stmt->execute([$_GET['patient_id']]);
            if ($row = $stmt->fetch()) {
                echo "<option value='{$row['patient_id']}' selected>{$row['full_name']} (ID: {$row['patient_id']})</option>";
            }
        }

        ?>
    </select>
</form>

<?php
if (isset($_GET['patient_id'])) {
    $stmt = $pdo->prepare("SELECT p.full_name, s.service_name, s.cost, b.quantity, (s.cost * b.quantity) AS total, b.id AS bill_id, s.id AS service_id
                           FROM hos_bills b
                           JOIN bill_services s ON b.service_id = s.id
                           JOIN patients p ON b.patient_id = p.patient_id
                           WHERE b.patient_id = ? AND b.printed = 0");
    $stmt->execute([$_GET['patient_id']]);
    $rows = $stmt->fetchAll();

    if ($rows) {
        $grandTotal = 0;
        echo "<table class='table table-bordered'>";
        echo "<tr><th>Patient</th><th>Service</th><th>Cost</th><th>Qty</th><th>Total</th></tr>";
        foreach ($rows as $r) {
            $grandTotal += $r['total'];
            echo "<tr>
                    <td>{$r['full_name']}</td>
                    <td>{$r['service_name']}</td>
                    <td>₦{$r['cost']}</td>
                    <td>{$r['quantity']}</td>
                    <td>₦{$r['total']}</td>
                  </tr>";
        }
        echo "<tr><td colspan='4'><strong>Grand Total</strong></td><td><strong>₦$grandTotal</strong></td></tr>";
        echo "</table>";

        $patient_id = $_GET['patient_id'];
        $inserted_services = [];

        foreach ($rows as $row) {
            $service_id = $row['service_id'];

            $check = $pdo->prepare("SELECT * FROM billings WHERE patient_id = ? AND service_id = ?");
            $check->execute([$patient_id, $service_id]);

            if ($check->rowCount() == 0) {
                $insert = $pdo->prepare("INSERT INTO billings (patient_id, service_id, status, paid_at) VALUES (?, ?, 'paid', NOW())");
                $insert->execute([$patient_id, $service_id]);
                $inserted_services[] = $service_id;
            }
        }


$updateLab = $pdo->prepare("UPDATE lab_orders SET is_paid = 1 WHERE patient_id = ? AND is_sent_to_cashier = 1");
$updateLab->execute([$patient_id]);

$updateNursing = $pdo->prepare("UPDATE nursing_orders SET is_paid = 1 WHERE patient_id = ? AND is_sent_to_cashier = 1");
$updateNursing->execute([$patient_id]);

$updatePharmacy = $pdo->prepare("UPDATE pharmacy_orders SET is_paid = 1 WHERE patient_id = ? AND is_sent_to_cashier = 1");
$updatePharmacy->execute([$patient_id]);


        

        echo "<a href='bill_print-invoice.php?patient_id={$_GET['patient_id']}&new_only=1' class='btn btn-primary mt-3' target='_blank'>Print New Bills</a>";
    } else {
        echo "<div class='alert alert-info'>No new bills found for this patient.</div>";
    }
}
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#patientSelect').select2({
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

    $('#patientSelect').on('change', function () {
        this.form.submit();
    });
});
</script>

</body>
</html>
