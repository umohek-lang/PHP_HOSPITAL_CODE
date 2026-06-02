<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../db.php';

$message     = "";
$messageType = "";

/* ── HANDLE FORM SUBMISSION ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['vs_id'] ?? null;

    if (isset($_POST['action']) && $_POST['action'] === 'edit' && $id) {
        $sql = "UPDATE vital_signs SET
            patient_id=:patient_id, temperature=:temperature, pulse_rate=:pulse_rate,
            respiration_rate=:respiration_rate, blood_pressure=:blood_pressure,
            oxygen_saturation=:oxygen_saturation, pain_level=:pain_level,
            height_cm=:height_cm, weight_kg=:weight_kg, bmi=:bmi,
            blood_sugar=:blood_sugar, consciousness_level=:consciousness_level,
            vitals_time=:vitals_time, symptoms_notes=:symptoms_notes,
            recorded_by=:recorded_by WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_id'         => $_POST['patient_id'],
            ':temperature'        => $_POST['temperature'],
            ':pulse_rate'         => $_POST['pulse_rate'],
            ':respiration_rate'   => $_POST['respiration_rate'],
            ':blood_pressure'     => $_POST['blood_pressure'],
            ':oxygen_saturation'  => $_POST['oxygen_saturation'],
            ':pain_level'         => $_POST['pain_level'],
            ':height_cm'          => $_POST['height_cm'] ?: null,
            ':weight_kg'          => $_POST['weight_kg'] ?: null,
            ':bmi'                => $_POST['bmi'] ?: null,
            ':blood_sugar'        => $_POST['blood_sugar'] ?: null,
            ':consciousness_level'=> $_POST['consciousness_level'] ?: null,
            ':vitals_time'        => $_POST['vitals_time'] ?: null,
            ':symptoms_notes'     => $_POST['symptoms_notes'] ?: null,
            ':recorded_by'        => $_POST['recorded_by'],
            ':id'                 => $id
        ]);
        $message = "Vital signs updated successfully.";
        $messageType = "success";

    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM vital_signs WHERE id=?");
        $stmt->execute([$id]);
        $message = "Vital signs record deleted.";
        $messageType = "deleted";

    } else {
        $sql = "INSERT INTO vital_signs (
            patient_id, temperature, pulse_rate, respiration_rate,
            blood_pressure, oxygen_saturation, pain_level,
            height_cm, weight_kg, bmi, recorded_by,
            blood_sugar, consciousness_level, vitals_time, symptoms_notes
        ) VALUES (
            :patient_id, :temperature, :pulse_rate, :respiration_rate,
            :blood_pressure, :oxygen_saturation, :pain_level,
            :height_cm, :weight_kg, :bmi, :recorded_by,
            :blood_sugar, :consciousness_level, :vitals_time, :symptoms_notes
        )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_id'         => $_POST['patient_id'],
            ':temperature'        => $_POST['temperature'],
            ':pulse_rate'         => $_POST['pulse_rate'],
            ':respiration_rate'   => $_POST['respiration_rate'],
            ':blood_pressure'     => $_POST['blood_pressure'],
            ':oxygen_saturation'  => $_POST['oxygen_saturation'],
            ':pain_level'         => $_POST['pain_level'],
            ':height_cm'          => $_POST['height_cm'] ?: null,
            ':weight_kg'          => $_POST['weight_kg'] ?: null,
            ':bmi'                => $_POST['bmi'] ?: null,
            ':recorded_by'        => $_POST['recorded_by'],
            ':blood_sugar'        => $_POST['blood_sugar'] ?: null,
            ':consciousness_level'=> $_POST['consciousness_level'] ?: null,
            ':vitals_time'        => $_POST['vitals_time'] ?: null,
            ':symptoms_notes'     => $_POST['symptoms_notes'] ?: null
        ]);
        $message = "Vital signs recorded successfully.";
        $messageType = "success";
    }
}

/* ── FETCH DATA ── */
$patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name")->fetchAll();
$nurses   = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id=3 ORDER BY full_name")->fetchAll();

$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;
$totalRecords = $pdo->query("SELECT COUNT(*) FROM vital_signs")->fetchColumn();
$totalPages   = (int)ceil($totalRecords / $perPage);

$stmt = $pdo->prepare("
    SELECT v.*, p.full_name
    FROM vital_signs v
    LEFT JOIN patients p ON v.patient_id = p.patient_id
    ORDER BY v.created_at DESC
    LIMIT $perPage OFFSET $offset");
$stmt->execute();
$vitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vital Signs — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --black:     #0a0a0a;
      --ink:       #1a1a1a;
      --ink-mid:   #2d2d2d;
      --ink-light: #404040;
      --gray-700:  #525252;
      --gray-600:  #6b6b6b;
      --gray-500:  #8a8a8a;
      --gray-400:  #a3a3a3;
      --gray-300:  #c4c4c4;
      --gray-200:  #dcdcdc;
      --gray-150:  #e8e8e8;
      --gray-100:  #f0f0f0;
      --gray-50:   #f7f7f7;
      --white:     #ffffff;
      --accent:    #0a0a0a;
      --red:       #c0392b;
      --red-bg:    #fdf2f2;
      --green:     #1a7a4a;
      --green-bg:  #f2faf5;
      --shadow-crisp: 0 1px 0 rgba(0,0,0,.08), 0 2px 6px rgba(0,0,0,.05);
      --shadow-lift:  0 4px 20px rgba(0,0,0,.10), 0 1px 4px rgba(0,0,0,.06);
      --shadow-heavy: 0 8px 32px rgba(0,0,0,.16), 0 2px 8px rgba(0,0,0,.08);
      --font-body: 'IBM Plex Sans', sans-serif;
      --font-mono: 'IBM Plex Mono', monospace;
    }

    html, body {
      min-height: 100vh;
      font-family: var(--font-body);
      background: var(--gray-50);
      color: var(--ink);
    }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 2px; }

    /* ── TOPBAR ── */
    .topbar {
      position: sticky; top: 0; z-index: 200;
      background: var(--black);
      height: 58px;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 32px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 14px; }
    .brand-mark {
      width: 32px; height: 32px; border: 2px solid rgba(255,255,255,.9);
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      font-family: var(--font-mono); font-size: .7rem; font-weight: 600;
      color: var(--white); letter-spacing: .05em;
    }
    .brand-name {
      font-size: .88rem; font-weight: 700; color: var(--white);
      letter-spacing: .04em; text-transform: uppercase;
    }
    .brand-divider { width: 1px; height: 18px; background: rgba(255,255,255,.2); }
    .brand-sub { font-size: .72rem; color: rgba(255,255,255,.45); font-family: var(--font-mono); }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .topbar-time {
      font-size: .7rem; color: rgba(255,255,255,.4);
      font-family: var(--font-mono); padding: 4px 10px;
      border: 1px solid rgba(255,255,255,.12); border-radius: 4px;
    }
    .back-link {
      display: flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: 5px;
      border: 1px solid rgba(255,255,255,.2);
      color: rgba(255,255,255,.7); text-decoration: none;
      font-size: .74rem; font-weight: 500;
      transition: all .16s;
    }
    .back-link:hover { background: rgba(255,255,255,.09); color: var(--white); border-color: rgba(255,255,255,.4); }

    /* ── PAGE ── */
    .page { max-width: 1320px; margin: 0 auto; padding: 32px 24px 60px; }

    /* ── PAGE HEADER ── */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      gap: 16px; flex-wrap: wrap; margin-bottom: 28px;
      padding-bottom: 20px; border-bottom: 2px solid var(--black);
    }
    .ph-left {}
    .ph-label {
      font-family: var(--font-mono); font-size: .62rem; font-weight: 600;
      letter-spacing: .18em; text-transform: uppercase; color: var(--gray-500);
      margin-bottom: 6px;
    }
    .ph-title {
      font-size: 1.9rem; font-weight: 700; color: var(--black);
      letter-spacing: -.03em; line-height: 1.1;
    }
    .ph-title span { font-weight: 300; }
    .ph-sub { font-size: .8rem; color: var(--gray-500); margin-top: 5px; }

    .stats-row { display: flex; gap: 10px; flex-shrink: 0; }
    .stat-box {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 8px; padding: 12px 18px; text-align: center;
      box-shadow: var(--shadow-crisp); min-width: 80px;
    }
    .stat-num { font-family: var(--font-mono); font-size: 1.4rem; font-weight: 600; color: var(--black); line-height: 1; }
    .stat-lbl { font-size: .62rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: .1em; margin-top: 4px; }

    /* ── ALERT ── */
    .alert-msg {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 18px; border-radius: 7px; margin-bottom: 22px;
      font-size: .83rem; font-weight: 500;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }
    .alert-msg.success { background: var(--green-bg); border: 1px solid #b7dfca; color: var(--green); }
    .alert-msg.deleted { background: var(--red-bg);   border: 1px solid #f0c4c0; color: var(--red); }
    .alert-msg i { font-size: 1rem; flex-shrink: 0; }

    /* ── TABLE CARD ── */
    .table-card {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-lift);
    }
    .table-card-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 22px; border-bottom: 2px solid var(--black);
      background: var(--black);
    }
    .tch-left { display: flex; align-items: center; gap: 10px; }
    .tch-icon {
      width: 30px; height: 30px; border-radius: 6px;
      border: 1.5px solid rgba(255,255,255,.3);
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem; color: var(--white);
    }
    .tch-title { font-size: .88rem; font-weight: 700; color: var(--white); letter-spacing: .01em; }
    .tch-count {
      font-family: var(--font-mono); font-size: .68rem;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
      border-radius: 4px; padding: 3px 9px; color: rgba(255,255,255,.7);
    }

    /* ── SCROLLABLE TABLE WRAP ── */
    .table-scroll { overflow-x: auto; }

    /* ── DATA TABLE ── */
    .data-table { width: 100%; border-collapse: collapse; font-size: .78rem; min-width: 960px; }
    .data-table thead th {
      padding: 9px 14px; text-align: left;
      font-family: var(--font-mono); font-size: .6rem;
      font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
      color: var(--gray-500); background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200);
      white-space: nowrap;
    }
    .data-table thead th.center { text-align: center; }
    .data-table tbody tr {
      border-bottom: 1px solid var(--gray-150);
      transition: background .1s;
    }
    .data-table tbody tr:last-child { border-bottom: none; }
    .data-table tbody tr:hover { background: var(--gray-50); }
    .data-table tbody tr:nth-child(even) { background: #fafafa; }
    .data-table tbody tr:nth-child(even):hover { background: var(--gray-100); }
    .data-table td { padding: 10px 14px; color: var(--ink-light); vertical-align: middle; }
    .data-table td.center { text-align: center; }

    /* row index */
    .row-idx {
      font-family: var(--font-mono); font-size: .68rem;
      color: var(--gray-400); font-weight: 500;
    }

    /* patient name */
    .patient-cell { display: flex; align-items: center; gap: 8px; }
    .p-init {
      width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
      background: var(--black); color: var(--white);
      display: flex; align-items: center; justify-content: center;
      font-family: var(--font-mono); font-size: .62rem; font-weight: 600;
    }
    .p-name { font-weight: 600; color: var(--black); font-size: .8rem; }

    /* vital value chips */
    .val-chip {
      display: inline-flex; align-items: center;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 5px; padding: 3px 8px;
      font-family: var(--font-mono); font-size: .72rem;
      color: var(--ink); font-weight: 500; white-space: nowrap;
    }
    .val-chip.empty { background: transparent; border-color: transparent; color: var(--gray-400); font-style: italic; font-family: var(--font-body); }

    /* recorded by */
    .rec-by { font-size: .74rem; color: var(--gray-600); }

    /* time chip */
    .time-chip {
      font-family: var(--font-mono); font-size: .68rem;
      color: var(--gray-500); white-space: nowrap;
    }

    /* ── ACTION BUTTONS ── */
    .action-cell { display: flex; align-items: center; gap: 6px; justify-content: center; }
    .btn-edit {
      display: flex; align-items: center; gap: 4px;
      padding: 5px 11px; border-radius: 5px;
      background: var(--black); border: 1px solid var(--black);
      color: var(--white); font-family: var(--font-body);
      font-size: .7rem; font-weight: 600; cursor: pointer;
      text-decoration: none; transition: all .15s;
    }
    .btn-edit:hover { background: var(--ink-mid); }
    .btn-delete {
      display: flex; align-items: center; gap: 4px;
      padding: 5px 11px; border-radius: 5px;
      background: var(--white); border: 1px solid var(--gray-300);
      color: var(--red); font-family: var(--font-body);
      font-size: .7rem; font-weight: 600; cursor: pointer;
      transition: all .15s;
    }
    .btn-delete:hover { background: var(--red); color: var(--white); border-color: var(--red); }

    /* ── EMPTY STATE ── */
    .empty-row td {
      padding: 52px 20px; text-align: center; color: var(--gray-400);
    }
    .empty-icon {
      font-size: 2.8rem; color: var(--gray-300);
      display: block; margin-bottom: 10px;
    }
    .empty-text { font-size: .82rem; }

    /* ── PAGINATION ── */
    .pagination-wrap {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 22px; border-top: 1px solid var(--gray-200);
      background: var(--gray-50); flex-wrap: wrap; gap: 10px;
    }
    .pag-info {
      font-family: var(--font-mono); font-size: .7rem; color: var(--gray-500);
    }
    .pag-btns { display: flex; gap: 3px; }
    .pag-btn {
      display: flex; align-items: center; justify-content: center;
      min-width: 34px; height: 34px; padding: 0 10px;
      border-radius: 5px; border: 1px solid var(--gray-200);
      background: var(--white); color: var(--gray-600);
      font-family: var(--font-mono); font-size: .75rem; font-weight: 500;
      text-decoration: none; transition: all .14s;
    }
    .pag-btn:hover { background: var(--black); color: var(--white); border-color: var(--black); }
    .pag-btn.active { background: var(--black); color: var(--white); border-color: var(--black); font-weight: 700; }
    .pag-btn.disabled { opacity: .3; pointer-events: none; }

    /* ── RESPONSIVE ── */
    @media (max-width: 700px) {
      .topbar { padding: 0 14px; }
      .topbar-time { display: none; }
      .page { padding: 18px 12px 48px; }
      .ph-title { font-size: 1.4rem; }
      .stats-row { display: none; }
    }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-mark">AH</div>
    <div class="brand-name">Angelora</div>
    <div class="brand-divider"></div>
    <div class="brand-sub">vital_signs.mgr</div>
  </div>
  <div class="topbar-right">
    <span class="topbar-time"><?= date('Y-m-d  H:i') ?></span>
    <a href="dashboard.php" class="back-link"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-left">
      <div class="ph-label">Clinical Records · Nurse Module</div>
      <div class="ph-title">Vital <span>Signs</span></div>
      <div class="ph-sub">View, edit and manage all patient vital sign records.</div>
    </div>
    <div class="stats-row">
      <div class="stat-box">
        <div class="stat-num"><?= number_format($totalRecords) ?></div>
        <div class="stat-lbl">Records</div>
      </div>
      <div class="stat-box">
        <div class="stat-num"><?= $totalPages ?></div>
        <div class="stat-lbl">Pages</div>
      </div>
      <div class="stat-box">
        <div class="stat-num"><?= $page ?></div>
        <div class="stat-lbl">Current</div>
      </div>
    </div>
  </div>

  <!-- ALERT -->
  <?php if ($message): ?>
  <div class="alert-msg <?= $messageType ?>">
    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'trash3-fill' ?>"></i>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <!-- TABLE CARD -->
  <div class="table-card">
    <div class="table-card-head">
      <div class="tch-left">
        <div class="tch-icon"><i class="bi bi-heart-pulse-fill"></i></div>
        <div class="tch-title">Vital Signs Registry</div>
      </div>
      <span class="tch-count"><?= number_format($totalRecords) ?> total records</span>
    </div>

    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Patient</th>
            <th>Temp °C</th>
            <th>Pulse</th>
            <th>Resp</th>
            <th>Blood Pressure</th>
            <th>O₂ Sat %</th>
            <th>Pain</th>
            <th>BMI</th>
            <th>Recorded By</th>
            <th>Vitals Time</th>
            <th class="center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($vitals)): ?>
          <tr class="empty-row">
            <td colspan="12">
              <i class="bi bi-activity empty-icon"></i>
              <div class="empty-text">No vital signs records found.</div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($vitals as $i => $v):
            $name     = $v['full_name'] ?? '';
            $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $name)))));
            $initials = substr($initials, 0, 2) ?: '—';
            function vchip($val) {
              return $val !== null && $val !== ''
                ? '<span class="val-chip">' . htmlspecialchars($val) . '</span>'
                : '<span class="val-chip empty">—</span>';
            }
          ?>
          <tr>
            <td><span class="row-idx"><?= str_pad($offset + $i + 1, 3, '0', STR_PAD_LEFT) ?></span></td>
            <td>
              <div class="patient-cell">
                <div class="p-init"><?= htmlspecialchars($initials) ?></div>
                <div class="p-name"><?= htmlspecialchars($name ?: '—') ?></div>
              </div>
            </td>
            <td><?= vchip($v['temperature']) ?></td>
            <td><?= vchip($v['pulse_rate']) ?></td>
            <td><?= vchip($v['respiration_rate']) ?></td>
            <td><?= vchip($v['blood_pressure']) ?></td>
            <td><?= vchip($v['oxygen_saturation']) ?></td>
            <td><?= vchip($v['pain_level']) ?></td>
            <td><?= vchip($v['bmi']) ?></td>
            <td><span class="rec-by"><?= htmlspecialchars($v['recorded_by'] ?? '—') ?></span></td>
            <td><span class="time-chip"><?= htmlspecialchars($v['vitals_time'] ?? '—') ?></span></td>
            <td>
              <div class="action-cell">
                <a href="edit_vital.php?vs_id=<?= $v['id'] ?>" class="btn-edit">
                  <i class="bi bi-pencil-fill"></i> Edit
                </a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete vital sign record #<?= str_pad($offset+$i+1,3,'0',STR_PAD_LEFT) ?> for <?= htmlspecialchars(addslashes($name)) ?>?')">
                  <input type="hidden" name="vs_id" value="<?= $v['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="btn-delete">
                    <i class="bi bi-trash3-fill"></i> Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1):
      $start = max(1, $page - 2);
      $end   = min($totalPages, $page + 2);
    ?>
    <div class="pagination-wrap">
      <span class="pag-info">
        Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRecords) ?> of <?= number_format($totalRecords) ?> records
      </span>
      <div class="pag-btns">
        <a href="?page=<?= $page - 1 ?>" class="pag-btn <?= $page <= 1 ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-left"></i>
        </a>
        <?php if ($start > 1): ?>
          <a href="?page=1" class="pag-btn">1</a>
          <?php if ($start > 2): ?><span class="pag-btn disabled">…</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
          <a href="?page=<?= $p ?>" class="pag-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?><span class="pag-btn disabled">…</span><?php endif; ?>
          <a href="?page=<?= $totalPages ?>" class="pag-btn"><?= $totalPages ?></a>
        <?php endif; ?>
        <a href="?page=<?= $page + 1 ?>" class="pag-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>