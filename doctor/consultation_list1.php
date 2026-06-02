<?php
require '../includes/auth.php';
require '../db.php';

// Handle search
$search = $_GET['search'] ?? '';
$date = $_GET['date'] ?? '';

// Pagination settings
$limit = 10; // number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // never below 1
$offset = ($page - 1) * $limit;

// Base query
$query = "FROM consultations c 
          LEFT JOIN patients p ON c.patient_id = p.patient_id 
          WHERE (p.full_name LIKE :search)";

$params = [
    ':search' => "%$search%",
];

if (!empty($date)) {
    $query .= " AND c.consultation_date = :date";
    $params[':date'] = $date;
}

// Fetch total records count
$countStmt = $pdo->prepare("SELECT COUNT(*) $query");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch actual records with pagination
$sql = "SELECT c.*, p.full_name, p.photo $query 
        ORDER BY c.consultation_date DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Bind integer values separately
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Consultation Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .passport {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .table th, .table td {
            vertical-align: middle;
            font-size: 13px;
        }
        .table thead th {
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card">
        <div class="card-header bg-dark text-white text-center">
            <h2 class="mb-0">Patient Consultation Records</h2>
        </div>
        <div class="card-body">

            <!-- Search Form -->
            <form class="row row-cols-lg-auto g-3 align-items-center mb-4" method="GET">
                <div class="col-12">
                    <input type="text" name="search" class="form-control" placeholder="Search by patient name" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-12">
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="consultation_list.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <?php if (count($consultations) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Patient</th>
                                <th>Photo</th>
                                <th>BP</th>
                                <th>Temp</th>
                                <th>Pulse</th>
                                <th>Resp. Rate</th>
                                <th>O2 Sat</th>
                                <th>Pain</th>
                                <th>Height</th>
                                <th>Weight</th>
                                <th>BMI</th>
                                <th>Blood Sugar</th>
                                <th>AVPU</th>
                                <th>Vitals Time</th>
                                <th>Symptoms Notes</th>
                                <th>Complaint</th>
                                <th>Exam</th>
                                <th>Diagnosis</th>
                                <th>Investigations</th>
                                <th>Treatment</th>
                                <th>Doctor's Signature</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultations as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($row['full_name'] ?? 'Unknown') ?></td>
                                    <td>
                                        <?php if (!empty($row['photo'])): ?>
                                            <img src="../uploads/<?= htmlspecialchars($row['photo']) ?>" class="passport" alt="Patient">
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['bp']?? '') ?></td>
                                    <td><?= htmlspecialchars($row['temperature'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['pulse'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['respiratory_rate']?? '') ?></td>
                                    <td><?= htmlspecialchars($row['oxygen_saturation'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['pain_level'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['height_cm'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['weight_kg'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['bmi'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['blood_sugar'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['consciousness_level'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['vitals_time'] ?? '') ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['symptoms_notes'] ?? '')) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['chief_complaint'] ?? '')) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['physical_exam'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['diagnosis'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['investigations'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['treatment_plan'])) ?></td>
                                    <td><?= htmlspecialchars($row['doctor_signature']) ?></td>
                                    <td><?= htmlspecialchars($row['consultation_date']) ?></td>
                                    <td>
    <a href="edit_consultation.php?patient_id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-warning">Edit</a>

    <a href="delete_consultation.php?patient_id=<?= $row['patient_id'] ?>" 
       class="btn btn-sm btn-danger" 
       onclick="return confirm('Are you sure you want to delete this consultation?')">Delete</a>

    <a href="print_full_consultation.php?patient_id=<?= $row['patient_id'] ?>" 
       class="btn btn-sm btn-outline-primary" target="_blank">
        🖨️ Print PDF
    </a>

    <!--<a href="send_consultation_email.php?patient_id=<?= $row['patient_id'] ?>" -->
    <!--   class="btn btn-sm btn-success" -->
    <!--   onclick="return confirm('Send this consultation to the patient\'s email?')">-->
    <!--    📧 Email-->
    <!--</a>-->
</td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">

        <!-- Previous button -->
        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" 
               href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
               Previous
            </a>
        </li>

        <!-- Page numbers -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" 
                   href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>

        <!-- Next button -->
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link" 
               href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
               Next
            </a>
        </li>

    </ul>
</nav>
<?php endif; ?>

                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">No consultations found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
