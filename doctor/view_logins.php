<?php
require '../db.php';
session_start();

// Restrict access to Admin and Doctor
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 2])) {
    header("Location: ../login.php");
    exit;
}

// Get filters
$search = trim($_GET['search'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// SQL setup
$sql = "SELECT user_id, full_name, email, role_id, login_time, logout_time, status, login_state, duration, ip_address, user_agent FROM login_activity WHERE 1";
$count_sql = "SELECT COUNT(*) FROM login_activity WHERE 1";
$params = $count_params = [];

// Apply filters
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

// Total pages
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch paginated data
$sql .= " ORDER BY login_time DESC LIMIT $offset, $records_per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Role mapping
$roles = [
    1 => 'Admin', 2 => 'Doctor', 3 => 'Nurse', 4 => 'Cashier',
    5 => 'Pharmacist', 6 => 'Lab Technician', 7 => 'Patient',
    8 => 'Receptionist', 9 => 'Radiologist', 10 => 'Cleaner'
];

// Determine back link
$backLink = ($_SESSION['user']['role_id'] == 1) ? 'admin/dashboard.php' : 'doctor/dashboard.php';
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
                    <th>Online State</th>
                    <th>Duration</th>
                    <th>IP</th>
                    <th>Device</th>
                    <th>Action</th>

                </tr>
            </thead>
            <tbody id="loginTableBody">
                <?php if ($logins): ?>
                    <?php foreach ($logins as $index => $log): ?>
                        <tr>
                            <td class="text-center"><?= $offset + $index + 1 ?></td>
                            <td><?= htmlspecialchars($log['full_name']) ?></td>
                            <td><?= htmlspecialchars($log['email']) ?></td>
                            <td><span class="badge bg-info text-dark role-badge"><?= $roles[$log['role_id']] ?? 'Unknown' ?></span></td>
                            <td><?= htmlspecialchars($log['login_time']) ?></td>
                            <td><?= $log['logout_time'] ?? '<em class="text-muted">Still logged in</em>' ?></td>
                            <td class="text-center">
                                <span class="<?= $log['status'] === 'success' ? 'status-success' : 'status-failed' ?>">
                                    <?= ucfirst($log['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="<?= $log['login_state'] === 'Online' ? 'text-success' : 'text-secondary' ?>">
                                    <?= $log['login_state'] ?? '<em class="text-muted">—</em>' ?>
                                </span>
                            </td>
                            <td><?= $log['duration'] ?? '<em class="text-muted">—</em>' ?></td>
                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                            <td style="max-width: 200px; word-break: break-word;"><?= htmlspecialchars($log['user_agent']) ?></td>
<td class="text-center">
    <form method="POST" action="delete_login.php" onsubmit="return confirm('Are you sure you want to delete this record?');">
        <input type="hidden" name="user_id" value="<?= $log['user_id'] ?>">
        <input type="hidden" name="login_time" value="<?= $log['login_time'] ?>">
        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
    </form>
</td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted">No login records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <div class="text-center mt-4">
        <!-- <a href="<?= $backLink ?>" class="btn btn-secondary">← Back to Dashboard</a> -->
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<!-- JavaScript for live updates -->
<script>
const roleMap = {
    1: 'Admin', 2: 'Doctor', 3: 'Nurse', 4: 'Cashier',
    5: 'Pharmacist', 6: 'Lab Technician', 7: 'Patient',
    8: 'Receptionist', 9: 'Radiologist', 10: 'Cleaner'
};

function fetchLogins() {
    fetch('fetch_login_activity.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('loginTableBody');
            tbody.innerHTML = '';

            if (data.length === 0 || data.error) {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">No login records found.</td></tr>';
                return;
            }

            data.forEach((log, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">${index + 1}</td>
                    <td>${log.full_name}</td>
                    <td>${log.email}</td>
                    <td><span class="badge bg-info text-dark role-badge">${roleMap[log.role_id] || 'Unknown'}</span></td>
                    <td>${log.login_time}</td>
                    <td>${log.logout_time ?? '<em class="text-muted">Still logged in</em>'}</td>
                    <td class="text-center">
                        <span class="${log.status === 'success' ? 'status-success' : 'status-failed'}">
                            ${log.status.charAt(0).toUpperCase() + log.status.slice(1)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="${log.login_state === 'Online' ? 'text-success' : 'text-secondary'}">
                            ${log.login_state ?? '<em class="text-muted">—</em>'}
                        </span>
                    </td>
                    <td>${log.duration ?? '<em class="text-muted">—</em>'}</td>
                    <td>${log.ip_address}</td>
                    <td style="max-width: 200px; word-break: break-word;">${log.user_agent}</td>
                    <td class="text-center">
        <form method="POST" action="delete_login.php" onsubmit="return confirm('Are you sure you want to delete this record?');">
            <input type="hidden" name="user_id" value="${log.user_id}">
            <input type="hidden" name="login_time" value="${log.login_time}">
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching login data:', error);
            const tbody = document.getElementById('loginTableBody');
            tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Failed to load login data.</td></tr>';
        });
}

// Initial fetch
 fetchLogins();
 setInterval(fetchLogins, 10000);
</script>

</body>
</html>
