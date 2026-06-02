<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../includes/auth.php';
checkRole(8);

$staff_name = $_SESSION['user']['full_name'] ?? 'Receptionist';
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$initial = strtoupper(substr($staff_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receptionist Dashboard — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900:   #0f2d6b;
      --blue-800:   #1a3f8f;
      --blue-700:   #1d4ed8;
      --blue-600:   #2563eb;
      --blue-500:   #3b82f6;
      --blue-400:   #60a5fa;
      --blue-300:   #93c5fd;
      --blue-100:   #dbeafe;
      --blue-50:    #eff6ff;
      --white:      #ffffff;
      --gray-50:    #f8fafc;
      --gray-100:   #f1f5f9;
      --gray-200:   #e2e8f0;
      --gray-400:   #94a3b8;
      --gray-500:   #64748b;
      --gray-700:   #334155;
      --gray-900:   #0f172a;
      --green:      #059669;
      --amber:      #d97706;
      --rose:       #e11d48;
      --violet:     #7c3aed;
      --shadow-sm:  0 1px 3px rgba(15,45,107,.08), 0 1px 2px rgba(15,45,107,.06);
      --shadow-md:  0 4px 16px rgba(15,45,107,.10), 0 2px 6px rgba(15,45,107,.07);
      --shadow-lg:  0 12px 40px rgba(15,45,107,.14), 0 4px 12px rgba(15,45,107,.08);
      --radius:     14px;
    }

    html, body {
      min-height: 100vh;
      font-family: 'Sora', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 10px; }

    /* ════ TOP BAR ════ */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 36px;
      height: 66px;
    }

    .topbar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-icon {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 12px rgba(37,99,235,.30);
    }
    .brand-icon i { font-size: 18px; color: white; }

    .brand-text { display: flex; flex-direction: column; }
    .brand-name {
      font-family: 'Instrument Serif', serif;
      font-size: 18px;
      color: var(--blue-800);
      line-height: 1.1;
    }
    .brand-role {
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: var(--blue-500);
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .topbar-date {
      font-size: 12.5px;
      color: var(--gray-400);
      padding: 6px 14px;
      background: var(--gray-100);
      border-radius: 999px;
      border: 1px solid var(--gray-200);
    }

    .avatar {
      width: 38px; height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-weight: 700;
      font-size: 14px;
      color: white;
      box-shadow: 0 4px 12px rgba(37,99,235,.25);
      border: 2px solid var(--blue-100);
    }

    .logout-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: 8px;
      background: #fff0f3;
      border: 1px solid #fecdd3;
      color: var(--rose);
      font-family: 'Sora', sans-serif;
      font-size: 12.5px;
      font-weight: 600;
      text-decoration: none;
      transition: all .18s;
    }
    .logout-btn:hover { background: #ffe4e6; border-color: #fda4af; }

    /* ════ TICKER ════ */
    .ticker {
      background: linear-gradient(90deg, var(--blue-700) 0%, var(--blue-500) 100%);
      overflow: hidden;
      height: 38px;
      display: flex;
      align-items: center;
    }

    .ticker-label {
      background: rgba(0,0,0,.18);
      padding: 0 18px;
      height: 100%;
      display: flex;
      align-items: center;
      font-size: 10.5px;
      font-weight: 700;
      letter-spacing: .13em;
      text-transform: uppercase;
      color: rgba(255,255,255,.92);
      white-space: nowrap;
      flex-shrink: 0;
      gap: 6px;
      border-right: 1px solid rgba(255,255,255,.15);
    }

    .ticker-track { flex: 1; overflow: hidden; }

    .ticker-inner {
      display: inline-flex;
      align-items: center;
      gap: 48px;
      white-space: nowrap;
      animation: ticker 28s linear infinite;
      font-size: 12.5px;
      font-weight: 500;
      color: rgba(255,255,255,.95);
      padding-left: 32px;
    }

    @keyframes ticker {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); }
    }

    .ticker-sep { color: rgba(255,255,255,.3); }

    /* ════ PAGE BODY ════ */
    .page {
      max-width: 1180px;
      margin: 0 auto;
      padding: 40px 28px 64px;
    }

    /* ════ HERO ════ */
    .hero {
      background: linear-gradient(135deg, var(--blue-800) 0%, var(--blue-600) 60%, var(--blue-400) 100%);
      border-radius: var(--radius);
      padding: 40px 44px;
      margin-bottom: 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      flex-wrap: wrap;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow-lg);
    }

    /* decorative circles */
    .hero::before {
      content: '';
      position: absolute;
      top: -70px; right: -70px;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
      pointer-events: none;
    }
    .hero::after {
      content: '';
      position: absolute;
      bottom: -50px; right: 120px;
      width: 180px; height: 180px;
      border-radius: 50%;
      background: rgba(255,255,255,.04);
      pointer-events: none;
    }

    .hero-left { position: relative; z-index: 1; }

    .hero-eyebrow {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .14em;
      text-transform: uppercase;
      color: var(--blue-200, #bfdbfe);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .hero-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: #bfdbfe;
      box-shadow: 0 0 0 3px rgba(191,219,254,.25);
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%,100% { box-shadow: 0 0 0 3px rgba(191,219,254,.25); }
      50%      { box-shadow: 0 0 0 7px rgba(191,219,254,.07); }
    }

    .hero-title {
      font-family: 'Instrument Serif', serif;
      font-size: clamp(1.7rem, 3vw, 2.5rem);
      font-weight: 400;
      color: var(--white);
      line-height: 1.2;
      margin-bottom: 8px;
    }
    .hero-title em { font-style: italic; color: #bfdbfe; }

    .hero-sub {
      font-size: 13.5px;
      color: rgba(255,255,255,.65);
    }

    .hero-right {
      display: flex;
      gap: 14px;
      flex-shrink: 0;
      position: relative;
      z-index: 1;
    }

    .hero-stat {
      text-align: center;
      padding: 16px 24px;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 12px;
      backdrop-filter: blur(8px);
    }
    .hero-stat-num {
      font-family: 'Instrument Serif', serif;
      font-size: 28px;
      color: var(--white);
      line-height: 1;
    }
    .hero-stat-label {
      font-size: 10.5px;
      color: rgba(255,255,255,.6);
      margin-top: 5px;
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    /* ════ SECTION LABEL ════ */
    .section-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--gray-400);
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .section-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--gray-200);
    }

    /* ════ CARD GRID ════ */
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
    }

    /* ════ NAV CARD ════ */
    .nav-card {
      position: relative;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 28px 24px;
      text-decoration: none;
      display: flex;
      flex-direction: column;
      gap: 18px;
      overflow: hidden;
      transition: transform .22s cubic-bezier(.16,1,.3,1),
                  border-color .22s,
                  box-shadow .22s;
      box-shadow: var(--shadow-sm);
    }

    .nav-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
      border-color: var(--c-border, var(--blue-300));
    }

    /* top colour bar */
    .nav-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 4px;
      background: var(--c, var(--blue-600));
      transform: scaleX(0);
      transform-origin: left;
      border-radius: 4px 4px 0 0;
      transition: transform .28s cubic-bezier(.16,1,.3,1);
    }
    .nav-card:hover::after { transform: scaleX(1); }

    /* subtle bg tint on hover */
    .nav-card::before {
      content: '';
      position: absolute;
      inset: 0;
      background: var(--c-bg, rgba(37,99,235,.03));
      opacity: 0;
      transition: opacity .22s;
    }
    .nav-card:hover::before { opacity: 1; }

    /* colour variants */
    .nav-card.blue   { --c: var(--blue-600);  --c-border: var(--blue-300);  --c-bg: rgba(37,99,235,.04);  --c-icon-bg: var(--blue-50);  --c-icon: var(--blue-600); }
    .nav-card.green  { --c: var(--green);      --c-border: #6ee7b7;          --c-bg: rgba(5,150,105,.04);  --c-icon-bg: #ecfdf5;         --c-icon: var(--green); }
    .nav-card.amber  { --c: var(--amber);      --c-border: #fcd34d;          --c-bg: rgba(217,119,6,.04);  --c-icon-bg: #fffbeb;         --c-icon: var(--amber); }
    .nav-card.violet { --c: var(--violet);     --c-border: #c4b5fd;          --c-bg: rgba(124,58,237,.04); --c-icon-bg: #f5f3ff;         --c-icon: var(--violet); }
    .nav-card.rose   { --c: var(--rose);       --c-border: #fda4af;          --c-bg: rgba(225,29,72,.04);  --c-icon-bg: #fff1f2;         --c-icon: var(--rose); }
    .nav-card.sky    { --c: #0284c7;           --c-border: #7dd3fc;          --c-bg: rgba(2,132,199,.04);  --c-icon-bg: #f0f9ff;         --c-icon: #0284c7; }

    .card-icon-wrap {
      width: 52px; height: 52px;
      border-radius: 14px;
      background: var(--c-icon-bg, var(--blue-50));
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      transition: background .22s;
      position: relative; z-index: 1;
    }
    .card-icon-wrap i {
      font-size: 22px;
      color: var(--c-icon, var(--blue-600));
    }

    .card-body-inner {
      display: flex;
      flex-direction: column;
      gap: 5px;
      flex: 1;
      position: relative;
      z-index: 1;
    }

    .card-name {
      font-size: 15px;
      font-weight: 600;
      color: var(--gray-900);
      line-height: 1.3;
    }

    .card-desc {
      font-size: 12.5px;
      color: var(--gray-400);
      line-height: 1.55;
    }

    .card-arrow {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      color: var(--gray-400);
      font-size: 18px;
      transition: color .2s, transform .2s;
      position: relative;
      z-index: 1;
    }
    .nav-card:hover .card-arrow {
      color: var(--c, var(--blue-600));
      transform: translateX(4px);
    }

    /* ════ FOOTER ════ */
    .footer {
      margin-top: 52px;
      padding-top: 22px;
      border-top: 1px solid var(--gray-200);
      text-align: center;
      font-size: 12px;
      color: var(--gray-400);
    }

    /* ════ RESPONSIVE ════ */
    @media (max-width: 768px) {
      .topbar { padding: 0 16px; }
      .topbar-date { display: none; }
      .page { padding: 24px 16px 48px; }
      .hero { padding: 26px 22px; }
      .hero-right { display: none; }
      .card-grid { grid-template-columns: 1fr 1fr; gap: 14px; }
      .nav-card { padding: 20px 16px; }
    }

    @media (max-width: 480px) {
      .card-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- ════ TOP BAR ════ -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <div class="brand-text">
      <span class="brand-name">Angelora</span>
      <span class="brand-role">Reception Portal</span>
    </div>
  </div>
  <div class="topbar-right">
    <span class="topbar-date"><i class="bi bi-calendar3" style="margin-right:5px"></i><?= date('l, d F Y') ?></span>
    <div class="avatar" title="<?= htmlspecialchars($staff_name) ?>"><?= $initial ?></div>
    <a href="../logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</header>

<!-- ════ TICKER ════ -->
<div class="ticker">
  <div class="ticker-label">
    <i class="bi bi-broadcast-pin"></i> Live
  </div>
  <div class="ticker-track">
    <div class="ticker-inner">
      <span>🏥 Welcome to Angelora Hospital Reception</span>
      <span class="ticker-sep">·</span>
      <span>📋 Register new patients quickly using the Patient Registration module</span>
      <span class="ticker-sep">·</span>
      <span>📅 All appointments must be booked through the Appointment module</span>
      <span class="ticker-sep">·</span>
      <span>💊 Doctor orders are routed directly to the billing desk</span>
      <span class="ticker-sep">·</span>
      <span>✅ Monitor all doctor activities from the Doctor Activities section</span>
      <span class="ticker-sep">·</span>
      <span>🏥 Welcome to Angelora Hospital Reception</span>
      <span class="ticker-sep">·</span>
      <span>📋 Register new patients quickly using the Patient Registration module</span>
      <span class="ticker-sep">·</span>
      <span>📅 All appointments must be booked through the Appointment module</span>
      <span class="ticker-sep">·</span>
      <span>💊 Doctor orders are routed directly to the billing desk</span>
      <span class="ticker-sep">·</span>
      <span>✅ Monitor all doctor activities from the Doctor Activities section</span>
    </div>
  </div>
</div>

<!-- ════ PAGE ════ -->
<div class="page">

  <!-- Hero -->
  <div class="hero">
    <div class="hero-left">
      <div class="hero-eyebrow">
        <span class="hero-dot"></span>
        Receptionist Dashboard
      </div>
      <h1 class="hero-title">
        <?= $greeting ?>,<br>
        <em><?= htmlspecialchars(explode(' ', $staff_name)[0]) ?></em>
      </h1>
      <p class="hero-sub">Manage patients, appointments and doctor activities from one place.</p>
    </div>
    <div class="hero-right">
      <div class="hero-stat">
        <div class="hero-stat-num">4</div>
        <div class="hero-stat-label">Modules</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num"><?= date('H:i') ?></div>
        <div class="hero-stat-label">Current Time</div>
      </div>
    </div>
  </div>

  <!-- Cards -->
  <div class="section-label">Quick Access</div>

  <div class="card-grid">

    <a href="patients_register.php" class="nav-card blue">
      <div class="card-icon-wrap">
        <i class="bi bi-person-plus-fill"></i>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Register New Patient</div>
        <div class="card-desc">Add new patients to the hospital system quickly and accurately.</div>
      </div>
      <div class="card-arrow"><i class="bi bi-arrow-right"></i></div>
    </a>

    <a href="book_appointment.php" class="nav-card sky">
      <div class="card-icon-wrap">
        <i class="bi bi-calendar-check-fill"></i>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Book Appointment</div>
        <div class="card-desc">Schedule and manage patient appointments with available doctors.</div>
      </div>
      <div class="card-arrow"><i class="bi bi-arrow-right"></i></div>
    </a>

    <a href="pending_cashier_orders.php" class="nav-card amber">
      <div class="card-icon-wrap">
        <i class="bi bi-bag-fill"></i>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Doctor Orders for Billing</div>
        <div class="card-desc">View and process orders submitted by doctors for billing.</div>
      </div>
      <div class="card-arrow"><i class="bi bi-arrow-right"></i></div>
    </a>

    <a href="view_doctor_submissions.php" class="nav-card green">
      <div class="card-icon-wrap">
        <i class="bi bi-activity"></i>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Doctor Activities</div>
        <div class="card-desc">Monitor submissions and track activity logs by the medical team.</div>
      </div>
      <div class="card-arrow"><i class="bi bi-arrow-right"></i></div>
    </a>

  </div>

  <div class="footer">
    &copy; <?= date('Y') ?> Angelora Hospital &mdash; Receptionist Portal &nbsp;&middot;&nbsp; All activity is logged and monitored.
  </div>

</div>
</body>
</html>