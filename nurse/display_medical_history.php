<?php
require '../db.php';

$search           = $_GET['search'] ?? '';
$date             = $_GET['date']   ?? '';
$page             = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset           = ($page - 1) * $records_per_page;

$sql    = "FROM medical_historys mh WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (mh.full_name LIKE :search OR mh.patient_id LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($date)) {
    $sql .= " AND DATE(mh.created_at) = :date";
    $params[':date'] = $date;
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) $sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages   = ceil($total_records / $records_per_page);

$data_stmt = $pdo->prepare("SELECT mh.* $sql ORDER BY mh.id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) $data_stmt->bindValue($key, $value);
$data_stmt->bindValue(':limit',  $records_per_page, PDO::PARAM_INT);
$data_stmt->bindValue(':offset', $offset,           PDO::PARAM_INT);
$data_stmt->execute();
$records = $data_stmt->fetchAll();

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function display_json_field($json) {
    $data = json_decode($json, true);
    if (!empty($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                echo "<div class='json-entry'>";
                foreach ($value as $sk => $sv) {
                    echo "<div class='json-row'><span class='json-key'>" . ucfirst(str_replace('_', ' ', $sk)) . "</span><span class='json-val'>" . h($sv) . "</span></div>";
                }
                echo "</div>";
            } else {
                echo "<div class='json-row'><span class='json-key'>" . ucfirst(str_replace('_', ' ', $key)) . "</span><span class='json-val'>" . h($value) . "</span></div>";
            }
        }
    } else {
        echo "<span class='none-label'>None recorded</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical History Records — Angelora Hospital</title>
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
      --red-600: #dc2626; --red-50: #fef2f2; --red-100: #fee2e2; --red-700: #b91c1c;
      --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --sky-600: #0284c7; --sky-50: #f0f9ff; --sky-100: #e0f2fe; --sky-700: #0369a1;
      --violet-600: #7c3aed; --violet-50: #f5f3ff; --violet-100: #ede9fe;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
      --blue-glow: rgba(37,99,235,.12);
      --radius: 14px;
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
    .topbar-brand { display: flex; align-items: center; gap: 11px; }
    .brand-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.25);
    }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 1.05rem; color: var(--blue-800); }
    .brand-sep  { color: var(--gray-300); margin: 0 2px; }
    .brand-page { font-size: .78rem; color: var(--blue-600); font-weight: 600; }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .topbar-date {
      font-size: .72rem; color: var(--gray-400);
      padding: 4px 11px; background: var(--gray-100);
      border-radius: 999px; border: 1px solid var(--gray-200);
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 15px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: .75rem; font-weight: 600; text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── PAGE ── */
    .page { position: relative; z-index: 1; max-width: 1080px; margin: 0 auto; padding: 32px 24px 60px; }

    /* ── PAGE HEADER ── */
    .page-header { margin-bottom: 24px; }
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

    /* ── SEARCH CARD ── */
    .search-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); padding: 18px 22px;
      margin-bottom: 22px; box-shadow: var(--shadow-sm);
    }
    .search-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .search-wrap { flex: 1; min-width: 200px; position: relative; }
    .s-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: .85rem; pointer-events: none; }
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
      transition: border-color .18s, box-shadow .18s;
    }
    .date-input:focus { border-color: var(--blue-400); box-shadow: 0 0 0 3px var(--blue-glow); background: var(--white); }
    .btn-search {
      height: 40px; padding: 0 18px;
      background: var(--blue-600); border: none; border-radius: 9px;
      color: #fff; font-family: 'Sora', sans-serif;
      font-size: .8rem; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; gap: 6px;
      box-shadow: 0 3px 10px rgba(37,99,235,.22); transition: all .18s; flex-shrink: 0;
    }
    .btn-search:hover { background: var(--blue-700); transform: translateY(-1px); }
    .btn-reset {
      height: 40px; padding: 0 16px; border-radius: 9px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-500); font-family: 'Sora', sans-serif;
      font-size: .8rem; font-weight: 600; text-decoration: none;
      display: flex; align-items: center; gap: 6px; transition: all .16s; flex-shrink: 0;
    }
    .btn-reset:hover { background: var(--gray-200); color: var(--gray-700); }

    /* result count */
    .result-info {
      display: flex; align-items: center; gap: 8px; margin-top: 12px;
      font-size: .74rem; color: var(--gray-400);
    }
    .result-chip {
      display: flex; align-items: center; gap: 5px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 3px 11px;
      font-size: .68rem; font-weight: 700; color: var(--blue-700);
    }

    /* ── ACCORDION ── */
    .accordion { display: flex; flex-direction: column; gap: 12px; }

    .acc-item {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden;
      box-shadow: var(--shadow-sm); transition: box-shadow .2s;
    }
    .acc-item.open { box-shadow: var(--shadow-md); border-color: var(--blue-200); }

    /* accordion header */
    .acc-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px; cursor: pointer;
      background: var(--white); gap: 12px;
      transition: background .15s;
      user-select: none;
    }
    .acc-header:hover { background: var(--blue-50); }
    .acc-item.open .acc-header { background: #fafcff; border-bottom: 1px solid var(--blue-100); }

    .acc-header-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
    .acc-avatar {
      width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .82rem; font-weight: 700; color: #fff;
      box-shadow: 0 2px 8px rgba(37,99,235,.22);
    }
    .acc-name { font-size: .9rem; font-weight: 700; color: var(--gray-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .acc-id   { font-size: .7rem; color: var(--gray-400); font-family: monospace; margin-top: 1px; }

    .acc-meta { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .acc-date-pill {
      display: flex; align-items: center; gap: 5px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 999px; padding: 4px 11px;
      font-size: .68rem; color: var(--gray-500); font-weight: 500;
    }
    .acc-chevron {
      width: 28px; height: 28px; border-radius: 7px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: center;
      font-size: .78rem; color: var(--gray-400);
      transition: all .22s; flex-shrink: 0;
    }
    .acc-item.open .acc-chevron { background: var(--blue-600); border-color: var(--blue-600); color: #fff; transform: rotate(180deg); }

    /* accordion body */
    .acc-body { display: none; padding: 22px 24px; }
    .acc-item.open .acc-body { display: block; animation: bodyIn .25s ease; }
    @keyframes bodyIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }

    /* patient photo + info grid */
    .patient-grid { display: grid; grid-template-columns: 120px 1fr; gap: 22px; margin-bottom: 22px; }
    .patient-photo-wrap {
      display: flex; flex-direction: column; gap: 10px; align-items: center;
    }
    .patient-photo {
      width: 110px; height: 130px; border-radius: 12px;
      object-fit: cover; border: 2px solid var(--blue-100);
      box-shadow: var(--shadow-sm);
    }
    .no-photo {
      width: 110px; height: 130px; border-radius: 12px;
      background: var(--blue-50); border: 2px dashed var(--blue-200);
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 6px; color: var(--gray-400);
    }
    .no-photo i { font-size: 1.8rem; color: var(--blue-200); }
    .no-photo span { font-size: .65rem; }

    /* info grid */
    .info-cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px 16px; margin-bottom: 14px; }
    .info-box {
      background: var(--gray-50); border: 1px solid var(--gray-100);
      border-radius: 8px; padding: 8px 12px;
    }
    .ib-label { font-size: .6rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: var(--gray-400); margin-bottom: 2px; }
    .ib-value { font-size: .82rem; font-weight: 600; color: var(--gray-900); }

    /* divider */
    .section-divider {
      display: flex; align-items: center; gap: 10px;
      margin: 18px 0 14px;
    }
    .section-divider-label {
      font-size: .62rem; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--gray-400); white-space: nowrap;
    }
    .section-divider::before, .section-divider::after {
      content: ''; flex: 1; height: 1px; background: var(--gray-200);
    }

    /* clinical notes */
    .clinical-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .clinical-field {
      background: var(--gray-50); border: 1px solid var(--gray-100);
      border-radius: 9px; padding: 10px 14px;
    }
    .cf-label { font-size: .62rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--gray-400); margin-bottom: 4px; }
    .cf-value { font-size: .8rem; color: var(--gray-700); line-height: 1.55; }
    .clinical-field.full { grid-column: 1 / -1; }

    /* clinician row */
    .clinician-row {
      display: flex; align-items: center; gap: 10px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 9px; padding: 11px 16px; margin-top: 14px;
    }
    .clinician-row i { color: var(--blue-500); font-size: 1rem; flex-shrink: 0; }
    .cr-name { font-weight: 700; font-size: .82rem; color: var(--blue-800); }
    .cr-meta { font-size: .7rem; color: var(--blue-500); }

    /* json sections */
    .json-sections { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
    .json-block {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 10px; overflow: hidden;
    }
    .json-block-head {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 13px; background: var(--gray-50);
      border-bottom: 1px solid var(--gray-100);
      font-size: .68rem; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; color: var(--gray-500);
    }
    .json-block-head i { color: var(--blue-400); }
    .json-block-body { padding: 11px 13px; }
    .json-entry {
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 6px; padding: 7px 10px; margin-bottom: 7px;
    }
    .json-entry:last-child { margin-bottom: 0; }
    .json-row { display: flex; gap: 6px; font-size: .76rem; margin-bottom: 3px; }
    .json-row:last-child { margin-bottom: 0; }
    .json-key { font-weight: 700; color: var(--blue-700); white-space: nowrap; flex-shrink: 0; }
    .json-val { color: var(--gray-600); }
    .none-label { font-size: .75rem; color: var(--gray-400); font-style: italic; }

    /* action bar */
    .action-bar { display: flex; gap: 10px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--gray-100); }
    .btn-pdf {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 20px; border-radius: 9px;
      background: var(--red-50); border: 1px solid var(--red-100);
      color: var(--red-700); font-family: 'Sora', sans-serif;
      font-size: .8rem; font-weight: 700; text-decoration: none; transition: all .16s;
    }
    .btn-pdf:hover { background: var(--red-600); color: #fff; border-color: var(--red-600); }

    /* ── EMPTY STATE ── */
    .empty-state {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: var(--radius); padding: 64px 20px;
      text-align: center; box-shadow: var(--shadow-sm);
    }
    .empty-state i { font-size: 2.8rem; color: var(--blue-200); display: block; margin-bottom: 14px; }
    .empty-state h3 { font-size: .95rem; font-weight: 700; color: var(--gray-700); margin-bottom: 5px; }
    .empty-state p  { font-size: .8rem; color: var(--gray-400); }

    /* ── PAGINATION ── */
    .pagination-wrap {
      display: flex; align-items: center; justify-content: space-between;
      margin-top: 28px; flex-wrap: wrap; gap: 12px;
    }
    .pagination-info { font-size: .74rem; color: var(--gray-400); font-weight: 500; }
    .pagination { display: flex; gap: 4px; list-style: none; }
    .page-item a, .page-item span {
      display: flex; align-items: center; justify-content: center;
      min-width: 34px; height: 34px; padding: 0 10px;
      border-radius: 8px; font-size: .78rem; font-weight: 600;
      text-decoration: none; color: var(--gray-500);
      background: var(--white); border: 1px solid var(--gray-200);
      transition: all .15s;
    }
    .page-item a:hover { background: var(--blue-50); color: var(--blue-600); border-color: var(--blue-200); }
    .page-item.active a { background: var(--blue-600); border-color: var(--blue-600); color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.28); }
    .page-item.disabled span { opacity: .35; cursor: not-allowed; }

    /* ── PRINT ── */
    @media print {
      .topbar, .search-card, .acc-chevron, .action-bar .btn-pdf, .pagination-wrap { display: none !important; }
      .acc-body { display: block !important; }
      .acc-item { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; }
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 700px) {
      .topbar { padding: 0 14px; }
      .topbar-date { display: none; }
      .page { padding: 18px 12px 48px; }
      .info-cols { grid-template-columns: 1fr 1fr; }
      .clinical-grid { grid-template-columns: 1fr; }
      .json-sections { grid-template-columns: 1fr; }
      .patient-grid  { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="brand-name">Angelora</span>
    <span class="brand-sep">·</span>
    <span class="brand-page">Medical History</span>
  </div>
  <div class="topbar-right">
    <span class="topbar-date"><i class="bi bi-calendar3" style="margin-right:4px"></i><?= date('d M Y') ?></span>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-eyebrow"><i class="bi bi-journal-medical"></i> Clinical Records</div>
    <div class="ph-title">Medical <em>History Records</em></div>
    <div class="ph-sub">View, search and print detailed patient medical history records.</div>
  </div>

  <!-- SEARCH CARD -->
  <div class="search-card no-print">
    <form method="GET">
      <div class="search-row">
        <div class="search-wrap">
          <i class="bi bi-search s-icon"></i>
          <input type="text" name="search" class="search-input"
            placeholder="Search by full name or patient ID…"
            value="<?= h($search) ?>">
        </div>
        <input type="date" name="date" class="date-input" value="<?= h($date) ?>">
        <button type="submit" class="btn-search"><i class="bi bi-search"></i> Search</button>
        <a href="display_medical_history.php" class="btn-reset"><i class="bi bi-arrow-clockwise"></i> Reset</a>
      </div>
      <?php if ($total_records > 0): ?>
      <div class="result-info">
        <span class="result-chip">
          <i class="bi bi-journal-medical"></i>
          <?= number_format($total_records) ?> record<?= $total_records !== 1 ? 's' : '' ?> found
        </span>
        <?php if ($search): ?>
          <span class="result-chip" style="background:var(--green-50);border-color:var(--green-100);color:var(--green-700)">
            <i class="bi bi-search"></i> "<?= h($search) ?>"
          </span>
        <?php endif; ?>
        <?php if ($date): ?>
          <span class="result-chip" style="background:var(--amber-50);border-color:var(--amber-100);color:var(--amber-700)">
            <i class="bi bi-calendar3"></i> <?= h($date) ?>
          </span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- RECORDS -->
  <?php if ($records): ?>
  <div class="accordion" id="recordAccordion">
    <?php foreach ($records as $idx => $row):
      $name     = $row['full_name'] ?? '';
      $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $name)))));
      $initials = substr($initials, 0, 2);
      $photo    = $row['photo'] ?? '';
      $photoPath = "../uploads/" . $photo;
      $hasPhoto  = !empty($photo) && file_exists(__DIR__ . '/../uploads/' . $photo);
    ?>
    <div class="acc-item" id="acc-<?= $idx ?>">

      <!-- HEADER -->
      <div class="acc-header" onclick="toggleAcc(<?= $idx ?>)">
        <div class="acc-header-left">
          <div class="acc-avatar"><?= h($initials) ?></div>
          <div>
            <div class="acc-name"><?= h($name) ?></div>
            <div class="acc-id">ID: <?= h($row['patient_id'] ?? '—') ?></div>
          </div>
        </div>
        <div class="acc-meta">
          <?php if (!empty($row['visit_date'])): ?>
          <div class="acc-date-pill">
            <i class="bi bi-calendar3"></i>
            <?= h($row['visit_date']) ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($row['clinician_name'])): ?>
          <div class="acc-date-pill">
            <i class="bi bi-person-badge-fill"></i>
            <?= h($row['clinician_name']) ?>
          </div>
          <?php endif; ?>
          <div class="acc-chevron"><i class="bi bi-chevron-down"></i></div>
        </div>
      </div>

      <!-- BODY -->
      <div class="acc-body">

        <!-- Patient photo + demographics -->
        <div class="patient-grid">
          <div class="patient-photo-wrap">
            <?php if ($hasPhoto): ?>
              <img src="<?= h($photoPath) ?>" class="patient-photo" alt="<?= h($name) ?>">
            <?php else: ?>
              <div class="no-photo"><i class="bi bi-person-fill"></i><span>No photo</span></div>
            <?php endif; ?>
          </div>
          <div>
            <div class="info-cols">
              <div class="info-box"><div class="ib-label">Date of Birth</div><div class="ib-value"><?= h($row['dob'] ?? '—') ?></div></div>
              <div class="info-box"><div class="ib-label">Age</div><div class="ib-value"><?= h($row['age'] ?? '—') ?></div></div>
              <div class="info-box"><div class="ib-label">Gender</div><div class="ib-value"><?= h($row['gender'] ?? '—') ?></div></div>
              <div class="info-box"><div class="ib-label">Phone</div><div class="ib-value"><?= h($row['phone'] ?? '—') ?></div></div>
              <div class="info-box" style="grid-column:span 2"><div class="ib-label">Address</div><div class="ib-value"><?= h($row['address'] ?? '—') ?></div></div>
              <div class="info-box"><div class="ib-label">Visit Date</div><div class="ib-value"><?= h($row['visit_date'] ?? '—') ?></div></div>
              <div class="info-box"><div class="ib-label">Created At</div><div class="ib-value"><?= h($row['created_at'] ?? '—') ?></div></div>
            </div>
          </div>
        </div>

        <!-- Clinical notes -->
        <div class="section-divider"><span class="section-divider-label"><i class="bi bi-clipboard2-pulse-fill" style="margin-right:5px;color:var(--blue-400)"></i>Clinical Notes</span></div>

        <div class="clinical-grid">
          <div class="clinical-field"><div class="cf-label">Chief Complaint</div><div class="cf-value"><?= h($row['chief_complaint'] ?? '—') ?></div></div>
          <div class="clinical-field"><div class="cf-label">HPI</div><div class="cf-value"><?= h($row['hpi'] ?? '—') ?></div></div>
          <div class="clinical-field"><div class="cf-label">Allergies</div><div class="cf-value"><?= h($row['allergies'] ?? '—') ?></div></div>
          <div class="clinical-field"><div class="cf-label">Family History</div><div class="cf-value"><?= h($row['family_history'] ?? '—') ?></div></div>
          <div class="clinical-field full"><div class="cf-label">Assessment &amp; Plan</div><div class="cf-value"><?= h($row['assessment_plan'] ?? '—') ?></div></div>
        </div>

        <!-- Clinician -->
        <div class="clinician-row">
          <i class="bi bi-person-badge-fill"></i>
          <div>
            <div class="cr-name"><?= h($row['clinician_name'] ?? '—') ?> <span style="font-weight:400;color:var(--blue-500)">(<?= h($row['clinician_designation'] ?? '') ?>)</span></div>
            <div class="cr-meta">Date: <?= h($row['clinician_date'] ?? '—') ?> &nbsp;·&nbsp; Signature: <?= h($row['clinician_signature'] ?? '—') ?></div>
          </div>
        </div>

        <!-- JSON fields -->
        <div class="section-divider" style="margin-top:20px"><span class="section-divider-label"><i class="bi bi-list-columns-reverse" style="margin-right:5px;color:var(--blue-400)"></i>Detailed Medical Data</span></div>

        <div class="json-sections">
          <?php
          $jsonBlocks = [
            ['icon'=>'bi-scissors',            'label'=>'Surgical History',  'field'=>'surgical_history'],
            ['icon'=>'bi-capsule-pill',         'label'=>'Medications',       'field'=>'medications'],
            ['icon'=>'bi-person-walking',       'label'=>'Social History',    'field'=>'social_history'],
            ['icon'=>'bi-shield-fill-check',    'label'=>'Immunization',      'field'=>'immunization'],
            ['icon'=>'bi-lungs-fill',           'label'=>'Review of Systems', 'field'=>'ros'],
            ['icon'=>'bi-gender-female',        'label'=>'Obstetric History', 'field'=>'obstetric'],
          ];
          foreach ($jsonBlocks as $jb): ?>
          <div class="json-block">
            <div class="json-block-head"><i class="bi <?= $jb['icon'] ?>"></i><?= $jb['label'] ?></div>
            <div class="json-block-body"><?php display_json_field($row[$jb['field']] ?? ''); ?></div>
          </div>
          <?php endforeach; ?>
          <div class="json-block" style="grid-column:1/-1">
            <div class="json-block-head"><i class="bi bi-stethoscope"></i>Physical Examination</div>
            <div class="json-block-body"><?php display_json_field($row['physical_exam'] ?? ''); ?></div>
          </div>
        </div>

        <!-- Actions -->
        <div class="action-bar no-print">
          <a href="export_medical_history_pdf.php?id=<?= h($row['id']) ?>" class="btn-pdf" target="_blank">
            <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
          </a>
        </div>

      </div><!-- /acc-body -->
    </div><!-- /acc-item -->
    <?php endforeach; ?>
  </div>

  <!-- PAGINATION -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination-wrap no-print">
    <span class="pagination-info">
      Showing <?= $offset + 1 ?>–<?= min($offset + $records_per_page, $total_records) ?> of <?= number_format($total_records) ?> records
    </span>
    <ul class="pagination">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <?php if ($page > 1): ?>
          <a href="?search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&page=<?= $page-1 ?>"><i class="bi bi-chevron-left"></i></a>
        <?php else: ?><span><i class="bi bi-chevron-left"></i></span><?php endif; ?>
      </li>
      <?php
        $s = max(1, $page-2); $e = min($total_pages, $page+2);
        if ($s>1) { echo '<li class="page-item"><a href="?search='.urlencode($search).'&date='.urlencode($date).'&page=1">1</a></li>'; }
        if ($s>2) { echo '<li class="page-item disabled"><span>…</span></li>'; }
        for ($i=$s; $i<=$e; $i++) { echo '<li class="page-item '.($i==$page?'active':'').'"><a href="?search='.urlencode($search).'&date='.urlencode($date).'&page='.$i.'">'.$i.'</a></li>'; }
        if ($e<$total_pages-1) { echo '<li class="page-item disabled"><span>…</span></li>'; }
        if ($e<$total_pages) { echo '<li class="page-item"><a href="?search='.urlencode($search).'&date='.urlencode($date).'&page='.$total_pages.'">'.$total_pages.'</a></li>'; }
      ?>
      <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
        <?php if ($page < $total_pages): ?>
          <a href="?search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>&page=<?= $page+1 ?>"><i class="bi bi-chevron-right"></i></a>
        <?php else: ?><span><i class="bi bi-chevron-right"></i></span><?php endif; ?>
      </li>
    </ul>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state">
    <i class="bi bi-journal-x"></i>
    <h3>No Records Found</h3>
    <p><?= ($search || $date) ? 'Try adjusting your search or date filter.' : 'No medical history records have been added yet.' ?></p>
  </div>
  <?php endif; ?>

</div>

<script>
function toggleAcc(idx) {
  const item = document.getElementById('acc-' + idx);
  const isOpen = item.classList.contains('open');
  // close all
  document.querySelectorAll('.acc-item').forEach(i => i.classList.remove('open'));
  // toggle clicked
  if (!isOpen) item.classList.add('open');
}
</script>
</body>
</html>