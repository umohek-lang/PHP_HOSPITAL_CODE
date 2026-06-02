<?php
include '../db.php';

$message     = '';
$msg_type    = '';

if (isset($_POST['bill'])) {
    $patient_id = $_POST['patient_id'];
    $invoice_no = 'INV' . date('YmdHis');
    $services_post = $_POST['services'] ?? [];

    if (empty($services_post)) {
        $message  = "No services selected. Please add at least one service.";
        $msg_type = "error";
    } else {
        try {
            $pdo->beginTransaction();
            foreach ($services_post as $srv) {
                list($service_id, $source) = explode("|", $srv['id']);
                $quantity = max(1, (int)$srv['quantity']);
                $cost     = floatval($srv['unit_cost']);
                $total    = $quantity * $cost;

                $serviceStmt = $pdo->prepare("SELECT service_name FROM $source WHERE id = ?");
                $serviceStmt->execute([$service_id]);
                $service = $serviceStmt->fetch();
                if (!$service) continue;

                $pdo->prepare("INSERT INTO hos_bills (patient_id, service_id, service_name, quantity, cost, total, source_table, invoice_no, paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$patient_id, $service_id, $service['service_name'], $quantity, $cost, $total, $source, $invoice_no, 0]);
            }
            $pdo->commit();
            $message     = "Bill generated successfully! Invoice No: <strong>$invoice_no</strong>";
            $msg_type    = "success";
            $invoice_link = "view_bill.php?patient_id=$patient_id&invoice_no=$invoice_no";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message  = "Error: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}

$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

function fetchServices($pdo) {
    return $pdo->query("
        SELECT id, service_name, cost, 'service_roles' AS source FROM service_roles
        UNION
        SELECT id, service_name, cost, 'bill_services' AS source FROM bill_services
    ")->fetchAll(PDO::FETCH_ASSOC);
}
$services = fetchServices($pdo);
$patient_selected = !empty($_POST['patient_id']);
$selected_patient_id = $_POST['patient_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Generate Bill — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-800:  #1e40af;
      --blue-700:  #1d4ed8;
      --blue-600:  #2563eb;
      --blue-500:  #3b82f6;
      --blue-400:  #60a5fa;
      --blue-300:  #93c5fd;
      --blue-200:  #bfdbfe;
      --blue-100:  #dbeafe;
      --blue-50:   #eff6ff;
      --blue-glow: rgba(37,99,235,.12);

      --white:     #ffffff;
      --gray-50:   #f8fafc;
      --gray-100:  #f1f5f9;
      --gray-200:  #e2e8f0;
      --gray-300:  #cbd5e1;
      --gray-400:  #94a3b8;
      --gray-500:  #64748b;
      --gray-600:  #475569;
      --gray-700:  #334155;
      --gray-800:  #1e293b;
      --gray-900:  #0f172a;

      --green:     #16a34a; --green-bg: #dcfce7; --green-100: #bbf7d0;
      --amber:     #d97706; --amber-bg: #fef3c7; --amber-100: #fde68a;
      --red:       #dc2626; --red-bg:   #fee2e2; --red-100:   #fecaca;

      --radius:    12px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.04);
      --shadow:    0 4px 16px rgba(37,99,235,.09), 0 1px 4px rgba(0,0,0,.05);
      --shadow-lg: 0 12px 36px rgba(37,99,235,.14), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body { min-height: 100vh; font-family: 'Sora', sans-serif; background: var(--gray-50); color: var(--gray-800); }
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

    /* ════ TOP BAR ═══════════════════ */
    .topbar {
      position: sticky; top: 0; z-index: 100;
      height: 64px; background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: 0 1px 8px rgba(0,0,0,.06);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 36px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .brand-mark i { font-size: 17px; color: white; }
    .brand-text { display: flex; flex-direction: column; gap: 1px; }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 17px; color: var(--gray-900); line-height: 1; }
    .brand-sub  { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue-600); line-height: 1; }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .date-pill {
      display: flex; align-items: center; gap: 7px;
      padding: 6px 14px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      font-size: 12px; color: var(--blue-700); font-weight: 500;
    }
    .date-pill i { color: var(--blue-500); }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 12.5px; font-weight: 500; text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-600); }

    /* ════ PAGE ══════════════════════ */
    .page { max-width: 1100px; margin: 0 auto; padding: 36px 28px 72px; }

    /* ════ PAGE HEADER ═══════════════ */
    .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--gray-400); margin-bottom: 10px; }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }
    .page-title { font-family: 'Instrument Serif', serif; font-size: clamp(1.6rem,3vw,2.2rem); font-weight: 400; color: var(--gray-900); margin-bottom: 5px; }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-bottom: 28px; }

    /* ════ ALERT ══════════════════════ */
    .alert {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px 18px; border-radius: 10px; margin-bottom: 22px;
      font-size: 13.5px; line-height: 1.5;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    .alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .alert-success { background: var(--green-bg); border: 1px solid var(--green-100); color: var(--green); }
    .alert-error   { background: var(--red-bg);   border: 1px solid var(--red-100);   color: var(--red);   }
    .alert-actions { margin-top: 10px; }
    .btn-invoice {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 8px;
      background: var(--green); color: white;
      font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 600;
      text-decoration: none; transition: opacity .18s;
    }
    .btn-invoice:hover { opacity: .88; color: white; }

    /* ════ CARD ══════════════════════ */
    .card {
      background: var(--white); border: 1.5px solid var(--gray-200);
      border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden;
      margin-bottom: 20px;
    }
    .card-header {
      padding: 14px 22px; border-bottom: 1px solid var(--gray-100);
      background: linear-gradient(135deg, var(--blue-800), var(--blue-600));
      display: flex; align-items: center; gap: 10px;
    }
    .card-header i { font-size: 17px; color: rgba(255,255,255,.85); }
    .card-header-text { display: flex; flex-direction: column; gap: 1px; }
    .card-title { font-size: 14px; font-weight: 700; color: white; }
    .card-sub   { font-size: 11.5px; color: rgba(255,255,255,.65); }
    .card-body  { padding: 24px 22px; }

    /* ════ FIELD ═════════════════════ */
    .field { display: flex; flex-direction: column; gap: 7px; }
    .field label { font-size: 11.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--gray-500); }

    /* ════ SELECT2 WHITE THEME ════════ */
    .select2-container--default .select2-selection--single {
      background: var(--gray-50) !important; border: 1.5px solid var(--gray-200) !important;
      border-radius: 9px !important; height: 46px !important;
      display: flex !important; align-items: center !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default .select2-selection--single:focus {
      border-color: var(--blue-400) !important; box-shadow: 0 0 0 3px var(--blue-glow) !important; background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--gray-800) !important; font-family: 'Sora', sans-serif !important;
      font-size: 13.5px !important; font-weight: 500 !important;
      line-height: 46px !important; padding-left: 14px !important;
    }
    .select2-container--default .select2-selection__placeholder { color: var(--gray-400) !important; font-weight: 400 !important; }
    .select2-container--default .select2-selection__arrow { height: 46px !important; right: 12px !important; }
    .select2-dropdown {
      background: var(--white) !important; border: 1.5px solid var(--gray-200) !important;
      border-radius: 10px !important; box-shadow: var(--shadow-lg) !important;
      font-family: 'Sora', sans-serif !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      background: var(--gray-50) !important; border: 1px solid var(--gray-200) !important;
      border-radius: 7px !important; color: var(--gray-800) !important;
      font-family: 'Sora', sans-serif !important; font-size: 13px !important; padding: 8px 12px !important;
    }
    .select2-results__option { color: var(--gray-700) !important; font-size: 13px !important; padding: 9px 14px !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--blue-50) !important; color: var(--blue-700) !important; }

    /* ════ SERVICES TABLE ════════════ */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 640px; }
    thead th {
      padding: 10px 14px; text-align: left;
      font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-500); background: var(--gray-50); border-bottom: 1px solid var(--gray-200);
      white-space: nowrap;
    }
    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .1s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--blue-50); }
    td { padding: 10px 14px; vertical-align: middle; }

    /* Inline inputs in table */
    .tbl-input {
      width: 100%; padding: 8px 10px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 7px; color: var(--gray-800);
      font-family: 'Sora', sans-serif; font-size: 13px;
      outline: none; transition: border-color .16s, box-shadow .16s;
    }
    .tbl-input:focus { border-color: var(--blue-400); background: var(--white); box-shadow: 0 0 0 2px var(--blue-glow); }
    .tbl-input[readonly] { background: var(--blue-50); border-color: var(--blue-100); color: var(--blue-700); font-weight: 600; cursor: default; }

    .tbl-select {
      width: 100%; padding: 8px 10px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 7px; color: var(--gray-800);
      font-family: 'Sora', sans-serif; font-size: 13px;
      outline: none; appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 9px center; padding-right: 28px;
      transition: border-color .16s, box-shadow .16s;
    }
    .tbl-select:focus { border-color: var(--blue-400); background-color: var(--white); box-shadow: 0 0 0 2px var(--blue-glow); }

    /* Row action buttons */
    .btn-edit, .btn-remove {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 11px; border-radius: 7px;
      font-family: 'Sora', sans-serif; font-size: 11.5px; font-weight: 600;
      border: none; cursor: pointer; transition: all .16s; white-space: nowrap;
    }
    .btn-edit   { background: var(--blue-50); border: 1px solid var(--blue-200); color: var(--blue-700); }
    .btn-edit:hover   { background: var(--blue-100); }
    .btn-remove { background: var(--red-bg); border: 1px solid var(--red-100); color: var(--red); }
    .btn-remove:hover { background: var(--red-100); }

    /* ════ GRAND TOTAL BAR ════════════ */
    .total-bar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 22px; background: var(--blue-50);
      border-top: 1px solid var(--blue-100);
      flex-wrap: wrap; gap: 14px;
    }
    .add-service-btn {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 18px; border-radius: 8px;
      background: var(--white); border: 1.5px solid var(--gray-200);
      color: var(--gray-700); font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; cursor: pointer;
      box-shadow: var(--shadow-sm); transition: all .18s;
    }
    .add-service-btn:hover { background: var(--blue-50); border-color: var(--blue-300); color: var(--blue-600); }

    .grand-total-display {
      display: flex; align-items: center; gap: 10px;
      font-size: 14px; font-weight: 700; color: var(--gray-700);
    }
    .grand-total-amt {
      font-family: 'Instrument Serif', serif;
      font-size: 26px; color: var(--blue-700); line-height: 1;
    }

    /* ════ FORM FOOTER ═══════════════ */
    .form-footer {
      padding: 18px 22px; border-top: 1px solid var(--gray-100);
      background: var(--gray-50);
      display: flex; align-items: center; justify-content: space-between;
      gap: 16px; flex-wrap: wrap;
    }
    .form-footer-note { font-size: 12px; color: var(--gray-400); display: flex; align-items: center; gap: 6px; }
    .form-footer-note i { color: var(--blue-400); }

    .btn-generate {
      display: flex; align-items: center; gap: 8px;
      padding: 12px 28px; border-radius: 9px; border: none;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      color: white; font-family: 'Sora', sans-serif;
      font-size: 14px; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(37,99,235,.3);
      transition: opacity .18s, transform .15s, box-shadow .18s;
      position: relative; overflow: hidden;
    }
    .btn-generate::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,.1) 0%,transparent 60%); }
    .btn-generate:hover { opacity:.95; transform:translateY(-1px); box-shadow:0 8px 24px rgba(37,99,235,.4); }

    /* ════ IDLE STATE ════════════════ */
    .idle-state {
      display: flex; flex-direction: column; align-items: center;
      gap: 10px; padding: 48px 24px; text-align: center;
    }
    .idle-icon {
      width: 64px; height: 64px; border-radius: 50%;
      background: var(--blue-50); border: 2px solid var(--blue-100);
      display: flex; align-items: center; justify-content: center;
    }
    .idle-icon i { font-size: 28px; color: var(--blue-400); }
    .idle-title { font-size: 15px; font-weight: 700; color: var(--gray-700); }
    .idle-sub   { font-size: 13px; color: var(--gray-400); }

    /* ════ MODAL ═════════════════════ */
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 400; display: none; align-items: center; justify-content: center; }
    .modal-backdrop.open { display: flex; }
    .modal-box {
      background: var(--white); border-radius: 16px;
      width: 100%; max-width: 460px; margin: 20px;
      overflow: hidden; box-shadow: var(--shadow-lg);
      animation: popIn .25s cubic-bezier(.16,1,.3,1);
    }
    @keyframes popIn { from { opacity:0; transform:translateY(20px) scale(.97); } to { opacity:1; transform:none; } }
    .modal-head {
      padding: 18px 24px; border-bottom: 1px solid var(--gray-100);
      background: linear-gradient(135deg, var(--blue-800), var(--blue-600));
      display: flex; align-items: center; justify-content: space-between;
    }
    .modal-head h5 { font-size: 15px; font-weight: 700; color: white; display: flex; align-items: center; gap: 8px; }
    .modal-close {
      width: 28px; height: 28px; border-radius: 7px;
      background: rgba(255,255,255,.15); border: none; color: white;
      font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background .15s;
    }
    .modal-close:hover { background: rgba(255,255,255,.25); }
    .modal-body-inner { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .modal-field { display: flex; flex-direction: column; gap: 6px; }
    .modal-field label { font-size: 11.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--gray-500); }
    .modal-input {
      width: 100%; padding: 11px 14px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; color: var(--gray-800);
      font-family: 'Sora', sans-serif; font-size: 13.5px;
      outline: none; transition: border-color .16s, box-shadow .16s;
    }
    .modal-input:focus { border-color: var(--blue-400); background: var(--white); box-shadow: 0 0 0 3px var(--blue-glow); }
    .modal-footer-btns {
      padding: 16px 24px; border-top: 1px solid var(--gray-100);
      display: flex; gap: 10px; justify-content: flex-end;
    }
    .btn-modal-cancel {
      padding: 9px 18px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-modal-cancel:hover { background: var(--gray-200); }
    .btn-modal-save {
      padding: 9px 20px; border-radius: 8px; border: none;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      color: white; font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 700; cursor: pointer;
      box-shadow: 0 3px 10px rgba(37,99,235,.25); transition: opacity .15s;
    }
    .btn-modal-save:hover { opacity: .9; }

    /* ════ RESPONSIVE ══════════════════ */
    @media (max-width: 768px) {
      .topbar { padding: 0 16px; }
      .date-pill { display: none; }
      .page { padding: 20px 14px 52px; }
      .card-body { padding: 16px; }
      .form-footer { flex-direction: column; align-items: stretch; }
      .btn-generate { justify-content: center; }
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
    <a href="bill_dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</header>

<!-- ════ PAGE ════════════════════════════ -->
<div class="page">

  <div class="breadcrumb">
    <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="bill_dashboard.php">Billing</a>
    <i class="bi bi-chevron-right"></i>
    <span>Bill Patient</span>
  </div>
  <h1 class="page-title">Generate <em>Multi-Service Bill</em></h1>
  <p class="page-sub">Select a patient, add services, and generate an invoice.</p>

  <!-- Alert -->
  <?php if ($message): ?>
    <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-error' ?>">
      <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
      <div>
        <?= $message ?>
        <?php if ($msg_type === 'success' && isset($invoice_link)): ?>
          <div class="alert-actions">
            <a href="<?= $invoice_link ?>" class="btn-invoice">
              <i class="bi bi-receipt"></i> View Invoice
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" id="billingForm">

    <!-- Patient Select Card -->
    <div class="card">
      <div class="card-header">
        <i class="bi bi-person-badge-fill"></i>
        <div class="card-header-text">
          <div class="card-title">Select Patient</div>
          <div class="card-sub">Choose a patient to begin billing</div>
        </div>
      </div>
      <div class="card-body">
        <div class="field">
          <label>Patient</label>
          <select name="patient_id" id="patient_id" required onchange="this.form.submit()">
            <option value="">— Search and select a patient —</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?= $p['patient_id'] ?>"
                <?= $selected_patient_id == $p['patient_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['full_name']) ?> (ID: <?= $p['patient_id'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Services Card -->
    <div class="card">
      <div class="card-header">
        <i class="bi bi-list-check"></i>
        <div class="card-header-text">
          <div class="card-title">Services</div>
          <div class="card-sub">Add one or more billable services</div>
        </div>
      </div>

      <?php if ($patient_selected): ?>
        <div class="tbl-wrap">
          <table id="servicesTable">
            <thead>
              <tr>
                <th style="min-width:260px;">Service</th>
                <th style="width:100px;">Qty</th>
                <th style="width:140px;">Unit Cost (₦)</th>
                <th style="width:130px;">Total (₦)</th>
                <th style="width:150px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr class="service-row">
                <td>
                  <select class="tbl-select service-select" name="services[0][id]" required>
                    <option value="">— Select service —</option>
                    <?php foreach ($services as $s): ?>
                      <option value="<?= $s['id'] ?>|<?= $s['source'] ?>" data-cost="<?= $s['cost'] ?>">
                        <?= htmlspecialchars($s['service_name']) ?> — ₦<?= number_format($s['cost'], 2) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td><input type="number" name="services[0][quantity]" class="tbl-input quantity" value="1" min="1"></td>
                <td><input type="number" name="services[0][unit_cost]" class="tbl-input unit-cost" step="0.01"></td>
                <td><input type="text" class="tbl-input total" readonly placeholder="0.00"></td>
                <td style="display:flex;gap:6px;flex-wrap:wrap;">
                  <button type="button" class="btn-edit edit-service"><i class="bi bi-pencil"></i> Edit</button>
                  <button type="button" class="btn-remove remove-row"><i class="bi bi-trash3"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="total-bar">
          <button type="button" id="addService" class="add-service-btn">
            <i class="bi bi-plus-circle"></i> Add Another Service
          </button>
          <div class="grand-total-display">
            Grand Total &nbsp; ₦<span class="grand-total-amt" id="grandTotal">0.00</span>
          </div>
        </div>

        <div class="form-footer">
          <span class="form-footer-note">
            <i class="bi bi-shield-check"></i>
            Invoice will be created immediately upon submission.
          </span>
          <button type="submit" name="bill" class="btn-generate">
            <i class="bi bi-receipt-cutoff"></i> Generate Invoice
          </button>
        </div>

      <?php else: ?>
        <div class="idle-state">
          <div class="idle-icon"><i class="bi bi-receipt"></i></div>
          <div class="idle-title">No patient selected</div>
          <div class="idle-sub">Select a patient above to start adding services and generating their bill.</div>
        </div>
      <?php endif; ?>

    </div>
  </form>
</div>

<!-- ════ EDIT SERVICE MODAL ══════════════ -->
<div class="modal-backdrop" id="editModal">
  <div class="modal-box">
    <div class="modal-head">
      <h5><i class="bi bi-pencil-square"></i> Edit Service</h5>
      <button class="modal-close" id="closeModal"><i class="bi bi-x"></i></button>
    </div>
    <form id="editServiceForm">
      <div class="modal-body-inner">
        <input type="hidden" name="id"     id="editServiceId">
        <input type="hidden" name="source" id="editServiceSource">
        <div class="modal-field">
          <label>Service Name</label>
          <input type="text" name="service_name" id="editServiceName" class="modal-input" required>
        </div>
        <div class="modal-field">
          <label>Cost (₦)</label>
          <input type="number" step="0.01" name="cost" id="editServiceCost" class="modal-input" required>
        </div>
      </div>
      <div class="modal-footer-btns">
        <button type="button" class="btn-modal-cancel" id="cancelModal">Cancel</button>
        <button type="submit" class="btn-modal-save"><i class="bi bi-floppy-fill"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {

  // Patient dropdown with Select2
  $('#patient_id').select2({ placeholder: '— Search and select a patient —', allowClear: true, width: '100%' });

  // ── Row total calculation ───────────────
  function updateRowTotal(row) {
    const qty  = parseFloat(row.find('.quantity').val())  || 0;
    const cost = parseFloat(row.find('.unit-cost').val()) || 0;
    row.find('.total').val((qty * cost).toFixed(2));
    updateGrandTotal();
  }

  function updateGrandTotal() {
    let grand = 0;
    $('#servicesTable .service-row').each(function () {
      grand += parseFloat($(this).find('.total').val()) || 0;
    });
    $('#grandTotal').text(grand.toFixed(2));
  }

  // Service selected → fill unit cost
  $(document).on('change', '.service-select', function () {
    const row  = $(this).closest('tr');
    const cost = $(this).find(':selected').data('cost') || 0;
    row.find('.unit-cost').val(parseFloat(cost).toFixed(2));
    updateRowTotal(row);
  });

  $(document).on('input', '.quantity, .unit-cost', function () {
    updateRowTotal($(this).closest('tr'));
  });

  // ── Add row ─────────────────────────────
  $('#addService').on('click', function () {
    const index  = $('#servicesTable .service-row').length;
    const newRow = $('#servicesTable .service-row:first').clone();
    newRow.find('select').attr('name', 'services['+index+'][id]').val('');
    newRow.find('.quantity').attr('name',  'services['+index+'][quantity]').val(1);
    newRow.find('.unit-cost').attr('name', 'services['+index+'][unit_cost]').val('');
    newRow.find('.total').val('');
    $('#servicesTable tbody').append(newRow);
  });

  // ── Remove row ──────────────────────────
  $(document).on('click', '.remove-row', function () {
    if ($('#servicesTable .service-row').length > 1) {
      $(this).closest('tr').remove();
      updateGrandTotal();
    } else {
      alert('At least one service is required.');
    }
  });

  // ── Edit modal ──────────────────────────
  $(document).on('click', '.edit-service', function () {
    const row     = $(this).closest('tr');
    const service = row.find('.service-select option:selected');
    if (!service.val()) { alert('Please select a service first.'); return; }
    const [id, source] = service.val().split('|');
    $('#editServiceId').val(id);
    $('#editServiceSource').val(source);
    $('#editServiceName').val(service.text().split('—')[0].trim());
    $('#editServiceCost').val(row.find('.unit-cost').val());
    $('#editModal').addClass('open');
  });

  $('#closeModal, #cancelModal').on('click', function () { $('#editModal').removeClass('open'); });
  $('#editModal').on('click', function (e) { if (e.target === this) $(this).removeClass('open'); });

  // ── Save edit via AJAX ──────────────────
  $('#editServiceForm').submit(function (e) {
    e.preventDefault();
    $.post('update_service.php', $(this).serialize(), function (res) {
      if (res.success) {
        $.get('fetch_services1.php', function (data) {
          $('.service-select').each(function () {
            const current = $(this).val();
            $(this).html(data).val(current).trigger('change');
          });
        });
        $('#editModal').removeClass('open');
      } else {
        alert('Error: ' + res.message);
      }
    }, 'json');
  });

});
</script>
</body>
</html>