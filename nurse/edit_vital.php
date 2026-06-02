<?php    
error_reporting(E_ALL);  
ini_set('display_errors',1);  
require '../db.php';

$message = "";
$vs_id = $_GET['vs_id'] ?? null;

/* =========================
   FETCH RECORD FOR EDIT
========================= */
$record = null;
if($vs_id){
    $stmt = $pdo->prepare("SELECT * FROM vital_signs WHERE id=?");
    $stmt->execute([$vs_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   HANDLE FORM SUBMISSION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['patient_id'])) {
        $message = "Please select a patient.";
    } elseif (empty($_POST['recorded_by'])) {
        $message = "Please select the nurse.";
    } else {

        if (isset($_POST['action']) && $_POST['action'] === 'edit') {

            $id = $_POST['vs_id'];

            $sql = "UPDATE vital_signs SET
                patient_id=:patient_id,
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
                ':symptoms_notes' => $_POST['symptoms_notes'] ?: null,
                ':id' => $id
            ]);

            $message = "Vital signs record updated successfully!";
        }
    }
}

/* =========================
   FETCH DATA FOR DROPDOWNS
========================= */
$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name")->fetchAll();
$nurses = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 3 ORDER BY full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $record?'Edit':'Record' ?> Vital Signs</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body class="bg-light">
<div class="container mt-5">

<h2 class="mb-4 text-center"><?= $record?'Edit':'Record' ?> Vital Signs</h2>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" class="bg-white p-4 rounded shadow-sm mb-5" id="vitalForm">

<input type="hidden" name="action" value="<?= $record?'edit':'insert' ?>">
<?php if($record): ?>
<input type="hidden" name="vs_id" value="<?= $record['id'] ?>">
<?php endif; ?>

<!-- STEP 1 -->
<fieldset class="form-step" id="step1">
<legend>Step 1: Patient Info</legend>

<div class="mb-3">
<label class="form-label">Select Patient</label>
<select name="patient_id" id="patient_id" class="form-select" required>
<?php if($record): ?>
<option value="<?= $record['patient_id'] ?>" selected><?= htmlspecialchars($record['patient_id']) ?></option>
<?php endif; ?>
</select>
</div>

<div class="mb-3">
<label class="form-label">Recorded By (Nurse)</label>
<select name="recorded_by" class="form-select" required>
<option value="" disabled>Select Nurse</option>
<?php foreach ($nurses as $nurse): ?>
<option value="<?= $nurse['user_id'] ?>" <?= ($record && $record['recorded_by']==$nurse['user_id'])?'selected':'' ?>>
<?= htmlspecialchars($nurse['full_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<button type="button" class="btn btn-primary float-end" onclick="nextStep(2)">Next</button>
</fieldset>

<!-- STEP 2 -->
<fieldset class="form-step d-none" id="step2">
<legend>Step 2: Vital Signs</legend>
<input type="number" step="0.1" name="temperature" class="form-control mb-2" placeholder="Temperature" required value="<?= $record['temperature']??'' ?>">
<input type="number" name="pulse_rate" class="form-control mb-2" placeholder="Pulse Rate" required value="<?= $record['pulse_rate']??'' ?>">
<input type="number" name="respiration_rate" class="form-control mb-2" placeholder="Respiration Rate" required value="<?= $record['respiration_rate']??'' ?>">
<input type="text" name="blood_pressure" class="form-control mb-2" placeholder="Blood Pressure" required value="<?= $record['blood_pressure']??'' ?>">
<input type="number" name="oxygen_saturation" class="form-control mb-2" placeholder="Oxygen Saturation" required value="<?= $record['oxygen_saturation']??'' ?>">
<input type="number" name="pain_level" class="form-control mb-2" placeholder="Pain Level" required value="<?= $record['pain_level']??'' ?>">

<button type="button" class="btn btn-secondary" onclick="nextStep(1)">Back</button>
<button type="button" class="btn btn-primary float-end" onclick="nextStep(3)">Next</button>
</fieldset>

<!-- STEP 3 -->
<fieldset class="form-step d-none" id="step3">
<legend>Step 3: Measurements</legend>
<input type="number" name="height_cm" id="height_cm" class="form-control mb-2" placeholder="Height (cm)" value="<?= $record['height_cm']??'' ?>">
<input type="number" name="weight_kg" id="weight_kg" class="form-control mb-2" placeholder="Weight (kg)" value="<?= $record['weight_kg']??'' ?>">
<input type="text" name="bmi" id="bmi" class="form-control mb-2" readonly placeholder="BMI" value="<?= $record['bmi']??'' ?>">

<button type="button" class="btn btn-secondary" onclick="nextStep(2)">Back</button>
<button type="button" class="btn btn-primary float-end" onclick="nextStep(4)">Next</button>
</fieldset>

<!-- STEP 4 -->
<fieldset class="form-step d-none" id="step4">
<legend>Step 4: Additional Observations</legend>

<input type="number" name="blood_sugar" class="form-control mb-2" placeholder="Blood Sugar" value="<?= $record['blood_sugar']??'' ?>">
<select name="consciousness_level" class="form-select mb-2">
<option value="">Select Consciousness</option>
<option value="Alert" <?= ($record['consciousness_level']??'')=='Alert'?'selected':'' ?>>Alert</option>
<option value="Verbal" <?= ($record['consciousness_level']??'')=='Verbal'?'selected':'' ?>>Verbal</option>
<option value="Pain" <?= ($record['consciousness_level']??'')=='Pain'?'selected':'' ?>>Pain</option>
<option value="Unresponsive" <?= ($record['consciousness_level']??'')=='Unresponsive'?'selected':'' ?>>Unresponsive</option>
</select>

<label>Time</label>
<input type="time" name="vitals_time" class="form-control mb-2" value="<?= $record['vitals_time']??'' ?>">
<label>Symptom Notes</label>
<textarea name="symptoms_notes" class="form-control mb-2"><?= $record['symptoms_notes']??'' ?></textarea>

<button type="button" class="btn btn-secondary" onclick="nextStep(3)">Back</button>
<button type="submit" class="btn btn-success float-end">Update Vital Signs</button>
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

// Pre-fill select2 if editing
<?php if($record): ?>
var patientOption = new Option("<?= htmlspecialchars($record['patient_id']) ?>", <?= $record['patient_id'] ?>, true, true);
$('#patient_id').append(patientOption).trigger('change');
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
