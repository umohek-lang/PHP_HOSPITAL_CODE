<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include dependencies
session_start();
require '../includes/auth.php';
require '../db.php';

// Ensure receptionist is logged in
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 8) {
    exit('Unauthorized');
}

// Fetch consultations with patient info and doctor info (role_id = 2)
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               p.full_name AS patient_name, p.photo, p.gender, p.age, p.patient_pin,
               u.full_name AS doctor_name
        FROM consultations c
        JOIN patients p ON c.patient_id = p.patient_id
        LEFT JOIN users u 
               ON u.full_name = c.doctor_name AND u.role_id = 2
        ORDER BY c.created_at DESC
    ");
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit("Database error: " . $e->getMessage());
}
?>

<?php if (empty($consultations)): ?>
  <div class="alert alert-warning text-center">No doctor submissions found.</div>
<?php else: ?>
  <?php foreach ($consultations as $c): ?>
  <div class="card mb-4 shadow-sm patient-card">
    <div class="card-header bg-primary text-white">
      <?= htmlspecialchars($c['patient_name']) ?> 
      <span class="float-end"><?= htmlspecialchars($c['doctor_name'] ?? 'N/A') ?></span>
    </div>
    <div class="card-body">
      <div class="d-flex mb-3">
        <img src="../uploads/<?= htmlspecialchars($c['photo'] ?? 'default.png') ?>" class="rounded" width="100" height="100">
        <div class="ms-3 small">
          <strong>PIN:</strong> <?= htmlspecialchars($c['patient_pin'] ?? '-') ?><br>
          <strong>Age:</strong> <?= htmlspecialchars($c['age'] ?? '-') ?> years<br>
          <strong>Gender:</strong> <?= htmlspecialchars($c['gender'] ?? '-') ?><br>
          <strong>Date:</strong> <?= htmlspecialchars($c['created_at'] ?? '-') ?>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <h6 class="section-title">Diagnosis</h6>
          <p><?= nl2br(htmlspecialchars($c['diagnosis'] ?? '')) ?></p>

          <h6 class="section-title">Investigations</h6>
          <p><?= nl2br(htmlspecialchars($c['investigations'] ?? '')) ?></p>

          <h6 class="section-title">Chief Complaint</h6>
          <p><?= nl2br(htmlspecialchars($c['chief_complaint'] ?? '')) ?></p>

          <h6 class="section-title">Physical Examination</h6>
          <p><?= nl2br(htmlspecialchars($c['physical_exam'] ?? '')) ?></p>
        </div>

        <div class="col-md-6">
          <h6 class="section-title">Vital Signs</h6>
          <ul class="list-group mb-2">
            <li class="list-group-item">Temperature: <?= htmlspecialchars($c['temperature'] ?? '-') ?> °C</li>
            <li class="list-group-item">Pulse: <?= htmlspecialchars($c['pulse'] ?? '-') ?> bpm</li>
            <li class="list-group-item">BP: <?= htmlspecialchars($c['blood_pressure'] ?? '-') ?></li>
            <li class="list-group-item">Respiration: <?= htmlspecialchars($c['respiratory_rate'] ?? '-') ?></li>
            <li class="list-group-item">O₂ Sat: <?= htmlspecialchars($c['oxygen_saturation'] ?? '-') ?>%</li>
            <li class="list-group-item">Pain: <?= htmlspecialchars($c['pain_level'] ?? '-') ?>/10</li>
            <li class="list-group-item">Weight: <?= htmlspecialchars($c['weight_kg'] ?? '-') ?> kg</li>
            <li class="list-group-item">Height: <?= htmlspecialchars($c['height_cm'] ?? '-') ?> cm</li>
            <li class="list-group-item">BMI: <?= htmlspecialchars($c['bmi'] ?? '-') ?></li>
          </ul>
        </div>
      </div>

      <!-- Lab Orders -->
      <h6 class="section-title mt-3">Lab Orders</h6>
      <?php
      $labStmt = $pdo->prepare("SELECT * FROM lab_orders WHERE patient_id = ?");
      $labStmt->execute([$c['patient_id']]);
      $labs = $labStmt->fetchAll();
      if ($labs):
      ?>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Test</th><th>Status</th><th>Result</th></tr></thead>
        <tbody>
        <?php foreach ($labs as $l): ?>
          <tr>
            <td><?= htmlspecialchars($l['test_name']) ?></td>
            <td><?= $l['is_paid'] ? 'Paid' : 'Unpaid' ?></td>
            <td><?= htmlspecialchars($l['result'] ?? 'Pending') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p class="text-muted">No lab orders.</p>
      <?php endif; ?>

      <!-- Nursing Orders -->
      <h6 class="section-title">Nursing Orders</h6>
      <?php
      $nurseStmt = $pdo->prepare("SELECT * FROM nursing_orders WHERE patient_id = ?");
      $nurseStmt->execute([$c['patient_id']]);
      $nurse = $nurseStmt->fetchAll();
      if ($nurse):
      ?>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Procedure</th><th>Paid</th></tr></thead>
        <tbody>
        <?php foreach ($nurse as $n): ?>
          <tr>
            <td><?= htmlspecialchars($n['procedure_name']) ?></td>
            <td><?= $n['is_paid'] ? 'Yes' : 'No' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p class="text-muted">No nursing orders.</p>
      <?php endif; ?>

      <!-- Pharmacy Orders -->
      <h6 class="section-title">Pharmacy Orders</h6>
      <?php
      $phStmt = $pdo->prepare("SELECT * FROM pharmacy_orders WHERE patient_id = ?");
      $phStmt->execute([$c['patient_id']]);
      $pharmacy = $phStmt->fetchAll();
      if ($pharmacy):
      ?>
      <table class="table table-sm table-bordered">
        <thead><tr><th>Medicine</th><th>Dosage</th><th>Paid</th></tr></thead>
        <tbody>
        <?php foreach ($pharmacy as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['medicine_name']) ?></td>
            <td><?= htmlspecialchars($p['dosage']) ?></td>
            <td><?= $p['is_paid'] ? 'Yes' : 'No' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p class="text-muted">No pharmacy orders.</p>
      <?php endif; ?>

    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>