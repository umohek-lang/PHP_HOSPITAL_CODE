<?php
require '../payment_alerts.php';
require '../includes/auth.php';
require '../db.php';

$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$searchValue = '';
$statusValue = '';
$whereClauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $searchValue = trim($_GET['search']);
    $whereClauses[] = "(full_name LIKE :search OR patient_pin LIKE :search OR email LIKE :search OR phone LIKE :search OR patient_status LIKE :search OR created_at LIKE :search)";
    $params[':search'] = '%' . $searchValue . '%';
}
if (!empty($_GET['status'])) {
    $statusValue = $_GET['status'];
    $whereClauses[] = "patient_status = :status";
    $params[':status'] = $statusValue;
}
$searchQuery = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$stmt = $pdo->prepare("SELECT * FROM patients" . $searchQuery . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM patients" . $searchQuery);
foreach ($params as $key => $value) $countStmt->bindValue($key, $value, PDO::PARAM_STR);
$countStmt->execute();
$totalPatients = $countStmt->fetchColumn();
$totalPages = (int)ceil($totalPatients / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Patients — Angelora Hospital</title>
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
      --green-600: #059669; --green-50: #ecfdf5; --green-100: #d1fae5; --green-700: #047857;
      --amber-500: #f59e0b; --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --red-600: #dc2626; --red-50: #fef2f2; --red-100: #fee2e2; --red-700: #b91c1c;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.14), 0 4px 12px rgba(15,45,107,.08);
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
      position: sticky; top: 0; z-index: 100;
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
      font-size: 1rem; color: #fff;
      box-shadow: 0 3px 10px rgba(37,99,235,.25);
    }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 1.05rem; color: var(--blue-800); }
    .brand-sep  { color: var(--gray-300); margin: 0 2px; }
    .brand-page { font-size: .78rem; color: var(--blue-600); font-weight: 600; }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .topbar-date {
      font-size: .72rem; color: var(--gray-400);
      padding: 4px 11px; background: var(--gray-100); border-radius: 999px; border: 1px solid var(--gray-200);
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 15px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: .75rem; font-weight: 600; text-decoration: none;
      transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── PAGE ── */
    .page { position: relative; z-index: 1; max-width: 1280px; margin: 0 auto; padding: 32px 28px 56px; }

    /* ── PAGE HEADER ── */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      gap: 16px; flex-wrap: wrap; margin-bottom: 26px;
    }
    .ph-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 4px 12px;
      font-size: .65rem; font-weight: 700; color: var(--blue-700);
      text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px;
    }
    .ph-title { font-family: 'Instrument Serif', serif; font-size: 1.6rem; font-weight: 400; color: var(--gray-900); }
    .ph-title em { font-style: italic; color: var(--blue-600); }
    .ph-sub   { font-size: .78rem; color: var(--gray-400); margin-top: 4px; }
    .ph-stat  {
      display: flex; align-items: center; gap: 8px;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 12px; padding: 12px 18px; box-shadow: var(--shadow-sm);
      flex-shrink: 0;
    }
    .ph-stat-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: var(--blue-50); display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: var(--blue-600);
    }
    .ph-stat-num { font-family: 'Instrument Serif', serif; font-size: 1.5rem; color: var(--gray-900); line-height: 1; }
    .ph-stat-lbl { font-size: .65rem; color: var(--gray-400); text-transform: uppercase; letter-spacing: .07em; margin-top: 2px; }

    /* ── SEARCH BAR ── */
    .search-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; padding: 18px 22px; margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
    }
    .search-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .search-wrap {
      flex: 1; min-width: 220px; position: relative;
    }
    .search-icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: .9rem; pointer-events: none;
    }
    .search-input {
      width: 100%; height: 40px; padding: 0 14px 0 36px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; font-family: 'Sora', sans-serif;
      font-size: .82rem; color: var(--gray-700); outline: none;
      transition: border-color .18s, box-shadow .18s;
    }
    .search-input::placeholder { color: var(--gray-300); }
    .search-input:focus { border-color: var(--blue-400); box-shadow: 0 0 0 3px var(--blue-glow); background: var(--white); }

    .status-select {
      height: 40px; padding: 0 32px 0 13px; min-width: 160px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; font-family: 'Sora', sans-serif;
      font-size: .82rem; color: var(--gray-700); outline: none; cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 10px center;
      transition: border-color .18s;
    }
    .status-select:focus { border-color: var(--blue-400); box-shadow: 0 0 0 3px var(--blue-glow); }

    .btn-search {
      height: 40px; padding: 0 20px;
      background: var(--blue-600); border: none; border-radius: 9px;
      color: #fff; font-family: 'Sora', sans-serif;
      font-size: .82rem; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; gap: 6px;
      box-shadow: 0 3px 10px rgba(37,99,235,.25);
      transition: all .18s; flex-shrink: 0;
    }
    .btn-search:hover { background: var(--blue-700); transform: translateY(-1px); box-shadow: 0 5px 16px rgba(37,99,235,.35); }

    .btn-clear {
      height: 40px; padding: 0 15px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 9px; color: var(--gray-500); font-family: 'Sora', sans-serif;
      font-size: .78rem; font-weight: 600; cursor: pointer; text-decoration: none;
      display: flex; align-items: center; gap: 5px; transition: all .16s; flex-shrink: 0;
    }
    .btn-clear:hover { background: var(--gray-200); color: var(--gray-700); }

    /* active filter chips */
    .filter-chips { display: flex; gap: 7px; margin-top: 12px; flex-wrap: wrap; }
    .filter-chip {
      display: flex; align-items: center; gap: 5px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 3px 11px;
      font-size: .68rem; font-weight: 600; color: var(--blue-700);
    }

    /* ── PATIENTS GRID ── */
    .patients-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 18px;
    }

    /* ── PATIENT CARD ── */
    .patient-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 16px; overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: transform .22s cubic-bezier(.16,1,.3,1), box-shadow .22s, border-color .22s;
      display: flex; flex-direction: column;
    }
    .patient-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
      border-color: var(--blue-200);
    }

    /* photo */
    .card-photo-wrap {
      position: relative; height: 160px; overflow: hidden;
      background: linear-gradient(135deg, var(--blue-50), var(--blue-100));
    }
    .card-photo {
      width: 100%; height: 100%; object-fit: cover; display: block;
      transition: transform .3s;
    }
    .patient-card:hover .card-photo { transform: scale(1.03); }

    /* fallback avatar */
    .card-avatar-fallback {
      width: 100%; height: 100%;
      display: flex; align-items: center; justify-content: center;
    }
    .avatar-initials {
      width: 72px; height: 72px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; font-weight: 700; color: #fff;
      box-shadow: 0 4px 18px rgba(37,99,235,.3);
    }

    /* status badge on photo */
    .status-badge-photo {
      position: absolute; top: 10px; right: 10px;
      font-size: .62rem; font-weight: 700; padding: 3px 10px;
      border-radius: 999px; letter-spacing: .05em;
    }
    .badge-inpatient  { background: rgba(37,99,235,.9);  color: #fff; }
    .badge-outpatient { background: rgba(5,150,105,.9);  color: #fff; }
    .badge-default    { background: rgba(100,116,139,.85); color: #fff; }

    /* card body */
    .card-body {
      padding: 16px 18px; flex: 1;
    }
    .card-name { font-size: .95rem; font-weight: 700; color: var(--gray-900); margin-bottom: 10px; }

    .info-row {
      display: flex; align-items: center; gap: 7px;
      font-size: .76rem; color: var(--gray-500); margin-bottom: 5px;
    }
    .info-row i { color: var(--blue-400); font-size: .8rem; flex-shrink: 0; }
    .info-row span { font-weight: 500; color: var(--gray-700); }

    /* card footer */
    .card-footer {
      padding: 12px 18px;
      background: var(--gray-50); border-top: 1px solid var(--gray-100);
      display: flex; gap: 8px;
    }
    .btn-edit {
      flex: 1; padding: 7px 0; border-radius: 8px; text-align: center;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-700); font-family: 'Sora', sans-serif;
      font-size: .76rem; font-weight: 700; text-decoration: none;
      display: flex; align-items: center; justify-content: center; gap: 5px;
      transition: all .16s;
    }
    .btn-edit:hover { background: var(--blue-600); color: #fff; border-color: var(--blue-600); }
    .btn-delete {
      flex: 1; padding: 7px 0; border-radius: 8px; text-align: center;
      background: var(--red-50); border: 1px solid var(--red-100);
      color: var(--red-700); font-family: 'Sora', sans-serif;
      font-size: .76rem; font-weight: 700; text-decoration: none;
      display: flex; align-items: center; justify-content: center; gap: 5px;
      transition: all .16s;
    }
    .btn-delete:hover { background: var(--red-600); color: #fff; border-color: var(--red-600); }

    /* ── EMPTY STATE ── */
    .empty-state {
      grid-column: 1 / -1; text-align: center; padding: 60px 20px;
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 16px; box-shadow: var(--shadow-sm);
    }
    .empty-state i { font-size: 3rem; color: var(--blue-200); display: block; margin-bottom: 14px; }
    .empty-state h3 { font-size: .95rem; font-weight: 700; color: var(--gray-700); margin-bottom: 5px; }
    .empty-state p  { font-size: .8rem; color: var(--gray-400); }

    /* ── PAGINATION ── */
    .pagination-wrap {
      display: flex; align-items: center; justify-content: space-between;
      margin-top: 28px; flex-wrap: wrap; gap: 12px;
    }
    .pagination-info { font-size: .75rem; color: var(--gray-400); font-weight: 500; }
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
    .page-item.active a {
      background: var(--blue-600); border-color: var(--blue-600);
      color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.28);
    }
    .page-item.disabled span { opacity: .35; cursor: not-allowed; }

    /* ── RESPONSIVE ── */
    @media (max-width: 640px) {
      .topbar { padding: 0 16px; }
      .topbar-date { display: none; }
      .page { padding: 20px 14px 48px; }
      .search-row { flex-direction: column; }
      .search-wrap, .status-select, .btn-search { width: 100%; }
      .ph-stat { display: none; }
    }
  </style>
</head>
<body>

<!-- ── TOPBAR ── -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="brand-name">Angelora</span>
    <span class="brand-sep">·</span>
    <span class="brand-page">Patients</span>
  </div>
  <div class="topbar-right">
    <span class="topbar-date"><i class="bi bi-calendar3" style="margin-right:4px"></i><?= date('d M Y') ?></span>
    <a href="../index.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div>
      <div class="ph-eyebrow"><i class="bi bi-people-fill"></i> Patient Registry</div>
      <div class="ph-title">All <em>Patients</em></div>
      <div class="ph-sub">Browse, search and manage all registered patients.</div>
    </div>
    <div class="ph-stat">
      <div class="ph-stat-icon"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="ph-stat-num"><?= number_format($totalPatients) ?></div>
        <div class="ph-stat-lbl">Total Patients</div>
      </div>
    </div>
  </div>

  <!-- SEARCH CARD -->
  <div class="search-card">
    <form method="GET">
      <div class="search-row">
        <div class="search-wrap">
          <i class="bi bi-search search-icon"></i>
          <input type="text" name="search" class="search-input"
            placeholder="Search by name, PIN, email, phone or date…"
            value="<?= htmlspecialchars($searchValue) ?>">
        </div>
        <select name="status" class="status-select">
          <option value="">All Statuses</option>
          <option value="Inpatient"  <?= $statusValue==='Inpatient'  ? 'selected':'' ?>>Inpatient</option>
          <option value="Outpatient" <?= $statusValue==='Outpatient' ? 'selected':'' ?>>Outpatient</option>
        </select>
        <button type="submit" class="btn-search">
          <i class="bi bi-search"></i> Search
        </button>
        <?php if ($searchValue || $statusValue): ?>
          <a href="patients.php" class="btn-clear">
            <i class="bi bi-x-lg"></i> Clear
          </a>
        <?php endif; ?>
      </div>
      <?php if ($searchValue || $statusValue): ?>
      <div class="filter-chips">
        <?php if ($searchValue): ?>
          <span class="filter-chip"><i class="bi bi-search"></i> "<?= htmlspecialchars($searchValue) ?>"</span>
        <?php endif; ?>
        <?php if ($statusValue): ?>
          <span class="filter-chip"><i class="bi bi-funnel-fill"></i> <?= htmlspecialchars($statusValue) ?></span>
        <?php endif; ?>
        <span class="filter-chip" style="background:var(--green-50);border-color:var(--green-100);color:var(--green-700)">
          <i class="bi bi-check-circle-fill"></i> <?= $totalPatients ?> result<?= $totalPatients !== 1 ? 's' : '' ?>
        </span>
      </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- PATIENTS GRID -->
  <div class="patients-grid">
    <?php if (count($patients) > 0): ?>
      <?php foreach ($patients as $p):
        $name     = $p['full_name'] ?? '';
        $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $name)))));
        $initials = substr($initials, 0, 2);
        $photo    = !empty($p['photo']) ? '../' . ltrim($p['photo'], '/') : '';
        $status   = $p['patient_status'] ?? '';
        $badgeCls = $status === 'Inpatient' ? 'badge-inpatient' : ($status === 'Outpatient' ? 'badge-outpatient' : 'badge-default');
      ?>
      <div class="patient-card">

        <!-- Photo / Avatar -->
        <div class="card-photo-wrap">
          <?php if ($photo && file_exists($photo)): ?>
            <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($name) ?>" class="card-photo">
          <?php else: ?>
            <div class="card-avatar-fallback">
              <div class="avatar-initials"><?= htmlspecialchars($initials) ?></div>
            </div>
          <?php endif; ?>
          <?php if ($status): ?>
            <span class="status-badge-photo <?= $badgeCls ?>"><?= htmlspecialchars($status) ?></span>
          <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="card-body">
          <div class="card-name"><?= htmlspecialchars($name) ?></div>
          <?php if (!empty($p['gender'])): ?>
          <div class="info-row">
            <i class="bi bi-person-fill"></i>
            <span><?= htmlspecialchars($p['gender']) ?></span>
            <?php if (!empty($p['age'])): ?> &nbsp;·&nbsp; <span><?= htmlspecialchars($p['age']) ?> yrs</span><?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($p['phone'])): ?>
          <div class="info-row">
            <i class="bi bi-telephone-fill"></i>
            <span><?= htmlspecialchars($p['phone']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($p['email'])): ?>
          <div class="info-row">
            <i class="bi bi-envelope-fill"></i>
            <span><?= htmlspecialchars($p['email']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($p['patient_pin'])): ?>
          <div class="info-row">
            <i class="bi bi-key-fill"></i>
            <span style="font-family:monospace;font-size:.74rem;"><?= htmlspecialchars($p['patient_pin']) ?></span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="card-footer">
          <a href="edit_patient.php?id=<?= htmlspecialchars($p['patient_id']) ?>" class="btn-edit">
            <i class="bi bi-pencil-fill"></i> Edit
          </a>
          <a href="delete_patient.php?id=<?= htmlspecialchars($p['patient_id']) ?>"
             class="btn-delete"
             onclick="return confirm('Delete <?= htmlspecialchars(addslashes($name)) ?>? This cannot be undone.')">
            <i class="bi bi-trash3-fill"></i> Delete
          </a>
        </div>

      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <i class="bi bi-person-x"></i>
        <h3>No Patients Found</h3>
        <p><?= ($searchValue || $statusValue) ? 'Try adjusting your search or filter.' : 'No patient records have been added yet.' ?></p>
      </div>
    <?php endif; ?>
  </div>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination-wrap">
    <span class="pagination-info">
      Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $totalPatients) ?> of <?= number_format($totalPatients) ?> patients
    </span>
    <ul class="pagination">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page-1 ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        <?php else: ?><span><i class="bi bi-chevron-left"></i></span><?php endif; ?>
      </li>
      <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        if ($start > 1) { echo '<li class="page-item"><a href="?page=1&search='.urlencode($searchValue).'&status='.urlencode($statusValue).'">1</a></li>'; }
        if ($start > 2) { echo '<li class="page-item disabled"><span>…</span></li>'; }
        for ($i = $start; $i <= $end; $i++):
      ?>
      <li class="page-item <?= $i == $page ? 'active' : '' ?>">
        <a href="?page=<?= $i ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>"><?= $i ?></a>
      </li>
      <?php endfor;
        if ($end < $totalPages - 1) { echo '<li class="page-item disabled"><span>…</span></li>'; }
        if ($end < $totalPages) { echo '<li class="page-item"><a href="?page='.$totalPages.'&search='.urlencode($searchValue).'&status='.urlencode($statusValue).'">'.$totalPages.'</a></li>'; }
      ?>
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page+1 ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        <?php else: ?><span><i class="bi bi-chevron-right"></i></span><?php endif; ?>
      </li>
    </ul>
  </div>
  <?php endif; ?>

</div>
</body>
</html>