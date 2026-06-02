<?php
include "db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/auth.php';
require '../db.php';

if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: ../login.php');
    exit();
}

$staff_name = $_SESSION['user']['full_name'] ?? 'Receptionist';
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
      --blue-900:   #0f2d6b;
      --blue-800:   #1a3f8f;
      --blue-700:   #1d4ed8;
      --blue-600:   #2563eb;
      --blue-500:   #3b82f6;
      --blue-400:   #60a5fa;
      --blue-300:   #93c5fd;
      --blue-200:   #bfdbfe;
      --blue-100:   #dbeafe;
      --blue-50:    #eff6ff;
      --white:      #ffffff;
      --gray-50:    #f8fafc;
      --gray-100:   #f1f5f9;
      --gray-200:   #e2e8f0;
      --gray-300:   #cbd5e1;
      --gray-400:   #94a3b8;
      --gray-500:   #64748b;
      --gray-600:   #475569;
      --gray-700:   #334155;
      --gray-900:   #0f172a;
      --green-500:  #10b981;
      --green-50:   #ecfdf5;
      --green-100:  #d1fae5;
      --green-700:  #047857;
      --amber-500:  #f59e0b;
      --amber-50:   #fffbeb;
      --amber-100:  #fef3c7;
      --amber-700:  #b45309;
      --violet-500: #8b5cf6;
      --violet-50:  #f5f3ff;
      --violet-100: #ede9fe;
      --violet-700: #6d28d9;
      --red-500:    #ef4444;
      --red-50:     #fef2f2;
      --red-100:    #fee2e2;
      --shadow-sm:  0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md:  0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg:  0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
      --blue-glow:  rgba(37,99,235,.12);
      --radius:     12px;
    }

    html, body {
      min-height: 100vh;
      font-family: 'Sora', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 10px; }

    /* ── Top Bar ── */
    .topbar {
      position: sticky; top: 0; z-index: 200;
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
      height: 66px;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 36px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 12px rgba(37,99,235,.28);
    }
    .brand-icon i { font-size: 18px; color: white; }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 18px; color: var(--blue-800); }
    .brand-sep  { color: var(--gray-300); margin: 0 2px; }
    .brand-page { font-size: 13px; color: var(--blue-600); font-weight: 600; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .topbar-date {
      font-size: 12px; color: var(--gray-400);
      padding: 5px 12px; background: var(--gray-100);
      border-radius: 999px; border: 1px solid var(--gray-200);
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: 12.5px; font-weight: 600; text-decoration: none;
      transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── Page ── */
    .page { max-width: 1300px; margin: 0 auto; padding: 36px 28px 60px; }

    /* ── Page Header ── */
    .page-header { margin-bottom: 28px; }
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--gray-400); margin-bottom: 10px;
    }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; font-weight: 500; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; color: var(--gray-300); }
    .page-title { font-family: 'Instrument Serif', serif; font-size: 2rem; font-weight: 400; color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-400); margin-top: 5px; }

    /* ── Selector Card ── */
    .selector-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 22px 28px;
      margin-bottom: 24px;
      display: flex;
      align-items: flex-end;
      gap: 16px;
      flex-wrap: wrap;
      box-shadow: var(--shadow-sm);
    }
    .selector-field { flex: 1; min-width: 260px; }
    .selector-label {
      font-size: 11px; font-weight: 700; letter-spacing: .09em;
      text-transform: uppercase; color: var(--gray-500);
      margin-bottom: 10px; display: flex; align-items: center; gap: 7px;
    }
    .selector-label i { color: var(--blue-500); }

    /* ── Select2 Light Theme ── */
    .select2-container { z-index: 10; }
    .select2-container--default .select2-selection--single {
      background: var(--gray-50) !important;
      border: 1.5px solid var(--gray-200) !important;
      border-radius: 10px !important;
      height: 46px !important;
      display: flex !important; align-items: center !important;
      transition: border-color .2s, box-shadow .2s !important;
    }
    .select2-container--default .select2-selection--single:hover {
      border-color: var(--blue-300) !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: var(--blue-500) !important;
      background: var(--white) !important;
      box-shadow: 0 0 0 3px var(--blue-glow) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--blue-700) !important; font-weight: 600 !important;
      font-family: 'Sora', sans-serif !important; font-size: 14px !important;
      line-height: 46px !important; padding-left: 16px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
      color: var(--gray-400) !important; font-weight: 400 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 46px !important; right: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: var(--gray-400) transparent transparent transparent !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
      border-color: transparent transparent var(--gray-400) transparent !important;
    }
    .select2-dropdown {
      background: var(--white) !important;
      border: 1.5px solid var(--blue-200) !important;
      border-radius: 10px !important;
      box-shadow: var(--shadow-lg) !important;
      font-family: 'Sora', sans-serif !important;
      overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      background: var(--gray-50) !important;
      border: 1px solid var(--gray-200) !important;
      border-radius: 7px !important;
      color: var(--gray-700) !important;
      font-family: 'Sora', sans-serif !important;
      font-size: 13px !important;
      padding: 8px 12px !important;
      outline: none !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
      border-color: var(--blue-400) !important;
      box-shadow: 0 0 0 2px var(--blue-glow) !important;
    }
    .select2-results__option {
      color: var(--gray-700) !important; font-size: 13.5px !important; padding: 10px 16px !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background: var(--blue-50) !important; color: var(--blue-700) !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
      background: var(--blue-100) !important; color: var(--blue-800) !important; font-weight: 600 !important;
    }

    /* ── Refresh Badge ── */
    .selector-meta { display: flex; align-items: center; gap: 16px; flex-shrink: 0; }
    .refresh-badge {
      display: flex; align-items: center; gap: 8px;
      padding: 9px 16px;
      background: var(--gray-100);
      border: 1px solid var(--gray-200);
      border-radius: 999px;
      font-size: 12px; color: var(--gray-500); font-weight: 500;
    }
    .refresh-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--gray-300); transition: background .3s;
    }
    .refresh-dot.active {
      background: var(--green-500);
      box-shadow: 0 0 0 3px rgba(16,185,129,.2);
      animation: pulse-dot 2s ease-in-out infinite;
    }
    @keyframes pulse-dot {
      0%,100% { box-shadow: 0 0 0 3px rgba(16,185,129,.2); }
      50%      { box-shadow: 0 0 0 6px rgba(16,185,129,.07); }
    }

    /* ── Idle State ── */
    .state-idle {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; padding: 80px 20px; gap: 14px;
      color: var(--gray-400); text-align: center;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); box-shadow: var(--shadow-sm);
    }
    .state-idle i { font-size: 54px; color: var(--blue-200); }
    .state-idle h3 { font-size: 16px; font-weight: 600; color: var(--gray-700); }
    .state-idle p { font-size: 13px; max-width: 320px; color: var(--gray-400); }

    /* ── Sections Layout ── */
    #ordersView { display: none; }
    #ordersView.visible { display: block; }

    .sections-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }
    .section-full { grid-column: 1 / -1; }

    /* ── Section Panel ── */
    .section-panel {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }

    .section-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px;
      border-bottom: 1px solid var(--gray-100);
    }
    .section-title-wrap { display: flex; align-items: center; gap: 10px; }
    .section-icon {
      width: 34px; height: 34px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
    }
    .section-icon i { font-size: 16px; }
    .section-name { font-size: 13.5px; font-weight: 700; color: var(--gray-900); }

    /* Panel colour variants */
    .panel-lab     .section-icon { background: var(--blue-50);   } .panel-lab    .section-icon i { color: var(--blue-600);   }
    .panel-lab     .section-header { background: #fafcff; border-bottom-color: var(--blue-100); }
    .panel-nursing .section-icon { background: var(--green-50);  } .panel-nursing .section-icon i { color: var(--green-500); }
    .panel-nursing .section-header { background: #fafffe; border-bottom-color: var(--green-100); }
    .panel-pharmacy .section-icon { background: var(--violet-50); } .panel-pharmacy .section-icon i { color: var(--violet-500); }
    .panel-pharmacy .section-header { background: #fdfaff; border-bottom-color: var(--violet-100); }
    .panel-drug    .section-icon { background: var(--amber-50);  } .panel-drug   .section-icon i { color: var(--amber-500); }
    .panel-drug    .section-header { background: #fffdf5; border-bottom-color: var(--amber-100); }
    .panel-consult .section-icon { background: var(--blue-50);   } .panel-consult .section-icon i { color: var(--blue-700); }
    .panel-consult .section-header { background: #fafcff; border-bottom-color: var(--blue-100); }

    .record-count {
      font-size: 11px; color: var(--gray-500); font-weight: 600;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 999px; padding: 3px 12px;
    }

    /* ── Table ── */
    .table-wrap { overflow-x: auto; }
    .data-table {
      width: 100%; border-collapse: collapse;
      font-size: 12.5px; min-width: 480px;
    }
    .data-table thead th {
      padding: 10px 16px;
      text-align: left;
      font-size: 10px; font-weight: 700;
      letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-400); background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200);
      white-space: nowrap;
    }
    .data-table tbody tr {
      border-bottom: 1px solid var(--gray-100);
      transition: background .12s;
    }
    .data-table tbody tr:last-child { border-bottom: none; }
    .data-table tbody tr:hover { background: var(--blue-50); }
    .data-table td {
      padding: 11px 16px; color: var(--gray-700);
      vertical-align: middle; line-height: 1.5;
    }
    .data-table td.muted { color: var(--gray-400); }
    .data-table td.text-wrap-cell { white-space: normal; word-break: break-word; max-width: 220px; }
    .data-table td.mono {
      font-family: 'Courier New', monospace; font-size: 11.5px;
      color: var(--gray-400); font-weight: 600;
    }

    /* ── Status Pills ── */
    .status-pill {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 3px 11px; border-radius: 999px;
      font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .status-pill.pending  { background: var(--amber-50);  border: 1px solid var(--amber-100); color: var(--amber-700); }
    .status-pill.complete { background: var(--green-50);  border: 1px solid var(--green-100); color: var(--green-700); }
    .status-pill.default  { background: var(--gray-100);  border: 1px solid var(--gray-200);  color: var(--gray-500); }

    /* ── No Data ── */
    .no-data {
      padding: 36px 20px; text-align: center;
      color: var(--gray-400); font-size: 13px;
    }
    .no-data i { display: block; font-size: 30px; margin-bottom: 8px; color: var(--gray-300); }

    /* ── Loading Skeleton ── */
    .skeleton {
      padding: 32px 20px; text-align: center;
      color: var(--gray-400); font-size: 13px;
      display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .skeleton i { color: var(--blue-400); }
    .spin { animation: spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .sections-grid { grid-template-columns: 1fr; }
      .section-full { grid-column: auto; }
    }
    @media (max-width: 600px) {
      .topbar { padding: 0 16px; }
      .topbar-date { display: none; }
      .page { padding: 20px 14px 48px; }
      .selector-card { padding: 16px; }
    }
  </style>
</head>
<body>

<!-- ══ TOP BAR ══ -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="brand-name">Angelora</span>
    <span class="brand-sep">·</span>
    <span class="brand-page">Patient Orders</span>
  </div>
  <div class="topbar-right">
    <span class="topbar-date"><i class="bi bi-calendar3" style="margin-right:5px"></i><?= date('l, d F Y') ?></span>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<!-- ══ PAGE ══ -->
<div class="page">

  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php"><i class="bi bi-house-fill"></i> Dashboard</a>
      <i class="bi bi-chevron-right"></i>
      <span>Patient Orders Viewer</span>
    </div>
    <h1 class="page-title">Patient <em>Orders</em> Viewer</h1>
    <p class="page-sub">Select a patient to view their lab, nursing, pharmacy, and consultation records.</p>
  </div>

  <!-- Selector -->
  <div class="selector-card">
    <div class="selector-field">
      <div class="selector-label">
        <i class="bi bi-person-badge-fill"></i> Select Patient
      </div>
      <select id="patientSelect" style="width:100%;">
        <option value=""></option>
        <?php foreach ($patients as $p): ?>
          <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="selector-meta">
      <div class="refresh-badge">
        <span class="refresh-dot" id="refreshDot"></span>
        <span id="refreshLabel">Auto-refresh every 5s</span>
      </div>
    </div>
  </div>

  <!-- Idle State -->
  <div class="state-idle" id="idleState">
    <i class="bi bi-person-search"></i>
    <h3>No Patient Selected</h3>
    <p>Use the dropdown above to search and select a patient to load their records.</p>
  </div>

  <!-- Orders View -->
  <div id="ordersView">
    <div class="sections-grid">

      <!-- Lab Orders -->
      <div class="section-panel panel-lab">
        <div class="section-header">
          <div class="section-title-wrap">
            <div class="section-icon"><i class="bi bi-eyedropper"></i></div>
            <span class="section-name">Lab Orders</span>
          </div>
          <span class="record-count" id="labCount">—</span>
        </div>
        <div id="labContent"><div class="skeleton"><i class="bi bi-arrow-clockwise spin"></i> Loading…</div></div>
      </div>

      <!-- Nursing Orders -->
      <div class="section-panel panel-nursing">
        <div class="section-header">
          <div class="section-title-wrap">
            <div class="section-icon"><i class="bi bi-clipboard2-heart"></i></div>
            <span class="section-name">Nursing Orders</span>
          </div>
          <span class="record-count" id="nursingCount">—</span>
        </div>
        <div id="nursingContent"><div class="skeleton"><i class="bi bi-arrow-clockwise spin"></i> Loading…</div></div>
      </div>

      <!-- Pharmacy Orders -->
      <div class="section-panel panel-pharmacy">
        <div class="section-header">
          <div class="section-title-wrap">
            <div class="section-icon"><i class="bi bi-capsule"></i></div>
            <span class="section-name">Pharmacy Orders</span>
          </div>
          <span class="record-count" id="pharmacyCount">—</span>
        </div>
        <div id="pharmacyContent"><div class="skeleton"><i class="bi bi-arrow-clockwise spin"></i> Loading…</div></div>
      </div>

      <!-- Drug Chart -->
      <div class="section-panel panel-drug">
        <div class="section-header">
          <div class="section-title-wrap">
            <div class="section-icon"><i class="bi bi-journal-medical"></i></div>
            <span class="section-name">Drug Chart / Prescription</span>
          </div>
          <span class="record-count" id="drugCount">—</span>
        </div>
        <div id="drugChartContent"><div class="skeleton"><i class="bi bi-arrow-clockwise spin"></i> Loading…</div></div>
      </div>

      <!-- Consultations (full width) -->
      <div class="section-panel panel-consult section-full">
        <div class="section-header">
          <div class="section-title-wrap">
            <div class="section-icon"><i class="bi bi-clipboard2-pulse"></i></div>
            <span class="section-name">Consultations</span>
          </div>
          <span class="record-count" id="consultCount">—</span>
        </div>
        <div id="consultationContent"><div class="skeleton"><i class="bi bi-arrow-clockwise spin"></i> Loading…</div></div>
      </div>

    </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let selectedPatientId = "";
const refreshDot  = document.getElementById('refreshDot');
const idleState   = document.getElementById('idleState');
const ordersView  = document.getElementById('ordersView');

$(document).ready(function () {
  $('#patientSelect').select2({
    placeholder: "Search and select a patient…",
    allowClear: true,
    width: '100%',
  });

  $('#patientSelect').on('change', function () {
    selectedPatientId = $(this).val();
    if (selectedPatientId) {
      idleState.style.display = 'none';
      ordersView.classList.add('visible');
      refreshDot.classList.add('active');
      loadOrders(selectedPatientId);
    } else {
      idleState.style.display = '';
      ordersView.classList.remove('visible');
      refreshDot.classList.remove('active');
    }
  });
});

function loadOrders(id) {
  fetch("fetch_patient_orders.php?patient_id=" + id)
    .then(r => r.json())
    .then(data => {
      renderLabOrders(data.lab_orders       || []);
      renderNursingOrders(data.nursing_orders   || []);
      renderPharmacyOrders(data.pharmacy_orders || []);
      renderDrugChart(data.drug_chart        || []);
    })
    .catch(() => {
      ['labContent','nursingContent','pharmacyContent','drugChartContent'].forEach(i => noData(i, 'Could not load data.'));
    });

  fetch("fetch_patient_consultations.php?patient_id=" + id)
    .then(r => r.json())
    .then(data => renderConsultations(data.consultations || []))
    .catch(() => noData('consultationContent', 'Could not load consultations.'));
}

function noData(elId, msg) {
  document.getElementById(elId).innerHTML =
    `<div class="no-data"><i class="bi bi-inbox"></i>${msg || 'No records found.'}</div>`;
}

function setCount(elId, n) {
  document.getElementById(elId).textContent = n + ' record' + (n !== 1 ? 's' : '');
}

function safeVal(v) {
  return (v !== null && v !== undefined && v !== '')
    ? v
    : '<span style="color:var(--gray-300)">—</span>';
}

function statusPill(status) {
  if (!status) return '<span class="status-pill default">—</span>';
  const cls = status.toLowerCase().includes('complet') ? 'complete'
            : status.toLowerCase().includes('pending')  ? 'pending' : 'default';
  return `<span class="status-pill ${cls}">${status}</span>`;
}

function renderLabOrders(data) {
  setCount('labCount', data.length);
  if (!data.length) { noData('labContent'); return; }
  let h = `<div class="table-wrap"><table class="data-table"><thead><tr>
    <th>#</th><th>Test Name</th><th>Notes</th>
  </tr></thead><tbody>`;
  data.forEach(r => {
    h += `<tr>
      <td class="mono">${r.id}</td>
      <td>${safeVal(r.test_name)}</td>
      <td class="text-wrap-cell muted">${safeVal(r.lab_notes)}</td>
    </tr>`;
  });
  h += '</tbody></table></div>';
  document.getElementById('labContent').innerHTML = h;
}

function renderNursingOrders(data) {
  setCount('nursingCount', data.length);
  if (!data.length) { noData('nursingContent'); return; }
  let h = `<div class="table-wrap"><table class="data-table"><thead><tr>
    <th>#</th><th>Procedure</th><th>Notes</th>
  </tr></thead><tbody>`;
  data.forEach(r => {
    h += `<tr>
      <td class="mono">${r.id}</td>
      <td>${safeVal(r.procedure_name)}</td>
      <td class="text-wrap-cell muted">${safeVal(r.notes)}</td>
    </tr>`;
  });
  h += '</tbody></table></div>';
  document.getElementById('nursingContent').innerHTML = h;
}

function renderPharmacyOrders(data) {
  setCount('pharmacyCount', data.length);
  if (!data.length) { noData('pharmacyContent'); return; }
  let h = `<div class="table-wrap"><table class="data-table"><thead><tr>
    <th>#</th><th>Medicine</th><th>Dosage</th><th>Notes</th><th>Status</th>
  </tr></thead><tbody>`;
  data.forEach(r => {
    h += `<tr>
      <td class="mono">${r.id}</td>
      <td>${safeVal(r.medicine_name)}</td>
      <td>${safeVal(r.dosage)}</td>
      <td class="text-wrap-cell muted">${safeVal(r.notes)}</td>
      <td>${statusPill(r.status)}</td>
    </tr>`;
  });
  h += '</tbody></table></div>';
  document.getElementById('pharmacyContent').innerHTML = h;
}

function renderDrugChart(data) {
  setCount('drugCount', data.length);
  if (!data.length) { noData('drugChartContent'); return; }
  let h = `<div class="table-wrap"><table class="data-table"><thead><tr>
    <th>#</th><th>Drug</th><th>Dosage</th><th>Route</th><th>Frequency</th>
    <th>Duration</th><th>Start</th><th>End</th><th>Prescribed By</th><th>Notes</th>
  </tr></thead><tbody>`;
  data.forEach(r => {
    h += `<tr>
      <td class="mono">${r.id}</td>
      <td>${safeVal(r.drug_name)}</td>
      <td>${safeVal(r.dosage)}</td>
      <td>${safeVal(r.route)}</td>
      <td>${safeVal(r.frequency)}</td>
      <td>${safeVal(r.duration)}</td>
      <td class="muted">${safeVal(r.start_date)}</td>
      <td class="muted">${safeVal(r.end_date)}</td>
      <td>${safeVal(r.prescribed_by)}</td>
      <td class="text-wrap-cell muted">${safeVal(r.notes)}</td>
    </tr>`;
  });
  h += '</tbody></table></div>';
  document.getElementById('drugChartContent').innerHTML = h;
}

function renderConsultations(data) {
  setCount('consultCount', data.length);
  if (!data.length) { noData('consultationContent'); return; }
  let h = `<div class="table-wrap"><table class="data-table"><thead><tr>
    <th>#</th><th>Date</th><th>O₂ Sat</th><th>Pain</th><th>BMI</th>
    <th>Chief Complaint</th><th>Diagnosis</th><th>Treatment Plan</th>
  </tr></thead><tbody>`;
  data.forEach(r => {
    h += `<tr>
      <td class="mono">${r.consultation_id}</td>
      <td class="muted">${safeVal(r.consultation_date)}</td>
      <td>${safeVal(r.oxygen_saturation)}</td>
      <td>${safeVal(r.pain_level)}</td>
      <td>${safeVal(r.bmi)}</td>
      <td class="text-wrap-cell">${safeVal(r.chief_complaint)}</td>
      <td class="text-wrap-cell">${safeVal(r.diagnosis)}</td>
      <td class="text-wrap-cell muted">${safeVal(r.treatment_plan)}</td>
    </tr>`;
  });
  h += '</tbody></table></div>';
  document.getElementById('consultationContent').innerHTML = h;
}

setInterval(() => {
  if (selectedPatientId) loadOrders(selectedPatientId);
}, 5000);
</script>
</body>
</html>