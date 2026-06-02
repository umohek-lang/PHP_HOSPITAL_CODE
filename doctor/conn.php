<?php 
require '../includes/auth.php';
require '../db.php';

// Ensure doctor is logged in
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit();
}

$patient_id = $_GET['patient_id'] ?? null;

// Fetch patient info using PDO
$patient = null;
if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = :patient_id");
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch consultation records using PDO
$consultations = [];
if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM consultations WHERE patient_id = :patient_id ORDER BY consultation_date DESC");
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Doctor Consultation Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<style>
body { background-color: #f8f9fa; }
.card { margin-bottom: 1rem; }
.table td, .table th { padding: .5rem; vertical-align: middle; }
.badge { font-size: 0.85rem; }
.alert { margin: .5rem 0; }
.card-header { font-weight: bold; background-color: #007bff; color: white; }
</style>
</head>
<body>
<div class="container-fluid mt-3">

    <h3 class="mb-3">Consultation Dashboard</h3>

    <div class="row">
        <!-- Patient Info -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-header">Patient Info</div>
                <div class="card-body">
                    <?php if ($patient): ?>
                        <p><strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?></p>
                        <p><strong>Age:</strong> <?= htmlspecialchars($patient['age']) ?></p>
                        <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($patient['contact']) ?></p>
                    <?php else: ?>
                        <p>No patient selected.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lab Results -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-header">Lab Results</div>
                <div class="card-body">
                    <button class="btn btn-success w-100 mb-2">View Lab</button>
                    <button class="btn btn-outline-success w-100">Add Lab Result</button>
                </div>
            </div>
        </div>

        <!-- Prescriptions -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-header">Prescriptions</div>
                <div class="card-body">
                    <button class="btn btn-warning w-100 mb-2">View Prescriptions</button>
                    <button class="btn btn-outline-warning w-100">Add Prescription</button>
                </div>
            </div>
        </div>

        <!-- Nursing Notes -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-header">Nursing Notes</div>
                <div class="card-body">
                    <button class="btn btn-info w-100 mb-2">View Nursing Notes</button>
                    <button class="btn btn-outline-info w-100">Add Nursing Note</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultation Records Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">Consultation Records</div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Doctor</th>
                                <th>Notes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($consultations): ?>
                                <?php foreach ($consultations as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['consultation_date']) ?></td>
                                        <td><?= htmlspecialchars($c['doctor_name']) ?></td>
                                        <td><?= htmlspecialchars($c['notes']) ?></td>
                                        <td>
                                            <?php if ($c['status'] === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No consultations found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- GLOBAL EVENT PROPAGATION/BUBBLING PREVENTION -->
<script>
document.addEventListener('click', function(e) {
    if (e.target.tagName === 'BUTTON') {
        e.stopPropagation(); // Stop bubbling for all buttons
    }
}, true);

document.addEventListener('submit', function(e) {
    e.stopPropagation(); // Stop bubbling for all forms
}, true);
</script>

</body>
</html>
