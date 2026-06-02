<?php
require '../db.php';
session_start();

// ✅ Ensure only logged-in users can access
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// Pagination setup
$limit = 10; // patients per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search filter
$search = trim($_GET['search'] ?? '');
$where = "";
$params = [];

if ($search !== '') {
    $where = "WHERE full_name LIKE :search OR email LIKE :search OR phone LIKE :search";
    $params[':search'] = "%$search%";
}

// Count total patients
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM patients $where");
$stmtCount->execute($params);
$total_patients = $stmtCount->fetchColumn();
$total_pages = ceil($total_patients / $limit);

// Fetch patients
$sql = "SELECT * FROM patients
        $where
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Patients Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body { background-color: #f8f9fa; }
    .patient-photo { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
    .pagination .page-link { cursor: pointer; }
  </style>
</head>
<body>
<div class="container py-4">
  <h2 class="mb-4 text-center">Patients Dashboard</h2>

  <!-- Search -->
  <form class="row mb-3" method="get">
    <div class="col-md-10">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by name, email, or phone">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100">Search</button>
    </div>
  </form>

  <!-- Patients Table -->
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Gender</th>
          <th>Age</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Address</th>
          <th>Status</th>
          <th>Registered On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($patients): ?>
          <?php foreach ($patients as $p): ?>
          <tr>
            <td>
              <img src="<?= !empty($p['photo']) ? htmlspecialchars($p['photo']) : 'default-avatar.png' ?>" 
                   class="patient-photo" alt="Photo">
            </td>
            <td>
              <?= htmlspecialchars($p['full_name'] ?? '') ?><br>
              <small class="text-muted">PIN: <?= htmlspecialchars($p['patient_pin'] ?? '') ?></small>
            </td>
            <td><?= htmlspecialchars($p['gender'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['age'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['phone'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['email'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['address'] ?? '') ?></td>
            <td>
              <span class="badge bg-<?= ($p['patient_status'] ?? '') === 'Active' ? 'success' : 'secondary' ?>">
                <?= htmlspecialchars($p['patient_status'] ?? '') ?>
              </span>
            </td>
            <td><?= !empty($p['created_at']) ? date("Y-m-d", strtotime($p['created_at'])) : '' ?></td>
            <td>
              <!-- View Details Button -->
              <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#patientModal<?= $p['patient_id'] ?>">
                View Details
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="10" class="text-center text-muted">No patients found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <nav>
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>

</div>

<!-- ✅ Patient Modals Outside the Table -->
<?php foreach ($patients as $p): ?>
<div class="modal fade" id="patientModal<?= $p['patient_id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Patient Details - <?= htmlspecialchars($p['full_name'] ?? '') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-4 text-center">
            <img src="<?= !empty($p['photo']) ? htmlspecialchars($p['photo']) : 'default-avatar.png' ?>" 
                 class="img-fluid rounded-circle mb-3" style="max-width:150px;">
            <p><strong>PIN:</strong> <?= htmlspecialchars($p['patient_pin'] ?? '') ?></p>
          </div>
          <div class="col-md-8">
            <table class="table table-bordered">
              <tr><th>Full Name</th><td><?= htmlspecialchars($p['full_name'] ?? '') ?></td></tr>
              <tr><th>Date of Birth</th><td><?= htmlspecialchars($p['dob'] ?? '') ?></td></tr>
              <tr><th>Age</th><td><?= htmlspecialchars($p['age'] ?? '') ?></td></tr>
              <tr><th>Gender</th><td><?= htmlspecialchars($p['gender'] ?? '') ?></td></tr>
              <tr><th>Phone</th><td><?= htmlspecialchars($p['phone'] ?? '') ?></td></tr>
              <tr><th>Email</th><td><?= htmlspecialchars($p['email'] ?? '') ?></td></tr>
              <tr><th>Address</th><td><?= htmlspecialchars($p['address'] ?? '') ?></td></tr>
              <tr><th>Patient Type</th><td><?= htmlspecialchars($p['patient_type'] ?? '') ?></td></tr>
              <tr><th>Status</th><td><?= htmlspecialchars($p['patient_status'] ?? '') ?></td></tr>
              <tr><th>HMO</th><td><?= htmlspecialchars($p['hmo_name'] ?? '') ?></td></tr>
              <tr><th>Dispensation</th><td><?= htmlspecialchars($p['has_dispensation'] ?? '') ?></td></tr>
              <tr><th>SSN</th><td><?= htmlspecialchars($p['ssn'] ?? '') ?></td></tr>
              <tr><th>Language</th><td><?= htmlspecialchars($p['language'] ?? '') ?></td></tr>
              <tr><th>Marital Status</th><td><?= htmlspecialchars($p['marital_status'] ?? '') ?></td></tr>
              <tr><th>Registered By</th><td><?= htmlspecialchars($p['registered_by'] ?? '') ?></td></tr>
              <tr><th>Registration Date</th><td><?= htmlspecialchars($p['registration_date'] ?? '') ?></td></tr>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

</body>
</html>
