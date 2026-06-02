<?php
require '../includes/auth.php';
require '../db.php';

if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php'); exit();
}

$patient_id = $_GET['patient_id'] ?? null;
$patient = null;
if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$patient) { echo "<p style='color:red;padding:20px'>Patient not found.</p>"; exit; }

function fetchGroupedData($pdo, $query, $key) {
    $stmt = $pdo->query($query);
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $data[$row[$key]][] = $row; }
    return $data;
}

$labTestsCatalog         = $pdo->query("SELECT * FROM lab_tests_catalog")->fetchAll(PDO::FETCH_ASSOC);
$nursingProceduresCatalog= $pdo->query("SELECT * FROM nursing_procedures_catalog")->fetchAll(PDO::FETCH_ASSOC);
$pharmacyMedicinesCatalog= $pdo->query("SELECT * FROM pharmacy_medicines")->fetchAll(PDO::FETCH_ASSOC);
$medicalHistory          = fetchGroupedData($pdo, "SELECT mr.*, u.full_name AS doctor_name FROM medical_records mr LEFT JOIN users u ON mr.doctor_id = u.user_id", 'patient_id');
$vitalSigns              = fetchGroupedData($pdo, "SELECT * FROM vital_signs", 'patient_id');
$labTests                = fetchGroupedData($pdo, "SELECT * FROM lab_tests", 'patient_id');
$prescriptions           = fetchGroupedData($pdo, "SELECT * FROM prescriptions", 'patient_id');

$prescItems = [];
$prescItemsStmt = $pdo->query("SELECT pi.prescription_id, m.medicine_name, pi.dosage, pi.duration FROM prescription_items pi JOIN medicines m ON pi.medicine_id = m.medicine_id");
while ($row = $prescItemsStmt->fetch(PDO::FETCH_ASSOC)) { $prescItems[$row['prescription_id']][] = $row; }

$hasPhoto = !empty($patient['photo']) && file_exists('../uploads/' . $patient['photo']);
$photoSrc = $hasPhoto ? '../uploads/' . htmlspecialchars($patient['photo']) : '';
$initials = strtoupper(substr($patient['full_name'] ?? 'P', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Consultation — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
      --amber-600:#d97706; --amber-50:#fffbeb; --amber-100:#fef3c7; --amber-700:#b45309;
      --violet-600:#7c3aed; --violet-50:#f5f3ff; --violet-100:#ede9fe;
      --sky-600:#0284c7; --sky-50:#f0f9ff; --sky-100:#e0f2fe;
      --shadow-sm:0 1px 3px rgba(15,45,107,.07); --shadow-md:0 4px 16px rgba(15,45,107,.09);
      --shadow-lg:0 12px 40px rgba(15,45,107,.13); --blue-glow:rgba(37,99,235,.12);
    }
    html,body { min-height:100vh; font-family:'Sora',sans-serif; background:var(--gray-50); color:var(--gray-700); }
    body::before { content:''; position:fixed; inset:0; z-index:0; pointer-events:none;
      background: radial-gradient(ellipse 600px 400px at 5% 10%,rgba(37,99,235,.05) 0%,transparent 70%),
                  radial-gradient(ellipse 500px 350px at 95% 90%,rgba(96,165,250,.04) 0%,transparent 70%); }
    ::-webkit-scrollbar { width:5px; } ::-webkit-scrollbar-track { background:var(--gray-100); }
    ::-webkit-scrollbar-thumb { background:var(--blue-300); border-radius:4px; }

    /* ── TOPBAR ── */
    .topbar { position:sticky; top:0; z-index:200; background:var(--white);
      border-bottom:1px solid var(--gray-200); box-shadow:var(--shadow-sm);
      height:62px; display:flex; align-items:center; justify-content:space-between; padding:0 28px; }
    .tb-brand { display:flex; align-items:center; gap:10px; }
    .tb-icon { width:36px; height:36px; border-radius:9px;
      background:linear-gradient(135deg,var(--blue-600),var(--blue-400));
      display:flex; align-items:center; justify-content:center; font-size:1rem; color:#fff;
      box-shadow:0 3px 10px rgba(37,99,235,.25); }
    .tb-name { font-family:'Instrument Serif',serif; font-size:1.05rem; color:var(--blue-800); }
    .tb-sep  { color:var(--gray-300); margin:0 2px; }
    .tb-page { font-size:.78rem; color:var(--blue-600); font-weight:600; }
    .back-btn { display:flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px;
      background:var(--blue-50); border:1px solid var(--blue-100); color:var(--blue-600);
      font-size:.75rem; font-weight:600; text-decoration:none; transition:all .18s; }
    .back-btn:hover { background:var(--blue-100); }

    /* ── LAYOUT ── */
    .page { position:relative; z-index:1; max-width:1200px; margin:0 auto; padding:28px 20px 60px; }
    .two-col { display:grid; grid-template-columns:340px 1fr; gap:20px; align-items:start; }

    /* ── PATIENT CARD ── */
    .patient-card { background:var(--white); border:1px solid var(--gray-200);
      border-radius:16px; overflow:hidden; box-shadow:var(--shadow-md); position:sticky; top:80px; }
    .pc-hero { background:linear-gradient(135deg,var(--blue-800),var(--blue-600),var(--blue-400));
      padding:24px 20px; display:flex; flex-direction:column; align-items:center; gap:14px;
      position:relative; overflow:hidden; }
    .pc-hero::before { content:''; position:absolute; top:-30px; right:-30px;
      width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,.07); }
    .pc-avatar { width:80px; height:80px; border-radius:50%;
      border:3px solid rgba(255,255,255,.4); object-fit:cover;
      box-shadow:0 4px 16px rgba(0,0,0,.2); position:relative; z-index:1; }
    .pc-avatar-fallback { width:80px; height:80px; border-radius:50%;
      background:rgba(255,255,255,.2); border:3px solid rgba(255,255,255,.4);
      display:flex; align-items:center; justify-content:center;
      font-size:1.6rem; font-weight:700; color:#fff; position:relative; z-index:1; }
    .pc-name { color:#fff; font-size:1rem; font-weight:700; text-align:center; position:relative; z-index:1; }
    .pc-pin  { color:rgba(255,255,255,.7); font-size:.72rem; font-family:monospace; position:relative; z-index:1; }
    .pc-status { display:inline-flex; align-items:center; gap:5px;
      background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25);
      border-radius:999px; padding:3px 11px; font-size:.68rem; color:#fff; font-weight:600;
      position:relative; z-index:1; }
    .pc-body { padding:16px 18px; }
    .pc-row { display:flex; align-items:flex-start; gap:8px; padding:7px 0;
      border-bottom:1px solid var(--gray-100); font-size:.78rem; }
    .pc-row:last-child { border-bottom:none; }
    .pc-row i { color:var(--blue-400); font-size:.85rem; flex-shrink:0; margin-top:1px; }
    .pc-label { color:var(--gray-400); min-width:60px; font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; }
    .pc-val   { color:var(--gray-900); font-weight:600; }

    /* ── RIGHT COLUMN ── */
    .right-col { display:flex; flex-direction:column; gap:18px; }

    /* ── SECTION CARD ── */
    .sec-card { background:var(--white); border:1px solid var(--gray-200);
      border-radius:14px; overflow:hidden; box-shadow:var(--shadow-sm); }
    .sec-head { display:flex; align-items:center; justify-content:space-between;
      padding:13px 20px; border-bottom:1px solid var(--gray-100); }
    .sec-head.blue   { background:#fafcff; border-bottom-color:var(--blue-100); }
    .sec-head.green  { background:#fafffe; border-bottom-color:var(--green-100); }
    .sec-head.amber  { background:#fffef5; border-bottom-color:var(--amber-100); }
    .sec-head.violet { background:#fdfaff; border-bottom-color:var(--violet-100); }
    .sec-head.sky    { background:#f9feff; border-bottom-color:var(--sky-100); }
    .sec-head.red    { background:#fff9f9; border-bottom-color:var(--red-100); }
    .sh-left { display:flex; align-items:center; gap:10px; }
    .sh-icon { width:32px; height:32px; border-radius:8px;
      display:flex; align-items:center; justify-content:center; font-size:.88rem; }
    .sh-icon.blue   { background:var(--blue-50);   color:var(--blue-600); }
    .sh-icon.green  { background:var(--green-50);  color:var(--green-600); }
    .sh-icon.amber  { background:var(--amber-50);  color:var(--amber-600); }
    .sh-icon.violet { background:var(--violet-50); color:var(--violet-600); }
    .sh-icon.sky    { background:var(--sky-50);    color:var(--sky-600); }
    .sh-icon.red    { background:var(--red-50);    color:var(--red-600); }
    .sh-title { font-size:.88rem; font-weight:800; color:var(--gray-900); }
    .sh-sub   { font-size:.68rem; color:var(--gray-400); margin-top:1px; }
    .sec-body { padding:18px 20px; }

    /* ── FIELD ── */
    .field { margin-bottom:14px; }
    .field label { display:block; font-size:.65rem; font-weight:700;
      letter-spacing:.08em; text-transform:uppercase; color:var(--gray-500); margin-bottom:6px; }
    input[type=text],input[type=number],input[type=date],input[type=time],
    select,textarea {
      width:100%; padding:8px 12px; background:var(--gray-50);
      border:1.5px solid var(--gray-200); border-radius:9px;
      font-family:'Sora',sans-serif; font-size:.8rem; color:var(--gray-700);
      outline:none; transition:border-color .18s,box-shadow .18s,background .18s;
    }
    input:hover,select:hover,textarea:hover { border-color:var(--blue-300); }
    input:focus,select:focus,textarea:focus { border-color:var(--blue-500);
      box-shadow:0 0 0 3px var(--blue-glow); background:var(--white); }
    input::placeholder,textarea::placeholder { color:var(--gray-300); }
    select { appearance:none; cursor:pointer;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right 10px center;
      padding-right:30px; background-color:var(--gray-50); }
    textarea { resize:vertical; min-height:72px; line-height:1.55; }
    .fg2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .fg3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
    .fg4 { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:12px; }

    /* ── VITALS STEP PROGRESS ── */
    .vstep-nav { display:flex; gap:6px; margin-bottom:16px; }
    .vstep-btn { flex:1; padding:8px 6px; border-radius:8px; text-align:center;
      font-size:.68rem; font-weight:700; border:1.5px solid var(--gray-200);
      background:var(--white); color:var(--gray-400); cursor:pointer; transition:all .18s; }
    .vstep-btn.active { background:var(--blue-600); border-color:var(--blue-600); color:#fff; }
    .vstep-btn.done   { background:var(--green-50); border-color:var(--green-100); color:var(--green-700); }
    .vstep-content { display:none; }
    .vstep-content.active { display:block; animation:fadeUp .25s ease; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }

    /* ── VITAL BOX ── */
    .vital-box { background:var(--gray-50); border:1px solid var(--gray-200); border-radius:10px; padding:11px 13px; }
    .vb-label { font-size:.6rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase; color:var(--gray-400); margin-bottom:5px; }
    .vital-box input { padding:6px 10px; font-size:.8rem; height:34px; }

    /* ── CHECK ITEMS ── */
    .check-grid   { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
    .check-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px; }
    .check-item { display:flex; align-items:center; gap:8px; padding:7px 11px;
      border-radius:8px; background:var(--gray-50); border:1px solid var(--gray-200);
      cursor:pointer; transition:all .14s; }
    .check-item:hover { border-color:var(--blue-200); background:var(--blue-50); }
    .check-item input { width:15px; height:15px; accent-color:var(--blue-600);
      flex-shrink:0; padding:0; }
    .check-item span { font-size:.76rem; color:var(--gray-700); font-weight:500; cursor:pointer; }

    /* ── DATA TABLE ── */
    .data-table { width:100%; border-collapse:collapse; font-size:.78rem; }
    .data-table thead th { padding:8px 13px; text-align:left; font-size:.62rem;
      font-weight:700; letter-spacing:.09em; text-transform:uppercase;
      color:var(--gray-400); background:var(--gray-50); border-bottom:1px solid var(--gray-200);
      white-space:nowrap; }
    .data-table tbody tr { border-bottom:1px solid var(--gray-100); transition:background .1s; }
    .data-table tbody tr:last-child { border-bottom:none; }
    .data-table tbody tr:hover { background:var(--blue-50); }
    .data-table td { padding:9px 13px; color:var(--gray-700); vertical-align:middle; }
    .data-table td.mono { font-family:monospace; color:var(--gray-400); font-size:.74rem; }

    /* ── STATUS PILLS ── */
    .pill { display:inline-flex; align-items:center; gap:4px;
      padding:2px 9px; border-radius:999px; font-size:.65rem; font-weight:700; white-space:nowrap; }
    .pill.pending  { background:var(--amber-50); border:1px solid var(--amber-100); color:var(--amber-700); }
    .pill.complete { background:var(--green-50); border:1px solid var(--green-100); color:var(--green-700); }
    .pill.sent     { background:var(--blue-50);  border:1px solid var(--blue-100);  color:var(--blue-700); }
    .pill.no       { background:var(--gray-100); border:1px solid var(--gray-200);  color:var(--gray-500); }
    .pill.yes      { background:var(--green-50); border:1px solid var(--green-100); color:var(--green-700); }

    /* ── BUTTONS ── */
    .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px;
      border-radius:9px; font-family:'Sora',sans-serif; font-size:.78rem;
      font-weight:700; cursor:pointer; border:none; transition:all .18s; }
    .btn-blue  { background:var(--blue-600); color:#fff; box-shadow:0 3px 10px rgba(37,99,235,.25); }
    .btn-blue:hover  { background:var(--blue-700); transform:translateY(-1px); }
    .btn-green { background:var(--green-600); color:#fff; box-shadow:0 3px 10px rgba(5,150,105,.22); }
    .btn-green:hover { background:var(--green-700); transform:translateY(-1px); }
    .btn-amber { background:var(--amber-600); color:#fff; box-shadow:0 3px 10px rgba(217,119,6,.22); }
    .btn-amber:hover { background:var(--amber-700); transform:translateY(-1px); }
    .btn-ghost { background:var(--white); border:1.5px solid var(--gray-200); color:var(--gray-500); }
    .btn-ghost:hover { border-color:var(--blue-200); color:var(--blue-600); background:var(--blue-50); }
    .btn-sm { padding:5px 12px; font-size:.72rem; }
    .btn-send-cashier {
      display:inline-flex; align-items:center; gap:4px; padding:4px 11px; border-radius:7px;
      background:var(--blue-50); border:1px solid var(--blue-100); color:var(--blue-700);
      font-size:.7rem; font-weight:700; cursor:pointer; font-family:'Sora',sans-serif; transition:all .15s; }
    .btn-send-cashier:hover { background:var(--blue-600); color:#fff; border-color:var(--blue-600); }

    /* ── SECTION DIVIDER ── */
    .sdiv { display:flex; align-items:center; gap:10px; margin:18px 0 14px; }
    .sdiv-label { font-size:.62rem; font-weight:700; letter-spacing:.1em;
      text-transform:uppercase; color:var(--gray-400); white-space:nowrap; display:flex; align-items:center; gap:5px; }
    .sdiv-label i { color:var(--blue-400); }
    .sdiv::before,.sdiv::after { content:''; flex:1; height:1px; background:var(--gray-200); }

    /* ── LAB RESULT BADGE ── */
    .badge-rel { position:relative; display:inline-flex; }
    .count-badge { position:absolute; top:-6px; right:-8px;
      background:var(--red-600); color:#fff; font-size:.58rem; font-weight:800;
      padding:2px 6px; border-radius:999px; min-width:18px; text-align:center;
      border:2px solid var(--white); }

    /* ── COLLAPSIBLE ── */
    .coll-body { display:none; border-top:1px solid var(--gray-100); }
    .coll-body.open { display:block; animation:fadeUp .2s ease; }

    /* ── HISTORY ITEM ── */
    .history-item { padding:12px 18px; border-bottom:1px solid var(--gray-100); font-size:.78rem; }
    .history-item:last-child { border-bottom:none; }
    .history-item:hover { background:var(--blue-50); }
    .hi-diag { font-weight:700; color:var(--gray-900); margin-bottom:3px; }
    .hi-meta  { font-size:.7rem; color:var(--gray-400); display:flex; align-items:center; gap:8px; }

    /* ── PRESC CARD ── */
    .presc-item { background:var(--gray-50); border:1px solid var(--gray-200);
      border-radius:10px; padding:12px 16px; margin-bottom:10px; }
    .presc-item:last-child { margin-bottom:0; }
    .presc-date { font-size:.7rem; color:var(--gray-400); font-weight:600; margin-bottom:7px; display:flex; align-items:center; gap:5px; }
    .med-chip { display:inline-flex; align-items:center; gap:5px;
      background:var(--blue-50); border:1px solid var(--blue-100);
      border-radius:6px; padding:3px 9px; font-size:.72rem; color:var(--blue-700); font-weight:600; margin:2px; }

    /* ── SUBMIT SECTION ── */
    .submit-area { background:var(--white); border:1px solid var(--gray-200);
      border-radius:14px; padding:22px 24px; margin-top:18px; box-shadow:var(--shadow-sm);
      display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    .submit-info { font-size:.78rem; color:var(--gray-400); }
    .btn-submit { padding:11px 32px; border-radius:10px; font-size:.9rem; font-weight:800;
      background:linear-gradient(135deg,var(--green-700),var(--green-600));
      color:#fff; border:none; cursor:pointer; font-family:'Sora',sans-serif;
      box-shadow:0 5px 18px rgba(5,150,105,.3); transition:all .2s;
      display:flex; align-items:center; gap:8px; }
    .btn-submit:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(5,150,105,.38); }

    /* Select2 light */
    .select2-container--default .select2-selection--single {
      background:var(--gray-50)!important; border:1.5px solid var(--gray-200)!important;
      border-radius:9px!important; height:38px!important; display:flex!important; align-items:center!important; }
    .select2-container--default .select2-selection--single:hover { border-color:var(--blue-300)!important; }
    .select2-container--default.select2-container--open .select2-selection--single {
      border-color:var(--blue-500)!important; box-shadow:0 0 0 3px var(--blue-glow)!important; background:var(--white)!important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color:var(--blue-700)!important; font-weight:600!important; font-family:'Sora',sans-serif!important;
      font-size:13px!important; line-height:38px!important; padding-left:12px!important; }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color:var(--gray-400)!important; font-weight:400!important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height:38px!important; right:10px!important; }
    .select2-dropdown { background:var(--white)!important; border:1.5px solid var(--blue-200)!important;
      border-radius:10px!important; box-shadow:var(--shadow-md)!important; font-family:'Sora',sans-serif!important; overflow:hidden; }
    .select2-results__option { color:var(--gray-700)!important; font-size:13px!important; padding:8px 13px!important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background:var(--blue-50)!important; color:var(--blue-700)!important; }

    /* empty state */
    .empty-state { padding:28px 20px; text-align:center; color:var(--gray-400); }
    .empty-state i { display:block; font-size:1.8rem; color:var(--gray-300); margin-bottom:8px; }
    .empty-state p { font-size:.78rem; }

    /* alert box */
    .info-box { background:var(--blue-50); border:1px solid var(--blue-100);
      border-radius:9px; padding:10px 14px; font-size:.76rem; color:var(--blue-800);
      display:flex; align-items:center; gap:8px; }
    .info-box i { color:var(--blue-500); flex-shrink:0; }
    .dispense-alert { background:var(--green-50); border:1px solid var(--green-100);
      border-radius:9px; padding:10px 14px; font-size:.76rem; color:var(--green-800);
      display:none; align-items:center; gap:8px; margin-bottom:12px; }
    .dispense-alert i { color:var(--green-500); }

    @media(max-width:900px) { .two-col { grid-template-columns:1fr; } .patient-card { position:relative; top:0; } }
    @media(max-width:600px) { .page { padding:14px 10px 48px; } .fg2,.fg3,.fg4 { grid-template-columns:1fr; } .fg2 { grid-template-columns:1fr; } }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="tb-brand">
    <div class="tb-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="tb-name">Angelora</span>
    <span class="tb-sep">·</span>
    <span class="tb-page">Consultation</span>
  </div>
  <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
</header>

<div class="page">
<div class="two-col">

<!-- ══ LEFT: PATIENT CARD ══ -->
<aside>
  <div class="patient-card">
    <div class="pc-hero">
      <?php if($hasPhoto): ?>
        <img src="<?= $photoSrc ?>" class="pc-avatar" alt="<?= htmlspecialchars($patient['full_name']) ?>">
      <?php else: ?>
        <div class="pc-avatar-fallback"><?= $initials ?></div>
      <?php endif; ?>
      <div class="pc-name"><?= htmlspecialchars($patient['full_name']) ?></div>
      <?php if(!empty($patient['patient_pin'])): ?>
        <div class="pc-pin"><?= htmlspecialchars($patient['patient_pin']) ?></div>
      <?php endif; ?>
      <?php if(!empty($patient['patient_status'])): ?>
        <div class="pc-status"><i class="bi bi-circle-fill" style="font-size:.45rem"></i><?= htmlspecialchars($patient['patient_status']) ?></div>
      <?php endif; ?>
    </div>
    <div class="pc-body">
      <?php
      $pcFields = ['gender'=>['bi-person-fill','Gender'],'age'=>['bi-calendar3','Age'],
        'phone'=>['bi-telephone-fill','Phone'],'email'=>['bi-envelope-fill','Email'],
        'address'=>['bi-geo-alt-fill','Address'],'patient_type'=>['bi-tag-fill','Type'],
        'hmo_name'=>['bi-shield-fill','HMO']];
      foreach($pcFields as $f=>[$ico,$lbl]):
        if(!empty($patient[$f])): ?>
      <div class="pc-row">
        <i class="bi <?= $ico ?>"></i>
        <div><div class="pc-label"><?= $lbl ?></div><div class="pc-val"><?= htmlspecialchars($patient[$f]) ?></div></div>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>

  <!-- PAST RECORDS (collapsed by default) -->
  <?php if(!empty($medicalHistory[$patient_id])): ?>
  <div class="sec-card" style="margin-top:16px">
    <div class="sec-head blue" style="cursor:pointer" onclick="toggleColl('mh')">
      <div class="sh-left">
        <div class="sh-icon blue"><i class="bi bi-journal-medical"></i></div>
        <div><div class="sh-title">Medical History</div><div class="sh-sub"><?= count($medicalHistory[$patient_id]) ?> record(s)</div></div>
      </div>
      <i class="bi bi-chevron-down" id="mh-chev" style="color:var(--gray-400);font-size:.78rem"></i>
    </div>
    <div id="mh-coll" class="coll-body">
      <?php foreach($medicalHistory[$patient_id] as $rec): ?>
      <div class="history-item">
        <div class="hi-diag"><?= htmlspecialchars($rec['diagnosis']) ?></div>
        <div class="hi-meta">
          <span><i class="bi bi-person-badge-fill"></i><?= htmlspecialchars($rec['doctor_name']) ?></span>
          <?php if($rec['notes']): ?><span><?= htmlspecialchars($rec['notes']) ?></span><?php endif; ?>
          <?php if($rec['attachment']): ?><a href="../uploads/<?= htmlspecialchars($rec['attachment']) ?>" target="_blank" style="color:var(--blue-600)">View</a><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- PRIOR PRESCRIPTIONS -->
  <?php if(!empty($prescriptions[$patient_id])): ?>
  <div class="sec-card" style="margin-top:16px">
    <div class="sec-head violet" style="cursor:pointer" onclick="toggleColl('px')">
      <div class="sh-left">
        <div class="sh-icon violet"><i class="bi bi-capsule-pill"></i></div>
        <div><div class="sh-title">Prior Prescriptions</div><div class="sh-sub"><?= count($prescriptions[$patient_id]) ?> prescription(s)</div></div>
      </div>
      <i class="bi bi-chevron-down" id="px-chev" style="color:var(--gray-400);font-size:.78rem"></i>
    </div>
    <div id="px-coll" class="coll-body">
      <div style="padding:14px 18px">
        <?php foreach($prescriptions[$patient_id] as $presc): ?>
        <div class="presc-item">
          <div class="presc-date"><i class="bi bi-clock"></i><?= htmlspecialchars($presc['prescription_date']) ?></div>
          <?php $items = $prescItems[$presc['prescription_id']] ?? [];
          if($items): foreach($items as $it): ?>
            <span class="med-chip"><i class="bi bi-capsule-pill"></i><?= htmlspecialchars($it['medicine_name']) ?> — <?= htmlspecialchars($it['dosage']) ?>, <?= htmlspecialchars($it['duration']) ?></span>
          <?php endforeach; else: ?><span style="font-size:.72rem;color:var(--gray-400)">No medicines listed</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</aside>

<!-- ══ RIGHT: CONSULTATION FORM ══ -->
<div class="right-col">

  <!-- VITAL SIGNS FROM NURSE -->
  <?php if(!empty($vitalSigns[$patient_id])): ?>
  <div class="sec-card">
    <div class="sec-head green" style="cursor:pointer" onclick="toggleColl('vs')">
      <div class="sh-left">
        <div class="sh-icon green"><i class="bi bi-heart-pulse-fill"></i></div>
        <div><div class="sh-title">Vital Signs (Nurse Records)</div><div class="sh-sub">Click to view / edit</div></div>
      </div>
      <i class="bi bi-chevron-down" id="vs-chev" style="color:var(--gray-400);font-size:.78rem;transform:rotate(180deg);display:inline-block"></i>
    </div>
    <div id="vs-coll" class="coll-body open">
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead><tr><th>Date</th><th>BP</th><th>Pulse</th><th>Temp</th><th>Resp</th><th>O₂</th><th>Pain</th><th>Height</th><th>Weight</th><th>Blood Sugar</th><th>Symptoms</th></tr></thead>
          <tbody>
          <?php foreach($vitalSigns[$patient_id] as $v): ?>
          <tr>
            <td class="mono"><?= $v['recorded_at'] ?></td>
            <td><input class="editable" style="width:80px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="blood_pressure" value="<?= htmlspecialchars($v['blood_pressure']) ?>"></td>
            <td><input class="editable" style="width:70px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="pulse_rate" value="<?= htmlspecialchars($v['pulse_rate']) ?>"></td>
            <td><input class="editable" style="width:70px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="temperature" value="<?= htmlspecialchars($v['temperature']) ?>"></td>
            <td><input class="editable" style="width:70px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="respiration_rate" value="<?= htmlspecialchars($v['respiration_rate']) ?>"></td>
            <td><input class="editable" style="width:70px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="oxygen_saturation" value="<?= htmlspecialchars($v['oxygen_saturation']) ?>"></td>
            <td><input class="editable" style="width:60px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="pain_level" value="<?= htmlspecialchars($v['pain_level']) ?>"></td>
            <td><input class="editable" style="width:70px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="height_cm" value="<?= htmlspecialchars($v['height_cm']) ?>"></td>
            <td><input class="editable" style="width:70px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="weight_kg" value="<?= htmlspecialchars($v['weight_kg']) ?>"></td>
            <td><input class="editable" style="width:80px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50)" data-id="<?= $v['vital_id'] ?>" data-field="blood_sugar" value="<?= htmlspecialchars($v['blood_sugar']) ?>"></td>
            <td><textarea class="editable" style="width:120px;padding:4px 8px;font-size:.72rem;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50);resize:none;height:50px" data-id="<?= $v['vital_id'] ?>" data-field="symptoms_notes"><?= htmlspecialchars($v['symptoms_notes']) ?></textarea></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- LAB TESTS HISTORY -->
  <?php if(!empty($labTests[$patient_id])): ?>
  <div class="sec-card">
    <div class="sec-head sky" style="cursor:pointer" onclick="toggleColl('lt')">
      <div class="sh-left">
        <div class="sh-icon sky"><i class="bi bi-eyedropper-fill"></i></div>
        <div><div class="sh-title">Previous Lab Tests</div><div class="sh-sub"><?= count($labTests[$patient_id]) ?> test(s)</div></div>
      </div>
      <i class="bi bi-chevron-down" id="lt-chev" style="color:var(--gray-400);font-size:.78rem"></i>
    </div>
    <div id="lt-coll" class="coll-body">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Test</th><th>Result</th></tr></thead>
        <tbody>
        <?php foreach($labTests[$patient_id] as $l): ?>
        <tr><td class="mono"><?= htmlspecialchars($l['test_date']) ?></td>
            <td><?= htmlspecialchars($l['test_name']) ?></td>
            <td><?= htmlspecialchars($l['result']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- NEW CONSULTATION FORM -->
  <form action="save_consultation.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action_type" value="save">
    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">

    <!-- ── VITAL SIGNS (4-step) ── -->
    <div class="sec-card">
      <div class="sec-head blue">
        <div class="sh-left">
          <div class="sh-icon blue"><i class="bi bi-heart-pulse-fill"></i></div>
          <div><div class="sh-title">Vital Signs &amp; Measurements</div><div class="sh-sub">4-step assessment</div></div>
        </div>
      </div>
      <div class="sec-body">
        <div class="vstep-nav">
          <div class="vstep-btn active" id="vn1" onclick="vstep(1)"><i class="bi bi-1-circle-fill"></i> Begin</div>
          <div class="vstep-btn" id="vn2" onclick="vstep(2)"><i class="bi bi-2-circle-fill"></i> Vitals</div>
          <div class="vstep-btn" id="vn3" onclick="vstep(3)"><i class="bi bi-3-circle-fill"></i> Measurements</div>
          <div class="vstep-btn" id="vn4" onclick="vstep(4)"><i class="bi bi-4-circle-fill"></i> Observations</div>
        </div>

        <!-- Step 1 -->
        <div class="vstep-content active" id="vs1">
          <div class="info-box"><i class="bi bi-info-circle-fill"></i>Review nurse-recorded vitals above, then proceed to document consultation vitals step by step.</div>
          <div style="margin-top:14px;text-align:right">
            <button type="button" class="btn btn-blue" onclick="vstep(2)">Begin <i class="bi bi-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="vstep-content" id="vs2">
          <div class="fg3">
            <div class="vital-box"><div class="vb-label">Temperature (°C)</div><input type="number" step="0.1" name="temperature" id="temperature" placeholder="36.5"></div>
            <div class="vital-box"><div class="vb-label">Pulse Rate (bpm)</div><input type="number" name="pulse" id="pulse_rate" placeholder="72"></div>
            <div class="vital-box"><div class="vb-label">Respiration Rate</div><input type="number" name="respiratory_rate" id="respiration_rate" placeholder="16"></div>
            <div class="vital-box"><div class="vb-label">Blood Pressure</div><input type="text" name="blood_pressure" id="blood_pressure" placeholder="120/80"></div>
            <div class="vital-box"><div class="vb-label">O₂ Saturation (%)</div><input type="number" name="oxygen_saturation" id="oxygen_saturation" placeholder="98"></div>
            <div class="vital-box"><div class="vb-label">Pain Level (0–10)</div><input type="number" min="0" max="10" name="pain_level" id="pain_level" placeholder="0"></div>
          </div>
          <div style="display:flex;justify-content:space-between;margin-top:14px">
            <button type="button" class="btn btn-ghost" onclick="vstep(1)"><i class="bi bi-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-blue" onclick="vstep(3)">Next <i class="bi bi-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="vstep-content" id="vs3">
          <div class="fg4">
            <div class="vital-box"><div class="vb-label">Height (cm)</div><input type="number" step="0.01" name="height_cm" id="height_cm" placeholder="170" oninput="calcBMI()"></div>
            <div class="vital-box"><div class="vb-label">Weight (kg)</div><input type="number" step="0.01" name="weight_kg" id="weight_kg" placeholder="70" oninput="calcBMI()"></div>
            <div class="vital-box"><div class="vb-label">BMI (auto)</div><input type="text" name="bmi" id="bmi" placeholder="—" readonly style="background:var(--blue-50);color:var(--blue-700);font-weight:700"></div>
          </div>
          <div style="font-size:.68rem;color:var(--gray-400);margin-top:6px"><i class="bi bi-info-circle"></i> Normal BMI: 18.5 – 24.9 kg/m²</div>
          <div style="display:flex;justify-content:space-between;margin-top:14px">
            <button type="button" class="btn btn-ghost" onclick="vstep(2)"><i class="bi bi-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-blue" onclick="vstep(4)">Next <i class="bi bi-arrow-right"></i></button>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="vstep-content" id="vs4">
          <div class="fg3">
            <div class="vital-box"><div class="vb-label">Blood Sugar (mg/dL)</div><input type="number" step="0.1" name="blood_sugar" id="blood_sugar" placeholder="—"></div>
            <div class="vital-box"><div class="vb-label">Level of Consciousness</div>
              <select name="consciousness_level" id="consciousness_level">
                <option value="" disabled selected>AVPU</option>
                <option>Alert</option><option>Verbal</option><option>Pain</option><option>Unresponsive</option>
              </select>
            </div>
            <div class="vital-box"><div class="vb-label">Time Vitals Taken</div><input type="time" name="vitals_time" id="vitals_time"></div>
          </div>
          <div class="field" style="margin-top:12px">
            <label>Observed Symptoms / Notes</label>
            <textarea name="symptoms_notes" id="symptoms_notes" rows="2" placeholder="Any observed symptoms or additional notes…"></textarea>
          </div>
          <div style="display:flex;justify-content:space-between;margin-top:14px">
            <button type="button" class="btn btn-ghost" onclick="vstep(3)"><i class="bi bi-arrow-left"></i> Back</button>
            <button type="button" class="btn btn-green" onclick="scrollToSection('clinical')"><i class="bi bi-check-circle-fill"></i> Done — Continue</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── CLINICAL NOTES ── -->
    <div class="sec-card" id="clinical">
      <div class="sec-head blue">
        <div class="sh-left">
          <div class="sh-icon blue"><i class="bi bi-clipboard2-pulse-fill"></i></div>
          <div><div class="sh-title">Clinical Notes</div><div class="sh-sub">Complaint, examination and diagnosis</div></div>
        </div>
      </div>
      <div class="sec-body">
        <div class="fg2">
          <div class="field">
            <label>Chief Complaint &amp; History</label>
            <textarea name="chief_complaint" rows="3" placeholder="Presenting complaints and relevant history…"></textarea>
          </div>
          <div class="field">
            <label>Physical Examination</label>
            <textarea name="physical_exam" rows="3" placeholder="Findings on examination…"></textarea>
          </div>
          <div class="field">
            <label>Diagnosis</label>
            <textarea name="diagnosis" rows="2" placeholder="Working / confirmed diagnosis…"></textarea>
          </div>
          <div class="field">
            <label>Investigations</label>
            <textarea name="investigations" rows="2" placeholder="Ordered investigations…"></textarea>
          </div>
          <div class="field" style="grid-column:span 2">
            <label>Treatment Plan</label>
            <textarea name="treatment_plan" rows="3" placeholder="Management plan, follow-up instructions…"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ── LAB INVESTIGATIONS ── -->
    <div class="sec-card">
      <div class="sec-head sky">
        <div class="sh-left">
          <div class="sh-icon sky"><i class="bi bi-eyedropper-fill"></i></div>
          <div><div class="sh-title">Lab Investigations</div><div class="sh-sub">Select tests and send to lab</div></div>
        </div>
      </div>
      <div class="sec-body">
        <div class="check-grid-3">
          <?php foreach($labTestsCatalog as $test): ?>
          <label class="check-item">
            <input type="checkbox" name="lab_order[]" value="<?= htmlspecialchars($test['test_name']) ?>" id="lab_<?= $test['id'] ?>">
            <span><?= htmlspecialchars($test['test_name']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="field" style="margin-top:14px">
          <label>Lab Notes / Instructions</label>
          <textarea name="lab_notes" id="lab_notes" rows="2" placeholder="Specific instructions or reasons for tests…"></textarea>
        </div>
        <div style="display:flex;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap">
          <button type="button" class="btn btn-blue btn-sm" onclick="sendToLab()">
            <i class="bi bi-send-fill"></i> Send to Lab
          </button>
          <div class="badge-rel">
            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleLabResults()">
              <i class="bi bi-search"></i> View Test Results
            </button>
            <span class="count-badge" id="labResultBadge" style="display:none">0</span>
          </div>
        </div>

        <!-- Lab Orders Table -->
        <div class="sdiv"><span class="sdiv-label"><i class="bi bi-table"></i>Current Lab Orders</span></div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Test</th><th>Send to Cashier</th><th>Paid</th></tr></thead>
          <tbody id="labOrdersTableBody">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM lab_orders WHERE patient_id = ?");
          $stmt->execute([$patient_id]);
          foreach($stmt->fetchAll() as $i=>$order):
          ?>
          <tr>
            <td class="mono"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($order['test_name']) ?></td>
            <td>
              <?= !empty($order['is_sent_to_cashier'])
                ? '<span class="pill sent">SENT</span>'
                : "<button class='btn-send-cashier send-to-cashier' data-id='{$order['id']}' data-type='lab'><i class='bi bi-send-fill'></i> Send</button>" ?>
            </td>
            <td data-type="lab" data-id="<?= $order['id'] ?>">
              <span class="pill <?= $order['is_paid'] ? 'yes' : 'no' ?>"><?= $order['is_paid'] ? 'YES' : 'NO' ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Lab Results Panel -->
        <div id="labResultsPanel" style="display:none;margin-top:14px">
          <div class="info-box" style="margin-bottom:10px"><i class="bi bi-arrow-clockwise"></i> Live results — auto-refreshes every 5 seconds</div>
          <table class="data-table">
            <thead><tr><th>Patient ID</th><th>Test</th><th>Requested By</th><th>Status</th><th>Result</th><th>Report</th></tr></thead>
            <tbody id="labTestTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── NURSING PROCEDURES ── -->
    <div class="sec-card">
      <div class="sec-head green">
        <div class="sh-left">
          <div class="sh-icon green"><i class="bi bi-clipboard2-heart-fill"></i></div>
          <div><div class="sh-title">Nursing Procedures</div><div class="sh-sub">Select procedures and send to nurse</div></div>
        </div>
      </div>
      <div class="sec-body">
        <div class="check-grid-3">
          <?php foreach($nursingProceduresCatalog as $proc): ?>
          <label class="check-item">
            <input type="checkbox" name="procedure_order[]" value="<?= htmlspecialchars($proc['procedure_name']) ?>" id="proc_<?= $proc['id'] ?>">
            <span><?= htmlspecialchars($proc['procedure_name']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="field" style="margin-top:14px">
          <label>Nursing Notes / Instructions</label>
          <textarea name="nursing_notes" id="nursing_notes" rows="2" placeholder="Specific instructions for the nurse…"></textarea>
        </div>
        <button type="button" class="btn btn-green btn-sm" style="margin-top:12px" onclick="sendToNurse()">
          <i class="bi bi-send-fill"></i> Send to Nurse
        </button>

        <div class="sdiv"><span class="sdiv-label"><i class="bi bi-table"></i>Current Nursing Orders</span></div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Procedure</th><th>Send to Cashier</th><th>Paid</th></tr></thead>
          <tbody id="nurseOrdersTableBody">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM nursing_orders WHERE patient_id = ?");
          $stmt->execute([$patient_id]);
          foreach($stmt->fetchAll() as $i=>$order):
          ?>
          <tr>
            <td class="mono"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($order['procedure_name']) ?></td>
            <td>
              <?= !empty($order['is_sent_to_cashier'])
                ? '<span class="pill sent">SENT</span>'
                : "<button class='btn-send-cashier send-to-cashier' data-id='{$order['id']}' data-type='nursing'><i class='bi bi-send-fill'></i> Send</button>" ?>
            </td>
            <td data-type="nursing" data-id="<?= $order['id'] ?>">
              <span class="pill <?= $order['is_paid'] ? 'yes' : 'no' ?>"><?= $order['is_paid'] ? 'YES' : 'NO' ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Treatments & Prescriptions dynamic -->
        <div class="sdiv"><span class="sdiv-label"><i class="bi bi-stethoscope"></i>Treatments &amp; Prescriptions</span></div>
        <div id="dynamic-medical-records">
          <div class="info-box"><i class="bi bi-arrow-clockwise"></i> Loading records…</div>
        </div>
      </div>
    </div>

    <!-- ── PHARMACY ORDERS ── -->
    <div class="sec-card">
      <div class="sec-head amber">
        <div class="sh-left">
          <div class="sh-icon amber"><i class="bi bi-capsule-pill"></i></div>
          <div><div class="sh-title">Pharmacy Orders</div><div class="sh-sub">Prescribe medicines and send to pharmacy</div></div>
        </div>
      </div>
      <div class="sec-body">
        <div class="fg2">
          <div class="field">
            <label>Prescription</label>
            <textarea name="pharmacy_order" rows="2" placeholder="e.g. Paracetamol 500mg, Amoxicillin 250mg…"></textarea>
          </div>
          <div class="field">
            <label>Dosage &amp; Instructions</label>
            <textarea name="pharmacy_dosage" rows="2" placeholder="e.g. 500mg × 3 daily, after meals…"></textarea>
          </div>
        </div>
        <button type="button" class="btn btn-amber btn-sm" onclick="sendToPharmacy()">
          <i class="bi bi-send-fill"></i> Send to Pharmacy
        </button>

        <!-- Dispensed alert -->
        <div class="dispense-alert" id="dispenseAlert"><i class="bi bi-check-circle-fill"></i> New medicine dispensed!</div>

        <div class="sdiv"><span class="sdiv-label"><i class="bi bi-table"></i>Current Pharmacy Orders</span></div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Send to Cashier</th><th>Paid</th></tr></thead>
          <tbody id="pharmacyOrdersTableBody">
          <?php
          $stmt = $pdo->prepare("SELECT * FROM pharmacy_orders WHERE patient_id = ?");
          $stmt->execute([$patient_id]);
          foreach($stmt->fetchAll() as $i=>$order):
          ?>
          <tr>
            <td class="mono"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($order['medicine_name']) ?></td>
            <td><?= htmlspecialchars($order['dosage']) ?></td>
            <td>
              <?= !empty($order['is_sent_to_cashier'])
                ? '<span class="pill sent">SENT</span>'
                : "<button class='btn-send-cashier send-to-cashier' data-id='{$order['id']}' data-type='pharmacy'><i class='bi bi-send-fill'></i> Send</button>" ?>
            </td>
            <td data-type="pharmacy" data-id="<?= $order['id'] ?>">
              <span class="pill <?= $order['is_paid'] ? 'yes' : 'no' ?>"><?= $order['is_paid'] ? 'YES' : 'NO' ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Dispensed medicines -->
        <div class="sdiv"><span class="sdiv-label"><i class="bi bi-check-circle-fill"></i>Dispensed Medicines</span></div>
        <table class="data-table">
          <thead><tr><th>Medicine</th><th>Qty</th><th>Prescribed By</th><th>Dispensed By</th><th>Notes</th></tr></thead>
          <tbody id="dispensedTableBody"></tbody>
        </table>
      </div>
    </div>

    <!-- ── CLINICIAN SIGN-OFF ── -->
    <div class="sec-card">
      <div class="sec-head blue">
        <div class="sh-left">
          <div class="sh-icon blue"><i class="bi bi-person-badge-fill"></i></div>
          <div><div class="sh-title">Doctor's Sign-off</div><div class="sh-sub">Name, signature and date</div></div>
        </div>
      </div>
      <div class="sec-body">
        <div class="fg2">
          <div class="field">
            <label>Doctor's Name / Signature</label>
            <input type="text" name="doctor_signature" placeholder="Full name of attending doctor">
          </div>
          <div class="field">
            <label>Consultation Date</label>
            <input type="date" name="consultation_date" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- SUBMIT -->
    <div class="submit-area">
      <div class="submit-info">
        <div style="font-size:.82rem;font-weight:700;color:var(--gray-900);margin-bottom:2px">Ready to save this consultation?</div>
        <div>All sections will be saved to the patient's permanent record.</div>
      </div>
      <button type="submit" class="btn-submit">
        <i class="bi bi-check-circle-fill"></i> Save Consultation
      </button>
    </div>

  </form>
</div><!-- /right-col -->
</div><!-- /two-col -->
</div><!-- /page -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const PATIENT_ID = <?= json_encode($patient_id) ?>;

/* ── COLLAPSIBLES ── */
function toggleColl(id) {
  const el = document.getElementById(id+'-coll');
  const chev = document.getElementById(id+'-chev');
  el.classList.toggle('open');
  chev.style.transform = el.classList.contains('open') ? 'rotate(180deg)' : '';
}

/* ── VITAL STEPS ── */
function vstep(n) {
  [1,2,3,4].forEach(i => {
    document.getElementById('vs'+i).classList.remove('active');
    const nb = document.getElementById('vn'+i);
    nb.classList.remove('active','done');
    if(i < n) nb.classList.add('done');
  });
  document.getElementById('vs'+n).classList.add('active');
  document.getElementById('vn'+n).classList.add('active');
}

/* ── BMI CALC ── */
function calcBMI() {
  const h = parseFloat(document.getElementById('height_cm').value)/100;
  const w = parseFloat(document.getElementById('weight_kg').value);
  if(h>0 && w>0) document.getElementById('bmi').value = (w/(h*h)).toFixed(1);
}

/* ── SCROLL ── */
function scrollToSection(id) {
  document.getElementById(id)?.scrollIntoView({behavior:'smooth',block:'start'});
}

/* ── LAB ── */
function sendToLab() {
  const form = new FormData();
  form.append('patient_id', PATIENT_ID);
  form.append('lab_notes', $('#lab_notes').val());
  $('input[name="lab_order[]"]:checked').each(function(){ form.append('lab_order[]', this.value); });
  $.ajax({ url:'send_to_lab.php', type:'POST', data:form, processData:false, contentType:false, dataType:'json',
    success:function(res){ alert(res.message); refreshLabOrders(); },
    error:function(){ alert('Failed to send lab order.'); }
  });
}
function refreshLabOrders() {
  $.get('fetch_lab_order_status.php',{patient_id:PATIENT_ID},function(html){ $('#labOrdersTableBody').html(html); });
}
function fetchLabTests() {
  $.ajax({ url:'fetch_lab_tests.php', type:'GET', data:{patient_id:PATIENT_ID}, dataType:'json',
    success:function(r){
      if(r.status==='success'){
        let h='';
        r.data.forEach(function(t){
          const badge = t.status.toLowerCase()==='pending'
            ? '<span class="pill pending">Pending</span>'
            : '<span class="pill complete">Complete</span>';
          const file = t.report_file ? t.report_file.replace(/^uploads[\\/]/,'') : '';
          const link = t.report_file
            ? `<a href="https://angelora.com.ng/ANGELORA/lab/uploads/${encodeURIComponent(file)}" target="_blank" style="color:var(--blue-600);font-size:.72rem;font-weight:600">View</a>`
            : '<span style="color:var(--gray-300)">—</span>';
          h+=`<tr><td class="mono">${t.patient_id}</td><td>${t.test_name}</td><td>${t.requested_by}</td><td>${badge}</td><td>${t.result??'—'}</td><td>${link}</td></tr>`;
        });
        $('#labTestTableBody').html(h||'<tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:20px">No results yet.</td></tr>');
      }
    }
  });
}
function markLabResultsAsSeen() {
  $.post('mark_lab_results_seen.php',{patient_id:PATIENT_ID},function(){ fetchNewLabResultsCount(); });
}
function toggleLabResults() {
  const p = document.getElementById('labResultsPanel');
  const vis = p.style.display==='block';
  p.style.display = vis?'none':'block';
  if(!vis){ fetchLabTests(); markLabResultsAsSeen(); }
}
function fetchNewLabResultsCount() {
  $.ajax({ url:'fetch_new_lab_result_count.php', data:{patient_id:PATIENT_ID}, dataType:'json',
    success:function(r){
      const b=$('#labResultBadge');
      const n=parseInt(r.count||0);
      b.text(n); if(n>0) b.show(); else b.hide();
    }
  });
}

/* ── NURSING ── */
function sendToNurse() {
  const form = new FormData();
  form.append('patient_id', PATIENT_ID);
  form.append('nursing_notes', $('#nursing_notes').val());
  $('input[name="procedure_order[]"]:checked').each(function(){ form.append('procedure_order[]', this.value); });
  $.ajax({ url:'send_to_nurse.php', type:'POST', data:form, processData:false, contentType:false, dataType:'json',
    success:function(res){ alert(res.message); refreshNurseOrders(); },
    error:function(){ alert('Failed to send nursing order.'); }
  });
}
function refreshNurseOrders() {
  $.get('fetch_nursing_orders.php',{patient_id:PATIENT_ID},function(html){ $('#nurseOrdersTableBody').html(html); });
}

/* ── PHARMACY ── */
function sendToPharmacy() {
  $.ajax({ url:'send_to_pharmacy.php', type:'POST', dataType:'json',
    data:{ patient_id:PATIENT_ID,
           pharmacy_order:$('textarea[name="pharmacy_order"]').val(),
           pharmacy_dosage:$('textarea[name="pharmacy_dosage"]').val() },
    success:function(res){ alert(res.message); refreshPharmacyOrders(); }
  });
}
function refreshPharmacyOrders() {
  $.get('fetch_pharmacy_order_status.php',{patient_id:PATIENT_ID},function(html){ $('#pharmacyOrdersTableBody').html(html); });
}

/* ── SEND TO CASHIER ── */
$(document).on('click','.send-to-cashier',function(e){
  e.preventDefault();
  const btn=$(this), id=btn.data('id'), type=btn.data('type');
  $.ajax({ url:'send_to_cashier.php', type:'POST', data:{id,type}, dataType:'json',
    success:function(r){
      if(r.status==='success'){
        btn.replaceWith('<span class="pill sent">SENT</span>');
        $(`td[data-type="${type}"][data-id="${id}"] span`).attr('class','pill yes').text('YES');
      } else { alert(r.message||'Failed.'); }
    }, error:function(){ alert('Server error.'); }
  });
});

/* ── PAID STATUS POLLING ── */
function updatePaidStatus(type) {
  $.ajax({ url:'fetch_paid_status.php', data:{patient_id:PATIENT_ID,type}, dataType:'json',
    success:function(r){
      if(r.status==='success'){
        r.data.forEach(row => {
          if(row.is_paid==1){
            $(`[data-type="${type}"][data-id="${row.id}"] span`).attr('class','pill yes').text('YES');
          }
        });
      }
    }
  });
}

/* ── VITALS INLINE EDIT ── */
$('.editable').on('change',function(){
  const id=$(this).data('id'), field=$(this).data('field'), value=$(this).val();
  $.ajax({ url:'update_vitals_ajax.php', method:'POST', data:{vital_id:id,field,value},
    success:function(res){
      try{ const r=JSON.parse(res); if(r.status!=='success') alert('Update failed: '+r.message); }
      catch(e){ console.error('Unexpected response'); }
    }
  });
});

/* ── DISPENSED MEDICINES ── */
let lastDispensedId = 0;
function fetchDispensedMedicines() {
  fetch(`fetch_dispensed_medicines.php?patient_id=${PATIENT_ID}&last_id=${lastDispensedId}`)
    .then(r=>r.json()).then(data=>{
      if(data.new_count>0){
        document.getElementById('dispensedTableBody').innerHTML += data.html;
        lastDispensedId = data.latest_id;
        const alert = document.getElementById('dispenseAlert');
        alert.style.display='flex'; setTimeout(()=>alert.style.display='none',3000);
      }
    });
}

/* ── TREATMENTS & PRESCRIPTIONS ── */
function loadTreatmentsAndPrescriptions() {
  fetch(`get_treatments_prescriptions.php?patient_id=${PATIENT_ID}`)
    .then(r=>r.json()).then(data=>{
      let h='';
      if(data.error){ document.getElementById('dynamic-medical-records').innerHTML=`<div style="color:var(--red-600);font-size:.78rem">${data.error}</div>`; return; }
      if(data.treatments.length){
        h+='<table class="data-table"><thead><tr><th>Treatment</th><th>Medicine</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
        data.treatments.forEach(t=>{ h+=`<tr><td>${t.treatment_name}</td><td>${t.medicine_name??'—'}</td><td>${t.notes}</td><td>${t.treatment_date}</td></tr>`; });
        h+='</tbody></table>';
      }
      if(data.prescriptions.length){
        h+='<div style="margin-top:10px"></div><table class="data-table"><thead><tr><th>Medicine</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
        data.prescriptions.forEach(p=>{ h+=`<tr><td>${p.medicine_name}</td><td>${p.notes}</td><td>${p.prescription_date}</td></tr>`; });
        h+='</tbody></table>';
      }
      if(!h) h='<div class="empty-state"><i class="bi bi-inbox"></i><p>No treatments or prescriptions found.</p></div>';
      document.getElementById('dynamic-medical-records').innerHTML=h;
    }).catch(()=>{ document.getElementById('dynamic-medical-records').innerHTML='<div style="color:var(--red-600);font-size:.78rem">Error loading data.</div>'; });
}

/* ── MARK AS SEEN (order rows) ── */
$(document).on('click', '.mark-seen', function(e) {
  e.preventDefault();
  const btn = $(this), id = btn.data('id'), type = btn.data('type');
  $.ajax({
    url: 'mark_seen.php', method: 'POST', data: { id, type },
    success: function() { btn.closest('td').html('<span class="pill sent">Seen</span>'); },
    error:   function() { alert('Failed to mark as seen. Try again.'); }
  });
});

/* ── MARK SEEN via mark_seenn.php (removes row) ── */
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('mark-seen-link')) {
    e.preventDefault();
    const id = e.target.getAttribute('data-id');
    const type = e.target.getAttribute('data-type');
    fetch(`mark_seenn.php?id=${id}&type=${type}`)
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          const row = e.target.closest('tr');
          if (row) row.parentNode.removeChild(row);
        } else { alert('Failed to mark as seen.'); }
      })
      .catch(() => alert('Request failed.'));
  }
});

/* ── FORM SUBMIT: remove required from hidden steps ── */
document.querySelector('form').addEventListener('submit',function(){
  document.querySelectorAll('.vstep-content:not(.active) input, .vstep-content:not(.active) textarea, .vstep-content:not(.active) select').forEach(el=>el.removeAttribute('required'));
});

/* ── INIT ── */
$(document).ready(function(){
  fetchNewLabResultsCount();
  loadTreatmentsAndPrescriptions();
  fetchDispensedMedicines();
  setInterval(()=>{ fetchNewLabResultsCount(); if($('#labResultsPanel').is(':visible')) fetchLabTests(); },5000);
  setInterval(()=>{ updatePaidStatus('lab'); updatePaidStatus('nursing'); updatePaidStatus('pharmacy'); },5000);
  setInterval(fetchDispensedMedicines,5000);
});
</script>
</body>
</html>