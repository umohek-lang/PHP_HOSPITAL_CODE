<?php
require '../db.php';
session_start();

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("Location: ../login.php"); exit();
}

$userRole = (int)$_SESSION['user']['role_id'];
if (!in_array($userRole, [8, 3])) {
    $_SESSION['error'] = "You do not have permission to access this page.";
    header("Location: ../login.php"); exit();
}

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id       = $_POST['patient_id']       ?? null;
    $doctor_id        = $_POST['doctor_id']         ?? null;
    $appointment_date = $_POST['appointment_date']  ?? null;
    $appointment_time = $_POST['appointment_time']  ?? null;
    $status           = $_POST['status']            ?? 'Pending';

    if ($patient_id && $doctor_id && $appointment_date && $appointment_time) {
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $status])) {
            $success = "Appointment created successfully.";
        } else {
            $error = "Failed to create appointment.";
        }
    } else {
        $error = "All fields are required.";
    }
}

$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$stmt     = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role_id = ?");
$stmt->execute([2]);
$doctors  = $stmt->fetchAll(PDO::FETCH_ASSOC);

$staff_name = $_SESSION['user']['full_name'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Appointment — Angelora Hospital</title>

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
      --green-200:  #a7f3d0;
      --green-700:  #047857;
      --amber-500:  #f59e0b;
      --amber-50:   #fffbeb;
      --amber-100:  #fef3c7;
      --amber-700:  #b45309;
      --red-500:    #ef4444;
      --red-50:     #fef2f2;
      --red-100:    #fee2e2;
      --red-700:    #b91c1c;
      --violet-500: #8b5cf6;
      --violet-50:  #f5f3ff;
      --violet-100: #ede9fe;
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

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 10px; }

    /* ── Top Bar ── */
    .topbar {
      position: sticky; top: 0; z-index: 100;
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
    .page { max-width: 780px; margin: 0 auto; padding: 36px 24px 60px; }

    /* ── Header ── */
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

    /* ── Alerts ── */
    .alert {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px 18px; border-radius: 10px;
      font-size: 13.5px; line-height: 1.5; margin-bottom: 20px;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    .alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .alert-error   { background: var(--red-50);   border: 1px solid var(--red-100);   color: var(--red-700); }
    .alert-error i { color: var(--red-500); }
    .alert-success   { background: var(--green-50);  border: 1px solid var(--green-100);  color: var(--green-700); }
    .alert-success i { color: var(--green-500); }

    /* ── Form Card ── */
    .form-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow-md);
    }

    /* ── Sections ── */
    .form-section {
      padding: 28px 32px;
      border-bottom: 1px solid var(--gray-100);
    }
    .form-section:last-child { border-bottom: none; }

    .section-heading {
      display: flex; align-items: center; gap: 12px; margin-bottom: 22px;
    }
    .section-num {
      width: 28px; height: 28px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; color: white; flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(37,99,235,.25);
    }
    .section-name {
      font-size: 12px; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--blue-700);
    }
    .section-line { flex: 1; height: 1px; background: var(--gray-100); }

    /* ── Grid ── */
    .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .col-full { grid-column: 1 / -1; }
    @media (max-width: 600px) { .field-grid { grid-template-columns: 1fr; } .col-full { grid-column: auto; } }

    /* ── Field ── */
    .field { display: flex; flex-direction: column; gap: 7px; }
    .field label {
      font-size: 11px; font-weight: 700; letter-spacing: .06em;
      text-transform: uppercase; color: var(--gray-500);
      display: flex; align-items: center; gap: 5px;
    }
    .field label .req { color: var(--red-500); }

    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: 15px; pointer-events: none; z-index: 3;
    }

    input[type="date"],
    input[type="time"],
    select {
      width: 100%;
      padding: 12px 14px 12px 40px;
      background: var(--gray-50);
      border: 1.5px solid var(--gray-200);
      border-radius: 9px;
      color: var(--gray-700);
      font-family: 'Sora', sans-serif;
      font-size: 13.5px;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
      appearance: none;
    }
    input:hover, select:hover { border-color: var(--blue-300); }
    input:focus, select:focus {
      border-color: var(--blue-500);
      background: var(--white);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }
    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 13px center;
      padding-right: 36px; background-color: var(--gray-50);
    }

    /* ── Status pills ── */
    .status-hint { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
    .status-pill {
      font-size: 10.5px; font-weight: 700; padding: 4px 12px;
      border-radius: 999px; letter-spacing: .05em;
    }
    .pill-pending  { background: var(--amber-50);  border: 1px solid var(--amber-100); color: var(--amber-700); }
    .pill-confirm  { background: var(--green-50);  border: 1px solid var(--green-100); color: var(--green-700); }
    .pill-complete { background: var(--blue-50);   border: 1px solid var(--blue-100);  color: var(--blue-700); }
    .pill-cancel   { background: var(--red-50);    border: 1px solid var(--red-100);   color: var(--red-700); }

    /* ── Select2 Light Theme ── */
    .select2-container { z-index: 10; }
    .select2-container--default .select2-selection--single {
      background: var(--gray-50) !important;
      border: 1.5px solid var(--gray-200) !important;
      border-radius: 9px !important;
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
      color: var(--gray-700) !important;
      font-family: 'Sora', sans-serif !important;
      font-size: 13.5px !important;
      line-height: 46px !important;
      padding-left: 40px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
      color: var(--gray-400) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 46px !important; right: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: var(--gray-400) transparent transparent !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
      border-color: transparent transparent var(--gray-400) !important;
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
      color: var(--gray-700) !important;
      font-size: 13.5px !important;
      padding: 10px 16px !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background: var(--blue-50) !important;
      color: var(--blue-700) !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
      background: var(--blue-100) !important;
      color: var(--blue-800) !important;
      font-weight: 600 !important;
    }
    .select2-results__option--disabled { color: var(--gray-400) !important; }

    /* ── Summary Card ── */
    .summary-card {
      background: linear-gradient(135deg, var(--blue-50), #f0f7ff);
      border: 1.5px solid var(--blue-100);
      border-radius: 12px;
      padding: 20px 24px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px 32px;
    }
    .summary-row { display: flex; flex-direction: column; gap: 4px; }
    .summary-key {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: .1em; color: var(--blue-400);
    }
    .summary-val {
      font-size: 13.5px; font-weight: 600; color: var(--gray-900);
      min-height: 20px;
    }
    .summary-val.empty {
      color: var(--gray-300); font-weight: 400;
      font-style: italic; font-size: 12px;
    }

    /* Summary icon row */
    .summary-icon-row {
      display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
    }
    .summary-icon-row i { font-size: 15px; color: var(--blue-500); }
    .summary-icon-row span {
      font-size: 11px; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--blue-600);
    }

    /* ── Form Footer ── */
    .form-footer {
      padding: 20px 32px;
      background: var(--gray-50);
      border-top: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: space-between;
      gap: 16px; flex-wrap: wrap;
    }
    .form-footer-note {
      font-size: 12px; color: var(--gray-500);
      display: flex; align-items: center; gap: 7px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 8px; padding: 8px 14px;
    }
    .form-footer-note i { color: var(--blue-500); font-size: 14px; }

    .btn-submit {
      display: flex; align-items: center; gap: 9px;
      padding: 13px 32px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; border-radius: 10px;
      color: white; font-family: 'Sora', sans-serif;
      font-size: 14px; font-weight: 700; cursor: pointer;
      position: relative; overflow: hidden;
      transition: all .2s;
      box-shadow: 0 6px 20px rgba(37,99,235,.35);
    }
    .btn-submit::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.15) 0%, transparent 60%);
    }
    .btn-submit:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(37,99,235,.45);
    }
    .btn-submit:active:not(:disabled) { transform: translateY(0); }
    .btn-submit:disabled { opacity: .55; cursor: not-allowed; }

    .btn-spinner {
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: white; border-radius: 50%;
      animation: spin .7s linear infinite; display: none;
    }
    .btn-submit.loading .btn-spinner { display: block; }
    .btn-submit.loading .btn-text   { opacity: .8; }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 600px) {
      .topbar { padding: 0 16px; }
      .topbar-date { display: none; }
      .page { padding: 20px 14px 48px; }
      .form-section { padding: 18px 16px; }
      .form-footer { flex-direction: column; align-items: stretch; }
      .btn-submit { justify-content: center; }
      .summary-card { grid-template-columns: 1fr; }
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
    <span class="brand-page">Book Appointment</span>
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
      <span>Book Appointment</span>
    </div>
    <h1 class="page-title">Book <em>New Appointment</em></h1>
    <p class="page-sub">Schedule a patient appointment with an available doctor.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <i class="bi bi-check-circle-fill"></i>
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <form method="POST" id="apptForm">
    <div class="form-card">

      <!-- Section 1: Patient & Doctor -->
      <div class="form-section">
        <div class="section-heading">
          <div class="section-num">1</div>
          <div class="section-name">Patient & Doctor</div>
          <div class="section-line"></div>
        </div>
        <div class="field-grid">

          <div class="field">
            <label><i class="bi bi-person-fill" style="font-size:13px;color:var(--blue-500)"></i> Patient <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-person-badge input-icon"></i>
              <select name="patient_id" id="patientSelect" required>
                <option value=""></option>
                <?php foreach ($patients as $p): ?>
                  <option value="<?= htmlspecialchars($p['patient_id']) ?>" data-name="<?= htmlspecialchars($p['full_name']) ?>">
                    <?= htmlspecialchars($p['full_name']) ?> · ID: <?= htmlspecialchars($p['patient_id']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label><i class="bi bi-person-badge-fill" style="font-size:13px;color:var(--blue-500)"></i> Doctor <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-person-lines-fill input-icon"></i>
              <select name="doctor_id" id="doctorSelect" required>
                <option value=""></option>
                <?php foreach ($doctors as $d): ?>
                  <option value="<?= htmlspecialchars($d['user_id']) ?>" data-name="<?= htmlspecialchars($d['full_name']) ?>">
                    Dr. <?= htmlspecialchars($d['full_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

        </div>
      </div>

      <!-- Section 2: Schedule & Status -->
      <div class="form-section">
        <div class="section-heading">
          <div class="section-num">2</div>
          <div class="section-name">Schedule & Status</div>
          <div class="section-line"></div>
        </div>
        <div class="field-grid">

          <div class="field">
            <label>Appointment Date <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-calendar3 input-icon"></i>
              <input type="date" name="appointment_date" id="apptDate"
                     min="<?= date('Y-m-d') ?>" required>
            </div>
          </div>

          <div class="field">
            <label>Appointment Time <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-clock input-icon"></i>
              <input type="time" name="appointment_time" id="apptTime" required>
            </div>
          </div>

          <div class="field col-full">
            <label>Status <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-flag-fill input-icon"></i>
              <select name="status" id="statusSelect" required>
                <option value="Pending">Pending</option>
                <option value="Confirmed">Confirmed</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="status-hint">
              <span class="status-pill pill-pending">⏳ Pending</span>
              <span class="status-pill pill-confirm">✅ Confirmed</span>
              <span class="status-pill pill-complete">🔵 Completed</span>
              <span class="status-pill pill-cancel">❌ Cancelled</span>
            </div>
          </div>

        </div>
      </div>

      <!-- Section 3: Summary -->
      <div class="form-section">
        <div class="section-heading">
          <div class="section-num">3</div>
          <div class="section-name">Appointment Summary</div>
          <div class="section-line"></div>
        </div>
        <div class="summary-card">
          <div class="summary-row">
            <span class="summary-key">Patient</span>
            <span class="summary-val empty" id="sumPatient">Not selected</span>
          </div>
          <div class="summary-row">
            <span class="summary-key">Doctor</span>
            <span class="summary-val empty" id="sumDoctor">Not selected</span>
          </div>
          <div class="summary-row">
            <span class="summary-key">Date</span>
            <span class="summary-val empty" id="sumDate">—</span>
          </div>
          <div class="summary-row">
            <span class="summary-key">Time</span>
            <span class="summary-val empty" id="sumTime">—</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="form-footer">
        <div class="form-footer-note">
          <i class="bi bi-info-circle-fill"></i>
          All appointments are saved to the patient's record immediately.
        </div>
        <button type="submit" id="submitBtn" class="btn-submit">
          <span class="btn-text"><i class="bi bi-calendar-plus-fill"></i> Create Appointment</span>
          <div class="btn-spinner"></div>
        </button>
      </div>

    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {

  $('#patientSelect').select2({
    placeholder: 'Search patient by name or ID…',
    allowClear: true, width: '100%'
  });
  $('#doctorSelect').select2({
    placeholder: 'Search and select a doctor…',
    allowClear: true, width: '100%'
  });

  function updateSummary() {
    const patient = $('#patientSelect option:selected').data('name') || '';
    const doctor  = $('#doctorSelect option:selected').data('name')  || '';
    const date    = document.getElementById('apptDate').value;
    const time    = document.getElementById('apptTime').value;

    setSummary('sumPatient', patient  ? patient          : null);
    setSummary('sumDoctor',  doctor   ? 'Dr. ' + doctor  : null);
    setSummary('sumDate',    date     ? formatDate(date)  : null);
    setSummary('sumTime',    time     ? formatTime(time)  : null);
  }

  function setSummary(id, val) {
    const el = document.getElementById(id);
    if (val) {
      el.textContent = val;
      el.classList.remove('empty');
    } else {
      el.textContent = (id === 'sumPatient' || id === 'sumDoctor') ? 'Not selected' : '—';
      el.classList.add('empty');
    }
  }

  function formatDate(d) {
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('en-NG', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' });
  }

  function formatTime(t) {
    const [h, m] = t.split(':');
    const ampm = h >= 12 ? 'PM' : 'AM';
    return ((h % 12) || 12) + ':' + m + ' ' + ampm;
  }

  $('#patientSelect, #doctorSelect').on('change', updateSummary);
  document.getElementById('apptDate').addEventListener('change', updateSummary);
  document.getElementById('apptTime').addEventListener('change', updateSummary);

  document.getElementById('apptForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.disabled = true;
  });

});
</script>
</body>
</html>