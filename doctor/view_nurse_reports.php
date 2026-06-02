<?php
session_start();
require '../db.php';
require '../includes/auth.php';
checkRole(2, 9);

$message     = '';
$messageType = '';

if (isset($_GET['mark_reviewed'])) {
    $report_id   = $_GET['mark_reviewed'];
    $doctor_name = $_SESSION['user']['full_name'];
    $stmt = $pdo->prepare("UPDATE nurse_reports SET reviewed_by = ?, reviewed_at = NOW() WHERE report_id = ?");
    if ($stmt->execute([$doctor_name, $report_id])) {
        $message     = "Report marked as reviewed by Dr. {$doctor_name}.";
        $messageType = 'success';
    } else {
        $message     = 'Failed to mark report as reviewed. Please try again.';
        $messageType = 'error';
    }
}

$filter_patient = $_GET['patient'] ?? '';
$filter_date    = $_GET['date']    ?? '';

$query  = "SELECT nr.*, p.full_name AS patient_name FROM nurse_reports nr JOIN patients p ON nr.patient_id = p.patient_id WHERE 1";
$params = [];

if (!empty($filter_patient)) { $query .= " AND p.full_name LIKE ?"; $params[] = "%$filter_patient%"; }
if (!empty($filter_date))    { $query .= " AND nr.report_date = ?"; $params[] = $filter_date; }

$query .= " ORDER BY nr.report_date DESC, nr.report_time DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalReports   = count($reports);
$pendingReports = count(array_filter($reports, fn($r) => empty($r['reviewed_by'])));
$reviewedReports= $totalReports - $pendingReports;

$doctor_name = $_SESSION['user']['full_name'] ?? 'Doctor';
$initial     = strtoupper(substr($doctor_name, 0, 1));
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
      --white:    #ffffff; --gray-50: #f8fafc; --gray-100: #f1f5f9;
      --gray-200: #e2e8f0; --gray-300: #cbd5e1; --gray-400: #94a3b8;
      --gray-500: #64748b; --gray-700: #334155; --gray-900: #0f172a;
      --green-600: #059669; --green-500: #10b981; --green-50: #ecfdf5;
      --green-100: #d1fae5; --green-700: #047857;
      --amber-500: #f59e0b; --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --red-50: #fef2f2; --red-100: #fee2e2; --red-600: #dc2626;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
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
        radial-gradient(ellipse 600px 400px at 5% 10%, rgba(37,99,235,.05) 0%, transparent 70%),
        radial-gradient(ellipse 500px 350px at 95% 90%, rgba(96,165,250,.04) 0%, transparent 70%);
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
    .tb-right { display: flex; align-items: center; gap: 10px; }
    .tb-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .78rem; font-weight: 700; color: #fff;
      box-shadow: 0 2px 8px rgba(37,99,235,.22);
    }
    .tb-doctor { font-size: .75rem; font-weight: 600; color: var(--gray-700); }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-size: .75rem; font-weight: 600;
      text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── PAGE ── */
    .page { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 32px 24px 60px; }

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

    /* ── STAT CHIPS ── */
    .stat-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
    .stat-chip {
      display: flex; align-items: center; gap: 12px;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 12px; padding: 12px 18px; box-shadow: var(--shadow-sm);
    }
    .sc-icon {
      width: 36px; height: 36px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center; font-size: .95rem;
    }
    .sc-icon.blue   { background: var(--blue-50);   color: var(--blue-600); }
    .sc-icon.amber  { background: var(--amber-50);  color: var(--amber-500); }
    .sc-icon.green  { background: var(--green-50);  color: var(--green-600); }
    .sc-num   { font-family: 'Instrument Serif', serif; font-size: 1.5rem; color: var(--gray-900); line-height: 1; }
    .sc-label { font-size: .65rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: .07em; margin-top: 2px; }

    /* ── ALERT ── */
    .alert-box {
      display: flex; align-items: flex-start; gap: 11px;
      padding: 13px 18px; border-radius: 10px; margin-bottom: 20px;
      font-size: .83rem; line-height: 1.5; animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
    .alert-box i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
    .alert-success { background: var(--green-50); border: 1px solid var(--green-100); color: var(--green-700); }
    .alert-success i { color: var(--green-500); }
    .alert-error   { background: var(--red-50);   border: 1px solid var(--red-100);   color: var(--red-600); }
    .alert-error i { color: var(--red-600); }

    /* ── SEARCH CARD ── */
    .search-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; padding: 18px 22px;
      margin-bottom: 22px; box-shadow: var(--shadow-sm);
    }
    .search-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .search-wrap { flex: 1; min-width: 220px; position: relative; }
    .s-icon {
      position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: .85rem; pointer-events: none;
    }
    .search-input {
      width: 100%; height: 40px; padding: 0 13px 0 34px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; font-family: 'Sora', sans-serif;
      font-size: .82rem; color: var(--gray-700); outline: none;
      transition: border-color .18s, box-shadow .18s;
    }
    .search-input::placeholder { color: var(--gray-300); }
    .search-input:focus { border-color: var(--blue-400); box-shadow: 0 0 0 3px var(--blue-glow); background: var(--white); }
    .date-input {
      height: 40px; padding: 0 13px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; font-family: 'Sora', sans-serif;
      font-size: .82rem; color: var(--gray-700); outline: none;
      transition: border-color .18s;
    }
    .date-input:focus { border-color: var(--blue-400); box-shadow: 0 0 0 3px var(--blue-glow); background: var(--white); }
    .btn-search {
      height: 40px; padding: 0 18px; border-radius: 9px;
      background: var(--blue-600); border: none; color: #fff;
      font-family: 'Sora', sans-serif; font-size: .8rem; font-weight: 700;
      cursor: pointer; display: flex; align-items: center; gap: 6px;
      box-shadow: 0 3px 10px rgba(37,99,235,.22); transition: all .18s; flex-shrink: 0;
    }
    .btn-search:hover { background: var(--blue-700); transform: translateY(-1px); }
    .btn-clear {
      height: 40px; padding: 0 14px; border-radius: 9px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-500); font-family: 'Sora', sans-serif;
      font-size: .78rem; font-weight: 600; text-decoration: none;
      display: flex; align-items: center; gap: 5px; transition: all .16s; flex-shrink: 0;
    }
    .btn-clear:hover { background: var(--gray-200); color: var(--gray-700); }

    /* active filter chips */
    .filter-chips { display: flex; gap: 7px; margin-top: 12px; flex-wrap: wrap; }
    .fchip {
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
    .table-card-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 22px; border-bottom: 1px solid var(--gray-100);
      background: #fafcff;
    }
    .tch-left { display: flex; align-items: center; gap: 10px; }
    .tch-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--blue-50); color: var(--blue-600);
      display: flex; align-items: center; justify-content: center; font-size: .88rem;
    }
    .tch-title { font-size: .88rem; font-weight: 800; color: var(--gray-900); }
    .tch-sub   { font-size: .68rem; color: var(--gray-400); margin-top: 1px; }
    .tch-count {
      font-size: .68rem; font-weight: 700; padding: 3px 12px;
      border-radius: 999px; background: var(--blue-50);
      border: 1px solid var(--blue-100); color: var(--blue-700);
    }

    /* ── DATA TABLE ── */
    .data-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .data-table thead th {
      padding: 10px 16px; text-align: left;
      font-size: .62rem; font-weight: 700; letter-spacing: .09em;
      text-transform: uppercase; color: var(--gray-400);
      background: var(--gray-50); border-bottom: 1px solid var(--gray-200);
      white-space: nowrap;
    }
    .data-table tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .1s; }
    .data-table tbody tr:last-child { border-bottom: none; }
    .data-table tbody tr:hover { background: var(--blue-50); }
    .data-table td { padding: 12px 16px; color: var(--gray-700); vertical-align: top; line-height: 1.5; }
    .data-table td.center { text-align: center; vertical-align: middle; }
    .data-table td.mono   { font-family: monospace; color: var(--gray-400); font-size: .74rem; font-weight: 700; vertical-align: middle; }

    /* patient cell */
    .patient-cell { display: flex; align-items: center; gap: 9px; }
    .pat-av {
      width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .66rem; font-weight: 700; color: #fff;
    }
    .pat-name { font-weight: 700; color: var(--gray-900); font-size: .82rem; }

    /* nurse cell */
    .nurse-name { font-weight: 600; color: var(--gray-700); }
    .nurse-sig  { font-size: .7rem; color: var(--gray-400); font-style: italic; margin-top: 2px; }

    /* report text */
    .report-text { font-size: .78rem; color: var(--gray-600); line-height: 1.6; max-width: 280px; }

    /* date/time cell */
    .datetime-cell { white-space: nowrap; }
    .dt-date { font-weight: 600; color: var(--gray-900); font-size: .8rem; }
    .dt-time { font-size: .7rem; color: var(--gray-400); margin-top: 2px; display: flex; align-items: center; gap: 4px; }

    /* status pills */
    .pill {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 11px; border-radius: 999px;
      font-size: .68rem; font-weight: 700; white-space: nowrap;
    }
    .pill.pending  { background: var(--amber-50);  border: 1px solid var(--amber-100); color: var(--amber-700); }
    .pill.reviewed { background: var(--green-50);  border: 1px solid var(--green-100); color: var(--green-700); }
    .reviewed-meta { font-size: .65rem; color: var(--green-600); margin-top: 3px; display: block; }

    /* action buttons */
    .btn-review {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 6px 14px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-700); font-family: 'Sora', sans-serif;
      font-size: .73rem; font-weight: 700; text-decoration: none;
      transition: all .16s; white-space: nowrap;
    }
    .btn-review:hover { background: var(--blue-600); color: #fff; border-color: var(--blue-600); }
    .done-label { font-size: .72rem; color: var(--gray-400); display: flex; align-items: center; gap: 4px; }

    /* ── EMPTY STATE ── */
    .empty-state {
      padding: 64px 20px; text-align: center; color: var(--gray-400);
    }
    .empty-state i { display: block; font-size: 3rem; color: var(--blue-200); margin-bottom: 14px; }
    .empty-state h3 { font-size: .95rem; font-weight: 700; color: var(--gray-700); margin-bottom: 6px; }
    .empty-state p  { font-size: .8rem; }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
      .topbar { padding: 0 14px; }
      .tb-doctor { display: none; }
      .page { padding: 18px 12px 48px; }
      .stat-row { display: none; }
      .search-row { flex-direction: column; }
    }
  </style>
</head>
<body>

<!-- ── TOPBAR ── -->
<header class="topbar">
  <div class="tb-brand">
    <div class="tb-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="tb-name">Angelora</span>
    <span class="tb-sep">·</span>
    <span class="tb-page">Nurse Reports</span>
  </div>
  <div class="tb-right">
    <span class="tb-doctor">Dr. <?= htmlspecialchars($doctor_name) ?></span>
    <div class="tb-avatar"><?= $initial ?></div>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-clipboard2-heart-fill"></i> Clinical · Nurse Reports</div>
    <div class="ph-title">Nurse <em>Reports</em></div>
    <div class="ph-sub">Review and approve nursing reports for your patients.</div>
  </div>

  <!-- STATS -->
  <div class="stat-row">
    <div class="stat-chip">
      <div class="sc-icon blue"><i class="bi bi-clipboard2-text-fill"></i></div>
      <div><div class="sc-num"><?= $totalReports ?></div><div class="sc-label">Total Reports</div></div>
    </div>
    <div class="stat-chip">
      <div class="sc-icon amber"><i class="bi bi-clock-fill"></i></div>
      <div><div class="sc-num"><?= $pendingReports ?></div><div class="sc-label">Pending Review</div></div>
    </div>
    <div class="stat-chip">
      <div class="sc-icon green"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="sc-num"><?= $reviewedReports ?></div><div class="sc-label">Reviewed</div></div>
    </div>
  </div>

  <!-- ALERT -->
  <?php if ($message): ?>
  <div class="alert-box alert-<?= $messageType ?>">
    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <!-- SEARCH CARD -->
  <div class="search-card">
    <form method="GET">
      <div class="search-row">
        <div class="search-wrap">
          <i class="bi bi-search s-icon"></i>
          <input type="text" name="patient" class="search-input"
            placeholder="Search by patient name…"
            value="<?= htmlspecialchars($filter_patient) ?>">
        </div>
        <input type="date" name="date" class="date-input" value="<?= htmlspecialchars($filter_date) ?>">
        <button type="submit" class="btn-search"><i class="bi bi-search"></i> Filter</button>
        <?php if ($filter_patient || $filter_date): ?>
          <a href="nurse_report_view.php" class="btn-clear"><i class="bi bi-x-lg"></i> Clear</a>
        <?php endif; ?>
      </div>
      <?php if ($filter_patient || $filter_date): ?>
      <div class="filter-chips">
        <?php if ($filter_patient): ?>
          <span class="fchip"><i class="bi bi-search"></i>"<?= htmlspecialchars($filter_patient) ?>"</span>
        <?php endif; ?>
        <?php if ($filter_date): ?>
          <span class="fchip"><i class="bi bi-calendar3"></i><?= htmlspecialchars($filter_date) ?></span>
        <?php endif; ?>
        <span class="fchip" style="background:var(--green-50);border-color:var(--green-100);color:var(--green-700)">
          <i class="bi bi-check-circle-fill"></i><?= $totalReports ?> result<?= $totalReports !== 1 ? 's' : '' ?>
        </span>
      </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- TABLE -->
  <div class="table-card">
    <div class="table-card-head">
      <div class="tch-left">
        <div class="tch-icon"><i class="bi bi-clipboard2-text-fill"></i></div>
        <div>
          <div class="tch-title">Nurse Reports</div>
          <div class="tch-sub">All submitted nursing reports</div>
        </div>
      </div>
      <span class="tch-count"><?= $totalReports ?> report<?= $totalReports !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($reports)): ?>
      <div class="empty-state">
        <i class="bi bi-clipboard2-x"></i>
        <h3>No Reports Found</h3>
        <p><?= ($filter_patient || $filter_date) ? 'Try adjusting your search or date filter.' : 'No nurse reports have been submitted yet.' ?></p>
      </div>
    <?php else: ?>
    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Patient</th>
            <th>Nurse</th>
            <th>Report</th>
            <th>Date &amp; Time</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $i => $r):
            $nameParts = explode(' ', $r['patient_name']);
            $initials  = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter($nameParts))));
            $initials  = substr($initials, 0, 2);
            $isReviewed = !empty($r['reviewed_by']);
          ?>
          <tr>
            <td class="mono"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></td>

            <td>
              <div class="patient-cell">
                <div class="pat-av"><?= htmlspecialchars($initials) ?></div>
                <div class="pat-name"><?= htmlspecialchars($r['patient_name']) ?></div>
              </div>
            </td>

            <td>
              <div class="nurse-name"><?= htmlspecialchars($r['nurse_name']) ?></div>
              <?php if (!empty($r['signature'])): ?>
                <div class="nurse-sig"><i class="bi bi-pen-fill" style="font-size:.65rem"></i> <?= htmlspecialchars($r['signature']) ?></div>
              <?php endif; ?>
            </td>

            <td>
              <div class="report-text"><?= nl2br(htmlspecialchars($r['report'])) ?></div>
            </td>

            <td class="datetime-cell">
              <div class="dt-date"><i class="bi bi-calendar3" style="color:var(--blue-400);margin-right:4px;font-size:.72rem"></i><?= htmlspecialchars($r['report_date']) ?></div>
              <div class="dt-time"><i class="bi bi-clock"></i><?= date('h:i A', strtotime($r['report_time'])) ?></div>
            </td>

            <td class="center">
              <?php if ($isReviewed): ?>
                <span class="pill reviewed"><i class="bi bi-check-circle-fill"></i> Reviewed</span>
                <span class="reviewed-meta">
                  Dr. <?= htmlspecialchars($r['reviewed_by']) ?><br>
                  <?= date('d M Y, h:i A', strtotime($r['reviewed_at'])) ?>
                </span>
              <?php else: ?>
                <span class="pill pending"><i class="bi bi-clock-fill"></i> Pending</span>
              <?php endif; ?>
            </td>

            <td class="center">
              <?php if (!$isReviewed): ?>
                <a href="mark_as_reviewed.php?report_id=<?= $r['report_id'] ?>"
                   class="btn-review"
                   onclick="return confirm('Mark this report as reviewed by Dr. <?= htmlspecialchars(addslashes($doctor_name)) ?>?')">
                  <i class="bi bi-check-circle-fill"></i> Mark Reviewed
                </a>
              <?php else: ?>
                <div class="done-label"><i class="bi bi-check2-all"></i> Done</div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>