<?php
require '../db.php';

try {
    $stmt = $pdo->query("SELECT dc.*, p.full_name AS patient_name, u.full_name AS doctor_name
        FROM drug_chart dc
        JOIN patients p ON dc.patient_id = p.patient_id
        JOIN users u ON dc.prescribed_by = u.user_id
        WHERE dc.seen_by_pharmacist = 0
        ORDER BY dc.start_date DESC");

    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (count($prescriptions) > 0): ?>
        <table class="table table-bordered table-sm align-middle small">
          <thead class="table-light">
            <tr>
              <th>#</th>
                <th>Patient ID</th>
              <th>Patient</th>
              <th>Drug</th>
              <th>Dosage</th>
              <th>Route</th>
              <th>Frequency</th>
              <th>Duration</th>
              <th>Doctor</th>
              <th>Start</th>
              <th>End</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($prescriptions as $index => $row): ?>
              <tr id="row-<?= $row['chart_id'] ?>">
                <td><?= $index + 1 ?></td>
                 <td><?= htmlspecialchars($row['patient_id']) ?></td> 
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= htmlspecialchars($row['drug_name']) ?></td>
                <td><?= htmlspecialchars($row['dosage']) ?></td>
                <td><?= htmlspecialchars($row['route']) ?></td>
                <td><?= htmlspecialchars($row['frequency']) ?></td>
                <td><?= htmlspecialchars($row['duration']) ?></td>
                <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                <td><?= htmlspecialchars($row['start_date']) ?></td>
                <td><?= htmlspecialchars($row['end_date']) ?></td>
                <td class="d-flex gap-1 flex-wrap">
  <button class="btn btn-sm btn-success markSeenBtn" data-id="<?= $row['chart_id'] ?>">✔ Mark as Seen</button>
  
<a href="dispensed_medicines.php?patient_id=<?= $row['patient_id'] ?>&prescribed_by=<?= urlencode($row['doctor_name']) ?>&medicine_name=<?= urlencode($row['drug_name']) ?>" class="btn btn-sm btn-primary">
  💊 Dispense
</a>


</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center">No new prescriptions for now.</div>
    <?php endif;

    echo ob_get_clean();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Error loading prescriptions.</div>";
}
?>
