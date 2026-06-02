<?php
require '../db.php';
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
  <title>Medical History Form — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
      --green-600: #059669; --green-50: #ecfdf5; --green-100: #d1fae5; --green-700: #047857;
      --red-50: #fef2f2; --red-100: #fee2e2; --red-600: #dc2626;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07);
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
      position: sticky; top: 0; z-index: 200;
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
    .page { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; padding: 32px 20px 60px; }

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

    /* ── PROGRESS BAR ── */
    .progress-wrap {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); padding: 20px 24px;
      margin-bottom: 22px; box-shadow: var(--shadow-sm);
    }
    .progress-top {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 14px;
    }
    .progress-title { font-size: .82rem; font-weight: 700; color: var(--gray-900); }
    .progress-count { font-size: .72rem; color: var(--gray-400); }
    .progress-track {
      height: 6px; background: var(--gray-200); border-radius: 999px; overflow: hidden;
      margin-bottom: 14px;
    }
    .progress-fill {
      height: 100%; background: linear-gradient(90deg, var(--blue-600), var(--blue-400));
      border-radius: 999px; transition: width .4s ease;
    }
    .steps-row { display: flex; gap: 6px; flex-wrap: wrap; }
    .step-dot {
      display: flex; align-items: center; gap: 5px;
      padding: 5px 10px; border-radius: 999px; font-size: .68rem; font-weight: 600;
      border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-400);
      cursor: pointer; transition: all .18s;
    }
    .step-dot.done    { background: var(--green-50);  border-color: var(--green-100); color: var(--green-700); }
    .step-dot.active  { background: var(--blue-600);  border-color: var(--blue-600);  color: #fff; }
    .step-dot .sn {
      width: 18px; height: 18px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: .6rem; font-weight: 700;
      background: rgba(255,255,255,.2);
    }
    .step-dot.done .sn    { background: var(--green-100); color: var(--green-700); }
    .step-dot.active .sn  { background: rgba(255,255,255,.25); color: #fff; }
    .step-dot:not(.active) .sn { background: var(--gray-100); color: var(--gray-500); }

    /* ── FORM CARD ── */
    .form-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-md);
    }
    .form-step { display: none; }
    .form-step.active { display: block; animation: stepIn .3s ease; }
    @keyframes stepIn { from{opacity:0;transform:translateX(10px)} to{opacity:1;transform:none} }

    /* step header */
    .step-head {
      padding: 16px 24px; border-bottom: 1px solid var(--gray-100);
      background: #fafcff; display: flex; align-items: center; gap: 11px;
    }
    .step-head-num {
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .78rem; font-weight: 700; color: #fff; flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(37,99,235,.25);
    }
    .step-head-title { font-size: .9rem; font-weight: 800; color: var(--gray-900); }
    .step-head-sub   { font-size: .7rem; color: var(--gray-400); margin-top: 1px; }

    /* form body */
    .step-body { padding: 24px; }

    /* ── FIELD ── */
    .field { margin-bottom: 16px; }
    .field label {
      display: block; font-size: .68rem; font-weight: 700;
      letter-spacing: .07em; text-transform: uppercase;
      color: var(--gray-500); margin-bottom: 7px;
    }
    .field label .req { color: var(--blue-500); margin-left: 2px; }

    input[type="text"], input[type="number"], input[type="date"],
    input[type="tel"], input[type="email"], select, textarea {
      width: 100%;
      padding: 9px 13px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; font-family: 'Sora', sans-serif;
      font-size: .82rem; color: var(--gray-700); outline: none;
      transition: border-color .18s, box-shadow .18s, background .18s;
      appearance: none;
    }
    input:hover, select:hover, textarea:hover { border-color: var(--blue-300); }
    input:focus, select:focus, textarea:focus {
      border-color: var(--blue-500);
      box-shadow: 0 0 0 3px var(--blue-glow);
      background: var(--white);
    }
    input::placeholder, textarea::placeholder { color: var(--gray-300); }
    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 11px center;
      padding-right: 32px; background-color: var(--gray-50);
    }
    textarea { resize: vertical; min-height: 80px; line-height: 1.55; }

    /* grid */
    .field-grid { display: grid; gap: 14px; }
    .g2 { grid-template-columns: 1fr 1fr; }
    .g3 { grid-template-columns: 1fr 1fr 1fr; }
    .g4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    @media (max-width: 600px) {
      .g2, .g3, .g4 { grid-template-columns: 1fr; }
    }

    /* ── SELECT2 LIGHT ── */
    .select2-container { z-index: 10; }
    .select2-container--default .select2-selection--single {
      background: var(--gray-50) !important; border: 1.5px solid var(--gray-200) !important;
      border-radius: 9px !important; height: 40px !important;
      display: flex !important; align-items: center !important;
    }
    .select2-container--default .select2-selection--single:hover { border-color: var(--blue-300) !important; }
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: var(--blue-500) !important; box-shadow: 0 0 0 3px var(--blue-glow) !important; background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--blue-700) !important; font-weight: 600 !important;
      font-family: 'Sora', sans-serif !important; font-size: 13px !important;
      line-height: 40px !important; padding-left: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--gray-400) !important; font-weight: 400 !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; right: 10px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow b { border-color: var(--gray-400) transparent transparent !important; }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b { border-color: transparent transparent var(--gray-400) !important; }
    .select2-dropdown {
      background: var(--white) !important; border: 1.5px solid var(--blue-200) !important;
      border-radius: 10px !important; box-shadow: var(--shadow-md) !important; overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      background: var(--gray-50) !important; border: 1px solid var(--gray-200) !important;
      border-radius: 7px !important; color: var(--gray-700) !important;
      font-size: 13px !important; padding: 6px 11px !important; outline: none !important;
    }
    .select2-results__option { color: var(--gray-700) !important; font-size: 13px !important; padding: 8px 13px !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--blue-50) !important; color: var(--blue-700) !important; }
    .select2-container--default .select2-results__option[aria-selected=true] { background: var(--blue-100) !important; color: var(--blue-800) !important; font-weight: 700 !important; }

    /* ── PATIENT PHOTO ── */
    .photo-preview {
      width: 110px; height: 130px; border-radius: 12px;
      border: 2px solid var(--blue-100); object-fit: cover;
      box-shadow: var(--shadow-sm); background: var(--blue-50);
      display: flex; align-items: center; justify-content: center;
    }
    .photo-preview img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
    .photo-placeholder { color: var(--gray-300); font-size: 2rem; }

    /* ── CHECKBOXES & RADIOS ── */
    .check-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .check-item {
      display: flex; align-items: center; gap: 9px;
      padding: 8px 12px; border-radius: 8px;
      background: var(--gray-50); border: 1px solid var(--gray-200);
      cursor: pointer; transition: all .15s;
    }
    .check-item:hover { border-color: var(--blue-200); background: var(--blue-50); }
    .check-item input[type="checkbox"],
    .check-item input[type="radio"] {
      width: 16px; height: 16px; accent-color: var(--blue-600);
      border-radius: 4px; cursor: pointer; flex-shrink: 0;
      padding: 0; background: var(--white); border: 1.5px solid var(--gray-300);
    }
    .check-item label { font-size: .78rem; color: var(--gray-700); cursor: pointer; margin: 0; font-weight: 500; }

    /* ── SECTION DIVIDER ── */
    .sec-divider {
      display: flex; align-items: center; gap: 10px; margin: 20px 0 16px;
    }
    .sec-divider-label {
      font-size: .62rem; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--gray-400); white-space: nowrap;
      display: flex; align-items: center; gap: 5px;
    }
    .sec-divider-label i { color: var(--blue-400); }
    .sec-divider::before, .sec-divider::after { content: ''; flex: 1; height: 1px; background: var(--gray-200); }

    /* ── DYNAMIC ROWS ── */
    .dynamic-row {
      background: var(--gray-50); border: 1px solid var(--gray-200);
      border-radius: 10px; padding: 14px; margin-bottom: 10px;
      position: relative;
    }
    .btn-remove-row {
      position: absolute; top: 10px; right: 10px;
      width: 24px; height: 24px; border-radius: 6px;
      background: var(--red-50); border: 1px solid var(--red-100);
      color: var(--red-600); font-size: .7rem; cursor: pointer;
      display: flex; align-items: center; justify-content: center; transition: all .15s;
    }
    .btn-remove-row:hover { background: var(--red-600); color: #fff; }
    .btn-add-row {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 16px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-700); font-family: 'Sora', sans-serif;
      font-size: .76rem; font-weight: 700; cursor: pointer; transition: all .16s;
    }
    .btn-add-row:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── ROS TABLE ── */
    .ros-table { width: 100%; border-collapse: collapse; }
    .ros-table th {
      font-size: .65rem; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; color: var(--gray-400);
      background: var(--gray-50); padding: 9px 13px;
      border-bottom: 1px solid var(--gray-200); text-align: left;
    }
    .ros-table td {
      padding: 7px 13px; border-bottom: 1px solid var(--gray-100);
      vertical-align: middle; font-size: .8rem;
    }
    .ros-table tr:last-child td { border-bottom: none; }
    .ros-table tr:hover td { background: var(--blue-50); }
    .ros-table .sys-name { font-weight: 600; color: var(--gray-900); }
    .ros-table input {
      padding: 6px 10px; font-size: .78rem; height: 34px;
    }

    /* ── VITALS GRID ── */
    .vitals-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .vital-box {
      background: var(--gray-50); border: 1px solid var(--gray-200);
      border-radius: 10px; padding: 12px 14px;
    }
    .vital-label { font-size: .6rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--gray-400); margin-bottom: 6px; }
    .vital-box input { padding: 7px 11px; font-size: .82rem; height: 36px; }

    /* ── STEP FOOTER ── */
    .step-footer {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 24px; border-top: 1px solid var(--gray-100);
      background: var(--gray-50);
    }
    .btn-prev {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 20px; border-radius: 9px;
      background: var(--white); border: 1px solid var(--gray-200);
      color: var(--gray-500); font-family: 'Sora', sans-serif;
      font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .16s;
    }
    .btn-prev:hover { background: var(--gray-100); color: var(--gray-700); }
    .btn-next {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 24px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; color: #fff; font-family: 'Sora', sans-serif;
      font-size: .82rem; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(37,99,235,.26); transition: all .18s;
    }
    .btn-next:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.36); }
    .btn-submit {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 28px; border-radius: 9px;
      background: linear-gradient(135deg, var(--green-700), var(--green-600));
      border: none; color: #fff; font-family: 'Sora', sans-serif;
      font-size: .82rem; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(5,150,105,.26); transition: all .18s;
    }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(5,150,105,.36); }
    .step-info { font-size: .72rem; color: var(--gray-400); }

    /* responsive */
    @media (max-width: 700px) {
      .topbar { padding: 0 14px; }
      .page { padding: 18px 12px 48px; }
      .step-body { padding: 16px; }
      .vitals-grid { grid-template-columns: 1fr 1fr; }
      .steps-row { display: none; }
    }
    @media (max-width: 480px) { .vitals-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="brand-name">Angelora</span>
    <span class="brand-sep">·</span>
    <span class="brand-page">Medical History Form</span>
  </div>
  <div class="topbar-right">
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-journal-medical"></i> Clinical · Medical History</div>
    <div class="ph-title">Medical <em>History Form</em></div>
    <div class="ph-sub">Complete all 7 sections to record the patient's full medical history.</div>
  </div>

  <!-- PROGRESS -->
  <div class="progress-wrap">
    <div class="progress-top">
      <div class="progress-title" id="progressTitle">Step 1 of 7 — Personal Information</div>
      <div class="progress-count" id="progressCount">14%</div>
    </div>
    <div class="progress-track">
      <div class="progress-fill" id="progressFill" style="width:14%"></div>
    </div>
    <div class="steps-row" id="stepsRow">
      <div class="step-dot active" onclick="jumpStep(0)"><span class="sn">1</span> Personal</div>
      <div class="step-dot" onclick="jumpStep(1)"><span class="sn">2</span> Complaint</div>
      <div class="step-dot" onclick="jumpStep(2)"><span class="sn">3</span> Medications</div>
      <div class="step-dot" onclick="jumpStep(3)"><span class="sn">4</span> Allergies</div>
      <div class="step-dot" onclick="jumpStep(4)"><span class="sn">5</span> Immunization</div>
      <div class="step-dot" onclick="jumpStep(5)"><span class="sn">6</span> Examination</div>
      <div class="step-dot" onclick="jumpStep(6)"><span class="sn">7</span> Clinician</div>
    </div>
  </div>

  <!-- FORM -->
  <form id="medicalForm" method="POST" action="save_medical_form.php">
  <!--<form id="medicalForm" method="POST" action="medical_history.php">-->
  <div class="form-card">

    <!-- ══ STEP 1: PERSONAL INFO ══ -->
    <fieldset class="form-step active">
      <div class="step-head">
        <div class="step-head-num">1</div>
        <div>
          <div class="step-head-title">Personal Information</div>
          <div class="step-head-sub">Identify the patient and confirm demographics</div>
        </div>
      </div>
      <div class="step-body">

        <div class="field-grid g2" style="margin-bottom:16px">
          <div class="field">
            <label>Full Name</label>
            <select id="full_name" name="full_name" style="width:100%"></select>
          </div>
          <div class="field">
            <label>Patient ID / Hospital Number</label>
            <select id="patient_id" name="patient_id" style="width:100%"></select>
          </div>
        </div>

        <!-- Photo + demographics -->
        <div style="display:grid;grid-template-columns:120px 1fr;gap:20px;align-items:start;margin-bottom:16px">
          <div>
            <div class="field"><label>Photo</label></div>
            <div class="photo-preview" id="photoWrap">
              <img id="patient_photo" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:10px">
              <i class="bi bi-person-fill photo-placeholder" id="photoIcon"></i>
            </div>
            <input type="hidden" id="photo" name="photo">
          </div>
          <div>
            <div class="field-grid g3">
              <div class="field">
                <label>Date of Birth</label>
                <input type="date" name="dob" id="f_dob">
              </div>
              <div class="field">
                <label>Age</label>
                <input type="number" name="age" id="f_age" placeholder="Years">
              </div>
              <div class="field">
                <label>Marital Status</label>
                <select name="marital_status" id="f_marital">
                  <option>Single</option><option>Married</option><option>Widowed</option><option>Divorced</option>
                </select>
              </div>
              <div class="field">
                <label>Phone Number</label>
                <input type="tel" name="phone" id="f_phone" placeholder="08012345678">
              </div>
              <div class="field" style="grid-column:span 2">
                <label>Address</label>
                <input type="text" name="address" id="f_address" placeholder="Residential address">
              </div>
            </div>
          </div>
        </div>

        <div class="field-grid g3">
          <div class="field">
            <label>Gender</label>
            <div style="display:flex;gap:8px;margin-top:4px">
              <label class="check-item" style="flex:1"><input type="radio" name="gender" value="Male" id="gM"> <span>Male</span></label>
              <label class="check-item" style="flex:1"><input type="radio" name="gender" value="Female" id="gF"> <span>Female</span></label>
              <label class="check-item" style="flex:1"><input type="radio" name="gender" value="Other" id="gO"> <span>Other</span></label>
            </div>
          </div>
          <div class="field">
            <label>Occupation</label>
            <input type="text" name="occupation" placeholder="Job title">
          </div>
          <div class="field">
            <label>Date of Visit</label>
            <input type="date" name="visit_date">
          </div>
        </div>
      </div>
      <div class="step-footer">
        <div class="step-info">Fields marked <span style="color:var(--blue-500)">*</span> are required</div>
        <button type="button" class="btn-next next-step">Next <i class="bi bi-arrow-right"></i></button>
      </div>
    </fieldset>

    <!-- ══ STEP 2: COMPLAINT + HPI + PMH + SURGICAL ══ -->
    <fieldset class="form-step">
      <div class="step-head">
        <div class="step-head-num">2</div>
        <div>
          <div class="step-head-title">Chief Complaint, HPI &amp; Past Medical History</div>
          <div class="step-head-sub">Clinical presentation and medical background</div>
        </div>
      </div>
      <div class="step-body">

        <div class="field">
          <label>Chief Complaint <span class="req">*</span></label>
          <textarea name="chief_complaint" rows="2" placeholder="e.g. Chest pain for 2 days"></textarea>
        </div>
        <div class="field">
          <label>History of Present Illness (HPI)</label>
          <textarea name="hpi" rows="3" placeholder="Onset, duration, severity, aggravating/relieving factors…"></textarea>
        </div>

        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-clipboard2-check-fill"></i>Past Medical History (PMH)</span></div>
        <div class="check-grid">
          <?php foreach (['Hypertension','Diabetes Mellitus','Asthma/COPD','Tuberculosis','HIV/AIDS','Cardiac Disease','Stroke','Psychiatric Illness'] as $pmh): ?>
          <label class="check-item">
            <input type="checkbox" name="pmh[]" value="<?= $pmh ?>">
            <label><?= $pmh ?></label>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="field" style="margin-top:12px">
          <label>Other (Specify)</label>
          <input type="text" name="pmh_other" placeholder="Any other condition">
        </div>

        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-scissors"></i>Past Surgical History</span></div>
        <div id="surgery-entries"></div>
        <button type="button" class="btn-add-row" onclick="addSurgeryRow()">
          <i class="bi bi-plus-circle-fill"></i> Add Surgery
        </button>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-prev prev-step"><i class="bi bi-arrow-left"></i> Back</button>
        <button type="button" class="btn-next next-step">Next <i class="bi bi-arrow-right"></i></button>
      </div>
    </fieldset>

    <!-- ══ STEP 3: CURRENT MEDICATIONS ══ -->
    <fieldset class="form-step">
      <div class="step-head">
        <div class="step-head-num">3</div>
        <div>
          <div class="step-head-title">Current Medications</div>
          <div class="step-head-sub">List all current drugs, dosages and indications</div>
        </div>
      </div>
      <div class="step-body">
        <div id="medication-entries"></div>
        <button type="button" class="btn-add-row" onclick="addMedicationRow()">
          <i class="bi bi-plus-circle-fill"></i> Add Medication
        </button>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-prev prev-step"><i class="bi bi-arrow-left"></i> Back</button>
        <button type="button" class="btn-next next-step">Next <i class="bi bi-arrow-right"></i></button>
      </div>
    </fieldset>

    <!-- ══ STEP 4: ALLERGIES + FAMILY + SOCIAL ══ -->
    <fieldset class="form-step">
      <div class="step-head">
        <div class="step-head-num">4</div>
        <div>
          <div class="step-head-title">Allergies, Family &amp; Social History</div>
          <div class="step-head-sub">Allergic reactions, hereditary conditions and lifestyle</div>
        </div>
      </div>
      <div class="step-body">

        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-shield-exclamation"></i>Allergies</span></div>
        <label class="check-item" style="display:inline-flex;margin-bottom:12px">
          <input type="checkbox" name="nkda" value="No Known Drug Allergies">
          <label>No Known Drug Allergies (NKDA)</label>
        </label>
        <div class="field">
          <label>Allergic To</label>
          <input type="text" name="allergies" placeholder="Specify allergens if any">
        </div>

        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-people-fill"></i>Family History</span></div>
        <div class="check-grid">
          <?php foreach (['Hypertension','Diabetes','Cancer','Stroke','Mental Illness'] as $fh): ?>
          <label class="check-item">
            <input type="checkbox" name="fh[]" value="<?= $fh ?>">
            <label><?= $fh ?></label>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="field" style="margin-top:12px">
          <label>Others</label>
          <input type="text" name="fh_other" placeholder="Specify other hereditary conditions">
        </div>

        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-person-walking"></i>Social History</span></div>
        <div class="field-grid g2">
          <div class="field">
            <label>Smoking</label>
            <select name="smoking"><option>No</option><option>Yes</option></select>
          </div>
          <div class="field">
            <label>Packs / Day</label>
            <input type="text" name="smoking_packs" placeholder="If yes">
          </div>
          <div class="field">
            <label>Alcohol</label>
            <select name="alcohol"><option>No</option><option>Yes</option></select>
          </div>
          <div class="field">
            <label>Type / Frequency</label>
            <input type="text" name="alcohol_details" placeholder="If yes">
          </div>
          <div class="field">
            <label>Recreational Drugs</label>
            <select name="drugs"><option>No</option><option>Yes</option></select>
          </div>
          <div class="field">
            <label>Specify</label>
            <input type="text" name="drugs_specify" placeholder="If yes">
          </div>
          <div class="field">
            <label>Sexual Activity</label>
            <select name="sexual_activity"><option>Active</option><option>Inactive</option></select>
          </div>
          <div class="field">
            <label>Number of Partners</label>
            <input type="number" name="partners" placeholder="If active">
          </div>
          <div class="field">
            <label>Use of Protection</label>
            <select name="protection"><option>Always</option><option>Sometimes</option><option>Never</option></select>
          </div>
          <div class="field">
            <label>Occupation / Work Hazards</label>
            <input type="text" name="work_hazards" placeholder="Exposure to chemicals, radiation, etc.">
          </div>
        </div>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-prev prev-step"><i class="bi bi-arrow-left"></i> Back</button>
        <button type="button" class="btn-next next-step">Next <i class="bi bi-arrow-right"></i></button>
      </div>
    </fieldset>

    <!-- ══ STEP 5: IMMUNIZATION + ROS + OBSTETRIC ══ -->
    <fieldset class="form-step">
      <div class="step-head">
        <div class="step-head-num">5</div>
        <div>
          <div class="step-head-title">Immunization, Review of Systems &amp; Obstetric</div>
          <div class="step-head-sub">Vaccination records, systems review and gynecologic history</div>
        </div>
      </div>
      <div class="step-body">

        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-shield-fill-check"></i>Immunization History</span></div>
        <div class="field-grid g3">
          <div class="field">
            <label>Childhood Vaccines</label>
            <select name="childhood_vaccines"><option>Up to Date</option><option>Not Sure</option></select>
          </div>
          <div class="field">
            <label>Tetanus</label>
            <select name="tetanus"><option>No</option><option>Yes</option></select>
          </div>
          <div class="field">
            <label>Tetanus Date</label>
            <input type="date" name="tetanus_date">
          </div>
          <div class="field">
            <label>Hepatitis B</label>
            <select name="hepatitis_b"><option>No</option><option>Yes</option></select>
          </div>
          <div class="field">
            <label>COVID-19 Vaccine</label>
            <select name="covid19"><option>No</option><option>Yes</option></select>
          </div>
          <div class="field">
            <label>Other Vaccines</label>
            <input type="text" name="other_vaccines" placeholder="Specify">
          </div>
        </div>

        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-lungs-fill"></i>Review of Systems (ROS)</span></div>
        <div style="overflow-x:auto;border:1px solid var(--gray-200);border-radius:10px;overflow:hidden">
          <table class="ros-table">
            <thead>
              <tr>
                <th style="width:22%">System</th>
                <th style="width:39%">Symptoms Present</th>
                <th style="width:39%">Description</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $systems = [
                ['General','ros_general','ros_general_desc'],
                ['Cardiovascular','ros_cardio','ros_cardio_desc'],
                ['Respiratory','ros_resp','ros_resp_desc'],
                ['Gastrointestinal','ros_gi','ros_gi_desc'],
                ['Genitourinary','ros_gu','ros_gu_desc'],
                ['Nervous System','ros_ns','ros_ns_desc'],
                ['Musculoskeletal','ros_msk','ros_msk_desc'],
                ['Dermatologic','ros_derm','ros_derm_desc'],
                ['Psychiatric','ros_psych','ros_psych_desc'],
              ];
              foreach ($systems as $s): ?>
              <tr>
                <td class="sys-name"><?= $s[0] ?></td>
                <td><input type="text" name="<?= $s[1] ?>" placeholder="Yes / No / None"></td>
                <td><input type="text" name="<?= $s[2] ?>" placeholder="Describe if present"></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="sec-divider" style="margin-top:20px"><span class="sec-divider-label"><i class="bi bi-gender-female"></i>Obstetric / Gynecologic History</span></div>
        <div class="field-grid g4">
          <div class="field"><label>Gravida</label><input type="number" name="gravida" placeholder="0"></div>
          <div class="field"><label>Para</label><input type="number" name="para" placeholder="0"></div>
          <div class="field"><label>Abortions</label><input type="number" name="abortions" placeholder="0"></div>
          <div class="field"><label>Last Menstrual Period</label><input type="date" name="lmp"></div>
          <div class="field"><label>Menstrual Cycle</label><select name="menstrual_cycle"><option>Regular</option><option>Irregular</option></select></div>
          <div class="field"><label>Contraceptive Use</label><select name="contraceptive_use"><option>No</option><option>Yes</option></select></div>
          <div class="field" style="grid-column:span 2"><label>Contraceptive Type</label><input type="text" name="contraceptive_type" placeholder="If yes, specify"></div>
        </div>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-prev prev-step"><i class="bi bi-arrow-left"></i> Back</button>
        <button type="button" class="btn-next next-step">Next <i class="bi bi-arrow-right"></i></button>
      </div>
    </fieldset>

    <!-- ══ STEP 6: PHYSICAL EXAM + ASSESSMENT ══ -->
    <fieldset class="form-step">
      <div class="step-head">
        <div class="step-head-num">6</div>
        <div>
          <div class="step-head-title">Physical Examination &amp; Clinical Assessment</div>
          <div class="step-head-sub">Vital signs, findings and initial diagnosis plan</div>
        </div>
      </div>
      <div class="step-body">
        <div class="sec-divider"><span class="sec-divider-label"><i class="bi bi-heart-pulse-fill"></i>Vital Signs</span></div>
        <div class="vitals-grid">
          <div class="vital-box"><div class="vital-label">Temperature (°C)</div><input type="text" name="temp" placeholder="e.g. 36.5"></div>
          <div class="vital-box"><div class="vital-label">Blood Pressure (mmHg)</div><input type="text" name="bp" placeholder="e.g. 120/80"></div>
          <div class="vital-box"><div class="vital-label">Heart Rate (bpm)</div><input type="text" name="hr" placeholder="e.g. 72"></div>
          <div class="vital-box"><div class="vital-label">Respiratory Rate</div><input type="text" name="rr" placeholder="breaths/min"></div>
          <div class="vital-box"><div class="vital-label">O₂ Saturation (%)</div><input type="text" name="spo2" placeholder="e.g. 98%"></div>
          <div class="vital-box"><div class="vital-label">Weight (kg)</div><input type="text" name="weight" placeholder="e.g. 70"></div>
          <div class="vital-box"><div class="vital-label">Height (cm)</div><input type="text" name="height" placeholder="e.g. 170"></div>
          <div class="vital-box"><div class="vital-label">BMI (kg/m²)</div><input type="text" name="bmi" placeholder="Auto or enter"></div>
        </div>

        <div class="sec-divider" style="margin-top:20px"><span class="sec-divider-label"><i class="bi bi-clipboard2-pulse-fill"></i>Clinical Assessment &amp; Plan</span></div>
        <div class="field">
          <label>Assessment &amp; Plan</label>
          <textarea name="clinical_assessment" rows="4" placeholder="Working diagnosis, investigations ordered, treatment plan…"></textarea>
        </div>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-prev prev-step"><i class="bi bi-arrow-left"></i> Back</button>
        <button type="button" class="btn-next next-step">Next <i class="bi bi-arrow-right"></i></button>
      </div>
    </fieldset>

    <!-- ══ STEP 7: CLINICIAN DETAILS ══ -->
    <fieldset class="form-step">
      <div class="step-head">
        <div class="step-head-num">7</div>
        <div>
          <div class="step-head-title">Clinician Details &amp; Sign-off</div>
          <div class="step-head-sub">Final review and authorisation</div>
        </div>
      </div>
      <div class="step-body">
        <div class="field-grid g2">
          <div class="field">
            <label>Clinician Name <span class="req">*</span></label>
            <input type="text" name="clinician_name" placeholder="Full name of attending clinician">
          </div>
          <div class="field">
            <label>Designation</label>
            <input type="text" name="clinician_designation" placeholder="e.g. Medical Officer, Consultant">
          </div>
          <div class="field">
            <label>Date</label>
            <input type="date" name="clinician_date">
          </div>
          <div class="field">
            <label>Signature</label>
            <input type="text" name="clinician_signature" placeholder="Typed signature">
          </div>
        </div>

        <!-- Summary box -->
        <div style="margin-top:20px;background:var(--blue-50);border:1px solid var(--blue-100);border-radius:12px;padding:16px 20px">
          <div style="font-size:.72rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--blue-600);margin-bottom:8px">
            <i class="bi bi-info-circle-fill" style="margin-right:5px"></i>Before you submit
          </div>
          <ul style="padding-left:16px;font-size:.78rem;color:var(--blue-800);line-height:1.8">
            <li>Verify patient details are correct on Step 1.</li>
            <li>Ensure all clinical notes are complete and accurate.</li>
            <li>Confirm the clinician name and designation are correct.</li>
            <li>This record will be saved permanently to the patient's file.</li>
          </ul>
        </div>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-prev prev-step"><i class="bi bi-arrow-left"></i> Back</button>
        <button type="submit" class="btn-submit">
          <i class="bi bi-check-circle-fill"></i> Submit Medical History
        </button>
      </div>
    </fieldset>

  </div><!-- /form-card -->
  </form>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
/* ── STEP LABELS ── */
const stepLabels = [
  'Personal Information','Chief Complaint & History',
  'Current Medications','Allergies & Social History',
  'Immunization & ROS','Physical Examination','Clinician Sign-off'
];
const totalSteps = 7;
let currentStep = 0;

function updateProgress() {
  const pct = Math.round(((currentStep + 1) / totalSteps) * 100);
  document.getElementById('progressFill').style.width  = pct + '%';
  document.getElementById('progressCount').textContent = pct + '%';
  document.getElementById('progressTitle').textContent =
    'Step ' + (currentStep + 1) + ' of ' + totalSteps + ' — ' + stepLabels[currentStep];

  document.querySelectorAll('.step-dot').forEach((dot, i) => {
    dot.classList.remove('active','done');
    if (i === currentStep) dot.classList.add('active');
    else if (i < currentStep) dot.classList.add('done');
  });
}

function goToStep(idx) {
  const steps = document.querySelectorAll('.form-step');
  steps[currentStep].classList.remove('active');
  currentStep = idx;
  steps[currentStep].classList.add('active');
  updateProgress();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function jumpStep(idx) {
  if (idx <= currentStep) goToStep(idx);
}

document.querySelectorAll('.next-step').forEach(btn => {
  btn.addEventListener('click', () => {
    if (currentStep < totalSteps - 1) goToStep(currentStep + 1);
  });
});
document.querySelectorAll('.prev-step').forEach(btn => {
  btn.addEventListener('click', () => {
    if (currentStep > 0) goToStep(currentStep - 1);
  });
});

/* ── SELECT2 ── */
$(document).ready(function () {
  const ajaxConfig = (q_key) => ({
    url: 'fetch_patients.php',
    dataType: 'json',
    delay: 250,
    data: p => ({ q: p.term }),
    processResults: d => ({ results: d.results }),
    cache: true
  });

  $('#full_name').select2({ placeholder: 'Search patient by name…', ajax: ajaxConfig('name'), width: '100%' });
  $('#patient_id').select2({ placeholder: 'Search by patient ID…',  ajax: ajaxConfig('id'),   width: '100%' });

  function fillFields(data) {
    if (data.photo) {
      $('#patient_photo').attr('src', '../uploads/' + data.photo).show();
      $('#photoIcon').hide();
    }
    $('#photo').val(data.photo || '');
    $('#f_dob').val(data.dob || '');
    $('#f_age').val(data.age || '');
    $('#f_address').val(data.address || '');
    $('#f_phone').val(data.phone || '');
    if (data.marital_status) $('#f_marital').val(data.marital_status);
    if (data.gender) $('input[name="gender"][value="' + data.gender + '"]').prop('checked', true);
  }

  $('#full_name').on('select2:select', function(e) {
    const d = e.params.data;
    fillFields(d);
    if (d.patient_pin) {
      const opt = new Option(d.patient_pin, d.patient_pin, true, true);
      $('#patient_id').append(opt).trigger('change');
    }
  });

  $('#patient_id').on('select2:select', function(e) {
    const d = e.params.data;
    fillFields(d);
    if (d.full_name) {
      const opt = new Option(d.full_name, d.full_name, true, true);
      $('#full_name').append(opt).trigger('change');
    }
  });
});

/* ── DYNAMIC ROWS ── */
function addSurgeryRow() {
  const wrap = document.getElementById('surgery-entries');
  const div  = document.createElement('div');
  div.className = 'dynamic-row';
  div.innerHTML = `
    <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    <div class="field-grid g3">
      <div class="field"><label>Surgery / Procedure</label><input type="text" name="surgery_name[]" placeholder="e.g. Appendectomy"></div>
      <div class="field"><label>Date</label><input type="date" name="surgery_date[]"></div>
      <div class="field"><label>Complications</label><input type="text" name="surgery_complications[]" placeholder="None / Describe"></div>
    </div>`;
  wrap.appendChild(div);
}

function addMedicationRow() {
  const wrap = document.getElementById('medication-entries');
  const div  = document.createElement('div');
  div.className = 'dynamic-row';
  div.innerHTML = `
    <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    <div class="field-grid" style="grid-template-columns:2fr 1fr 1fr 1fr 2fr;gap:12px">
      <div class="field"><label>Drug Name</label><input type="text" name="med_name[]" placeholder="Generic / Brand"></div>
      <div class="field"><label>Dosage</label><input type="text" name="med_dosage[]" placeholder="e.g. 500mg"></div>
      <div class="field"><label>Frequency</label><input type="text" name="med_frequency[]" placeholder="e.g. BD"></div>
      <div class="field"><label>Duration</label><input type="text" name="med_duration[]" placeholder="e.g. 7 days"></div>
      <div class="field"><label>Indication</label><input type="text" name="med_indication[]" placeholder="Reason for use"></div>
    </div>`;
  wrap.appendChild(div);
}

/* ── AUTO BMI ── */
document.querySelector('[name="weight"]')?.addEventListener('input', calcBMI);
document.querySelector('[name="height"]')?.addEventListener('input', calcBMI);
function calcBMI() {
  const w = parseFloat(document.querySelector('[name="weight"]').value);
  const h = parseFloat(document.querySelector('[name="height"]').value) / 100;
  if (w && h) {
    document.querySelector('[name="bmi"]').value = (w / (h * h)).toFixed(1);
  }
}

/* ── INITIAL ROWS ── */
addSurgeryRow();
addMedicationRow();
</script>
</body>
</html>