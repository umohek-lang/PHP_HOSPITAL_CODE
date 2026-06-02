<?php
include '../db.php';

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) die("No patient selected.");

$patient_stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE patient_id=?");
$patient_stmt->execute([$patient_id]);
$patient = $patient_stmt->fetch();
if (!$patient) die("Invalid patient.");

$stmt = $pdo->prepare("SELECT * FROM hos_bills WHERE patient_id=? ORDER BY invoice_no DESC, id");
$stmt->execute([$patient_id]);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$invoices = [];
foreach ($bills as $b) {
    $invoices[$b['invoice_no']][] = $b;
}

if (isset($_POST['mark_paid_invoices']) && isset($_POST['selected_invoices'])) {
    $selected_invoices = $_POST['selected_invoices'];
    $placeholders = implode(',', array_fill(0, count($selected_invoices), '?'));
    $pdo->prepare("UPDATE hos_bills SET paid=1 WHERE invoice_no IN ($placeholders)")->execute($selected_invoices);
    echo "<script>window.location='view_bill.php?patient_id=$patient_id&paid=1';</script>";
    exit;
}

$grand_total_all   = array_sum(array_column($bills, 'total'));
$paid_count        = count(array_filter($invoices, fn($items) => array_sum(array_column($items,'paid')) > 0));
$unpaid_count      = count($invoices) - $paid_count;
$initials          = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $patient['full_name']), 0, 2)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Bills — <?= htmlspecialchars($patient['full_name']) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      /* Shifted one step lighter across the board */
      --blue-800:  #2563eb; --blue-700: #3b82f6; --blue-600: #60a5fa;
      --blue-500:  #93c5fd; --blue-400: #bfdbfe; --blue-300: #dbeafe;
      --blue-200:  #e0effe; --blue-100: #eff6ff; --blue-50:  #f5f9ff;
      --blue-glow: rgba(96,165,250,.15);

      /* Text/UI stays readable — keep these anchored */
      --blue-text:    #1d4ed8;  /* used for labels, links */
      --blue-heading: #1e40af;  /* hero numbers, totals   */

      --white:   #ffffff;
      --gray-50: #f8fafc; --gray-100:#f1f5f9; --gray-200:#e2e8f0;
      --gray-300:#cbd5e1; --gray-400:#94a3b8; --gray-500:#64748b;
      --gray-600:#475569; --gray-700:#334155; --gray-800:#1e293b; --gray-900:#0f172a;

      --green:   #16a34a; --green-bg: #dcfce7; --green-100: #bbf7d0;
      --amber:   #d97706; --amber-bg: #fef3c7; --amber-100: #fde68a;
      --red:     #dc2626; --red-bg:   #fee2e2; --red-100:   #fecaca;

      --radius: 12px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.06),0 2px 8px rgba(0,0,0,.03);
      --shadow:    0 4px 16px rgba(96,165,250,.12),0 1px 4px rgba(0,0,0,.04);
      --shadow-lg: 0 12px 36px rgba(96,165,250,.18),0 2px 8px rgba(0,0,0,.05);
    }

    html, body { min-height:100vh; font-family:'Sora',sans-serif; background:var(--gray-50); color:var(--gray-800); }
    ::-webkit-scrollbar{width:6px;height:6px;} ::-webkit-scrollbar-track{background:var(--gray-100);} ::-webkit-scrollbar-thumb{background:var(--gray-300);border-radius:10px;}

    /* ════ PRINT ═════════════════════ */
    .print-header {
      display: none;
      border-bottom: 2px solid #1d4ed8;
      padding-bottom: 14px; margin-bottom: 20px;
    }
    .print-header-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
    .print-hosp-name  { font-size: 20px; font-weight: 700; color: #1d4ed8; }
    .print-hosp-sub   { font-size: 11px; color: #64748b; margin-top: 2px; }
    .print-meta       { text-align: right; font-size: 11.5px; color: #334155; }
    .print-meta strong { color: #1d4ed8; }
    .print-patient-row {
      margin-top: 10px; padding: 8px 12px;
      background: #eff6ff; border-radius: 6px;
      font-size: 12px; color: #1e293b;
      display: flex; gap: 28px; flex-wrap: wrap;
    }
    .print-patient-row span strong { color: #1d4ed8; }

    @media print {
      .topbar, .action-bar, .no-print,
      .breadcrumb, .patient-hero, .modal-backdrop,
      .select-wrap, .inv-print-btn { display: none !important; }

      body { background: white !important; font-family: sans-serif; }

      .print-header { display: block !important; }

      /* Default: hide all invoice cards */
      .invoice-card { display: none !important; break-inside: avoid; box-shadow: none !important; border: 1px solid #ccc !important; }

      /* Print ALL */
      body.print-all .invoice-card { display: block !important; }

      /* Print SINGLE/SELECTED */
      .invoice-card.printing { display: block !important; }
    }

    /* ════ TOP BAR ═══════════════════ */
    .topbar {
      position:sticky; top:0; z-index:100; height:64px; background:var(--white);
      border-bottom:1px solid var(--gray-200); box-shadow:0 1px 8px rgba(0,0,0,.06);
      display:flex; align-items:center; justify-content:space-between; padding:0 36px;
    }
    .topbar-brand { display:flex; align-items:center; gap:12px; }
    .brand-mark {
      width:36px; height:36px; border-radius:10px;
      background:linear-gradient(135deg,#3b82f6,#93c5fd);
      display:flex; align-items:center; justify-content:center;
      box-shadow:0 3px 10px rgba(96,165,250,.35);
    }
    .brand-mark i { font-size:17px; color:white; }
    .brand-text { display:flex; flex-direction:column; gap:1px; }
    .brand-name { font-family:'Instrument Serif',serif; font-size:17px; color:var(--gray-900); line-height:1; }
    .brand-sub  { font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#60a5fa; line-height:1; }
    .topbar-right { display:flex; align-items:center; gap:10px; }
    .date-pill {
      display:flex; align-items:center; gap:7px; padding:6px 14px; border-radius:20px;
      background:#f5f9ff; border:1px solid #dbeafe; font-size:12px; color:#3b82f6; font-weight:500;
    }
    .back-btn {
      display:flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px;
      background:var(--gray-100); border:1px solid var(--gray-200); color:var(--gray-600);
      font-family:'Sora',sans-serif; font-size:12.5px; font-weight:500; text-decoration:none; transition:all .18s;
    }
    .back-btn:hover { background:#f5f9ff; border-color:#bfdbfe; color:#3b82f6; }

    /* ════ PAGE ══════════════════════ */
    .page { max-width:1000px; margin:0 auto; padding:36px 24px 72px; }

    /* Breadcrumb */
    .breadcrumb { display:flex; align-items:center; gap:6px; font-size:12px; color:var(--gray-400); margin-bottom:12px; }
    .breadcrumb a { color:#3b82f6; text-decoration:none; }
    .breadcrumb a:hover { text-decoration:underline; }
    .breadcrumb i { font-size:10px; }

    /* ════ PATIENT HERO ══════════════ */
    .patient-hero {
      background:linear-gradient(135deg,#3b82f6,#60a5fa,#93c5fd);
      border-radius:18px; padding:28px 32px; margin-bottom:28px;
      display:flex; align-items:center; justify-content:space-between;
      gap:20px; flex-wrap:wrap; position:relative; overflow:hidden;
      box-shadow:var(--shadow-lg);
    }
    .patient-hero::before {
      content:''; position:absolute; top:-60px; right:-40px;
      width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.07);
    }
    .patient-hero::after {
      content:''; position:absolute; bottom:-70px; right:120px;
      width:170px; height:170px; border-radius:50%; background:rgba(255,255,255,.05);
    }
    .patient-hero-left { display:flex; align-items:center; gap:16px; position:relative; z-index:1; }
    .patient-avatar {
      width:56px; height:56px; border-radius:50%;
      background:rgba(255,255,255,.2); border:2px solid rgba(255,255,255,.35);
      display:flex; align-items:center; justify-content:center;
      font-family:'Instrument Serif',serif; font-size:20px; color:white; flex-shrink:0;
    }
    .patient-name { font-family:'Instrument Serif',serif; font-size:clamp(1.2rem,2.5vw,1.65rem); color:white; font-weight:400; line-height:1.2; }
    .patient-id   { font-size:12px; color:rgba(255,255,255,.65); margin-top:3px; }

    .hero-stats { display:flex; gap:14px; position:relative; z-index:1; flex-wrap:wrap; }
    .hstat {
      min-width:90px; text-align:center; padding:12px 16px;
      background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.22);
      border-radius:12px; backdrop-filter:blur(4px);
    }
    .hstat-num   { font-family:'Instrument Serif',serif; font-size:22px; color:white; line-height:1; }
    .hstat-label { font-size:10px; color:rgba(255,255,255,.6); margin-top:3px; text-transform:uppercase; letter-spacing:.06em; }

    /* ════ ALERT ═════════════════════ */
    .alert {
      display:flex; align-items:flex-start; gap:12px;
      padding:14px 18px; border-radius:10px; margin-bottom:20px;
      font-size:13.5px; line-height:1.5; animation:slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
    .alert i { font-size:18px; flex-shrink:0; margin-top:1px; }
    .alert-success { background:var(--green-bg); border:1px solid var(--green-100); color:var(--green); }
    .alert-info    { background:#f5f9ff; border:1px solid #dbeafe; color:#3b82f6; }

    /* ════ INVOICE CARD ══════════════ */
    .invoice-card {
      background:var(--white); border:1.5px solid var(--gray-200);
      border-radius:var(--radius); overflow:hidden;
      box-shadow:var(--shadow-sm); margin-bottom:16px;
      transition:box-shadow .2s;
    }
    .invoice-card:hover { box-shadow:var(--shadow); }

    .inv-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 20px; gap:12px; flex-wrap:wrap;
    }
    .inv-header.paid-header   { background:var(--green-bg); border-bottom:1px solid var(--green-100); }
    .inv-header.unpaid-header { background:var(--amber-bg); border-bottom:1px solid var(--amber-100); }

    .inv-no-group { display:flex; align-items:center; gap:10px; }
    .inv-icon {
      width:34px; height:34px; border-radius:9px;
      display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .paid-icon   { background:var(--green-100); } .paid-icon   i { color:var(--green);  font-size:15px; }
    .unpaid-icon { background:var(--amber-100); } .unpaid-icon i { color:var(--amber); font-size:15px; }

    .inv-no    { font-size:13px; font-weight:700; color:var(--gray-800); font-family:'Sora',sans-serif; }
    .inv-date  { font-size:11px; color:var(--gray-400); margin-top:1px; }

    .status-pill {
      display:inline-flex; align-items:center; gap:5px;
      font-size:11.5px; font-weight:700; padding:4px 12px;
      border-radius:20px; white-space:nowrap;
    }
    .pill-paid   { background:var(--green-bg); color:var(--green); border:1px solid var(--green-100); }
    .pill-unpaid { background:var(--amber-bg); color:var(--amber); border:1px solid var(--amber-100); }

    /* Table */
    .tbl-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; min-width:420px; }
    thead th {
      padding:9px 14px; text-align:left;
      font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
      color:var(--gray-500); background:var(--gray-50); border-bottom:1px solid var(--gray-200);
    }
    thead th.text-end { text-align:right; }
    tbody tr { border-bottom:1px solid var(--gray-100); transition:background .1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#f5f9ff; }
    td { padding:10px 14px; color:var(--gray-700); vertical-align:middle; }
    td.text-end { text-align:right; }
    .source-tag {
      display:inline-flex; align-items:center;
      font-size:10.5px; font-weight:700; padding:2px 9px; border-radius:20px;
      background:#eff6ff; color:#3b82f6; border:1px solid #dbeafe;
    }
    .total-row td { background:#eff6ff; font-weight:700; color:#1d4ed8; }

    /* Checkbox select */
    .select-wrap {
      display:flex; align-items:center; gap:10px;
      padding:14px 20px; border-top:1px solid var(--gray-100);
      background:var(--gray-50);
    }
    .custom-checkbox {
      width:18px; height:18px; border-radius:5px; flex-shrink:0;
      border:2px solid var(--gray-300); background:var(--white);
      appearance:none; cursor:pointer; transition:all .15s; position:relative;
    }
    .custom-checkbox:checked { background:#60a5fa; border-color:#60a5fa; }
    .custom-checkbox:checked::after {
      content:''; position:absolute; top:2px; left:5px;
      width:5px; height:9px; border:2px solid white;
      border-top:none; border-left:none; transform:rotate(45deg);
    }
    .select-label { font-size:13px; color:var(--gray-600); font-weight:500; cursor:pointer; }

    /* ════ ACTION BAR ════════════════ */
    .action-bar {
      position:sticky; bottom:20px; z-index:50;
      background:var(--white); border:1.5px solid var(--gray-200);
      border-radius:14px; padding:16px 22px;
      display:flex; align-items:center; justify-content:space-between;
      gap:16px; flex-wrap:wrap; box-shadow:var(--shadow-lg);
      margin-top:20px;
    }
    .action-bar-left  { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .action-bar-right { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

    .selected-total {
      display:flex; align-items:center; gap:8px;
      font-size:13px; color:var(--gray-600);
    }
    .selected-total-amt {
      font-family:'Instrument Serif',serif; font-size:20px;
      color:#3b82f6; font-weight:400; line-height:1;
    }

    /* Buttons */
    .btn {
      display:inline-flex; align-items:center; gap:7px;
      padding:9px 18px; border-radius:8px; border:none;
      font-family:'Sora',sans-serif; font-size:12.5px; font-weight:600;
      cursor:pointer; transition:all .18s; white-space:nowrap; text-decoration:none;
    }
    .btn-primary { background:linear-gradient(135deg,#3b82f6,#93c5fd); color:white; box-shadow:0 3px 10px rgba(96,165,250,.3); }
    .btn-primary:hover { opacity:.92; transform:translateY(-1px); color:white; }
    .btn-success { background:var(--green-bg); color:var(--green); border:1.5px solid var(--green-100); }
    .btn-success:hover { background:var(--green-100); }
    .btn-amber  { background:var(--amber-bg); color:var(--amber); border:1.5px solid var(--amber-100); }
    .btn-amber:hover { background:var(--amber-100); }
    .btn-gray   { background:var(--gray-100); color:var(--gray-600); border:1.5px solid var(--gray-200); }
    .btn-gray:hover { background:var(--gray-200); }

    /* ════ PRINT MODAL ══════════════ */
    .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:400; display:none; align-items:center; justify-content:center; }
    .modal-backdrop.open { display:flex; }
    .modal-box {
      background:var(--white); border-radius:16px;
      width:100%; max-width:500px; margin:20px;
      overflow:hidden; box-shadow:var(--shadow-lg);
      animation:popIn .25s cubic-bezier(.16,1,.3,1);
    }
    @keyframes popIn { from{opacity:0;transform:translateY(16px) scale(.97);} to{opacity:1;transform:none;} }
    .modal-head {
      padding:18px 24px; border-bottom:1px solid var(--gray-100);
      background:linear-gradient(135deg,#3b82f6,#93c5fd);
      display:flex; align-items:center; justify-content:space-between;
    }
    .modal-head h5 { font-size:15px; font-weight:700; color:white; display:flex; align-items:center; gap:8px; }
    .modal-close {
      width:28px; height:28px; border-radius:7px;
      background:rgba(255,255,255,.2); border:none; color:white;
      font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center;
      transition:background .15s;
    }
    .modal-close:hover { background:rgba(255,255,255,.35); }
    .modal-body-inner { padding:20px 24px; display:flex; flex-direction:column; gap:10px; }
    .modal-note { font-size:12.5px; color:var(--gray-500); margin-bottom:4px; }

    /* Print option rows */
    .print-option {
      display:flex; align-items:center; justify-content:space-between;
      padding:12px 16px; border-radius:10px;
      border:1.5px solid var(--gray-200); background:var(--gray-50);
      transition:border-color .15s, background .15s;
      gap:12px;
    }
    .print-option:hover { border-color:#bfdbfe; background:#f5f9ff; }
    .print-option-info { display:flex; align-items:center; gap:10px; }
    .print-option-icon {
      width:34px; height:34px; border-radius:9px; flex-shrink:0;
      display:flex; align-items:center; justify-content:center;
    }
    .print-option-icon.c-all   { background:#eff6ff; } .print-option-icon.c-all   i { color:#3b82f6; font-size:15px; }
    .print-option-icon.c-paid  { background:var(--green-bg); } .print-option-icon.c-paid  i { color:var(--green); font-size:15px; }
    .print-option-icon.c-one   { background:#fef3c7; } .print-option-icon.c-one   i { color:var(--amber); font-size:15px; }
    .print-option-name { font-size:13px; font-weight:700; color:var(--gray-800); }
    .print-option-sub  { font-size:11px; color:var(--gray-400); margin-top:1px; }
    .btn-print-go {
      display:inline-flex; align-items:center; gap:5px; flex-shrink:0;
      padding:7px 14px; border-radius:7px; border:none; cursor:pointer;
      font-family:'Sora',sans-serif; font-size:12px; font-weight:600;
      background:linear-gradient(135deg,#3b82f6,#93c5fd); color:white;
      box-shadow:0 2px 8px rgba(96,165,250,.3); transition:opacity .15s, transform .15s;
    }
    .btn-print-go:hover { opacity:.9; transform:translateY(-1px); }

    /* Single-invoice divider */
    .inv-divider {
      display:flex; align-items:center; gap:10px;
      font-size:10.5px; font-weight:700; letter-spacing:.1em;
      text-transform:uppercase; color:var(--gray-400);
      margin:4px 0;
    }
    .inv-divider::before, .inv-divider::after { content:''; flex:1; height:1px; background:var(--gray-200); }

    /* Per-invoice print button in the invoice cards */
    .inv-print-btn {
      display:inline-flex; align-items:center; gap:5px;
      padding:5px 12px; border-radius:7px; border:1.5px solid var(--gray-200);
      background:var(--gray-50); color:var(--gray-600);
      font-family:'Sora',sans-serif; font-size:11.5px; font-weight:600;
      cursor:pointer; transition:all .15s;
    }
    .inv-print-btn:hover { background:#f5f9ff; border-color:#bfdbfe; color:#3b82f6; }

    /* ════ RESPONSIVE ═══════════════ */
    @media (max-width:768px) {
      .topbar { padding:0 16px; } .date-pill { display:none; }
      .page { padding:20px 14px 100px; }
      .patient-hero { padding:20px; }
      .hero-stats { display:none; }
      .action-bar { bottom:8px; flex-direction:column; align-items:stretch; }
      .action-bar-right { justify-content:stretch; }
      .action-bar-right .btn { flex:1; justify-content:center; }
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
      <span class="brand-sub">Billing System</span>
    </div>
  </div>
  <div class="topbar-right">
    <div class="date-pill"><i class="bi bi-calendar3"></i><?= date('D, d M Y') ?></div>
    <a href="select_patient_paid_bills.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</header>

<!-- ════ PAGE ════════════════════════════ -->
<div class="page">

  <!-- Print-only header (hidden on screen) -->
  <div class="print-header" id="printHeader">
    <div class="print-header-top">
      <div>
        <div class="print-hosp-name">Angelora Hospital</div>
        <div class="print-hosp-sub">Plot 73B Cornershop Area, First Avenue Gwarinpa &nbsp;|&nbsp; 07048221888</div>
      </div>
      <div class="print-meta">
        <div><strong>Billing Statement</strong></div>
        <div id="printDateTime"></div>
      </div>
    </div>
    <div class="print-patient-row">
      <span><strong>Patient:</strong> <?= htmlspecialchars($patient['full_name']) ?></span>
      <span><strong>Patient ID:</strong> <?= $patient['patient_id'] ?></span>
      <span id="printPaidInfo"></span>
    </div>
  </div>

  <div class="breadcrumb">
    <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="bill_dashboard.php">Billing</a>
    <i class="bi bi-chevron-right"></i>
    <a href="select_patient_paid_bills.php">Patient Search</a>
    <i class="bi bi-chevron-right"></i>
    <span>View Bills</span>
  </div>

  <!-- Patient Hero -->
  <div class="patient-hero">
    <div class="patient-hero-left">
      <div class="patient-avatar"><?= $initials ?></div>
      <div>
        <div class="patient-name"><?= htmlspecialchars($patient['full_name']) ?></div>
        <div class="patient-id"><i class="bi bi-person-badge"></i> Patient ID: <?= $patient['patient_id'] ?></div>
      </div>
    </div>
    <div class="hero-stats">
      <div class="hstat">
        <div class="hstat-num"><?= count($invoices) ?></div>
        <div class="hstat-label">Invoices</div>
      </div>
      <div class="hstat">
        <div class="hstat-num"><?= $paid_count ?></div>
        <div class="hstat-label">Paid</div>
      </div>
      <div class="hstat">
        <div class="hstat-num"><?= $unpaid_count ?></div>
        <div class="hstat-label">Unpaid</div>
      </div>
      <div class="hstat">
        <div class="hstat-num">₦<?= number_format($grand_total_all, 0) ?></div>
        <div class="hstat-label">Total Billed</div>
      </div>
    </div>
  </div>

  <!-- Success alert -->
  <?php if (isset($_GET['paid']) && $_GET['paid'] == 1): ?>
    <div class="alert alert-success">
      <i class="bi bi-check-circle-fill"></i>
      Payment successful — selected invoices have been marked as paid.
    </div>
  <?php endif; ?>

  <?php if (empty($invoices)): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle-fill"></i>
      No invoices found for this patient.
    </div>
  <?php else: ?>

  <form method="post" action="" id="invoiceForm">

    <?php foreach ($invoices as $inv_no => $items):
      $total  = array_sum(array_column($items, 'total'));
      $paid   = array_sum(array_column($items, 'paid')) > 0;
    ?>

    <div class="invoice-card" data-inv="<?= htmlspecialchars($inv_no) ?>">

      <!-- Invoice header -->
      <div class="inv-header <?= $paid ? 'paid-header' : 'unpaid-header' ?>">
        <div class="inv-no-group">
          <div class="inv-icon <?= $paid ? 'paid-icon' : 'unpaid-icon' ?>">
            <i class="bi bi-<?= $paid ? 'check-circle-fill' : 'clock-fill' ?>"></i>
          </div>
          <div>
            <div class="inv-no"><i class="bi bi-receipt"></i> <?= htmlspecialchars($inv_no) ?></div>
            <div class="inv-date"><?= count($items) ?> line item<?= count($items) !== 1 ? 's' : '' ?></div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span style="font-size:13px;font-weight:700;color:var(--gray-700);">
            ₦<?= number_format($total, 2) ?>
          </span>
          <span class="status-pill <?= $paid ? 'pill-paid' : 'pill-unpaid' ?>">
            <i class="bi bi-<?= $paid ? 'check-circle-fill' : 'hourglass-split' ?>"></i>
            <?= $paid ? 'Paid' : 'Unpaid' ?>
          </span>
          <button type="button" class="inv-print-btn no-print" data-inv="<?= htmlspecialchars($inv_no) ?>">
            <i class="bi bi-printer"></i> Print this
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>Service</th>
              <th>Source</th>
              <th>Qty</th>
              <th class="text-end">Unit Cost (₦)</th>
              <th class="text-end">Total (₦)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['service_name']) ?></td>
              <td><span class="source-tag"><?= ucfirst($b['source_table']) ?></span></td>
              <td><?= $b['quantity'] ?></td>
              <td class="text-end"><?= number_format($b['cost'], 2) ?></td>
              <td class="text-end"><?= number_format($b['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
              <td colspan="4" class="text-end">Invoice Total</td>
              <td class="text-end">₦<?= number_format($total, 2) ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payment checkbox (unpaid only) -->
      <?php if (!$paid): ?>
      <div class="select-wrap">
        <input class="custom-checkbox select-invoice" type="checkbox"
               name="selected_invoices[]" id="inv_<?= $inv_no ?>"
               value="<?= htmlspecialchars($inv_no) ?>" data-total="<?= $total ?>">
        <label class="select-label" for="inv_<?= $inv_no ?>">
          Select for payment
        </label>
      </div>
      <?php endif; ?>

      <!-- Print-select checkbox (all invoices, shown only in screen context via no-print wrapper suppression) -->
      <div class="select-wrap no-print" style="background:var(--blue-50,#eff6ff);border-top:1px solid #dbeafe;">
        <input class="custom-checkbox print-select" type="checkbox"
               id="psel_<?= $inv_no ?>"
               data-inv="<?= htmlspecialchars($inv_no) ?>">
        <label class="select-label" for="psel_<?= $inv_no ?>" style="color:#3b82f6;">
          <i class="bi bi-printer" style="margin-right:4px;"></i> Select for printing
        </label>
      </div>

    </div>
    <?php endforeach; ?>

    <!-- ════ ACTION BAR ════════════════════ -->
    <div class="action-bar no-print">
      <div class="action-bar-left">
        <div class="selected-total">
          <span style="color:var(--gray-500);font-size:13px;">Selected total:</span>
          <span class="selected-total-amt" id="totalAmountDisplay">₦0.00</span>
        </div>
      </div>
      <div class="action-bar-right">
        <button type="submit" name="mark_paid_invoices" class="btn btn-amber"
                onclick="return confirm('Mark selected invoices as paid?')">
          <i class="bi bi-check2-circle"></i> Mark Paid
        </button>
        <button type="button" id="paystackButton" class="btn btn-success">
          <i class="bi bi-credit-card-fill"></i> Pay via Paystack
        </button>
        <button type="button" id="printButton" class="btn btn-gray" onclick="openPrintModal()">
          <i class="bi bi-printer-fill"></i> Print
        </button>
        <button type="button" id="downloadButton" class="btn btn-primary">
          <i class="bi bi-download"></i> Download PDF
        </button>
      </div>
    </div>

  </form>
  <?php endif; ?>

</div>

<!-- ════ PRINT OPTIONS MODAL ════════════════ -->
<div class="modal-backdrop" id="printModal">
  <div class="modal-box">
    <div class="modal-head">
      <h5><i class="bi bi-printer-fill"></i> Print Options</h5>
      <button class="modal-close" onclick="closePrintModal()"><i class="bi bi-x"></i></button>
    </div>
    <div class="modal-body-inner">
      <p class="modal-note">Choose how you'd like to print the invoices for this patient.</p>

      <!-- Print all -->
      <div class="print-option">
        <div class="print-option-info">
          <div class="print-option-icon c-all"><i class="bi bi-files"></i></div>
          <div>
            <div class="print-option-name">Print All Invoices</div>
            <div class="print-option-sub">Prints every invoice for this patient on one sheet</div>
          </div>
        </div>
        <button class="btn-print-go" onclick="printAll()"><i class="bi bi-printer"></i> Print All</button>
      </div>

      <!-- Print selected (payment checkboxes) -->
      <div class="print-option">
        <div class="print-option-info">
          <div class="print-option-icon c-paid"><i class="bi bi-check2-square"></i></div>
          <div>
            <div class="print-option-name">Print Selected for Payment</div>
            <div class="print-option-sub">Prints invoices where you ticked the blue "Select for printing" checkbox</div>
          </div>
        </div>
        <button class="btn-print-go" onclick="printSelected()"><i class="bi bi-printer"></i> Print Selected</button>
      </div>

      <div class="inv-divider">or print individually</div>

      <!-- Per-invoice options built by JS -->
      <div id="singleInvOptions"></div>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {

  // ── Set print date/time on load ──────────
  const now = new Date();
  const opts = { weekday:'short', day:'numeric', month:'short', year:'numeric' };
  const dateStr = now.toLocaleDateString('en-NG', opts);
  const timeStr = now.toLocaleTimeString('en-NG', { hour:'2-digit', minute:'2-digit' });
  $('#printDateTime').text('Printed: ' + dateStr + ' at ' + timeStr);

  // ── Payment total tracker ───────────────
  function updateTotal() {
    let total = 0;
    $('.select-invoice:checked').each(function () {
      total += parseFloat($(this).data('total'));
    });
    $('#totalAmountDisplay').text('\u20a6' + total.toFixed(2));
    return total;
  }
  $('.select-invoice').on('change', updateTotal);

  // ── Paystack ─────────────────────────────
  $('#paystackButton').on('click', function () {
    const selected = $('.select-invoice:checked').map(function () { return $(this).val(); }).get();
    if (!selected.length) { alert('Please select at least one unpaid invoice.'); return; }
    const total = updateTotal();
    const amountKobo = Math.round(total * 100);
    const patientId  = <?= $patient_id ?>;
    $('<form action="paystack_payment.php" method="post">' +
      '<input type="hidden" name="patient_id" value="' + patientId + '">' +
      '<input type="hidden" name="invoice_no" value="' + selected.join(',') + '">' +
      '<input type="hidden" name="amount" value="' + amountKobo + '">' +
      '</form>').appendTo('body').submit();
  });

  // ── Download PDF ──────────────────────────
  $('#downloadButton').on('click', function () {
    const selected = $('.select-invoice:checked').map(function () { return $(this).val(); }).get();
    const patientId  = <?= $patient_id ?>;
    const invoicesParam = selected.length ? selected.join(',') : 'all';
    window.open('download_invoice.php?patient_id=' + patientId + '&invoices=' + invoicesParam);
  });

  // ── Per-invoice "Print this" button ───────
  $(document).on('click', '.inv-print-btn', function () {
    const inv = $(this).data('inv');
    setPrintPaidInfo(inv);
    printSingle(inv);
  });

  // ── Build single-invoice list in modal ────
  function buildSingleOptions() {
    let html = '';
    $('.invoice-card').each(function () {
      const inv    = $(this).data('inv');
      const title  = $(this).find('.inv-no').text().trim();
      const isPaid = $(this).find('.pill-paid').length > 0;
      const pillCls  = isPaid ? 'pill-paid' : 'pill-unpaid';
      const pillText = isPaid ? 'Paid' : 'Unpaid';
      html += `
        <div class="print-option">
          <div class="print-option-info">
            <div class="print-option-icon c-one"><i class="bi bi-receipt"></i></div>
            <div>
              <div class="print-option-name" style="font-size:12px;">${title}</div>
              <div class="print-option-sub">
                <span class="status-pill ${pillCls}" style="font-size:10px;padding:2px 8px;">${pillText}</span>
              </div>
            </div>
          </div>
          <button class="btn-print-go" onclick="setPrintPaidInfo('${inv}');printSingle('${inv}');closePrintModal()">
            <i class="bi bi-printer"></i> Print
          </button>
        </div>`;
    });
    $('#singleInvOptions').html(html);
  }

  buildSingleOptions();

});

// ── Helpers ─────────────────────────────────
function nowString() {
  const n = new Date();
  const d = n.toLocaleDateString('en-NG', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
  const t = n.toLocaleTimeString('en-NG', { hour:'2-digit', minute:'2-digit' });
  return d + ' at ' + t;
}

// Update the "Paid/Printed" info line in the print header
function setPrintPaidInfo(invLabel) {
  const el = document.getElementById('printPaidInfo');
  if (el) el.innerHTML = '<strong>Printed:</strong> ' + nowString();
}

// ── Modal open/close ────────────────────────
function openPrintModal()  { document.getElementById('printModal').classList.add('open'); }
function closePrintModal() { document.getElementById('printModal').classList.remove('open'); }
document.getElementById('printModal').addEventListener('click', function(e) {
  if (e.target === this) closePrintModal();
});

// ── Print ALL invoices ──────────────────────
function printAll() {
  closePrintModal();
  document.getElementById('printPaidInfo').innerHTML = '<strong>Printed:</strong> ' + nowString();
  document.querySelectorAll('.invoice-card').forEach(c => c.classList.remove('printing'));
  document.body.classList.add('print-all');
  window.print();
  document.body.classList.remove('print-all');
}

// ── Print SELECTED (print-select checkboxes) ─
function printSelected() {
  // Use dedicated print-select checkboxes (one on every card, paid or unpaid)
  const checked = document.querySelectorAll('.print-select:checked');
  if (!checked.length) {
    closePrintModal();
    alert('No invoices are ticked for printing. Please tick the "Print this" checkbox on at least one invoice.');
    return;
  }
  closePrintModal();
  document.getElementById('printPaidInfo').innerHTML = '<strong>Printed:</strong> ' + nowString();
  document.body.classList.remove('print-all');
  document.querySelectorAll('.invoice-card').forEach(c => c.classList.remove('printing'));
  checked.forEach(chk => {
    const inv  = chk.dataset.inv;
    const card = document.querySelector('.invoice-card[data-inv="' + inv + '"]');
    if (card) card.classList.add('printing');
  });
  window.print();
  document.querySelectorAll('.invoice-card').forEach(c => c.classList.remove('printing'));
}

// ── Print a SINGLE invoice ───────────────────
function printSingle(inv) {
  document.getElementById('printPaidInfo').innerHTML = '<strong>Printed:</strong> ' + nowString();
  document.body.classList.remove('print-all');
  document.querySelectorAll('.invoice-card').forEach(c => c.classList.remove('printing'));
  const card = document.querySelector('.invoice-card[data-inv="' + inv + '"]');
  if (card) card.classList.add('printing');
  window.print();
  document.querySelectorAll('.invoice-card').forEach(c => c.classList.remove('printing'));
}
</script>
</body>
</html>