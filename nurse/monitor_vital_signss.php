<?php    
error_reporting(E_ALL);  
ini_set('display_errors',1);  
require '../db.php';

$message = "";

/* =========================
   HANDLE FORM SUBMISSION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* REQUIRED SAFETY CHECKS */
    if (empty($_POST['patient_id'])) {
        $message = "Please select a patient.";
    } elseif (empty($_POST['recorded_by'])) {
        $message = "Please select the nurse.";
    } else {

        if (isset($_POST['action']) && $_POST['action'] === 'edit') {

            $id = $_POST['vs_id'];

            $sql = "UPDATE vital_signs SET
                temperature=:temperature,
                pulse_rate=:pulse_rate,
                respiration_rate=:respiration_rate,
                blood_pressure=:blood_pressure,
                oxygen_saturation=:oxygen_saturation,
                pain_level=:pain_level,
                height_cm=:height_cm,
                weight_kg=:weight_kg,
                bmi=:bmi,
                recorded_by=:recorded_by,
                blood_sugar=:blood_sugar,
                consciousness_level=:consciousness_level,
                vitals_time=:vitals_time,
                symptoms_notes=:symptoms_notes
                WHERE id=:id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':temperature' => $_POST['temperature'],
                ':pulse_rate' => $_POST['pulse_rate'],
                ':respiration_rate' => $_POST['respiration_rate'],
                ':blood_pressure' => $_POST['blood_pressure'],
                ':oxygen_saturation' => $_POST['oxygen_saturation'],
                ':pain_level' => $_POST['pain_level'],
                ':height_cm' => $_POST['height_cm'] ?: null,
                ':weight_kg' => $_POST['weight_kg'] ?: null,
                ':bmi' => $_POST['bmi'] ?: null,
                ':recorded_by' => $_POST['recorded_by'],
                ':blood_sugar' => $_POST['blood_sugar'] ?: null,
                ':consciousness_level' => $_POST['consciousness_level'] ?: null,
                ':vitals_time' => $_POST['vitals_time'] ?: null,
                ':symptoms_notes' => $_POST['symptoms_notes'] ?: null,
                ':id' => $id
            ]);

            $message = "Vital signs record updated successfully!";

        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {

            $stmt = $pdo->prepare("DELETE FROM vital_signs WHERE id=?");
            $stmt->execute([$_POST['vs_id']]);
            $message = "Vital signs record deleted successfully!";

        } else {

            /* ===== INSERT (MAIN FIX) ===== */
            $sql = "INSERT INTO vital_signs (
                patient_id, temperature, pulse_rate, respiration_rate,
                blood_pressure, oxygen_saturation, pain_level,
                height_cm, weight_kg, bmi,
                recorded_by, blood_sugar, consciousness_level,
                vitals_time, symptoms_notes
            ) VALUES (
                :patient_id, :temperature, :pulse_rate, :respiration_rate,
                :blood_pressure, :oxygen_saturation, :pain_level,
                :height_cm, :weight_kg, :bmi,
                :recorded_by, :blood_sugar, :consciousness_level,
                :vitals_time, :symptoms_notes
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':patient_id' => $_POST['patient_id'],
                ':temperature' => $_POST['temperature'],
                ':pulse_rate' => $_POST['pulse_rate'],
                ':respiration_rate' => $_POST['respiration_rate'],
                ':blood_pressure' => $_POST['blood_pressure'],
                ':oxygen_saturation' => $_POST['oxygen_saturation'],
                ':pain_level' => $_POST['pain_level'],
                ':height_cm' => $_POST['height_cm'] ?: null,
                ':weight_kg' => $_POST['weight_kg'] ?: null,
                ':bmi' => $_POST['bmi'] ?: null,
                ':recorded_by' => $_POST['recorded_by'],
                ':blood_sugar' => $_POST['blood_sugar'] ?: null,
                ':consciousness_level' => $_POST['consciousness_level'] ?: null,
                ':vitals_time' => $_POST['vitals_time'] ?: null,
                ':symptoms_notes' => $_POST['symptoms_notes'] ?: null
            ]);

            $message = "Vital signs recorded successfully!";
        }
    }
}

/* =========================
   FETCH DATA
========================= */
$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name")->fetchAll();

$nurseStmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role_id = 3 ORDER BY full_name");
$nurseStmt->execute();
$nurses = $nurseStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Record Vital Signs</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body class="bg-light">
<div class="container mt-5">

<h2 class="mb-4 text-center">Record Vital Signs</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white p-4 rounded shadow-sm mb-5" id="vitalForm">

<!-- STEP 1 -->
<fieldset class="form-step" id="step1">
<legend>Step 1: Patient Info</legend>

<div class="mb-3">
<label class="form-label">Select Patient</label>
<select name="patient_id" id="patient_id" class="form-select" required></select>
</div>

<div class="mb-3">
<label class="form-label">Recorded By (Nurse)</label>
<select name="recorded_by" class="form-select" required>
<option value="" disabled selected>Select Nurse</option>
<?php foreach ($nurses as $nurse): ?>
<option value="<?= $nurse['user_id'] ?>"><?= htmlspecialchars($nurse['full_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<button type="button" class="btn btn-primary float-end" onclick="nextStep(2)">Next</button>
</fieldset>

<!-- STEP 2 -->
<fieldset class="form-step d-none" id="step2">
<legend>Step 2: Vital Signs</legend>
<input type="number" step="0.1" name="temperature" class="form-control mb-2" placeholder="Temperature" required>
<input type="number" name="pulse_rate" class="form-control mb-2" placeholder="Pulse Rate" required>
<input type="number" name="respiration_rate" class="form-control mb-2" placeholder="Respiration Rate" required>
<input type="text" name="blood_pressure" class="form-control mb-2" placeholder="Blood Pressure" required>
<input type="number" name="oxygen_saturation" class="form-control mb-2" placeholder="Oxygen Saturation" required>
<input type="number" name="pain_level" class="form-control mb-2" placeholder="Pain Level" required>

<button type="button" class="btn btn-secondary" onclick="nextStep(1)">Back</button>
<button type="button" class="btn btn-primary float-end" onclick="nextStep(3)">Next</button>
</fieldset>

<!-- STEP 3 -->
<fieldset class="form-step d-none" id="step3">
<legend>Step 3: Measurements</legend>
<input type="number" name="height_cm" id="height_cm" class="form-control mb-2" placeholder="Height (cm)">
<input type="number" name="weight_kg" id="weight_kg" class="form-control mb-2" placeholder="Weight (kg)">
<input type="text" name="bmi" id="bmi" class="form-control mb-2" readonly placeholder="BMI">

<button type="button" class="btn btn-secondary" onclick="nextStep(2)">Back</button>
<button type="button" class="btn btn-primary float-end" onclick="nextStep(4)">Next</button>
</fieldset>

<!-- STEP 4 -->
<fieldset class="form-step d-none" id="step4">
<legend>Step 4: Additional Observations</legend>

<input type="number" name="blood_sugar" class="form-control mb-2" placeholder="Blood Sugar">
<select name="consciousness_level" class="form-select mb-2">
<option value="">Select Consciousness</option>
<option value="Alert">Alert</option>
<option value="Verbal">Verbal</option>
<option value="Pain">Pain</option>
<option value="Unresponsive">Unresponsive</option>
</select>

<label>time</label>
<input type="time" name="vitals_time" class="form-control mb-2" placeholder = "time taken">
<label>symptom notes</label>
<textarea name="symptoms_notes" class="form-control mb-2"></textarea>

<button type="button" class="btn btn-secondary" onclick="nextStep(3)">Back</button>
<button type="submit" class="btn btn-success float-end">Submit Vital Signs</button>
</fieldset>

</form>
</div>

<script>
function nextStep(step){
    document.querySelectorAll('.form-step').forEach(s=>s.classList.add('d-none'));
    document.getElementById('step'+step).classList.remove('d-none');
}

function calculateBMI(){
    let w = weight_kg.value, h = height_cm.value;
    if(w && h) bmi.value = (w / ((h/100)**2)).toFixed(1);
}

height_cm.oninput = calculateBMI;
weight_kg.oninput = calculateBMI;
</script>

<script>
$('#patient_id').select2({
    placeholder: '-- Choose Patient --',
    ajax:{
        url:'search_patients.php',
        dataType:'json',
        delay:250,
        data:params=>({term:params.term}),
        processResults:data=>({results:data.results})
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>