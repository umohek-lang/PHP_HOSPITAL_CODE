<?php
require '../includes/auth.php';
require '../db.php';

try {
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=ablehand", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Handle delete
    if (isset($_POST['delete_id'])) {
        $del = $pdo->prepare("DELETE FROM lab_tests WHERE lab_test_id = ?");
        $del->execute([$_POST['delete_id']]);
        header("Location: view_lab_test.php?deleted=1");
        exit();
    }

    $limit  = 10;
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    $sq     = "%$search%";

    $stmt = $pdo->prepare("
        SELECT l.*, p.full_name
        FROM lab_tests l
        LEFT JOIN patients p ON l.patient_id = p.patient_id
        WHERE l.test_name LIKE :s OR l.patient_id LIKE :s
        ORDER BY l.lab_test_id DESC
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindParam(':s',   $sq,     PDO::PARAM_STR);
    $stmt->bindParam(':lim', $limit,  PDO::PARAM_INT);
    $stmt->bindParam(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $lab_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM lab_tests WHERE test_name LIKE :s OR patient_id LIKE :s");
    $cnt->bindParam(':s', $sq, PDO::PARAM_STR);
    $cnt->execute();
    $total_tests = $cnt->fetchColumn();
    $total_pages = max(1, (int)ceil($total_tests / $limit));

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Result decode helper
function decodeResult($raw) {
    $data = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        $lines = [];
        foreach ($data as $k => $v) {
            if ($v !== '' && $v !== null) $lines[] = "<span class='res-key'>$k:</span> " . htmlspecialchars($v);
        }
        return implode('<br>', $lines);
    }
    return htmlspecialchars($raw);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lab Test Results — Angelora</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-800: #1e40af; --blue-700: #1d4ed8; --blue-600: #2563eb;
      --blue-500: #3b82f6; --blue-400: #60a5fa; --blue-300: #93c5fd;
      --blue-200: #bfdbfe; --blue-100: #dbeafe; --blue-50:  #eff6ff;

      --white:    #ffffff;
      --gray-50:  #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
      --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
      --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;

      --green:     #16a34a; --green-bg:  #dcfce7; --green-100: #bbf7d0;
      --amber:     #d97706; --amber-bg:  #fef3c7; --amber-100: #fde68a;
      --red:       #dc2626; --red-bg:    #fef2f2; --red-100:   #fecaca;

      --radius: 12px;
      --sh-sm: 0 1px 3px rgba(0,0,0,.06), 0 2px 8px rgba(0,0,0,.04);
      --sh:    0 4px 16px rgba(37,99,235,.09), 0 1px 4px rgba(0,0,0,.05);
      --sh-lg: 0 12px 40px rgba(37,99,235,.14), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body { min-height: 100vh; font-family: 'Sora', sans-serif; background: var(--gray-50); color: var(--gray-800); }
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 8px; }

    /* ── TOPBAR ─────────────────────── */
    .topbar {
      position: sticky; top: 0; z-index: 100; height: 62px;
      background: var(--white); border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--sh-sm);
      display: flex; align-items: center; justify-content: space-between; padding: 0 36px;
    }
    .tb-brand { display: flex; align-items: center; gap: 11px; }
    .tb-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .tb-mark i  { font-size: 17px; color: white; }
    .tb-name    { font-family: 'Instrument Serif', serif; font-size: 17px; color: var(--gray-900); line-height: 1; }
    .tb-sub     { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue-600); }
    .tb-right   { display: flex; align-items: center; gap: 10px; }
    .date-pill  {
      display: flex; align-items: center; gap: 6px; padding: 5px 13px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      font-size: 11.5px; color: var(--blue-700); font-weight: 500;
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200); color: var(--gray-600);
      font-family: 'Sora', sans-serif; font-size: 12.5px; font-weight: 500;
      text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-600); }

    /* ── PAGE ───────────────────────── */
    .page { max-width: 1280px; margin: 0 auto; padding: 36px 24px 72px; }

    .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--gray-400); margin-bottom: 12px; }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }

    .page-title { font-family: 'Instrument Serif', serif; font-size: clamp(1.5rem, 3vw, 2rem); color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-top: 5px; margin-bottom: 24px; }

    /* ── ALERTS ─────────────────────── */
    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 18px; border-radius: 10px; margin-bottom: 18px;
      font-size: 13.5px; font-weight: 600; animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-6px);} to{opacity:1;transform:none;} }
    .alert-success { background: var(--green-bg); border: 1px solid var(--green-100); color: var(--green); }
    .alert-warning { background: var(--amber-bg); border: 1px solid var(--amber-100); color: var(--amber); }

    /* ── TOOLBAR ────────────────────── */
    .toolbar {
      display: flex; align-items: center; justify-content: space-between;
      gap: 14px; flex-wrap: wrap; margin-bottom: 18px;
    }
    .toolbar-left  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .toolbar-right { display: flex; align-items: center; gap: 8px; }

    .search-form { display: flex; align-items: center; gap: 0; }
    .search-input {
      padding: 8px 14px; border-radius: 8px 0 0 8px;
      border: 1.5px solid var(--gray-200); border-right: none;
      font-family: 'Sora', sans-serif; font-size: 13px; color: var(--gray-800);
      background: var(--white); outline: none; min-width: 260px;
      transition: border-color .18s;
    }
    .search-input:focus { border-color: var(--blue-400); }
    .search-input::placeholder { color: var(--gray-400); }
    .btn-search {
      padding: 8px 16px; border-radius: 0 8px 8px 0;
      background: var(--blue-600); border: 1.5px solid var(--blue-600);
      color: white; font-family: 'Sora', sans-serif; font-size: 13px;
      font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;
      transition: opacity .15s;
    }
    .btn-search:hover { opacity: .9; }

    .results-count {
      font-size: 12.5px; color: var(--gray-500);
      background: var(--gray-100); border: 1px solid var(--gray-200);
      padding: 6px 14px; border-radius: 20px;
    }
    .results-count strong { color: var(--gray-800); }

    .btn-add {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 18px; border-radius: 8px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      color: white; font-family: 'Sora', sans-serif; font-size: 13px;
      font-weight: 700; text-decoration: none;
      box-shadow: 0 2px 8px rgba(37,99,235,.25); transition: all .18s;
    }
    .btn-add:hover { opacity: .9; transform: translateY(-1px); color: white; }

    /* ── TABLE CARD ─────────────────── */
    .table-card {
      background: var(--white); border: 1.5px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden; box-shadow: var(--sh);
    }

    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 900px; }

    thead th {
      padding: 11px 14px; text-align: left;
      font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: white; background: linear-gradient(135deg, var(--blue-800), var(--blue-600));
      white-space: nowrap;
    }
    thead th:first-child { border-radius: 0; }

    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .1s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--blue-50); }
    td { padding: 12px 14px; color: var(--gray-700); vertical-align: middle; }

    /* Patient cell */
    .patient-cell { display: flex; align-items: center; gap: 9px; }
    .p-avatar {
      width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; color: white; letter-spacing: .04em;
    }
    .p-name { font-weight: 600; color: var(--gray-800); font-size: 13px; line-height: 1.2; }
    .p-id   { font-size: 11px; color: var(--gray-400); }

    /* Test name */
    .test-name { font-weight: 600; color: var(--gray-800); }

    /* Result cell */
    .result-cell {
      max-width: 220px; max-height: 80px; overflow-y: auto;
      font-size: 11.5px; line-height: 1.6;
    }
    .res-key { color: var(--blue-600); font-weight: 600; }

    /* Status pills */
    .pill {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
    }
    .pill-completed { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-100);  }
    .pill-pending   { background: var(--amber-bg);  color: var(--amber);  border: 1px solid var(--amber-100);  }
    .pill-progress  { background: var(--blue-50);   color: var(--blue-700); border: 1px solid var(--blue-100); }

    /* Report link */
    .report-link {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 11.5px; color: var(--blue-600); text-decoration: none; font-weight: 600;
    }
    .report-link:hover { text-decoration: underline; }
    .no-report { font-size: 11.5px; color: var(--gray-400); }

    /* Action buttons */
    .action-cell { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .btn-act {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 5px 11px; border-radius: 7px; border: none;
      font-family: 'Sora', sans-serif; font-size: 11.5px; font-weight: 700;
      cursor: pointer; text-decoration: none; transition: all .15s; white-space: nowrap;
    }
    .btn-print { background: var(--gray-100); color: var(--gray-700); border: 1.5px solid var(--gray-200); }
    .btn-print:hover { background: var(--gray-200); color: var(--gray-900); }
    .btn-email { background: var(--blue-50);  color: var(--blue-700); border: 1.5px solid var(--blue-100); }
    .btn-email:hover { background: var(--blue-100); }
    .btn-delete { background: var(--red-bg);  color: var(--red);      border: 1.5px solid var(--red-100);  }
    .btn-delete:hover { background: var(--red-100); }

    /* ── EMPTY STATE ────────────────── */
    .empty-state {
      display: flex; flex-direction: column; align-items: center; gap: 10px;
      padding: 60px 24px; color: var(--gray-400); text-align: center;
    }
    .empty-state i { font-size: 36px; opacity: .25; }
    .empty-state p { font-size: 14px; }

    /* ── PAGINATION ─────────────────── */
    .pag-wrap {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; border-top: 1px solid var(--gray-100);
      background: var(--gray-50); flex-wrap: wrap; gap: 10px;
    }
    .pag-info  { font-size: 12px; color: var(--gray-500); }
    .pag-links { display: flex; align-items: center; gap: 4px; }
    .pag-btn {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 34px; height: 34px; border-radius: 8px; padding: 0 10px;
      font-family: 'Sora', sans-serif; font-size: 12.5px; font-weight: 600;
      text-decoration: none; border: 1.5px solid var(--gray-200);
      background: var(--white); color: var(--gray-600); transition: all .15s;
    }
    .pag-btn:hover   { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-700); }
    .pag-btn.active  { background: var(--blue-600); border-color: var(--blue-600); color: white; pointer-events: none; }
    .pag-btn.disabled{ opacity: .4; pointer-events: none; }
    .pag-dots { font-size: 13px; color: var(--gray-400); padding: 0 2px; line-height: 34px; }

    /* ── DELETE MODAL ───────────────── */
    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(0,0,0,.45);
      z-index: 400; display: none; align-items: center; justify-content: center;
    }
    .modal-backdrop.open { display: flex; }
    .modal-box {
      background: var(--white); border-radius: 16px;
      width: 100%; max-width: 420px; margin: 20px;
      overflow: hidden; box-shadow: var(--sh-lg);
      animation: popIn .22s cubic-bezier(.16,1,.3,1);
    }
    @keyframes popIn { from{opacity:0;transform:scale(.96) translateY(10px);} to{opacity:1;transform:none;} }
    .modal-head {
      padding: 18px 22px; background: var(--red-bg);
      border-bottom: 1px solid var(--red-100);
      display: flex; align-items: center; gap: 10px;
    }
    .modal-head-icon {
      width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
      background: var(--red-100); display: flex; align-items: center; justify-content: center;
    }
    .modal-head-icon i { font-size: 17px; color: var(--red); }
    .modal-head-title { font-size: 15px; font-weight: 700; color: var(--red); }
    .modal-head-sub   { font-size: 12px; color: #b91c1c; margin-top: 1px; }
    .modal-body-inner { padding: 20px 22px; font-size: 13.5px; color: var(--gray-700); line-height: 1.6; }
    .modal-body-inner strong { color: var(--gray-900); }
    .modal-footer {
      padding: 14px 22px; border-top: 1px solid var(--gray-100);
      display: flex; justify-content: flex-end; gap: 10px;
    }
    .btn-modal-cancel {
      padding: 8px 20px; border-radius: 8px;
      background: var(--gray-100); border: 1.5px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s;
    }
    .btn-modal-cancel:hover { background: var(--gray-200); }
    .btn-modal-delete {
      padding: 8px 20px; border-radius: 8px;
      background: var(--red); border: none; color: white;
      font-family: 'Sora', sans-serif; font-size: 13px;
      font-weight: 700; cursor: pointer; transition: opacity .15s;
    }
    .btn-modal-delete:hover { opacity: .88; }

    @media (max-width: 768px) {
      .topbar { padding: 0 16px; } .date-pill { display: none; }
      .page { padding: 20px 14px 52px; }
      .search-input { min-width: 160px; }
    }
  </style>
</head>
<body>

<!-- ── TOPBAR ──────────────────────────────── -->
<header class="topbar">
  <div class="tb-brand">
    <div class="tb-mark"><i class="bi bi-hospital"></i></div>
    <div>
      <div class="tb-name">Angelora</div>
      <div class="tb-sub">Laboratory</div>
    </div>
  </div>
  <div class="tb-right">
    <div class="date-pill"><i class="bi bi-calendar3"></i><?= date('D, d M Y') ?></div>
    <a href="lab_welcome.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</header>

<!-- ── PAGE ───────────────────────────────── -->
<div class="page">

  <div class="breadcrumb">
    <a href="lab_welcome.php"><i class="bi bi-house"></i> Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Lab Test Results</span>
  </div>

  <h1 class="page-title">Lab Test <em>Results</em></h1>
  <p class="page-sub">View, search, print and manage all recorded lab tests.</p>

  <?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Lab test deleted successfully.</div>
  <?php endif; ?>

  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
  <div class="alert alert-success">
    <i class="bi bi-envelope-check-fill"></i>
    <?= htmlspecialchars($_GET['msg'] ?? 'Email sent successfully.') ?>
  </div>
  <?php endif; ?>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar-left">
      <form method="get" class="search-form">
        <input type="text" name="search" class="search-input"
               placeholder="Search by test name or patient ID…"
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search"><i class="bi bi-search"></i> Search</button>
      </form>
      <div class="results-count">
        <strong><?= number_format($total_tests) ?></strong> result<?= $total_tests != 1 ? 's' : '' ?><?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
      </div>
    </div>
    <div class="toolbar-right">
      <a href="lab_tests.php" class="btn-add"><i class="bi bi-plus-circle-fill"></i> Add Lab Test</a>
    </div>
  </div>

  <!-- Table card -->
  <div class="table-card">
    <?php if (count($lab_tests) > 0): ?>
    <div class="tbl-wrap">
      <table id="labTestTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Patient</th>
            <th>Test Name</th>
            <th>Date</th>
            <th>Result</th>
            <th>Status</th>
            <th>Report</th>
            <th>Requested By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lab_tests as $i => $test):
            $name     = htmlspecialchars($test['full_name'] ?? 'N/A');
            $pid      = htmlspecialchars($test['patient_id']);
            $words    = explode(' ', trim($name));
            $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));
            $statusLow = strtolower($test['status']);
            $pillCls   = match($statusLow) {
              'completed'   => 'pill-completed',
              'in progress' => 'pill-progress',
              default       => 'pill-pending',
            };
            $pillIcon = match($statusLow) {
              'completed'   => 'bi-check-circle-fill',
              'in progress' => 'bi-arrow-repeat',
              default       => 'bi-hourglass-split',
            };
          ?>
          <tr id="test-<?= $test['lab_test_id'] ?>">
            <td style="color:var(--gray-400);font-size:11.5px;white-space:nowrap;">
              #<?= $test['lab_test_id'] ?>
            </td>
            <td>
              <div class="patient-cell">
                <div class="p-avatar"><?= $initials ?></div>
                <div>
                  <div class="p-name"><?= $name ?></div>
                  <div class="p-id">ID: <?= $pid ?></div>
                </div>
              </div>
            </td>
            <td><span class="test-name"><?= htmlspecialchars($test['test_name']) ?></span></td>
            <td style="white-space:nowrap;font-size:12.5px;">
              <i class="bi bi-calendar3" style="color:var(--blue-400);margin-right:4px;font-size:11px;"></i>
              <?= htmlspecialchars($test['test_date']) ?>
            </td>
            <td>
              <div class="result-cell">
                <?= decodeResult($test['result']) ?>
              </div>
            </td>
            <td>
              <span class="pill <?= $pillCls ?>">
                <i class="bi <?= $pillIcon ?>"></i>
                <?= htmlspecialchars($test['status']) ?>
              </span>
            </td>
            <td>
              <?php if (!empty($test['report_file'])): ?>
                <a href="<?= htmlspecialchars($test['report_file']) ?>" target="_blank" class="report-link">
                  <i class="bi bi-file-earmark-text"></i> View
                </a>
              <?php else: ?>
                <span class="no-report">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12.5px;"><?= htmlspecialchars($test['requested_by'] ?? '—') ?></td>
            <td>
              <div class="action-cell">
                <a class="btn-act btn-print"
                   href="generate_test_pdf.php?id=<?= $test['lab_test_id'] ?>" target="_blank">
                  <i class="bi bi-printer"></i> Print
                </a>
                <?php if ($statusLow === 'completed' && !empty($test['report_file'])): ?>
                <a class="btn-act btn-email"
                   href="send_lab_email.php?id=<?= $test['lab_test_id'] ?>">
                  <i class="bi bi-envelope"></i> Email
                </a>
                <?php endif; ?>
                <button class="btn-act btn-delete"
                        onclick="openDeleteModal(<?= $test['lab_test_id'] ?>, '<?= addslashes($test['test_name']) ?>', '<?= addslashes($name) ?>')">
                  <i class="bi bi-trash3"></i> Delete
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pag-wrap">
      <div class="pag-info">
        Page <?= $page ?> of <?= $total_pages ?> &nbsp;&middot;&nbsp;
        <?= number_format($total_tests) ?> total record<?= $total_tests != 1 ? 's' : '' ?>
      </div>
      <div class="pag-links">
        <!-- First / Prev -->
        <a href="?page=1&search=<?= urlencode($search) ?>"
           class="pag-btn <?= $page == 1 ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-double-left"></i>
        </a>
        <a href="?page=<?= max(1,$page-1) ?>&search=<?= urlencode($search) ?>"
           class="pag-btn <?= $page == 1 ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-left"></i>
        </a>

        <?php
        $prev = null;
        for ($i = 1; $i <= $total_pages; $i++):
          $show = ($i === 1 || $i === $total_pages || abs($i - $page) <= 1);
          if (!$show) {
            if ($prev !== '…') { echo '<span class="pag-dots">…</span>'; $prev = '…'; }
            continue;
          }
        ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
           class="pag-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php $prev = $i; endfor; ?>

        <!-- Next / Last -->
        <a href="?page=<?= min($total_pages,$page+1) ?>&search=<?= urlencode($search) ?>"
           class="pag-btn <?= $page == $total_pages ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
        <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>"
           class="pag-btn <?= $page == $total_pages ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-double-right"></i>
        </a>
      </div>
    </div>

    <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-inbox"></i>
      <p><?= $search ? 'No results for "' . htmlspecialchars($search) . '".' : 'No lab tests recorded yet.' ?></p>
      <?php if ($search): ?>
        <a href="view_lab_test.php" style="font-size:13px;color:var(--blue-600);text-decoration:none;">
          <i class="bi bi-x-circle"></i> Clear search
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div><!-- /table-card -->

</div><!-- /page -->

<!-- ── DELETE CONFIRMATION MODAL ──────────── -->
<div class="modal-backdrop" id="deleteModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-icon"><i class="bi bi-trash3-fill"></i></div>
      <div>
        <div class="modal-head-title">Delete Lab Test</div>
        <div class="modal-head-sub">This action cannot be undone</div>
      </div>
    </div>
    <div class="modal-body-inner">
      Are you sure you want to delete the lab test
      <strong id="modalTestName"></strong> for patient
      <strong id="modalPatientName"></strong>?
    </div>
    <div class="modal-footer">
      <button class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
      <form method="POST" style="margin:0;" id="deleteForm">
        <input type="hidden" name="delete_id" id="deleteIdInput">
        <button type="submit" class="btn-modal-delete">
          <i class="bi bi-trash3"></i> Yes, Delete
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function openDeleteModal(id, testName, patientName) {
  document.getElementById('modalTestName').textContent    = '"' + testName + '"';
  document.getElementById('modalPatientName').textContent = patientName;
  document.getElementById('deleteIdInput').value          = id;
  document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeDeleteModal();
});
</script>
</body>
</html>