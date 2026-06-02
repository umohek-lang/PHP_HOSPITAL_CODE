<?php 
require '../payment_alerts.php';
require '../includes/auth.php';  // Authentication (login check)
require '../db.php';    // Database connection

// Pagination configuration
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Initialize search and filter values
$searchQuery = '';
$searchValue = '';
$statusValue = '';

// Build WHERE clause if search or filter is used
$whereClauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $searchValue = trim($_GET['search']);
    $whereClauses[] = "(full_name LIKE :search OR patient_pin LIKE :search OR email LIKE :search OR phone LIKE :search OR patient_status LIKE :search OR created_at LIKE :search)";
    $params[':search'] = '%' . $searchValue . '%';
}

if (!empty($_GET['status'])) {
    $statusValue = $_GET['status'];
    $whereClauses[] = "patient_status = :status";
    $params[':status'] = $statusValue;
}

// Final WHERE clause
if (!empty($whereClauses)) {
    $searchQuery = ' WHERE ' . implode(' AND ', $whereClauses);
}

// Fetch patients
$stmt = $pdo->prepare("SELECT * FROM patients" . $searchQuery . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset");

// Bind search/status params
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll();

// Count total patients
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM patients" . $searchQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$countStmt->execute();
$totalPatients = $countStmt->fetchColumn();
$totalPages = ceil($totalPatients / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-body { padding: 1rem; }
        .card-title { font-size: 1.1rem; font-weight: bold; }
        .card-text { font-size: 0.9rem; color: #555; }
        .card-footer { background-color: #f8f9fa; padding: 0.75rem; }
        .card-footer .btn { margin-right: 5px; font-size: 0.85rem; }
        .card-img-top {
            object-fit: contain;
            width: 100%;
            height: 180px;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
        .search-form, .card { margin-left: 15px; margin-right: 15px; }
        .row { margin-left: auto; margin-right: auto; }
        .btn-dashboard { margin-left: 20px; }
    </style>
</head>
<body>

<h3 class="mb-4 text-center">All Patients</h3>
<a href="../index.php" class="btn btn-primary mb-3 btn-dashboard">Back to Dashboard</a>

<!-- Search Form -->
<form method="get" class="mb-4 search-form">
    <div class="row g-2">
        <div class="col-md-6">
            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($searchValue) ?>" placeholder="Search by name, PIN, email, phone, or date created">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="Inpatient" <?= ($statusValue === 'Inpatient') ? 'selected' : '' ?>>Inpatient</option>
                <option value="Outpatient" <?= ($statusValue === 'Outpatient') ? 'selected' : '' ?>>Outpatient</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">Search</button>
        </div>
    </div>
</form>

<!-- Patient Cards -->
<div class="row row-cols-1 row-cols-md-3 g-3">
    <?php if (count($patients) > 0): ?>
        <?php foreach ($patients as $patient): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="../uploads/<?= htmlspecialchars($patient['photo'] ?? '') ?>" alt="Patient Photo" class="card-img-top">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($patient['full_name'] ?? '') ?></h5>
                        <p class="card-text">
                            <strong>Gender:</strong> <?= htmlspecialchars($patient['gender'] ?? '') ?><br>
                            <strong>Age:</strong> <?= htmlspecialchars($patient['age'] ?? '') ?><br>
                            <strong>Phone:</strong> <?= htmlspecialchars($patient['phone'] ?? '') ?><br>
                            <strong>Email:</strong> <?= htmlspecialchars($patient['email'] ?? '') ?><br>
                            <strong>Status:</strong> <?= htmlspecialchars($patient['patient_status'] ?? '') ?><br>
                            
                        </p>
                    </div>
                    <div class="card-footer text-center">
                        <a href="edit_patient.php?id=<?= htmlspecialchars($patient['patient_id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete_patient.php?id=<?= htmlspecialchars($patient['patient_id']) ?>" class="btn btn-danger btn-sm">Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col text-center">
            <div class="alert alert-warning">No patient records found.</div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation" class="mt-4">
  <ul class="pagination justify-content-center">
    <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>" aria-label="Previous">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i == $page ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
      <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>" aria-label="Next">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>
  </ul>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
