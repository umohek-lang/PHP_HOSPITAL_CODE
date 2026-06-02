<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: ../login.php'); exit; }
require '../payment_alerts.php';
require '../includes/auth.php';
checkRole(3);

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM nursing_orders WHERE status = 'pending'");
$stmtCount->execute();
$newOrders = $stmtCount->fetchColumn();

$stmt = $pdo->prepare("
    SELECT n.*, p.full_name
    FROM nursing_orders n
    JOIN patients p ON n.patient_id = p.patient_id
    WHERE n.status = 'pending'
    ORDER BY n.ordered_at DESC
");
$stmt->execute();
$nursingOrders = $stmt->fetchAll();

$staff_name = $_SESSION['user']['full_name'] ?? 'Nurse';
$initial    = strtoupper(substr($staff_name, 0, 1));
$hour       = (int)date('H');
$greeting   = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nurse Dashboard — Angelora Hospital</title>
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
      --gray-500: #64748b; --gray-600: #475569; --gray-700: #334155; --gray-900: #0f172a;
      --green-600: #059669; --green-500: #10b981; --green-50: #ecfdf5; --green-100: #d1fae5; --green-700: #047857;
      --amber-500: #f59e0b; --amber-50: #fffbeb; --amber-100: #fef3c7; --amber-700: #b45309;
      --red-500: #ef4444; --red-50: #fef2f2; --red-100: #fee2e2; --red-700: #b91c1c;
      --violet-600: #7c3aed; --violet-50: #f5f3ff; --violet-100: #ede9fe;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
      --sidebar-w: 250px;
    }

    html, body { height: 100%; font-family: 'Sora', sans-serif; background: var(--gray-50); color: var(--gray-700); }
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--blue-200); border-radius: 4px; }

    /* ══ SIDEBAR ══ */
    .sidebar {
      position: fixed; top: 0; left: 0;
      width: var(--sidebar-w); height: 100vh;
      background: linear-gradient(170deg, var(--blue-900) 0%, var(--blue-800) 55%, var(--blue-700) 100%);
      display: flex; flex-direction: column; z-index: 200;
      box-shadow: 4px 0 28px rgba(15,45,107,.22); overflow: hidden;
    }
    .sidebar::before {
      content: ''; position: absolute; top: -50px; right: -50px;
      width: 180px; height: 180px; border-radius: 50%;
      background: rgba(255,255,255,.05); pointer-events: none;
    }

    .sidebar-brand {
      padding: 22px 18px 16px;
      border-bottom: 1px solid rgba(255,255,255,.08); flex-shrink: 0;
    }
    .brand-icon {
      width: 38px; height: 38px; background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.18); border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: #fff; margin-bottom: 9px;
    }
    .brand-title { color: #fff; font-size: .9rem; font-weight: 700; margin-bottom: 1px; }
    .brand-sub   { color: var(--blue-300); font-size: .68rem; font-weight: 500; }

    .sidebar-nav { flex: 1; overflow-y: auto; padding: 8px 10px; }
    .sidebar-nav::-webkit-scrollbar { width: 3px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); }

    .nav-label {
      padding: 12px 10px 4px;
      font-size: .58rem; font-weight: 700; letter-spacing: .13em;
      text-transform: uppercase; color: rgba(255,255,255,.28);
    }
    .nav-link {
      display: flex; align-items: center; gap: 9px;
      padding: 9px 11px; border-radius: 9px;
      color: rgba(255,255,255,.70); text-decoration: none;
      font-size: .78rem; font-weight: 500; margin-bottom: 1px;
      transition: background .18s, color .18s;
    }
    .nav-link .ni {
      width: 26px; height: 26px; min-width: 26px;
      background: rgba(255,255,255,.08); border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      font-size: .8rem; transition: background .18s;
    }
    .nav-link:hover, .nav-link.active { background: rgba(255,255,255,.10); color: #fff; }
    .nav-link:hover .ni, .nav-link.active .ni { background: var(--blue-500); }

    .sidebar-footer {
      padding: 10px; border-top: 1px solid rgba(255,255,255,.08); flex-shrink: 0;
    }
    .logout-link {
      display: flex; align-items: center; gap: 9px;
      padding: 9px 11px; border-radius: 9px;
      color: #fca5a5; text-decoration: none; font-size: .78rem; font-weight: 600;
      transition: background .18s;
    }
    .logout-link .ni { width: 26px; height: 26px; background: rgba(248,113,113,.12); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: .8rem; }
    .logout-link:hover { background: rgba(248,113,113,.10); color: #fca5a5; }

    /* ══ MAIN ══ */
    .main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }

    /* topbar */
    .topbar {
      background: var(--white); border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm); height: 60px;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 28px; position: sticky; top: 0; z-index: 100; flex-shrink: 0;
    }
    .topbar-left { display: flex; align-items: center; gap: 10px; }
    .topbar-title { font-size: .9rem; font-weight: 700; color: var(--blue-800); }
    .topbar-sub   { font-size: .7rem; color: var(--gray-400); margin-top: 1px; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .topbar-date  { font-size: .72rem; color: var(--gray-400); padding: 4px 11px; background: var(--gray-100); border-radius: 999px; border: 1px solid var(--gray-200); }
    .nurse-badge  {
      display: flex; align-items: center; gap: 6px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 5px 13px;
      font-size: .68rem; font-weight: 700; color: var(--blue-700);
      text-transform: uppercase; letter-spacing: .06em;
    }
    .topbar-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: .78rem; font-weight: 700; color: #fff;
      box-shadow: 0 2px 8px rgba(37,99,235,.25);
    }

    /* content */
    .content { flex: 1; padding: 24px 28px 40px; }

    /* welcome strip */
    .welcome-strip {
      background: linear-gradient(135deg, var(--blue-800) 0%, var(--blue-600) 60%, var(--blue-400) 100%);
      border-radius: 12px; padding: 14px 22px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; margin-bottom: 22px;
      box-shadow: 0 6px 20px rgba(37,99,235,.25);
      position: relative; overflow: hidden;
    }
    .welcome-strip::before {
      content: ''; position: absolute; top: -30px; right: -30px;
      width: 100px; height: 100px; border-radius: 50%;
      background: rgba(255,255,255,.06); pointer-events: none;
    }
    .ws-left { display: flex; align-items: center; gap: 12px; position: relative; z-index: 1; }
    .ws-av {
      width: 34px; height: 34px; border-radius: 8px;
      background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.28);
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem; color: #fff; flex-shrink: 0;
    }
    .ws-greet { color: rgba(255,255,255,.7); font-size: .68rem; }
    .ws-name  { color: #fff; font-size: .88rem; font-weight: 700; }
    .ws-right { display: flex; gap: 8px; position: relative; z-index: 1; }
    .ws-stat {
      display: flex; align-items: center; gap: 5px;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.16);
      border-radius: 7px; padding: 5px 12px;
    }
    .ws-stat-num { color: #fff; font-size: .82rem; font-weight: 700; }
    .ws-stat-lbl { color: rgba(255,255,255,.55); font-size: .62rem; text-transform: uppercase; letter-spacing: .06em; }

    /* section label */
    .section-label {
      font-size: .65rem; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: var(--gray-400);
      margin-bottom: 14px; display: flex; align-items: center; gap: 10px;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: var(--gray-200); }

    /* panels grid */
    .panels-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .panel-full  { grid-column: 1 / -1; }

    /* panel */
    .panel {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-sm);
      display: flex; flex-direction: column;
    }
    .panel-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 13px 18px; border-bottom: 1px solid var(--gray-100); flex-shrink: 0;
    }
    .panel-head.blue   { background: #fafcff; border-bottom-color: var(--blue-100); }
    .panel-head.green  { background: #fafffe; border-bottom-color: var(--green-100); }
    .panel-head.amber  { background: #fffef5; border-bottom-color: var(--amber-100); }
    .panel-head.violet { background: #fdfaff; border-bottom-color: var(--violet-100); }

    .ph-left { display: flex; align-items: center; gap: 9px; }
    .ph-icon {
      width: 32px; height: 32px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .ph-icon.blue   { background: var(--blue-50);   color: var(--blue-600);   }
    .ph-icon.green  { background: var(--green-50);  color: var(--green-600);  }
    .ph-icon.amber  { background: var(--amber-50);  color: var(--amber-500);  }
    .ph-icon.violet { background: var(--violet-50); color: var(--violet-600); }

    .ph-title { font-size: .85rem; font-weight: 700; color: var(--gray-900); }
    .ph-sub   { font-size: .68rem; color: var(--gray-400); margin-top: 1px; }

    .ph-badge {
      font-size: .65rem; font-weight: 700; padding: 3px 10px; border-radius: 999px;
    }
    .ph-badge.blue   { background: var(--blue-50);   color: var(--blue-700);   border: 1px solid var(--blue-100); }
    .ph-badge.green  { background: var(--green-50);  color: var(--green-700);  border: 1px solid var(--green-100); }
    .ph-badge.amber  { background: var(--amber-50);  color: var(--amber-700);  border: 1px solid var(--amber-100); }
    .ph-badge.violet { background: var(--violet-50); color: var(--violet-600); border: 1px solid var(--violet-100); }

    .panel-body { flex: 1; overflow-y: auto; max-height: 320px; }
    .panel-body::-webkit-scrollbar { width: 3px; }
    .panel-body::-webkit-scrollbar-thumb { background: var(--gray-200); }

    /* order item */
    .order-item {
      padding: 13px 18px; border-bottom: 1px solid var(--gray-100);
      transition: background .12s;
    }
    .order-item:last-child { border-bottom: none; }
    .order-item:hover { background: var(--blue-50); }

    .oi-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 5px; }
    .oi-name { font-size: .83rem; font-weight: 700; color: var(--gray-900); }
    .oi-patient { font-size: .74rem; color: var(--gray-500); margin-bottom: 3px; display: flex; align-items: center; gap: 5px; }
    .oi-date    { font-size: .7rem;  color: var(--gray-400); display: flex; align-items: center; gap: 5px; }
    .oi-notes   { font-size: .73rem; color: var(--gray-500); background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 6px; padding: 5px 9px; margin-top: 7px; line-height: 1.5; }

    .status-pill {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 2px 9px; border-radius: 999px;
      font-size: .65rem; font-weight: 700; white-space: nowrap;
    }
    .status-pill.pending  { background: var(--amber-50);  border: 1px solid var(--amber-100); color: var(--amber-700); }
    .status-pill.complete { background: var(--green-50);  border: 1px solid var(--green-100); color: var(--green-700); }

    .btn-complete {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 12px; border-radius: 7px; margin-top: 9px;
      background: var(--green-50); border: 1px solid var(--green-100);
      color: var(--green-700); font-family: 'Sora', sans-serif;
      font-size: .73rem; font-weight: 700; cursor: pointer;
      transition: all .16s;
    }
    .btn-complete:hover { background: var(--green-600); color: #fff; border-color: var(--green-600); }

    /* payment alert item */
    .payment-item {
      padding: 13px 18px; border-bottom: 1px solid var(--gray-100); transition: background .12s;
    }
    .payment-item:last-child { border-bottom: none; }
    .payment-item:hover { background: var(--green-50); }
    .pi-name { font-size: .83rem; font-weight: 700; color: var(--gray-900); }
    .pi-service { font-size: .74rem; color: var(--gray-500); margin: 2px 0; display: flex; align-items: center; gap: 5px; }
    .pi-time    { font-size: .7rem;  color: var(--gray-400); display: flex; align-items: center; gap: 5px; }
    .btn-seen {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 11px; border-radius: 6px; margin-top: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-700); font-family: 'Sora', sans-serif;
      font-size: .71rem; font-weight: 700; cursor: pointer;
      transition: all .16s;
    }
    .btn-seen:hover { background: var(--blue-600); color: #fff; border-color: var(--blue-600); }

    /* empty / loading */
    .empty-state {
      padding: 32px 20px; text-align: center; color: var(--gray-400);
    }
    .empty-state i { display: block; font-size: 1.8rem; color: var(--gray-300); margin-bottom: 8px; }
    .empty-state p { font-size: .78rem; }
    .loading-state {
      padding: 24px 20px; text-align: center; color: var(--gray-400);
      font-size: .78rem; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .spin { animation: spin .8s linear infinite; display: inline-block; color: var(--blue-400); }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* prescription item (loaded dynamically) */
    .pres-item { padding: 12px 18px; border-bottom: 1px solid var(--gray-100); transition: background .12s; }
    .pres-item:hover { background: var(--violet-50); }
    .pres-item:last-child { border-bottom: none; }

    /* responsive */
    @media (max-width: 900px) {
      .panels-grid { grid-template-columns: 1fr; }
      .panel-full  { grid-column: auto; }
    }
    @media (max-width: 700px) {
      .sidebar { display: none; }
      .main { margin-left: 0; }
      .content { padding: 16px; }
      .ws-right { display: none; }
    }
  </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<nav class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <div class="brand-title">Angelora Hospital</div>
    <div class="brand-sub">Nurse Portal</div>
  </div>

  <div class="sidebar-nav">
    <div class="nav-label">Navigation</div>

    <a href="#" class="nav-link active">
      <span class="ni"><i class="bi bi-grid-fill"></i></span> Dashboard
    </a>
    <a href="patients.php" class="nav-link">
      <span class="ni"><i class="bi bi-people-fill"></i></span> Patients
    </a>
    <a href="medical_history.php" class="nav-link">
      <span class="ni"><i class="bi bi-journal-medical"></i></span> Medical History
    </a>

    <div class="nav-label">Clinical Tasks</div>

    <a href="monitor_vital_signs.php" class="nav-link">
      <span class="ni"><i class="bi bi-heart-pulse-fill"></i></span> Record Vitals
    </a>
    <a href="administer_medications.php" class="nav-link">
      <span class="ni"><i class="bi bi-capsule-pill"></i></span> Give Medication
    </a>
    <a href="administer_treatment.php" class="nav-link">
      <span class="ni"><i class="bi bi-clipboard2-pulse-fill"></i></span> Give Treatment
    </a>

    <div class="nav-label">Records & Reports</div>

    <a href="display_medical_history.php" class="nav-link">
      <span class="ni"><i class="bi bi-file-earmark-medical-fill"></i></span> Patient History
    </a>
    <a href="show_vitals.php" class="nav-link">
      <span class="ni"><i class="bi bi-activity"></i></span> View Vitals
    </a>
    <a href="nurse_report.php" class="nav-link">
      <span class="ni"><i class="bi bi-file-text-fill"></i></span> Make Report
    </a>
    <a href="patients_preview.php" class="nav-link">
      <span class="ni"><i class="bi bi-person-lines-fill"></i></span> Patient Info
    </a>
  </div>

  <div class="sidebar-footer">
    <a href="../logout.php" class="logout-link">
      <span class="ni"><i class="bi bi-box-arrow-right"></i></span> Logout
    </a>
  </div>
</nav>

<!-- ══ MAIN ══ -->
<div class="main">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <div>
        <div class="topbar-title">Nurse Dashboard</div>
        <div class="topbar-sub">Angelora Hospital Management System</div>
      </div>
    </div>
    <div class="topbar-right">
      <span class="topbar-date"><i class="bi bi-calendar3" style="margin-right:4px"></i><?= date('d M Y') ?></span>
      <div class="nurse-badge"><i class="bi bi-clipboard2-heart-fill"></i> Nurse</div>
      <div class="topbar-avatar"><?= $initial ?></div>
    </div>
  </header>

  <!-- Content -->
  <div class="content">

    <!-- Welcome strip -->
    <div class="welcome-strip">
      <div class="ws-left">
        <div class="ws-av"><i class="bi bi-person-fill"></i></div>
        <div>
          <div class="ws-greet"><?= $greeting ?>,</div>
          <div class="ws-name"><?= htmlspecialchars($staff_name) ?></div>
        </div>
      </div>
      <div class="ws-right">
        <div class="ws-stat">
          <div class="ws-stat-num"><?= $newOrders ?></div>
          <div class="ws-stat-lbl">Pending Orders</div>
        </div>
        <div class="ws-stat">
          <div class="ws-stat-num"><?= date('H:i') ?></div>
          <div class="ws-stat-lbl">Time</div>
        </div>
      </div>
    </div>

    <!-- Section label -->
    <div class="section-label">Live Panels</div>

    <!-- Panels grid -->
    <div class="panels-grid">

      <!-- Nursing Orders -->
      <div class="panel">
        <div class="panel-head green">
          <div class="ph-left">
            <div class="ph-icon green"><i class="bi bi-clipboard2-heart-fill"></i></div>
            <div>
              <div class="ph-title">Nurse Orders</div>
              <div class="ph-sub">Pending procedures from doctors</div>
            </div>
          </div>
          <span class="ph-badge green" id="nurseOrderCount"><?= $newOrders ?> pending</span>
        </div>
        <div class="panel-body" id="nurseOrdersContainer">
          <?php if (empty($nursingOrders)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i><p>No pending orders.</p></div>
          <?php else: ?>
            <?php foreach ($nursingOrders as $order): ?>
            <div class="order-item" id="order-<?= $order['id'] ?>">
              <div class="oi-head">
                <span class="oi-name"><?= htmlspecialchars($order['procedure_name']) ?></span>
                <span class="status-pill pending">Pending</span>
              </div>
              <div class="oi-patient"><i class="bi bi-person-fill"></i><?= htmlspecialchars($order['full_name']) ?></div>
              <div class="oi-date"><i class="bi bi-clock"></i><?= htmlspecialchars($order['ordered_at']) ?></div>
              <?php if (!empty($order['notes'])): ?>
                <div class="oi-notes"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($order['notes'])) ?></div>
              <?php endif; ?>
              <button class="btn-complete mark-complete-btn" data-id="<?= $order['id'] ?>">
                <i class="bi bi-check-circle-fill"></i> Mark Completed
              </button>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Payment Notifications -->
      <div class="panel">
        <div class="panel-head amber">
          <div class="ph-left">
            <div class="ph-icon amber"><i class="bi bi-bell-fill"></i></div>
            <div>
              <div class="ph-title">Payment Alerts</div>
              <div class="ph-sub">Recent patient payments</div>
            </div>
          </div>
          <span class="ph-badge amber"><?= !empty($alerts) ? count($alerts) : 0 ?> new</span>
        </div>
        <div class="panel-body">
          <?php if (!empty($alerts)): ?>
            <?php foreach ($alerts as $alert): ?>
            <div class="payment-item" id="alert-<?= $alert['billing_id'] ?>">
              <div class="pi-name"><?= htmlspecialchars($alert['full_name']) ?></div>
              <div class="pi-service"><i class="bi bi-bag-fill"></i><?= htmlspecialchars($alert['service_name']) ?></div>
              <div class="pi-time"><i class="bi bi-clock"></i><?= htmlspecialchars($alert['paid_at']) ?></div>
              <button class="btn-seen markSeenBtn" data-id="<?= $alert['billing_id'] ?>">
                <i class="bi bi-check2"></i> Mark as Seen
              </button>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-state"><i class="bi bi-bell-slash"></i><p>No new payment alerts.</p></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Prescriptions (full width) -->
      <div class="panel panel-full">
        <div class="panel-head violet">
          <div class="ph-left">
            <div class="ph-icon violet"><i class="bi bi-capsule-pill"></i></div>
            <div>
              <div class="ph-title">Prescriptions</div>
              <div class="ph-sub">Medications to administer</div>
            </div>
          </div>
          <span class="ph-badge violet">Auto-refresh</span>
        </div>
        <div class="panel-body" id="prescriptionsContainer">
          <div class="loading-state"><i class="bi bi-arrow-clockwise spin"></i> Loading prescriptions…</div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
// ── MARK SEEN (payments) ──
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.markSeenBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      fetch('mark_alert_seen.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'billing_id=' + encodeURIComponent(id)
      })
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') document.getElementById('alert-' + id)?.remove();
        else alert('Failed to mark as seen.');
      });
    });
  });
});

// ── MARK NURSING COMPLETE ──
function bindMarkCompleteButtons() {
  document.querySelectorAll('.mark-complete-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const id  = this.dataset.id;
      const div = document.getElementById('order-' + id);
      fetch('mark_nursing_complete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=' + id
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          div.querySelector('.mark-complete-btn')?.remove();
          const pill = div.querySelector('.status-pill');
          if (pill) { pill.className = 'status-pill complete'; pill.textContent = 'Completed'; }
        } else {
          alert('Failed to update.');
        }
      });
    });
  });
}
bindMarkCompleteButtons();

// ── RELOAD NURSING ORDERS ──
setInterval(() => {
  fetch('fetch_nursing_orders.php')
    .then(r => r.text())
    .then(html => {
      document.getElementById('nurseOrdersContainer').innerHTML = html;
      bindMarkCompleteButtons();
    });
}, 60000);

// ── LOAD & RELOAD PRESCRIPTIONS ──
function loadPrescriptions() {
  fetch('fetch_prescriptions.php')
    .then(r => r.text())
    .then(html => {
      document.getElementById('prescriptionsContainer').innerHTML = html;
      bindPrescriptionButtons();
    });
}
function bindPrescriptionButtons() {
  document.querySelectorAll('.mark-pres-complete-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const id  = this.dataset.id;
      const div = document.getElementById('pres-' + id);
      fetch('mark_prescription_complete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          div.querySelector('.mark-pres-complete-btn')?.remove();
          const s = div.querySelector('.order-status');
          if (s) { s.textContent = 'Completed'; s.style.color = 'var(--green-600)'; }
        } else {
          alert('Failed to update prescription.');
        }
      });
    });
  });
}
loadPrescriptions();
setInterval(loadPrescriptions, 3000);
</script>
</body>
</html>