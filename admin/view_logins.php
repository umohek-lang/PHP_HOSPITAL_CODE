<?php
require '../db.php';
session_start();

// Restrict to Admin only
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header("Location: login.php");
    exit;
}

// Get filters
$search = trim($_GET['search'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Build SQL query
$sql = "SELECT user_id, full_name, email, role_id, login_time, logout_time, status, ip_address, user_agent FROM login_activity WHERE 1";
$count_sql = "SELECT COUNT(*) FROM login_activity WHERE 1";
$params = [];
$count_params = [];

// Filtering
if ($search) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $count_sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = $count_params[] = "%$search%";
    $params[] = $count_params[] = "%$search%";
}

if ($start_date && $end_date) {
    $sql .= " AND login_time BETWEEN ? AND ?";
    $count_sql .= " AND login_time BETWEEN ? AND ?";
    $params[] = $count_params[] = $start_date . " 00:00:00";
    $params[] = $count_params[] = $end_date . " 23:59:59";
}

// Count total records
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Append LIMIT for pagination
$sql .= " ORDER BY login_time DESC LIMIT $offset, $records_per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Role name mapping
$roles = [
    1 => 'Admin', 2 => 'Doctor', 3 => 'Nurse', 4 => 'Cashier',
    5 => 'Pharmacist', 6 => 'Lab Technician', 7 => 'Patient',
    8 => 'Receptionist', 9 => 'Radiologist', 10 => 'Cleaner'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Activity - Angelora Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .table th, .table td { vertical-align: middle; }
        .status-success { color: green; font-weight: bold; }
        .status-failed { color: red; font-weight: bold; }
        .role-badge { font-size: 0.9rem; }
        .container { margin-top: 50px; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-4 text-primary">Login Activity - Angelora Hospital</h2>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4 border p-3 rounded shadow-sm bg-white">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-3">
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <?php if ($total_records > 0): ?>
        <p class="text-muted">
            Showing <?= $offset + 1 ?>–<?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
        </p>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped shadow-sm">
            <thead class="table-primary text-center">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>User Agent (Device)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logins): ?>
                    <?php foreach ($logins as $index => $log): ?>
                        <tr>
                            <td class="text-center"><?= $offset + $index + 1 ?></td>
                            <td><?= htmlspecialchars($log['full_name']) ?></td>
                            <td><?= htmlspecialchars($log['email']) ?></td>
                            <td>
                                <span class="badge bg-info text-dark role-badge">
                                    <?= $roles[$log['role_id']] ?? 'Unknown' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($log['login_time']) ?></td>
                            <td><?= $log['logout_time'] ?? '<em class="text-muted">Still logged in</em>' ?></td>
                            <td class="text-center">
                                <span class="<?= $log['status'] === 'success' ? 'status-success' : 'status-failed' ?>">
                                    <?= ucfirst($log['status']) ?>
                                </span>
                            </td>
                            <td><?= $log['ip_address'] ?></td>
                            <td style="max-width: 200px; word-break: break-word;"><?= htmlspecialchars($log['user_agent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No login records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Next Button -->
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
