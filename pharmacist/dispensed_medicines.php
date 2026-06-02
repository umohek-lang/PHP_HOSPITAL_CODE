<?php 
require '../includes/auth.php';
require '../db.php';

// Fetch medicines
$medicines = $pdo->query("SELECT * FROM medicines WHERE stock > 0")->fetchAll();

// Fetch patient IDs
$patients = $pdo->query("SELECT patient_id, full_name, has_dispensation FROM patients")->fetchAll();

// Handle dispensing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $medicine_id = $_POST['medicine_id'];
    $quantity = (int) $_POST['quantity'];
    $prescribed_by = $_POST['prescribed_by'] ?? '';
    $dispensed_by = $_POST['dispensed_by'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Check stock
    $stmt = $pdo->prepare("SELECT stock FROM medicines WHERE medicine_id = ?");
    $stmt->execute([$medicine_id]);
    $medicine = $stmt->fetch();

    if ($medicine && $medicine['stock'] >= $quantity) {
        // Insert dispense record
        $stmt = $pdo->prepare("INSERT INTO dispensed_medicines (patient_id, medicine_id, quantity, prescribed_by, dispensed_by, notes)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $medicine_id, $quantity, $prescribed_by, $dispensed_by, $notes]);

        // Update stock
        $new_stock = $medicine['stock'] - $quantity;
        $stmt = $pdo->prepare("UPDATE medicines SET stock = ? WHERE medicine_id = ?");
        $stmt->execute([$new_stock, $medicine_id]);

        // Mark patient as having received medicine
$updatePatient = $pdo->prepare("UPDATE patients SET has_dispensation = 1 WHERE patient_id = ?");
$updatePatient->execute([$patient_id]);

        
        $success = "Medicine dispensed successfully.";
    } else {
        $error = "Insufficient stock!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Dispense Medicine</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap 5.3.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


    <style>
        body {
            background-color: #f8f9fa;
        }

        .fade-slide {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease-out forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Landscape card: wider than tall */
        .landscape-card {
            max-width: 900px;
            padding: 30px;
            font-size: 0.95rem;
        }

        h2 {
            animation: fadeInUp 1s ease-out forwards;
            animation-delay: 0.2s;
        }
    </style>
</head>
<body class="container d-flex justify-content-center align-items-center mt-5" style="min-height: 100vh;">
    <div class="w-100 mx-auto">
        <h2 class="text-center mb-4 fade-slide">Dispense Medicine</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success fade-slide"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger fade-slide"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm landscape-card fade-slide mx-auto">
            <form method="post" novalidate>
                <div class="row g-3">
                    <!-- PATIENT SELECT -->
<div class="col-md-6">
    <label for="patient_id" class="form-label">Select Patient</label>
    <select id="patient_id" class="form-select" name="patient_id" style="width: 100%;" required>
        <option value="">-- Select Patient --</option>
        <?php foreach ($patients as $patient): ?>
            <option value="<?= htmlspecialchars($patient['patient_id']) ?>"
                <?= isset($_GET['patient_id']) && $_GET['patient_id'] == $patient['patient_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($patient['patient_id']) ?> - <?= htmlspecialchars($patient['full_name']) ?>
                <?= $patient['has_dispensation'] ? '(✓ Dispensed)' : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- MEDICINE SELECT -->
<div class="col-md-6">
    <label for="medicine_id" class="form-label">Select Medicine</label>
    <select id="medicine_id" class="form-select" name="medicine_id" style="width: 100%;" required>
        <option value="">-- Select Medicine --</option>
        <?php foreach ($medicines as $med): ?>
            <option value="<?= htmlspecialchars($med['medicine_id']) ?>"
                <?= isset($_GET['medicine_name']) && strtolower(trim($_GET['medicine_name'])) == strtolower(trim($med['medicine_name'])) ? 'selected' : '' ?>>
                <?= htmlspecialchars($med['medicine_name']) ?> (Stock: <?= (int)$med['stock'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

                    <div class="col-md-6">
                        <label for="quantity" class="form-label">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" required class="form-control form-control-sm" min="1" />
                    </div>

                    <div class="col-md-6">
                        <label for="prescribed_by" class="form-label">Prescribed By:</label>
                        <input type="text" id="prescribed_by" name="prescribed_by" class="form-control form-control-sm"
    value="<?= isset($_GET['prescribed_by']) ? htmlspecialchars($_GET['prescribed_by']) : '' ?>" />

                    </div>

                    <div class="col-md-6">
                        <label for="dispensed_by" class="form-label">Dispensed By:</label>
                        <input type="text" id="dispensed_by" name="dispensed_by" class="form-control form-control-sm" />
                    </div>

                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes:</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control form-control-sm"></textarea>
                    </div>
                </div>

                <div class="mt-4 d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Dispense</button>
                    <button type="button" onclick="window.history.back();" class="btn btn-secondary btn-sm">Go Back</button>
                </div>
            </form>
        </div>
    </div>


<!-- SELECT 2 AJAX -->
<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 Initialization (Move here!) -->
<script>
$(document).ready(function () {
    $('#patient_id').select2({
        placeholder: 'Select a patient',
        allowClear: true
    });

    $('#medicine_id').select2({
        placeholder: 'Select a medicine',
        allowClear: true
    });
});
</script>

</body>
</html>
