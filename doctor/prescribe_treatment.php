<?php
session_start();
require '../includes/auth.php'; // assume $pdo is defined here
require '../db.php'; // PDO connection

checkRole(2); // doctor only

$patient_id = $_GET['patient_id'] ?? null;
$message = '';

// Fetch all medicines for dropdown
$medicines = $pdo->query("SELECT medicine_id, medicine_name FROM medicines ORDER BY medicine_name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $treatment_name = trim($_POST['treatment_name']);
    $medicine_id = $_POST['medicine_id'] ?? null;
    $notes = trim($_POST['notes']);
    $treatment_date = $_POST['treatment_date'] ?? date('Y-m-d');
    
    if ($patient_id && $treatment_name) {
        $stmt = $pdo->prepare("INSERT INTO treatments (patient_id, medicine_id, treatment_name, notes, treatment_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$patient_id, $medicine_id, $treatment_name, $notes, $treatment_date]);
        $message = "✅ Treatment prescribed successfully!";
    } else {
        $message = "⚠️ Please provide a treatment name.";
    }
}

// Fetch previous treatments
$prevTreatments = [];
if ($patient_id) {
    $stmt = $pdo->prepare("SELECT t.*, m.medicine_name FROM treatments t LEFT JOIN medicines m ON t.medicine_id = m.medicine_id WHERE t.patient_id = ? ORDER BY t.created_at DESC");
    $stmt->execute([$patient_id]);
    $prevTreatments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prescribe Treatment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">

    <h4>Prescribe Treatment</h4>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="treatment_name" class="form-label">Treatment Name</label>
            <input type="text" class="form-control" id="treatment_name" name="treatment_name" required>
        </div>

        <div class="mb-3">
            <label for="medicine_id" class="form-label">Medicine (Optional)</label>
            <select class="form-select" id="medicine_id" name="medicine_id">
                <option value="">-- Select Medicine --</option>
                <?php foreach ($medicines as $m): ?>
                    <option value="<?= $m['medicine_id'] ?>"><?= htmlspecialchars($m['medicine_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Notes / Instructions</label>
            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="treatment_date" class="form-label">Treatment Date</label>
            <input type="date" class="form-control" id="treatment_date" name="treatment_date" value="<?= date('Y-m-d') ?>">
        </div>

        <button type="submit" class="btn btn-primary">Prescribe</button>
    </form>

    <h5>Previous Treatments</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Treatment</th>
                <th>Medicine</th>
                <th>Notes</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prevTreatments as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['treatment_name']) ?></td>
                <td><?= htmlspecialchars($t['medicine_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($t['notes']) ?></td>
                <td><?= date('d M Y', strtotime($t['treatment_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
</body>
</html>
