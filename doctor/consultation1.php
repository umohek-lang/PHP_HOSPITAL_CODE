<?php
require '../includes/auth.php';
require '../db.php';

// Check doctor login (role_id = 2)
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit();
}

// Fetch patients
$stmt = $pdo->query("SELECT * FROM patients ORDER BY full_name");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map patients by ID for quick lookup in JS
$patientMap = [];
foreach ($patients as $p) {
    $patientMap[$p['patient_id']] = $p;
}

// Fetch medical history
// $medHistStmt = $pdo->query("SELECT * FROM medical_records");
// $medicalHistory = [];
// while ($row = $medHistStmt->fetch(PDO::FETCH_ASSOC)) {
//     $medicalHistory[$row['patient_id']][] = $row;
// }

// Fetch medical history with doctor info
$medHistStmt = $pdo->query("
    SELECT mr.*, u.full_name AS doctor_name 
    FROM medical_records mr
    LEFT JOIN users u ON mr.doctor_id = u.user_id
");

$medicalHistory = [];
while ($row = $medHistStmt->fetch(PDO::FETCH_ASSOC)) {
    $medicalHistory[$row['patient_id']][] = $row;
}



// Fetch vital signs
$vitalStmt = $pdo->query("SELECT * FROM vital_signs");
$vitalSigns = [];
while ($row = $vitalStmt->fetch(PDO::FETCH_ASSOC)) {
    $vitalSigns[$row['patient_id']][] = $row;
}

// Fetch lab tests
$labStmt = $pdo->query("SELECT * FROM lab_tests");
$labTests = [];
while ($row = $labStmt->fetch(PDO::FETCH_ASSOC)) {
    $labTests[$row['patient_id']][] = $row;
}

// Fetch prescriptions
$prescStmt = $pdo->query("SELECT * FROM prescriptions");
$prescriptions = [];
while ($row = $prescStmt->fetch(PDO::FETCH_ASSOC)) {
    $prescriptions[$row['patient_id']][] = $row;
}

// Fetch prescription items joined with medicines
$prescItemsStmt = $pdo->query("
    SELECT pi.prescription_id, m.medicine_name, pi.dosage, pi.duration 
    FROM prescription_items pi
    JOIN medicines m ON pi.medicine_id = m.medicine_id
");

$prescItems = [];
while ($row = $prescItemsStmt->fetch(PDO::FETCH_ASSOC)) {
    $prescItems[$row['prescription_id']][] = [
        'medicine_name' => $row['medicine_name'],
        'dosage' => $row['dosage'],
        'duration' => $row['duration']
    ];
}


// Columns to display for patient info
$columns = [
    'full_name' => 'Full Name',
    'gender' => 'Gender',
    'age' => 'Age',
    'address' => 'Address',
    'phone' => 'Phone',
    'email' => 'Email',
    'patient_pin' => 'Patient PIN',
    'patient_type' => 'Patient Type',
    'patient_status' => 'Patient Status',
    'hmo_name' => 'HMO Name',
    'photo' => 'Photo'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Patient Medical Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f5f8fa; }
        .section-title { margin-top: 2rem; margin-bottom: 1rem; }
        .img-thumbnail { max-width: 150px; }

        @media print {
    body * {
        visibility: hidden;
    }
    #patientDetails, #doctorReportSection, #patientDetails * , #doctorReportSection * {
        visibility: visible;
    }
    #patient_id, label[for="patient_id"] {
        display: none !important;
    }
    button, select, textarea {
        display: none !important;
    }
}

    </style>
</head>
<body>
<div class="container mt-5 mb-5">
    <h2 class="text-center mb-4">Patient Medical Details</h2>
    <div class="mb-4">
        <label for="patient_id" class="form-label">Select Patient</label>
        <select id="patient_id" class="form-select" onchange="showPatientDetails()">
            <option value="">-- Select Patient --</option>
            <?php foreach ($patients as $patient): ?>
                <option value="<?= htmlspecialchars($patient['patient_id']) ?>">
                    <?= htmlspecialchars($patient['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="patientDetails" style="display:none;">
        <!-- Patient Info -->
        <h4 class="section-title">Patient Information</h4>
        <div class="row mb-3">
            <div class="col-md-4 text-center">
                <img id="patient_photo" src="" alt="Patient Photo" class="img-thumbnail" style="display:none;">
            </div>
            <div class="col-md-8 row" id="patientInfoFields">
                <?php foreach ($columns as $field => $label): 
                    if ($field !== 'photo'): ?>
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-bold"><?= $label ?></label>
                            <input type="text" class="form-control form-control-sm" id="info_<?= $field ?>" readonly>
                        </div>
                <?php endif; endforeach; ?>
            </div>
        </div>

        <!-- Medical History -->
        <h4 class="section-title">Medical History</h4>
        <div id="medicalHistorySection">
            <p>No medical history available.</p>
        </div>

        <!-- Vital Signs -->
        <h4 class="section-title">Vital Signs</h4>
        <div id="vitalSignsSection">
            <p>No vital signs recorded.</p>
        </div>

        <!-- Lab Tests -->
        <h4 class="section-title">Lab Tests</h4>
        <div id="labTestsSection">
            <p>No lab tests recorded.</p>
        </div>

        <!-- Prescriptions -->
        <h4 class="section-title">Prescriptions</h4>
        <div id="prescriptionsSection">
            <p>No prescriptions available.</p>
        </div>
    </div>
</div>


<!-- Doctor's Report and Print Section -->
<div class="container mb-5" id="doctorReportSection" style="display:none;">
    <!-- <h4 class="section-title">Doctor's Final Report & Comments</h4> -->
    <form id="doctorReportForm">
        <div class="mb-3">
            <label for="finalReport" class="form-label fw-bold">Doctor's Final Report</label>
            <textarea id="finalReport" class="form-control" rows="4" placeholder="Write final report here..."></textarea>
<div id="finalReportPreview" class="d-none"></div>

        </div>
        <div class="mb-3">
            <label for="doctorComments" class="form-label fw-bold">Doctor's Comments</label>
            <textarea id="doctorComments" class="form-control" rows="3" placeholder="Enter any additional comments..."></textarea>
<div id="doctorCommentsPreview" class="d-none"></div>

        </div>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print Report as PDF</button>

       <button type="" id="printPatientBtn" class="">
    <!-- Print Patient Reporttttttttttt -->
</button>


    </form>
</div>

<script>
    window.onbeforeprint = () => {
        const finalReportText = document.getElementById('finalReport').value;
        const doctorCommentsText = document.getElementById('doctorComments').value;

        document.getElementById('finalReportPreview').textContent = finalReportText;
        document.getElementById('doctorCommentsPreview').textContent = doctorCommentsText;

        // Hide textareas, show previews
        document.getElementById('finalReport').style.display = 'none';
        document.getElementById('doctorComments').style.display = 'none';
        document.getElementById('finalReportPreview').classList.remove('d-none');
        document.getElementById('doctorCommentsPreview').classList.remove('d-none');
    };

    window.onafterprint = () => {
        // Restore original view after print
        document.getElementById('finalReport').style.display = '';
        document.getElementById('doctorComments').style.display = '';
        document.getElementById('finalReportPreview').classList.add('d-none');
        document.getElementById('doctorCommentsPreview').classList.add('d-none');
    };
</script>



<script>
    // Transfer PHP data to JS
    const patientMap = <?= json_encode($patientMap, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    const medicalHistory = <?= json_encode($medicalHistory, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    const vitalSigns = <?= json_encode($vitalSigns, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    const labTests = <?= json_encode($labTests, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    const prescriptions = <?= json_encode($prescriptions, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
    const prescItems = <?= json_encode($prescItems, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;

    function showPatientDetails() {
        const patientId = document.getElementById('patient_id').value;
        const detailsDiv = document.getElementById('patientDetails');
        if (!patientId || !patientMap[patientId]) {
            detailsDiv.style.display = 'none';
            return;
        }

        const patient = patientMap[patientId];

// Print Patient Button

        document.getElementById('printPatientBtn').onclick = function () {
    const patientId = document.getElementById('patient_id').value;
    if (patientId) {
        window.location.href = 'print_patient_report.php?patient_id=' + encodeURIComponent(patientId);
    } else {
        alert('Please select a patient first.');
    }
};


        // Show patient info fields
        <?php foreach ($columns as $field => $label): ?>
            <?php if ($field === 'photo'): ?>
                const photoElem = document.getElementById('patient_photo');
                if (patient['photo']) {
                    photoElem.src = '../uploads/' + patient['photo'];
                    photoElem.style.display = 'block';
                } else {
                    photoElem.style.display = 'none';
                }
            <?php else: ?>
                document.getElementById('info_<?= $field ?>').value = patient['<?= $field ?>'] ?? '';
            <?php endif; ?>
        <?php endforeach; ?>

        // Medical History
const medHistDiv = document.getElementById('medicalHistorySection');
medHistDiv.innerHTML = '';
if (medicalHistory[patientId] && medicalHistory[patientId].length > 0) {
    let html = `<table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Diagnosis</th>
                <th>Doctor</th>
                <th>Notes</th>
                <th>Attachment</th>
            </tr>
        </thead>
        <tbody>`;

    medicalHistory[patientId].forEach(record => {
        const doctorInfo = `${record.doctor_name || 'Unknown'} (ID: ${record.doctor_id || '-'})`;
        html += `<tr>
            <td>${record.diagnosis || '-'}</td>
            <td>${doctorInfo}</td>
            <td>${record.notes || '-'}</td>
            <td>${record.attachment 
                    ? `<a href="../uploads/${record.attachment}" target="_blank">View</a>` 
                    : '-'}</td>
        </tr>`;
    });

    html += `</tbody></table>`;
    medHistDiv.innerHTML = html;
} else {
    medHistDiv.innerHTML = '<p>No medical history available.</p>';
}


        // Vital Signs
        const vitalDiv = document.getElementById('vitalSignsSection');
        vitalDiv.innerHTML = '';
        if (vitalSigns[patientId] && vitalSigns[patientId].length > 0) {
            let table = `<table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Blood Pressure</th>
                                    <th>Heart Rate</th>
                                    <th>Temperature</th>
                                    <th>Respiration Rate</th>
                                </tr>
                            </thead>
                            <tbody>`;
            vitalSigns[patientId].forEach(v => {
                table += `<tr>
                            <td>${v.recorded_at || '-'}</td>
                            <td>${v.blood_pressure || '-'}</td>
                            <td>${v.pulse_rate || '-'}</td>
                            <td>${v.temperature || '-'}</td>
                            <td>${v.respiration_rate || '-'}</td>
                          </tr>`;
            });
            table += '</tbody></table>';
            vitalDiv.innerHTML = table;
        } else {
            vitalDiv.innerHTML = '<p>No vital signs recorded.</p>';
        }

        // Lab Tests
        const labDiv = document.getElementById('labTestsSection');
        labDiv.innerHTML = '';
        if (labTests[patientId] && labTests[patientId].length > 0) {
            let table = `<table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Test Name</th>
                                    <th>Result</th>
                                    
                                </tr>
                            </thead>
                            <tbody>`;
            labTests[patientId].forEach(l => {
                table += `<tr>
                            <td>${l.test_date || '-'}</td>
                            <td>${l.test_name || '-'}</td>
                            <td>${l.result || '-'}</td>
                            
                          </tr>`;
            });
            table += '</tbody></table>';
            labDiv.innerHTML = table;
        } else {
            labDiv.innerHTML = '<p>No lab tests recorded.</p>';
        }

        // Prescriptions
        const prescDiv = document.getElementById('prescriptionsSection');
        prescDiv.innerHTML = '';
        if (prescriptions[patientId] && prescriptions[patientId].length > 0) {
            let html = '';
            prescriptions[patientId].forEach(prescription => {
                html += `<div class="card mb-3">
                    <div class="card-header">
                        Prescription Date: ${prescription.prescription_date || '-'}
                    </div>
                    <div class="card-body">
                        <h6>Medicines:</h6>
                        <ul>`;
                const items = prescItems[prescription.prescription_id] || [];
                if (items.length > 0) {
                    items.forEach(item => {
    html += `<li>${item.medicine_name} - Dosage: ${item.dosage}, Duration: ${item.duration || '-'}</li>`;
});

                } else {
                    html += '<li>No medicines listed.</li>';
                }
                html += `</ul>
                        <p><strong>Notes:</strong> ${prescription.notes || '-'}</p>
                    </div>
                </div>`;
            });
            prescDiv.innerHTML = html;
        } else {
            prescDiv.innerHTML = '<p>No prescriptions available.</p>';
        }

        detailsDiv.style.display = 'block';
        document.getElementById('doctorReportSection').style.display = 'block';

    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
