<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../db.php'; // Database connection

// Pagination settings
$limit = 10; // number of rows per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search & filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Base query
$query = "
    SELECT 
        a.*,
        p.full_name AS patient_name,
        u.full_name AS doctor_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users u ON a.doctor_id = u.user_id
    WHERE u.role_id = 2
";

// Apply filters
$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = "p.full_name LIKE :search";
    $params[':search'] = "%$search%";
}
if ($date !== '') {
    $conditions[] = "a.appointment_date = :date";
    $params[':date'] = $date;
}
if ($status !== '') {
    $conditions[] = "a.status = :status";
    $params[':status'] = $status;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Count total rows for pagination
$countStmt = $pdo->prepare(str_replace("a.*, p.full_name AS patient_name, u.full_name AS doctor_name", "COUNT(*)", $query));
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// Add pagination to query
$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);

// Bind dynamic parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total pages
$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Appointments</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h4 class="mb-0">📅 Appointments — Doctors (Role ID: 2)</h4>
      <span class="badge bg-light text-dark"><?= $total ?> Found</span>
    </div>

    <div class="card-body">

      <!-- 🔍 Search and Filter Bar -->
      <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
          <input type="text" name="search" class="form-control" placeholder="Search by Patient Name" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
          <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button type="submit" class="btn btn-success">Filter</button>
        </div>
      </form>

      <?php if (empty($appointments)): ?>
        <div class="alert alert-warning text-center">No appointments found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>#</th>
                <th>Patient Name</th>
                <th>Doctor Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Seen</th>
                <!--<th>Seen At</th>-->
                <!--<th>Seen By</th>-->
                <!--<th>Seen Time</th>-->
                <th>Created</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <?php foreach ($appointments as $i => $row): ?>
                <tr>
                  <td><?= $offset + $i + 1 ?></td>
                  <td><?= htmlspecialchars($row['patient_name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($row['doctor_name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                  <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                  <td>
                    <span class="badge <?= $row['status'] === 'completed' ? 'bg-success' : ($row['status'] === 'cancelled' ? 'bg-danger' : 'bg-secondary') ?>">
                      <?= ucfirst($row['status']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($row['seen']) && $row['seen'] == 1): ?>
                      <span class="badge bg-success">Seen</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Not Seen</span>
                    <?php endif; ?>
                  </td>
                  <!--<td><?= htmlspecialchars($row['seen_at'] ?? '-') ?></td>-->
                  <!--<td><?= htmlspecialchars($row['seen_by'] ?? '-') ?></td>-->
                  <!--<td><?= htmlspecialchars($row['seen_time'] ?? '-') ?></td>-->
                  <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- 📄 Pagination -->
        <nav>
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&status=<?= urlencode($status) ?>">Previous</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&status=<?= urlencode($status) ?>">Next</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
