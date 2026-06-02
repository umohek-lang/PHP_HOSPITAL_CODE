<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE drug_chart SET
        time_administered = :time_administered,
        status = :status,
        administered_by = :administered_by,
        notes = :notes
    WHERE chart_id = :chart_id");

    $stmt->execute([
        ':time_administered' => $_POST['time_administered'],
        ':status' => $_POST['status'],
        ':administered_by' => $_POST['administered_by'],
        ':notes' => $_POST['notes'],
        ':chart_id' => $_POST['chart_id']
    ]);

    echo "✔️ Nurse administration record saved successfully.";
} else {
    echo "❌ Invalid request method.";
}
?>



<!-- nurse_administered_form.php -->
<form method="POST" action="" class="border rounded p-3">
  <h5 class="text-success">Nurse Drug Administration Entry</h5>

  <input type="hidden" name="chart_id" value="[CHART_ID]">

  <div class="mb-2">
    <label>Time Administered</label>
    <input type="time" name="time_administered" class="form-control" required>
  </div>

  <div class="mb-2">
    <label>Status</label>
    <select name="status" class="form-select" required>
      <option value="Ongoing">Ongoing</option>
      <option value="Completed">Completed</option>
      <option value="Discontinued">Discontinued</option>
    </select>
  </div>

  <div class="mb-2">
    <label>Administered By (Nurse ID)</label>
    <input type="number" name="administered_by" class="form-control" required>
  </div>

  <div class="mb-2">
    <label>Administration Notes</label>
    <textarea name="notes" class="form-control" rows="3" placeholder="Details on dose, patient reaction, etc..."></textarea>
  </div>

  <button type="submit" class="btn btn-success">Submit Administration Record</button>
</form>
