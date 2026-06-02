<?php
// Include DB connection
require '../db.php';

// Fetch patients
$patients = [];
try {
$stmt = $pdo->query("SELECT patient_id, full_name, patient_pin, photo, dob, age, gender, address, phone, marital_status FROM patients");

  $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical History Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- In <head> -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  
  <style>
    .form-step { display: none; }
    .form-step.active { display: block; }
  </style>
</head>
<body>
<div class="container mt-4 mb-5">
  <form id="medicalForm" method="post" action="save_medical_form.php">
    <!-- Step 1: Identifying Information -->
    <fieldset class="form-step active">
      <legend>1. Personal Information</legend>
      <div class="row">
      <!-- Full Name (Populated from DB) -->


 <!-- Full Name (AJAX Select2) -->

<div class="col-md-6 mb-3">
  <label for="full_name">Full Name</label>
  <select class="form-select" id="full_name" name="full_name" style="width: 100%;"></select>
</div>

<!-- Patient ID / Hospital Number (AJAX Select2) -->
<div class="col-md-6 mb-3">
  <label for="patient_id">Patient ID / Hospital Number</label>
  <select class="form-select" id="patient_id" name="patient_id" style="width: 100%;"></select>
</div>

<div class="col-md-4 mb-3">
  <label>Patient Photo</label><br>
  <img id="patient_photo" src="" alt="No photo" class="img-thumbnail" width="150" height="150">
  <input type="hidden" id="photo" name="photo">
</div>


        <div class="col-md-4 mb-3">
          <label>Date of Birth</label>
          <input type="date" class="form-control" name="dob" autocomplete="bday">
        </div>
        <div class="col-md-2 mb-3">
          <label>Age</label>
          <input type="number" class="form-control" name="age">
        </div>
        <div class="col-md-6 mb-3">
          <label>Gender</label><br>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="gender" value="Male">
            <label class="form-check-label">Male</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="gender" value="Female">
            <label class="form-check-label">Female</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="gender" value="Other">
            <label class="form-check-label">Other</label>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <label>Marital Status</label>
          <select class="form-select" name="marital_status">
            <option>Single</option>
            <option>Married</option>
            <option>Widowed</option>
            <option>Divorced</option>
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label>Occupation</label>
          <input type="text" class="form-control" name="occupation" autocomplete="organization-title">
        </div>
        <div class="col-md-4 mb-3">
          <label>Address</label>
          <input type="text" class="form-control" name="address" autocomplete="street-address">
        </div>
        <div class="col-md-4 mb-3">
          <label>Phone Number</label>
          <input type="tel" class="form-control" name="phone" autocomplete="tel">
        </div>
        <div class="col-md-4 mb-3">
          <label>Date of Visit</label>
          <input type="date" class="form-control" name="visit_date">
        </div>
      </div>
      <button type="button" class="btn btn-primary next-step">Next</button>
    </fieldset>

    <!-- Step 2: Chief Complaint, HPI, PMH -->
<fieldset class="form-step">
  <legend>2. Chief Complaint</legend>
  <div class="mb-3">
    <textarea class="form-control" name="chief_complaint" rows="2" placeholder="E.g., Chest pain for 2 days"></textarea>
  </div>
  <legend>3. History of Present Illness (HPI)</legend>
  <div class="mb-3">
    <textarea class="form-control" name="hpi" rows="4" placeholder="Include onset, duration, severity, etc."></textarea>
  </div>
  <legend>4. Past Medical History (PMH)</legend>
  <div class="row">
    <div class="col-md-6">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="Hypertension"><label class="form-check-label">Hypertension</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="Diabetes Mellitus"><label class="form-check-label">Diabetes Mellitus</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="Asthma/COPD"><label class="form-check-label">Asthma/COPD</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="Tuberculosis"><label class="form-check-label">Tuberculosis</label></div>
    </div>
    <div class="col-md-6">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="HIV/AIDS"><label class="form-check-label">HIV/AIDS</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="Cardiac Disease"><label class="form-check-label">Cardiac Disease</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="Stroke"><label class="form-check-label">Stroke</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="pmh[]" value="Psychiatric Illness" autocomplete="on"><label class="form-check-label">Psychiatric Illness</label></div>
    </div>
    <div class="col-md-12 mt-2">
      <label>Other (Specify)</label>
      <input type="text" class="form-control" name="pmh_other" autocomplete="on">
    </div>
  </div>
<!-- PAST SURGICAL HISTORY -->
<legend>Past Surgical History</legend>
      <div id="surgery-entries">
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Surgery/Procedure</label>
            <input type="text" name="surgery_name[]" class="form-control" autocomplete="on">
          </div>
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="surgery_date[]" class="form-control" autocomplete="on">
          </div>
          <div class="col-md-4">
            <label class="form-label">Complications</label>
            <input type="text" name="surgery_complications[]" class="form-control" autocomplete="on">
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary mb-3" onclick="addSurgeryRow()">Add More</button>
      <div class="d-flex justify-content-end">
        <!-- <button type="button" class="btn btn-primary next-step">Next</button> -->
      </div>

  <div class="d-flex justify-content-between mt-4">
    <button type="button" class="btn btn-secondary prev-step">Previous</button>
    <button type="button" class="btn btn-primary next-step">Next</button>
  </div>
</fieldset>

<!-- Step 3: Past Surgical History & Medications -->

<!-- <fieldset class="form-step active">
      
    </fieldset> -->

    <!-- Medications Section -->
    <fieldset class="form-step">
      <legend>Current Medications</legend>
      <div id="medication-entries">
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label">Drug Name</label>
            <input type="text" name="med_name[]" class="form-control" autocomplete="on">
          </div>
          <div class="col-md-2">
            <label class="form-label">Dosage</label>
            <input type="text" name="med_dosage[]" class="form-control" autocomplete="on">
          </div>
          <div class="col-md-2">
            <label class="form-label">Frequency</label>
            <input type="text" name="med_frequency[]" class="form-control" autocomplete="on">
          </div>
          <div class="col-md-2">
            <label class="form-label">Duration</label>
            <input type="text" name="med_duration[]" class="form-control" autocomplete="on">
          </div>
          <div class="col-md-3">
            <label class="form-label">Indication</label>
            <input type="text" name="med_indication[]" class="form-control" autocomplete="on">
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary mb-3" onclick="addMedicationRow()">Add More</button>
      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary prev-step">Previous</button>
        <button type="button" class="btn btn-primary next-step">Next</button>
      </div>
    </fieldset>



<!-- Step 4: Allergies, Family History, Social History -->
<fieldset class="form-step">
  <legend>7. Allergies</legend>
  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="nkda" value="No Known Drug Allergies">
    <label class="form-check-label">No Known Drug Allergies (NKDA)</label>
  </div>
  <div class="mb-3">
    <label>Allergic to</label>
    <input type="text" class="form-control" name="allergies">
  </div>
  <legend>8. Family History</legend>
  <div class="row">
    <div class="col-md-3">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="fh[]" value="Hypertension"><label class="form-check-label">Hypertension</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="fh[]" value="Diabetes"><label class="form-check-label">Diabetes</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="fh[]" value="Cancer"><label class="form-check-label">Cancer</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="fh[]" value="Stroke"><label class="form-check-label">Stroke</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="fh[]" value="Mental Illness"><label class="form-check-label">Mental Illness</label></div>
    </div>
    <div class="col-md-9">
      <label>Others</label>
      <input type="text" class="form-control" name="fh_other">
    </div>
  </div>
  <legend>9. Social History</legend>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label>Smoking</label>
      <select class="form-select" name="smoking">
        <option>No</option>
        <option>Yes</option>
      </select>
    </div>
    <div class="col-md-6 mb-3">
      <label>Packs/day</label>
      <input type="text" class="form-control" name="smoking_packs">
    </div>
    <div class="col-md-6 mb-3">
      <label>Alcohol</label>
      <select class="form-select" name="alcohol">
        <option>No</option>
        <option>Yes</option>
      </select>
    </div>
    <div class="col-md-6 mb-3">
      <label>Type/Frequency</label>
      <input type="text" class="form-control" name="alcohol_details">
    </div>
    <div class="col-md-6 mb-3">
      <label>Recreational Drugs</label>
      <select class="form-select" name="drugs">
        <option>No</option>
        <option>Yes</option>
      </select>
    </div>
    <div class="col-md-6 mb-3">
      <label>Specify</label>
      <input type="text" class="form-control" name="drugs_specify">
    </div>
    <div class="col-md-4 mb-3">
      <label>Sexual Activity</label>
      <select class="form-select" name="sexual_activity">
        <option>Active</option>
        <option>Inactive</option>
      </select>
    </div>
    <div class="col-md-4 mb-3">
      <label>Number of Partners</label>
      <input type="number" class="form-control" name="partners">
    </div>
    <div class="col-md-4 mb-3">
      <label>Use of Protection</label>
      <select class="form-select" name="protection">
        <option>Always</option>
        <option>Sometimes</option>
        <option>Never</option>
      </select>
    </div>
    <div class="col-md-12 mb-3">
      <label>Occupation/Work Hazards</label>
      <input type="text" class="form-control" name="work_hazards">
    </div>
  </div>
  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary prev-step">Previous</button>
    <button type="button" class="btn btn-primary next-step">Next</button>
  </div>
</fieldset>

<!-- Step 5: Immunization History, Review of Systems, Obstetric History -->
<fieldset class="form-step">
  <legend>10. Immunization History</legend>
  <div class="row mb-3">
    <div class="col-md-4">
      <label>Childhood Vaccines</label>
      <select class="form-select" name="childhood_vaccines">
        <option>Up to Date</option>
        <option>Not Sure</option>
      </select>
    </div>
    <div class="col-md-4">
      <label>Tetanus</label>
      <select class="form-select" name="tetanus">
        <option>No</option>
        <option>Yes</option>
      </select>
    </div>
    <div class="col-md-4">
      <label>Date</label>
      <input type="date" class="form-control" name="tetanus_date">
    </div>
    <div class="col-md-4">
      <label>Hepatitis B</label>
      <select class="form-select" name="hepatitis_b">
        <option>No</option>
        <option>Yes</option>
      </select>
    </div>
    <div class="col-md-4">
      <label>COVID-19 Vaccine</label>
      <select class="form-select" name="covid19">
        <option>No</option>
        <option>Yes</option>
      </select>
    </div>
    <div class="col-md-4">
      <label>Other Vaccines</label>
      <input type="text" class="form-control" name="other_vaccines">
    </div>
  </div>

  <legend>11. Review of Systems (ROS)</legend>
  <div class="table-responsive mb-3">
    <table class="table table-bordered">
      <thead><tr><th>System</th><th>Symptoms Present</th><th>Description</th></tr></thead>
      <tbody>
        <tr>
          <td>General</td>
          <td>
            <input type="text" class="form-control" name="ros_general" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_general_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Cardiovascular</td>
          <td>
            <input type="text" class="form-control" name="ros_cardio" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_cardio_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Respiratory</td>
          <td>
            <input type="text" class="form-control" name="ros_resp" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_resp_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Gastrointestinal</td>
          <td>
            <input type="text" class="form-control" name="ros_gi" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_gi_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Genitourinary</td>
          <td>
            <input type="text" class="form-control" name="ros_gu" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_gu_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Nervous System</td>
          <td>
            <input type="text" class="form-control" name="ros_ns" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_ns_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Musculoskeletal</td>
          <td>
            <input type="text" class="form-control" name="ros_msk" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_msk_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Dermatologic</td>
          <td>
            <input type="text" class="form-control" name="ros_derm" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_derm_desc" autocomplete="on">
          </td>
        </tr>
        <tr>
          <td>Psychiatric</td>
          <td>
            <input type="text" class="form-control" name="ros_psych" autocomplete="on">
          </td>
          <td>
            <input type="text" class="form-control" name="ros_psych_desc" autocomplete="on"></td></tr>
      </tbody>
    </table>
  </div>

  <legend>12. Obstetric/Gynecologic History</legend>
  <div class="row mb-3">
    <div class="col-md-3">
      <label>Gravida</label>
      <input type="number" class="form-control" name="gravida" autocomplete="on">
    </div>
    <div class="col-md-3">
      <label>Para</label>
      <input type="number" class="form-control" name="para" autocomplete="on">
    </div>
    <div class="col-md-3">
      <label>Abortions</label>
      <input type="number" class="form-control" name="abortions" autocomplete="on">
    </div>
    <div class="col-md-3">
      <label>Last Menstrual Period</label>
      <input type="date" class="form-control" name="lmp" autocomplete="on">
    </div>
    <div class="col-md-6">
      <label>Menstrual Cycle</label>
      <select class="form-select" name="menstrual_cycle">
        <option>Regular</option>
        <option>Irregular</option>
      </select>
    </div>
    <div class="col-md-6">
      <label>Contraceptive Use</label>
      <select class="form-select" name="contraceptive_use">
        <option>No</option>
        <option>Yes</option>
      </select>
      <label class="mt-1">Type</label>
      <input type="text" class="form-control" name="contraceptive_type" autocomplete="on">
    </div>
  </div>
  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary prev-step">Previous</button>
    <button type="button" class="btn btn-primary next-step">Next</button>
  </div>
</fieldset>

<!-- Step 6: Physical Examination and Clinical Assessment -->
<fieldset class="form-step">
  <legend>13. Physical Examination</legend>
  <div class="row mb-3">
    <div class="col-md-4"><label>Temperature (°C)</label>
      <input type="text" class="form-control" name="temp" autocomplete="on">
    </div>
    <div class="col-md-4"><label>Blood Pressure (mmHg)</label>
      <input type="text" class="form-control" name="bp"autocomplete="on">
    </div>
    <div class="col-md-4"><label>Heart Rate (bpm)</label>
      <input type="text" class="form-control" name="hr" autocomplete="on">
    </div>
    <div class="col-md-4"><label>Respiratory Rate</label>
      <input type="text" class="form-control" name="rr" autocomplete="on">
    </div>
    <div class="col-md-4"><label>O₂ Saturation (%)</label>
      <input type="text" class="form-control" name="spo2" autocomplete="on">
    </div>
    <div class="col-md-4"><label>Weight (kg)</label>
      <input type="text" class="form-control" name="weight" autocomplete="on">
    </div>
    <div class="col-md-4"><label>Height (cm)</label>
      <input type="text" class="form-control" name="height" autocomplete="on">
    </div>
    <div class="col-md-4"><label>BMI (kg/m²)</label>
      <input type="text" class="form-control" name="bmi" autocomplete="on">
    </div>
  </div>
  <legend>14. Clinical Assessment & Plan</legend>
  <div class="mb-3">
    <textarea class="form-control" name="clinical_assessment" rows="4" placeholder="Diagnosis and initial plan" autocomplete="on"></textarea>
  </div>
  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary prev-step">Previous</button>
    <button type="button" class="btn btn-primary next-step">Next</button>
  </div>
</fieldset>

<!-- Step 7: Clinician Details -->
<fieldset class="form-step">
  <legend>15. Clinician Details</legend>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label>Name</label>
      <input type="text" class="form-control" name="clinician_name" autocomplete="on">
    </div>
    <div class="col-md-6 mb-3">
      <label>Designation</label>
      <input type="text" class="form-control" name="clinician_designation" autocomplete="on">
    </div>
    <div class="col-md-6 mb-3">
      <label>Date</label>
      <input type="date" class="form-control" name="clinician_date" autocomplete="on">
    </div>
    <div class="col-md-6 mb-3">
      <label>Signature</label>
      <input type="text" class="form-control" name="clinician_signature" autocomplete="on">
    </div>
  </div>
  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary prev-step">Previous</button>
    <button type="submit" class="btn btn-success">Submit</button>
  </div>
</fieldset>

  </form>
</div>


<!-- Before </body> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
  // Initialize Select2 for Full Name
  $('#full_name').select2({
    placeholder: 'Select Full Name',
    ajax: {
      url: 'fetch_patients.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { q: params.term };
      },
      processResults: function (data) {
        return {
          results: data.results.map(function (item) {
            return {
              id: item.full_name,
              text: item.text,
              patient_pin: item.patient_pin,
              photo: item.photo,
              dob: item.dob,
            age: item.age,
            gender: item.gender,
            address: item.address,
            phone: item.phone,
            marital_status: item.marital_status
            };
          })
        };
      },
      cache: true
    }
  });

  // Initialize Select2 for Patient ID
  $('#patient_id').select2({
    placeholder: 'Select Patient ID',
    ajax: {
      url: 'fetch_patients.php',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return { q: params.term };
      },
      processResults: function (data) {
        return {
          results: data.results.map(function (item) {
            return {
              id: item.patient_pin,
              text: item.text,
              full_name: item.full_name,
              photo: item.photo,
              dob: item.dob,
        age: item.age,
        gender: item.gender,
        address: item.address,
        phone: item.phone,
        marital_status: item.marital_status
            };
          })
        };
      },
      cache: true
    }
  });

  // When full_name is selected
  $('#full_name').on('select2:select', function (e) {
    let data = e.params.data;
    let idOption = new Option(data.patient_pin + ' - ' + data.id, data.patient_pin, true, true);
    $('#patient_id').append(idOption).trigger('change');

    // Show photo and set hidden input
    $('#patient_photo').attr('src', '../uploads/' + data.photo);
    $('#photo').val(data.photo);


  // Autofill extra fields
  $('input[name="dob"]').val(data.dob);
  $('input[name="age"]').val(data.age);
  $('input[name="address"]').val(data.address);
  $('input[name="phone"]').val(data.phone);
  $('select[name="marital_status"]').val(data.marital_status);
  $('input[name="gender"][value="' + data.gender + '"]').prop('checked', true);

  });

  // When patient_id is selected
  $('#patient_id').on('select2:select', function (e) {
    let data = e.params.data;
    let nameOption = new Option(data.full_name + ' (' + data.id + ')', data.full_name, true, true);
    $('#full_name').append(nameOption).trigger('change');

    // Show photo and set hidden input
    $('#patient_photo').attr('src', '../uploads/' + data.photo);
    $('#photo').val(data.photo);
  });
});
</script>

<script>
  const steps = document.querySelectorAll('.form-step');
  let currentStep = 0;
  document.querySelectorAll('.next-step').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!validateStep(steps[currentStep])) return;
      steps[currentStep].classList.remove('active');
      currentStep++;
      steps[currentStep].classList.add('active');
    });
  });
  document.querySelectorAll('.prev-step').forEach(btn => {
    btn.addEventListener('click', () => {
      steps[currentStep].classList.remove('active');
      currentStep--;
      steps[currentStep].classList.add('active');
    });
  });
  function validateStep(step) {
    const inputs = step.querySelectorAll('input, select, textarea');
    for (let input of inputs) {
      if (input.hasAttribute('required') && !input.value.trim()) {
        alert('Please fill out all required fields.');
        input.focus();
        return false;
      }
    }
    return true;
  }
  function addSurgeryRow() {
    const div = document.createElement('div');
    div.className = 'row mb-3';
    div.innerHTML = `
      <div class="col-md-4">
        <input type="text" name="surgery_name[]" class="form-control" placeholder="Surgery/Procedure">
      </div>
      <div class="col-md-4">
        <input type="date" name="surgery_date[]" class="form-control">
      </div>
      <div class="col-md-4">
        <input type="text" name="surgery_complications[]" class="form-control" placeholder="Complications">
      </div>
    `;
    document.getElementById('surgery-entries').appendChild(div);
  }
  function addMedicationRow() {
    const div = document.createElement('div');
    div.className = 'row mb-3';
    div.innerHTML = `
      <div class="col-md-3">
        <input type="text" name="med_name[]" class="form-control" placeholder="Drug Name">
      </div>
      <div class="col-md-2">
        <input type="text" name="med_dosage[]" class="form-control" placeholder="Dosage">
      </div>
      <div class="col-md-2">
        <input type="text" name="med_frequency[]" class="form-control" placeholder="Frequency">
      </div>
      <div class="col-md-2">
        <input type="text" name="med_duration[]" class="form-control" placeholder="Duration">
      </div>
      <div class="col-md-3">
        <input type="text" name="med_indication[]" class="form-control" placeholder="Indication">
      </div>
    `;
    document.getElementById('medication-entries').appendChild(div);
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
