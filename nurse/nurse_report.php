<?php
session_start();
require '../db.php';
require '../includes/auth.php';
checkRole(3);

$message     = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nurse_name = $_SESSION['user']['full_name'];
    $patient_id = $_POST['patient_id'];      // kept for DB compatibility
    $report     = trim($_POST['report']);
    $signature  = trim($_POST['signature']);
    $date       = date('Y-m-d');
    $time       = date('H:i:s');

    if (empty($patient_id) || empty($report) || empty($signature)) {
        $message     = "Please fill all required fields.";
        $messageType = "error";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO nurse_reports (patient_id, nurse_name, report, signature, report_date, report_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$patient_id, $nurse_name, $report, $signature, $date, $time])) {
            $message     = "Daily report submitted successfully!";
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
$hour           = (int)date('H');
$shift          = $hour >= 7  && $hour < 15 ? 'Morning  (07:00 – 15:00)'
                : ($hour >= 15 && $hour < 23 ? 'Afternoon (15:00 – 23:00)'
                :                               'Night     (23:00 – 07:00)');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daily Nurse Report — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --blue-800:#1a3f8f;--blue-700:#1d4ed8;--blue-600:#2563eb;--blue-500:#3b82f6;
      --blue-400:#60a5fa;--blue-300:#93c5fd;--blue-200:#bfdbfe;--blue-100:#dbeafe;--blue-50:#eff6ff;
      --white:#fff;--gray-50:#f8fafc;--gray-100:#f1f5f9;--gray-200:#e2e8f0;
      --gray-300:#cbd5e1;--gray-400:#94a3b8;--gray-500:#64748b;--gray-700:#334155;--gray-900:#0f172a;
      --green-600:#059669;--green-50:#ecfdf5;--green-100:#d1fae5;--green-700:#047857;
      --red-600:#dc2626;--red-50:#fef2f2;--red-100:#fee2e2;--red-700:#b91c1c;
      --amber-500:#f59e0b;--amber-50:#fffbeb;--amber-100:#fef3c7;--amber-700:#b45309;
      --shadow-sm:0 1px 3px rgba(15,45,107,.07);
      --shadow-md:0 4px 16px rgba(15,45,107,.09),0 2px 6px rgba(15,45,107,.06);
      --shadow-lg:0 12px 40px rgba(15,45,107,.13),0 4px 12px rgba(15,45,107,.07);
      --glow:rgba(37,99,235,.12);
    }
    html,body{min-height:100vh;font-family:'Sora',sans-serif;background:var(--gray-50);color:var(--gray-700)}
    body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
      background:radial-gradient(ellipse 600px 400px at 5% 10%,rgba(37,99,235,.05) 0%,transparent 70%),
                 radial-gradient(ellipse 500px 350px at 95% 90%,rgba(96,165,250,.04) 0%,transparent 70%)}
    ::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--gray-100)}
    ::-webkit-scrollbar-thumb{background:var(--blue-300);border-radius:4px}

    /* ── TOPBAR ── */
    .topbar{position:sticky;top:0;z-index:200;background:var(--white);
      border-bottom:1px solid var(--gray-200);box-shadow:var(--shadow-sm);height:62px;
      display:flex;align-items:center;justify-content:space-between;padding:0 28px}
    .tb-brand{display:flex;align-items:center;gap:11px}
    .tb-icon{width:36px;height:36px;border-radius:9px;
      background:linear-gradient(135deg,var(--blue-600),var(--blue-400));
      display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;
      box-shadow:0 3px 10px rgba(37,99,235,.25)}
    .tb-name{font-family:'Instrument Serif',serif;font-size:1.05rem;color:var(--blue-800)}
    .tb-sep{color:var(--gray-300);margin:0 2px}
    .tb-page{font-size:.78rem;color:var(--blue-600);font-weight:600}
    .tb-right{display:flex;align-items:center;gap:10px}
    .nurse-chip{display:flex;align-items:center;gap:6px;padding:5px 13px;
      background:var(--blue-50);border:1px solid var(--blue-100);
      border-radius:999px;font-size:.68rem;font-weight:700;color:var(--blue-700)}
    .pat-chip{display:flex;align-items:center;gap:6px;padding:5px 13px;
      background:var(--green-50);border:1px solid var(--green-100);
      border-radius:999px;font-size:.68rem;font-weight:700;color:var(--green-700)}
    .back-btn{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;
      background:var(--gray-100);border:1px solid var(--gray-200);color:var(--gray-500);
      font-size:.74rem;font-weight:600;text-decoration:none;transition:all .18s}
    .back-btn:hover{background:var(--gray-200);color:var(--gray-700)}

    /* ── PAGE ── */
    .page{position:relative;z-index:1;max-width:820px;margin:0 auto;padding:28px 20px 60px}

    /* ── PAGE HEADER ── */
    .ph-eyebrow{display:inline-flex;align-items:center;gap:6px;
      background:var(--blue-50);border:1px solid var(--blue-100);border-radius:999px;
      padding:4px 12px;font-size:.65rem;font-weight:700;color:var(--blue-700);
      text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}
    .ph-title{font-family:'Instrument Serif',serif;font-size:1.6rem;color:var(--gray-900)}
    .ph-title em{font-style:italic;color:var(--blue-600)}
    .ph-sub{font-size:.78rem;color:var(--gray-400);margin-top:4px;margin-bottom:22px}

    /* ── NURSE STRIP ── */
    .nurse-strip{
      background:linear-gradient(135deg,var(--blue-800),var(--blue-600),var(--blue-400));
      border-radius:12px;padding:14px 22px;margin-bottom:22px;
      display:flex;align-items:center;justify-content:space-between;gap:12px;
      position:relative;overflow:hidden;box-shadow:0 6px 20px rgba(37,99,235,.25);flex-wrap:wrap;
    }
    .nurse-strip::before{content:'';position:absolute;top:-30px;right:-30px;
      width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.06)}
    .ns-left{display:flex;align-items:center;gap:12px;position:relative;z-index:1}
    .ns-av{width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.2);
      border:1.5px solid rgba(255,255,255,.3);display:flex;align-items:center;
      justify-content:center;font-size:.95rem;color:#fff;flex-shrink:0}
    .ns-name{color:#fff;font-size:.9rem;font-weight:700}
    .ns-sub{color:rgba(255,255,255,.65);font-size:.68rem}
    .ns-right{display:flex;gap:8px;position:relative;z-index:1;flex-shrink:0;flex-wrap:wrap}
    .ns-stat{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);
      border-radius:8px;padding:6px 14px;text-align:center;white-space:nowrap}
    .ns-stat-val{color:#fff;font-size:.82rem;font-weight:700}
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
    .sec-head{display:flex;align-items:center;gap:10px;padding:13px 22px;
      background:var(--gray-50);border-bottom:1px solid var(--gray-100);
      cursor:pointer;user-select:none;transition:background .15s}
    .sec-head:hover{background:var(--blue-50)}
    .sec-icon{width:30px;height:30px;border-radius:7px;background:var(--blue-50);
      color:var(--blue-600);display:flex;align-items:center;justify-content:center;font-size:.85rem}
    .sec-lbl{font-size:.82rem;font-weight:800;color:var(--gray-900);flex:1}
    .sec-badge{font-size:.62rem;font-weight:700;padding:2px 9px;border-radius:999px;
      background:var(--blue-50);border:1px solid var(--blue-100);color:var(--blue-600)}
    .sec-badge.req{background:var(--red-50);border-color:var(--red-100);color:var(--red-600)}
    .sec-chev{font-size:.75rem;color:var(--gray-400);transition:transform .22s}
    .form-section.collapsed .sec-chev{transform:rotate(-90deg)}
    .sec-body{padding:18px 22px;display:grid;gap:14px}
    .form-section.collapsed .sec-body{display:none}

    /* grids */
    .g2{grid-template-columns:1fr 1fr}
    .g3{grid-template-columns:1fr 1fr 1fr}
    .full{grid-column:1/-1}

    /* ── FIELD ── */
    .field label{display:block;font-size:.65rem;font-weight:700;letter-spacing:.08em;
      text-transform:uppercase;color:var(--gray-500);margin-bottom:6px}
    .field label .r{color:var(--blue-500);margin-left:2px}
    .iw{position:relative}
    .ii{position:absolute;left:11px;top:50%;transform:translateY(-50%);
      color:var(--gray-400);font-size:.82rem;pointer-events:none}
    .ii.top{top:12px;transform:none}
    .iw.hi input,.iw.hi select,.iw.hi textarea{padding-left:32px}

    input[type=text],input[type=number],input[type=date],input[type=time],select,textarea{
      width:100%;padding:9px 13px;background:var(--gray-50);border:1.5px solid var(--gray-200);
      border-radius:9px;font-family:'Sora',sans-serif;font-size:.8rem;color:var(--gray-700);
      outline:none;transition:border-color .18s,box-shadow .18s,background .18s;appearance:none}
    input:hover,select:hover,textarea:hover{border-color:var(--blue-300)}
    input:focus,select:focus,textarea:focus{border-color:var(--blue-500);
      box-shadow:0 0 0 3px var(--glow);background:var(--white)}
    input::placeholder,textarea::placeholder{color:var(--gray-300)}
    select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat:no-repeat;background-position:right 10px center;
      padding-right:30px;background-color:var(--gray-50);cursor:pointer}
    textarea{resize:vertical;line-height:1.6}

    /* word count hint */
    .wc-hint{font-size:.68rem;color:var(--gray-400);margin-top:5px;display:flex;justify-content:flex-end}

    /* ── FOOTER ── */
    .form-footer{padding:20px 22px;border-top:2px solid var(--blue-100);
      background:var(--blue-50);display:flex;align-items:flex-end;
      justify-content:space-between;gap:16px;flex-wrap:wrap}
    .ff-left{display:flex;flex-direction:column;gap:10px}
    .ff-meta{font-size:.76rem;color:var(--gray-500);line-height:1.8}
    .ff-meta strong{color:var(--gray-900)}
    .sig-wrap{display:flex;flex-direction:column;gap:6px}
    .sig-label{font-size:.65rem;font-weight:700;letter-spacing:.08em;
      text-transform:uppercase;color:var(--gray-500)}
    .sig-label .r{color:var(--blue-500)}
    .sig-input{padding:8px 13px;border-radius:9px;border:1.5px solid var(--gray-200);
      background:var(--white);font-family:'Sora',sans-serif;font-size:.82rem;
      color:var(--gray-700);outline:none;width:280px;
      transition:border-color .18s,box-shadow .18s}
    .sig-input:focus{border-color:var(--blue-500);box-shadow:0 0 0 3px var(--glow)}
    .sig-input::placeholder{color:var(--gray-300)}
    .btn-submit{display:flex;align-items:center;gap:8px;padding:12px 32px;
      border-radius:10px;background:linear-gradient(135deg,var(--blue-700),var(--blue-500));
      border:none;color:#fff;font-family:'Sora',sans-serif;font-size:.9rem;font-weight:800;
      cursor:pointer;box-shadow:0 5px 18px rgba(37,99,235,.3);transition:all .2s;white-space:nowrap}
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.4)}

    @media(max-width:680px){
      .topbar{padding:0 14px}
      .tb-right>:not(.back-btn){display:none}
      .page{padding:16px 12px 48px}
      .sec-body{grid-template-columns:1fr!important}
      .ns-right{display:none}
      .sig-input{width:100%}
      .form-footer{flex-direction:column;align-items:stretch}
      .btn-submit{justify-content:center}
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
    <span class="tb-page">Daily Nurse Report</span>
  </div>
  <div class="tb-right">
    <span class="nurse-chip"><i class="bi bi-clipboard2-heart-fill"></i><?= htmlspecialchars($nurse_name) ?></span>
    <span class="pat-chip"><i class="bi bi-people-fill"></i><?= number_format($total_patients) ?> Patients</span>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- HEADER -->
  <div class="ph-eyebrow"><i class="bi bi-clipboard2-heart-fill"></i> Nurse · Daily Ward Report</div>
  <div class="ph-title">Daily <em>Nurse Report</em></div>
  <div class="ph-sub">Document the general status of the ward and all patients for this shift.</div>

  <!-- NURSE STRIP -->
  <div class="nurse-strip">
    <div class="ns-left">
      <div class="ns-av"><i class="bi bi-person-fill"></i></div>
      <div>
        <div class="ns-name"><?= htmlspecialchars($nurse_name) ?></div>
        <div class="ns-sub">Registered Nurse · Angelora Hospital</div>
      </div>
    </div>
    <div class="ns-right">
      <div class="ns-stat"><div class="ns-stat-val"><?= date('d M Y') ?></div><div class="ns-stat-lbl">Date</div></div>
      <div class="ns-stat"><div class="ns-stat-val"><?= date('h:i A') ?></div><div class="ns-stat-lbl">Time</div></div>
      <div class="ns-stat"><div class="ns-stat-val"><?= explode(' ', $shift)[0] ?></div><div class="ns-stat-lbl">Shift</div></div>
    </div>
  </div>

  <!-- ALERT -->
  <?php if ($message): ?>
  <div class="alert-box <?= $messageType ?>">
    <i class="bi bi-<?= $messageType==='success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="reportForm">
  <div class="form-card">

    <!-- ═══ 1. SHIFT OVERVIEW ═══ -->
    <div class="form-section">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-sun-fill"></i></div>
        <span class="sec-lbl">1 — Shift Overview</span>
        <span class="sec-badge req">Required</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body g3">
        <div class="field">
          <label>Shift <span class="r">*</span></label>
          <div class="iw hi">
            <i class="bi bi-clock-fill ii"></i>
            <select name="shift_type" required>
              <option value="">Select shift</option>
              <option <?= str_contains($shift,'Morning') ? 'selected':'' ?>>Morning  (07:00 – 15:00)</option>
              <option <?= str_contains($shift,'Afternoon') ? 'selected':'' ?>>Afternoon (15:00 – 23:00)</option>
              <option <?= str_contains($shift,'Night') ? 'selected':'' ?>>Night     (23:00 – 07:00)</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Ward / Unit <span class="r">*</span></label>
          <div class="iw hi">
            <i class="bi bi-building ii"></i>
            <input type="text" name="ward" placeholder="e.g. Male Ward, ICU, Paediatrics" required>
          </div>
        </div>
        <div class="field">
          <label>Report Date</label>
          <div class="iw hi">
            <i class="bi bi-calendar3 ii"></i>
            <input type="date" name="report_date_field" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="field">
          <label>Patient Census (on-ward)</label>
          <div class="iw hi">
            <i class="bi bi-people-fill ii"></i>
            <input type="number" name="census_count" placeholder="Total patients on ward today">
          </div>
        </div>
        <div class="field">
          <label>New Admissions Today</label>
          <div class="iw hi">
            <i class="bi bi-person-plus-fill ii"></i>
            <input type="number" name="new_admissions" placeholder="0">
          </div>
        </div>
        <div class="field">
          <label>Discharges Today</label>
          <div class="iw hi">
            <i class="bi bi-box-arrow-right ii"></i>
            <input type="number" name="discharges" placeholder="0">
          </div>
        </div>
        <!-- link patient field (kept for DB) -->
        <div class="field full">
          <label>Reference Patient (if any) <span class="r">*</span></label>
          <div class="iw hi">
            <i class="bi bi-person-badge-fill ii"></i>
            <select name="patient_id" required>
              <option value="">— Select a patient or primary patient for this report —</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= htmlspecialchars($p['patient_id']) ?>"><?= htmlspecialchars($p['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ 2. WARD CONDITION & ENVIRONMENT ═══ -->
    <div class="form-section collapsed">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-hospital-fill"></i></div>
        <span class="sec-lbl">2 — Ward Condition &amp; Environment</span>
        <span class="sec-badge">Environment</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body g2">
        <div class="field">
          <label>Ward Cleanliness</label>
          <select name="ward_cleanliness">
            <option value="">Select</option>
            <option>Clean and tidy</option>
            <option>Needs attention — informed housekeeping</option>
            <option>Soiled — cleaned during shift</option>
          </select>
        </div>
        <div class="field">
          <label>Equipment Status</label>
          <select name="equipment_status">
            <option value="">Select</option>
            <option>All equipment functional</option>
            <option>Minor equipment fault — reported</option>
            <option>Critical equipment down — maintenance called</option>
          </select>
        </div>
        <div class="field">
          <label>Oxygen / Gas Supply</label>
          <select name="oxygen_supply">
            <option value="">Select</option>
            <option>Adequate</option>
            <option>Running low — reported</option>
            <option>Interrupted — resolved</option>
          </select>
        </div>
        <div class="field">
          <label>Medication Stock Status</label>
          <select name="drug_stock">
            <option value="">Select</option>
            <option>Stock adequate</option>
            <option>Some items low — requisition made</option>
            <option>Critical shortages</option>
          </select>
        </div>
        <div class="field full">
          <label>Environment Remarks</label>
          <textarea name="environment_remarks" rows="2"
            placeholder="Any issues with the ward environment, facilities, beds, equipment repairs needed…"></textarea>
        </div>
      </div>
    </div>

    <!-- ═══ 3. STAFFING ═══ -->
    <div class="form-section collapsed">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-people-fill"></i></div>
        <span class="sec-lbl">3 — Staffing &amp; Team</span>
        <span class="sec-badge">Staff</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body g3">
        <div class="field">
          <label>Nurses on Duty</label>
          <div class="iw hi">
            <i class="bi bi-clipboard2-heart-fill ii"></i>
            <input type="number" name="nurses_on_duty" placeholder="Number on this shift">
          </div>
        </div>
        <div class="field">
          <label>Nurses Absent / Off-Duty</label>
          <div class="iw hi">
            <i class="bi bi-person-dash-fill ii"></i>
            <input type="number" name="nurses_absent" placeholder="0">
          </div>
        </div>
        <div class="field">
          <label>Doctors on Ward (this shift)</label>
          <div class="iw hi">
            <i class="bi bi-person-badge-fill ii"></i>
            <input type="number" name="doctors_present" placeholder="Number present">
          </div>
        </div>
        <div class="field full">
          <label>Staffing Remarks</label>
          <textarea name="staffing_remarks" rows="2"
            placeholder="Any staffing issues, late arrivals, workload concerns, agency staff used…"></textarea>
        </div>
      </div>
    </div>

    <!-- ═══ 4. PATIENT SUMMARY ═══ -->
    <div class="form-section collapsed">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-person-lines-fill"></i></div>
        <span class="sec-lbl">4 — General Patient Summary</span>
        <span class="sec-badge">Patients</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body g2">
        <div class="field">
          <label>Overall Patient Condition</label>
          <select name="patient_condition">
            <option value="">Select</option>
            <option>All patients stable</option>
            <option>Mostly stable — one critical</option>
            <option>Multiple patients requiring close monitoring</option>
            <option>Emergency situation managed</option>
          </select>
        </div>
        <div class="field">
          <label>Critical / High-Dependency Patients</label>
          <input type="number" name="critical_count" placeholder="Number requiring close watch">
        </div>
        <div class="field full">
          <label>Summary of Patient Events This Shift</label>
          <textarea name="patient_events" rows="3"
            placeholder="Describe significant patient events — e.g. fall, deterioration, emergency, procedure performed, doctor review requested, new diagnosis…"></textarea>
        </div>
        <div class="field full">
          <label>Medications &amp; IV Fluids — General Notes</label>
          <textarea name="medications_notes" rows="2"
            placeholder="General notes on medication rounds, missed doses, drug reactions, IV complications…"></textarea>
        </div>
      </div>
    </div>

    <!-- ═══ 5. INCIDENTS & SAFETY ═══ -->
    <div class="form-section collapsed">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <span class="sec-lbl">5 — Incidents, Safety &amp; Infections</span>
        <span class="sec-badge">Safety</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body g2">
        <div class="field">
          <label>Any Incident / Accident?</label>
          <select name="incident_occurred" id="incidentSelect" onchange="toggleIncident()">
            <option value="No">No incident this shift</option>
            <option value="Yes">Yes — incident occurred</option>
          </select>
        </div>
        <div class="field" id="incidentTypeWrap">
          <label>Incident Type</label>
          <select name="incident_type">
            <option value="">Select type</option>
            <option>Patient fall</option>
            <option>Medication error</option>
            <option>Patient absconded</option>
            <option>Adverse drug reaction</option>
            <option>Needle-stick injury</option>
            <option>Cardiac arrest</option>
            <option>Fire / Safety emergency</option>
            <option>Other</option>
          </select>
        </div>
        <div class="field full" id="incidentDetailWrap">
          <label>Incident Details &amp; Action Taken</label>
          <textarea name="incident_details" rows="2"
            placeholder="Describe what happened, time, patient involved, action taken, who was notified…"></textarea>
        </div>
        <div class="field">
          <label>Infection Control</label>
          <select name="infection_control">
            <option value="">Select</option>
            <option>No infection concerns</option>
            <option>Isolation precautions in place</option>
            <option>New infection suspected — doctor notified</option>
            <option>Wound infection noted — dressed</option>
          </select>
        </div>
        <div class="field">
          <label>Pressure Sore / Ulcer</label>
          <select name="pressure_sore">
            <option value="">Select</option>
            <option>None</option>
            <option>Existing — unchanged</option>
            <option>Existing — worsening</option>
            <option>New sore identified</option>
            <option>Healed / Improving</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ═══ 6. DOCTOR VISITS & ORDERS ═══ -->
    <div class="form-section collapsed">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-person-badge-fill"></i></div>
        <span class="sec-lbl">6 — Doctor Visits &amp; New Orders</span>
        <span class="sec-badge">Medical</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body">
        <div class="field full">
          <label>Doctors Who Visited / Reviewed Patients</label>
          <textarea name="doctor_visits" rows="2"
            placeholder="e.g. Dr. Okafor reviewed patient in Bed 5 at 10:00 — new IV order given. Dr. Amadi on-call consulted for Bed 3."></textarea>
        </div>
        <div class="field full">
          <label>New Medical Orders / Instructions Received</label>
          <textarea name="new_orders" rows="2"
            placeholder="e.g. Change IV fluid to D5W, commence oral feeding for Bed 8, prepare patient in Bed 2 for theatre tomorrow…"></textarea>
        </div>
        <div class="field full">
          <label>Lab / Investigation Results Received</label>
          <textarea name="lab_results_received" rows="2"
            placeholder="e.g. FBC results for Bed 4 received — Hb low, doctor notified. X-ray results for Bed 6 reported normal."></textarea>
        </div>
      </div>
    </div>

    <!-- ═══ 7. MAIN REPORT (Required) ═══ -->
    <div class="form-section">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div>
        <span class="sec-lbl">7 — Full Shift Report <span style="color:var(--red-600)">(Required)</span></span>
        <span class="sec-badge req">Required</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body">
        <div class="field full">
          <label>Complete Shift Narrative <span class="r">*</span></label>
          <textarea name="report" id="reportText" rows="8" required
            placeholder="Write your complete daily/shift report here — summarise everything that happened on the ward during this shift. Include patient updates, significant events, procedures, family communications, outstanding issues, and anything the next shift needs to know…"></textarea>
          <div class="wc-hint" id="wcHint">0 words</div>
        </div>
      </div>
    </div>

    <!-- ═══ 8. HANDOVER ═══ -->
    <div class="form-section collapsed">
      <div class="sec-head" onclick="toggleSec(this)">
        <div class="sec-icon"><i class="bi bi-arrow-left-right"></i></div>
        <span class="sec-lbl">8 — Handover to Next Shift</span>
        <span class="sec-badge">Handover</span>
        <i class="bi bi-chevron-down sec-chev"></i>
      </div>
      <div class="sec-body g2">
        <div class="field">
          <label>Handover To (Nurse Name)</label>
          <div class="iw hi">
            <i class="bi bi-person-fill ii"></i>
            <input type="text" name="handover_to" placeholder="Name of incoming nurse">
          </div>
        </div>
        <div class="field">
          <label>Handover Time</label>
          <div class="iw hi">
            <i class="bi bi-clock ii"></i>
            <input type="time" name="handover_time">
          </div>
        </div>
        <div class="field full">
          <label>Outstanding Tasks &amp; Pending Issues</label>
          <textarea name="pending_tasks" rows="2"
            placeholder="e.g. Bed 3 awaiting surgical consult. IV for Bed 7 due at 15:00. Blood results for Bed 9 not yet received. Family counselling pending for Bed 1."></textarea>
        </div>
        <div class="field full">
          <label>Patients Requiring Special Attention Next Shift</label>
          <textarea name="special_attention" rows="2"
            placeholder="Highlight any patients the next nurse should prioritise or watch closely…"></textarea>
        </div>
      </div>
    </div>

    <!-- ═══ FOOTER / SIGNATURE ═══ -->
    <div class="form-footer">
      <div class="ff-left">
        <div class="ff-meta">
          <strong>Nurse:</strong> <?= htmlspecialchars($nurse_name) ?> &nbsp;·&nbsp;
          <strong>Date:</strong> <?= date('d M Y') ?> &nbsp;·&nbsp;
          <strong>Time:</strong> <?= date('h:i A') ?>
        </div>
        <div class="sig-wrap">
          <div class="sig-label">Nurse Signature <span class="r">*</span></div>
          <input type="text" name="signature" class="sig-input" required
            placeholder="Type your full name as signature">
        </div>
      </div>
      <button type="submit" class="btn-submit">
        <i class="bi bi-send-fill"></i> Submit Report
      </button>
    </div>

  </div>
  </form>
</div>

<script>
// ── COLLAPSIBLE SECTIONS ──
function toggleSec(head) {
  head.closest('.form-section').classList.toggle('collapsed');
}

// ── INCIDENT TOGGLE ──
function toggleIncident() {
  const show = document.getElementById('incidentSelect').value === 'Yes';
  document.getElementById('incidentTypeWrap').style.opacity   = show ? '1' : '.4';
  document.getElementById('incidentDetailWrap').style.opacity = show ? '1' : '.4';
}
toggleIncident(); // run on load

// ── WORD COUNT ──
const reportText = document.getElementById('reportText');
const wcHint     = document.getElementById('wcHint');
reportText.addEventListener('input', () => {
  const words = reportText.value.trim().split(/\s+/).filter(Boolean).length;
  wcHint.textContent = words + ' word' + (words !== 1 ? 's' : '');
  wcHint.style.color = words < 30 ? 'var(--amber-700)' : 'var(--green-600)';
});
</script>
</body>
</html>