<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/auth.php';
require '../db.php';

if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 8) {
    exit('Unauthorized');
}

$stmt = $pdo->query("
    SELECT pr.*, 
           p.full_name AS patient_name, p.photo, p.gender, p.age, p.patient_pin,
           u.full_name AS doctor_name
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.patient_id
    JOIN users u ON pr.doctor_id = u.user_id
    ORDER BY pr.created_at DESC
");
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($prescriptions)): ?>
  <div class="alert alert-warning text-center">No prescriptions found.</div>
<?php else: ?>
  <?php foreach ($prescriptions as $pr): ?>
  <div class="card mb-3 shadow-sm border-start border-primary border-4">
    <div class="card-header bg-primary text-white">
      <?= htmlspecialchars($pr['patient_name']) ?>
      <span class="float-end">By: <?= htmlspecialchars($pr['doctor_name']) ?></span>
    </div>
    <div class="card-body">
      <div class="d-flex mb-3">
        <img src="../uploads/<?= htmlspecialchars($pr['photo']) ?>" width="90" height="90" class="rounded">
        <div class="ms-3 small">
          <strong>PIN:</strong> <?= htmlspecialchars($pr['patient_pin']) ?><br>
          <strong>Age:</strong> <?= htmlspecialchars($pr['age']) ?> years<br>
          <strong>Gender:</strong> <?= htmlspecialchars($pr['gender']) ?><br>
          <strong>Date:</strong> <?= htmlspecialchars($pr['created_at']) ?>
        </div>
      </div>

      <h6 class="text-primary">Prescription Details</h6>
      <table class="table table-bordered table-sm">
        <thead><tr><th>Medicine</th><th>Dosage</th><th>Duration</th><th>Instructions</th></tr></thead>
        <tbody>
        <?php
        $items = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
        $items->execute([$pr['id']]);
        foreach ($items->fetchAll() as $it):
        ?>
          <tr>
            <td><?= htmlspecialchars($it['medicine_name']) ?></td>
            <td><?= htmlspecialchars($it['dosage']) ?></td>
            <td><?= htmlspecialchars($it['duration']) ?></td>
            <td><?= nl2br(htmlspecialchars($it['instructions'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
