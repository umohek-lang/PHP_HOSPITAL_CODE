<?php
// include "db.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/auth.php';
require '../db.php';

if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Orders Viewer — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900: #0f2d6b; --blue-800: #1a3f8f; --blue-700: #1d4ed8;
      --blue-600: #2563eb; --blue-500: #3b82f6; --blue-400: #60a5fa;
      --blue-300: #93c5fd; --blue-200: #bfdbfe; --blue-100: #dbeafe; --blue-50: #eff6ff;
      --white:    #ffffff; --gray-50: #f8fafc; --gray-100: #f1f5f9;
      --gray-200: #e2e8f0; --gray-300: #cbd5e1; --gray-400: #94a3b8;
      --gray-500: #64748b; --gray-700: #334155; --gray-900: #0f172a;
      --green-600: #059669; --green-50: #ecfdf5; --green-100: #d1fae5; --green-700: #047857;
      --amber-600: #d97706; --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --violet-600: #7c3aed; --violet-50: #f5f3ff; --violet-100: #ede9fe; --violet-700: #6d28d9;
      --sky-600: #0284c7; --sky-50: #f0f9ff; --sky-100: #e0f2fe; --sky-700: #0369a1;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --blue-glow: rgba(37,99,235,.11);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }

    body::before {
      content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 600px 400px at 5% 10%, rgba(37,99,235,.05) 0%, transparent 70%),
        radial-gradient(ellipse 500px 350px at 95% 90%, rgba(96,165,250,.04) 0%, transparent 70%);
    }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 4px; }

    /* ── PAGE ── */
    .page { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 36px 24px 60px; }

    /* ── PAGE HEADER ── */
    .page-header { margin-bottom: 28px; }
    .ph-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 4px 12px;
      font-size: .68rem; font-weight: 700; color: var(--blue-700);
      text-transform: uppercase; letter-spacing: .08em; margin-bottom: 10px;
    }
    .ph-title { font-size: 1.55rem; font-weight: 800; color: var(--gray-900); letter-spacing: -.03em; }
    .ph-title em { font-style: italic; color: var(--blue-600); }
    .ph-sub { font-size: .82rem; color: var(--gray-400); margin-top: 4px; }

    /* ── SELECTOR CARD ── */
    .selector-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; padding: 20px 24px; margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
      display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;
    }
    .selector-field { flex: 1; min-width: 260px; }
    .selector-label {
      font-size: .68rem; font-weight: 700; letter-spacing: .09em;
      text-transform: uppercase; color: var(--gray-500);
      margin-bottom: 8px; display: flex; align-items: center; gap: 6px;
    }
    .selector-label i { color: var(--blue-500); }
    .refresh-badge {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 16px; background: var(--gray-100);
      border: 1px solid var(--gray-200); border-radius: 999px;
      font-size: .75rem; color: var(--gray-500); font-weight: 500;
      flex-shrink: 0;
    }
    .rdot {
      width: 7px; height: 7px; border-radius: 50%; background: var(--gray-300);
      transition: background .3s;
    }
    .rdot.active {
      background: var(--green-600);
      animation: rdpulse 2s ease-in-out infinite;
      box-shadow: 0 0 0 3px rgba(5,150,105,.2);
    }
    @keyframes rdpulse {
      0%,100% { box-shadow: 0 0 0 3px rgba(5,150,105,.2); }
      50%      { box-shadow: 0 0 0 6px rgba(5,150,105,.06); }
    }

    /* ── Select2 Light ── */
    .select2-container { z-index: 10; }
    .select2-container--default .select2-selection--single {
      background: var(--gray-50) !important; border: 1.5px solid var(--gray-200) !important;
      border-radius: 10px !important; height: 44px !important;
      display: flex !important; align-items: center !important;
    }
    .select2-container--default .select2-selection--single:hover { border-color: var(--blue-300) !important; }
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: var(--blue-500) !important; box-shadow: 0 0 0 3px var(--blue-glow) !important;
      background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--blue-700) !important; font-weight: 700 !important;
      font-family: 'Plus Jakarta Sans', sans-serif !important;
      font-size: 13.5px !important; line-height: 44px !important; padding-left: 14px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--gray-400) !important; font-weight: 400 !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px !important; right: 12px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: var(--gray-400) transparent transparent !important; }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b { border-color: transparent transparent var(--gray-400) !important; }
    .select2-dropdown {
      background: var(--white) !important; border: 1.5px solid var(--blue-200) !important;
      border-radius: 10px !important; box-shadow: var(--shadow-md) !important;
      font-family: 'Plus Jakarta Sans', sans-serif !important; overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      background: var(--gray-50) !important; border: 1px solid var(--gray-200) !important;
      border-radius: 7px !important; color: var(--gray-700) !important;
      font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 13px !important;
      padding: 7px 12px !important; outline: none !important;
    }
    .select2-results__option { color: var(--gray-700) !important; font-size: 13.5px !important; padding: 9px 16px !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--blue-50) !important; color: var(--blue-700) !important; }
    .select2-container--default .select2-results__option[aria-selected=true] { background: var(--blue-100) !important; color: var(--blue-800) !important; font-weight: 700 !important; }

    /* ── IDLE STATE ── */
    .idle-state {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; box-shadow: var(--shadow-sm);
      padding: 72px 20px; text-align: center;
    }
    .idle-state i { font-size: 3rem; color: var(--blue-200); display: block; margin-bottom: 14px; }
    .idle-state h3 { font-size: 1rem; font-weight: 700; color: var(--gray-700); margin-bottom: 6px; }
    .idle-state p  { font-size: .82rem; color: var(--gray-400); }

    /* ── ORDERS VIEW ── */
    #ordersView { display: none; }
    #ordersView.visible { display: block; }

    .sections-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .section-full { grid-column: 1 / -1; }

    /* ── SECTION PANEL ── */
    .section-panel {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .section-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; border-bottom: 1px solid var(--gray-100);
    }
    .sh-left { display: flex; align-items: center; gap: 10px; }
    .sh-icon {
      width: 34px; height: 34px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center; font-size: .95rem;
    }
    .sh-icon.blue   { background: var(--blue-50);   color: var(--blue-600);   }
    .sh-icon.green  { background: var(--green-50);  color: var(--green-600);  }
    .sh-icon.violet { background: var(--violet-50); color: var(--violet-600); }
    .sh-icon.sky    { background: var(--sky-50);    color: var(--sky-600);    }

    .panel-blue   .section-head { background: #fafcff; border-bottom-color: var(--blue-100); }
    .panel-green  .section-head { background: #fafffe; border-bottom-color: var(--green-100); }
    .panel-violet .section-head { background: #fdfaff; border-bottom-color: var(--violet-100); }
    .panel-sky    .section-head { background: #f9feff; border-bottom-color: var(--sky-100); }

    .sh-title { font-size: .88rem; font-weight: 800; color: var(--gray-900); }
    .sh-sub   { font-size: .7rem; color: var(--gray-400); margin-top: 1px; }
    .sh-badge {
      font-size: .67rem; font-weight: 700; padding: 3px 11px;
      border-radius: 999px;
    }
    .sh-badge.blue   { background: var(--blue-50);   color: var(--blue-700);   border: 1px solid var(--blue-100); }
    .sh-badge.green  { background: var(--green-50);  color: var(--green-700);  border: 1px solid var(--green-100); }
    .sh-badge.violet { background: var(--violet-50); color: var(--violet-700); border: 1px solid var(--violet-100); }
    .sh-badge.sky    { background: var(--sky-50);    color: var(--sky-700);    border: 1px solid var(--sky-100); }

    /* content slot */
    .section-content { overflow-x: auto; }

    /* ── TABLE ── */
    .data-table { width: 100%; border-collapse: collapse; font-size: .8rem; min-width: 400px; }
    .data-table thead th {
      padding: 9px 16px; text-align: left;
      font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-400); background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200); white-space: nowrap;
    }
    .data-table tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .1s; }
    .data-table tbody tr:last-child { border-bottom: none; }
    .data-table tbody tr:hover { background: var(--blue-50); }
    .data-table td { padding: 10px 16px; color: var(--gray-700); vertical-align: middle; line-height: 1.45; }
    .data-table td.mono { font-family: monospace; font-size: .72rem; color: var(--gray-400); font-weight: 700; }
    .data-table td.muted { color: var(--gray-400); font-size: .78rem; }
    .data-table td.wrap  { white-space: normal; word-break: break-word; max-width: 200px; }

    .status-pill {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 3px 10px; border-radius: 999px;
      font-size: .69rem; font-weight: 700; white-space: nowrap;
    }
    .status-pill.pending  { background: var(--amber-50);  border: 1px solid var(--amber-100); color: var(--amber-700); }
    .status-pill.complete { background: var(--green-50);  border: 1px solid var(--green-100); color: var(--green-700); }
    .status-pill.default  { background: var(--gray-100);  border: 1px solid var(--gray-200);  color: var(--gray-500); }

    /* ── STATES ── */
    .no-data {
      padding: 36px 20px; text-align: center; color: var(--gray-400);
    }
    .no-data i { display: block; font-size: 1.8rem; color: var(--gray-300); margin-bottom: 8px; }
    .no-data p { font-size: .8rem; }

    .loading-state {
      padding: 28px 20px; text-align: center; color: var(--gray-400);
      font-size: .8rem; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .spin-icon { color: var(--blue-400); animation: spin .8s linear infinite; display: inline-block; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── RESPONSIVE ── */
    @media (max-width: 860px) {
      .sections-grid { grid-template-columns: 1fr; }
      .section-full  { grid-column: auto; }
    }
    @media (max-width: 560px) {
      .page { padding: 20px 14px 48px; }
      .selector-card { padding: 16px; }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-activity"></i> Admin · EMR Orders</div>
    <div class="ph-title">Patient <em>Orders</em> Viewer</div>
    <div class="ph-sub">Select a patient to view their lab, nursing, pharmacy and consultation records.</div>
  </div>

  <!-- SELECTOR CARD -->
  <div class="selector-card">
    <div class="selector-field">
      <div class="selector-label"><i class="bi bi-person-badge-fill"></i> Select Patient</div>
      <select id="patientSelect" style="width:100%">
        <option value=""></option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="refresh-badge">
      <span class="rdot" id="rdot"></span>
      <span>Auto-refresh every 5s</span>
    </div>
  </div>

  <!-- IDLE STATE -->
  <div class="idle-state" id="idleState">
    <i class="bi bi-person-search"></i>
    <h3>No Patient Selected</h3>
    <p>Use the dropdown above to search and select a patient.</p>
  </div>

  <!-- ORDERS VIEW -->
  <div id="ordersView">
    <div class="sections-grid">

      <!-- Lab -->
      <div class="section-panel panel-blue">
        <div class="section-head">
          <div class="sh-left">
            <div class="sh-icon blue"><i class="bi bi-eyedropper-fill"></i></div>
            <div><div class="sh-title">Lab Orders</div><div class="sh-sub">Diagnostic investigations</div></div>
          </div>
          <span class="sh-badge blue" id="labBadge">—</span>
        </div>
        <div class="section-content" id="labContent">
          <div class="loading-state"><i class="bi bi-arrow-clockwise spin-icon"></i> Loading…</div>
        </div>
      </div>

      <!-- Nursing -->
      <div class="section-panel panel-green">
        <div class="section-head">
          <div class="sh-left">
            <div class="sh-icon green"><i class="bi bi-clipboard2-heart-fill"></i></div>
            <div><div class="sh-title">Nursing Orders</div><div class="sh-sub">Procedures & activities</div></div>
          </div>
          <span class="sh-badge green" id="nursingBadge">—</span>
        </div>
        <div class="section-content" id="nursingContent">
          <div class="loading-state"><i class="bi bi-arrow-clockwise spin-icon"></i> Loading…</div>
        </div>
      </div>

      <!-- Pharmacy -->
      <div class="section-panel panel-violet">
        <div class="section-head">
          <div class="sh-left">
            <div class="sh-icon violet"><i class="bi bi-capsule-pill"></i></div>
            <div><div class="sh-title">Pharmacy Orders</div><div class="sh-sub">Medicines & prescriptions</div></div>
          </div>
          <span class="sh-badge violet" id="pharmacyBadge">—</span>
        </div>
        <div class="section-content" id="pharmacyContent">
          <div class="loading-state"><i class="bi bi-arrow-clockwise spin-icon"></i> Loading…</div>
        </div>
      </div>

      <!-- Consultations (full width) -->
      <div class="section-panel panel-sky section-full">
        <div class="section-head">
          <div class="sh-left">
            <div class="sh-icon sky"><i class="bi bi-clipboard2-pulse-fill"></i></div>
            <div><div class="sh-title">Consultations</div><div class="sh-sub">Clinical records & diagnoses</div></div>
          </div>
          <span class="sh-badge sky" id="consultBadge">—</span>
        </div>
        <div class="section-content" id="consultationContent">
          <div class="loading-state"><i class="bi bi-arrow-clockwise spin-icon"></i> Loading…</div>
        </div>
      </div>

    </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let selectedPatientId = '';

$(document).ready(function () {
  $('#patientSelect').select2({
    placeholder: 'Search and select a patient…',
    allowClear: true,
    width: '100%',
  });

  $('#patientSelect').on('change', function () {
    selectedPatientId = $(this).val();
    if (selectedPatientId) {
      document.getElementById('idleState').style.display  = 'none';
      document.getElementById('ordersView').classList.add('visible');
      document.getElementById('rdot').classList.add('active');
      loadOrders(selectedPatientId);
    } else {
      document.getElementById('idleState').style.display  = '';
      document.getElementById('ordersView').classList.remove('visible');
      document.getElementById('rdot').classList.remove('active');
    }
  });
});

function loadOrders(id) {
  fetch('fetch_patient_orders.php?patient_id=' + id)
    .then(r => r.json())
    .then(data => {
      renderLab(data.lab_orders       || []);
      renderNursing(data.nursing_orders   || []);
      renderPharmacy(data.pharmacy_orders || []);
    })
    .catch(() => {
      ['labContent','nursingContent','pharmacyContent'].forEach(i => noData(i));
    });

  fetch('fetch_patient_consultations.php?patient_id=' + id)
    .then(r => r.json())
    .then(data => renderConsultations(data.consultations || []))
    .catch(() => noData('consultationContent'));
}

function setCount(id, n) {
  document.getElementById(id).textContent = n + ' record' + (n !== 1 ? 's' : '');
}
function safe(v) {
  return (v !== null && v !== undefined && v !== '') ? v : '<span style="color:var(--gray-300)">—</span>';
}
function statusPill(s) {
  if (!s) return '<span class="status-pill default">—</span>';
  const c = s.toLowerCase().includes('complet') ? 'complete'
          : s.toLowerCase().includes('pending')  ? 'pending' : 'default';
  return `<span class="status-pill ${c}">${s}</span>`;
}
function noData(id) {
  document.getElementById(id).innerHTML =
    `<div class="no-data"><i class="bi bi-inbox"></i><p>No records found.</p></div>`;
}

function renderLab(data) {
  setCount('labBadge', data.length);
  if (!data.length) { noData('labContent'); return; }
  let h = `<table class="data-table"><thead><tr><th>#</th><th>Test Name</th><th>Notes</th></tr></thead><tbody>`;
  data.forEach(r => { h += `<tr><td class="mono">${r.id}</td><td>${safe(r.test_name)}</td><td class="wrap muted">${safe(r.lab_notes)}</td></tr>`; });
  document.getElementById('labContent').innerHTML = h + '</tbody></table>';
}

function renderNursing(data) {
  setCount('nursingBadge', data.length);
  if (!data.length) { noData('nursingContent'); return; }
  let h = `<table class="data-table"><thead><tr><th>#</th><th>Procedure</th><th>Notes</th></tr></thead><tbody>`;
  data.forEach(r => { h += `<tr><td class="mono">${r.id}</td><td>${safe(r.procedure_name)}</td><td class="wrap muted">${safe(r.notes)}</td></tr>`; });
  document.getElementById('nursingContent').innerHTML = h + '</tbody></table>';
}

function renderPharmacy(data) {
  setCount('pharmacyBadge', data.length);
  if (!data.length) { noData('pharmacyContent'); return; }
  let h = `<table class="data-table"><thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Notes</th><th>Status</th></tr></thead><tbody>`;
  data.forEach(r => { h += `<tr><td class="mono">${r.id}</td><td>${safe(r.medicine_name)}</td><td>${safe(r.dosage)}</td><td class="wrap muted">${safe(r.notes)}</td><td>${statusPill(r.status)}</td></tr>`; });
  document.getElementById('pharmacyContent').innerHTML = h + '</tbody></table>';
}

function renderConsultations(data) {
  setCount('consultBadge', data.length);
  if (!data.length) { noData('consultationContent'); return; }
  let h = `<table class="data-table"><thead><tr>
    <th>#</th><th>Date</th><th>O₂ Sat</th><th>Pain</th><th>BMI</th>
    <th>Chief Complaint</th><th>Diagnosis</th><th>Treatment Plan</th>
  </tr></thead><tbody>`;
  data.forEach(r => { h += `<tr>
    <td class="mono">${r.consultation_id}</td>
    <td class="muted">${safe(r.consultation_date)}</td>
    <td>${safe(r.oxygen_saturation)}</td>
    <td>${safe(r.pain_level)}</td>
    <td>${safe(r.bmi)}</td>
    <td class="wrap">${safe(r.chief_complaint)}</td>
    <td class="wrap">${safe(r.diagnosis)}</td>
    <td class="wrap muted">${safe(r.treatment_plan)}</td>
  </tr>`; });
  document.getElementById('consultationContent').innerHTML = h + '</tbody></table>';
}

setInterval(() => {
  if (selectedPatientId) loadOrders(selectedPatientId);
}, 5000);
</script>
</body>
</html>