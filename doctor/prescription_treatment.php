<?php
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

require '../includes/auth.php';
require '../db.php';

header('Content-Type: text/html; charset=UTF-8');

// Handle AJAX search for patients
if (isset($_GET['search']) && $_GET['search'] === 'patient') {
    $term = $_GET['q'] ?? '';
    $stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE full_name LIKE ? LIMIT 20");
    $stmt->execute(["%$term%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Handle AJAX search for medicines
if (isset($_GET['search']) && $_GET['search'] === 'medicine') {
    $term = $_GET['q'] ?? '';
    $stmt = $pdo->prepare("SELECT medicine_id, medicine_name FROM medicines WHERE medicine_name LIKE ? LIMIT 20");
    $stmt->execute(["%$term%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Handle treatment form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'], $_POST['medicine_id'], $_POST['treatment_name'])) {
    $patient_id = (int)$_POST['patient_id'];
    $medicine_id = (int)$_POST['medicine_id'];
    $treatment_name = trim($_POST['treatment_name']);
    $notes = trim($_POST['notes'] ?? '');
    $treatment_date = $_POST['treatment_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        INSERT INTO treatments (treatment_name, patient_id, medicine_id, notes, treatment_date, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    if ($stmt->execute([$treatment_name, $patient_id, $medicine_id, $notes, $treatment_date])) {
        $message = '<div class="alert alert-success">Treatment prescribed successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to prescribe treatment.</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prescribe Treatment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background-color: #e9f7ef; }
        .select2-container { width: 100% !important; }
    </style>
</head>
<body class="container mt-5">
    <h3 class="mb-4 text-center">Prescribe Treatment</h3>

    <?= $message ?>

    <form method="POST" class="mx-auto" style="max-width: 500px;">
        <div class="mb-3">
            <label for="treatment_name" class="form-label">Treatment Type</label>
            <select name="treatment_name" id="treatment_name" class="form-select" required>
                <option value="">-- Choose Treatment Type --</option>
                <option value="Injection">Injection</option>
                <option value="IV Drip">IV Drip</option>
                <option value="Tablet">Tablet</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="patient_id" class="form-label">Select Patient</label>
            <select name="patient_id" id="patient_id" class="form-select" required></select>
        </div>

        <div class="mb-3">
            <label for="medicine_id" class="form-label">Select Medicine</label>
            <select name="medicine_id" id="medicine_id" class="form-select" required></select>
        </div>

        <div class="mb-3">
            <label for="treatment_date" class="form-label">Treatment Date</label>
            <input type="date" name="treatment_date" id="treatment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Notes / Dosage Instructions (optional)</label>
            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="E.g. 2ml daily for 3 days"></textarea>
        </div>

        <button type="submit" class="btn btn-success w-100">Prescribe Treatment</button>
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#treatment_name').select2({
                placeholder: "Choose Treatment Type"
            });

            $('#patient_id').select2({
                placeholder: "Search Patient...",
                ajax: {
                    url: '?search=patient',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function(item) {
                                return { id: item.patient_id, text: item.full_name };
                            })
                        };
                    },
                    cache: true
                }
            });

            $('#medicine_id').select2({
                placeholder: "Search Medicine...",
                ajax: {
                    url: '?search=medicine',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function(item) {
                                return { id: item.medicine_id, text: item.medicine_name };
                            })
                        };
                    },
                    cache: true
                }
            });
        });
    </script>
</body>
</html>
