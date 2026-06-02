<?php
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

require '../includes/auth.php';
require '../db.php';

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prescription_id'])) {
    $delete_id = (int)$_POST['delete_prescription_id'];
    $deleteStmt = $pdo->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
    if ($deleteStmt->execute([$delete_id])) {
        $message = 'Prescription deleted successfully.';
        $msgType = 'success';
    } else {
        $message = 'Failed to delete prescription.';
        $msgType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['patient_id'], $_POST['medicine_ids'])) {
    $patient_id  = $_POST['patient_id'];
    $medicine_ids = $_POST['medicine_ids'];

    foreach ($medicine_ids as $medicine_id) {
        $checkStmt = $pdo->prepare("
            SELECT * FROM prescriptions
            WHERE patient_id = ? AND medicine_id = ?
            AND (notes IS NULL OR notes NOT LIKE 'Administered on%')
        ");
        $checkStmt->execute([$patient_id, $medicine_id]);
        $prescription = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($prescription) {
            $note = "Administered on " . date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE prescriptions SET notes = ?, prescription_date = NOW(), created_at = NOW() WHERE prescription_id = ?");
            $stmt->execute([$note, $prescription['prescription_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, medicine_id, notes, prescription_date, created_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$patient_id, $medicine_id, "Administered on " . date('Y-m-d H:i:s')]);
        }
    }
    $message = 'Medication(s) saved and administered successfully.';
    $msgType = 'success';
}

$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$medicines = $pdo->query("SELECT medicine_id, medicine_name FROM medicines ORDER BY medicine_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$rows = $pdo->query("
    SELECT p.prescription_id, pt.patient_id, pt.full_name AS patient_name,
           m.medicine_name, p.notes, p.prescription_date
    FROM prescriptions p
    JOIN patients pt ON p.patient_id = pt.patient_id
    JOIN medicines m  ON p.medicine_id = m.medicine_id
    WHERE p.notes LIKE 'Administered on%'
    ORDER BY pt.full_name ASC, p.prescription_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$administered = [];
foreach ($rows as $row) {
    $pid = $row['patient_id'];
    if (!isset($administered[$pid])) {
        $administered[$pid] = ['patient_name' => $row['patient_name'], 'medicines' => []];
    }
    $administered[$pid]['medicines'][] = [
        'medicine_name'   => $row['medicine_name'],
        'notes'           => $row['notes'],
        'date'            => $row['prescription_date'],
        'prescription_id' => $row['prescription_id'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administer Medication — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900: #0f2d6b; --blue-800: #1a3f8f; --blue-700: #1d4ed8;
      --blue-600: #2563eb; --blue-500: #3b82f6; --blue-400: #60a5fa;
      --blue-300: #93c5fd; --blue-200: #bfdbfe; --blue-100: #dbeafe; --blue-50: #eff6ff;
      --white: #ffffff; --gray-50: #f8fafc; --gray-100: #f1f5f9;
      --gray-200: #e2e8f0; --gray-300: #cbd5e1; --gray-400: #94a3b8;
      --gray-500: #64748b; --gray-700: #334155; --gray-900: #0f172a;
      --green-600: #059669; --green-500: #10b981; --green-50: #ecfdf5;
      --green-100: #d1fae5; --green-700: #047857;
      --red-600: #dc2626; --red-50: #fef2f2; --red-100: #fee2e2; --red-700: #b91c1c;
      --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --violet-600: #7c3aed; --violet-50: #f5f3ff; --violet-100: #ede9fe;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
      --blue-glow: rgba(37,99,235,.12);
      --radius: 14px;
    }

    html, body {
      min-height: 100vh;
      font-family: 'Sora', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }
    body::before {
      content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 600px 400px at 5% 10%, rgba(37,99,235,.05) 0%, transparent 70%),
        radial-gradient(ellipse 500px 350px at 95% 90%, rgba(96,165,250,.04) 0%, transparent 70%);
    }
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 4px; }

    /* ── TOPBAR ── */
    .topbar {
      position: sticky; top: 0; z-index: 100;
      background: var(--white); border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm); height: 62px;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 32px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 11px; }
    .brand-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.25);
    }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 1.05rem; color: var(--blue-800); }
    .brand-sep  { color: var(--gray-300); margin: 0 2px; }
    .brand-page { font-size: .78rem; color: var(--blue-600); font-weight: 600; }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 15px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: .75rem; font-weight: 600; text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── PAGE ── */
    .page { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 32px 24px 60px; }

    /* ── PAGE HEADER ── */
    .page-header { margin-bottom: 28px; }
    .ph-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 4px 12px;
      font-size: .65rem; font-weight: 700; color: var(--blue-700);
      text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px;
    }
    .ph-title { font-family: 'Instrument Serif', serif; font-size: 1.6rem; font-weight: 400; color: var(--gray-900); }
    .ph-title em { font-style: italic; color: var(--blue-600); }
    .ph-sub { font-size: .78rem; color: var(--gray-400); margin-top: 4px; }

    /* ── ALERT ── */
    .alert-box {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px 18px; border-radius: 10px; margin-bottom: 22px;
      font-size: .85rem; line-height: 1.5; animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
    .alert-box i { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
    .alert-success { background: var(--green-50); border: 1px solid var(--green-100); color: var(--green-700); }
    .alert-success i { color: var(--green-500); }
    .alert-error   { background: var(--red-50);   border: 1px solid var(--red-100);   color: var(--red-700); }
    .alert-error i { color: var(--red-600); }

    /* ── TWO-COL LAYOUT ── */
    .main-grid { display: grid; grid-template-columns: 400px 1fr; gap: 22px; align-items: start; }

    /* ── FORM CARD ── */
    .form-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-md);
    }
    .card-head {
      padding: 16px 22px; border-bottom: 1px solid var(--gray-100);
      background: #fafcff; display: flex; align-items: center; gap: 10px;
    }
    .card-head-icon {
      width: 34px; height: 34px; border-radius: 9px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem; color: var(--blue-600);
    }
    .card-head-title { font-size: .9rem; font-weight: 700; color: var(--gray-900); }
    .card-head-sub   { font-size: .7rem; color: var(--gray-400); margin-top: 1px; }
    .card-body { padding: 22px; }

    /* ── FIELD ── */
    .field { margin-bottom: 18px; }
    .field label {
      display: block; font-size: .68rem; font-weight: 700;
      letter-spacing: .07em; text-transform: uppercase;
      color: var(--gray-500); margin-bottom: 7px;
    }
    .field label .req { color: var(--blue-500); }
    .field-hint { font-size: .7rem; color: var(--gray-400); margin-top: 6px; display: flex; align-items: center; gap: 5px; }

    /* ── Select2 light ── */
    .select2-container { z-index: 10; }
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
      background: var(--gray-50) !important;
      border: 1.5px solid var(--gray-200) !important;
      border-radius: 9px !important;
      min-height: 44px !important;
      font-family: 'Sora', sans-serif !important;
    }
    .select2-container--default .select2-selection--single:hover,
    .select2-container--default .select2-selection--multiple:hover { border-color: var(--blue-300) !important; }
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--multiple {
      border-color: var(--blue-500) !important; box-shadow: 0 0 0 3px var(--blue-glow) !important;
      background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--blue-700) !important; font-weight: 600 !important;
      font-family: 'Sora', sans-serif !important; font-size: 13px !important;
      line-height: 44px !important; padding-left: 13px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--gray-400) !important; font-weight: 400 !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px !important; right: 10px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: var(--gray-400) transparent transparent !important; }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b { border-color: transparent transparent var(--gray-400) !important; }
    .select2-container--default .select2-selection--multiple .select2-selection__rendered { padding: 4px 8px !important; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      background: var(--blue-50) !important; border: 1px solid var(--blue-100) !important;
      border-radius: 6px !important; color: var(--blue-700) !important;
      font-size: 12px !important; font-weight: 600 !important; padding: 2px 8px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: var(--blue-400) !important; margin-right: 4px !important; }
    .select2-dropdown {
      background: var(--white) !important; border: 1.5px solid var(--blue-200) !important;
      border-radius: 10px !important; box-shadow: var(--shadow-md) !important;
      font-family: 'Sora', sans-serif !important; overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      background: var(--gray-50) !important; border: 1px solid var(--gray-200) !important;
      border-radius: 7px !important; color: var(--gray-700) !important;
      font-size: 13px !important; padding: 7px 12px !important; outline: none !important;
    }
    .select2-results__option { color: var(--gray-700) !important; font-size: 13px !important; padding: 9px 14px !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--blue-50) !important; color: var(--blue-700) !important; }
    .select2-container--default .select2-results__option[aria-selected=true] { background: var(--blue-100) !important; color: var(--blue-800) !important; font-weight: 700 !important; }

    /* ── SUBMIT BTN ── */
    .btn-submit {
      width: 100%; height: 46px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; border-radius: 10px; color: #fff;
      font-family: 'Sora', sans-serif; font-size: .88rem; font-weight: 700;
      cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
      box-shadow: 0 5px 16px rgba(37,99,235,.28); transition: all .2s;
      position: relative; overflow: hidden;
    }
    .btn-submit::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.13) 0%, transparent 60%);
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.38); }
    .btn-submit:active { transform: translateY(0); }

    /* ── TABLE CARD ── */
    .table-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-md);
    }
    .table-card .card-head { background: #fafffe; border-bottom-color: var(--green-100); }
    .table-card .card-head-icon { background: var(--green-50); border-color: var(--green-100); color: var(--green-600); }

    /* DataTables override */
    .dataTables_wrapper { padding: 16px 20px 20px; }
    .dataTables_filter input {
      height: 36px; padding: 0 12px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200) !important;
      border-radius: 8px; font-family: 'Sora', sans-serif;
      font-size: .78rem; color: var(--gray-700); outline: none;
      margin-left: 8px;
    }
    .dataTables_filter input:focus { border-color: var(--blue-400) !important; box-shadow: 0 0 0 3px var(--blue-glow); }
    .dataTables_length select {
      height: 36px; padding: 0 28px 0 10px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200) !important;
      border-radius: 8px; font-family: 'Sora', sans-serif; font-size: .78rem;
      color: var(--gray-700); outline: none; appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 8px center;
      margin: 0 6px;
    }
    .dataTables_info, .dataTables_length, .dataTables_filter { font-size: .75rem; color: var(--gray-400); }

    table.dataTable { border-collapse: collapse !important; font-size: .8rem; }
    table.dataTable thead th {
      background: var(--gray-50) !important; color: var(--gray-400) !important;
      font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      border-bottom: 1px solid var(--gray-200) !important; padding: 10px 14px !important;
      border-top: none !important;
    }
    table.dataTable tbody tr { border-bottom: 1px solid var(--gray-100) !important; transition: background .1s; }
    table.dataTable tbody tr:last-child { border-bottom: none !important; }
    table.dataTable tbody tr:hover { background: var(--blue-50) !important; }
    table.dataTable tbody td { padding: 11px 14px !important; color: var(--gray-700) !important; vertical-align: top; }
    table.dataTable.no-footer { border-bottom: none !important; }

    /* patient name in table */
    .patient-cell { display: flex; align-items: center; gap: 9px; }
    .pat-av {
      width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .7rem; font-weight: 700; color: #fff;
    }
    .pat-name { font-weight: 700; color: var(--gray-900); font-size: .82rem; }

    /* med list in table */
    .med-list { list-style: none; display: flex; flex-direction: column; gap: 7px; }
    .med-list li { display: flex; align-items: flex-start; gap: 8px; }
    .med-name { font-weight: 600; color: var(--gray-900); font-size: .8rem; }
    .med-meta { font-size: .7rem; color: var(--gray-400); margin-top: 1px; }

    /* delete button */
    .btn-del-pres {
      width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
      background: var(--red-50); border: 1px solid var(--red-100);
      color: var(--red-600); font-size: .78rem;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all .15s;
    }
    .btn-del-pres:hover { background: var(--red-600); color: #fff; border-color: var(--red-600); }

    /* print button */
    .btn-print {
      display: flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-700); font-family: 'Sora', sans-serif;
      font-size: .74rem; font-weight: 700; cursor: pointer; transition: all .16s;
    }
    .btn-print:hover { background: var(--blue-600); color: #fff; border-color: var(--blue-600); }

    /* pagination */
    .dataTables_paginate .paginate_button {
      padding: 4px 10px !important; border-radius: 7px !important;
      font-size: .75rem !important; color: var(--gray-500) !important;
      background: var(--white) !important; border: 1px solid var(--gray-200) !important;
      margin: 0 2px !important; cursor: pointer;
    }
    .dataTables_paginate .paginate_button:hover {
      background: var(--blue-50) !important; color: var(--blue-600) !important;
      border-color: var(--blue-200) !important;
    }
    .dataTables_paginate .paginate_button.current {
      background: var(--blue-600) !important; color: #fff !important;
      border-color: var(--blue-600) !important; font-weight: 700 !important;
    }
    .dataTables_paginate .paginate_button.disabled { opacity: .35 !important; cursor: default !important; }

    /* empty state */
    .empty-state {
      padding: 48px 20px; text-align: center; color: var(--gray-400);
    }
    .empty-state i { display: block; font-size: 2.2rem; color: var(--gray-300); margin-bottom: 10px; }
    .empty-state p { font-size: .8rem; }

    /* responsive */
    @media (max-width: 900px) {
      .main-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
      .topbar { padding: 0 14px; }
      .page { padding: 18px 12px 48px; }
    }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="brand-name">Angelora</span>
    <span class="brand-sep">·</span>
    <span class="brand-page">Administer Medication</span>
  </div>
  <div class="topbar-right">
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-capsule-pill"></i> Nurse · Medication Administration</div>
    <div class="ph-title">Administer <em>Medication</em></div>
    <div class="ph-sub">Select a patient and medicines to administer. All records are timestamped automatically.</div>
  </div>

  <!-- ALERT -->
  <?php if ($message): ?>
  <div class="alert-box alert-<?= $msgType ?>">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
    <?= h($message) ?>
  </div>
  <?php endif; ?>

  <!-- MAIN GRID -->
  <div class="main-grid">

    <!-- FORM CARD -->
    <div class="form-card">
      <div class="card-head">
        <div class="card-head-icon"><i class="bi bi-plus-circle-fill"></i></div>
        <div>
          <div class="card-head-title">New Administration</div>
          <div class="card-head-sub">Select patient and medicine(s)</div>
        </div>
      </div>
      <div class="card-body">
        <form method="POST">

          <div class="field">
            <label>Patient <span class="req">*</span></label>
            <select name="patient_id" id="patient_id" required style="width:100%">
              <option value=""></option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= h($p['patient_id']) ?>"><?= h($p['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label>Medicine(s) <span class="req">*</span></label>
            <select name="medicine_ids[]" id="medicine_ids" multiple required style="width:100%">
              <?php foreach ($medicines as $m): ?>
                <option value="<?= h($m['medicine_id']) ?>"><?= h($m['medicine_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="field-hint"><i class="bi bi-info-circle"></i> You can select multiple medicines.</div>
          </div>

          <button type="submit" class="btn-submit">
            <i class="bi bi-check-circle-fill"></i> Save &amp; Administer
          </button>

        </form>
      </div>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
      <div class="card-head">
        <div class="card-head-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div>
          <div class="card-head-title">Administered Medications</div>
          <div class="card-head-sub">Grouped by patient · sorted by date</div>
        </div>
      </div>

      <?php if (empty($administered)): ?>
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <p>No medications have been administered yet.</p>
        </div>
      <?php else: ?>
        <table id="adminTable" class="dataTable no-footer" style="width:100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Patient</th>
              <th>Medications Administered</th>
              <th>Print</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; foreach ($administered as $pid => $data):
              $nameParts = explode(' ', $data['patient_name']);
              $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter($nameParts))));
              $initials = substr($initials, 0, 2);
            ?>
            <tr data-patient-id="<?= h($pid) ?>" data-patient-name="<?= h($data['patient_name']) ?>">
              <td class="muted"><?= $i++ ?></td>
              <td>
                <div class="patient-cell">
                  <div class="pat-av"><?= h($initials) ?></div>
                  <div class="pat-name"><?= h($data['patient_name']) ?></div>
                </div>
              </td>
              <td>
                <ul class="med-list">
                  <?php foreach ($data['medicines'] as $med): ?>
                  <li>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= h(addslashes($med['medicine_name'])) ?>?')">
                      <input type="hidden" name="delete_prescription_id" value="<?= $med['prescription_id'] ?>">
                      <button type="submit" class="btn-del-pres" title="Delete">
                        <i class="bi bi-trash3-fill"></i>
                      </button>
                    </form>
                    <div>
                      <div class="med-name"><i class="bi bi-capsule-pill" style="color:var(--violet-600);margin-right:4px;font-size:.75rem"></i><?= h($med['medicine_name']) ?></div>
                      <div class="med-meta"><?= h($med['notes']) ?> &nbsp;·&nbsp; <?= h($med['date']) ?></div>
                    </div>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </td>
              <td>
                <button class="btn-print" onclick="printReport(this)">
                  <i class="bi bi-printer-fill"></i> Print Report
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
  $('#patient_id').select2({
    width: '100%',
    placeholder: 'Search and select a patient…',
    allowClear: true,
  });
  $('#medicine_ids').select2({
    width: '100%',
    placeholder: 'Select one or more medicines…',
    closeOnSelect: false,
    allowClear: true,
  });

  if ($.fn.DataTable.isDataTable('#adminTable')) return;
  if ($('#adminTable').length) {
    $('#adminTable').DataTable({
      pageLength: 10,
      order: [[0, 'asc']],
      columnDefs: [{ orderable: false, targets: [2, 3] }],
      language: {
        search: '', searchPlaceholder: 'Search records…',
        lengthMenu: 'Show _MENU_ rows',
        info: 'Showing _START_–_END_ of _TOTAL_',
      }
    });
  }
});

function printReport(button) {
  const row = button.closest('tr');
  const patientName = row.getAttribute('data-patient-name');
  const meds = row.querySelectorAll('.med-list li');

  const popup = window.open('', '', 'width=900,height=700');
  popup.document.write(`
    <html><head><title>Medication Report – ${patientName}</title>
    <style>
      body{font-family:Arial,sans-serif;margin:40px;color:#1e3a5f}
      h2{color:#1d4ed8;margin-bottom:4px}
      p{color:#64748b;font-size:13px;margin-bottom:24px}
      table{border-collapse:collapse;width:100%}
      th{background:#1d4ed8;color:#fff;padding:10px 14px;text-align:left;font-size:12px;letter-spacing:.08em;text-transform:uppercase}
      td{border-bottom:1px solid #e2e8f0;padding:10px 14px;font-size:13px}
      tr:hover td{background:#eff6ff}
      .footer{margin-top:32px;font-size:11px;color:#94a3b8;text-align:right}
    </style></head><body>
    <h2>Medication Report</h2>
    <p>Patient: <strong>${patientName}</strong> &nbsp;·&nbsp; Printed: ${new Date().toLocaleString()}</p>
    <table><thead><tr><th>Medicine</th><th>Notes</th><th>Date Administered</th></tr></thead><tbody>`);

  meds.forEach(li => {
    const name = li.querySelector('.med-name')?.textContent.trim() || '—';
    const meta = li.querySelector('.med-meta')?.textContent.split('·') || ['', ''];
    popup.document.write(`<tr><td>${name}</td><td>${(meta[0]||'').trim()}</td><td>${(meta[1]||'').trim()}</td></tr>`);
  });

  popup.document.write(`</tbody></table><div class="footer">Angelora Hospital — Confidential Medical Record</div></body></html>`);
  popup.document.close();
  popup.print();
}
</script>
</body>
</html>
