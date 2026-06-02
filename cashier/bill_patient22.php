<?php
include '../db.php';

$message = '';

// Handle billing form
if (isset($_POST['bill'])) {
    $patient_id = $_POST['patient_id'];
    $invoice_no = 'INV' . date('YmdHis');

    $services = $_POST['services'] ?? [];
    if (empty($services)) {
        $message = "<div class='alert alert-danger mt-3'>❌ No services selected.</div>";
    } else {
        try {
            $pdo->beginTransaction();
            foreach ($services as $srv) {
                list($service_id, $source) = explode("|", $srv['id']);
                $quantity = max(1, (int)$srv['quantity']);
                $cost = floatval($srv['unit_cost']);
                $total = $quantity * $cost;

                $serviceStmt = $pdo->prepare("SELECT service_name FROM $source WHERE id = ?");
                $serviceStmt->execute([$service_id]);
                $service = $serviceStmt->fetch();

                if (!$service) continue;
                $service_name = $service['service_name'];

                $stmt = $pdo->prepare("INSERT INTO hos_bills 
                    (patient_id, service_id, service_name, quantity, cost, total, source_table, invoice_no, paid)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$patient_id, $service_id, $service_name, $quantity, $cost, $total, $source, $invoice_no, 0]);
            }
            $pdo->commit();
            $message = "<div class='alert alert-success mt-3'>
                ✔ Bill generated successfully!<br>
                <a href='view_bill.php?patient_id=$patient_id&invoice_no=$invoice_no' class='btn btn-primary mt-2'>🔍 View Invoice</a>
            </div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger mt-3'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch patients
$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch services (used for dropdown)
function fetchServices($pdo) {
    return $pdo->query("
        SELECT id, service_name, cost, 'service_roles' AS source FROM service_roles
        UNION
        SELECT id, service_name, cost, 'bill_services' AS source FROM bill_services
    ")->fetchAll(PDO::FETCH_ASSOC);
}
$services = fetchServices($pdo);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Multi-Service Bill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="container mt-5">
<h2>Generate Multi-Service Bill</h2>
<?= $message ?>

<form method="post" id="billingForm">
    <label for="patient_id" class="form-label">Select Patient</label>
    <select name="patient_id" id="patient_id" class="form-control mb-3" required onchange="this.form.submit()">
        <option value="">-- Select Patient --</option>
        <?php foreach ($patients as $p): ?>
            <option value="<?= $p['patient_id'] ?>" <?= (!empty($_POST['patient_id']) && $_POST['patient_id'] == $p['patient_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['full_name']) ?> (ID: <?= $p['patient_id'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

<?php if (!empty($_POST['patient_id'])): ?>
    <label class="form-label">Select Services</label>
    <table class="table table-bordered" id="servicesTable">
        <thead>
            <tr>
                <th>Service</th>
                <th>Quantity</th>
                <th>Unit Cost (₦)</th>
                <th>Total (₦)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr class="service-row">
                <td>
                    <select class="form-select service-select" name="services[0][id]" required>
                        <option value="">Select Service</option>
                        <?php foreach($services as $s): ?>
                            <option value="<?= $s['id'] ?>|<?= $s['source'] ?>" data-cost="<?= $s['cost'] ?>">
                                <?= htmlspecialchars($s['service_name']) ?> - ₦<?= number_format($s['cost'],2) ?> (<?= $s['source'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="services[0][quantity]" class="form-control quantity" value="1" min="1"></td>
                <td><input type="number" name="services[0][unit_cost]" class="form-control unit-cost" step="0.01"></td>
                <td><input type="text" class="form-control total" readonly></td>
                <td>
                    <button type="button" class="btn btn-info btn-sm edit-service">✏ Edit</button>
                    <button type="button" class="btn btn-danger btn-sm remove-row">🗑 Remove</button>
                </td>
            </tr>
        </tbody>
    </table>
    <button type="button" id="addService" class="btn btn-secondary mb-3">➕ Add Another Service</button>
    <div><strong>Grand Total: ₦<span id="grandTotal">0.00</span></strong></div>
    <button class="btn btn-warning" name="bill">Generate Invoice</button>
<?php endif; ?>
</form>

<!-- Edit Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editServiceForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Service</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="editServiceId">
        <input type="hidden" name="source" id="editServiceSource">
        <div class="mb-3">
            <label>Service Name</label>
            <input type="text" name="service_name" id="editServiceName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Cost (₦)</label>
            <input type="number" step="0.01" name="cost" id="editServiceCost" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
    $('#patient_id').select2();

    function updateRowTotal(row){
        let qty = parseFloat(row.find('.quantity').val()) || 0;
        let cost = parseFloat(row.find('.unit-cost').val()) || 0;
        row.find('.total').val((qty * cost).toFixed(2));
        updateGrandTotal();
    }
    function updateGrandTotal(){
        let grand = 0;
        $('#servicesTable .service-row').each(function(){
            grand += parseFloat($(this).find('.total').val()) || 0;
        });
        $('#grandTotal').text(grand.toFixed(2));
    }

    // Update totals when service or qty changes
    $(document).on('change', '.service-select', function(){
        let row = $(this).closest('tr');
        let cost = $(this).find(':selected').data('cost') || 0;
        row.find('.unit-cost').val(cost);
        updateRowTotal(row);
    });
    $(document).on('input', '.quantity, .unit-cost', function(){
        updateRowTotal($(this).closest('tr'));
    });

    // Add another service row
    $('#addService').on('click', function(){
        let index = $('#servicesTable .service-row').length;
        let newRow = $('#servicesTable .service-row:first').clone();

        newRow.find('select').attr('name', 'services['+index+'][id]').val('');
        newRow.find('.quantity').attr('name', 'services['+index+'][quantity]').val(1);
        newRow.find('.unit-cost').attr('name', 'services['+index+'][unit_cost]').val('');
        newRow.find('.total').val('');

        $('#servicesTable tbody').append(newRow);
    });

    // Remove row
    $(document).on('click', '.remove-row', function(){
        if($('#servicesTable .service-row').length > 1){
            $(this).closest('tr').remove();
            updateGrandTotal();
        } else {
            alert("At least one service is required.");
        }
    });

    // Edit service modal
    $(document).on('click', '.edit-service', function(){
        let row = $(this).closest('tr');
        let service = row.find('.service-select option:selected');
        if (!service.val()) return alert("Please select a service first.");
        let [id, source] = service.val().split('|');
        $('#editServiceId').val(id);
        $('#editServiceSource').val(source);
        $('#editServiceName').val(service.text().split(' - ')[0].trim());
        $('#editServiceCost').val(row.find('.unit-cost').val());
        $('#editServiceModal').modal('show');
    });

    // Save edit via AJAX
    $('#editServiceForm').submit(function(e){
        e.preventDefault();
        $.post('update_service.php', $(this).serialize(), function(res){
            if(res.success){
                $.get('fetch_services1.php', function(data){
                    $('.service-select').each(function(){
                        let current = $(this).val();
                        $(this).html(data).val(current).trigger('change');
                    });
                });
                $('#editServiceModal').modal('hide');
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });
});
</script>
</body>
</html>
