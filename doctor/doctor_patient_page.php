<?php
session_start();
require '../includes/auth.php';
require '../db.php';

checkRole(2); // doctor only

$patient_id = $_GET['patient_id'] ?? null;

// Fetch all medicines for dropdown
$medicines = $pdo->query("SELECT medicine_id, medicine_name FROM medicines ORDER BY medicine_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Treatment & Dispense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body class="p-4">
<div class="container">

    <h4>Patient Management</h4>

    <div class="row">
        <!-- Treatment Section -->
        <div class="col-md-6">
            <h5>Prescribe Treatment</h5>
            <form id="treatmentForm">
                <input type="hidden" name="action" value="prescribe_treatment">
                <div class="mb-3">
                    <label for="treatment_name" class="form-label">Treatment Name</label>
                    <input type="text" class="form-control" id="treatment_name" name="treatment_name" required>
                </div>

                <div class="mb-3">
                    <label for="treatment_medicine_id" class="form-label">Optional Medicine</label>
                    <select class="form-select" id="treatment_medicine_id" name="treatment_medicine_id">
                        <option value="">-- Select Medicine --</option>
                        <?php foreach ($medicines as $m): ?>
                            <option value="<?= $m['medicine_id'] ?>"><?= htmlspecialchars($m['medicine_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="treatment_notes" class="form-label">Notes / Instructions</label>
                    <textarea class="form-control" id="treatment_notes" name="treatment_notes" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="treatment_date" class="form-label">Treatment Date</label>
                    <input type="date" class="form-control" id="treatment_date" name="treatment_date" value="<?= date('Y-m-d') ?>">
                </div>

                <button type="submit" class="btn btn-primary">Prescribe Treatment</button>
            </form>
        </div>

        <!-- Dispense Medicine Section -->
        <div class="col-md-6">
            <h5>Dispense Medicine</h5>
            <form id="dispenseForm">
                <input type="hidden" name="action" value="dispense_medicine">
                <div class="mb-3">
                    <label for="dispense_medicine_id" class="form-label">Select Medicine</label>
                    <select class="form-select" id="dispense_medicine_id" name="dispense_medicine_id" required>
                        <option value="">-- Select Medicine --</option>
                        <?php foreach ($medicines as $m): ?>
                            <option value="<?= $m['medicine_id'] ?>"><?= htmlspecialchars($m['medicine_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1">
                </div>

                <div class="mb-3">
                    <label for="dispense_notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="dispense_notes" name="dispense_notes" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-success">Dispense Medicine</button>
            </form>
        </div>
    </div>

    <hr>

    <!-- Previous Records -->
    <div class="row">
        <div class="col-md-6">
            <h5>Previous Treatments</h5>
            <div id="treatmentsTable"></div>
        </div>

        <div class="col-md-6">
            <h5>Previous Dispensed Medicines</h5>
            <div id="medicinesTable"></div>
        </div>
    </div>

</div>

<script>
$(document).ready(function(){

    const patient_id = <?= json_encode($patient_id) ?>;

    // Function to load tables
    function loadTables(){
        $('#treatmentsTable').load('ajax_patient_tables.php', {patient_id: patient_id, type: 'treatments'});
        $('#medicinesTable').load('ajax_patient_tables.php', {patient_id: patient_id, type: 'medicines'});
    }

    loadTables(); // initial load

    // Submit treatment form via AJAX
    $('#treatmentForm').submit(function(e){
        e.preventDefault();
        $.post('ajax_save.php', $(this).serialize() + '&patient_id=' + patient_id, function(data){
            alert(data.message);
            $('#treatmentForm')[0].reset();
            loadTables();
        }, 'json');
    });

    // Submit dispense form via AJAX
    $('#dispenseForm').submit(function(e){
        e.preventDefault();
        $.post('ajax_save.php', $(this).serialize() + '&patient_id=' + patient_id, function(data){
            alert(data.message);
            $('#dispenseForm')[0].reset();
            loadTables();
        }, 'json');
    });

});
</script>
</body>
</html>
