<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 4) {
    header("Location: ../login.php");
    exit;
}

$staff_name = $_SESSION['user']['full_name'] ?? 'Cashier';
$initial    = strtoupper(substr($staff_name, 0, 1));
$first_name = explode(' ', $staff_name)[0];
$hour       = (int)date('H');
$greeting   = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cashier Dashboard — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      /* Blues */
      --blue-900:   #1e3a5f;
      --blue-800:   #1e40af;
      --blue-700:   #1d4ed8;
      --blue-600:   #2563eb;
      --blue-500:   #3b82f6;
      --blue-400:   #60a5fa;
      --blue-300:   #93c5fd;
      --blue-200:   #bfdbfe;
      --blue-100:   #dbeafe;
      --blue-50:    #eff6ff;
      --blue-glow:  rgba(37,99,235,.13);

      /* Grays */
      --white:      #ffffff;
      --gray-50:    #f8fafc;
      --gray-100:   #f1f5f9;
      --gray-200:   #e2e8f0;
      --gray-300:   #cbd5e1;
      --gray-400:   #94a3b8;
      --gray-500:   #64748b;
      --gray-600:   #475569;
      --gray-700:   #334155;
      --gray-800:   #1e293b;
      --gray-900:   #0f172a;

      /* Status */
      --green:      #16a34a;
      --green-bg:   #dcfce7;
      --red:        #dc2626;
      --red-bg:     #fee2e2;
      --amber:      #d97706;
      --amber-bg:   #fef3c7;

      --radius:     14px;
      --shadow-sm:  0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.05);
      --shadow:     0 4px 16px rgba(37,99,235,.08), 0 1px 4px rgba(0,0,0,.06);
      --shadow-lg:  0 12px 40px rgba(37,99,235,.14), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Sora', sans-serif;
      background: var(--gray-50);
      color: var(--gray-800);
    }

    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

    /* ════ TOP BAR ═══════════════════ */
    .topbar {
      position: sticky; top: 0; z-index: 100;
      height: 64px;
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: 0 1px 8px rgba(0,0,0,.06);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 36px;
    }

    .topbar-brand { display: flex; align-items: center; gap: 12px; }

    .brand-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .brand-mark i { font-size: 17px; color: white; }

    .brand-text { display: flex; flex-direction: column; gap: 1px; }
    .brand-name {
      font-family: 'Instrument Serif', serif;
      font-size: 17px; color: var(--gray-900); line-height: 1;
    }
    .brand-role {
      font-size: 10px; font-weight: 700; letter-spacing: .14em;
      text-transform: uppercase; color: var(--blue-600); line-height: 1;
    }

    .topbar-right { display: flex; align-items: center; gap: 12px; }

    .date-pill {
      display: flex; align-items: center; gap: 7px;
      padding: 6px 14px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      font-size: 12px; color: var(--blue-700); font-weight: 500;
    }
    .date-pill i { font-size: 13px; color: var(--blue-500); }

    .topbar-divider { width: 1px; height: 22px; background: var(--gray-200); }

    .staff-chip { display: flex; align-items: center; gap: 9px; }
    .staff-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700; color: white;
      border: 2px solid var(--blue-200);
      box-shadow: 0 2px 8px rgba(37,99,235,.2);
      position: relative;
    }
    .online-dot {
      position: absolute; bottom: 0; right: 0;
      width: 9px; height: 9px; border-radius: 50%;
      background: var(--green); border: 2px solid var(--white);
    }
    .staff-name { font-size: 13px; font-weight: 600; color: var(--gray-700); }

    /* ════ PAGE ══════════════════════ */
    .page { max-width: 1000px; margin: 0 auto; padding: 44px 28px 72px; }

    /* ════ HERO ══════════════════════ */
    .hero {
      position: relative; overflow: hidden;
      background: linear-gradient(135deg, var(--blue-800) 0%, var(--blue-600) 60%, var(--blue-400) 100%);
      border-radius: 20px;
      padding: 44px 48px;
      margin-bottom: 40px;
      display: flex; align-items: center;
      justify-content: space-between; gap: 24px; flex-wrap: wrap;
      box-shadow: var(--shadow-lg);
    }

    /* Decorative circles */
    .hero::before {
      content: '';
      position: absolute; top: -80px; right: -60px;
      width: 320px; height: 320px; border-radius: 50%;
      background: rgba(255,255,255,.07); pointer-events: none;
    }
    .hero::after {
      content: '';
      position: absolute; bottom: -100px; right: 120px;
      width: 240px; height: 240px; border-radius: 50%;
      background: rgba(255,255,255,.05); pointer-events: none;
    }

    /* Dot grid */
    .hero-dots {
      position: absolute; inset: 0; pointer-events: none; z-index: 0;
      opacity: .08;
      background-image: radial-gradient(circle, white 1px, transparent 1px);
      background-size: 24px 24px;
    }

    .hero-left { position: relative; z-index: 1; }
    .hero-right { position: relative; z-index: 1; display: flex; gap: 14px; flex-shrink: 0; }

    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.3);
      border-radius: 20px; padding: 5px 14px;
      font-size: 10.5px; font-weight: 700;
      letter-spacing: .12em; text-transform: uppercase;
      color: rgba(255,255,255,.9); margin-bottom: 18px;
    }
    .live-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: #4ade80;
      box-shadow: 0 0 0 3px rgba(74,222,128,.3);
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%,100% { box-shadow: 0 0 0 3px rgba(74,222,128,.3); }
      50%      { box-shadow: 0 0 0 7px rgba(74,222,128,.08); }
    }

    .hero-title {
      font-family: 'Instrument Serif', serif;
      font-size: clamp(1.8rem, 4vw, 2.8rem);
      font-weight: 400; color: white;
      line-height: 1.15; margin-bottom: 10px;
    }
    .hero-title em { font-style: italic; color: var(--blue-200); }

    .hero-sub {
      font-size: 14px; color: rgba(255,255,255,.75);
      line-height: 1.65; max-width: 400px;
    }

    /* Stat boxes */
    .stat-box {
      min-width: 108px; text-align: center;
      padding: 18px 22px;
      background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.25);
      border-radius: 14px; backdrop-filter: blur(8px);
      position: relative; overflow: hidden;
      transition: background .2s;
    }
    .stat-box:hover { background: rgba(255,255,255,.22); }
    .stat-box::before {
      content: ''; position: absolute;
      bottom: 0; left: 0; right: 0; height: 2px;
      background: rgba(255,255,255,.4);
    }
    .stat-num {
      font-family: 'Instrument Serif', serif;
      font-size: 30px; color: white; line-height: 1;
    }
    .stat-label {
      font-size: 10px; color: rgba(255,255,255,.65);
      margin-top: 5px; letter-spacing: .08em; text-transform: uppercase;
    }

    /* ════ SECTION LABEL ══════════════ */
    .section-label {
      display: flex; align-items: center; gap: 12px;
      font-size: 10.5px; font-weight: 700;
      letter-spacing: .16em; text-transform: uppercase;
      color: var(--gray-500); margin-bottom: 16px;
    }
    .section-pip {
      width: 20px; height: 3px; border-radius: 2px;
      background: linear-gradient(90deg, var(--blue-700), var(--blue-400));
      flex-shrink: 0;
    }
    .section-label::after {
      content: ''; flex: 1; height: 1px;
      background: var(--gray-200);
    }

    /* ════ CARD GRID ══════════════════ */
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 16px; margin-bottom: 32px;
    }

    /* ════ NAV CARD ═══════════════════ */
    .nav-card {
      position: relative; overflow: hidden;
      background: var(--white);
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 28px 24px;
      text-decoration: none;
      display: flex; flex-direction: column; gap: 18px;
      box-shadow: var(--shadow-sm);
      transition:
        transform .22s cubic-bezier(.16,1,.3,1),
        border-color .22s,
        box-shadow .22s;
    }

    /* Left colour strip */
    .nav-card::before {
      content: '';
      position: absolute; top: 0; left: 0; bottom: 0; width: 4px;
      background: var(--c-strip, var(--blue-500));
      border-radius: var(--radius) 0 0 var(--radius);
      transform: scaleY(0); transform-origin: bottom;
      transition: transform .28s cubic-bezier(.16,1,.3,1);
    }
    .nav-card:hover {
      transform: translateY(-4px);
      border-color: var(--c-border, var(--blue-300));
      box-shadow: var(--shadow-lg);
    }
    .nav-card:hover::before { transform: scaleY(1); }

    /* Colour variants */
    .nav-card.blue {
      --c-strip:  linear-gradient(180deg, var(--blue-700), var(--blue-400));
      --c-border: var(--blue-300);
      --c-icon-bg: var(--blue-50);
      --c-icon-bd: var(--blue-100);
      --c-icon:   var(--blue-600);
    }
    .nav-card.gray {
      --c-strip:  linear-gradient(180deg, var(--gray-600), var(--gray-400));
      --c-border: var(--gray-300);
      --c-icon-bg: var(--gray-100);
      --c-icon-bd: var(--gray-200);
      --c-icon:   var(--gray-600);
    }
    .nav-card.red {
      --c-strip:  linear-gradient(180deg, #991b1b, var(--red));
      --c-border: #fca5a5;
      --c-icon-bg: var(--red-bg);
      --c-icon-bd: #fecaca;
      --c-icon:   var(--red);
    }

    .card-head {
      display: flex; align-items: flex-start;
      justify-content: space-between; gap: 12px;
    }

    .card-icon {
      width: 50px; height: 50px; border-radius: 14px; flex-shrink: 0;
      background: var(--c-icon-bg, var(--blue-50));
      border: 1px solid var(--c-icon-bd, var(--blue-100));
      display: flex; align-items: center; justify-content: center;
      transition: transform .2s;
    }
    .nav-card:hover .card-icon { transform: scale(1.08) rotate(-3deg); }
    .card-icon i { font-size: 22px; color: var(--c-icon, var(--blue-600)); }

    .card-arrow {
      width: 32px; height: 32px; border-radius: 9px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: center;
      color: var(--gray-400); font-size: 14px;
      transition: background .2s, border-color .2s, color .2s, transform .2s;
    }
    .nav-card:hover .card-arrow {
      background: var(--c-icon-bg, var(--blue-50));
      border-color: var(--c-icon-bd, var(--blue-200));
      color: var(--c-icon, var(--blue-600));
      transform: translate(2px, -2px);
    }

    .card-body { display: flex; flex-direction: column; gap: 6px; }
    .card-name {
      font-size: 15px; font-weight: 700; color: var(--gray-900); line-height: 1.3;
    }
    .nav-card.red .card-name { color: var(--red); }
    .card-desc { font-size: 13px; color: var(--gray-500); line-height: 1.6; }

    .card-cta {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11.5px; font-weight: 700; color: var(--c-icon, var(--blue-600));
      letter-spacing: .04em;
      opacity: 0; transform: translateY(5px);
      transition: opacity .2s, transform .2s;
    }
    .nav-card:hover .card-cta { opacity: 1; transform: translateY(0); }

    /* ════ FOOTER ══════════════════════ */
    .page-footer {
      margin-top: 52px; padding-top: 20px;
      border-top: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: center;
      gap: 10px; font-size: 12px; color: var(--gray-400);
    }
    .footer-sep { width: 3px; height: 3px; border-radius: 50%; background: var(--gray-300); }

    /* ════ RESPONSIVE ══════════════════ */
    @media (max-width: 768px) {
      .topbar { padding: 0 18px; }
      .topbar-divider, .staff-name { display: none; }
      .page { padding: 24px 14px 52px; }
      .hero { padding: 28px 24px; }
      .hero-right { display: none; }
      .card-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
      .nav-card { padding: 20px 18px; }
    }
    @media (max-width: 480px) {
      .card-grid { grid-template-columns: 1fr; }
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
      <span class="brand-role">Cashier Portal</span>
    </div>
  </div>
  <div class="topbar-right">
    <div class="date-pill">
      <i class="bi bi-calendar3"></i>
      <?= date('D, d M Y') ?>
    </div>
    <div class="topbar-divider"></div>
    <div class="staff-chip">
      <div class="staff-avatar">
        <?= $initial ?>
        <span class="online-dot"></span>
      </div>
      <span class="staff-name"><?= htmlspecialchars($first_name) ?></span>
    </div>
  </div>
</header>

<!-- ════ PAGE ════════════════════════════ -->
<div class="page">

  <!-- ── Hero ──────────────────────────── -->
  <div class="hero">
    <div class="hero-dots"></div>

    <div class="hero-left">
      <div class="hero-badge">
        <span class="live-dot"></span>
        Cashier Dashboard — Active
      </div>
      <h1 class="hero-title">
        <?= $greeting ?>,<br>
        <em><?= htmlspecialchars($first_name) ?></em>
      </h1>
      <p class="hero-sub">
        Manage billing, invoices, and today's patient appointments — all from one place.
      </p>
    </div>

    <div class="hero-right">
      <div class="stat-box">
        <div class="stat-num" id="live-time"><?= date('H:i') ?></div>
        <div class="stat-label">Current Time</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">2</div>
        <div class="stat-label">Modules</div>
      </div>
    </div>
  </div>

  <!-- ── Quick Access ───────────────────── -->
  <div class="section-label">
    <span class="section-pip"></span>
    Quick Access
  </div>

  <div class="card-grid">

    <a href="view_appointments.php" class="nav-card blue">
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body">
        <div class="card-name">Today's Appointments</div>
        <div class="card-desc">View and manage all patient appointments scheduled for today.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> Open module</span>
    </a>

    <a href="bill_dashboard.php" class="nav-card gray">
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body">
        <div class="card-name">Bill Management</div>
        <div class="card-desc">Access the full billing dashboard to process and track patient bills.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> Open module</span>
    </a>

  </div>

  <!-- ── Account ────────────────────────── -->
  <div class="section-label">
    <span class="section-pip"></span>
    Account
  </div>

  <div class="card-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">

    <a href="../logout.php" class="nav-card red">
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-box-arrow-right"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body">
        <div class="card-name">Logout</div>
        <div class="card-desc">Sign out of the cashier portal securely.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> Sign out</span>
    </a>

  </div>

  <!-- ── Footer ─────────────────────────── -->
  <div class="page-footer">
    <span>&copy; <?= date('Y') ?> Angelora Hospital</span>
    <span class="footer-sep"></span>
    <span>Cashier Portal</span>
    <span class="footer-sep"></span>
    <span>All activity is logged and monitored</span>
  </div>

</div>

<script>
  function tick() {
    const now = new Date();
    const el = document.getElementById('live-time');
    if (el) el.textContent =
      String(now.getHours()).padStart(2,'0') + ':' +
      String(now.getMinutes()).padStart(2,'0');
  }
  setInterval(tick, 1000);
</script>
</body>
</html>