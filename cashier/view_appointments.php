<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../db.php';

$limit  = 10;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date   = isset($_GET['date'])   ? trim($_GET['date'])   : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$query = "
    SELECT a.*, p.full_name AS patient_name, u.full_name AS doctor_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users u ON a.doctor_id = u.user_id
    WHERE u.role_id = 2
";

$conditions = [];
$params     = [];

if ($search !== '') { $conditions[] = "p.full_name LIKE :search"; $params[':search'] = "%$search%"; }
if ($date   !== '') { $conditions[] = "a.appointment_date = :date";  $params[':date']   = $date;   }
if ($status !== '') { $conditions[] = "a.status = :status";          $params[':status'] = $status; }

if (!empty($conditions)) { $query .= " AND " . implode(" AND ", $conditions); }

$countStmt = $pdo->prepare(str_replace("a.*, p.full_name AS patient_name, u.full_name AS doctor_name", "COUNT(*)", $query));
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages  = (int)ceil($total / $limit);

// Build pagination URL helper
function pgUrl($p, $search, $date, $status) {
    return '?page=' . $p . '&search=' . urlencode($search) . '&date=' . urlencode($date) . '&status=' . urlencode($status);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Appointments — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      /* Blues */
      --blue-900:  #1e3a5f;
      --blue-800:  #1e40af;
      --blue-700:  #1d4ed8;
      --blue-600:  #2563eb;
      --blue-500:  #3b82f6;
      --blue-400:  #60a5fa;
      --blue-300:  #93c5fd;
      --blue-100:  #dbeafe;
      --blue-50:   #eff6ff;
      --blue-glow: rgba(37,99,235,.12);

      /* Grays / whites */
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

      /* Status */
      --green:     #16a34a;
      --green-bg:  #dcfce7;
      --amber:     #d97706;
      --amber-bg:  #fef3c7;
      --red:       #dc2626;
      --red-bg:    #fee2e2;
      --slate:     #64748b;
      --slate-bg:  #f1f5f9;

      --radius: 12px;
      --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
      --shadow-md: 0 4px 20px rgba(37,99,235,.1), 0 1px 4px rgba(0,0,0,.06);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Sora', sans-serif;
      background: var(--gray-50);
      color: var(--gray-800);
    }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

    /* ════ TOP BAR ═══════════════════ */
    .topbar {
      position: sticky; top: 0; z-index: 100;
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      height: 62px;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 32px;
      box-shadow: 0 1px 8px rgba(0,0,0,.06);
    }

    .topbar-brand { display: flex; align-items: center; gap: 11px; }

    .brand-mark {
      width: 34px; height: 34px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 2px 8px rgba(37,99,235,.3);
    }
    .brand-mark i { font-size: 16px; color: white; }

    .brand-text { display: flex; flex-direction: column; gap: 1px; }
    .brand-name {
      font-family: 'Instrument Serif', serif;
      font-size: 16px; color: var(--gray-900); line-height: 1;
    }
    .brand-sub {
      font-size: 10px; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--blue-600); line-height: 1;
    }

    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .topbar-date {
      font-size: 12px; color: var(--gray-500);
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 20px; padding: 5px 12px;
      display: flex; align-items: center; gap: 6px;
    }
    .topbar-date i { color: var(--blue-500); font-size: 12px; }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 12.5px; font-weight: 500; text-decoration: none;
      transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-300); color: var(--blue-600); }

    /* ════ PAGE ══════════════════════ */
    .page { max-width: 1200px; margin: 0 auto; padding: 36px 28px 64px; }

    /* ════ PAGE HEADER ═══════════════ */
    .page-header { margin-bottom: 28px; }
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--gray-400); margin-bottom: 10px;
    }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }

    .header-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .page-title {
      font-family: 'Instrument Serif', serif;
      font-size: clamp(1.6rem, 3vw, 2.2rem);
      font-weight: 400; color: var(--gray-900);
    }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-top: 5px; }

    /* Summary chips */
    .summary-chips { display: flex; gap: 10px; flex-wrap: wrap; }
    .summary-chip {
      display: flex; align-items: center; gap: 8px;
      padding: 8px 14px;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 10px; box-shadow: var(--shadow);
    }
    .chip-icon {
      width: 28px; height: 28px; border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
    }
    .chip-icon.blue   { background: var(--blue-50);  } .chip-icon.blue i   { color: var(--blue-600); font-size: 13px; }
    .chip-icon.green  { background: var(--green-bg); } .chip-icon.green i  { color: var(--green);    font-size: 13px; }
    .chip-icon.amber  { background: var(--amber-bg); } .chip-icon.amber i  { color: var(--amber);    font-size: 13px; }
    .chip-num   { font-family: 'Instrument Serif', serif; font-size: 20px; color: var(--gray-900); line-height: 1; }
    .chip-label { font-size: 10.5px; color: var(--gray-500); }

    /* ════ FILTER CARD ═══════════════ */
    .filter-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); padding: 20px 24px;
      margin-bottom: 20px; box-shadow: var(--shadow);
    }

    .filter-label {
      font-size: 10.5px; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--gray-400);
      margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
    }
    .filter-label i { color: var(--blue-500); }

    .filter-row { display: grid; grid-template-columns: 2fr 1.5fr 1.5fr auto auto; gap: 12px; align-items: end; }

    .f-field { display: flex; flex-direction: column; gap: 6px; }
    .f-field label {
      font-size: 11px; font-weight: 600; letter-spacing: .05em;
      text-transform: uppercase; color: var(--gray-500);
    }

    .f-input-wrap { position: relative; }
    .f-icon {
      position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: 14px; pointer-events: none;
    }

    input[type="text"], input[type="date"], select {
      width: 100%; padding: 9px 12px 9px 34px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 8px; color: var(--gray-800);
      font-family: 'Sora', sans-serif; font-size: 13px;
      outline: none; appearance: none;
      transition: border-color .18s, box-shadow .18s, background .18s;
    }
    input:focus, select:focus {
      border-color: var(--blue-400);
      background: var(--white);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }
    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 11px center; padding-right: 30px;
    }

    .btn-filter, .btn-reset {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 18px; border-radius: 8px;
      font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 600;
      cursor: pointer; border: none; white-space: nowrap;
      transition: all .18s;
    }
    .btn-filter {
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      color: white;
      box-shadow: 0 3px 10px rgba(37,99,235,.25);
    }
    .btn-filter:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37,99,235,.3); }

    .btn-reset {
      background: var(--gray-100); color: var(--gray-600);
      border: 1.5px solid var(--gray-200);
    }
    .btn-reset:hover { background: var(--gray-200); color: var(--gray-700); }

    /* ════ TABLE CARD ════════════════ */
    .table-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden;
      box-shadow: var(--shadow);
    }

    .table-card-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 24px; border-bottom: 1px solid var(--gray-100);
      background: linear-gradient(135deg, var(--blue-800) 0%, var(--blue-600) 100%);
    }
    .table-title {
      display: flex; align-items: center; gap: 10px;
      font-size: 14px; font-weight: 600; color: white;
    }
    .table-title i { font-size: 16px; color: rgba(255,255,255,.8); }
    .count-badge {
      background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3);
      color: white; border-radius: 20px;
      font-size: 11.5px; font-weight: 700; padding: 3px 12px;
    }

    /* Table */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 680px; }

    thead th {
      padding: 11px 16px; text-align: left;
      font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-500); background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200); white-space: nowrap;
    }
    thead th:first-child { border-radius: 0; }

    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .12s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--blue-50); }

    td { padding: 13px 16px; color: var(--gray-700); vertical-align: middle; }
    td.num { color: var(--gray-400); font-size: 12px; font-weight: 600; }

    /* Patient cell */
    .patient-cell { display: flex; align-items: center; gap: 10px; }
    .p-init {
      width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; color: white;
    }
    .p-name { font-weight: 600; color: var(--gray-800); }

    /* Doctor cell */
    .doctor-cell { display: flex; align-items: center; gap: 8px; }
    .d-init {
      width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
      background: var(--blue-100);
      display: flex; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 700; color: var(--blue-700);
    }
    .d-name { font-size: 13px; color: var(--gray-700); }

    /* Date/time */
    .date-cell { display: flex; flex-direction: column; gap: 2px; }
    .date-val { font-weight: 600; color: var(--gray-800); font-size: 13px; }
    .time-val { font-size: 11.5px; color: var(--gray-400); display: flex; align-items: center; gap: 4px; }

    /* Status pills */
    .pill {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11px; font-weight: 700; letter-spacing: .04em;
      padding: 4px 11px; border-radius: 20px; white-space: nowrap;
    }
    .pill::before { content: ''; width: 5px; height: 5px; border-radius: 50%; }

    .pill-completed { background: var(--green-bg);  color: var(--green);  border: 1px solid rgba(22,163,74,.2);  }
    .pill-completed::before { background: var(--green); }
    .pill-cancelled { background: var(--red-bg);   color: var(--red);    border: 1px solid rgba(220,38,38,.2);   }
    .pill-cancelled::before { background: var(--red); }
    .pill-pending   { background: var(--amber-bg); color: var(--amber);  border: 1px solid rgba(217,119,6,.2);   }
    .pill-pending::before   { background: var(--amber); }
    .pill-confirmed { background: var(--blue-50);  color: var(--blue-700); border: 1px solid rgba(37,99,235,.2); }
    .pill-confirmed::before { background: var(--blue-600); }
    .pill-default   { background: var(--slate-bg); color: var(--slate);  border: 1px solid rgba(100,116,139,.2); }
    .pill-default::before   { background: var(--slate); }

    .pill-seen     { background: var(--green-bg); color: var(--green); border: 1px solid rgba(22,163,74,.2); }
    .pill-seen::before { background: var(--green); }
    .pill-unseen   { background: var(--red-bg);   color: var(--red);   border: 1px solid rgba(220,38,38,.2); }
    .pill-unseen::before { background: var(--red); }

    /* Created at */
    .created-val { font-size: 12px; color: var(--gray-400); }

    /* ════ EMPTY STATE ═══════════════ */
    .empty-state {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; padding: 60px 24px; gap: 12px;
    }
    .empty-icon {
      width: 64px; height: 64px; border-radius: 50%;
      background: var(--blue-50); border: 2px solid var(--blue-100);
      display: flex; align-items: center; justify-content: center;
    }
    .empty-icon i { font-size: 28px; color: var(--blue-400); }
    .empty-title { font-size: 15px; font-weight: 600; color: var(--gray-700); }
    .empty-sub   { font-size: 13px; color: var(--gray-400); }
    .btn-clear-filter {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 9px 18px; border-radius: 8px; margin-top: 4px;
      background: var(--blue-50); border: 1.5px solid var(--blue-200);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; text-decoration: none;
      transition: background .18s;
    }
    .btn-clear-filter:hover { background: var(--blue-100); }

    /* ════ PAGINATION ════════════════ */
    .pagination-wrap {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 24px; border-top: 1px solid var(--gray-100);
      flex-wrap: wrap; gap: 12px;
    }
    .pagination-info { font-size: 12.5px; color: var(--gray-500); }
    .pagination-info strong { color: var(--gray-700); }

    .pagination { display: flex; gap: 4px; list-style: none; }
    .page-item a, .page-item span {
      display: flex; align-items: center; justify-content: center;
      min-width: 34px; height: 34px; padding: 0 10px;
      border-radius: 8px; font-size: 13px; font-weight: 500;
      text-decoration: none; color: var(--gray-600);
      background: var(--white); border: 1.5px solid var(--gray-200);
      transition: all .15s;
    }
    .page-item a:hover { background: var(--blue-50); border-color: var(--blue-300); color: var(--blue-600); }
    .page-item.active a {
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border-color: var(--blue-600); color: white; font-weight: 700;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .page-item.disabled span { opacity: .4; cursor: not-allowed; }

    /* ════ RESPONSIVE ════════════════ */
    @media (max-width: 900px) {
      .filter-row { grid-template-columns: 1fr 1fr; }
      .filter-row .btn-filter, .filter-row .btn-reset { grid-column: span 1; }
    }
    @media (max-width: 600px) {
      .topbar { padding: 0 16px; }
      .topbar-date { display: none; }
      .page { padding: 20px 12px 48px; }
      .filter-row { grid-template-columns: 1fr; }
      .summary-chips { display: none; }
      .table-card-header { padding: 14px 16px; }
      td, thead th { padding: 10px 12px; }
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
      <span class="brand-sub">Cashier Portal</span>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-date">
      <i class="bi bi-calendar3"></i>
      <?= date('D, d M Y') ?>
    </div>
    <a href="dashboard.php" class="back-btn">
      <i class="bi bi-arrow-left"></i> Dashboard
    </a>
  </div>
</header>

<!-- ════ PAGE ═══════════════════════════ -->
<div class="page">

  <!-- Header -->
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
      <i class="bi bi-chevron-right"></i>
      <span>Doctor Appointments</span>
    </div>
    <div class="header-row">
      <div>
        <h1 class="page-title">Doctor <em>Appointments</em></h1>
        <p class="page-sub">All scheduled appointments assigned to doctors (Role ID: 2).</p>
      </div>
      <div class="summary-chips">
        <div class="summary-chip">
          <div class="chip-icon blue"><i class="bi bi-calendar3"></i></div>
          <div>
            <div class="chip-num"><?= $total ?></div>
            <div class="chip-label">Total Found</div>
          </div>
        </div>
        <div class="summary-chip">
          <div class="chip-icon green"><i class="bi bi-files"></i></div>
          <div>
            <div class="chip-num"><?= $total_pages ?></div>
            <div class="chip-label">Pages</div>
          </div>
        </div>
        <div class="summary-chip">
          <div class="chip-icon amber"><i class="bi bi-file-text"></i></div>
          <div>
            <div class="chip-num"><?= $page ?></div>
            <div class="chip-label">Current Page</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="filter-card">
    <div class="filter-label"><i class="bi bi-funnel-fill"></i> Search & Filter</div>
    <form method="GET">
      <div class="filter-row">

        <div class="f-field">
          <label>Patient Name</label>
          <div class="f-input-wrap">
            <i class="bi bi-search f-icon"></i>
            <input type="text" name="search" placeholder="Search patient…"
                   value="<?= htmlspecialchars($search) ?>">
          </div>
        </div>

        <div class="f-field">
          <label>Date</label>
          <div class="f-input-wrap">
            <i class="bi bi-calendar3 f-icon"></i>
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
          </div>
        </div>

        <div class="f-field">
          <label>Status</label>
          <div class="f-input-wrap">
            <i class="bi bi-flag f-icon"></i>
            <select name="status">
              <option value="">All Status</option>
              <option value="Pending"   <?= $status === 'Pending'   ? 'selected' : '' ?>>Pending</option>
              <option value="Confirmed" <?= $status === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
              <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
              <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>
        </div>

        <div class="f-field" style="justify-content:flex-end;">
          <label>&nbsp;</label>
          <button type="submit" class="btn-filter">
            <i class="bi bi-funnel"></i> Filter
          </button>
        </div>

        <div class="f-field" style="justify-content:flex-end;">
          <label>&nbsp;</label>
          <a href="view_appointments.php" class="btn-reset" style="text-decoration:none;">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
          </a>
        </div>

      </div>
    </form>
  </div>

  <!-- Table Card -->
  <div class="table-card">

    <div class="table-card-header">
      <div class="table-title">
        <i class="bi bi-calendar-week"></i>
        Appointment Records
      </div>
      <span class="count-badge"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($appointments)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
        <div class="empty-title">No appointments found</div>
        <div class="empty-sub">
          <?= ($search || $date || $status) ? 'Try adjusting your search filters.' : 'No appointment records exist yet.' ?>
        </div>
        <?php if ($search || $date || $status): ?>
          <a href="view_appointments.php" class="btn-clear-filter">
            <i class="bi bi-x-circle"></i> Clear Filters
          </a>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Patient</th>
              <th>Doctor</th>
              <th>Date &amp; Time</th>
              <th>Status</th>
              <th>Seen</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($appointments as $i => $row):
              $pName    = $row['patient_name'] ?? 'N/A';
              $dName    = $row['doctor_name']  ?? 'N/A';
              $pInit    = strtoupper(substr($pName, 0, 1));
              $dInit    = strtoupper(substr($dName, 0, 1));
              $statusLo = strtolower($row['status'] ?? '');
              $pillMap  = [
                'completed' => 'pill-completed',
                'cancelled' => 'pill-cancelled',
                'pending'   => 'pill-pending',
                'confirmed' => 'pill-confirmed',
              ];
              $pillCls  = $pillMap[$statusLo] ?? 'pill-default';

              // Format date
              $apptDate = $row['appointment_date'] ?? '';
              $apptTime = $row['appointment_time'] ?? '';
              $dateFormatted = $apptDate ? date('d M Y', strtotime($apptDate)) : '—';
              $timeFormatted = $apptTime ? date('h:i A', strtotime($apptTime)) : '—';

              // Created
              $created = $row['created_at'] ?? '';
              $createdFmt = $created ? date('d M Y, h:i A', strtotime($created)) : '—';
            ?>
            <tr>
              <td class="num"><?= $offset + $i + 1 ?></td>

              <td>
                <div class="patient-cell">
                  <div class="p-init"><?= htmlspecialchars($pInit) ?></div>
                  <span class="p-name"><?= htmlspecialchars($pName) ?></span>
                </div>
              </td>

              <td>
                <div class="doctor-cell">
                  <div class="d-init"><?= htmlspecialchars($dInit) ?></div>
                  <span class="d-name">Dr. <?= htmlspecialchars($dName) ?></span>
                </div>
              </td>

              <td>
                <div class="date-cell">
                  <span class="date-val"><?= $dateFormatted ?></span>
                  <span class="time-val"><i class="bi bi-clock" style="font-size:10px;"></i><?= $timeFormatted ?></span>
                </div>
              </td>

              <td><span class="pill <?= $pillCls ?>"><?= ucfirst($row['status'] ?? 'Unknown') ?></span></td>

              <td>
                <?php if (!empty($row['seen']) && $row['seen'] == 1): ?>
                  <span class="pill pill-seen"><i class="bi bi-check2-circle" style="font-size:11px;"></i>Seen</span>
                <?php else: ?>
                  <span class="pill pill-unseen"><i class="bi bi-dash-circle" style="font-size:11px;"></i>Not Seen</span>
                <?php endif; ?>
              </td>

              <td><span class="created-val"><?= $createdFmt ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1):
        $pgStart = max(1, $page - 2);
        $pgEnd   = min($total_pages, $page + 2);
      ?>
      <div class="pagination-wrap">
        <span class="pagination-info">
          Showing <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $limit, $total) ?></strong>
          of <strong><?= $total ?></strong> appointments
        </span>
        <ul class="pagination">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <?php if ($page > 1): ?>
              <a href="<?= pgUrl($page - 1, $search, $date, $status) ?>"><i class="bi bi-chevron-left"></i></a>
            <?php else: ?><span><i class="bi bi-chevron-left"></i></span><?php endif; ?>
          </li>

          <?php if ($pgStart > 1): ?>
            <li class="page-item"><a href="<?= pgUrl(1, $search, $date, $status) ?>">1</a></li>
            <?php if ($pgStart > 2): ?><li class="page-item disabled"><span>…</span></li><?php endif; ?>
          <?php endif; ?>

          <?php for ($pg = $pgStart; $pg <= $pgEnd; $pg++): ?>
            <li class="page-item <?= $pg === $page ? 'active' : '' ?>">
              <a href="<?= pgUrl($pg, $search, $date, $status) ?>"><?= $pg ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($pgEnd < $total_pages): ?>
            <?php if ($pgEnd < $total_pages - 1): ?><li class="page-item disabled"><span>…</span></li><?php endif; ?>
            <li class="page-item"><a href="<?= pgUrl($total_pages, $search, $date, $status) ?>"><?= $total_pages ?></a></li>
          <?php endif; ?>

          <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <?php if ($page < $total_pages): ?>
              <a href="<?= pgUrl($page + 1, $search, $date, $status) ?>"><i class="bi bi-chevron-right"></i></a>
            <?php else: ?><span><i class="bi bi-chevron-right"></i></span><?php endif; ?>
          </li>
        </ul>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div><!-- /table-card -->

</div><!-- /page -->
</body>
</html>