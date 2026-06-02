<?php 
require '../auth.php';
require '../db.php';

$message = '';

// Fetch data for medicines
$medicines = [];
try {
    $stmt = $pdo->query("SELECT medicine_id, medicine_name FROM medicines");
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "❌ Error fetching data: " . $e->getMessage();
}

// Ensure prescription_id is set
if (!isset($_GET['prescription_id'])) {
    header("Location: prescription.php");
    exit();
}

$prescription_id = $_GET['prescription_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicine_id = $_POST['medicine_id'] ?? '';
    $dosage = trim($_POST['dosage'] ?? '');
    $duration = trim($_POST['duration'] ?? '');

    // Basic validation
    if ($medicine_id && $dosage && $duration) {
        try {
            $sql = "INSERT INTO prescription_items (prescription_id, medicine_id, dosage, duration) 
                    VALUES (:prescription_id, :medicine_id, :dosage, :duration)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':prescription_id' => $prescription_id,
                ':medicine_id' => $medicine_id,
                ':dosage' => $dosage,
                ':duration' => $duration
            ]);

            $message = "✅ Prescription item added successfully!";
            header("Location: prescriptions.php?prescription_id=" . urlencode($prescription_id));
            exit();
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Prescription Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            transition: transform 0.3s ease-in-out;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <h3 class="mb-4">Add Prescription Item</h3>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow rounded-4">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="prescription_id" value="<?= htmlspecialchars($prescription_id) ?>">

                <div class="mb-3">
                    <label for="medicine_id" class="form-label">Select Medicine</label>
                    <select class="form-select" id="medicine_id" name="medicine_id" required>
                        <option value="">-- Select Medicine --</option>
                        <?php foreach ($medicines as $medicine): ?>
                            <option value="<?= $medicine['medicine_id'] ?>">
                                <?= htmlspecialchars($medicine['medicine_id']) ?> - <?= htmlspecialchars($medicine['medicine_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="dosage" class="form-label">Dosage</label>
                    <input type="text" class="form-control" id="dosage" name="dosage" required>
                </div>

                <div class="mb-3">
                    <label for="duration" class="form-label">Duration</label>
                    <input type="text" class="form-control" id="duration" name="duration" required>
                </div>

                <button type="submit" class="btn btn-primary">Add Item</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
