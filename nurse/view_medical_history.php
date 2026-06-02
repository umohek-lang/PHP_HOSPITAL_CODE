<?php
require '../includes/auth.php';
require '../db.php';

$patient_id = $_GET['id'] ?? null;

if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM medical_historys WHERE id = ?");
    $stmt->execute([$patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $surgical_history = json_decode($row['surgical_history'], true);
        $medications = json_decode($row['medications'], true);
        $social_history = json_decode($row['social_history'], true);
        $immunization = json_decode($row['immunization'], true);
        $ros = json_decode($row['ros'], true);
        $obstetric = json_decode($row['obstetric'], true);
        $physical_exam = json_decode($row['physical_exam'], true);
    } else {
        echo "<div class='alert alert-danger'>❌ Record not found.</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-warning'>❌ Patient ID not provided.</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Medical History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4 class="text-primary">🩺 Patient Medical History</h4>
        <div>
            <a href="print_medical_history.php?id=<?= $row['id'] ?>" class="btn btn-success" target="_blank">
    🖨️ Download PDF
</a>

            <a href="dashboard.php" class="btn btn-outline-secondary">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3"><?= htmlspecialchars($row['full_name']) ?></h5>
            <p>
                <strong>Gender:</strong> <?= htmlspecialchars($row['gender']) ?> |
                <strong>Age:</strong> <?= htmlspecialchars($row['age']) ?><br>
                <strong>Phone:</strong> <?= htmlspecialchars($row['phone']) ?><br>
                <strong>Address:</strong> <?= htmlspecialchars($row['address']) ?>
            </p>

            <!-- Surgical History -->
            <div class="mt-4">
                <h6 class="text-primary">Surgical History</h6>
                <ul class="list-group">
                    <?php if (!empty($surgical_history)): ?>
                        <?php foreach ($surgical_history as $surgery): ?>
                            <li class="list-group-item">
                                <strong>Date:</strong> <?= htmlspecialchars($surgery['date']) ?>,
                                <strong>Name:</strong> <?= htmlspecialchars($surgery['name']) ?>,
                                <strong>Complications:</strong> <?= htmlspecialchars($surgery['complications']) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No surgical history recorded.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Medications -->
            <div class="mt-4">
                <h6 class="text-primary">Medications</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Indication</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medications as $med): ?>
                        <tr>
                            <td><?= htmlspecialchars($med['name']) ?></td>
                            <td><?= htmlspecialchars($med['dosage']) ?></td>
                            <td><?= htmlspecialchars($med['frequency']) ?></td>
                            <td><?= htmlspecialchars($med['duration']) ?></td>
                            <td><?= htmlspecialchars($med['indication']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Social History -->
            <div class="mt-4">
                <h6 class="text-primary">Social History</h6>
                <ul class="list-group">
                    <?php foreach ($social_history as $key => $value): ?>
                        <li class="list-group-item">
                            <strong><?= ucwords(str_replace("_", " ", $key)) ?>:</strong> <?= htmlspecialchars($value) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Immunization -->
            <div class="mt-4">
                <h6 class="text-primary">Immunization</h6>
                <ul class="list-group">
                    <?php foreach ($immunization as $key => $value): ?>
                        <li class="list-group-item">
                            <strong><?= ucwords(str_replace("_", " ", $key)) ?>:</strong> <?= htmlspecialchars($value) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Review of Systems -->
            <div class="mt-4">
                <h6 class="text-primary">Review of Systems (ROS)</h6>
                <ul class="list-group">
                    <?php foreach ($ros as $key => $value): ?>
                        <li class="list-group-item">
                            <strong><?= ucwords(str_replace("_", " ", $key)) ?>:</strong> <?= htmlspecialchars($value) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Obstetric History -->
            <div class="mt-4">
                <h6 class="text-primary">Obstetric History</h6>
                <ul class="list-group">
                    <?php foreach ($obstetric as $key => $value): ?>
                        <li class="list-group-item">
                            <strong><?= ucwords(str_replace("_", " ", $key)) ?>:</strong> <?= htmlspecialchars($value) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Physical Examination -->
            <div class="mt-4">
                <h6 class="text-primary">Physical Exam</h6>
                <ul class="list-group">
                    <?php foreach ($physical_exam as $key => $value): ?>
                        <li class="list-group-item">
                            <strong><?= strtoupper($key) ?>:</strong> <?= htmlspecialchars($value) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
