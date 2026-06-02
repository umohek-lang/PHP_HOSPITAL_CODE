<?php
require '../includes/auth.php';
require '../db.php';

$search = $_GET['search'] ?? '';
$date   = $_GET['date']   ?? '';

$limit  = 10;
$page   = max((int)($_GET['page'] ?? 1), 1);
$offset = ($page - 1) * $limit;

$query  = "FROM consultations c
           LEFT JOIN patients p ON c.patient_id = p.patient_id
           WHERE (p.full_name LIKE :search)";
$params = [':search' => "%$search%"];

if (!empty($date)) {
    $query .= " AND c.consultation_date = :date";
    $params[':date'] = $date;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) $query");
$countStmt->execute($params);
$totalRows  = $countStmt->fetchColumn();
$totalPages = (int)ceil($totalRows / $limit);

$sql  = "SELECT c.*, p.full_name, p.photo $query ORDER BY c.consultation_date DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: show "—" for empty values
function val($v) { return htmlspecialchars($v ?? '') ?: '<span class="empty">—</span>'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Consultation Records — Angelora Hospital</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:       #0c1b33;
      --navy-mid:   #162344;
      --navy-light: #1e3157;
      --teal:       #0d9488;
      --teal-light: #14b8a6;
      --teal-glow:  rgba(13,148,136,.15);
      --amber:      #f59e0b;
      --red:        #ef4444;
      --green:      #10b981;
      --blue:       #3b82f6;
      --white:      #ffffff;
      --text-main:  #e2e8f0;
      --text-muted: #94a3b8;
      --border:     rgba(255,255,255,.07);
      --card-bg:    rgba(255,255,255,.04);
      --card-hover: rgba(255,255,255,.06);
      --radius:     12px;
    }

    html, body {
      min-height: 100%;
      font-family: 'Sora', sans-serif;
      background: var(--navy);
      color: var(--text-main);
    }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 10px; }

    /* ════ PAGE WRAPPER ════ */
    .page { max-width: 1600px; margin: 0 auto; padding: 32px 28px 60px; }

    /* ════ PAGE HEADER ════ */
    .page-header {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 28px;
      flex-wrap: wrap;
    }

    .page-header-left { display: flex; flex-direction: column; gap: 4px; }

    .breadcrumb-line {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 6px;
    }
    .breadcrumb-line a { color: var(--teal-light); text-decoration: none; }
    .breadcrumb-line a:hover { text-decoration: underline; }
    .breadcrumb-line i { font-size: 10px; }

    .page-title {
      font-family: 'Instrument Serif', serif;
      font-size: 2rem;
      font-weight: 400;
      color: var(--white);
      line-height: 1.15;
    }
    .page-title em { font-style: italic; color: var(--teal-light); }

    .page-meta {
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* ════ SUMMARY CHIPS ════ */
    .summary-chips {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .chip {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      font-size: 13px;
    }
    .chip-num {
      font-family: 'Instrument Serif', serif;
      font-size: 20px;
      color: var(--teal-light);
      line-height: 1;
    }
    .chip-label { color: var(--text-muted); font-size: 11px; }

    /* ════ SEARCH BAR ════ */
    .search-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--navy-mid);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 20px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .search-field {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,0,0,.25);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 9px 14px;
      flex: 1;
      min-width: 180px;
      transition: border-color .2s;
    }
    .search-field:focus-within {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px var(--teal-glow);
    }
    .search-field i { color: var(--text-muted); font-size: 15px; flex-shrink: 0; }
    .search-field input {
      background: none;
      border: none;
      outline: none;
      color: var(--text-main);
      font-family: 'Sora', sans-serif;
      font-size: 13.5px;
      width: 100%;
    }
    .search-field input::placeholder { color: var(--text-muted); }

    .search-date {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,0,0,.25);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 9px 14px;
      transition: border-color .2s;
    }
    .search-date:focus-within {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px var(--teal-glow);
    }
    .search-date i { color: var(--text-muted); font-size: 15px; }
    .search-date input {
      background: none;
      border: none;
      outline: none;
      color: var(--text-main);
      font-family: 'Sora', sans-serif;
      font-size: 13.5px;
      color-scheme: dark;
    }

    .btn-search {
      display: flex; align-items: center; gap: 7px;
      padding: 10px 20px;
      background: var(--teal);
      color: white;
      border: none;
      border-radius: 8px;
      font-family: 'Sora', sans-serif;
      font-size: 13.5px;
      font-weight: 600;
      cursor: pointer;
      transition: background .18s, transform .15s;
      white-space: nowrap;
    }
    .btn-search:hover { background: #0f766e; transform: translateY(-1px); }

    .btn-reset {
      display: flex; align-items: center; gap: 7px;
      padding: 10px 16px;
      background: rgba(255,255,255,.05);
      color: var(--text-muted);
      border: 1px solid var(--border);
      border-radius: 8px;
      font-family: 'Sora', sans-serif;
      font-size: 13.5px;
      font-weight: 500;
      cursor: pointer;
      text-decoration: none;
      transition: background .18s, color .18s;
      white-space: nowrap;
    }
    .btn-reset:hover { background: var(--card-hover); color: var(--white); }

    /* ════ TABLE CARD ════ */
    .table-card {
      background: var(--navy-mid);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .table-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      background: rgba(13,148,136,.06);
      flex-wrap: wrap;
      gap: 10px;
    }

    .table-card-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      font-weight: 600;
      color: var(--teal-light);
    }

    .result-count {
      font-size: 12px;
      color: var(--text-muted);
      background: rgba(0,0,0,.2);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 3px 12px;
    }

    /* ════ TABLE ════ */
    .table-scroll { overflow-x: auto; }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12.5px;
      min-width: 1600px;
    }

    thead th {
      padding: 11px 14px;
      text-align: left;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border);
      background: rgba(0,0,0,.15);
      white-space: nowrap;
    }

    /* Sticky first cols */
    thead th:nth-child(1),
    thead th:nth-child(2) { position: sticky; left: 0; z-index: 2; background: #101f3a; }
    thead th:nth-child(2) { left: 50px; }
    tbody td:nth-child(1),
    tbody td:nth-child(2) { position: sticky; z-index: 1; background: var(--navy-mid); }
    tbody td:nth-child(1) { left: 0; }
    tbody td:nth-child(2) { left: 50px; }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover td { background: var(--card-hover) !important; }

    td {
      padding: 11px 14px;
      color: var(--text-main);
      vertical-align: middle;
      white-space: nowrap;
    }

    .empty { color: var(--text-muted); }

    /* Patient cell */
    .patient-cell {
      display: flex;
      align-items: center;
      gap: 9px;
      min-width: 160px;
    }
    .patient-avatar {
      width: 32px; height: 32px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      background: linear-gradient(135deg, #0ea5e9, var(--teal));
    }
    .patient-initials {
      width: 32px; height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0ea5e9, var(--teal));
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: white;
      flex-shrink: 0;
    }
    .patient-name { font-weight: 600; color: var(--white); }

    /* Vital chips */
    .vital {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(255,255,255,.05);
      border: 1px solid var(--border);
      border-radius: 6px;
      padding: 3px 8px;
      font-size: 11.5px;
      color: var(--text-main);
    }
    .vital.bp    { border-color: rgba(239,68,68,.3);   color: #fca5a5; }
    .vital.temp  { border-color: rgba(245,158,11,.3);  color: #fcd34d; }
    .vital.pulse { border-color: rgba(59,130,246,.3);  color: #93c5fd; }
    .vital.o2    { border-color: rgba(16,185,129,.3);  color: #6ee7b7; }
    .vital.bmi   { border-color: rgba(139,92,246,.3);  color: #c4b5fd; }
    .vital.sugar { border-color: rgba(236,72,153,.3);  color: #f9a8d4; }

    /* Text cells (notes, diagnosis etc.) */
    .text-cell {
      max-width: 200px;
      white-space: normal;
      word-break: break-word;
      line-height: 1.45;
      font-size: 12px;
      color: var(--text-muted);
    }

    /* Date badge */
    .date-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: var(--text-muted);
    }

    /* Action buttons */
    .actions { display: flex; align-items: center; gap: 5px; }

    .btn-action {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 5px 10px;
      border-radius: 7px;
      font-family: 'Sora', sans-serif;
      font-size: 11px;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      border: 1px solid transparent;
      transition: background .18s, border-color .18s, transform .12s;
      white-space: nowrap;
    }
    .btn-action:hover { transform: translateY(-1px); }

    .btn-edit   { background: rgba(245,158,11,.12); border-color: rgba(245,158,11,.3); color: var(--amber); }
    .btn-edit:hover { background: rgba(245,158,11,.22); border-color: rgba(245,158,11,.5); }

    .btn-delete { background: rgba(239,68,68,.1); border-color: rgba(239,68,68,.25); color: #f87171; }
    .btn-delete:hover { background: rgba(239,68,68,.2); border-color: rgba(239,68,68,.45); }

    .btn-print  { background: rgba(59,130,246,.1); border-color: rgba(59,130,246,.25); color: #93c5fd; }
    .btn-print:hover { background: rgba(59,130,246,.2); border-color: rgba(59,130,246,.45); }

    /* ════ EMPTY STATE ════ */
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 64px 20px;
      gap: 12px;
      color: var(--text-muted);
    }
    .empty-state i { font-size: 48px; opacity: .2; }
    .empty-state h3 { font-size: 16px; font-weight: 600; color: var(--text-main); }
    .empty-state p { font-size: 13px; }

    /* ════ PAGINATION ════ */
    .pagination-wrap {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 20px;
      border-top: 1px solid var(--border);
      flex-wrap: wrap;
      gap: 12px;
    }

    .pagination-info {
      font-size: 12.5px;
      color: var(--text-muted);
    }

    .pagination {
      display: flex;
      align-items: center;
      gap: 4px;
      list-style: none;
    }

    .page-item a, .page-item span {
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 32px;
      height: 32px;
      padding: 0 10px;
      border-radius: 7px;
      font-size: 12.5px;
      font-weight: 500;
      text-decoration: none;
      color: var(--text-muted);
      background: rgba(255,255,255,.04);
      border: 1px solid var(--border);
      transition: background .15s, color .15s, border-color .15s;
    }
    .page-item a:hover { background: var(--card-hover); color: var(--white); }
    .page-item.active a {
      background: var(--teal-glow);
      border-color: rgba(13,148,136,.4);
      color: var(--teal-light);
      font-weight: 700;
    }
    .page-item.disabled span { opacity: .35; cursor: not-allowed; }

    /* ════ RESPONSIVE ════ */
    @media (max-width: 768px) {
      .page { padding: 20px 16px 48px; }
      .page-header { gap: 14px; }
      .summary-chips { display: none; }
      .search-bar { padding: 12px; gap: 8px; }
      .search-field { min-width: 100%; }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- ── Page Header ───────────────────── -->
  <div class="page-header">
    <div class="page-header-left">
      <div class="breadcrumb-line">
        <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
        <i class="bi bi-chevron-right"></i>
        <span>Consultations</span>
      </div>
      <h1 class="page-title">Patient <em>Consultation</em> Records</h1>
      <p class="page-meta">
        <?php if ($search || $date): ?>
          Showing results for
          <?= $search ? '<strong>"'.htmlspecialchars($search).'"</strong>' : '' ?>
          <?= $date   ? ' on <strong>'.date('d M Y', strtotime($date)).'</strong>' : '' ?>
          &nbsp;·&nbsp; <?= $totalRows ?> record<?= $totalRows != 1 ? 's' : '' ?> found
        <?php else: ?>
          All consultation records &nbsp;·&nbsp; <?= $totalRows ?> total
        <?php endif; ?>
      </p>
    </div>
    <div class="summary-chips">
      <div class="chip">
        <div>
          <div class="chip-num"><?= $totalRows ?></div>
          <div class="chip-label">Total Records</div>
        </div>
      </div>
      <div class="chip">
        <div>
          <div class="chip-num"><?= $totalPages ?></div>
          <div class="chip-label">Pages</div>
        </div>
      </div>
      <div class="chip">
        <div>
          <div class="chip-num"><?= $page ?></div>
          <div class="chip-label">Current Page</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Search Bar ────────────────────── -->
  <form class="search-bar" method="GET">
    <div class="search-field">
      <i class="bi bi-search"></i>
      <input type="text" name="search"
             placeholder="Search by patient name…"
             value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="search-date">
      <i class="bi bi-calendar3"></i>
      <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
    </div>
    <button type="submit" class="btn-search">
      <i class="bi bi-funnel"></i> Filter
    </button>
    <a href="consultation_list.php" class="btn-reset">
      <i class="bi bi-x-circle"></i> Reset
    </a>
  </form>

  <!-- ── Table Card ───────────────────── -->
  <div class="table-card">
    <div class="table-card-header">
      <div class="table-card-title">
        <i class="bi bi-clipboard2-pulse"></i>
        Consultations
      </div>
      <span class="result-count">
        <?= count($consultations) ?> of <?= $totalRows ?> records
        &nbsp;·&nbsp; Page <?= $page ?> / <?= max($totalPages,1) ?>
      </span>
    </div>

    <?php if (count($consultations) > 0): ?>
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Patient</th>
            <th>Vitals</th>
            <th>Resp. Rate</th>
            <th>O₂ Sat</th>
            <th>Pain</th>
            <th>Height</th>
            <th>Weight</th>
            <th>BMI</th>
            <th>Blood Sugar</th>
            <th>AVPU</th>
            <th>Vitals Time</th>
            <th>Symptoms</th>
            <th>Complaint</th>
            <th>Examination</th>
            <th>Diagnosis</th>
            <th>Investigations</th>
            <th>Treatment Plan</th>
            <th>Doctor Signature</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($consultations as $i => $row):
            $name     = $row['full_name'] ?? 'Unknown';
            $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $name)))));
            $initials = substr($initials, 0, 2);
          ?>
          <tr>
            <!-- # -->
            <td style="color:var(--text-muted);"><?= $offset + $i + 1 ?></td>

            <!-- Patient -->
            <td>
              <div class="patient-cell">
                <?php if (!empty($row['photo'])): ?>
                  <img src="../uploads/<?= htmlspecialchars($row['photo']) ?>"
                       class="patient-avatar" alt="">
                <?php else: ?>
                  <div class="patient-initials"><?= $initials ?></div>
                <?php endif; ?>
                <span class="patient-name"><?= htmlspecialchars($name) ?></span>
              </div>
            </td>

            <!-- Vitals (BP / Temp / Pulse grouped) -->
            <td>
              <div style="display:flex; gap:4px; flex-wrap:wrap;">
                <?php if (!empty($row['bp'])): ?>
                  <span class="vital bp"><i class="bi bi-heart-pulse"></i><?= htmlspecialchars($row['bp']) ?></span>
                <?php endif; ?>
                <?php if (!empty($row['temperature'])): ?>
                  <span class="vital temp"><i class="bi bi-thermometer-half"></i><?= htmlspecialchars($row['temperature']) ?>°</span>
                <?php endif; ?>
                <?php if (!empty($row['pulse'])): ?>
                  <span class="vital pulse"><i class="bi bi-activity"></i><?= htmlspecialchars($row['pulse']) ?> bpm</span>
                <?php endif; ?>
                <?php if (empty($row['bp']) && empty($row['temperature']) && empty($row['pulse'])): ?>
                  <span class="empty">—</span>
                <?php endif; ?>
              </div>
            </td>

            <td><?= val($row['respiratory_rate']) ?></td>

            <td>
              <?php if (!empty($row['oxygen_saturation'])): ?>
                <span class="vital o2"><i class="bi bi-lungs"></i><?= htmlspecialchars($row['oxygen_saturation']) ?>%</span>
              <?php else: ?><span class="empty">—</span><?php endif; ?>
            </td>

            <td><?= val($row['pain_level']) ?></td>
            <td><?= !empty($row['height_cm']) ? htmlspecialchars($row['height_cm']).' cm' : '<span class="empty">—</span>' ?></td>
            <td><?= !empty($row['weight_kg']) ? htmlspecialchars($row['weight_kg']).' kg' : '<span class="empty">—</span>' ?></td>

            <td>
              <?php if (!empty($row['bmi'])): ?>
                <span class="vital bmi"><?= htmlspecialchars($row['bmi']) ?></span>
              <?php else: ?><span class="empty">—</span><?php endif; ?>
            </td>

            <td>
              <?php if (!empty($row['blood_sugar'])): ?>
                <span class="vital sugar"><?= htmlspecialchars($row['blood_sugar']) ?></span>
              <?php else: ?><span class="empty">—</span><?php endif; ?>
            </td>

            <td><?= val($row['consciousness_level']) ?></td>

            <td>
              <span class="date-badge">
                <i class="bi bi-clock"></i>
                <?= htmlspecialchars($row['vitals_time'] ?? '—') ?>
              </span>
            </td>

            <td class="text-cell"><?= nl2br(htmlspecialchars($row['symptoms_notes'] ?? '')) ?: '<span class="empty">—</span>' ?></td>
            <td class="text-cell"><?= nl2br(htmlspecialchars($row['chief_complaint'] ?? '')) ?: '<span class="empty">—</span>' ?></td>
            <td class="text-cell"><?= nl2br(htmlspecialchars($row['physical_exam'] ?? '')) ?: '<span class="empty">—</span>' ?></td>
            <td class="text-cell"><?= nl2br(htmlspecialchars($row['diagnosis'] ?? '')) ?: '<span class="empty">—</span>' ?></td>
            <td class="text-cell"><?= nl2br(htmlspecialchars($row['investigations'] ?? '')) ?: '<span class="empty">—</span>' ?></td>
            <td class="text-cell"><?= nl2br(htmlspecialchars($row['treatment_plan'] ?? '')) ?: '<span class="empty">—</span>' ?></td>
            <td><?= val($row['doctor_signature']) ?></td>

            <td>
              <span class="date-badge">
                <i class="bi bi-calendar3"></i>
                <?= htmlspecialchars($row['consultation_date'] ?? '—') ?>
              </span>
            </td>

            <!-- Actions -->
            <td>
              <div class="actions">
                <a href="edit_consultation.php?patient_id=<?= $row['patient_id'] ?>"
                   class="btn-action btn-edit">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="delete_consultation.php?patient_id=<?= $row['patient_id'] ?>"
                   class="btn-action btn-delete"
                   onclick="return confirm('Delete this consultation? This cannot be undone.')">
                  <i class="bi bi-trash3"></i> Delete
                </a>
                <a href="print_full_consultation.php?patient_id=<?= $row['patient_id'] ?>"
                   class="btn-action btn-print"
                   target="_blank">
                  <i class="bi bi-printer"></i> Print
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ── Pagination ──────────────────── -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrap">
      <span class="pagination-info">
        Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> records
      </span>
      <ul class="pagination">

        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          <?php else: ?>
            <span><i class="bi bi-chevron-left"></i></span>
          <?php endif; ?>
        </li>

        <?php
          // Smart pagination: show at most 7 page links
          $start = max(1, $page - 3);
          $end   = min($totalPages, $page + 3);
          if ($start > 1): ?>
            <li class="page-item">
              <a href="?page=1&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">1</a>
            </li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span>…</span></li><?php endif; ?>
          <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span>…</span></li><?php endif; ?>
          <li class="page-item">
            <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>"><?= $totalPages ?></a>
          </li>
        <?php endif; ?>

        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($date) ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          <?php else: ?>
            <span><i class="bi bi-chevron-right"></i></span>
          <?php endif; ?>
        </li>

      </ul>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-clipboard2-x"></i>
      <h3>No Consultations Found</h3>
      <p>
        <?= ($search || $date) ? 'Try adjusting your search filters.' : 'No consultation records have been added yet.' ?>
      </p>
      <?php if ($search || $date): ?>
        <a href="consultation_list.php" class="btn-reset" style="margin-top:8px;">
          <i class="bi bi-x-circle"></i> Clear Filters
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div><!-- /table-card -->

</div><!-- /page -->

</body>
</html>