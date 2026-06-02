<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../db.php';

$message     = "";
$messageType = "";

/* ── HANDLE FORM SUBMISSION ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['patient_id'])) {
        $message = "Please select a patient.";
        $messageType = "error";
    } elseif (empty($_POST['recorded_by'])) {
        $message = "Please select the nurse.";
        $messageType = "error";
    } else {

        if (isset($_POST['action']) && $_POST['action'] === 'edit') {
            $id  = $_POST['vs_id'];
            $sql = "UPDATE vital_signs SET
                temperature=:temperature, pulse_rate=:pulse_rate,
                respiration_rate=:respiration_rate, blood_pressure=:blood_pressure,
                oxygen_saturation=:oxygen_saturation, pain_level=:pain_level,
                height_cm=:height_cm, weight_kg=:weight_kg, bmi=:bmi,
                recorded_by=:recorded_by, blood_sugar=:blood_sugar,
                consciousness_level=:consciousness_level, vitals_time=:vitals_time,
                symptoms_notes=:symptoms_notes WHERE id=:id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':temperature'        => $_POST['temperature'],
                ':pulse_rate'         => $_POST['pulse_rate'],
                ':respiration_rate'   => $_POST['respiration_rate'],
                ':blood_pressure'     => $_POST['blood_pressure'],
                ':oxygen_saturation'  => $_POST['oxygen_saturation'],
                ':pain_level'         => $_POST['pain_level'],
                ':height_cm'          => $_POST['height_cm'] ?: null,
                ':weight_kg'          => $_POST['weight_kg'] ?: null,
                ':bmi'                => $_POST['bmi'] ?: null,
                ':recorded_by'        => $_POST['recorded_by'],
                ':blood_sugar'        => $_POST['blood_sugar'] ?: null,
                ':consciousness_level'=> $_POST['consciousness_level'] ?: null,
                ':vitals_time'        => $_POST['vitals_time'] ?: null,
                ':symptoms_notes'     => $_POST['symptoms_notes'] ?: null,
                ':id'                 => $id
            ]);
            $message     = "Vital signs updated successfully!";
            $messageType = "success";

        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM vital_signs WHERE id=?");
            $stmt->execute([$_POST['vs_id']]);
            $message     = "Vital signs record deleted.";
            $messageType = "success";

        } else {
            $sql = "INSERT INTO vital_signs (
                patient_id, temperature, pulse_rate, respiration_rate,
                blood_pressure, oxygen_saturation, pain_level,
                height_cm, weight_kg, bmi, recorded_by,
                blood_sugar, consciousness_level, vitals_time, symptoms_notes
            ) VALUES (
                :patient_id, :temperature, :pulse_rate, :respiration_rate,
                :blood_pressure, :oxygen_saturation, :pain_level,
                :height_cm, :weight_kg, :bmi, :recorded_by,
                :blood_sugar, :consciousness_level, :vitals_time, :symptoms_notes
            )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':patient_id'         => $_POST['patient_id'],
                ':temperature'        => $_POST['temperature'],
                ':pulse_rate'         => $_POST['pulse_rate'],
                ':respiration_rate'   => $_POST['respiration_rate'],
                ':blood_pressure'     => $_POST['blood_pressure'],
                ':oxygen_saturation'  => $_POST['oxygen_saturation'],
                ':pain_level'         => $_POST['pain_level'],
                ':height_cm'          => $_POST['height_cm'] ?: null,
                ':weight_kg'          => $_POST['weight_kg'] ?: null,
                ':bmi'                => $_POST['bmi'] ?: null,
                ':recorded_by'        => $_POST['recorded_by'],
                ':blood_sugar'        => $_POST['blood_sugar'] ?: null,
                ':consciousness_level'=> $_POST['consciousness_level'] ?: null,
                ':vitals_time'        => $_POST['vitals_time'] ?: null,
                ':symptoms_notes'     => $_POST['symptoms_notes'] ?: null
            ]);
            $message     = "Vital signs recorded successfully!";
            $messageType = "success";
        }
    }
}

/* ── FETCH DATA ── */
$patients  = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name")->fetchAll();
$nurseStmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role_id = 3 ORDER BY full_name");
$nurseStmt->execute();
$nurses = $nurseStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Record Vital Signs — Angelora Hospital</title>
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
      --red-600: #dc2626; --red-50: #fef2f2; --red-100: #fee2e2; --red-700: #b91c1c;
      --amber-500: #f59e0b; --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 20px rgba(15,45,107,.10), 0 2px 8px rgba(15,45,107,.07);
      --shadow-lg: 0 12px 48px rgba(15,45,107,.14), 0 4px 14px rgba(15,45,107,.08);
      --blue-glow: rgba(37,99,235,.12);
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
        radial-gradient(ellipse 700px 500px at 10% 20%, rgba(37,99,235,.06) 0%, transparent 70%),
        radial-gradient(ellipse 600px 400px at 90% 80%, rgba(96,165,250,.05) 0%, transparent 70%);
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
    .tb-brand { display: flex; align-items: center; gap: 11px; }
    .tb-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.25);
    }
    .tb-name { font-family: 'Instrument Serif', serif; font-size: 1.05rem; color: var(--blue-800); }
    .tb-sep  { color: var(--gray-300); margin: 0 2px; }
    .tb-page { font-size: .78rem; color: var(--blue-600); font-weight: 600; }
    .back-btn {
      display: flex; align-items: center; gap: 6px; padding: 7px 15px;
      border-radius: 8px; background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: .75rem; font-weight: 600; text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── PAGE ── */
    .page {
      position: relative; z-index: 1;
      max-width: 700px; margin: 0 auto;
      padding: 36px 20px 60px;
    }

    /* ── PAGE HEADER ── */
    .page-header { margin-bottom: 28px; }
    .ph-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 4px 12px;
      font-size: .65rem; font-weight: 700; color: var(--blue-700);
      text-transform: uppercase; letter-spacing: .08em; margin-bottom: 10px;
    }
    .ph-title {
      font-family: 'Instrument Serif', serif;
      font-size: 1.7rem; font-weight: 400; color: var(--gray-900);
    }
    .ph-title em { font-style: italic; color: var(--blue-600); }
    .ph-sub { font-size: .78rem; color: var(--gray-400); margin-top: 4px; }

    /* ── ALERT ── */
    .alert-box {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 13px 18px; border-radius: 10px; margin-bottom: 22px;
      font-size: .83rem; line-height: 1.5; font-weight: 500;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
    .alert-box.success { background: var(--green-50); border: 1px solid var(--green-100); color: var(--green-700); }
    .alert-box.error   { background: var(--red-50);   border: 1px solid var(--red-100);   color: var(--red-700); }
    .alert-box i { font-size: .95rem; flex-shrink: 0; margin-top: 1px; }

    /* ── PROGRESS BAR ── */
    .progress-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; padding: 18px 22px;
      margin-bottom: 20px; box-shadow: var(--shadow-sm);
    }
    .progress-top {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 12px;
    }
    .progress-label { font-size: .8rem; font-weight: 700; color: var(--gray-900); }
    .progress-pct   { font-size: .72rem; color: var(--gray-400); font-weight: 500; }
    .progress-track {
      height: 5px; background: var(--gray-200); border-radius: 999px;
      margin-bottom: 14px; overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--blue-700), var(--blue-400));
      border-radius: 999px; transition: width .4s cubic-bezier(.4,0,.2,1);
    }
    .steps-nav { display: flex; gap: 6px; }
    .step-pill {
      flex: 1; padding: 6px 4px; border-radius: 7px; text-align: center;
      font-size: .65rem; font-weight: 700; border: 1.5px solid var(--gray-200);
      background: var(--white); color: var(--gray-400); cursor: pointer;
      transition: all .18s; white-space: nowrap;
    }
    .step-pill.active { background: var(--blue-600); border-color: var(--blue-600); color: #fff; }
    .step-pill.done   { background: var(--green-50); border-color: var(--green-100); color: var(--green-700); }
    .step-pill i { display: block; font-size: .82rem; margin-bottom: 2px; }

    /* ── FORM CARD ── */
    .form-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md);
    }

    .form-step { display: none; }
    .form-step.active { display: block; animation: stepIn .3s ease; }
    @keyframes stepIn { from{opacity:0;transform:translateX(8px)} to{opacity:1;transform:none} }

    /* step header */
    .step-header {
      padding: 16px 24px; border-bottom: 1px solid var(--gray-100);
      background: #fafcff; display: flex; align-items: center; gap: 12px;
    }
    .step-num {
      width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .82rem; font-weight: 700; color: #fff;
      box-shadow: 0 3px 10px rgba(37,99,235,.25);
    }
    .step-title { font-size: .92rem; font-weight: 800; color: var(--gray-900); }
    .step-sub   { font-size: .7rem; color: var(--gray-400); margin-top: 1px; }

    /* step body */
    .step-body { padding: 24px; }

    /* ── FIELD ── */
    .field { margin-bottom: 16px; }
    .field label {
      display: block; font-size: .67rem; font-weight: 700;
      letter-spacing: .08em; text-transform: uppercase;
      color: var(--gray-500); margin-bottom: 7px;
    }
    .field label .req { color: var(--blue-500); margin-left: 2px; }

    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: .85rem; pointer-events: none; z-index: 1;
    }
    .has-icon input, .has-icon select { padding-left: 36px; }

    input[type=text], input[type=number], input[type=time],
    select, textarea {
      width: 100%; padding: 9px 13px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 10px; font-family: 'Sora', sans-serif;
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
      background-repeat: no-repeat; background-position: right 12px center;
      padding-right: 32px; background-color: var(--gray-50); cursor: pointer;
    }
    input[readonly] {
      background: var(--blue-50) !important;
      color: var(--blue-700) !important;
      font-weight: 700; border-color: var(--blue-100) !important; cursor: default;
    }
    textarea { resize: vertical; min-height: 80px; line-height: 1.55; }

    .fg2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .fg3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

    /* vital boxes */
    .vital-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .vital-box {
      background: var(--gray-50); border: 1px solid var(--gray-200);
      border-radius: 10px; padding: 12px 14px;
    }
    .vb-label {
      font-size: .6rem; font-weight: 700; letter-spacing: .09em;
      text-transform: uppercase; color: var(--gray-400); margin-bottom: 6px;
      display: flex; align-items: center; gap: 5px;
    }
    .vb-label i { color: var(--blue-400); }
    .vital-box input { padding: 7px 10px; font-size: .82rem; }

    /* bmi highlight */
    .bmi-hint {
      font-size: .68rem; color: var(--gray-400);
      margin-top: 6px; display: flex; align-items: center; gap: 4px;
    }
    .bmi-hint i { color: var(--blue-400); }

    /* Select2 light */
    .select2-container { z-index: 10; }
    .select2-container--default .select2-selection--single {
      background: var(--gray-50) !important; border: 1.5px solid var(--gray-200) !important;
      border-radius: 10px !important; height: 42px !important;
      display: flex !important; align-items: center !important;
    }
    .select2-container--default .select2-selection--single:hover { border-color: var(--blue-300) !important; }
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color: var(--blue-500) !important; box-shadow: 0 0 0 3px var(--blue-glow) !important;
      background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--blue-700) !important; font-weight: 600 !important;
      font-family: 'Sora', sans-serif !important; font-size: 13px !important;
      line-height: 42px !important; padding-left: 14px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
      color: var(--gray-400) !important; font-weight: 400 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 42px !important; right: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: var(--gray-400) transparent transparent !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
      border-color: transparent transparent var(--gray-400) !important;
    }
    .select2-dropdown {
      background: var(--white) !important; border: 1.5px solid var(--blue-200) !important;
      border-radius: 10px !important; box-shadow: var(--shadow-lg) !important;
      font-family: 'Sora', sans-serif !important; overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      background: var(--gray-50) !important; border: 1px solid var(--gray-200) !important;
      border-radius: 7px !important; font-family: 'Sora', sans-serif !important;
      font-size: 13px !important; padding: 7px 12px !important; outline: none !important;
      color: var(--gray-700) !important;
    }
    .select2-results__option { color: var(--gray-700) !important; font-size: 13px !important; padding: 9px 14px !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background: var(--blue-50) !important; color: var(--blue-700) !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
      background: var(--blue-100) !important; color: var(--blue-800) !important; font-weight: 700 !important;
    }

    /* ── STEP FOOTER ── */
    .step-footer {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 24px; border-top: 1px solid var(--gray-100);
      background: var(--gray-50);
    }
    .step-counter { font-size: .72rem; color: var(--gray-400); font-weight: 500; }

    .btn-back {
      display: flex; align-items: center; gap: 6px;
      padding: 9px 20px; border-radius: 9px;
      background: var(--white); border: 1.5px solid var(--gray-200);
      color: var(--gray-500); font-family: 'Sora', sans-serif;
      font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .16s;
    }
    .btn-back:hover { border-color: var(--blue-200); color: var(--blue-600); background: var(--blue-50); }

    .btn-next {
      display: flex; align-items: center; gap: 6px;
      padding: 9px 24px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; color: #fff; font-family: 'Sora', sans-serif;
      font-size: .8rem; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(37,99,235,.26); transition: all .18s;
    }
    .btn-next:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.36); }

    .btn-submit {
      display: flex; align-items: center; gap: 7px;
      padding: 10px 28px; border-radius: 9px;
      background: linear-gradient(135deg, var(--green-700), var(--green-600));
      border: none; color: #fff; font-family: 'Sora', sans-serif;
      font-size: .85rem; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(5,150,105,.28); transition: all .18s;
    }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(5,150,105,.38); }

    /* responsive */
    @media (max-width: 600px) {
      .topbar { padding: 0 14px; }
      .page { padding: 18px 12px 48px; }
      .step-body { padding: 18px; }
      .fg2, .fg3, .vital-grid { grid-template-columns: 1fr; }
      .steps-nav { flex-wrap: wrap; }
    }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="tb-brand">
    <div class="tb-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="tb-name">Angelora</span>
    <span class="tb-sep">·</span>
    <span class="tb-page">Record Vitals</span>
  </div>
  <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-heart-pulse-fill"></i> Nurse · Vital Signs</div>
    <div class="ph-title">Record <em>Vital Signs</em></div>
    <div class="ph-sub">Complete all 4 steps to record a patient's vital signs accurately.</div>
  </div>

  <!-- ALERT -->
  <?php if ($message): ?>
  <div class="alert-box <?= $messageType ?>">
    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <!-- PROGRESS -->
  <div class="progress-card">
    <div class="progress-top">
      <div class="progress-label" id="progressLabel">Step 1 of 4 — Patient Info</div>
      <div class="progress-pct" id="progressPct">25%</div>
    </div>
    <div class="progress-track">
      <div class="progress-fill" id="progressFill" style="width:25%"></div>
    </div>
    <div class="steps-nav">
      <div class="step-pill active" id="pill1">
        <i class="bi bi-person-badge-fill"></i> Patient
      </div>
      <div class="step-pill" id="pill2">
        <i class="bi bi-heart-pulse-fill"></i> Vitals
      </div>
      <div class="step-pill" id="pill3">
        <i class="bi bi-rulers"></i> Measurements
      </div>
      <div class="step-pill" id="pill4">
        <i class="bi bi-clipboard2-check-fill"></i> Observations
      </div>
    </div>
  </div>

  <!-- FORM -->
  <form method="POST" id="vitalForm">
  <div class="form-card">

    <!-- ── STEP 1: PATIENT INFO ── -->
    <div class="form-step active" id="step1">
      <div class="step-header">
        <div class="step-num">1</div>
        <div>
          <div class="step-title">Patient Info</div>
          <div class="step-sub">Identify the patient and the recording nurse</div>
        </div>
      </div>
      <div class="step-body">
        <div class="field">
          <label>Select Patient <span class="req">*</span></label>
          <select name="patient_id" id="patient_id" required style="width:100%">
            <option value=""></option>
          </select>
        </div>
        <div class="field">
          <label>Recorded By (Nurse) <span class="req">*</span></label>
          <div class="input-wrap has-icon">
            <i class="bi bi-person-badge-fill input-icon"></i>
            <select name="recorded_by" required>
              <option value="" disabled selected>Select Nurse</option>
              <?php foreach ($nurses as $nurse): ?>
                <option value="<?= $nurse['user_id'] ?>"><?= htmlspecialchars($nurse['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="step-footer">
        <span class="step-counter">Step 1 of 4</span>
        <button type="button" class="btn-next" onclick="goStep(2)">
          Next <i class="bi bi-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ── STEP 2: VITAL SIGNS ── -->
    <div class="form-step" id="step2">
      <div class="step-header">
        <div class="step-num">2</div>
        <div>
          <div class="step-title">Vital Signs</div>
          <div class="step-sub">Core clinical measurements</div>
        </div>
      </div>
      <div class="step-body">
        <div class="vital-grid">
          <div class="vital-box">
            <div class="vb-label"><i class="bi bi-thermometer-half"></i> Temperature (°C)</div>
            <input type="number" step="0.1" name="temperature" placeholder="e.g. 36.5" required>
          </div>
          <div class="vital-box">
            <div class="vb-label"><i class="bi bi-heart-fill"></i> Pulse Rate (bpm)</div>
            <input type="number" name="pulse_rate" placeholder="e.g. 72" required>
          </div>
          <div class="vital-box">
            <div class="vb-label"><i class="bi bi-lungs-fill"></i> Respiration Rate</div>
            <input type="number" name="respiration_rate" placeholder="breaths/min" required>
          </div>
          <div class="vital-box">
            <div class="vb-label"><i class="bi bi-activity"></i> Blood Pressure</div>
            <input type="text" name="blood_pressure" placeholder="e.g. 120/80" required>
          </div>
          <div class="vital-box">
            <div class="vb-label"><i class="bi bi-droplet-fill"></i> O₂ Saturation (%)</div>
            <input type="number" name="oxygen_saturation" placeholder="e.g. 98" required>
          </div>
          <div class="vital-box">
            <div class="vb-label"><i class="bi bi-emoji-frown-fill"></i> Pain Level (0–10)</div>
            <input type="number" min="0" max="10" name="pain_level" placeholder="0 – 10" required>
          </div>
        </div>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-back" onclick="goStep(1)">
          <i class="bi bi-arrow-left"></i> Back
        </button>
        <span class="step-counter">Step 2 of 4</span>
        <button type="button" class="btn-next" onclick="goStep(3)">
          Next <i class="bi bi-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ── STEP 3: MEASUREMENTS ── -->
    <div class="form-step" id="step3">
      <div class="step-header">
        <div class="step-num">3</div>
        <div>
          <div class="step-title">Physical Measurements</div>
          <div class="step-sub">Height, weight and auto-calculated BMI</div>
        </div>
      </div>
      <div class="step-body">
        <div class="fg2">
          <div class="field">
            <label>Height (cm)</label>
            <div class="input-wrap has-icon">
              <i class="bi bi-arrows-vertical input-icon"></i>
              <input type="number" name="height_cm" id="height_cm" placeholder="e.g. 170" oninput="calcBMI()">
            </div>
          </div>
          <div class="field">
            <label>Weight (kg)</label>
            <div class="input-wrap has-icon">
              <i class="bi bi-speedometer input-icon"></i>
              <input type="number" name="weight_kg" id="weight_kg" placeholder="e.g. 70" oninput="calcBMI()">
            </div>
          </div>
        </div>
        <div class="field">
          <label>BMI (Auto-calculated)</label>
          <input type="text" name="bmi" id="bmi" readonly placeholder="Calculated from height & weight">
          <div class="bmi-hint"><i class="bi bi-info-circle-fill"></i> Normal range: 18.5 – 24.9 kg/m²</div>
        </div>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-back" onclick="goStep(2)">
          <i class="bi bi-arrow-left"></i> Back
        </button>
        <span class="step-counter">Step 3 of 4</span>
        <button type="button" class="btn-next" onclick="goStep(4)">
          Next <i class="bi bi-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- ── STEP 4: OBSERVATIONS ── -->
    <div class="form-step" id="step4">
      <div class="step-header">
        <div class="step-num">4</div>
        <div>
          <div class="step-title">Additional Observations</div>
          <div class="step-sub">Blood sugar, consciousness level and symptom notes</div>
        </div>
      </div>
      <div class="step-body">
        <div class="fg2">
          <div class="field">
            <label>Blood Sugar (mg/dL)</label>
            <div class="input-wrap has-icon">
              <i class="bi bi-droplet-half input-icon"></i>
              <input type="number" name="blood_sugar" placeholder="e.g. 90">
            </div>
          </div>
          <div class="field">
            <label>Level of Consciousness (AVPU)</label>
            <div class="input-wrap has-icon">
              <i class="bi bi-brain input-icon"></i>
              <select name="consciousness_level">
                <option value="">Select AVPU level</option>
                <option value="Alert">Alert</option>
                <option value="Verbal">Verbal</option>
                <option value="Pain">Pain</option>
                <option value="Unresponsive">Unresponsive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="field">
          <label>Time Vitals Taken</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-clock-fill input-icon"></i>
            <input type="time" name="vitals_time">
          </div>
        </div>
        <div class="field">
          <label>Symptoms / Notes</label>
          <textarea name="symptoms_notes" rows="3"
            placeholder="Describe any observed symptoms, patient complaints or additional observations…"></textarea>
        </div>
      </div>
      <div class="step-footer">
        <button type="button" class="btn-back" onclick="goStep(3)">
          <i class="bi bi-arrow-left"></i> Back
        </button>
        <span class="step-counter">Step 4 of 4</span>
        <button type="submit" class="btn-submit">
          <i class="bi bi-check-circle-fill"></i> Submit Vital Signs
        </button>
      </div>
    </div>

  </div><!-- /form-card -->
  </form>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const stepLabels = ['','Patient Info','Vital Signs','Physical Measurements','Additional Observations'];
let currentStep = 1;

function goStep(n) {
  // hide all
  document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
  document.getElementById('step' + n).classList.add('active');
  currentStep = n;

  // progress bar
  const pct = Math.round((n / 4) * 100);
  document.getElementById('progressFill').style.width = pct + '%';
  document.getElementById('progressPct').textContent  = pct + '%';
  document.getElementById('progressLabel').textContent = 'Step ' + n + ' of 4 — ' + stepLabels[n];

  // step pills
  [1,2,3,4].forEach(i => {
    const p = document.getElementById('pill' + i);
    p.classList.remove('active','done');
    if (i === n) p.classList.add('active');
    else if (i < n) p.classList.add('done');
  });

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function calcBMI() {
  const h = parseFloat(document.getElementById('height_cm').value) / 100;
  const w = parseFloat(document.getElementById('weight_kg').value);
  const bmiEl = document.getElementById('bmi');
  if (h > 0 && w > 0) {
    bmiEl.value = (w / (h * h)).toFixed(1);
  } else {
    bmiEl.value = '';
  }
}

// Select2 for patient dropdown
$(document).ready(function () {
  $('#patient_id').select2({
    placeholder: 'Search and select a patient…',
    allowClear: true,
    width: '100%',
    ajax: {
      url: 'search_patients.php',
      dataType: 'json',
      delay: 250,
      data: params => ({ term: params.term }),
      processResults: data => ({ results: data.results })
    }
  });
});
</script>
</body>
</html>