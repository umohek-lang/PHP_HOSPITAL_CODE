<?php
session_start();
require '../db.php';
require_once('../tcpdf/tcpdf.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="container mt-5">

<h2 class="mb-4">Add Service & Bill Patient</h2>
<form id="billingForm" method="post">
    <div class="row">
        <div class="col-md-4">
            <select name="service_name" class="form-select mb-3" required>
                <option value="">-- Select Service --</option>
                <option value="Consultation">Consultation (Doctor)</option>
                <option value="Lab">Lab Test (Lab Technician)</option>
                <option value="Drugs">Drugs (Pharmacist)</option>
                <option value="Nursing">Nursing Care (Nurse)</option>
            </select>
            <input type="number" step="0.01" name="cost" class="form-control mb-3" placeholder="Cost (₦)" required>
        </div>
        <div class="col-md-4">
            <select id="patientSelect" name="patient_id" class="form-select mb-3" required>
                <option value="">-- Select Patient --</option>
                <?php
                $patients = $pdo->query("SELECT * FROM patients");
                while ($row = $patients->fetch()) {
                    echo "<option value='{$row['patient_id']}'>{$row['full_name']}</option>";
                }
                ?>
            </select>
            <input type="number" name="quantity" class="form-control mb-3" placeholder="Quantity" required>
        </div>
        <div class="col-md-4">
            <button class="btn btn-success mt-4 w-100" name="add_and_bill">Add & Bill</button>
        </div>
    </div>
</form>

<div id="invoiceOutput" class="mt-5"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#patientSelect').select2({
        placeholder: 'Search Patient',
        allowClear: true
    });

    $('#billingForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'billing_system.php',
            method: 'POST',
            data: $(this).serialize() + '&ajax=1',
            success: function(response) {
                $('#invoiceOutput').html(response);
            },
            error: function() {
                alert('Something went wrong.');
            }
        });
    });
});
</script>

<?php
if (isset($_POST['ajax']) && $_POST['ajax'] == 1 && isset($_POST['add_and_bill'])) {
    $service_name = $_POST['service_name'];
    $cost = $_POST['cost'];
    $patient_id = $_POST['patient_id'];
    $quantity = $_POST['quantity'];

    $role_map = [
        'Consultation' => 2,
        'Nursing' => 3,
        'Drugs' => 5,
        'Lab' => 6
    ];
    $role_id = $role_map[$service_name] ?? null;

    if ($role_id !== null) {
        $stmt = $pdo->prepare("INSERT INTO bill_services (service_name, cost, role_id) VALUES (?, ?, ?)");
        $stmt->execute([$service_name, $cost, $role_id]);
        $service_id = $pdo->lastInsertId();

        $insertBill = $pdo->prepare("INSERT INTO hos_bills (patient_id, service_id, quantity, printed) VALUES (?, ?, ?, 0)");
        $insertBill->execute([$patient_id, $service_id, $quantity]);

        generateAndReturnInvoice($pdo, $patient_id);
        exit;
    } else {
        echo "<div class='alert alert-danger mt-3'>Invalid Service</div>";
        exit;
    }
}

function generateAndReturnInvoice($pdo, $patient_id) {
    $patientStmt = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id = ?");
    $patientStmt->execute([$patient_id]);
    $patient = $patientStmt->fetch();

    if (!$patient) {
        echo "<div class='alert alert-danger'>Invalid patient ID.</div>";
        return;
    }

    $patient_name = htmlspecialchars($patient['full_name']);
    $currentDate = date('Y-m-d H:i');

    $stmt = $pdo->prepare("SELECT s.service_name, s.cost, b.quantity, (s.cost * b.quantity) AS total
                           FROM hos_bills b
                           JOIN bill_services s ON b.service_id = s.id
                           WHERE b.patient_id = ? AND b.printed = 0");
    $stmt->execute([$patient_id]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        echo "<div class='alert alert-info mt-3'>No new bills to print.</div>";
        return;
    }

    $html = "<h4>Invoice for $patient_name</h4><table class='table table-bordered'><thead><tr><th>Service</th><th>Cost</th><th>Qty</th><th>Total</th></tr></thead><tbody>";
    $grandTotal = 0;

    foreach ($rows as $r) {
        $grandTotal += $r['total'];
        $html .= "<tr>
            <td>{$r['service_name']}</td>
            <td>₦" . number_format($r['cost'], 2) . "</td>
            <td>{$r['quantity']}</td>
            <td>₦" . number_format($r['total'], 2) . "</td>
        </tr>";
    }
    $html .= "<tr><td colspan='3'><strong>Grand Total</strong></td><td><strong>₦" . number_format($grandTotal, 2) . "</strong></td></tr></tbody></table>";

    echo $html;

    $update = $pdo->prepare("UPDATE hos_bills SET printed = 1 WHERE patient_id = ? AND printed = 0");
    $update->execute([$patient_id]);
}
?>
</body>
</html>
