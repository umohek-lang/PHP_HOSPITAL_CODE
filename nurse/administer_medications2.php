<?php
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

require '../includes/auth.php';
require '../db.php';

$message = '';

// Handle deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prescription_id'])) {
    $delete_id = (int)$_POST['delete_prescription_id'];
    $deleteStmt = $pdo->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
    if ($deleteStmt->execute([$delete_id])) {
        $message = '<div class="alert alert-success">Prescription deleted successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to delete prescription.</div>';
    }
}

// Handle form submission for administering medication (MULTIPLE MEDICINES)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['patient_id'], $_POST['medicine_ids'])) {
    $patient_id = $_POST['patient_id'];
    $medicine_ids = $_POST['medicine_ids'];

    foreach ($medicine_ids as $medicine_id) {
        $checkStmt = $pdo->prepare("
            SELECT * FROM prescriptions 
            WHERE patient_id = ? AND medicine_id = ? 
            AND (notes IS NULL OR notes NOT LIKE 'Administered on%')
        ");
        $checkStmt->execute([$patient_id, $medicine_id]);
        $prescription = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($prescription) {
            $note = "Administered on " . date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("
                UPDATE prescriptions 
                SET notes = ?, prescription_date = NOW(), created_at = NOW() 
                WHERE prescription_id = ?
            ");
            $stmt->execute([$note, $prescription['prescription_id']]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO prescriptions (patient_id, medicine_id, notes, prescription_date, created_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$patient_id, $medicine_id, "Administered on " . date('Y-m-d H:i:s')]);
        }
    }

    $message = '<div class="alert alert-success">✅ Medication(s) saved/administered successfully.</div>';
}

// ✅ Fetch all patients
$patients = $pdo->query("
    SELECT patient_id, full_name 
    FROM patients
    ORDER BY full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch all medicines
$medicines = $pdo->query("
    SELECT medicine_id, medicine_name 
    FROM medicines
    ORDER BY medicine_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch administered prescriptions
$rows = $pdo->query("
    SELECT p.prescription_id, pt.patient_id, pt.full_name AS patient_name, 
           m.medicine_name, p.notes, p.prescription_date 
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.patient_id
    JOIN medicines m ON p.medicine_id = m.medicine_id
    WHERE p.notes LIKE 'Administered on%'
    ORDER BY pt.full_name ASC, p.prescription_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Group by patient
$administered = [];
foreach ($rows as $row) {
    $pid = $row['patient_id'];
    if (!isset($administered[$pid])) {
        $administered[$pid] = [
            'patient_name' => $row['patient_name'],
            'medicines' => []
        ];
    }
    $administered[$pid]['medicines'][] = [
        'medicine_name' => $row['medicine_name'],
        'notes' => $row['notes'],
        'date' => $row['prescription_date'],
        'prescription_id' => $row['prescription_id']
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Administer Medication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background-color: #add8e6; }
        .select2-container .select2-selection--single,
        .select2-container .select2-selection--multiple {
            min-height: 38px !important;
            padding: 5px 10px;
        }
    
.select2-results__option {
  padding-left: 25px;
  position: relative;
}
.select2-results__option::before {
  content: "";
  position: absolute;
  left: 6px;
  top: 8px;
  height: 12px;
  width: 12px;
  border: 1px solid #999;
  border-radius: 2px;
  background-color: #fff;
}
.select2-results__option[aria-selected=true]::before {
  content: "✔";
  color: green;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>

<script>
$(document).ready(function() {
  $('#medicine_ids').select2({
    width: '100%',
    placeholder: 'Select Medicine(s)',
    closeOnSelect: false,
  });
});
</script>
    
    
</head>
<body class="container mt-5">
    <h3 class="text-center mb-4">Administer Medication</h3>

    <?php if ($message) echo $message; ?>

    <form method="POST" class="mx-auto mb-5" style="max-width: 600px;">
        <!-- Patient Dropdown -->
        <div class="mb-3">
            <label for="patient_id" class="form-label">Select Patient</label>
            <select name="patient_id" id="patient_id" class="form-select" required>
                <option value="">-- Choose Patient --</option>
                <?php foreach ($patients as $p): ?>
                    <option value="<?= h($p['patient_id']) ?>"><?= h($p['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Medicine Dropdown (MULTIPLE) -->
        <!-- Medicine Dropdown -->
<div class="mb-3">
  <label for="medicine_ids" class="form-label">Select Medicines</label>
  <select name="medicine_ids[]" id="medicine_ids" class="form-select" multiple required>
    <?php foreach ($medicines as $m): ?>
      <option value="<?= h($m['medicine_id']) ?>"><?= h($m['medicine_name']) ?></option>
    <?php endforeach; ?>
  </select>
  <small class="text-muted">💊 You can select multiple medicines (checkbox-style).</small>
</div>

        <button type="submit" class="btn btn-primary w-100">💾 Save / Administer Medication</button>
    </form>

    <h4 class="mb-3">Administered Medications by Patient</h4>

    <?php if (empty($administered)): ?>
        <div class="alert alert-info">No medications have been administered yet.</div>
    <?php else: ?>
    <table id="administeredTable" class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Medications</th>
                <th>Print</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; foreach ($administered as $pid => $data): ?>
                <tr data-patient="<?= h($pid) ?>" data-patient-name="<?= h($data['patient_name']) ?>">
                    <td><?= $i++ ?></td>
                    <td><?= h($data['patient_name']) ?></td>
                    <td>
                        <ul class="mb-0">
                            <?php foreach ($data['medicines'] as $med): ?>
                                <li>
                                    <?= h($med['medicine_name']) ?> 
                                    <small class="text-muted">(<?= h($med['notes']) ?>, <?= h($med['date']) ?>)</small>
                                    <!-- delete each prescription -->
                                    <form method="POST" style="display:inline-block" onsubmit="return confirm('Delete this prescription?');">
                                        <input type="hidden" name="delete_prescription_id" value="<?= $med['prescription_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger ms-1">🗑️</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="printAll(this)">🖨️ Print Patient Report</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            $('#administeredTable').DataTable();
            $('#patient_id, #medicine_ids').select2({ width: '100%' });
        });

        function printAll(button) {
            const row = button.closest('tr');
            const patientName = row.getAttribute('data-patient-name');
            const meds = row.querySelectorAll('ul li');

            const popup = window.open('', '', 'width=900,height=700');
            popup.document.write('<html><head><title>Print Report</title>');
            popup.document.write('<style>body{font-family:Arial;margin:40px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #000;padding:8px;}</style>');
            popup.document.write('</head><body>');
            popup.document.write('<h3>Medication Report - ' + patientName + '</h3>');
            popup.document.write('<table><thead><tr><th>Medicine</th><th>Notes</th><th>Date</th></tr></thead><tbody>');

            meds.forEach(li => {
                const text = li.innerText.replace('🗑️', '').trim();
                const parts = text.match(/^(.*?) \((.*?)\, (.*?)\)$/); 
                if (parts) {
                    popup.document.write('<tr><td>' + parts[1] + '</td><td>' + parts[2] + '</td><td>' + parts[3] + '</td></tr>');
                }
            });

            popup.document.write('</tbody></table></body></html>');
            popup.document.close();
            popup.print();
        }
    </script>
</body>
</html>
