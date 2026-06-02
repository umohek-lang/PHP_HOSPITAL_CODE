<?php
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

require '../includes/auth.php';
require '../db.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';

/* ======================================================
   ✅ AJAX Handlers (Patients, Medicines, Treatments)
====================================================== */
if (isset($_GET['ajax'])) {
    $term = $_GET['term'] ?? '';

    if ($_GET['ajax'] === 'patients') {
        $stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE full_name LIKE ? OR patient_id LIKE ? LIMIT 20");
        $stmt->execute(["%$term%", "%$term%"]);
        $data = [];
        while ($row = $stmt->fetch()) {
            $data[] = [
                'id' => $row['patient_id'],
                'text' => $row['full_name'] . " (ID: {$row['patient_id']})"
            ];
        }
        echo json_encode(['results' => $data]);
        exit;
    }

    if ($_GET['ajax'] === 'medicines') {
        $stmt = $pdo->prepare("SELECT medicine_id, medicine_name FROM medicines WHERE medicine_name LIKE ? LIMIT 20");
        $stmt->execute(["%$term%"]);
        $data = [];
        while ($row = $stmt->fetch()) {
            $data[] = [
                'id' => $row['medicine_id'],
                'text' => $row['medicine_name']
            ];
        }
        echo json_encode(['results' => $data]);
        exit;
    }

    if ($_GET['ajax'] === 'treatments') {
        $stmt = $pdo->prepare("SELECT DISTINCT treatment_name FROM treatments WHERE treatment_name LIKE ? LIMIT 20");
        $stmt->execute(["%$term%"]);
        $data = [];
        while ($row = $stmt->fetch()) {
            $data[] = [
                'id' => $row['treatment_name'],
                'text' => $row['treatment_name']
            ];
        }
        echo json_encode(['results' => $data]);
        exit;
    }
}

/* ======================================================
   ✅ Handle Treatment Form Submission (Multiple Insert)
====================================================== */
$selectedPatient = $_GET['patient_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['patient_id'])) {
    $patientId = (int)$_POST['patient_id'];
    $medicines = $_POST['medicine_id'] ?? [];
    $treatments = $_POST['treatment_name'] ?? [];
    $notes = trim($_POST['notes'] ?? '');

    $stmt = $pdo->prepare("
        INSERT INTO treatments (patient_id, medicine_id, treatment_name, notes, treatment_date, created_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");

    $insertedCount = 0;

    foreach ($treatments as $treat) {
        if (empty($medicines)) {
            $stmt->execute([$patientId, null, trim($treat), $notes]);
            $insertedCount++;
        } else {
            foreach ($medicines as $m) {
                $stmt->execute([$patientId, (int)$m, trim($treat), $notes]);
                $insertedCount++;

                // Update injection record
                $updateStmt = $pdo->prepare("
                    UPDATE injections 
                    SET notes = ?, injection_date = NOW(), created_at = NOW() 
                    WHERE patient_id = ? AND medicine_id = ? 
                    AND (notes IS NULL OR notes = '' OR notes NOT LIKE 'Administered on%') 
                    LIMIT 1
                ");
                $adminNote = "Administered on " . date('Y-m-d H:i:s');
                $updateStmt->execute([$adminNote, $patientId, (int)$m]);
            }
        }
    }

    if ($insertedCount > 0) {
        $message = '<div class="alert alert-success">' . $insertedCount . ' treatment(s) prescribed successfully.</div>';
    } else {
        $message = '<div class="alert alert-warning">No treatments were inserted.</div>';
    }

    // Keep patient selected after POST
    $selectedPatient = $patientId;
}

/* ======================================================
   ✅ Fetch Recently Administered Treatments
====================================================== */
$sql = "
    SELECT 
        t.treatment_id, 
        pt.full_name AS patient_name, 
        m.medicine_name, 
        t.treatment_name, 
        t.notes, 
        t.treatment_date
    FROM treatments t
    JOIN patients pt ON t.patient_id = pt.patient_id
    LEFT JOIN medicines m ON t.medicine_id = m.medicine_id
";

if ($selectedPatient) {
    $stmt = $pdo->prepare($sql . " WHERE t.patient_id = ? ORDER BY t.treatment_date DESC");
    $stmt->execute([$selectedPatient]);
    $administered = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $administered = $pdo->query($sql . " ORDER BY t.treatment_date DESC")->fetchAll(PDO::FETCH_ASSOC);
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
    </style>
</head>
<body class="container mt-5">

    <h3 class="mb-4 text-center">Prescribe Treatment</h3>

    <?= $message ?>

    <form method="POST" class="mx-auto" style="max-width: 500px;">
        <div class="mb-3">
            <label for="patient_id" class="form-label">Select Patient</label>
            <select name="patient_id" id="patient_id" class="form-select" required></select>
        </div>

        <div class="mb-3">
            <label for="medicine_id" class="form-label">Select Medicines</label>
            <select name="medicine_id[]" id="medicine_id" class="form-select" multiple></select>
        </div>

        <div class="mb-3">
            <label for="treatment_name" class="form-label">Treatment Names</label>
            <select name="treatment_name[]" id="treatment_name" class="form-select" multiple></select>
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Notes / Dosage Instructions (optional)</label>
            <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="E.g. 2ml daily for 3 days"></textarea>
        </div>

        <button type="submit" class="btn btn-success w-100">Prescribe Treatment</button>
    </form>

    <h4 class="mt-5 mb-3">Recently Administered Treatments</h4>

    <?php if (count($administered) === 0): ?>
        <div class="alert alert-info">No treatments have been administered yet.</div>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Patient</th>
                    <th>Medicine</th>
                    <th>Treatment Name</th>
                    <th>Notes</th>
                    <th>Date Administered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($administered as $index => $row): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= h($row['patient_name']) ?></td>
                        <td><?= h($row['medicine_name'] ?? '—') ?></td>
                        <td><?= h($row['treatment_name']) ?></td>
                        <td><?= h($row['notes']) ?></td>
                        <td><?= h($row['treatment_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Single patient select
        $('#patient_id').select2({
            placeholder: '-- Choose Patient --',
            allowClear: true,
            ajax: {
                url: '?ajax=patients',
                dataType: 'json',
                delay: 250,
                data: params => ({ term: params.term }),
                processResults: data => data
            }
        });

        // Preselect patient if GET parameter exists
        <?php if ($selectedPatient): ?>
        $.ajax({
            url: '?ajax=patients&term=',
            dataType: 'json'
        }).done(function(data) {
            const option = data.results.find(p => p.id == <?= $selectedPatient ?>);
            if (option) {
                const newOption = new Option(option.text, option.id, true, true);
                $('#patient_id').append(newOption).trigger('change');
            }
        });
        <?php endif; ?>

        $('#medicine_id').select2({
            placeholder: '-- Choose Medicines --',
            multiple: true,
            closeOnSelect: false,
            ajax: {
                url: '?ajax=medicines',
                dataType: 'json',
                delay: 250,
                data: params => ({ term: params.term }),
                processResults: data => data
            }
        });

        $('#treatment_name').select2({
            placeholder: '-- Choose or Type Treatment Name --',
            multiple: true,
            closeOnSelect: false,
            tags: true,
            ajax: {
                url: '?ajax=treatments',
                dataType: 'json',
                delay: 250,
                data: params => ({ term: params.term }),
                processResults: data => data
            }
        });
    });
    </script>

</body>
</html>
