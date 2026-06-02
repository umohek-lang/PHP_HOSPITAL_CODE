<?php
session_start();
require '../db.php';
require '../includes/auth.php';

checkRole(9);

$message     = "";
$messageType = "";

// Handle mark as reviewed
if (isset($_GET['mark_reviewed'])) {
    $report_id   = $_GET['mark_reviewed'];
    $doctor_name = $_SESSION['user']['full_name'];
    $stmt = $pdo->prepare("UPDATE nurse_reports SET reviewed_by = ?, reviewed_at = NOW() WHERE report_id = ?");
    if ($stmt->execute([$doctor_name, $report_id])) {
        $message     = "Report marked as reviewed by Dr. " . htmlspecialchars($doctor_name) . ".";
        $messageType = "success";
    }
}

// Filters
$filter_patient = $_GET['patient'] ?? '';
$filter_date    = $_GET['date']    ?? '';

// Pagination
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Query
$query  = "SELECT nr.*, p.full_name AS patient_name FROM nurse_reports nr JOIN patients p ON nr.patient_id = p.patient_id WHERE 1";
$params = [];
if (!empty($filter_patient)) { $query .= " AND p.full_name LIKE ?"; $params[] = "%$filter_patient%"; }
if (!empty($filter_date))    { $query .= " AND nr.report_date = ?"; $params[] = $filter_date; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) AS total");
$countStmt->execute($params);
$total_rows  = $countStmt->fetchColumn();
$total_pages = (int)ceil($total_rows / $limit);

$query .= " ORDER BY nr.report_date DESC, nr.report_time DESC LIMIT $limit OFFSET $offset";
$stmt  = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reviewed   = array_filter($reports, fn($r) => !empty($r['reviewed_by']));
$unreviewed = array_filter($reports, fn($r) =>  empty($r['reviewed_by']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nurse Reports — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
      --amber-500: #f59e0b; --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --red-600: #dc2626; --red-50: #fef2f2; --red-100: #fee2e2; --red-700: #b91c1c;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
      --blue-glow: rgba(37,99,235,.12);
    }

    html, body { min-height: 100vh; font-family: 'Sora', sans-serif; background: var(--gray-50); color: var(--gray-700); }
    body::before { content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background: radial-gradient(ellipse 600px 400px at 5% 10%, rgba(37,99,235,.05) 0%, transparent 70%),
                  radial-gradient(ellipse 500px 350px at 95% 90%, rgba(96,165,250,.04) 0%, transparent 70%); }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 4px; }

    /* ── TOPBAR ── */
    .topbar {
      position: sticky; top: 0; z-index: 200; background: var(--white);
      border-bottom: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);
      height: 62px; display: flex; align-items: center; justify-content: space-between; padding: 0 32px;
    }
    .tb-brand { display: flex; align-items: center; gap: 11px; }
    .tb-icon { width: 36px; height: 36px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.25); }
    .tb-name { font-family: 'Instrument Serif', serif; font-size: 1.05rem; color: var(--blue-800); }
    .tb-sep  { color: var(--gray-300); margin: 0 2px; }
    .tb-page { font-size: .78rem; color: var(--blue-600); font-weight: 600; }
    .back-btn {
      display: flex; align-items: center; gap: 6px; padding: 7px 15px;
      border-radius: 8px; background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-size: .75rem; font-weight: 600;
      text-decoration: none; transition: all .18s; font-family: 'Sora', sans-serif;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── PAGE ── */
    .page { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 32px 22px 60px; }

    /* ── PAGE HEADER ── */
    .page-header { margin-bottom: 26px; }
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

    /* ── SUMMARY PILLS ── */
    .summary-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .summary-pill {
      display: flex; align-items: center; gap: 10px;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 12px; padding: 12px 18px; box-shadow: var(--shadow-sm);
    }
    .sp-icon { width: 34px; height: 34px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center; font-size: .9rem; }
    .sp-icon.blue   { background: var(--blue-50);  color: var(--blue-600); }
    .sp-icon.amber  { background: var(--amber-50); color: var(--amber-700); }
    .sp-icon.green  { background: var(--green-50); color: var(--green-600); }
    .sp-num   { font-family: 'Instrument Serif', serif; font-size: 1.4rem; color: var(--gray-900); line-height: 1; }
    .sp-label { font-size: .66rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: .07em; margin-top: 1px; }

    /* ── ALERT ── */
    .alert-box {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 18px; border-radius: 10px; margin-bottom: 22px;
      font-size: .83rem; font-weight: 500;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
    .alert-box.success { background: var(--green-50); border: 1px solid var(--green-100); color: var(--green-700); }
    .alert-box i { font-size: .95rem; flex-shrink: 0; }

    /* ── FILTER CARD ── */
    .filter-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; padding: 18px 22px; margin-bottom: 22px;
      box-shadow: var(--shadow-sm);
    }
    .filter-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
    .filter-field { flex: 1; min-width: 180px; }
    .filter-label {
      font-size: .65rem; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; color: var(--gray-500); margin-bottom: 6px;
    }
    .filter-input {
      width: 100%; height: 40px; padding: 0 13px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; font-family: 'Sora', sans-serif;
      font-size: .82rem; color: var(--gray-700); outline: none;
      transition: border-color .18s, box-shadow .18s;
    }
    .filter-input:focus { border-color: var(--blue-500); box-shadow: 0 0 0 3px var(--blue-glow); background: var(--white); }
    .filter-input::placeholder { color: var(--gray-300); }
    .btn-filter {
      height: 40px; padding: 0 20px; border-radius: 9px;
      background: var(--blue-600); border: none; color: #fff;
      font-family: 'Sora', sans-serif; font-size: .8rem; font-weight: 700;
      cursor: pointer; display: flex; align-items: center; gap: 6px;
      box-shadow: 0 3px 10px rgba(37,99,235,.22); transition: all .18s; flex-shrink: 0;
    }
    .btn-filter:hover { background: var(--blue-700); transform: translateY(-1px); }
    .btn-clear {
      height: 40px; padding: 0 16px; border-radius: 9px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-500); font-family: 'Sora', sans-serif;
      font-size: .78rem; font-weight: 600; text-decoration: none;
      display: flex; align-items: center; gap: 5px; transition: all .15s; flex-shrink: 0;
    }
    .btn-clear:hover { background: var(--gray-200); color: var(--gray-700); }

    /* active filter chips */
    .filter-chips { display: flex; gap: 7px; margin-top: 12px; flex-wrap: wrap; }
    .f-chip {
      display: flex; align-items: center; gap: 5px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 3px 11px;
      font-size: .68rem; font-weight: 600; color: var(--blue-700);
    }

    /* ── TABLE CARD ── */
    .table-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-md);
    }
    .table-head-bar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; border-bottom: 1px solid var(--blue-100);
      background: #fafcff;
    }
    .thb-left { display: flex; align-items: center; gap: 10px; }
    .thb-icon { width: 32px; height: 32px; border-radius: 8px;
      background: var(--blue-50); color: var(--blue-600);
      display: flex; align-items: center; justify-content: center; font-size: .9rem; }
    .thb-title { font-size: .9rem; font-weight: 800; color: var(--gray-900); }
    .thb-count {
      font-size: .68rem; font-weight: 700; padding: 3px 11px; border-radius: 999px;
      background: var(--blue-50); border: 1px solid var(--blue-100); color: var(--blue-700);
    }

    /* ── DATA TABLE ── */
    .data-table { width: 100%; border-collapse: collapse; font-size: .78rem; min-width: 860px; }
    .data-table thead th {
      padding: 9px 16px; text-align: left;
      font-size: .62rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-400); background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200); white-space: nowrap;
    }
    .data-table thead th.center { text-align: center; }
    .data-table tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .1s; }
    .data-table tbody tr:last-child { border-bottom: none; }
    .data-table tbody tr:hover { background: var(--blue-50); }
    .data-table td { padding: 11px 16px; color: var(--gray-700); vertical-align: top; }
    .data-table td.center { text-align: center; vertical-align: middle; }
    .data-table td.mono { font-family: monospace; font-size: .72rem; color: var(--gray-400); font-weight: 600; }

    /* patient cell */
    .pat-cell { display: flex; align-items: center; gap: 9px; }
    .pat-av {
      width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .68rem; font-weight: 700; color: #fff;
    }
    .pat-name { font-weight: 700; color: var(--gray-900); font-size: .8rem; }

    /* report text */
    .report-text { font-size: .78rem; color: var(--gray-600); line-height: 1.55; max-width: 240px; }

    /* status pills */
    .pill-reviewed {
      display: inline-flex; flex-direction: column; align-items: center;
      background: var(--green-50); border: 1px solid var(--green-100);
      border-radius: 9px; padding: 5px 11px; font-size: .7rem; font-weight: 700; color: var(--green-700);
      gap: 1px;
    }
    .pill-reviewed small { font-weight: 400; color: var(--green-600); font-size: .65rem; }
    .pill-pending {
      display: inline-flex; align-items: center; gap: 5px;
      background: var(--amber-50); border: 1px solid var(--amber-100);
      border-radius: 999px; padding: 4px 11px;
      font-size: .7rem; font-weight: 700; color: var(--amber-700);
    }
    .pill-pending::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--amber-500); }

    /* done text */
    .done-text { display: flex; align-items: center; gap: 5px; color: var(--green-600); font-size: .76rem; font-weight: 600; }

    /* review button */
    .btn-review {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 6px 14px; border-radius: 8px;
      background: var(--green-50); border: 1px solid var(--green-100);
      color: var(--green-700); font-family: 'Sora', sans-serif;
      font-size: .74rem; font-weight: 700; text-decoration: none; transition: all .16s;
    }
    .btn-review:hover { background: var(--green-600); color: #fff; border-color: var(--green-600); }

    /* ── EMPTY STATE ── */
    .empty-state { padding: 56px 20px; text-align: center; }
    .empty-state i { font-size: 3rem; color: var(--blue-200); display: block; margin-bottom: 14px; }
    .empty-state h3 { font-size: .95rem; font-weight: 700; color: var(--gray-700); margin-bottom: 5px; }
    .empty-state p  { font-size: .8rem; color: var(--gray-400); }

    /* ── PAGINATION ── */
    .pagination-wrap {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; border-top: 1px solid var(--gray-100);
      background: var(--gray-50); flex-wrap: wrap; gap: 10px;
    }
    .pag-info { font-size: .74rem; color: var(--gray-400); font-weight: 500; }
    .pag-btns { display: flex; gap: 4px; }
    .pag-btn {
      display: flex; align-items: center; justify-content: center;
      min-width: 34px; height: 34px; padding: 0 10px;
      border-radius: 8px; font-size: .78rem; font-weight: 600;
      text-decoration: none; color: var(--gray-500);
      background: var(--white); border: 1px solid var(--gray-200); transition: all .15s;
    }
    .pag-btn:hover { background: var(--blue-50); color: var(--blue-600); border-color: var(--blue-200); }
    .pag-btn.active { background: var(--blue-600); border-color: var(--blue-600); color: #fff; font-weight: 700; box-shadow: 0 3px 10px rgba(37,99,235,.28); }
    .pag-btn.disabled { opacity: .35; pointer-events: none; }

    /* responsive */
    @media (max-width: 700px) {
      .topbar { padding: 0 14px; }
      .page { padding: 18px 12px 48px; }
      .summary-row { display: none; }
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
    <span class="tb-page">Nurse Reports</span>
  </div>
  <a href="md_dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> MD Dashboard</a>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-clipboard2-check-fill"></i> MD · Clinical Review</div>
    <div class="ph-title">Nurse <em>Reports</em></div>
    <div class="ph-sub">Review and approve nurse reports submitted for patients under your care.</div>
  </div>

  <!-- SUMMARY PILLS -->
  <div class="summary-row">
    <div class="summary-pill">
      <div class="sp-icon blue"><i class="bi bi-clipboard2-fill"></i></div>
      <div>
        <div class="sp-num"><?= number_format($total_rows) ?></div>
        <div class="sp-label">Total Reports</div>
      </div>
    </div>
    <div class="summary-pill">
      <div class="sp-icon amber"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="sp-num"><?= count($unreviewed) ?></div>
        <div class="sp-label">Pending Review</div>
      </div>
    </div>
    <div class="summary-pill">
      <div class="sp-icon green"><i class="bi bi-check-circle-fill"></i></div>
      <div>
        <div class="sp-num"><?= count($reviewed) ?></div>
        <div class="sp-label">Reviewed</div>
      </div>
    </div>
    <div class="summary-pill">
      <div class="sp-icon blue"><i class="bi bi-file-earmark-text-fill"></i></div>
      <div>
        <div class="sp-num"><?= $total_pages ?></div>
        <div class="sp-label">Pages</div>
      </div>
    </div>
  </div>

  <!-- ALERT -->
  <?php if ($message): ?>
  <div class="alert-box success">
    <i class="bi bi-check-circle-fill"></i>
    <?= $message ?>
  </div>
  <?php endif; ?>

  <!-- FILTER CARD -->
  <div class="filter-card">
    <form method="GET">
      <div class="filter-row">
        <div class="filter-field">
          <div class="filter-label"><i class="bi bi-person-fill" style="color:var(--blue-400);margin-right:4px"></i>Patient Name</div>
          <input type="text" name="patient" class="filter-input"
            placeholder="Search by patient name…"
            value="<?= htmlspecialchars($filter_patient) ?>">
        </div>
        <div class="filter-field" style="max-width:200px">
          <div class="filter-label"><i class="bi bi-calendar3" style="color:var(--blue-400);margin-right:4px"></i>Report Date</div>
          <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <button type="submit" class="btn-filter">
          <i class="bi bi-search"></i> Filter
        </button>
        <?php if ($filter_patient || $filter_date): ?>
          <a href="view_nurse_reports.php" class="btn-clear"><i class="bi bi-x-lg"></i> Clear</a>
        <?php endif; ?>
      </div>
      <?php if ($filter_patient || $filter_date): ?>
      <div class="filter-chips">
        <?php if ($filter_patient): ?>
          <span class="f-chip"><i class="bi bi-person-fill"></i> "<?= htmlspecialchars($filter_patient) ?>"</span>
        <?php endif; ?>
        <?php if ($filter_date): ?>
          <span class="f-chip"><i class="bi bi-calendar3"></i> <?= htmlspecialchars($filter_date) ?></span>
        <?php endif; ?>
        <span class="f-chip" style="background:var(--green-50);border-color:var(--green-100);color:var(--green-700)">
          <i class="bi bi-check-circle-fill"></i> <?= $total_rows ?> result<?= $total_rows !== 1 ? 's' : '' ?>
        </span>
      </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- TABLE CARD -->
  <div class="table-card">
    <div class="table-head-bar">
      <div class="thb-left">
        <div class="thb-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div class="thb-title">Nurse Reports</div>
      </div>
      <span class="thb-count"><?= number_format($total_rows) ?> report<?= $total_rows !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($reports)): ?>
    <div class="empty-state">
      <i class="bi bi-clipboard2-x"></i>
      <h3>No Reports Found</h3>
      <p><?= ($filter_patient || $filter_date) ? 'Try adjusting your search filters.' : 'No nurse reports have been submitted yet.' ?></p>
    </div>

    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Patient</th>
            <th>Nurse</th>
            <th>Report</th>
            <th>Signature</th>
            <th>Date</th>
            <th>Time</th>
            <th class="center">Status</th>
            <th class="center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $idx => $r):
            $name     = $r['patient_name'] ?? '';
            $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $name)))));
            $initials = substr($initials, 0, 2) ?: '—';
          ?>
          <tr>
            <td class="mono"><?= str_pad($offset + $idx + 1, 3, '0', STR_PAD_LEFT) ?></td>
            <td>
              <div class="pat-cell">
                <div class="pat-av"><?= htmlspecialchars($initials) ?></div>
                <div class="pat-name"><?= htmlspecialchars($name) ?></div>
              </div>
            </td>
            <td style="font-weight:600;color:var(--gray-900)"><?= htmlspecialchars($r['nurse_name']) ?></td>
            <td><div class="report-text"><?= nl2br(htmlspecialchars($r['report'])) ?></div></td>
            <td style="font-size:.74rem;color:var(--gray-500)"><?= htmlspecialchars($r['signature']) ?></td>
            <td style="font-size:.74rem;color:var(--gray-500);white-space:nowrap">
              <i class="bi bi-calendar3" style="color:var(--blue-400);margin-right:4px"></i>
              <?= htmlspecialchars($r['report_date']) ?>
            </td>
            <td style="font-size:.74rem;color:var(--gray-500);white-space:nowrap">
              <i class="bi bi-clock" style="color:var(--blue-400);margin-right:4px"></i>
              <?= date('h:i A', strtotime($r['report_time'])) ?>
            </td>
            <td class="center">
              <?php if (!empty($r['reviewed_by'])): ?>
                <div class="pill-reviewed">
                  <span><i class="bi bi-person-check-fill"></i> <?= htmlspecialchars($r['reviewed_by']) ?></span>
                  <small><?= date('d M Y, h:i A', strtotime($r['reviewed_at'])) ?></small>
                </div>
              <?php else: ?>
                <span class="pill-pending">Pending Review</span>
              <?php endif; ?>
            </td>
            <td class="center">
              <?php if (empty($r['reviewed_by'])): ?>
                <a href="mark_as_reviewed.php?report_id=<?= $r['report_id'] ?>"
                   class="btn-review"
                   onclick="return confirm('Mark this nurse report as reviewed?')">
                  <i class="bi bi-check-circle-fill"></i> Mark Reviewed
                </a>
              <?php else: ?>
                <span class="done-text"><i class="bi bi-check-all"></i> Done</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1):
      $start = max(1, $page - 2);
      $end   = min($total_pages, $page + 2);
    ?>
    <div class="pagination-wrap">
      <span class="pag-info">
        Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $total_rows) ?> of <?= number_format($total_rows) ?> reports
      </span>
      <div class="pag-btns">
        <a href="?page=<?= $page-1 ?>&patient=<?= urlencode($filter_patient) ?>&date=<?= urlencode($filter_date) ?>"
           class="pag-btn <?= $page <= 1 ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-left"></i>
        </a>
        <?php if ($start > 1): ?>
          <a href="?page=1&patient=<?= urlencode($filter_patient) ?>&date=<?= urlencode($filter_date) ?>" class="pag-btn">1</a>
          <?php if ($start > 2): ?><span class="pag-btn disabled">…</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
          <a href="?page=<?= $p ?>&patient=<?= urlencode($filter_patient) ?>&date=<?= urlencode($filter_date) ?>"
             class="pag-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($end < $total_pages): ?>
          <?php if ($end < $total_pages - 1): ?><span class="pag-btn disabled">…</span><?php endif; ?>
          <a href="?page=<?= $total_pages ?>&patient=<?= urlencode($filter_patient) ?>&date=<?= urlencode($filter_date) ?>" class="pag-btn"><?= $total_pages ?></a>
        <?php endif; ?>
        <a href="?page=<?= $page+1 ?>&patient=<?= urlencode($filter_patient) ?>&date=<?= urlencode($filter_date) ?>"
           class="pag-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

</div>
</body>
</html>