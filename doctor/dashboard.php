<?php
session_start();
require '../db.php';

// Auth check
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit;
}

require '../payment_alerts.php';
require '../includes/auth.php';
checkRole(2);
require '../includes/functions.php';

$doctor_id = $_SESSION['user']['user_id'];
$doctor_name = $_SESSION['user']['full_name'];
$shifts = getUserShifts($pdo, $doctor_id);

// Fetch upcoming unseen appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time,
               a.status, p.patient_id, p.patient_pin, p.full_name AS patient_name, p.phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = :doctor_id
          AND a.appointment_date >= CURDATE()
          AND a.seen = 0
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 10
    ");
    $stmt->execute(['doctor_id' => $doctor_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Dashboard — Angelora Hospital</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:        #0c1b33;
      --navy-mid:    #162344;
      --navy-light:  #1e3157;
      --teal:        #0d9488;
      --teal-light:  #14b8a6;
      --teal-glow:   rgba(13,148,136,.18);
      --amber:       #f59e0b;
      --red:         #ef4444;
      --green:       #10b981;
      --sidebar-w:   240px;
      --topbar-h:    64px;
      --white:       #ffffff;
      --text-main:   #e2e8f0;
      --text-muted:  #94a3b8;
      --border:      rgba(255,255,255,.07);
      --card-bg:     rgba(255,255,255,.04);
      --card-hover:  rgba(255,255,255,.07);
      --radius:      12px;
    }

    html, body {
      height: 100%;
      font-family: 'Sora', sans-serif;
      background: var(--navy);
      color: var(--text-main);
      overflow-x: hidden;
    }

    /* ── Scrollbar ──────────────────── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 10px; }

    /* ═══════════════════════════════════
       SIDEBAR
    ═══════════════════════════════════ */
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: var(--sidebar-w);
      height: 100vh;
      background: var(--navy-mid);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      z-index: 200;
      transition: transform .3s cubic-bezier(.4,0,.2,1);
    }

    .sidebar-brand {
      padding: 22px 22px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-icon {
      width: 36px; height: 36px;
      background: var(--teal);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .brand-icon i { font-size: 18px; color: white; }

    .brand-text { display: flex; flex-direction: column; }
    .brand-name {
      font-family: 'Instrument Serif', serif;
      font-size: 15px;
      color: var(--white);
      line-height: 1.2;
    }
    .brand-role {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--teal-light);
    }

    .sidebar-nav {
      flex: 1;
      overflow-y: auto;
      padding: 16px 12px;
    }

    .nav-label {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: .14em;
      text-transform: uppercase;
      color: var(--text-muted);
      padding: 14px 10px 6px;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 10px 12px;
      border-radius: 9px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 13.5px;
      font-weight: 400;
      transition: background .18s, color .18s;
      margin-bottom: 2px;
    }
    .nav-link i { font-size: 16px; flex-shrink: 0; }
    .nav-link:hover { background: var(--card-hover); color: var(--white); }
    .nav-link.active {
      background: var(--teal-glow);
      color: var(--teal-light);
      font-weight: 600;
    }
    .nav-link.logout { color: #f87171; }
    .nav-link.logout:hover { background: rgba(239,68,68,.1); color: #f87171; }

    .sidebar-footer {
      padding: 16px 20px;
      border-top: 1px solid var(--border);
    }

    .doctor-card {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .doctor-avatar {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--teal), #0ea5e9);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Instrument Serif', serif;
      font-size: 15px;
      color: white;
      flex-shrink: 0;
    }

    .doctor-info { min-width: 0; }
    .doctor-name {
      font-size: 13px;
      font-weight: 600;
      color: var(--white);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .doctor-tag {
      font-size: 11px;
      color: var(--teal-light);
    }

    /* ═══════════════════════════════════
       TOP BAR
    ═══════════════════════════════════ */
    .topbar {
      position: fixed;
      top: 0;
      left: var(--sidebar-w);
      right: 0;
      height: var(--topbar-h);
      background: var(--navy-mid);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      z-index: 100;
    }

    .topbar-left {
      display: flex;
      flex-direction: column;
    }
    .topbar-title {
      font-family: 'Instrument Serif', serif;
      font-size: 20px;
      color: var(--white);
      line-height: 1.1;
    }
    .topbar-sub {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 1px;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* Icon buttons */
    .icon-btn {
      position: relative;
      width: 38px; height: 38px;
      border-radius: 10px;
      background: var(--card-bg);
      border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      color: var(--text-muted);
      transition: background .18s, color .18s, border-color .18s;
      text-decoration: none;
    }
    .icon-btn:hover {
      background: var(--card-hover);
      color: var(--white);
      border-color: rgba(255,255,255,.14);
    }
    .icon-btn i { font-size: 17px; }

    .icon-btn .badge-dot {
      position: absolute;
      top: 7px; right: 7px;
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--red);
      border: 2px solid var(--navy-mid);
    }

    .badge-count {
      position: absolute;
      top: -4px; right: -4px;
      min-width: 18px; height: 18px;
      border-radius: 9px;
      background: var(--red);
      color: white;
      font-size: 10px;
      font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      padding: 0 4px;
      border: 2px solid var(--navy-mid);
    }

    /* ═══════════════════════════════════
       MAIN CONTENT
    ═══════════════════════════════════ */
    .main {
      margin-left: var(--sidebar-w);
      margin-top: var(--topbar-h);
      padding: 28px;
      min-height: calc(100vh - var(--topbar-h));
    }

    /* ── Appointment panel (dropdown) ─── */
    .appt-panel {
      background: var(--navy-light);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      margin-bottom: 24px;
      animation: slideDown .3s cubic-bezier(.16,1,.3,1);
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .appt-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      background: rgba(13,148,136,.08);
    }

    .appt-panel-title {
      font-size: 14px;
      font-weight: 600;
      color: var(--teal-light);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,0,0,.2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 7px 12px;
    }
    .search-box i { color: var(--text-muted); font-size: 14px; }
    .search-box input {
      background: none;
      border: none;
      outline: none;
      color: var(--text-main);
      font-family: 'Sora', sans-serif;
      font-size: 13px;
      width: 200px;
    }
    .search-box input::placeholder { color: var(--text-muted); }

    /* ── Table ───────────────────────── */
    .appt-table-wrap { overflow-x: auto; }

    .appt-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .appt-table thead th {
      padding: 12px 16px;
      text-align: left;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    .appt-table tbody tr {
      transition: background .15s;
      border-bottom: 1px solid var(--border);
    }
    .appt-table tbody tr:hover { background: var(--card-hover); }
    .appt-table tbody tr:last-child { border-bottom: none; }

    .appt-table td {
      padding: 12px 16px;
      color: var(--text-main);
      white-space: nowrap;
    }

    .appt-table td .row-num { color: var(--text-muted); }

    .patient-cell { display: flex; align-items: center; gap: 10px; }
    .patient-thumb {
      width: 30px; height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0ea5e9, var(--teal));
      display: flex; align-items: center; justify-content: center;
      font-size: 12px;
      font-weight: 600;
      color: white;
      flex-shrink: 0;
    }

    .pin-badge {
      font-family: 'Courier New', monospace;
      font-size: 12px;
      background: rgba(13,148,136,.15);
      color: var(--teal-light);
      padding: 2px 8px;
      border-radius: 6px;
    }

    .date-badge {
      display: flex;
      align-items: center;
      gap: 5px;
      color: var(--text-muted);
      font-size: 12px;
    }
    .date-badge i { font-size: 12px; }

    /* Action buttons */
    .btn-sm-seen {
      padding: 5px 12px;
      border-radius: 7px;
      border: 1px solid rgba(245,158,11,.3);
      background: rgba(245,158,11,.1);
      color: var(--amber);
      font-family: 'Sora', sans-serif;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: background .18s, border-color .18s;
      white-space: nowrap;
    }
    .btn-sm-seen:hover { background: rgba(245,158,11,.2); border-color: rgba(245,158,11,.5); }

    .btn-sm-consult {
      padding: 5px 12px;
      border-radius: 7px;
      border: 1px solid rgba(16,185,129,.3);
      background: rgba(16,185,129,.1);
      color: var(--green);
      font-family: 'Sora', sans-serif;
      font-size: 11px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: background .18s, border-color .18s;
      white-space: nowrap;
    }
    .btn-sm-consult:hover { background: rgba(16,185,129,.2); border-color: rgba(16,185,129,.5); color: var(--green); }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-muted);
      font-size: 13px;
    }
    .empty-state i { font-size: 32px; margin-bottom: 10px; display: block; opacity: .4; }

    /* ── Payment alerts panel ──────────── */
    .pay-panel {
      background: var(--navy-light);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      margin-bottom: 24px;
      overflow: hidden;
    }

    .pay-panel-header {
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
      font-weight: 600;
      color: var(--amber);
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(245,158,11,.06);
    }

    .pay-item {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 14px 20px;
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }
    .pay-item:last-child { border-bottom: none; }
    .pay-item:hover { background: var(--card-hover); }

    .pay-icon {
      width: 34px; height: 34px;
      border-radius: 9px;
      background: rgba(16,185,129,.15);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .pay-icon i { font-size: 16px; color: var(--green); }

    .pay-info { flex: 1; min-width: 0; }
    .pay-name { font-size: 13px; font-weight: 600; color: var(--white); }
    .pay-service { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .pay-time { font-size: 11px; color: var(--text-muted); margin-top: 4px; display: flex; align-items: center; gap: 4px; }

    .btn-mark-pay {
      padding: 5px 10px;
      border-radius: 7px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.04);
      color: var(--text-muted);
      font-family: 'Sora', sans-serif;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: background .18s, color .18s;
      white-space: nowrap;
      flex-shrink: 0;
      align-self: center;
    }
    .btn-mark-pay:hover { background: rgba(16,185,129,.15); color: var(--green); border-color: rgba(16,185,129,.3); }

    /* ── Welcome card ────────────────── */
    .welcome-card {
      background: linear-gradient(135deg, var(--navy-light) 0%, rgba(13,148,136,.15) 100%);
      border: 1px solid rgba(13,148,136,.25);
      border-radius: var(--radius);
      padding: 24px 28px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }

    .welcome-text h2 {
      font-family: 'Instrument Serif', serif;
      font-size: 1.6rem;
      font-weight: 400;
      color: var(--white);
      margin-bottom: 4px;
    }
    .welcome-text h2 em { font-style: italic; color: var(--teal-light); }
    .welcome-text p { font-size: 13px; color: var(--text-muted); }

    .welcome-stats {
      display: flex;
      gap: 24px;
      flex-shrink: 0;
    }

    .wstat {
      text-align: center;
      padding: 12px 20px;
      background: rgba(0,0,0,.2);
      border: 1px solid var(--border);
      border-radius: 10px;
    }
    .wstat-num {
      font-family: 'Instrument Serif', serif;
      font-size: 26px;
      color: var(--teal-light);
      line-height: 1;
    }
    .wstat-label { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

    /* ── Content placeholder ─────────── */
    .content-area {
      background: var(--navy-light);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .content-placeholder {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 240px;
      color: var(--text-muted);
      gap: 12px;
    }
    .content-placeholder i { font-size: 40px; opacity: .25; }
    .content-placeholder p { font-size: 13px; }

    /* ── Dropdown panel toggle ─────────── */
    .panel-toggle { display: none; }
    .appt-panel-body { }
    .appt-panel-body.collapsed { display: none; }

    /* ── Notifications dropdown ─────── */
    .notif-dropdown {
      position: absolute;
      top: calc(var(--topbar-h) + 8px);
      right: 28px;
      width: 340px;
      background: var(--navy-light);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: 0 20px 60px rgba(0,0,0,.4);
      z-index: 300;
      display: none;
      overflow: hidden;
      animation: fadeDown .2s ease;
    }
    .notif-dropdown.open { display: block; }

    @keyframes fadeDown {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .notif-header {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
      font-weight: 600;
      color: var(--white);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .notif-header span { font-size: 11px; color: var(--text-muted); font-weight: 400; }

    .notif-item {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: flex-start;
      gap: 12px;
      transition: background .15s;
    }
    .notif-item:last-child { border-bottom: none; }
    .notif-item:hover { background: var(--card-hover); }

    .notif-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--green);
      margin-top: 5px;
      flex-shrink: 0;
    }

    .notif-text { font-size: 12.5px; color: var(--text-main); line-height: 1.5; }
    .notif-time { font-size: 11px; color: var(--text-muted); margin-top: 3px; }

    .notif-empty { padding: 28px; text-align: center; color: var(--text-muted); font-size: 13px; }
    .notif-empty i { display: block; font-size: 28px; margin-bottom: 8px; opacity: .3; }

    /* ── Mobile ──────────────────────── */
    .mob-toggle {
      display: none;
      background: none;
      border: none;
      color: var(--text-main);
      font-size: 22px;
      cursor: pointer;
      padding: 4px;
    }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .topbar { left: 0; }
      .main { margin-left: 0; padding: 16px; }
      .mob-toggle { display: block; }
      .welcome-stats { display: none; }
      .welcome-card { padding: 18px; }
      .notif-dropdown { right: 12px; width: calc(100vw - 24px); }
      .search-box input { width: 140px; }
    }

    /* ── Overlay for mobile ──────────── */
    .sidebar-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,.5);
      z-index: 199;
    }
    .sidebar-overlay.open { display: block; }
  </style>
</head>
<body>

<!-- ════════════════════════════════════
     SIDEBAR
════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-hospital"></i></div>
    <div class="brand-text">
      <span class="brand-name">Angelora</span>
      <span class="brand-role">Doctor Panel</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Clinical</div>
    <a href="prescriptions.php"          class="nav-link"><i class="bi bi-capsule"></i> Make Prescription</a>
    <a href="doctor_patient_page.php"    class="nav-link"><i class="bi bi-person-lines-fill"></i> Prescribe Treatment</a>
    <a href="consultation_list.php"      class="nav-link"><i class="bi bi-clipboard2-pulse"></i> View Consultations</a>
    <a href="test.php"                   class="nav-link"><i class="bi bi-eyedropper"></i> View Test Results</a>

    <div class="nav-label">Admin</div>
    <a href="view_logins.php"            class="nav-link"><i class="bi bi-key"></i> View Logins</a>
    <a href="view_nurse_reports.php"     class="nav-link"><i class="bi bi-file-earmark-medical"></i> Nurse Reports</a>

    <div class="nav-label">Account</div>
    <a href="../logout.php" class="nav-link logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>

  <div class="sidebar-footer">
    <div class="doctor-card">
      <div class="doctor-avatar"><?= strtoupper(substr($doctor_name, 0, 1)) ?></div>
      <div class="doctor-info">
        <div class="doctor-name"><?= htmlspecialchars($doctor_name) ?></div>
        <div class="doctor-tag">Physician</div>
      </div>
    </div>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ════════════════════════════════════
     TOP BAR
════════════════════════════════════ -->
<header class="topbar">
  <div style="display:flex; align-items:center; gap:14px;">
    <button class="mob-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
    <div class="topbar-left">
      <span class="topbar-title">Doctor Dashboard</span>
      <span class="topbar-sub"><?= date('l, d F Y') ?></span>
    </div>
  </div>

  <div class="topbar-right">
    <!-- Appointments bell -->
    <button class="icon-btn" id="apptBell" title="Upcoming Appointments">
      <i class="bi bi-calendar-event"></i>
      <span class="badge-count" id="apptCount">0</span>
    </button>

    <!-- Payments bell -->
    <button class="icon-btn" id="payBell" title="Payment Notifications">
      <i class="bi bi-currency-exchange"></i>
      <?php if (!empty($alerts)): ?>
        <span class="badge-count"><?= count($alerts) ?></span>
      <?php endif; ?>
    </button>
  </div>
</header>

<!-- Payment notifications dropdown -->
<div class="notif-dropdown" id="payDropdown">
  <div class="notif-header">
    Payment Alerts
    <span><?= count($alerts ?? []) ?> new</span>
  </div>
  <?php if (!empty($alerts)): ?>
    <?php foreach ($alerts as $alert): ?>
      <div class="notif-item" id="pay-<?= $alert['billing_id'] ?>">
        <span class="notif-dot"></span>
        <div style="flex:1; min-width:0;">
          <div class="notif-text">
            <strong><?= htmlspecialchars($alert['full_name']) ?></strong>
            paid for <strong><?= htmlspecialchars($alert['service_name']) ?></strong>
          </div>
          <div class="notif-time"><i class="bi bi-clock"></i> <?= htmlspecialchars($alert['paid_at']) ?></div>
        </div>
        <button class="btn-mark-pay markSeenBtn" data-id="<?= $alert['billing_id'] ?>">
          Dismiss
        </button>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="notif-empty">
      <i class="bi bi-check2-circle"></i>
      No new payment alerts
    </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════
     MAIN
════════════════════════════════════ -->
<main class="main">

  <!-- Welcome card -->
  <div class="welcome-card">
    <div class="welcome-text">
      <h2>Good <?= (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening') ?>, <em>Dr. <?= htmlspecialchars(explode(' ', $doctor_name)[0]) ?></em></h2>
      <p>Here's your activity overview for today. Use the sidebar to navigate.</p>
    </div>
    <div class="welcome-stats">
      <div class="wstat">
        <div class="wstat-num" id="wstat-appt"><?= count($appointments) ?></div>
        <div class="wstat-label">Pending Appts</div>
      </div>
      <div class="wstat">
        <div class="wstat-num"><?= count($alerts ?? []) ?></div>
        <div class="wstat-label">Pay Alerts</div>
      </div>
      <div class="wstat">
        <div class="wstat-num"><?= count($shifts) ?></div>
        <div class="wstat-label">Shifts</div>
      </div>
    </div>
  </div>

  <!-- ── Appointment Panel ──────────── -->
  <div class="appt-panel" id="apptPanel" style="display:none;">
    <div class="appt-panel-header">
      <div class="appt-panel-title">
        <i class="bi bi-calendar-check"></i>
        Upcoming Appointments
      </div>
      <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" placeholder="Search name, PIN, date…">
      </div>
    </div>
    <div class="appt-table-wrap">
      <table class="appt-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Patient ID</th>
            <th>PIN</th>
            <th>Patient</th>
            <th>Phone</th>
            <th>Date</th>
            <th>Time</th>
            <th>Action</th>
            <th>Consult</th>
          </tr>
        </thead>
        <tbody id="apptTableBody">
          <tr><td colspan="9"><div class="empty-state"><i class="bi bi-hourglass-split"></i>Loading appointments…</div></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Payment Alerts Panel ──────── -->
  <?php if (!empty($alerts)): ?>
  <div class="pay-panel" id="payPanel">
    <div class="pay-panel-header">
      <i class="bi bi-credit-card-2-front"></i>
      Recent Payment Notifications
    </div>
    <?php foreach ($alerts as $alert): ?>
      <div class="pay-item" id="pay-inline-<?= $alert['billing_id'] ?>">
        <div class="pay-icon"><i class="bi bi-check-circle"></i></div>
        <div class="pay-info">
          <div class="pay-name"><?= htmlspecialchars($alert['full_name']) ?></div>
          <div class="pay-service">Paid for: <?= htmlspecialchars($alert['service_name']) ?></div>
          <div class="pay-time"><i class="bi bi-clock"></i> <?= date('d M Y, h:i A', strtotime($alert['paid_at'])) ?></div>
        </div>
        <button class="btn-mark-pay markSeenBtn" data-id="<?= $alert['billing_id'] ?>">
          Dismiss
        </button>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Page content placeholder ──── -->
  <div class="content-area">
    <div class="content-placeholder">
      <i class="bi bi-grid-1x2"></i>
      <p>Select a section from the sidebar to get started.</p>
    </div>
  </div>

</main>

<!-- ════════════════════════════════════
     SCRIPTS
════════════════════════════════════ -->
<script>
// ── Sidebar toggle ──────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

// ── Payment dropdown ─────────────────
document.getElementById('payBell').addEventListener('click', (e) => {
  e.stopPropagation();
  document.getElementById('payDropdown').classList.toggle('open');
});
document.addEventListener('click', () => {
  document.getElementById('payDropdown').classList.remove('open');
});
document.getElementById('payDropdown').addEventListener('click', e => e.stopPropagation());

// ── Appointments bell ─────────────────
const apptBell  = document.getElementById('apptBell');
const apptPanel = document.getElementById('apptPanel');
const apptCount = document.getElementById('apptCount');
const tbody     = document.getElementById('apptTableBody');

apptBell.addEventListener('click', () => {
  const visible = apptPanel.style.display !== 'none';
  apptPanel.style.display = visible ? 'none' : 'block';
  if (!visible) fetchAppointments();
});

// ── Fetch appointments ────────────────
function fetchAppointments() {
  fetch('fetch_appointments.php')
    .then(r => r.json())
    .then(data => {
      tbody.innerHTML = '';
      if (!data.success || !data.appointments.length) {
        tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="bi bi-calendar-x"></i>No upcoming appointments</div></td></tr>`;
        return;
      }
      data.appointments.forEach((a, i) => {
        const initials = a.patient_name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);
        const tr = document.createElement('tr');
        tr.setAttribute('data-id', a.appointment_id);
        tr.innerHTML = `
          <td><span class="row-num">${i+1}</span></td>
          <td>${a.patient_id}</td>
          <td><span class="pin-badge">${a.patient_pin}</span></td>
          <td>
            <div class="patient-cell">
              <div class="patient-thumb">${initials}</div>
              ${a.patient_name}
            </div>
          </td>
          <td>${a.phone}</td>
          <td><div class="date-badge"><i class="bi bi-calendar3"></i>${a.appointment_date}</div></td>
          <td><div class="date-badge"><i class="bi bi-clock"></i>${a.appointment_time}</div></td>
          <td><button class="btn-sm-seen mark-seen-btn"><i class="bi bi-check2"></i> Mark Seen</button></td>
          <td><a href="consultation.php?patient_id=${a.patient_id}" class="btn-sm-consult"><i class="bi bi-arrow-right-circle"></i> Consult</a></td>
        `;
        tbody.appendChild(tr);
      });
      attachSeenHandlers();
    })
    .catch(() => {
      tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="bi bi-wifi-off"></i>Could not load appointments</div></td></tr>`;
    });
}

function attachSeenHandlers() {
  document.querySelectorAll('.mark-seen-btn').forEach(btn => {
    btn.onclick = function () {
      const row = this.closest('tr');
      const id  = row.getAttribute('data-id');
      this.disabled = true;
      this.innerHTML = '<i class="bi bi-hourglass-split"></i>';
      fetch('mark_seen.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ appointment_id: id })
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          row.style.opacity = '0';
          row.style.transition = 'opacity .3s';
          setTimeout(() => { row.remove(); updateCount(); }, 300);
        }
      });
    };
  });
}

function updateCount() {
  fetch('get_appointment_count.php')
    .then(r => r.json())
    .then(d => {
      apptCount.textContent = d.count;
      apptCount.style.background = d.count > 0 ? 'var(--red)' : 'var(--text-muted)';
      document.getElementById('wstat-appt').textContent = d.count;
    });
}

// ── Mark payment seen ────────────────
document.querySelectorAll('.markSeenBtn').forEach(btn => {
  btn.addEventListener('click', function () {
    const id  = this.getAttribute('data-id');
    const el1 = document.getElementById('pay-' + id);
    const el2 = document.getElementById('pay-inline-' + id);
    fetch('mark_seen.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'billing_id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(d => {
      if (d.status === 'success') {
        [el1, el2].forEach(el => {
          if (el) {
            el.style.opacity = '0';
            el.style.transition = 'opacity .3s';
            setTimeout(() => el.remove(), 300);
          }
        });
      }
    });
  });
});

// ── Search filter ─────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
  const val = this.value.toLowerCase();
  document.querySelectorAll('#apptTableBody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
});

// ── Poll for count every 5s ───────────
setInterval(updateCount, 5000);
updateCount();
</script>

</body>
</html>