<?php
include "db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/auth.php';
require '../db.php';

if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 4) {
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

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900:  #1e3a5f;
      --blue-800:  #1e40af;
      --blue-700:  #1d4ed8;
      --blue-600:  #2563eb;
      --blue-500:  #3b82f6;
      --blue-400:  #60a5fa;
      --blue-300:  #93c5fd;
      --blue-200:  #bfdbfe;
      --blue-100:  #dbeafe;
      --blue-50:   #eff6ff;
      --blue-glow: rgba(37,99,235,.12);

      --white:     #ffffff;
      --gray-50:   #f8fafc;
      --gray-100:  #f1f5f9;
      --gray-200:  #e2e8f0;
      --gray-300:  #cbd5e1;
      --gray-400:  #94a3b8;
      --gray-500:  #64748b;
      --gray-600:  #475569;
      --gray-700:  #334155;
      --gray-800:  #1e293b;
      --gray-900:  #0f172a;

      --green:     #16a34a;
      --green-bg:  #dcfce7;
      --green-100: #bbf7d0;
      --amber:     #d97706;
      --amber-bg:  #fef3c7;
      --red:       #dc2626;
      --red-bg:    #fee2e2;
      --violet:    #7c3aed;
      --violet-bg: #ede9fe;
      --teal:      #0d9488;
      --teal-bg:   #ccfbf1;

      --radius:    12px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.05);
      --shadow:    0 4px 16px rgba(37,99,235,.09), 0 1px 4px rgba(0,0,0,.06);
      --shadow-lg: 0 12px 40px rgba(37,99,235,.14), 0 2px 8px rgba(0,0,0,.07);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Sora', sans-serif;
      background: var(--gray-50);
      color: var(--gray-800);
    }

    ::-webkit-scrollbar { width: 6px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

    /* ════ TOP BAR ════════════════════ */
    .topbar {
      position: sticky; top: 0; z-index: 100;
      height: 64px;
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: 0 1px 8px rgba(0,0,0,.06);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 36px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .brand-mark i { font-size: 17px; color: white; }
    .brand-text { display: flex; flex-direction: column; gap: 1px; }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 17px; color: var(--gray-900); line-height: 1; }
    .brand-sub  { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue-600); line-height: 1; }

    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .refresh-badge {
      display: flex; align-items: center; gap: 6px;
      padding: 5px 13px; border-radius: 20px;
      background: var(--green-bg); border: 1px solid var(--green-100);
      font-size: 11.5px; color: var(--green); font-weight: 600;
    }
    .refresh-dot {
      width: 7px; height: 7px; border-radius: 50%; background: var(--green);
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%,100% { box-shadow: 0 0 0 0 rgba(22,163,74,.4); }
      50%      { box-shadow: 0 0 0 5px rgba(22,163,74,.0); }
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 12.5px; font-weight: 500; text-decoration: none;
      transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-600); }

    /* ════ PAGE ═══════════════════════ */
    .page { max-width: 1200px; margin: 0 auto; padding: 36px 28px 64px; }

    /* ════ PAGE HEADER ════════════════ */
    .page-header { margin-bottom: 28px; }
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--gray-400); margin-bottom: 10px;
    }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }
    .page-title { font-family: 'Instrument Serif', serif; font-size: clamp(1.6rem,3vw,2.2rem); font-weight: 400; color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-top: 5px; }

    /* ════ PATIENT SELECTOR ═══════════ */
    .selector-card {
      background: var(--white);
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 24px 28px;
      margin-bottom: 28px;
      box-shadow: var(--shadow-sm);
    }
    .selector-top { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .selector-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      display: flex; align-items: center; justify-content: center;
    }
    .selector-icon i { font-size: 17px; color: var(--blue-600); }
    .selector-label { font-size: 14px; font-weight: 700; color: var(--gray-800); }
    .selector-sub   { font-size: 12px; color: var(--gray-400); }

    /* Select2 custom style */
    .select2-container--default .select2-selection--single {
      height: 46px !important;
      background: var(--gray-50) !important;
      border: 1.5px solid var(--gray-200) !important;
      border-radius: 9px !important;
      display: flex !important; align-items: center !important;
      transition: border-color .18s, box-shadow .18s !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default .select2-selection--single:focus {
      border-color: var(--blue-400) !important;
      box-shadow: 0 0 0 3px var(--blue-glow) !important;
      background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--blue-700) !important; font-weight: 600 !important;
      font-family: 'Sora', sans-serif !important; font-size: 13.5px !important;
      line-height: 46px !important; padding-left: 14px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
      color: var(--gray-400) !important; font-weight: 400 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 46px !important; right: 12px !important;
    }
    .select2-dropdown {
      border: 1.5px solid var(--blue-200) !important;
      border-radius: 10px !important;
      box-shadow: var(--shadow-lg) !important;
      font-family: 'Sora', sans-serif !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border: 1.5px solid var(--gray-200) !important;
      border-radius: 7px !important; font-family: 'Sora', sans-serif !important;
      font-size: 13px !important; padding: 8px 12px !important;
    }
    .select2-results__option { font-size: 13.5px !important; padding: 10px 16px !important; color: var(--gray-700) !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background: var(--blue-50) !important; color: var(--blue-700) !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
      background: var(--blue-100) !important;
    }

    /* ════ IDLE STATE ═════════════════ */
    .idle-state {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; padding: 60px 24px; gap: 14px;
      text-align: center;
    }
    .idle-icon {
      width: 72px; height: 72px; border-radius: 50%;
      background: var(--blue-50); border: 2px solid var(--blue-100);
      display: flex; align-items: center; justify-content: center;
    }
    .idle-icon i { font-size: 32px; color: var(--blue-400); }
    .idle-title { font-size: 16px; font-weight: 600; color: var(--gray-700); }
    .idle-sub   { font-size: 13px; color: var(--gray-400); max-width: 320px; }

    /* ════ SECTIONS GRID ══════════════ */
    .sections-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
      display: none;
    }
    .sections-grid.visible { display: grid; }
    .section-full { grid-column: 1 / -1; }

    /* ════ SECTION PANEL ══════════════ */
    .section-panel {
      background: var(--white);
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: box-shadow .2s;
    }
    .section-panel:hover { box-shadow: var(--shadow); }

    .panel-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; border-bottom: 1px solid var(--gray-100);
    }
    .panel-title-wrap { display: flex; align-items: center; gap: 10px; }
    .panel-icon {
      width: 32px; height: 32px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
    }
    .panel-icon i { font-size: 15px; }

    /* Section colour variants */
    .s-blue   .panel-header { background: var(--blue-50);   } .s-blue   .panel-icon { background: var(--blue-100);   } .s-blue   .panel-icon i { color: var(--blue-600);  }
    .s-green  .panel-header { background: var(--green-bg);  } .s-green  .panel-icon { background: var(--green-100);  } .s-green  .panel-icon i { color: var(--green);     }
    .s-violet .panel-header { background: var(--violet-bg); } .s-violet .panel-icon { background: #c4b5fd;            } .s-violet .panel-icon i { color: var(--violet);    }
    .s-amber  .panel-header { background: var(--amber-bg);  } .s-amber  .panel-icon { background: #fde68a;            } .s-amber  .panel-icon i { color: var(--amber);     }
    .s-teal   .panel-header { background: var(--teal-bg);   } .s-teal   .panel-icon { background: #99f6e4;            } .s-teal   .panel-icon i { color: var(--teal);      }

    .panel-name { font-size: 13px; font-weight: 700; color: var(--gray-800); }

    .count-badge {
      font-size: 11px; font-weight: 700;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-500); border-radius: 20px; padding: 2px 10px;
    }

    /* ════ TABLE ══════════════════════ */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 12.5px; min-width: 400px; }
    thead th {
      padding: 10px 14px; text-align: left;
      font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-500); background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200); white-space: nowrap;
    }
    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .12s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--blue-50); }
    td { padding: 11px 14px; color: var(--gray-700); vertical-align: middle; }
    td.num { font-size: 11.5px; color: var(--gray-400); font-weight: 600; }
    td.wrap { max-width: 200px; white-space: normal; word-break: break-word; font-size: 12px; }

    /* Status pill */
    .status-pill {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 10.5px; font-weight: 700; padding: 3px 10px;
      border-radius: 20px; white-space: nowrap;
    }
    .status-pill::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }
    .pill-pending  { background: var(--amber-bg);  color: var(--amber); border: 1px solid #fde68a;  } .pill-pending::before  { background: var(--amber); }
    .pill-complete { background: var(--green-bg);  color: var(--green); border: 1px solid var(--green-100); } .pill-complete::before { background: var(--green); }
    .pill-default  { background: var(--gray-100); color: var(--gray-500); border: 1px solid var(--gray-200); } .pill-default::before  { background: var(--gray-400); }

    /* ════ EMPTY STATE IN PANEL ═══════ */
    .panel-empty {
      display: flex; align-items: center; gap: 10px;
      padding: 20px 20px; color: var(--gray-400); font-size: 13px;
    }
    .panel-empty i { font-size: 18px; opacity: .4; }

    /* ════ LOADING ════════════════════ */
    .panel-loading {
      display: flex; align-items: center; gap: 10px;
      padding: 20px 20px; color: var(--blue-500); font-size: 13px;
    }
    .spinner {
      width: 16px; height: 16px; border-radius: 50%;
      border: 2px solid var(--blue-200);
      border-top-color: var(--blue-600);
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ════ RESPONSIVE ═════════════════ */
    @media (max-width: 900px) {
      .sections-grid.visible { grid-template-columns: 1fr; }
      .section-full { grid-column: auto; }
    }
    @media (max-width: 600px) {
      .topbar { padding: 0 16px; }
      .refresh-badge span { display: none; }
      .page { padding: 20px 14px 48px; }
      .selector-card { padding: 18px; }
    }
  </style>
</head>
<body>

<!-- ════ TOP BAR ════════════════════════ -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-mark"><i class="bi bi-hospital"></i></div>
    <div class="brand-text">
      <span class="brand-name">Angelora</span>
      <span class="brand-sub">EMR Viewer</span>
    </div>
  </div>
  <div class="topbar-right">
    <div class="refresh-badge" id="refreshBadge" style="display:none;">
      <span class="refresh-dot"></span>
      <span>Auto-refreshing</span>
    </div>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<!-- ════ PAGE ════════════════════════════ -->
<div class="page">

  <!-- Header -->
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
      <i class="bi bi-chevron-right"></i>
      <span>Patient Orders Viewer</span>
    </div>
    <h1 class="page-title">Patient <em>Orders Viewer</em></h1>
    <p class="page-sub">Select a patient to view their lab, nursing, pharmacy, drug chart, and consultation records.</p>
  </div>

  <!-- Patient Selector -->
  <div class="selector-card">
    <div class="selector-top">
      <div class="selector-icon"><i class="bi bi-person-badge-fill"></i></div>
      <div>
        <div class="selector-label">Select Patient</div>
        <div class="selector-sub">Search by name to load all orders instantly</div>
      </div>
    </div>
    <select id="patientSelect" style="width:100%;">
      <option value=""></option>
      <?php foreach ($patients as $p): ?>
        <option value="<?= htmlspecialchars($p['patient_id']) ?>">
          <?= htmlspecialchars($p['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Idle state (before patient selected) -->
  <div id="idleState">
    <div class="idle-state">
      <div class="idle-icon"><i class="bi bi-search-heart"></i></div>
      <div class="idle-title">No patient selected</div>
      <div class="idle-sub">Search and select a patient above to view their full order history.</div>
    </div>
  </div>

  <!-- Sections grid (shown after patient selected) -->
  <div class="sections-grid" id="sectionsGrid">

    <!-- Lab Orders -->
    <div class="section-panel s-blue">
      <div class="panel-header">
        <div class="panel-title-wrap">
          <div class="panel-icon"><i class="bi bi-eyedropper"></i></div>
          <span class="panel-name">Lab Orders</span>
        </div>
        <span class="count-badge" id="labCount">—</span>
      </div>
      <div id="labContent">
        <div class="panel-loading"><div class="spinner"></div> Loading…</div>
      </div>
    </div>

    <!-- Nursing Orders -->
    <div class="section-panel s-green">
      <div class="panel-header">
        <div class="panel-title-wrap">
          <div class="panel-icon"><i class="bi bi-clipboard2-heart-fill"></i></div>
          <span class="panel-name">Nursing Orders</span>
        </div>
        <span class="count-badge" id="nursingCount">—</span>
      </div>
      <div id="nursingContent">
        <div class="panel-loading"><div class="spinner"></div> Loading…</div>
      </div>
    </div>

    <!-- Pharmacy Orders -->
    <div class="section-panel s-violet">
      <div class="panel-header">
        <div class="panel-title-wrap">
          <div class="panel-icon"><i class="bi bi-capsule-pill"></i></div>
          <span class="panel-name">Pharmacy Orders</span>
        </div>
        <span class="count-badge" id="pharmacyCount">—</span>
      </div>
      <div id="pharmacyContent">
        <div class="panel-loading"><div class="spinner"></div> Loading…</div>
      </div>
    </div>

    <!-- Drug Chart -->
    <div class="section-panel s-amber">
      <div class="panel-header">
        <div class="panel-title-wrap">
          <div class="panel-icon"><i class="bi bi-prescription2"></i></div>
          <span class="panel-name">Drug Chart / Prescription</span>
        </div>
        <span class="count-badge" id="drugCount">—</span>
      </div>
      <div id="drugChartContent">
        <div class="panel-loading"><div class="spinner"></div> Loading…</div>
      </div>
    </div>

    <!-- Consultations (full width) -->
    <div class="section-panel s-teal section-full">
      <div class="panel-header">
        <div class="panel-title-wrap">
          <div class="panel-icon"><i class="bi bi-stethoscope"></i></div>
          <span class="panel-name">Consultations</span>
        </div>
        <span class="count-badge" id="consultCount">—</span>
      </div>
      <div id="consultationContent">
        <div class="panel-loading"><div class="spinner"></div> Loading…</div>
      </div>
    </div>

  </div><!-- /sections-grid -->

</div><!-- /page -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  let selectedPatientId = "";

  $(document).ready(function () {
    $('#patientSelect').select2({
      placeholder: "Search and select a patient…",
      allowClear: true,
      width: '100%'
    });

    $('#patientSelect').on('change', function () {
      selectedPatientId = $(this).val();
      if (selectedPatientId) {
        document.getElementById('idleState').style.display = 'none';
        document.getElementById('sectionsGrid').classList.add('visible');
        document.getElementById('refreshBadge').style.display = 'flex';
        loadOrders(selectedPatientId);
      } else {
        document.getElementById('idleState').style.display = '';
        document.getElementById('sectionsGrid').classList.remove('visible');
        document.getElementById('refreshBadge').style.display = 'none';
      }
    });
  });

  function safeVal(v) {
    return (v !== null && v !== undefined && v !== '') ? v : '—';
  }

  function statusPill(s) {
    if (!s) return '<span class="status-pill pill-default">—</span>';
    const lo = s.toLowerCase();
    const cls = lo === 'pending' ? 'pill-pending' : lo === 'complete' || lo === 'completed' ? 'pill-complete' : 'pill-default';
    return `<span class="status-pill ${cls}">${s}</span>`;
  }

  function setBadge(id, count) {
    document.getElementById(id).textContent = count + ' record' + (count !== 1 ? 's' : '');
  }

  function setEmpty(id, msg) {
    document.getElementById(id).innerHTML =
      `<div class="panel-empty"><i class="bi bi-inbox"></i> ${msg}</div>`;
  }

  function loadOrders(patientId) {
    if (!patientId) return;

    fetch("fetch_patient_orders.php?patient_id=" + patientId)
      .then(r => r.json())
      .then(data => {
        renderLab(data.lab_orders || []);
        renderNursing(data.nursing_orders || []);
        renderPharmacy(data.pharmacy_orders || []);
        renderDrugChart(data.drug_chart || []);
      })
      .catch(() => {
        ['labContent','nursingContent','pharmacyContent','drugChartContent'].forEach(id =>
          setEmpty(id, 'Failed to load data.')
        );
      });

    fetch("fetch_patient_consultations.php?patient_id=" + patientId)
      .then(r => r.json())
      .then(data => renderConsultations(data.consultations || []))
      .catch(() => setEmpty('consultationContent', 'Failed to load data.'));
  }

  function renderLab(data) {
    setBadge('labCount', data.length);
    if (!data.length) { setEmpty('labContent', 'No lab orders found.'); return; }
    let html = `<div class="tbl-wrap"><table><thead><tr>
      <th>#</th><th>Test Name</th><th>Notes</th>
    </tr></thead><tbody>`;
    data.forEach((r, i) => {
      html += `<tr>
        <td class="num">${i+1}</td>
        <td>${safeVal(r.test_name)}</td>
        <td class="wrap">${safeVal(r.lab_notes)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('labContent').innerHTML = html;
  }

  function renderNursing(data) {
    setBadge('nursingCount', data.length);
    if (!data.length) { setEmpty('nursingContent', 'No nursing orders found.'); return; }
    let html = `<div class="tbl-wrap"><table><thead><tr>
      <th>#</th><th>Procedure</th><th>Notes</th>
    </tr></thead><tbody>`;
    data.forEach((r, i) => {
      html += `<tr>
        <td class="num">${i+1}</td>
        <td>${safeVal(r.procedure_name)}</td>
        <td class="wrap">${safeVal(r.notes)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('nursingContent').innerHTML = html;
  }

  function renderPharmacy(data) {
    setBadge('pharmacyCount', data.length);
    if (!data.length) { setEmpty('pharmacyContent', 'No pharmacy orders found.'); return; }
    let html = `<div class="tbl-wrap"><table><thead><tr>
      <th>#</th><th>Medicine</th><th>Dosage</th><th>Notes</th><th>Status</th>
    </tr></thead><tbody>`;
    data.forEach((r, i) => {
      html += `<tr>
        <td class="num">${i+1}</td>
        <td>${safeVal(r.medicine_name)}</td>
        <td>${safeVal(r.dosage)}</td>
        <td class="wrap">${safeVal(r.notes)}</td>
        <td>${statusPill(r.status)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('pharmacyContent').innerHTML = html;
  }

  function renderDrugChart(data) {
    setBadge('drugCount', data.length);
    if (!data.length) { setEmpty('drugChartContent', 'No drug chart found.'); return; }
    let html = `<div class="tbl-wrap"><table><thead><tr>
      <th>#</th><th>Drug Name</th><th>Dosage</th><th>Route</th>
      <th>Frequency</th><th>Duration</th><th>Start</th><th>End</th>
      <th>Prescribed By</th><th>Notes</th>
    </tr></thead><tbody>`;
    data.forEach((r, i) => {
      html += `<tr>
        <td class="num">${i+1}</td>
        <td>${safeVal(r.drug_name)}</td>
        <td>${safeVal(r.dosage)}</td>
        <td>${safeVal(r.route)}</td>
        <td>${safeVal(r.frequency)}</td>
        <td>${safeVal(r.duration)}</td>
        <td>${safeVal(r.start_date)}</td>
        <td>${safeVal(r.end_date)}</td>
        <td>${safeVal(r.prescribed_by)}</td>
        <td class="wrap">${safeVal(r.notes)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('drugChartContent').innerHTML = html;
  }

  function renderConsultations(data) {
    setBadge('consultCount', data.length);
    if (!data.length) { setEmpty('consultationContent', 'No consultations found.'); return; }
    let html = `<div class="tbl-wrap"><table><thead><tr>
      <th>#</th><th>Date</th><th>O₂ Sat</th><th>Pain</th><th>BMI</th>
      <th>Chief Complaint</th><th>Diagnosis</th><th>Treatment</th>
    </tr></thead><tbody>`;
    data.forEach((r, i) => {
      html += `<tr>
        <td class="num">${i+1}</td>
        <td>${safeVal(r.consultation_date)}</td>
        <td>${safeVal(r.oxygen_saturation)}</td>
        <td>${safeVal(r.pain_level)}</td>
        <td>${safeVal(r.bmi)}</td>
        <td class="wrap">${safeVal(r.chief_complaint)}</td>
        <td class="wrap">${safeVal(r.diagnosis)}</td>
        <td class="wrap">${safeVal(r.treatment_plan)}</td>
      </tr>`;
    });
    html += '</tbody></table></div>';
    document.getElementById('consultationContent').innerHTML = html;
  }

  // Auto-refresh every 5 seconds
  setInterval(() => {
    if (selectedPatientId) loadOrders(selectedPatientId);
  }, 5000);
</script>
</body>
</html>