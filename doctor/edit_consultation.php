<?php
require '../includes/auth.php';
require '../db.php';
// Helper functions to avoid 'undefined function' errors
function selected($array, $value) {
    return (is_array($array) && in_array($value, $array)) ? 'selected' : '';
}

function checked($array, $value) {
    return (is_array($array) && in_array($value, $array)) ? 'checked' : '';
}


if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit();
}

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo "Invalid request.";
    exit;
}

// Fetch the latest consultation for that patient
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE patient_id = ? ORDER BY consultation_date DESC LIMIT 1");
$stmt->execute([$patient_id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consultation) {
    echo "Consultation not found.";
    exit;
}

$consultation_id = $consultation['consultation_id'];
// 🆕 Define default arrays for form prepopulation (especially to avoid undefined variable errors)
$lab_order_arr = $_POST['lab_order'] ?? [];
$procedure_order_arr = $_POST['procedure_order'] ?? [];
$pharmacy_order_arr = $_POST['pharmacy_order'] ?? [''];
$pharmacy_dosage_arr = $_POST['pharmacy_dosage'] ?? [''];
$lab_order_arr = $_POST['lab_order'] ?? [];
$procedure_order_arr = $_POST['procedure_order'] ?? [];
$pharmacy_order_arr = $_POST['pharmacy_order'] ?? [''];
$pharmacy_dosage_arr = $_POST['pharmacy_dosage'] ?? [''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic vitals and notes
    $temperature = $_POST['temperature'] ?? '';
    $pulse = $_POST['pulse'] ?? '';
    $respiratory_rate = $_POST['respiratory_rate'] ?? '';
    $blood_pressure = $_POST['blood_pressure'] ?? '';
    $oxygen_saturation = $_POST['oxygen_saturation'] ?? '';
    $pain_level = $_POST['pain_level'] ?? '';
    $height_cm = $_POST['height_cm'] ?? '';
    $weight_kg = $_POST['weight_kg'] ?? '';
    $bmi = $_POST['bmi'] ?? '';
    $blood_sugar = $_POST['blood_sugar'] ?? '';
    $consciousness_level = $_POST['consciousness_level'] ?? '';
    $vitals_time = $_POST['vitals_time'] ?? '';
    $symptoms_notes = $_POST['symptoms_notes'] ?? '';
    $chief_complaint = $_POST['chief_complaint'] ?? '';
    $physical_exam = $_POST['physical_exam'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $investigations = $_POST['investigations'] ?? '';
    $treatment_plan = $_POST['treatment_plan'] ?? '';
    $doctor_signature = $_POST['doctor_signature'] ?? '';
    $consultation_date = $_POST['consultation_date'] ?? '';

    // Update consultation info
    $update = $pdo->prepare("
        UPDATE consultations SET 
            temperature = ?, pulse = ?, respiratory_rate = ?, blood_pressure = ?, 
            oxygen_saturation = ?, pain_level = ?, height_cm = ?, weight_kg = ?, 
            bmi = ?, blood_sugar = ?, consciousness_level = ?, vitals_time = ?, 
            symptoms_notes = ?, chief_complaint = ?, physical_exam = ?, 
            diagnosis = ?, investigations = ?, treatment_plan = ?, 
            doctor_signature = ?, consultation_date = ?
        WHERE consultation_id = ?
    ");

    $success = $update->execute([
        $temperature, $pulse, $respiratory_rate, $blood_pressure,
        $oxygen_saturation, $pain_level, $height_cm, $weight_kg,
        $bmi, $blood_sugar, $consciousness_level, $vitals_time,
        $symptoms_notes, $chief_complaint, $physical_exam,
        $diagnosis, $investigations, $treatment_plan,
        $doctor_signature, $consultation_date, $consultation_id
    ]);

    // 🔄 Clear old orders created by this user for the patient
    $pdo->prepare("DELETE FROM patient_orders WHERE patient_id = ? AND created_by = ?")
        ->execute([$patient_id, $_SESSION['user']['user_id']]);

    // 🧪 Lab Orders
    foreach ($_POST['lab_order'] ?? [] as $lab_test) {
        $stmt = $pdo->prepare("INSERT INTO patient_orders 
            (patient_id, service_type, details, status, created_by, created_at) 
            VALUES (?, 'Lab', ?, 'Pending', ?, NOW())");
        $stmt->execute([$patient_id, $lab_test, $_SESSION['user']['user_id']]);
    }

    // 🏥 Nursing Procedures
    foreach ($_POST['procedure_order'] ?? [] as $procedure) {
        $stmt = $pdo->prepare("INSERT INTO patient_orders 
            (patient_id, service_type, details, status, created_by, created_at) 
            VALUES (?, 'Procedure', ?, 'Pending', ?, NOW())");
        $stmt->execute([$patient_id, $procedure, $_SESSION['user']['user_id']]);
    }

    // 💊 Pharmacy Orders
    foreach ($_POST['pharmacy_order'] ?? [] as $i => $medicine) {
        if (!empty($medicine)) {
            $dosage = $_POST['pharmacy_dosage'][$i] ?? '';
            $details = $medicine . ' - ' . $dosage;

            $stmt = $pdo->prepare("INSERT INTO patient_orders 
                (patient_id, service_type, details, status, created_by, created_at) 
                VALUES (?, 'Pharmacy', ?, 'Pending', ?, NOW())");
            $stmt->execute([$patient_id, $details, $_SESSION['user']['user_id']]);
        }
    }

    if ($success) {
        echo "<div class='alert alert-success text-center'>✅ Consultation updated successfully.</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>❌ Failed to update consultation.</div>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Consultation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4 mb-5">
    <h3 class="text-center text-primary mb-4">Edit Consultation</h3>
    <form method="POST" class="card p-4 shadow-sm">

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Temperature (°C)</label>
                <input type="number" step="0.1" name="temperature" value="<?= $consultation['temperature'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pulse</label>
                <input type="number" name="pulse" value="<?= $consultation['pulse'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Respiratory Rate</label>
                <input type="number" name="respiratory_rate" value="<?= $consultation['respiratory_rate'] ?>" class="form-control">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Blood Pressure</label>
                <input type="text" name="blood_pressure" value="<?= $consultation['blood_pressure'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Oxygen Saturation (%)</label>
                <input type="number" name="oxygen_saturation" value="<?= $consultation['oxygen_saturation'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pain Level</label>
                <input type="number" name="pain_level" value="<?= $consultation['pain_level'] ?>" class="form-control">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Height (cm)</label>
                <input type="number" name="height_cm" value="<?= $consultation['height_cm'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Weight (kg)</label>
                <input type="number" name="weight_kg" value="<?= $consultation['weight_kg'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">BMI</label>
                <input type="text" name="bmi" value="<?= $consultation['bmi'] ?>" class="form-control">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Blood Sugar</label>
                <input type="number" name="blood_sugar" value="<?= $consultation['blood_sugar'] ?>" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Consciousness Level</label>
                <select name="consciousness_level" class="form-select">
                    <?php foreach (['Alert','Verbal','Pain','Unresponsive'] as $level): ?>
                        <option value="<?= $level ?>" <?= $consultation['consciousness_level'] == $level ? 'selected' : '' ?>><?= $level ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Vitals Time</label>
                <input type="time" name="vitals_time" value="<?= $consultation['vitals_time'] ?>" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Symptoms Notes</label>
            <textarea name="symptoms_notes" class="form-control" rows="3"><?= $consultation['symptoms_notes'] ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Chief Complaint</label>
            <textarea name="chief_complaint" class="form-control"><?= $consultation['chief_complaint'] ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Physical Exam</label>
            <textarea name="physical_exam" class="form-control"><?= $consultation['physical_exam'] ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Diagnosis</label>
            <textarea name="diagnosis" class="form-control"><?= $consultation['diagnosis'] ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Investigations</label>
            <textarea name="investigations" class="form-control"><?= $consultation['investigations'] ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Lab Orders</label>
            <select name="lab_order[]" class="form-select" multiple>
                <?php foreach (['FBC','Urinalysis','Malaria Parasite','Blood Sugar','HIV Test'] as $test): ?>
                    <option value="<?= $test ?>" <?= selected($lab_order_arr, $test) ?>><?= $test ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Nursing Procedures</label><br>
            <?php foreach (['Wound Dressing','IV Fluids','Catheterization'] as $proc): ?>
                <input type="checkbox" name="procedure_order[]" value="<?= $proc ?>" <?= checked($procedure_order_arr, $proc) ?>> <?= $proc ?><br>
            <?php endforeach; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Pharmacy Orders</label>
            <?php foreach ($pharmacy_order_arr as $i => $drug): ?>
                <div class="input-group mb-2">
                    <select name="pharmacy_order[]" class="form-select">
                        <option value="">-- Select Drug --</option>
                        <?php foreach (['Paracetamol','Amoxicillin','Ibuprofen','Ciprofloxacin'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $drug == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="pharmacy_dosage[]" class="form-control" placeholder="e.g. 500mg x3" value="<?= $pharmacy_dosage_arr[$i] ?? '' ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Treatment Plan</label>
            <textarea name="treatment_plan" class="form-control"><?= $consultation['treatment_plan'] ?></textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Doctor Signature</label>
                <input type="text" name="doctor_signature" class="form-control" value="<?= $consultation['doctor_signature'] ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Date</label>
                <input type="date" name="consultation_date" class="form-control" value="<?= $consultation['consultation_date'] ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100">📝 Update Consultation</button>
    </form>
</div>
</body>
</html>
