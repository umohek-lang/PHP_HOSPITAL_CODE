<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/auth.php';
require '../db.php'; // your DB connection

// Fetch pending lab tests from lab_tests table
$stmt = $pdo->prepare("
    SELECT l.*, p.full_name 
    FROM lab_tests l 
    JOIN patients p ON l.patient_id = p.patient_id 
    WHERE l.status = 'pending' 
    ORDER BY l.test_date DESC
");
$stmt->execute();
$labTests = $stmt->fetchAll();

// Pagination setup for completed tests
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total completed test count
$countStmt = $pdo->query("SELECT COUNT(*) FROM patient_orders WHERE service_type = 'lab' AND status = 'completed'");
$totalCompleted = $countStmt->fetchColumn();
$totalPages = ceil($totalCompleted / $limit);

// Fetch completed lab test orders from patient_orders table with pagination
$completedStmt = $pdo->prepare("
    SELECT po.*, p.full_name 
    FROM patient_orders po 
    JOIN patients p ON po.patient_id = p.patient_id 
    WHERE po.service_type = 'lab' AND po.status = 'completed'
    ORDER BY po.completed_at DESC
    LIMIT :limit OFFSET :offset
");
$completedStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$completedStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$completedStmt->execute();
$completedTests = $completedStmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lab Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .compressed-wrapper {
            max-width: 600px;
            margin: auto;
        }

        .compressed-table th,
        .compressed-table td {
            font-size: 0.75rem;
            padding: 0.3rem 0.4rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <h3 class="mb-4 text-primary">🧪 Pending Lab Tests</h3>

    <?php if (count($labTests) > 0): ?>
        <?php foreach ($labTests as $test): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5><?= htmlspecialchars($test['test_name']) ?></h5>
                    <p>
                        <strong>Patient:</strong> <?= htmlspecialchars($test['full_name']) ?><br>
                        <strong>Date:</strong> <?= htmlspecialchars($test['test_date']) ?><br>
                    </p>

                    <form action="submit_lab_result1.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="lab_test_id" value="<?= $test['lab_test_id'] ?>">

                        <div class="mb-2">
                            <label for="result_<?= $test['lab_test_id'] ?>" class="form-label">Result</label>
                            <textarea name="result" id="result_<?= $test['lab_test_id'] ?>" class="form-control" required></textarea>
                        </div>

                        <div class="mb-2">
                            <label for="report_<?= $test['lab_test_id'] ?>" class="form-label">Upload Report (PDF/Image)</label>
                            <input type="file" name="report_file" id="report_<?= $test['lab_test_id'] ?>" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>

                        <button type="submit" class="btn btn-success">✅ Submit Result</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No pending lab tests.</div>
    <?php endif; ?>

    <hr class="my-5">

    <h3 class="mb-4 text-success">🧾 Completed Lab order Tests</h3>

    <?php if (count($completedTests) > 0): ?>
        <div class="compressed-wrapper table-responsive">
            <table class="table table-bordered table-striped align-middle compressed-table text-center">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Details</th>
                        <th>Completed By</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedTests as $index => $test): ?>
                        <tr>
                            <td><?= $offset + $index + 1 ?></td>
                            <td><?= htmlspecialchars($test['full_name']) ?></td>
                            <td title="<?= htmlspecialchars($test['details'] ?? '') ?>">
                                <?= htmlspecialchars(substr($test['details'], 0, 30)) ?>...
                            </td>
                            <td><?= htmlspecialchars($test['completed_by'] ?? '') ?></td>
                            <td><?= htmlspecialchars($test['completed_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <nav aria-label="Completed Tests Pagination">
            <ul class="pagination justify-content-center mt-4">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">« Prev</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next »</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php else: ?>
        <div class="alert alert-secondary">No completed lab tests found.</div>
    <?php endif; ?>
</div>

</body>
</html>
