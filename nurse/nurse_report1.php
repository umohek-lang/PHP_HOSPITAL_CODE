<?php
session_start();
require '../db.php';
require '../includes/auth.php';
checkRole(3);

$message     = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nurse_name = $_SESSION['user']['full_name'];
    $patient_id = $_POST['patient_id'];
    $signature  = trim($_POST['signature']);
    $date       = date('Y-m-d');
    $time       = date('H:i:s');

    /* ── Build the full structured report ── */
    $sections = [];

    // 1. Admission / Shift Info
    $sections[] = "=== SHIFT / ADMISSION INFO ===";
    $sections[] = "Shift: "               . ($_POST['shift']            ?? '—');
    $sections[] = "Ward / Unit: "         . ($_POST['ward']             ?? '—');
    $sections[] = "Bed No: "              . ($_POST['bed_no']           ?? '—');
    $sections[] = "Admission Status: "    . ($_POST['admission_status'] ?? '—');
    $sections[] = "Admission Date: "      . ($_POST['admission_date']   ?? '—');

    // 2. Vital Signs
    $sections[] = "\n=== VITAL SIGNS ===";
    $sections[] = "Temperature: "         . ($_POST['temp']             ?? '—') . " °C";
    $sections[] = "Blood Pressure: "      . ($_POST['bp']               ?? '—') . " mmHg";
    $sections[] = "Pulse Rate: "          . ($_POST['pulse']            ?? '—') . " bpm";
    $sections[] = "Respiratory Rate: "    . ($_POST['resp_rate']        ?? '—') . " breaths/min";
    $sections[] = "O₂ Saturation: "       . ($_POST['spo2']             ?? '—') . " %";
    $sections[] = "Pain Level (0–10): "   . ($_POST['pain_level']       ?? '—');
    $sections[] = "Level of Consciousness: " . ($_POST['consciousness'] ?? '—');
    $sections[] = "Blood Sugar: "         . ($_POST['blood_sugar']      ?? '—') . " mg/dL";

    // 3. Physical Assessment
    $sections[] = "\n=== PHYSICAL ASSESSMENT ===";
    $sections[] = "General Appearance: "  . ($_POST['general_appearance']  ?? '—');
    $sections[] = "Skin Condition: "      . ($_POST['skin_condition']       ?? '—');
    $sections[] = "Respiratory Status: "  . ($_POST['respiratory_status']   ?? '—');
    $sections[] = "Cardiovascular Status: " . ($_POST['cardiovascular']     ?? '—');
    $sections[] = "GI / Abdomen: "        . ($_POST['gi_abdomen']           ?? '—');
    $sections[] = "Urinary Output: "      . ($_POST['urinary_output']       ?? '—') . " mL";
    $sections[] = "Bowel Movement: "      . ($_POST['bowel_movement']       ?? '—');
    $sections[] = "Mobility / Activity: " . ($_POST['mobility']             ?? '—');
    $sections[] = "Wound / IV Site: "     . ($_POST['wound_site']           ?? '—');
    $sections[] = "Oedema: "              . ($_POST['oedema']               ?? '—');

    // 4. Medications Administered
    $sections[] = "\n=== MEDICATIONS ADMINISTERED ===";
    $sections[] = $_POST['medications_given'] ?? 'None noted.';

    // 5. Procedures & Interventions
    $sections[] = "\n=== PROCEDURES & NURSING INTERVENTIONS ===";
    $sections[] = $_POST['procedures']        ?? 'None performed.';

    // 6. IV Fluids
    $sections[] = "\n=== IV FLUIDS ===";
    $sections[] = "Fluid Type: "          . ($_POST['iv_fluid_type']  ?? '—');
    $sections[] = "Rate: "                . ($_POST['iv_rate']         ?? '—') . " mL/hr";
    $sections[] = "Volume Infused: "      . ($_POST['iv_infused']      ?? '—') . " mL";
    $sections[] = "Intake (Total): "      . ($_POST['fluid_intake']    ?? '—') . " mL";
    $sections[] = "Output (Total): "      . ($_POST['fluid_output']    ?? '—') . " mL";

    // 7. Patient Response & Behaviour
    $sections[] = "\n=== PATIENT RESPONSE & BEHAVIOUR ===";
    $sections[] = "Mental Status: "       . ($_POST['mental_status']   ?? '—');
    $sections[] = "Mood / Behaviour: "    . ($_POST['mood']            ?? '—');
    $sections[] = "Sleep Pattern: "       . ($_POST['sleep_pattern']   ?? '—');
    $sections[] = "Patient Complaints: "  . ($_POST['patient_complaints'] ?? 'None reported.');
    $sections[] = "Family / Visitor Notes: " . ($_POST['family_notes'] ?? '—');

    // 8. Clinical Notes
    $sections[] = "\n=== CLINICAL OBSERVATIONS & NOTES ===";
    $sections[] = $_POST['report']         ?? '—';

    // 9. Handover
    $sections[] = "\n=== HANDOVER NOTES ===";
    $sections[] = "Handover To: "         . ($_POST['handover_to']    ?? '—');
    $sections[] = "Pending Tasks: "       . ($_POST['pending_tasks']  ?? 'None');

    $full_report = implode("\n", $sections);

    if (empty($patient_id) || empty($_POST['report']) || empty($signature)) {
        $message     = "Please fill all required fields (Patient, Clinical Notes, Signature).";
        $messageType = "error";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO nurse_reports (patient_id, nurse_name, report, signature, report_date, report_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$patient_id, $nurse_name, $full_report, $signature, $date, $time])) {
            $message     = "Nurse report submitted successfully!";
            $messageType = "success";
        } else {
            $message     = "Failed to save report. Please try again.";
            $messageType = "error";
        }
    }
}

$patients       = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$nurse_name     = $_SESSION['user']['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nurse Report — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900:#0f2d6b; --blue-800:#1a3f8f; --blue-700:#1d4ed8;
      --blue-600:#2563eb; --blue-500:#3b82f6; --blue-400:#60a5fa;
      --blue-300:#93c5fd; --blue-200:#bfdbfe; --blue-100:#dbeafe; --blue-50:#eff6ff;
      --white:#ffffff; --gray-50:#f8fafc; --gray-100:#f1f5f9;
      --gray-200:#e2e8f0; --gray-300:#cbd5e1; --gray-400:#94a3b8;
      --gray-500:#64748b; --gray-700:#334155; --gray-900:#0f172a;
      --green-600:#059669; --green-50:#ecfdf5; --green-100:#d1fae5; --green-700:#047857;
      --red-600:#dc2626; --red-50:#fef2f2; --red-100:#fee2e2; --red-700:#b91c1c;
      --amber-500:#f59e0b; --amber-50:#fffbeb; --amber-100:#fef3c7; --amber-700:#b45309;
      --shadow-sm:0 1px 3px rgba(15,45,107,.07); --shadow-md:0 4px 16px rgba(15,45,107,.09);
      --shadow-lg:0 12px 40px rgba(15,45,107,.13); --blue-glow:rgba(37,99,235,.12);
    }

    html, body { min-height:100vh; font-family:'Sora',sans-serif; background:var(--gray-50); color:var(--gray-700); }
    body::before { content:''; position:fixed; inset:0; z-index:0; pointer-events:none;
      background: radial-gradient(ellipse 600px 400px at 5% 10%,rgba(37,99,235,.05) 0%,transparent 70%),
                  radial-gradient(ellipse 500px 350px at 95% 90%,rgba(96,165,250,.04) 0%,transparent 70%); }
    ::-webkit-scrollbar{width:5px} ::-webkit-scrollbar-track{background:var(--gray-100)}
    ::-webkit-scrollbar-thumb{background:var(--blue-300);border-radius:4px}

    /* ── TOPBAR ── */
    .topbar {
      position:sticky;top:0;z-index:200;background:var(--white);
      border-bottom:1px solid var(--gray-200);box-shadow:var(--shadow-sm);height:62px;
      display:flex;align-items:center;justify-content:space-between;padding:0 28px;
    }
    .tb-brand{display:flex;align-items:center;gap:11px}
    .tb-icon{width:36px;height:36px;border-radius:9px;
      background:linear-gradient(135deg,var(--blue-600),var(--blue-400));
      display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;
      box-shadow:0 3px 10px rgba(37,99,235,.25)}
    .tb-name{font-family:'Instrument Serif',serif;font-size:1.05rem;color:var(--blue-800)}
    .tb-sep{color:var(--gray-300);margin:0 2px}
    .tb-page{font-size:.78rem;color:var(--blue-600);font-weight:600}
    .tb-right{display:flex;align-items:center;gap:10px}
    .nurse-badge{display:flex;align-items:center;gap:6px;padding:5px 13px;
      background:var(--blue-50);border:1px solid var(--blue-100);
      border-radius:999px;font-size:.68rem;font-weight:700;color:var(--blue-700)}
    .patient-chip{display:flex;align-items:center;gap:6px;padding:4px 12px;
      background:var(--green-50);border:1px solid var(--green-100);
      border-radius:999px;font-size:.7rem;font-weight:700;color:var(--green-700)}
    .back-btn{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;
      background:var(--gray-100);border:1px solid var(--gray-200);color:var(--gray-500);
      font-size:.74rem;font-weight:600;text-decoration:none;transition:all .18s}
    .back-btn:hover{background:var(--gray-200);color:var(--gray-700)}

    /* ── PAGE ── */
    .page{position:relative;z-index:1;max-width:900px;margin:0 auto;padding:28px 20px 60px}

    /* ── PAGE HEADER ── */
    .page-header{margin-bottom:24px}
    .ph-eyebrow{display:inline-flex;align-items:center;gap:6px;
      background:var(--blue-50);border:1px solid var(--blue-100);border-radius:999px;
      padding:4px 12px;font-size:.65rem;font-weight:700;color:var(--blue-700);
      text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}
    .ph-title{font-family:'Instrument Serif',serif;font-size:1.6rem;font-weight:400;color:var(--gray-900)}
    .ph-title em{font-style:italic;color:var(--blue-600)}
    .ph-sub{font-size:.78rem;color:var(--gray-400);margin-top:4px}

    /* ── NURSE INFO STRIP ── */
    .nurse-strip{
      background:linear-gradient(135deg,var(--blue-800),var(--blue-600),var(--blue-400));
      border-radius:12px;padding:14px 22px;margin-bottom:22px;
      display:flex;align-items:center;justify-content:space-between;gap:12px;
      position:relative;overflow:hidden;box-shadow:0 6px 20px rgba(37,99,235,.25);
    }
    .nurse-strip::before{content:'';position:absolute;top:-30px;right:-30px;
      width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.06)}
    .ns-left{display:flex;align-items:center;gap:12px;position:relative;z-index:1}
    .ns-av{width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.2);
      border:1.5px solid rgba(255,255,255,.3);display:flex;align-items:center;
      justify-content:center;font-size:.95rem;color:#fff;flex-shrink:0}
    .ns-name{color:#fff;font-size:.9rem;font-weight:700}
    .ns-role{color:rgba(255,255,255,.65);font-size:.68rem}
    .ns-right{display:flex;gap:8px;position:relative;z-index:1;flex-shrink:0}
    .ns-stat{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);
      border-radius:8px;padding:6px 14px;text-align:center}
    .ns-stat-val{color:#fff;font-size:.85rem;font-weight:700}
    .ns-stat-lbl{color:rgba(255,255,255,.55);font-size:.6rem;text-transform:uppercase;letter-spacing:.06em}

    /* ── ALERT ── */
    .alert-box{display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:10px;
      margin-bottom:20px;font-size:.83rem;font-weight:500;animation:slideIn .3s ease}
    @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
    .alert-box.success{background:var(--green-50);border:1px solid var(--green-100);color:var(--green-700)}
    .alert-box.error{background:var(--red-50);border:1px solid var(--red-100);color:var(--red-700)}

    /* ── FORM CARD ── */
    .form-card{background:var(--white);border:1px solid var(--gray-200);
      border-radius:16px;overflow:hidden;box-shadow:var(--shadow-md)}

    /* ── SECTION ── */
    .form-section{border-bottom:1px solid var(--gray-100)}
    .form-section:last-child{border-bottom:none}
    .section-head{
      display:flex;align-items:center;gap:10px;padding:13px 22px;
      background:var(--gray-50);border-bottom:1px solid var(--gray-100);cursor:pointer;
      user-select:none;transition:background .15s;
    }
    .section-head:hover{background:var(--blue-50)}
    .sec-icon{width:30px;height:30px;border-radius:7px;background:var(--blue-50);
      color:var(--blue-600);display:flex;align-items:center;justify-content:center;font-size:.85rem}
    .sec-label{font-size:.82rem;font-weight:800;color:var(--gray-900);flex:1}
    .sec-badge{font-size:.62rem;font-weight:700;padding:2px 9px;border-radius:999px;
      background:var(--blue-50);border:1px solid var(--blue-100);color:var(--blue-600)}
    .sec-badge.req{background:var(--red-50);border-color:var(--red-100);color:var(--red-600)}
    .sec-chevron{font-size:.75rem;color:var(--gray-400);transition:transform .22s}
    .form-section.collapsed .sec-chevron{transform:rotate(-90deg)}
    .section-body{padding:18px 22px;display:grid;gap:14px}
    .form-section.collapsed .section-body{display:none}

    /* ── GRID HELPERS ── */
    .fg2{grid-template-columns:1fr 1fr}
    .fg3{grid-template-columns:1fr 1fr 1fr}
    .fg4{grid-template-columns:1fr 1fr 1fr 1fr}

    /* ── FIELD ── */
    .field{}
    .field label{display:block;font-size:.65rem;font-weight:700;letter-spacing:.08em;
      text-transform:uppercase;color:var(--gray-500);margin-bottom:6px}
    .field label .req{color:var(--blue-500);margin-left:2px}

    .input-wrap{position:relative}
    .fi{position:absolute;left:11px;top:50%;transform:translateY(-50%);
      color:var(--gray-400);font-size:.82rem;pointer-events:none}
    .fi-top{top:13px;transform:none}
    .has-icon input,.has-icon select,.has-icon textarea{padding-left:32px}

    input[type=text],input[type=number],input[type=date],input[type=time],
    select,textarea{
      width:100%;padding:9px 13px;background:var(--gray-50);
      border:1.5px solid var(--gray-200);border-radius:9px;
      font-family:'Sora',sans-serif;font-size:.8rem;color:var(--gray-700);
      outline:none;transition:border-color .18s,box-shadow .18s,background .18s;appearance:none;
    }
    input:hover,select:hover,textarea:hover{border-color:var(--blue-300)}
    input:focus,select:focus,textarea:focus{border-color:var(--blue-500);
      box-shadow:0 0 0 3px var(--blue-glow);background:var(--white)}
    input::placeholder,textarea::placeholder{color:var(--gray-300)}
    select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat:no-repeat;background-position:right 10px center;
      padding-right:30px;background-color:var(--gray-50);cursor:pointer}
    textarea{resize:vertical;line-height:1.6;min-height:70px}

    /* pain scale */
    .pain-scale{display:flex;gap:5px;flex-wrap:wrap;margin-top:4px}
    .pain-btn{width:38px;height:38px;border-radius:7px;border:1.5px solid var(--gray-200);
      background:var(--white);font-size:.8rem;font-weight:700;color:var(--gray-500);
      cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s}
    .pain-btn:hover{border-color:var(--blue-300);color:var(--blue-600)}
    .pain-btn.selected{background:var(--blue-600);border-color:var(--blue-600);color:#fff}
    .pain-btn[data-val="8"],.pain-btn[data-val="9"],.pain-btn[data-val="10"]{border-color:var(--red-100)}
    .pain-btn[data-val="8"].selected,.pain-btn[data-val="9"].selected,.pain-btn[data-val="10"].selected
      {background:var(--red-600);border-color:var(--red-600)}
    #pain_level{display:none}

    /* full-span field */
    .span2{grid-column:1/-1}

    /* ── FORM FOOTER ── */
    .form-footer{
      padding:20px 22px;border-top:2px solid var(--blue-100);
      background:var(--blue-50);display:flex;align-items:center;justify-content:space-between;
      gap:14px;flex-wrap:wrap;
    }
    .footer-info{font-size:.76rem;color:var(--gray-500);line-height:1.7}
    .footer-info strong{color:var(--gray-900)}
    .btn-submit{display:flex;align-items:center;gap:8px;padding:12px 32px;border-radius:10px;
      background:linear-gradient(135deg,var(--blue-700),var(--blue-500));
      border:none;color:#fff;font-family:'Sora',sans-serif;font-size:.9rem;font-weight:800;
      cursor:pointer;box-shadow:0 5px 18px rgba(37,99,235,.3);transition:all .2s}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.4)}

    /* responsive */
    @media(max-width:700px){
      .topbar{padding:0 14px} .tb-right>:not(.back-btn){display:none}
      .page{padding:16px 12px 48px}
      .section-body{grid-template-columns:1fr!important}
      .ns-right{display:none}
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
    <span class="tb-page">Nurse Report</span>
  </div>
  <div class="tb-right">
    <span class="nurse-badge"><i class="bi bi-clipboard2-heart-fill"></i><?= htmlspecialchars($nurse_name) ?></span>
    <span class="patient-chip"><i class="bi bi-people-fill"></i><?= number_format($total_patients) ?> Patients</span>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-clipboard2-heart-fill"></i> Nurse · Clinical Documentation</div>
    <div class="ph-title">Nurse <em>Report</em></div>
    <div class="ph-sub">Complete all relevant sections and submit. Required fields are marked <span style="color:var(--blue-500)">*</span>.</div>
  </div>

  <!-- NURSE INFO STRIP -->
  <div class="nurse-strip">
    <div class="ns-left">
      <div class="ns-av"><i class="bi bi-person-fill"></i></div>
      <div>
        <div class="ns-name"><?= htmlspecialchars($nurse_name) ?></div>
        <div class="ns-role">Registered Nurse · Angelora Hospital</div>
      </div>
    </div>
    <div class="ns-right">
      <div class="ns-stat"><div class="ns-stat-val"><?= date('d M Y') ?></div><div class="ns-stat-lbl">Date</div></div>
      <div class="ns-stat"><div class="ns-stat-val"><?= date('h:i A') ?></div><div class="ns-stat-lbl">Time</div></div>
    </div>
  </div>

  <!-- ALERT -->
  <?php if ($message): ?>
  <div class="alert-box <?= $messageType ?>">
    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <!-- FORM -->
  <form method="POST" id="nurseReportForm">
  <div class="form-card">

    <!-- ═══ 1. PATIENT & SHIFT INFO ═══ -->
    <div class="form-section">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-person-badge-fill"></i></div>
        <span class="sec-label">1 — Patient &amp; Shift Information</span>
        <span class="sec-badge req">Required</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body fg2">
        <div class="field span2">
          <label>Select Patient <span class="req">*</span></label>
          <div class="input-wrap has-icon">
            <i class="bi bi-people-fill fi"></i>
            <select name="patient_id" required>
              <option value="">— Choose Patient —</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= htmlspecialchars($p['patient_id']) ?>"><?= htmlspecialchars($p['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Shift</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-clock-fill fi"></i>
            <select name="shift">
              <option value="">Select shift</option>
              <option>Morning (07:00 – 15:00)</option>
              <option>Afternoon (15:00 – 23:00)</option>
              <option>Night (23:00 – 07:00)</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Ward / Unit</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-building fi"></i>
            <input type="text" name="ward" placeholder="e.g. Female Ward, ICU, Paediatrics">
          </div>
        </div>
        <div class="field">
          <label>Bed Number</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-hospital fi"></i>
            <input type="text" name="bed_no" placeholder="e.g. B12">
          </div>
        </div>
        <div class="field">
          <label>Admission Status</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-tag-fill fi"></i>
            <select name="admission_status">
              <option value="">Select</option>
              <option>Inpatient</option>
              <option>Outpatient</option>
              <option>Day Case</option>
              <option>Emergency</option>
              <option>Observation</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Admission Date</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-calendar3 fi"></i>
            <input type="date" name="admission_date">
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ 2. VITAL SIGNS ═══ -->
    <div class="form-section">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-heart-pulse-fill"></i></div>
        <span class="sec-label">2 — Vital Signs</span>
        <span class="sec-badge">Clinical</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body fg3">
        <div class="field">
          <label>Temperature (°C)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-thermometer-half fi"></i>
            <input type="number" step="0.1" name="temp" placeholder="e.g. 36.5">
          </div>
        </div>
        <div class="field">
          <label>Blood Pressure (mmHg)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-activity fi"></i>
            <input type="text" name="bp" placeholder="e.g. 120/80">
          </div>
        </div>
        <div class="field">
          <label>Pulse Rate (bpm)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-heart-fill fi"></i>
            <input type="number" name="pulse" placeholder="e.g. 72">
          </div>
        </div>
        <div class="field">
          <label>Respiratory Rate (/min)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-lungs-fill fi"></i>
            <input type="number" name="resp_rate" placeholder="e.g. 16">
          </div>
        </div>
        <div class="field">
          <label>O₂ Saturation (%)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-droplet-fill fi"></i>
            <input type="number" name="spo2" placeholder="e.g. 98">
          </div>
        </div>
        <div class="field">
          <label>Blood Sugar (mg/dL)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-droplet-half fi"></i>
            <input type="number" step="0.1" name="blood_sugar" placeholder="e.g. 90">
          </div>
        </div>
        <div class="field">
          <label>Level of Consciousness (AVPU)</label>
          <select name="consciousness">
            <option value="">Select</option>
            <option>Alert</option>
            <option>Verbal</option>
            <option>Pain</option>
            <option>Unresponsive</option>
          </select>
        </div>
        <div class="field span2">
          <label>Pain Level (0–10)</label>
          <div class="pain-scale">
            <?php for ($p = 0; $p <= 10; $p++): ?>
            <button type="button" class="pain-btn" data-val="<?= $p ?>"><?= $p ?></button>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="pain_level" id="pain_level" value="">
          <div style="font-size:.68rem;color:var(--gray-400);margin-top:5px">
            0 = No pain &nbsp;·&nbsp; 1–3 = Mild &nbsp;·&nbsp; 4–6 = Moderate &nbsp;·&nbsp; 7–10 = Severe
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ 3. PHYSICAL ASSESSMENT ═══ -->
    <div class="form-section collapsed">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-stethoscope"></i></div>
        <span class="sec-label">3 — Physical Assessment</span>
        <span class="sec-badge">Assessment</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body fg2">
        <div class="field">
          <label>General Appearance</label>
          <select name="general_appearance">
            <option value="">Select</option>
            <option>Alert and well-oriented</option>
            <option>Appears in mild distress</option>
            <option>Appears in moderate distress</option>
            <option>Appears acutely ill</option>
            <option>Lethargic but rousable</option>
            <option>Unresponsive</option>
          </select>
        </div>
        <div class="field">
          <label>Skin Condition</label>
          <select name="skin_condition">
            <option value="">Select</option>
            <option>Intact, warm and dry</option>
            <option>Pale / Cyanosed</option>
            <option>Jaundiced</option>
            <option>Diaphoretic (Sweating)</option>
            <option>Oedematous</option>
            <option>Wound / Lesion present</option>
          </select>
        </div>
        <div class="field">
          <label>Respiratory Status</label>
          <select name="respiratory_status">
            <option value="">Select</option>
            <option>Normal</option>
            <option>Tachypnoeic</option>
            <option>Bradypnoeic</option>
            <option>Laboured breathing</option>
            <option>On oxygen therapy</option>
            <option>On mechanical ventilation</option>
          </select>
        </div>
        <div class="field">
          <label>Cardiovascular Status</label>
          <select name="cardiovascular">
            <option value="">Select</option>
            <option>Normal</option>
            <option>Irregular rhythm</option>
            <option>Hypotensive</option>
            <option>Hypertensive</option>
            <option>Tachycardic</option>
            <option>Bradycardic</option>
          </select>
        </div>
        <div class="field">
          <label>GI / Abdomen</label>
          <select name="gi_abdomen">
            <option value="">Select</option>
            <option>Soft, non-tender</option>
            <option>Distended</option>
            <option>Tender on palpation</option>
            <option>Vomiting</option>
            <option>Nausea present</option>
            <option>Bowel sounds present</option>
            <option>Bowel sounds absent</option>
          </select>
        </div>
        <div class="field">
          <label>Oedema</label>
          <select name="oedema">
            <option value="">Select</option>
            <option>None</option>
            <option>Mild — pitting</option>
            <option>Moderate — bilateral</option>
            <option>Severe — anasarca</option>
            <option>Periorbital</option>
            <option>Sacral</option>
          </select>
        </div>
        <div class="field">
          <label>Mobility / Activity Level</label>
          <select name="mobility">
            <option value="">Select</option>
            <option>Ambulatory — independent</option>
            <option>Ambulatory with assistance</option>
            <option>Bed-rest — partial movement</option>
            <option>Bed-rest — immobile</option>
            <option>Post-op restricted</option>
          </select>
        </div>
        <div class="field">
          <label>Wound / IV Site Condition</label>
          <input type="text" name="wound_site" placeholder="e.g. Wound clean and dry, IV site intact at right hand">
        </div>
      </div>
    </div>

    <!-- ═══ 4. FLUID BALANCE ═══ -->
    <div class="form-section collapsed">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-droplet-fill"></i></div>
        <span class="sec-label">4 — Fluid Balance</span>
        <span class="sec-badge">I&amp;O</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body fg2">
        <div class="field">
          <label>IV Fluid Type</label>
          <input type="text" name="iv_fluid_type" placeholder="e.g. Normal Saline 0.9%, Ringers Lactate">
        </div>
        <div class="field">
          <label>IV Rate (mL/hr)</label>
          <input type="number" name="iv_rate" placeholder="e.g. 100">
        </div>
        <div class="field">
          <label>Volume Infused (mL)</label>
          <input type="number" name="iv_infused" placeholder="e.g. 500">
        </div>
        <div class="field">
          <label>Urinary Output (mL)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-droplet fi"></i>
            <input type="number" name="urinary_output" placeholder="e.g. 300">
          </div>
        </div>
        <div class="field">
          <label>Bowel Movement</label>
          <select name="bowel_movement">
            <option value="">Select</option>
            <option>Yes — normal</option>
            <option>Yes — loose/diarrhoea</option>
            <option>No bowel movement</option>
            <option>Constipated</option>
            <option>Melaena (black stool)</option>
          </select>
        </div>
        <div class="field">
          <label>Total Fluid Intake (mL)</label>
          <input type="number" name="fluid_intake" placeholder="oral + IV total">
        </div>
        <div class="field">
          <label>Total Fluid Output (mL)</label>
          <input type="number" name="fluid_output" placeholder="urine + losses total">
        </div>
      </div>
    </div>

    <!-- ═══ 5. MEDICATIONS GIVEN ═══ -->
    <div class="form-section collapsed">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-capsule-pill"></i></div>
        <span class="sec-label">5 — Medications Administered</span>
        <span class="sec-badge">MAR</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body">
        <div class="field span2">
          <label>Medications Given</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-capsule-pill fi fi-top"></i>
            <textarea name="medications_given" rows="4"
              placeholder="List each medication on a new line — include drug name, dose, route, and time.&#10;e.g. Paracetamol 1g IV — 08:00&#10;Metronidazole 500mg PO — 08:00&#10;Cefuroxime 750mg IV — 08:30"></textarea>
          </div>
        </div>
        <div class="field span2">
          <label>Procedures &amp; Nursing Interventions</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-clipboard2-check-fill fi fi-top"></i>
            <textarea name="procedures" rows="3"
              placeholder="e.g. Wound dressing changed, Nasogastric tube inserted, Patient turned and repositioned, Catheter care done…"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ 6. PATIENT BEHAVIOUR & PSYCHOSOCIAL ═══ -->
    <div class="form-section collapsed">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-person-heart"></i></div>
        <span class="sec-label">6 — Patient Behaviour &amp; Psychosocial</span>
        <span class="sec-badge">Psych</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body fg2">
        <div class="field">
          <label>Mental Status / Orientation</label>
          <select name="mental_status">
            <option value="">Select</option>
            <option>Oriented ×3 (person, place, time)</option>
            <option>Confused / Disoriented</option>
            <option>Agitated</option>
            <option>Restless</option>
            <option>Sedated</option>
            <option>Unconscious</option>
          </select>
        </div>
        <div class="field">
          <label>Mood / Behaviour</label>
          <select name="mood">
            <option value="">Select</option>
            <option>Calm and cooperative</option>
            <option>Anxious</option>
            <option>Depressed</option>
            <option>Aggressive / Combative</option>
            <option>Withdrawn</option>
            <option>Tearful</option>
          </select>
        </div>
        <div class="field">
          <label>Sleep Pattern</label>
          <select name="sleep_pattern">
            <option value="">Select</option>
            <option>Slept well</option>
            <option>Restless sleep</option>
            <option>Unable to sleep</option>
            <option>Sedated — sleeping</option>
          </select>
        </div>
        <div class="field">
          <label>Patient Complaints</label>
          <input type="text" name="patient_complaints" placeholder="e.g. Complains of headache, nausea, difficulty breathing">
        </div>
        <div class="field span2">
          <label>Family / Visitor Notes</label>
          <input type="text" name="family_notes" placeholder="e.g. Wife present, patient counselled, family educated on diet restriction">
        </div>
      </div>
    </div>

    <!-- ═══ 7. CLINICAL OBSERVATIONS (main notes) ═══ -->
    <div class="form-section">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div>
        <span class="sec-label">7 — Clinical Observations &amp; Notes</span>
        <span class="sec-badge req">Required</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body">
        <div class="field span2">
          <label>Detailed Clinical Notes <span class="req">*</span></label>
          <div class="input-wrap has-icon">
            <i class="bi bi-pencil-fill fi fi-top"></i>
            <textarea name="report" rows="6" required
              placeholder="Describe the patient's overall condition during this shift — any significant changes, response to treatment, unusual findings, events, and nursing actions taken…"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ 8. HANDOVER ═══ -->
    <div class="form-section collapsed">
      <div class="section-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-arrow-left-right"></i></div>
        <span class="sec-label">8 — Handover Notes</span>
        <span class="sec-badge">Handover</span>
        <i class="bi bi-chevron-down sec-chevron"></i>
      </div>
      <div class="section-body fg2">
        <div class="field">
          <label>Handover To (Nurse Name)</label>
          <div class="input-wrap has-icon">
            <i class="bi bi-person-fill fi"></i>
            <input type="text" name="handover_to" placeholder="Name of receiving nurse">
          </div>
        </div>
        <div class="field">
          <label>Pending Tasks for Next Shift</label>
          <input type="text" name="pending_tasks" placeholder="e.g. IV to complete at 14:00, wound review due, lab results pending">
        </div>
      </div>
    </div>

    <!-- ═══ SIGNATURE ═══ -->
    <div class="form-footer">
      <div class="footer-info">
        <strong>Nurse:</strong> <?= htmlspecialchars($nurse_name) ?> &nbsp;·&nbsp;
        <strong>Date:</strong> <?= date('d M Y') ?> &nbsp;·&nbsp;
        <strong>Time:</strong> <?= date('h:i A') ?>
        <br>
        <label style="display:inline-flex;align-items:center;gap:8px;margin-top:8px;font-size:.76rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.07em">
          Signature <span style="color:var(--blue-500)">*</span>
        </label>
        <br>
        <input type="text" name="signature" required
          style="margin-top:5px;width:260px;padding:7px 12px;border-radius:8px;border:1.5px solid var(--gray-200);background:var(--white);font-family:'Sora',sans-serif;font-size:.82rem;outline:none"
          placeholder="Type your full name as signature">
      </div>
      <button type="submit" class="btn-submit">
        <i class="bi bi-send-fill"></i> Submit Report
      </button>
    </div>

  </div><!-- /form-card -->
  </form>

</div>

<script>
// ── COLLAPSIBLE SECTIONS ──
function toggleSec(head) {
  const sec = head.closest('.form-section');
  sec.classList.toggle('collapsed');
}

// ── PAIN SCALE BUTTONS ──
document.querySelectorAll('.pain-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.pain-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('pain_level').value = btn.dataset.val;
  });
});
</script>
</body>
</html>