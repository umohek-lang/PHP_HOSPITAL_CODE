<?php
require '../includes/auth.php';
require '../db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ==========================================================
   🧩 Helper for clean JSON output
========================================================== */
function send_json($data, $status = 200) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/* ==========================================================
   🔍 AJAX PATIENT SEARCH
========================================================== */
if (isset($_GET['ajax'])) {
    try {
        $term = trim($_GET['term'] ?? '');
        $like = "%$term%";

        $stmt = $pdo->prepare("
            SELECT patient_id, full_name, email, phone, patient_pin, registration_date
            FROM patients
            WHERE full_name LIKE :term
               OR patient_pin LIKE :term
               OR email LIKE :term
               OR phone LIKE :term
               OR DATE(registration_date) LIKE :term
            ORDER BY registration_date DESC
            LIMIT 50
        ");
        $stmt->execute([':term' => $like]);
        send_json($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        send_json(['error' => 'Search failed: ' . $e->getMessage()], 500);
    }
}

/* ==========================================================
   👤 FETCH SINGLE PATIENT DETAILS
========================================================== */
if (isset($_GET['patient_id'])) {
    try {
        $patient_id = (int) $_GET['patient_id'];
        if ($patient_id <= 0) send_json(['error' => 'Invalid patient ID.'], 400);

        $result = [];

        // ✅ Patient Info
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        $result['patient'] = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result['patient']) send_json(['error' => 'Patient not found'], 404);

        // ✅ Safe fetch helper
        function safeFetch($pdo, $sql, $params) {
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $ex) {
                return [['error' => $ex->getMessage()]];
            }
        }

        // ✅ Related tables
        $result['consultations'] = safeFetch($pdo, "
            SELECT consultation_date, doctor_name, diagnosis, treatment_plan
            FROM consultations WHERE patient_id = :id
            ORDER BY consultation_date DESC
        ", [':id' => $patient_id]);

        $result['vital_signs'] = safeFetch($pdo, "
            SELECT recorded_at, temperature, pulse_rate, blood_pressure, bmi
            FROM vitals WHERE patient_id = :id
            ORDER BY recorded_at DESC
        ", [':id' => $patient_id]);

        $result['lab_tests'] = safeFetch($pdo, "
            SELECT test_name, test_date, result, status
            FROM lab_tests WHERE patient_id = :id
            ORDER BY test_date DESC
        ", [':id' => $patient_id]);

$result['lab_orders'] = safeFetch($pdo, "
    SELECT id, patient_id, test_name, status, ordered_at, requested_by,
           completed_by, completed_at, is_sent_to_cashier, is_seen_by_doctor,
           is_paid, lab_notes
    FROM lab_orders WHERE patient_id = :id
    ORDER BY ordered_at DESC
", [':id' => $patient_id]);

        $result['prescriptions'] = safeFetch($pdo, "
            SELECT treatment_date, medicine_name, treatment_name, notes
            FROM prescriptions WHERE patient_id = :id
            ORDER BY treatment_date DESC
        ", [':id' => $patient_id]);

        send_json($result);
    } catch (Throwable $e) {
        error_log("Patient details error: " . $e->getMessage());
        send_json(['error' => 'Failed to load patient details: ' . $e->getMessage()], 500);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Patient Search</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.table-hover tbody tr:hover { background-color: #e9f5ff; cursor: pointer; }
.patient-photo {
    width: 100px; height: 100px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #ddd;
    margin-right: 15px;
}
</style>
</head>
<body class="p-3">
<div class="container">
  <h3 class="mb-3 text-center">Search Patients</h3>

  <!-- 🔍 Search Box -->
  <div class="row mb-3 g-2">
    <div class="col-md-7">
      <input id="searchInput" type="search" class="form-control" placeholder="Type name, PIN, phone, email or date (YYYY-MM-DD)..." autofocus>
    </div>
    <div class="col-md-3 d-grid">
      <button id="searchBtn" class="btn btn-primary">Search</button>
    </div>
    <div class="col-md-2 d-grid">
      <button id="clearBtn" class="btn btn-outline-secondary">Clear</button>
    </div>
  </div>

  <!-- 🧾 Patient List -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Full Name</th>
          <th>PIN</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Date Registered</th>
        </tr>
      </thead>
      <tbody id="patientsTable">
        <tr><td colspan="5" class="text-center">Type to search...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- 🧍 Patient Details Modal -->
  <div class="modal fade" id="patientModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Patient Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="patientDetails">Loading...</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchInput = document.getElementById('searchInput');
const patientsTable = document.getElementById('patientsTable');
const patientModalEl = document.getElementById('patientModal');
const patientModal = new bootstrap.Modal(patientModalEl);
const patientDetails = document.getElementById('patientDetails');

// 🔍 Search Patients
async function searchPatients(term) {
  const res = await fetch('?ajax=1&term=' + encodeURIComponent(term));
  if (!res.ok) return [];
  return res.json();
}

// 🧾 Render Search Results
function renderPatients(rows) {
  if (!rows.length) {
    patientsTable.innerHTML = '<tr><td colspan="5" class="text-center text-danger">No patients found.</td></tr>';
    return;
  }
  patientsTable.innerHTML = rows.map(r => `
    <tr data-patient-id="${r.patient_id}">
      <td>${r.full_name}</td>
      <td>${r.patient_pin}</td>
      <td>${r.email ?? ''}</td>
      <td>${r.phone ?? ''}</td>
      <td>${r.registration_date ?? ''}</td>
    </tr>
  `).join('');
}

// 📋 On Click — Load Full Patient Details
patientsTable.addEventListener('click', async e => {
  const tr = e.target.closest('tr');
  if (!tr || !tr.dataset.patientId) return;
  const patient_id = tr.dataset.patientId;

  try {
    const res = await fetch('?patient_id=' + patient_id);
    const data = await res.json();
    if (!res.ok || data.error) throw new Error(data.error || 'Unknown error');
    displayPatientDetails(data);
  } catch (err) {
    console.error('Fetch error:', err);
    alert("Error loading patient details: " + err.message);
  }
});

// 🩺 Display Details in Modal
function displayPatientDetails(d) {
  const p = d.patient;
  if (!p) return alert("Patient details not found.");

  // ✅ Default photo fallback
  const photo = p.photo && p.photo.trim() !== '' ? p.photo : 'default-avatar.png';

  patientDetails.innerHTML = `
    <div class="d-flex align-items-center mb-3">
      <img src="${photo}" alt="Patient Photo" class="patient-photo">
      <div>
        <h5 class="mb-0">${p.full_name}</h5>
        <small>${p.gender ?? 'N/A'}, ${p.age ?? 'N/A'} years</small><br>
        <small><strong>PIN:</strong> ${p.patient_pin}</small>
      </div>
    </div>
    <p><strong>Phone:</strong> ${p.phone ?? ''} | <strong>Email:</strong> ${p.email ?? ''}</p>
    <p><strong>Address:</strong> ${p.address ?? ''}</p>
    <hr>
<h6 class="text-primary mt-3">🧪 Lab Orders</h6>
${renderTable(d.lab_orders, [
  'id','patient_id','test_name','status','ordered_at','requested_by',
  'lab_notes'
])}

    <h6 class="text-primary mt-3">🩺 Consultations</h6>
    ${renderTable(d.consultations, ['consultation_date','doctor_name','diagnosis','treatment_plan'])}

    <h6 class="text-primary mt-3">❤️ Vital Signs</h6>
    ${renderTable(d.vital_signs, ['recorded_at','temperature','pulse_rate','blood_pressure','bmi'])}

    <h6 class="text-primary mt-3">🧪 Lab Tests</h6>
    ${renderTable(d.lab_tests, ['test_name','test_date','result','status'])}

    <h6 class="text-primary mt-3">💊 Prescriptions</h6>
    ${renderTable(d.prescriptions, ['treatment_date','medicine_name','treatment_name','notes'])}
  `;
  patientModal.show();
}

// 📊 Helper: Render Tables
function renderTable(data, fields) {
  if (!data || !data.length) return '<p class="text-muted">No records found.</p>';
  const headers = fields.map(f => `<th>${f.replace('_',' ').toUpperCase()}</th>`).join('');
  const rows = data.map(r => `<tr>${fields.map(f => `<td>${r[f] ?? ''}</td>`).join('')}</tr>`).join('');
  return `<div class="table-responsive"><table class="table table-sm table-bordered mt-2">
    <thead class="table-light"><tr>${headers}</tr></thead><tbody>${rows}</tbody></table></div>`;
}

// 🔎 Search Logic
document.getElementById('searchBtn').addEventListener('click', async () => {
  const q = searchInput.value.trim();
  const rows = await searchPatients(q);
  renderPatients(rows);
});

document.getElementById('clearBtn').addEventListener('click', () => {
  searchInput.value = '';
  patientsTable.innerHTML = '<tr><td colspan="5" class="text-center">Type to search...</td></tr>';
});

let timeout = null;
searchInput.addEventListener('input', () => {
  clearTimeout(timeout);
  timeout = setTimeout(async () => {
    const q = searchInput.value.trim();
    if (q.length >= 2) {
      const rows = await searchPatients(q);
      renderPatients(rows);
    }
  }, 400);
});

searchInput.addEventListener('keydown', async e => {
  if (e.key === 'Enter') {
    const q = searchInput.value.trim();
    const rows = await searchPatients(q);
    renderPatients(rows);
  }
});
</script>
</body>
</html>
