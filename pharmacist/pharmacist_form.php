<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE drug_chart SET
        batch_number = :batch_number,
        expiry_date = :expiry_date,
        hmo_covered = :hmo_covered
    WHERE chart_id = :chart_id");

    $stmt->execute([
        ':batch_number' => $_POST['batch_number'],
        ':expiry_date' => $_POST['expiry_date'],
        ':hmo_covered' => $_POST['hmo_covered'],
        ':chart_id' => $_POST['chart_id']
    ]);

    echo "Pharmacy info updated.";
} else {
    echo "Invalid request.";
}
?>


<!-- pharmacy_form.php -->
<form method="POST" action="pharmacy_update.php" class="mb-5 border rounded p-3">
  <h5 class="text-info">Pharmacist Verification Form</h5>

  <input type="hidden" name="chart_id" value="[CHART_ID]">

  <div class="mb-2">
    <label>Batch Number</label>
    <input type="text" name="batch_number" class="form-control">
  </div>
  <div class="mb-2">
    <label>Expiry Date</label>
    <input type="date" name="expiry_date" class="form-control">
  </div>
  <div class="mb-2">
    <label>Confirm HMO Covered</label>
    <select name="hmo_covered" class="form-select">
      <option value="1">Yes</option>
      <option value="0">No</option>
    </select>
  </div>
  <button type="submit" class="btn btn-info">Save Pharmacy Info</button>
</form>
