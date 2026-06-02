<?php
require '../includes/auth.php';
require '../db.php';

try {
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=ablehand", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_POST['submit'])) {
        $patient_id   = $_POST['patient_id'];
        $test_name    = $_POST['test_name'];
        $test_date    = $_POST['test_date'];
        $result       = $_POST['result'];
        $status       = $_POST['status'];
        $requested_by = $_POST['requested_by'];

        $report_file = '';
        if (!empty($_FILES['report_file']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $report_file = $targetDir . basename($_FILES['report_file']['name']);
            move_uploaded_file($_FILES['report_file']['tmp_name'], $report_file);
        }

        $stmt = $pdo->prepare("INSERT INTO lab_tests (patient_id, test_name, test_date, result, status, report_file, requested_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $test_name, $test_date, $result, $status, $report_file, $requested_by]);

        header("Location: view_lab_test.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conduct Lab Test — Angelora</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900: #1e3a8a; --blue-800: #1e40af; --blue-700: #1d4ed8;
      --blue-600: #2563eb; --blue-500: #3b82f6; --blue-400: #60a5fa;
      --blue-300: #93c5fd; --blue-200: #bfdbfe; --blue-100: #dbeafe;
      --blue-50:  #eff6ff;

      --white:    #ffffff;
      --gray-50:  #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
      --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
      --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;

      --green: #16a34a; --green-bg: #dcfce7; --green-100: #bbf7d0;
      --red:   #dc2626; --red-bg:   #fef2f2; --red-100:   #fecaca;

      --radius: 12px;
      --sh-sm: 0 1px 3px rgba(0,0,0,.06), 0 2px 8px rgba(0,0,0,.04);
      --sh:    0 4px 16px rgba(37,99,235,.1), 0 1px 4px rgba(0,0,0,.05);
      --sh-lg: 0 12px 40px rgba(37,99,235,.15), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body {
      min-height: 100vh; font-family: 'Sora', sans-serif;
      background: var(--gray-50); color: var(--gray-800);
    }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 8px; }

    /* ── TOPBAR ─────────────────────── */
    .topbar {
      position: sticky; top: 0; z-index: 100; height: 62px;
      background: var(--white); border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--sh-sm);
      display: flex; align-items: center; justify-content: space-between; padding: 0 36px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .brand-mark i { font-size: 17px; color: white; }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 17px; color: var(--gray-900); }
    .brand-sub  { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue-600); }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .date-pill {
      display: flex; align-items: center; gap: 6px; padding: 5px 13px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      font-size: 11.5px; color: var(--blue-700); font-weight: 500;
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200); color: var(--gray-600);
      font-family: 'Sora', sans-serif; font-size: 12.5px; font-weight: 500;
      text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-600); }

    /* ── PAGE ───────────────────────── */
    .page { max-width: 1020px; margin: 0 auto; padding: 36px 24px 72px; }

    .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--gray-400); margin-bottom: 12px; }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }

    .page-title { font-family: 'Instrument Serif', serif; font-size: clamp(1.5rem, 3vw, 2rem); color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-top: 5px; margin-bottom: 28px; }

    /* ── SUCCESS ALERT ──────────────── */
    .success-alert {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 18px; border-radius: 10px; margin-bottom: 20px;
      background: var(--green-bg); border: 1px solid var(--green-100);
      color: var(--green); font-size: 13.5px; font-weight: 600;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:none;} }

    /* ── FORM CARD ──────────────────── */
    .form-card {
      background: var(--white); border: 1.5px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden; box-shadow: var(--sh);
    }

    .form-card-header {
      padding: 18px 28px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      display: flex; align-items: center; gap: 12px;
    }
    .form-card-header-icon {
      width: 40px; height: 40px; border-radius: 10px;
      background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .form-card-header-icon i { font-size: 18px; color: white; }
    .form-card-title { font-family: 'Instrument Serif', serif; font-size: 20px; color: white; }
    .form-card-sub   { font-size: 12px; color: rgba(255,255,255,.65); margin-top: 2px; }

    .form-body { padding: 28px; }

    /* ── SECTIONS ───────────────────── */
    .form-section { margin-bottom: 28px; }
    .section-label {
      display: flex; align-items: center; gap: 8px;
      font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
      color: var(--blue-600); margin-bottom: 16px; padding-bottom: 8px;
      border-bottom: 1px solid var(--blue-100);
    }
    .section-label i { font-size: 13px; }

    /* Grid columns */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .span-2 { grid-column: span 2; }

    /* ── FORM CONTROLS ──────────────── */
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field label {
      font-size: 12px; font-weight: 600; color: var(--gray-700); letter-spacing: .02em;
    }
    .field label .req { color: var(--blue-500); margin-left: 2px; }

    .form-input, .form-select-native {
      width: 100%; padding: 9px 13px; border-radius: 8px;
      border: 1.5px solid var(--gray-200); background: var(--white);
      font-family: 'Sora', sans-serif; font-size: 13px; color: var(--gray-800);
      transition: border-color .18s, box-shadow .18s; outline: none;
    }
    .form-input:focus, .form-select-native:focus {
      border-color: var(--blue-400); box-shadow: 0 0 0 3px rgba(96,165,250,.15);
    }
    .form-input::placeholder { color: var(--gray-400); }

    /* File input */
    .file-input-wrap {
      position: relative; display: flex; align-items: center;
      border: 1.5px dashed var(--gray-300); border-radius: 8px;
      padding: 10px 14px; gap: 10px; cursor: pointer;
      transition: border-color .18s, background .18s;
      background: var(--gray-50);
    }
    .file-input-wrap:hover { border-color: var(--blue-300); background: var(--blue-50); }
    .file-input-wrap input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .file-icon { color: var(--blue-500); font-size: 18px; flex-shrink: 0; }
    .file-text { font-size: 12.5px; color: var(--gray-500); }
    .file-text strong { color: var(--gray-700); }

    /* ── TEST SELECTOR AREA ─────────── */
    .test-selector-card {
      background: var(--blue-50); border: 1.5px solid var(--blue-100);
      border-radius: 10px; padding: 18px; margin-bottom: 16px;
    }
    .test-selector-card .field label { color: var(--blue-800); }

    /* Dynamic fields container */
    #test-components-container .test-section {
      background: var(--white); border: 1.5px solid var(--blue-100);
      border-radius: 10px; overflow: hidden; margin-top: 16px;
    }
    .test-section-head {
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      padding: 11px 18px; display: flex; align-items: center; gap: 8px;
    }
    .test-section-head i { color: rgba(255,255,255,.7); font-size: 14px; }
    .test-section-title { font-size: 13px; font-weight: 700; color: white; }
    .test-fields-grid { padding: 16px 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    /* ── STATUS BADGES ──────────────── */
    .status-options { display: flex; gap: 10px; flex-wrap: wrap; }
    .status-opt { display: none; }
    .status-opt + label {
      display: flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 8px;
      border: 1.5px solid var(--gray-200); background: var(--white);
      font-size: 12.5px; font-weight: 600; cursor: pointer;
      color: var(--gray-600); transition: all .15s;
    }
    .status-opt + label i { font-size: 14px; }
    .status-opt:checked + label { border-color: var(--blue-500); background: var(--blue-50); color: var(--blue-700); }
    #status-completed:checked + label { border-color: var(--green); background: var(--green-bg); color: var(--green); }
    #status-progress:checked  + label { border-color: #d97706; background: #fef3c7; color: #b45309; }

    /* ── SUBMIT AREA ────────────────── */
    .submit-area {
      margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: flex-end; gap: 10px;
    }
    .btn-cancel {
      padding: 10px 22px; border-radius: 9px;
      background: var(--gray-100); border: 1.5px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 6px; transition: all .18s;
    }
    .btn-cancel:hover { background: var(--gray-200); color: var(--gray-800); }
    .btn-submit {
      padding: 10px 28px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; color: white; font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 700; cursor: pointer;
      display: inline-flex; align-items: center; gap: 7px;
      box-shadow: 0 3px 12px rgba(37,99,235,.3); transition: all .18s;
    }
    .btn-submit:hover { opacity: .92; transform: translateY(-1px); }

    /* ── SELECT2 OVERRIDES ──────────── */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
      height: 40px; border: 1.5px solid var(--gray-200); border-radius: 8px;
      display: flex; align-items: center; padding: 0 13px;
      font-family: 'Sora', sans-serif; font-size: 13px; background: var(--white);
      transition: border-color .18s, box-shadow .18s;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open  .select2-selection--single {
      border-color: var(--blue-400); box-shadow: 0 0 0 3px rgba(96,165,250,.15); outline: none;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--gray-800); padding: 0; line-height: 40px;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--gray-400); }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 38px; right: 10px;
    }
    .select2-dropdown {
      border: 1.5px solid var(--blue-200); border-radius: 10px; overflow: hidden;
      box-shadow: var(--sh-lg); font-family: 'Sora', sans-serif; font-size: 13px;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      border: 1.5px solid var(--gray-200); border-radius: 7px; padding: 7px 12px;
      font-family: 'Sora', sans-serif; font-size: 13px; outline: none;
    }
    .select2-container--default .select2-results__option--highlighted { background: var(--blue-600); }
    .select2-results__option { padding: 9px 14px; }

    @media (max-width: 768px) {
      .topbar { padding: 0 16px; } .date-pill { display: none; }
      .page { padding: 20px 14px 52px; }
      .grid-2, .grid-3 { grid-template-columns: 1fr; }
      .span-2 { grid-column: span 1; }
      .test-fields-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ── TOPBAR ──────────────────────────── -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-mark"><i class="bi bi-hospital"></i></div>
    <div>
      <div class="brand-name">Angelora</div>
      <div class="brand-sub">Laboratory</div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="date-pill"><i class="bi bi-calendar3"></i><?= date('D, d M Y') ?></div>
    <a href="lab_welcome.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</header>

<!-- ── PAGE ───────────────────────────── -->
<div class="page">

  <div class="breadcrumb">
    <a href="lab_welcome.php"><i class="bi bi-house"></i> Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Conduct Lab Test</span>
  </div>

  <h1 class="page-title">Conduct <em>Lab Test</em></h1>
  <p class="page-sub">Fill in patient and test details. Select the test type to reveal specific result fields.</p>

  <div class="form-card">

    <!-- Card header -->
    <div class="form-card-header">
      <div class="form-card-header-icon"><i class="bi bi-eyedropper"></i></div>
      <div>
        <div class="form-card-title">Add Lab Test Result</div>
        <div class="form-card-sub">All fields marked <span style="color:rgba(255,255,255,.5);">*</span> are required</div>
      </div>
    </div>

    <div class="form-body">
      <form action="lab_tests.php" method="POST" enctype="multipart/form-data" id="labForm">

        <!-- ── PATIENT INFO ─────────────── -->
        <div class="form-section">
          <div class="section-label">
            <i class="bi bi-person-badge"></i> Patient Information
          </div>
          <div class="grid-2">
            <div class="field span-2">
              <label>Patient <span class="req">*</span></label>
              <select id="patient-select" name="patient_id" required></select>
            </div>
            <div class="field">
              <label>Requested By <span class="req">*</span></label>
              <input type="text" class="form-input" name="requested_by" placeholder="e.g. Dr. Okafor" required>
            </div>
            <div class="field">
              <label>Test Date <span class="req">*</span></label>
              <input type="date" class="form-input" name="test_date" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
        </div>

        <!-- ── TEST DETAILS ────────────── -->
        <div class="form-section">
          <div class="section-label">
            <i class="bi bi-flask"></i> Test Details
          </div>

          <div class="grid-2" style="margin-bottom:18px;">
            <div class="field">
              <label>Test Name <span class="req">*</span></label>
              <input type="text" class="form-input" name="test_name" id="test-name-field" placeholder="Will auto-fill when you select below" required>
            </div>
            <div class="field">
              <label>Select Test Type <span class="req">*</span></label>
              <div class="test-selector-card" style="padding:10px 14px;">
                <select class="form-select-native" id="lab-test-selector">
                  <option value="">— Choose a test to load fields —</option>
                  <optgroup label="Haematology">
                    <option value="fbc">Full Blood Count (FBC)</option>
                    <option value="reticulocyte">Reticulocyte Count</option>
                    <option value="hb_electrophoresis">Haemoglobin Electrophoresis</option>
                    <option value="pbs">Peripheral Blood Smear</option>
                    <option value="esr">ESR</option>
                    <option value="blood_group">Blood Group &amp; Genotype</option>
                    <option value="genotype">Genotype</option>
                    <option value="blood_culture">Blood Culture</option>
                  </optgroup>
                  <optgroup label="Chemistry">
                    <option value="kft">Kidney Function Test</option>
                    <option value="lft">Liver Function Test</option>
                    <option value="lipid">Lipid Profile</option>
                    <option value="rbs_fbs">RBS / FBS</option>
                    <option value="blood_sugar">Blood Sugar</option>
                    <option value="hba1c">HbA1c</option>
                    <option value="psa">PSA</option>
                  </optgroup>
                  <optgroup label="Serology / Immunology">
                    <option value="serology">Serology Panel</option>
                    <option value="widal">Widal Test</option>
                    <option value="hbv_profile">HBV Profile</option>
                    <option value="hiv_test">HIV Test</option>
                    <option value="hsv">Herpes Simplex Virus (HSV)</option>
                    <option value="h_pylori">H. Pylori</option>
                    <option value="asotitre">ASO Titre</option>
                    <option value="rheumatoid_factor">Rheumatoid Factor (RF)</option>
                    <option value="coombs">Coombs Test</option>
                    <option value="pt_hcg">Pregnancy Test (HCG)</option>
                    <option value="microfilaria">Microfilaria</option>
                  </optgroup>
                  <optgroup label="Microbiology">
                    <option value="urinalysis">Urinalysis / MCS</option>
                    <option value="stool">Stool MCS / Microscopy</option>
                    <option value="hvs">HVS (High Vaginal Swab)</option>
                    <option value="sputum">Sputum MCS</option>
                    <option value="wound_swab">Wound Swab</option>
                    <option value="afb">AFB (Acid-Fast Bacilli)</option>
                    <option value="csf">CSF Analysis</option>
                    <option value="fob">Faecal Occult Blood (FOB)</option>
                  </optgroup>
                  <optgroup label="Hormonal">
                    <option value="hormonal">Hormonal Panel</option>
                  </optgroup>
                  <optgroup label="Other">
                    <option value="malaria">Malaria Parasite</option>
                    <option value="sfa">Seminal Fluid Analysis (SFA)</option>
                    <option value="papsmear">Pap Smear</option>
                  </optgroup>
                </select>
              </div>
            </div>
          </div>

          <!-- Dynamic fields render here -->
          <div id="test-components-container"></div>
          <input type="hidden" name="result" id="result-field">
        </div>

        <!-- ── STATUS ──────────────────── -->
        <div class="form-section">
          <div class="section-label">
            <i class="bi bi-activity"></i> Test Status
          </div>
          <div class="status-options">
            <input type="radio" class="status-opt" name="status" id="status-pending"   value="Pending"     required>
            <label for="status-pending">  <i class="bi bi-hourglass-split"></i> Pending</label>

            <input type="radio" class="status-opt" name="status" id="status-progress"  value="In Progress">
            <label for="status-progress"> <i class="bi bi-arrow-repeat"></i> In Progress</label>

            <input type="radio" class="status-opt" name="status" id="status-completed" value="Completed">
            <label for="status-completed"><i class="bi bi-check-circle"></i> Completed</label>
          </div>
        </div>

        <!-- ── UPLOAD ──────────────────── -->
        <div class="form-section">
          <div class="section-label">
            <i class="bi bi-paperclip"></i> Report Attachment <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:var(--gray-400);">&nbsp;Optional</span>
          </div>
          <div class="file-input-wrap">
            <i class="bi bi-cloud-arrow-up file-icon"></i>
            <div class="file-text">
              <strong>Click to upload</strong> or drag and drop<br>
              PDF, JPG, PNG — max 10 MB
            </div>
            <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png" id="reportFile">
          </div>
          <div id="fileNameDisplay" style="font-size:12px;color:var(--blue-600);margin-top:6px;display:none;">
            <i class="bi bi-file-earmark-check"></i> <span id="fileNameText"></span>
          </div>
        </div>

        <!-- ── SUBMIT ───────────────────── -->
        <div class="submit-area">
          <a href="lab_welcome.php" class="btn-cancel"><i class="bi bi-x"></i> Cancel</a>
          <button type="submit" name="submit" class="btn-submit" id="submitBtn">
            <i class="bi bi-check2-circle"></i> Submit Lab Test
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ── Patient Select2 ───────────────────────────────────── */
$(document).ready(function () {
  $('#patient-select').select2({
    placeholder: 'Search by patient name or ID…',
    ajax: {
      url: 'ajax_search_patients.php',
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term }),
      processResults: data => ({ results: data.results }),
      cache: true
    },
    minimumInputLength: 1
  });
});

/* ── File name display ─────────────────────────────────── */
document.getElementById('reportFile').addEventListener('change', function () {
  const display = document.getElementById('fileNameDisplay');
  const text    = document.getElementById('fileNameText');
  if (this.files.length) {
    text.textContent = this.files[0].name;
    display.style.display = 'block';
  } else {
    display.style.display = 'none';
  }
});

/* ── Test field definitions ────────────────────────────── */
const testFields = {
  hiv_test:         { title: "HIV Test",                      fields: ["HIV I", "HIV II", "Interpretation"] },
  fbc:              { title: "Full Blood Count",              fields: ["WBC","LYM","MID","GRA","LYM%","MID%","GRA%","RBC","HGB","HCT","MCV","MCH","MCHC","RDWc","PLT","PCT","MPV","PDWc","P-LCC","P-LCR","DIAGNOSTIC FLAGS","WARNING","LYSE","PRVW","PRVR"] },
  widal:            { title: "Widal Test",                    fields: ["Salmonella typhi O","Salmonella typhi H","Salmonella paratyphi A","Salmonella paratyphi B"] },
  kft:              { title: "Kidney Function Test",          fields: ["Urea","Creatinine","Sodium","Potassium","Chloride","Bicarbonate","Calcium"] },
  lft:              { title: "Liver Function Test",           fields: ["T. Bilirubin (0-1.0 mg/dL)","Direct Bilirubin (0-0.3 mg/dL)","SGOT (0-31 U/L)","SGPT (0-31 U/L)","Alkaline Phosphatase (64-306 U/L)","Total Protein (6.6-8.7 g/dL)","Albumin (3.6-5.5 g/dL)"] },
  lipid:            { title: "Lipid Profile",                 fields: ["Cholesterol (<200 mg/dL)","Triglycerides (35-135 mg/dL)","HDL (>35 mg/dL)","LDL (<130 mg/dL)"] },
  serology:         { title: "Serology",                      fields: ["HBsAg","HCV","VDRL","HIV"] },
  urinalysis:       { title: "Urinalysis / M/C/S",            fields: ["Color","Appearance","pH","Protein","Glucose","Ketones","Nitrite","Leukocyte","Bacteria","Epithelial cells","Casts","Crystals"] },
  stool:            { title: "Stool M/C/S / Microscopy",      fields: ["Color","Consistency","Ova","Cyst","Parasites","Pus Cells","RBC"] },
  hvs:              { title: "High Vaginal Swab (HVS)",       fields: ["Gram Reaction","Yeast","Trichomonas","Bacteria","Culture Result","Sensitivity"] },
  hsv:              { title: "Herpes Simplex Virus",          fields: ["HSV IgG 1","HSV IgG 2","HSV IgM 1","HSV IgM 2"] },
  hba1c:            { title: "HbA1c",                         fields: ["HbA1c (%)","Interpretation (Normal <6.0%, Good 6.0-6.8%, Fair 6.8-7.65%, Poor >7.65)"] },
  hbv_profile:      { title: "HBV Profile",                   fields: ["HBsAg","HBsAb","HBeAg","HBeAb","HBcAb"] },
  hormonal:         { title: "Hormonal Panel",                 fields: ["Prolactin","LH","FSH","Estrogen","Progesterone"] },
  psa:              { title: "Prostate Specific Antigen (PSA)",fields: ["Total PSA","Free PSA","PSA Ratio"] },
  rbs_fbs:          { title: "RBS / FBS",                     fields: ["Random Blood Sugar (mg/dL)","Fasting Blood Sugar (mg/dL)"] },
  esr:              { title: "ESR",                           fields: ["Erythrocyte Sedimentation Rate (mm/hr)"] },
  h_pylori:         { title: "H. Pylori",                     fields: ["H. Pylori Antigen","H. Pylori Antibody"] },
  blood_group:      { title: "Blood Group",                   fields: ["Blood Group","Rh Factor"] },
  genotype:         { title: "Genotype",                      fields: ["Genotype"] },
  sputum:           { title: "Sputum M/C/S",                  fields: ["Appearance","Color","Culture","Sensitivity","Organism Isolated"] },
  malaria:          { title: "Malaria Parasite",              fields: ["MP Test Result","Parasite Species","Parasitemia Level"] },
  blood_sugar:      { title: "Blood Sugar",                   fields: ["Fasting Blood Sugar (mg/dL)","2 Hours Postprandial (mg/dL)","Random Blood Sugar (mg/dL)"] },
  reticulocyte:     { title: "Reticulocyte Count",            fields: ["Reticulocyte %","Reticulocyte Absolute Count"] },
  hb_electrophoresis:{ title: "Haemoglobin Electrophoresis",  fields: ["HbA","HbF","HbS","HbC","Other Variants"] },
  pbs:              { title: "Peripheral Blood Smear",        fields: ["RBC Morphology","WBC Morphology","Platelet Morphology","Parasite Seen"] },
  blood_culture:    { title: "Blood Culture",                 fields: ["Culture Result","Organism Isolated","Sensitivity"] },
  wound_swab:       { title: "Wound Swab",                    fields: ["Culture Result","Organism Isolated","Sensitivity"] },
  afb:              { title: "Acid-Fast Bacilli (AFB)",       fields: ["AFB Result","Number of Bacilli","Grade"] },
  asotitre:         { title: "ASO Titre",                     fields: ["ASO Titre (IU/mL)"] },
  rheumatoid_factor:{ title: "Rheumatoid Factor (RF)",        fields: ["RF Level","Interpretation"] },
  microfilaria:     { title: "Microfilaria",                  fields: ["Test Result","Species Detected","Parasite Load"] },
  pt_hcg:           { title: "Pregnancy Test (PT - HCG)",     fields: ["Urine HCG Result","Serum HCG Result"] },
  papsmear:         { title: "Pap Smear",                     fields: ["Cellular Result","Inflammation","Dysplasia","HPV Status"] },
  coombs:           { title: "Coombs Test",                   fields: ["Direct Coombs Result","Indirect Coombs Result","Interpretation"] },
  sfa:              { title: "Seminal Fluid Analysis (SFA)",  fields: ["Volume","pH","Motility","Morphology","Count","WBCs","RBCs"] },
  csf:              { title: "CSF Analysis",                  fields: ["Appearance","Protein","Glucose","WBCs","RBCs","Culture Result"] },
  fob:              { title: "Faecal Occult Blood (FOB)",     fields: ["FOB Result","Method Used","Interpretation"] },
};

/* ── Render dynamic fields ─────────────────────────────── */
document.getElementById('lab-test-selector').addEventListener('change', function () {
  const val       = this.value;
  const container = document.getElementById('test-components-container');
  const nameField = document.getElementById('test-name-field');
  container.innerHTML = '';

  if (!testFields[val]) { nameField.placeholder = 'e.g. Full Blood Count'; return; }

  const { title, fields } = testFields[val];
  nameField.value = title;

  const section = document.createElement('div');
  section.className = 'test-section';

  section.innerHTML = `
    <div class="test-section-head">
      <i class="bi bi-list-check"></i>
      <span class="test-section-title">${title} — Result Fields</span>
    </div>
    <div class="test-fields-grid" id="test-grid-${val}"></div>
  `;
  container.appendChild(section);

  const grid = section.querySelector('.test-fields-grid');
  fields.forEach(field => {
    const div = document.createElement('div');
    div.className = 'field';
    div.innerHTML = `
      <label>${field}</label>
      <input type="text" class="form-input" name="result_values[${field}]" placeholder="Enter value">
    `;
    grid.appendChild(div);
  });
});

/* ── Collect result JSON on submit ─────────────────────── */
document.getElementById('labForm').addEventListener('submit', function () {
  const inputs = document.querySelectorAll('#test-components-container input[type="text"]');
  const data   = {};
  inputs.forEach(input => {
    const key = input.name.replace('result_values[', '').replace(']', '');
    data[key]  = input.value;
  });
  document.getElementById('result-field').value = JSON.stringify(data);
});
</script>
</body>
</html>