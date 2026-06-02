<?php
require '../includes/auth.php';
require '../db.php';



// Ensure doctor is logged in
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit();
}

$patient_id = $_GET['patient_id'] ?? null;
$patient = null;

if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$patient) {
    echo "<div class='alert alert-danger'>Patient not found.</div>";
    exit;
}

function fetchGroupedData($pdo, $query, $key) {
    $stmt = $pdo->query($query);
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[$row[$key]][] = $row;
    }
    return $data;
}

// Fetch dynamic dropdown data
$labTestsCatalog = $pdo->query("SELECT * FROM lab_tests_catalog")->fetchAll(PDO::FETCH_ASSOC);
$nursingProceduresCatalog = $pdo->query("SELECT * FROM nursing_procedures_catalog")->fetchAll(PDO::FETCH_ASSOC);
$pharmacyMedicinesCatalog = $pdo->query("SELECT * FROM pharmacy_medicines")->fetchAll(PDO::FETCH_ASSOC);


$medicalHistory = fetchGroupedData($pdo, "
    SELECT mr.*, u.full_name AS doctor_name 
    FROM medical_records mr
    LEFT JOIN users u ON mr.doctor_id = u.user_id
", 'patient_id');

$vitalSigns = fetchGroupedData($pdo, "SELECT * FROM vital_signs", 'patient_id');
$labTests = fetchGroupedData($pdo, "SELECT * FROM lab_tests", 'patient_id');
$prescriptions = fetchGroupedData($pdo, "SELECT * FROM prescriptions", 'patient_id');

$prescItems = [];
$prescItemsStmt = $pdo->query("
    SELECT pi.prescription_id, m.medicine_name, pi.dosage, pi.duration 
    FROM prescription_items pi
    JOIN medicines m ON pi.medicine_id = m.medicine_id
");
while ($row = $prescItemsStmt->fetch(PDO::FETCH_ASSOC)) {
    $prescItems[$row['prescription_id']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Consultation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        body { background-color: #f8f9fa; }
        .section-title { margin-top: 2rem; margin-bottom: 1rem; font-weight: 600; }
        .form-label { font-weight: 500; }
        textarea.form-control { resize: vertical; }
        
        
  /* Keeps everything aligned and scrollable nicely on mobile */
  #labOrdersSection {
    overflow-x: auto;
    max-width: 100%;
  }

  table {
    font-size: 0.9rem;
    word-wrap: break-word;
  }

  th, td {
    white-space: nowrap;
    vertical-align: middle;
  }

  @media (max-width: 768px) {
    table {
      font-size: 0.8rem;
    }
    .alert {
      font-size: 0.9rem;
    }
  }

    </style>
</head>
<body>

<div class="container mt-4 mb-5">
    <h3 class="text-center text-primary mb-4">Doctor Consultation Form</h3>

    <!-- Patient Information -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">Patient Information</div>
        <div class="card-body d-flex">
            <img src="../uploads/<?= htmlspecialchars($patient['photo']) ?>" class="rounded" width="120" height="120" alt="Photo">
            <div class="ms-4 row w-100">
                <?php
                $columns = [
                    'full_name' => 'Full Name', 'gender' => 'Gender', 'age' => 'Age',
                    'address' => 'Address', 'phone' => 'Phone', 'email' => 'Email',
                    'patient_pin' => 'Patient PIN', 'patient_type' => 'Patient Type',
                    'patient_status' => 'Status', 'hmo_name' => 'HMO Name'
                ];
                foreach ($columns as $field => $label):
                    if (!empty($patient[$field])): ?>
                        <div class="col-md-6 mb-2">
                            <strong><?= $label ?>:</strong> <?= htmlspecialchars($patient[$field]) ?>
                        </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Medical History -->
    <?php if (!empty($medicalHistory[$patient_id])): ?>
        <h5 class="section-title">Medical History</h5>
        <ul class="list-group mb-4">
            <?php foreach ($medicalHistory[$patient_id] as $record): ?>
                <li class="list-group-item small">
                    <strong>Diagnosis:</strong> <?= htmlspecialchars($record['diagnosis']) ?><br>
                    <strong>Doctor:</strong> <?= htmlspecialchars($record['doctor_name']) ?><br>
                    <?php if ($record['notes']): ?>
                        <strong>Notes:</strong> <?= htmlspecialchars($record['notes']) ?><br>
                    <?php endif; ?>
                    <?php if ($record['attachment']): ?>
                        <strong>Attachment:</strong> <a href="../uploads/<?= htmlspecialchars($record['attachment']) ?>" target="_blank">View</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Vital Signs -->
    <?php if (!empty($vitalSigns[$patient_id])): ?>
        <h5 class="section-title">Vital Signs from Nurse</h5>
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr><th>Date</th><th>BP</th><th>Pulse</th><th>Temp</th><th>Resp. Rate</th><th>Oxygen</th><th>Pain Level</th><th>Height</th><th>Weight</th><th>Blood Sugar</th><th>Symptoms</th></tr>
            </thead>
            <tbody>
            <?php foreach ($vitalSigns[$patient_id] as $v): ?>
                <tr>
                    <td><?= $v['recorded_at'] ?></td>
                    <td><?= $v['blood_pressure'] ?></td>
                    <td><?= $v['pulse_rate'] ?></td>
                    <td><?= $v['temperature'] ?></td>
                    <td><?= $v['respiration_rate'] ?></td>
                                        <td><?= $v['oxygen_saturation'] ?></td>
                                        <td><?= $v['pain_level'] ?></td>
                                        <td><?= $v['height_cm'] ?></td>
                                        <td><?= $v['weight_kg'] ?></td>
                                        <td><?= $v['blood_sugar'] ?></td>
                                        <td><?= $v['symptoms_notes'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <tbody>
<?php foreach ($vitalSigns[$patient_id] as $v): ?>
<tr>
    <td><?= $v['recorded_at'] ?></td>

    <td><br>
        blood pressure:
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="blood_pressure" value="<?= $v['blood_pressure'] ?>"></td>

    <td>
        <br>
        Pulse:
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="pulse_rate" value="<?= $v['pulse_rate'] ?>"></td>

    <td>
        <br>
        Temperature:
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="temperature" value="<?= $v['temperature'] ?>"></td>

    <td>
        <br>
        Respiration:
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="respiration_rate" value="<?= $v['respiration_rate'] ?>"></td>

    <td>
        <br>
        Oxygen:
        
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="oxygen_saturation" value="<?= $v['oxygen_saturation'] ?>"></td>

    <td>
        <br>
        Pain Level:
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="pain_level" value="<?= $v['pain_level'] ?>"></td>

    <td>
        <br>
        Height:
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="height_cm" value="<?= $v['height_cm'] ?>"></td>

    <td>
        <br>
        Weight:
        
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="weight_kg" value="<?= $v['weight_kg'] ?>"></td>

    <td>
        <br>
        Blood Sugar:
        <input class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="blood_sugar" value="<?= $v['blood_sugar'] ?>"></td>

    <td>
        <br>
        Symptoms:
        <textarea class="form-control form-control-sm editable" data-id="<?= $v['vital_id'] ?>" data-field="symptoms_notes"><?= $v['symptoms_notes'] ?></textarea></td>
</tr>
<?php endforeach; ?>
</tbody>

    
    

    <!-- Lab Tests -->
    <?php if (!empty($labTests[$patient_id])): ?>
        <h5 class="section-title">Lab Tests</h5>
        <table class="table table-striped table-sm">
            <thead>
                <tr><th>Date</th><th>Test</th><th>Result</th></tr>
            </thead>
            <tbody>
            <?php foreach ($labTests[$patient_id] as $l): ?>
                <tr>
                    <td><?= $l['test_date'] ?></td>
                    <td><?= $l['test_name'] ?></td>
                    <td><?= $l['result'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Prescriptions -->
    <?php if (!empty($prescriptions[$patient_id])): ?>
        <h5 class="section-title">Prescriptions</h5>
        <?php foreach ($prescriptions[$patient_id] as $presc): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header">Date: <?= htmlspecialchars($presc['prescription_date']) ?></div>
                <div class="card-body small">
                    <ul class="mb-2">
                        <?php
                        $items = $prescItems[$presc['prescription_id']] ?? [];
                        if (!empty($items)):
                            foreach ($items as $item): ?>
                                <li><?= $item['medicine_name'] ?> (<?= $item['dosage'] ?>, <?= $item['duration'] ?>)</li>
                            <?php endforeach;
                        else: echo "<li>No medicines listed.</li>";
                        endif;
                        ?>
                    </ul>
                    <strong>Notes:</strong> <?= htmlspecialchars($presc['notes']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
                        
    <!-- Full Consultation Form -->
    <h5 class="section-title">New Consultation Entry</h5>
    <form action="save_consultation.php" method="POST"  enctype="multipart/form-data"  class="card p-4 shadow-sm">
        <input type="hidden" id="action_type" name="action_type" value="save">
        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">

        <!-- 2. Vital Signs -->
<!-- Multi-step Vital Signs -->
<div class="card mb-3">
    <div class="card-header bg-secondary text-white">Vital Signs & Measurements</div>
    <div class="card-body">
        <!-- Step 1 -->
        <fieldset class="form-step" id="step1">
            <legend class="mb-3">Step 1: Begin Assessment</legend>
            <div class="text-end">
                <button type="button" class="btn btn-primary" onclick="nextStep(2)">Next</button>
            </div>
        </fieldset>

        <!-- Step 2 -->
        <fieldset class="form-step d-none" id="step2">
            <legend class="mb-3">Step 2: Vital Signs</legend>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="temperature" class="form-label">Temperature (°C)</label>
                    <input type="number" step="0.1" class="form-control" id="temperature" name="temperature" autocomplete="on">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="pulse_rate" class="form-label">Pulse Rate (bpm)</label>
                    <input type="number" class="form-control" id="pulse_rate" name="pulse" autocomplete="on">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="respiration_rate" class="form-label">Respiration Rate</label>
                    <input type="number" class="form-control" id="respiration_rate" name="respiratory_rate" autocomplete="on">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="blood_pressure" class="form-label">Blood Pressure</label>
                    <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" autocomplete="on">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="oxygen_saturation" class="form-label">Oxygen Saturation (%)</label>
                    <input type="number" class="form-control" id="oxygen_saturation" name="oxygen_saturation" autocomplete="on">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="pain_level" class="form-label">Pain Level (0–10)</label>
                    <input type="number" min="0" max="10" class="form-control" id="pain_level" name="pain_level" autocomplete="on">
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="nextStep(1)">Back</button>
                <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next</button>
            </div>
        </fieldset>

        <!-- Step 3 -->
        <fieldset class="form-step d-none" id="step3">
            <legend class="mb-3">Step 3: Physical Measurements</legend>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="height_cm" class="form-label">Height (cm)</label>
                    <input type="number" step="0.01" class="form-control" id="height_cm" name="height_cm" autocomplete="on" >
                </div>
                <div class="col-md-6 mb-3">
                    <label for="weight_kg" class="form-label">Weight (kg)</label>
                    <input type="number" step="0.01" class="form-control" id="weight_kg" name="weight_kg" autocomplete="on">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="bmi" class="form-label">BMI</label>
                    <input type="text" class="form-control" id="bmi" name="bmi" autocomplete="on">
                    <small class="text-muted">Auto-calculated (Normal: 18.5 – 24.9 kg/m²)</small>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="nextStep(2)">Back</button>
                <button type="button" class="btn btn-primary" onclick="nextStep(4)">Next</button>
            </div>
        </fieldset>

        <!-- Step 4 -->
        <fieldset class="form-step d-none" id="step4">
            <legend class="mb-3">Step 4: Additional Observations</legend>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="blood_sugar" class="form-label">Blood Sugar (mg/dL)</label>
                    <input type="number" step="0.1" class="form-control" id="blood_sugar" name="blood_sugar" autocomplete="on">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="consciousness_level" class="form-label">Level of Consciousness (AVPU)</label>
                    <select name="consciousness_level" id="consciousness_level" class="form-select">
                        <option value="" disabled selected>-- Select AVPU Level --</option>
                        <option value="Alert">Alert</option>
                        <option value="Verbal">Verbal</option>
                        <option value="Pain">Pain</option>
                        <option value="Unresponsive">Unresponsive</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="vitals_time" class="form-label">Time Vitals Taken</label>
                    <input type="time" class="form-control" id="vitals_time" name="vitals_time" autocomplete="on">
                </div>
            </div>

            <div class="mb-3">
                <label for="symptoms_notes" class="form-label">Observed Symptoms / Notes</label>
                <textarea class="form-control" id="symptoms_notes" name="symptoms_notes" rows="3" autocomplete="on"></textarea>
            </div>

<div class="d-flex justify-content-between mt-3">
    <button type="button" class="btn btn-secondary" onclick="nextStep(3)">Back</button>
    <button type="button" class="btn btn-success" onclick="scrollToSubmit()">Finish</button>
</div>

        </fieldset>
    </div>
</div>


        <div class="mb-3">
            <label class="form-label">Chief Complaint & History</label>
            <textarea name="chief_complaint" class="form-control" rows="3" autocomplete="on" ></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Physical Examination</label>
            <textarea name="physical_exam" class="form-control" rows="3" autocomplete="on" ></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Diagnosis</label>
            <textarea name="diagnosis" class="form-control" rows="2" autocomplete="on" ></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Investigations</label>
            <textarea name="investigations" class="form-control" rows="2" autocomplete="on" ></textarea>
        </div>

<div class="mb-3">
  <label class="form-label">Lab Investigations</label>
  <!-- <select name="lab_order[]" class="form-select"> -->
    <?php foreach ($labTestsCatalog as $test): ?>
     <div class="form-check">
        <input class="form-check-input" type="checkbox" name="lab_order[]" value="<?= htmlspecialchars($test['test_name']) ?>" id="lab_<?= $test['id'] ?>">
        <label class="form-check-label" for="lab_<?= $test['id'] ?>">
          <?= htmlspecialchars($test['test_name']) ?>
        </label>
      </div>
    <?php endforeach; ?>

    <!-- notes -->
     <div class="mb-3">
  <label class="form-label">Lab Notes / Instructions</label>
  <textarea name="lab_notes" id="lab_notes" class="form-control" rows="2" placeholder="Enter specific instructions or reasons for lab tests..."></textarea>
</div>

  <!-- </select> -->
  <!--<button type="button" id="submitLab" name="submit_lab" class="btn btn-outline-primary">Send Order to Lab</button>-->
  
  <button type="button" onclick="sendToLab()" class="btn btn-outline-primary">Send Order to Lab</button>

  <!-- LAB ORDER -->
  <h5>🧪 Lab Orders</h5>
<table class="table table-bordered" >
    
  <thead>
    <tr>
      <th>Test</th>
      <th>Send to Cashier forPayment or Billing</th>
      <th>Paid</th>
      <!-- <th>Seen</th> -->
    </tr>
  </thead>
  <!--<tbody>-->
      <tbody id="labOrdersTableBody">

     <?php
    $stmt = $pdo->prepare("SELECT * FROM lab_orders WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    foreach ($stmt->fetchAll() as $order) {
       echo "<tr>
          <td>" . htmlspecialchars($order['test_name']) . "</td>
          <td>" . (!empty($order['is_sent_to_cashier']) 
              ? 'SENT' 
              : "<button class='btn btn-sm btn-dark send-to-cashier' data-id='{$order['id']}' data-type='lab'>Send</button>") . "</td>
              
              
          <td data-type='lab' data-id='{$order['id']}'>" . ($order['is_paid'] ? 'YES' : 'NO') . "</td>
        </tr>";
    }
    ?>
    <!-- NEW BUTTON ROW -->
    <tr>
<td colspan="4" class="text-end">
        <!-- <button type="button" class="btn btn-outline-primary" onclick="toggleLabOrders()">🔍 View Test Result</button> --> 
<button type="button" class="btn btn-outline-primary position-relative" onclick="toggleLabOrders()">
  🔍 View Test Result
  <span id="labResultBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
    0
  </span>
</button>



      </td>
    </tr>
  </tbody>
</table>

<!-- 🔽 This is the collapsible section -->
<div id="labOrdersSection" style="display: none; margin-top: 20px;">
  <div class="alert alert-info">Live Lab Orders Feed (auto-refreshing)</div>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Patient ID</th>
        <th>Test Name</th>
        <th>Requested By</th>
        <th>Status</th>
          <th>Result</th> <!-- ✅ New column -->
            <th>Report File</th>
      </tr>
    </thead>
    <tbody id="labTestTableBody"></tbody>
  </table>
</div>

<!-- 💡 AJAX Script for Auto-refreshing -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
/** ===============================
 * 🔹 FETCH LAB TEST RESULTS TABLE
 * =============================== */
function fetchLabTests() {
    $.ajax({
        url: 'fetch_lab_tests.php',
        type: 'GET',
        data: { patient_id: <?= json_encode($patient_id) ?> },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '';
                response.data.forEach(function(test) {
                    let badge = test.status.toLowerCase() === 'pending'
                        ? '<span class="badge bg-warning text-dark">Pending</span>'
                        : '<span class="badge bg-success">Completed</span>';

                    let resultContent = test.result ?? 'N/A';
                    let fileNameOnly = test.report_file ? test.report_file.replace(/^uploads[\\/]/, '') : '';
                    let reportLink = test.report_file
                        ? `<a href="https://angelora.com.ng/ANGELORA/lab/uploads/${encodeURIComponent(fileNameOnly)}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>`
                        : '<span class="text-muted">No File</span>';

                    html += `
                        <tr>
                            <td>${test.patient_id}</td>
                            <td>${test.test_name}</td>
                            <td>${test.requested_by}</td>
                            <td>${badge}</td>
                            <td>${resultContent}</td>
                            <td>${reportLink}</td>
                        </tr>`;
                });
                $('#labTestTableBody').html(html);
            } else {
                $('#labTestTableBody').html('<tr><td colspan="6" class="text-center">No test results found for this patient.</td></tr>');
            }
        },
        error: function() {
            $('#labTestTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error fetching test results.</td></tr>');
        }
    });
}

/** ===============================
 * 🔹 MARK LAB RESULTS AS SEEN
 * =============================== */
function markLabResultsAsSeen() {
    $.ajax({
        url: 'mark_lab_results_seen.php',
        type: 'POST',
        data: { patient_id: <?= json_encode($patient_id) ?> },
        success: function() {
            fetchNewLabResultsCount(); // refresh badge after marking as seen
        }
    });
}

/** ===============================
 * 🔹 TOGGLE LAB RESULT SECTION
 * =============================== */
function toggleLabOrders() {
    const section = document.getElementById('labOrdersSection');
    if (section.style.display === 'none') {
        section.style.display = 'block';
        fetchLabTests();
        markLabResultsAsSeen(); // reset badge count
    } else {
        section.style.display = 'none';
    }
}

/** ===============================
 * 🔹 AUTO-REFRESH TEST RESULTS
 * =============================== */
setInterval(() => {
    if ($('#labOrdersSection').is(':visible')) {
        fetchLabTests();
    }
}, 5000);

/** ===============================
 * 🔹 AUTO-UPDATE BADGE COUNTER
 * =============================== */
function fetchNewLabResultsCount() {
    $.ajax({
        url: 'fetch_new_lab_result_count.php',
        type: 'GET',
        data: { patient_id: <?= json_encode($patient_id) ?> },
        dataType: 'json',
        success: function(response) {
            const badge = $('#labResultBadge');
            if (response.status === 'success') {
                const count = parseInt(response.count);
                if (count > 0) {
                    badge.text(count);
                    badge.show();
                } else {
                    badge.text('0');
                    badge.hide();
                }
            }
        },
        error: function() {
            $('#labResultBadge').hide();
        }
    });
}

// 🔁 Auto-refresh badge count every 5 seconds
setInterval(fetchNewLabResultsCount, 5000);

// Initial fetch on page load
$(document).ready(function() {
    fetchNewLabResultsCount();
});
</script>


</div>

<!-- nursing procedures -->
<div class="mb-3">
  <label class="form-label">Nursing Procedures</label>
  <?php foreach ($nursingProceduresCatalog as $proc): ?>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="procedure_order[]" value="<?= htmlspecialchars($proc['procedure_name']) ?>" id="proc_<?= $proc['id'] ?>">
      <label class="form-check-label" for="proc_<?= $proc['id'] ?>"><?= htmlspecialchars($proc['procedure_name']) ?></label>
    </div>
  <?php endforeach; ?>
  <div class="mb-3">
  <label for="nursing_notes" class="form-label">Nursing Notes / Instructions</label>
  <textarea name="nursing_notes" id="nursing_notes" class="form-control" rows="2" placeholder="Enter any specific instructions for the nurse..."></textarea>
</div>

 
 <div class="d-flex align-items-center gap-2 mb-3">
    
    
    <button type="button" onclick="sendToNurse()" class="btn btn-outline-success">Send Order to Nurse</button>

    <!--<?php if (!empty($patient_id)): ?>-->

    <!--<?php else: ?>-->
    <!--    <span class="text-muted">Select a patient to prescribe</span>-->
    <!--<?php endif; ?>-->
</div>


  
  <h5> Nurse Result</h5>
<table class="table table-bordered" id="nurseOrdersTable">
  <thead>
    <tr>
      <th>Procedure</th>
      <th> Send to Cashier for payment or billing</th>
      <th>Paid</th>
      <!-- <th>Seen</th> -->
    </tr>
  </thead>
  <tbody>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM nursing_orders WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    foreach ($stmt->fetchAll() as $order) {
      echo "<tr>
          <td>" . htmlspecialchars($order['procedure_name']) . "</td>
          <td>" . (!empty($order['is_sent_to_cashier']) 
              ? 'SENT' 
              : "<button class='btn btn-sm btn-dark send-to-cashier' data-id='{$order['id']}' data-type='nursing'>Send</button>") . "</td>
          <td data-type='nursing' data-id='{$order['id']}'>" . ($order['is_paid'] ? 'YES' : 'NO') . "</td>
        </tr>";
    }
    ?>
  </tbody>
</table>
<h5 class="mt-5">🩺 Treatments & Prescriptions</h5>

<div id="dynamic-medical-records">
  <div class="alert alert-info">Loading data...</div>
</div>


</div>


<div class="mb-3">
  <label class="form-label">Pharmacy Orders</label>
  <div class="input-group mb-2">
    

<!-- </select> -->
<div class="mb-3">
  <label class="form-label">Prescription</label>
  <textarea name="pharmacy_order" class="form-control" rows="2" placeholder="e.g. Paracetamol, Amoxicillin 250mg"></textarea>
</div>



<!-- Dosage / Instructions -->
<div class="mb-3">
  <label class="form-label">additional note (optional)</label>
  <!-- <textarea name="pharmacy_dosage" class="form-control" rows="2" placeholder="pharmacist will see it"></textarea> -->
  <textarea name="pharmacy_dosage" class="form-control" rows="2" placeholder="e.g. 500mg x3 daily, after meals"></textarea>
</div>


<button type="button" onclick="sendToPharmacy()" class="btn btn-outline-warning">Send to Pharmacy</button>


    <!-- Send Pharmacy Order -->
   <br> <h5>💊 Pharmacy Orders</h5>
<table class="table table-bordered"  id="pharmacyOrdersTable">
    

    <thead><tr><th>Medicine</th><th>Dosage</th><th>Cashier</th><th>Paid</th>
        <!-- <th>Seen</th> -->
    </tr></thead>
    <tbody>
<?php
    $stmt = $pdo->prepare("SELECT * FROM pharmacy_orders WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    foreach ($stmt->fetchAll() as $order) {
      echo "<tr>
          <td>" . htmlspecialchars($order['medicine_name']) . "</td>
          <td>" . htmlspecialchars($order['dosage']) . "</td>
          <td>" . (!empty($order['is_sent_to_cashier']) 
              ? 'SENT' 
              : "<button class='btn btn-sm btn-dark send-to-cashier' data-id='{$order['id']}' data-type='pharmacy'>Send</button>") . "</td>
          <td data-type='pharmacy' data-id='{$order['id']}'>" . ($order['is_paid'] ? 'YES' : 'NO') . "</td>
        </tr>";
    }
    ?>
    </tbody>

<div id="dispenseAlert" class="alert alert-success d-none">💊 New medicine dispensed!</div>

<table class="table table-bordered">
    <h5 class="mt-4">✅ Dispensed Medicines</h5>
    <thead>
        <tr><th>Medicine</th><th>Qty</th><th>Prescribed By</th><th>Dispensed By</th><th>Notes</th></tr>
    </thead>
    <tbody id="dispensedTableBody">
        <!-- New rows will be added here -->
    </tbody>
</table>

</table>
<div class="d-flex align-items-center gap-2 mb-6 mt-1">
    

    <?php if (!empty($patient_id)): ?>
    
    <?php else: ?>
        <span class="text-muted">Select a patient to prescribe</span>
    <?php endif; ?>
</div>

  </div>
</div>


        <div class="mb-3">
            <label class="form-label">Treatment Plan / Prescription</label>
            <textarea name="treatment_plan" class="form-control" rows="3" autocomplete="on"></textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Doctor's Name / Signature</label>
                <input type="text" name="doctor_signature" class="form-control" autocomplete="on">
            </div>
            <div class="col-md-6">
                <label class="form-label">Date</label>
                <input type="date" name="consultation_date" class="form-control" value="<?= date('Y-m-d') ?>" autocomplete="on">
            </div>
        </div>
<!--        <label for="xray_file">Upload X-ray Picture (Optional)</label>-->
<!--<input type="file" name="xray_file" id="xray_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" autocomplete="on">-->
<!--<br>-->

 

        <!--<button type="submit" class="btn btn-success w-100">📝 Save Consultation</button>-->
        <button type="submit" class="btn btn-primary">Save Consultation</button>
        
    </form>


</div>

<!--control action submission-->
<script>
function sendToLab() {
    const form = new FormData();
    form.append('patient_id', <?= json_encode($patient_id) ?>);
    form.append('lab_notes', $('#lab_notes').val());

    // Append each checked checkbox individually
    $('input[name="lab_order[]"]:checked').each(function(){
        form.append('lab_order[]', this.value);
    });

    $.ajax({
        url: 'send_to_lab.php',
        type: 'POST',
        data: form,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(res){
            alert(res.message);  
            refreshLabOrders(); // refresh table
        },
        error: function(err){
            alert("⚠️ Failed to send lab order.");
            console.error(err);
        }
    });
}


// function refreshLabOrders() {
//     $.ajax({
//         url: 'fetch_lab_order_status.php', // returns HTML <tr> rows
//         type: 'GET',
//         data: { patient_id: <?= json_encode($patient_id) ?> },
//         success: function(html){
//             $('table tbody ').filter(':has(.send-to-cashier[data-type="lab"])').html(html);
//         }
//     });
// }

function refreshLabOrders() {
    $.ajax({
        url: 'fetch_lab_order_status.php',
        type: 'GET',
        data: { patient_id: <?= json_encode($patient_id) ?> },
        success: function(html){
            $('#labOrdersTableBody').html(html); // ✅ correct & instant
        },
        error: function(err){
            console.error("Failed to refresh lab orders", err);
        }
    });
}


function sendToNurse() {
    const form = new FormData();
    form.append('patient_id', <?= json_encode($patient_id) ?>);
    form.append('nursing_notes', $('#nursing_notes').val());

    // Append each checked checkbox individually
    $('input[name="procedure_order[]"]:checked').each(function(){
        form.append('procedure_order[]', this.value);
    });

    $.ajax({
        url: 'send_to_nurse.php',
        type: 'POST',
        data: form,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(res){
            alert(res.message);  
            refreshNurseOrders(); // refresh table if you have a function
        },
        error: function(err){
            alert("⚠️ Failed to send nursing order.");
            console.error(err);
        }
    });
}


function refreshNurseOrders() {
    $.get(
        'fetch_nurse_order_status.php',
        { patient_id: <?= json_encode($patient_id) ?> },
        function(html){
            $('#nurseOrdersTable tbody').html(html);
        }
    );
}



function sendToPharmacy() {
    $.ajax({
        url: 'send_to_pharmacy.php',
        type: 'POST',
        data: {
            patient_id: <?= json_encode($patient_id) ?>,
            pharmacy_order: $('textarea[name="pharmacy_order"]').val(),
            pharmacy_dosage: $('textarea[name="pharmacy_dosage"]').val()
        },
        dataType: 'json',
        success: function(res){
            alert(res.message);
            refreshPharmacyOrders(); // ✅ instant
        }
    });
}


function refreshPharmacyOrders() {
    $.get(
        'fetch_pharmacy_order_status.php',
        { patient_id: <?= json_encode($patient_id) ?> },
        function(html){
            $('#pharmacyOrdersTable tbody').html(html);
        }
    );
}

</script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    const btn = document.getElementById("submitPharmacy");

    btn.addEventListener("click", function(event) {
        // Prevent page reload or form submission
        event.preventDefault();
        event.stopPropagation();

        // Collect values from the form
        const prescription = document.querySelector('textarea[name="pharmacy_order"]').value.trim();
        const dosage = document.querySelector('textarea[name="pharmacy_dosage"]').value.trim();

        if (prescription === '') {
            alert("Please enter a prescription before sending.");
            return;
        }

        // ✅ You can now send data via AJAX without page reload
        fetch('send_to_pharmacy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                pharmacy_order: prescription,
                pharmacy_dosage: dosage
            })
        })
        .then(response => response.text())
        .then(data => {
            alert("✅ Sent to pharmacy successfully!");
            console.log(data);
        })
        .catch(err => {
            console.error("Error:", err);
            alert("⚠️ Failed to send to pharmacy.");
        });
    });
});
</script>

<!-- vital signs steps -->
<script>

    function nextStep(step) {
        document.querySelectorAll('.form-step').forEach(field => field.classList.add('d-none'));
        document.getElementById('step' + step).classList.remove('d-none');

        if (step === 3) {
            const height = parseFloat(document.getElementById('height_cm').value) / 100;
            const weight = parseFloat(document.getElementById('weight_kg').value);
            if (height > 0 && weight > 0) {
                const bmi = weight / (height * height);
                document.getElementById('bmi').value = bmi.toFixed(2);
            }
        }
    }
</script>
<!-- PHARMACIST DISPENSED MEDICINE -->
<script>
let lastDispensedId = 0;
const patientId = <?= json_encode($patient_id ?? '') ?>;

function fetchDispensedMedicines() {
    fetch(`fetch_dispensed_medicines.php?patient_id=${patientId}&last_id=${lastDispensedId}`)
        .then(res => res.json())
        .then(data => {
            if (data.new_count > 0) {
                document.getElementById('dispensedTableBody').innerHTML += data.html;
                lastDispensedId = data.latest_id;

                // Show alert
                const alertBox = document.getElementById('dispenseAlert');
                alertBox.classList.remove('d-none');
                setTimeout(() => alertBox.classList.add('d-none'), 3000);
            }
        });
}

// Poll every 5 seconds
setInterval(fetchDispensedMedicines, 5000);

// First call on load
document.addEventListener('DOMContentLoaded', fetchDispensedMedicines);
</script>



<!-- script to remove all required fields -->
<script>
document.querySelector('form').addEventListener('submit', function (e) {
    // Disable all hidden fields so they don't block form submission
    document.querySelectorAll('.form-step.d-none input, .form-step.d-none textarea, .form-step.d-none select').forEach(function(el) {
        el.removeAttribute('required');
    });
});
</script>
<!-- script to handle finish submitt button -->
<script>
function scrollToSubmit() {
    document.querySelector('button[type="submit"]').scrollIntoView({ behavior: 'smooth' });
}
</script>


<script>
    function updatePaidStatus(type) {
    const patientId = <?= json_encode($patient_id) ?>;

    $.ajax({
        url: 'fetch_paid_status.php',
        method: 'GET',
        data: { patient_id: patientId, type: type },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                res.data.forEach(row => {
                    const rowElem = $(`[data-type='${type}'][data-id='${row.id}']`);
                    if (row.is_paid == 1) {
                        rowElem.text('YES').removeClass('btn-success').addClass('text-success fw-bold');
                    }
                });
            }
        }
    });
}

// Poll every 5 seconds
setInterval(() => {
    updatePaidStatus('lab');
    updatePaidStatus('nursing');
    updatePaidStatus('pharmacy');
}, 5000);

</script>


<script>
function loadTreatmentsAndPrescriptions(patientId) {
  const container = document.getElementById('dynamic-medical-records');
  container.innerHTML = '<div class="alert alert-info">Loading records...</div>';

  fetch(`get_treatments_prescriptions.php?patient_id=${patientId}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        container.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        return;
      }

      let html = '';

      // Treatments Table
      html += '<h6 class="mt-3">💊 Treatments</h6>';
      if (data.treatments.length === 0) {
        html += '<div class="alert alert-warning">No treatments found.</div>';
      } else {
        html += '<table class="table table-bordered">';
        html += '<thead><tr><th>Treatment</th><th>Medicine</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
        data.treatments.forEach(t => {
          html += `<tr>
                    <td>${t.treatment_name}</td>
                    <td>${t.medicine_name ?? 'N/A'}</td>
                    <td>${t.notes}</td>
                    <td>${t.treatment_date}</td>
                  </tr>`;
        });
        html += '</tbody></table>';
      }

      // Prescriptions Table
      html += '<h6 class="mt-4">📝 Prescriptions</h6>';
      if (data.prescriptions.length === 0) {
        html += '<div class="alert alert-warning">No prescriptions found.</div>';
      } else {
        html += '<table class="table table-bordered">';
        html += '<thead><tr><th>Medicine</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
        data.prescriptions.forEach(p => {
          html += `<tr>
                    <td>${p.medicine_name}</td>
                    <td>${p.notes}</td>
                    <td>${p.prescription_date}</td>
                  </tr>`;
        });
        html += '</tbody></table>';
      }

      container.innerHTML = html;
    })
    .catch(err => {
      container.innerHTML = '<div class="alert alert-danger">Error loading data.</div>';
      console.error(err);
    });
}

// Load once page is ready, and make sure `patient_id` is available
document.addEventListener('DOMContentLoaded', () => {
  const patientId = <?= json_encode($patient_id ?? null) ?>;
  if (patientId) {
    loadTreatmentsAndPrescriptions(patientId);
  }
});
</script>


<!-- LINK MARK AS SEENN -->
<script>
document.addEventListener('click', function (e) {
  if (e.target.classList.contains('mark-seen')) {
    const button = e.target;
    const id = button.getAttribute('data-id');
    const type = button.getAttribute('data-type');

    fetch(`mark_seenn.php?id=${id}&type=${type}`)
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          // Remove the row from the table
          const row = button.closest('tr');
          row.parentNode.removeChild(row);
        } else {
          alert('Failed to mark as seen.');
        }
      })
      .catch(() => alert('Request failed.'));
  }
});
</script>

<!-- Send payment alert to cashier -->

<script>
$(document).on('click', '.send-to-cashier', function(e) {
    e.preventDefault(); // Stop default click behavior

    const btn = $(this);                  // The button clicked
    const orderId = btn.data('id');       // Order ID
    const type = btn.data('type');        // Type: lab, nursing, pharmacy

    $.ajax({
        url: 'send_to_cashier.php',
        type: 'POST',
        data: { id: orderId, type: type },
        dataType: 'json', // expect JSON response
        success: function(response) {
            if (response.status === 'success') {
                // Replace the button with "SENT"
                btn.replaceWith('<span class="badge bg-success">SENT</span>');

                // Optionally, update the "Paid" column in the same row
                $('td[data-type="'+type+'"][data-id="'+orderId+'"]').text('YES');
            } else {
                alert(response.message || 'Failed to send to cashier.');
            }
        },
        error: function() {
            alert('Server error.');
        }
    });
});
</script>


<!-- mark as seen defaul form prevention -->

<script>
$(document).ready(function () {
    $(document).on('click', '.mark-seen', function (e) {
        e.preventDefault(); // ✅ Prevent default form/button behavior

        const orderId = $(this).data('id');
        const type = $(this).data('type');
        const button = $(this); // cache the button for UI feedback

        $.ajax({
            url: 'mark_seen.php',
            method: 'POST',
            data: {
                id: orderId,
                type: type
            },
            success: function (response) {
                // Optionally handle JSON response
                // Update the cell with an icon or status
                button.closest('td').html('👀'); // Replace the button with '👀'
            },
            error: function () {
                alert('Failed to mark as seen. Try again.');
            }
        });
    });
});
</script>

<!-- send to lab -->
<script>
$(document).ready(function () {
    $('#submitLab').click(function (e) {
        e.preventDefault();

        const selectedTests = $("input[name='lab_order[]']:checked")
            .map(function () { return $(this).val(); }).get();

        const labNotes = $('#lab_notes').val();

        if (selectedTests.length === 0 && !labNotes.trim()) {
            alert("Select at least one lab test or enter lab notes.");
            return;
        }

        $.ajax({
            url: 'submit_lab.php',
            method: 'POST',
            data: {
                lab_order: selectedTests,
                lab_notes: labNotes,
                patient_id: <?= json_encode($patient_id) ?> // pass patient ID if needed
            },
            // success: function (response) {
            //     alert("Lab order submitted successfully!");
            //     location.reload();
            // }
            success: function (response) {
    alert("Response from server: " + response); // Show actual server response
    location.reload();
}
,
            error: function () {
                alert("Failed to send lab order.");
            }
        });
    });
    // Nursing order AJAX
    $('#submitNursing').click(function (e) {
    e.preventDefault();

    const selectedProcedures = $("input[name='procedure_order[]']:checked")
        .map(function () { return $(this).val(); }).get();

    const nursingNotes = $('#nursing_notes').val(); // ✅ Capture notes

    if (selectedProcedures.length === 0 && !nursingNotes.trim()) {
        alert("Select at least one nursing procedure or enter nursing notes.");
        return;
    }

    $.ajax({
        url: 'submit_nursing.php',
        method: 'POST',
        data: {
            procedure_order: selectedProcedures,
            nursing_notes: nursingNotes,
            patient_id: <?= json_encode($patient_id) ?>
        },
        success: function () {
            alert("Nursing order sent.");
            location.reload();
        },
        error: function () {
            alert("Failed to send nursing order.");
        }
    });
});


    // Pharmacy order AJAX
    $('#submitPharmacy').click(function (e) {
    e.preventDefault();

    const medicine = $("textarea[name='pharmacy_order']").val().trim();
    const dosage = $("textarea[name='pharmacy_dosage']").val().trim();

    if (!medicine && !dosage) {
        alert("Enter at least a medicine name or dosage.");
        return;
    }

    $.ajax({
        url: 'submit_pharmacy.php',
        method: 'POST',
        data: {
            pharmacy_order: medicine,
            pharmacy_dosage: dosage,
            patient_id: <?= json_encode($patient_id) ?>
        },
        success: function (response) {
            alert("Pharmacy order sent.");
            location.reload();
        },
        error: function () {
            alert("Failed to send pharmacy order.");
        }
    });
});

});
</script>

<!--vital signs editting-->

<script>
$(".editable").on("change", function() {
    let vital_id = $(this).data("id");
    let field = $(this).data("field");
    let value = $(this).val();

    $.ajax({
        url: "update_vitals_ajax.php",
        method: "POST",
        data: {
            vital_id: vital_id,
            field: field,
            value: value
        },
        success: function(res) {
            try {
                let r = JSON.parse(res);
                if (r.status === "success") {
                    alert("Vital updated successfully");
                } else {
                    alert("Error: " + r.message);
                }
            } catch (e) {
                alert("Unexpected server response");
            }
        }
    });
});
</script>




<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
