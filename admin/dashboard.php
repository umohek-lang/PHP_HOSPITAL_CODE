<?php
require '../includes/auth.php';
checkRole(1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900: #0f2d6b;
      --blue-800: #1a3f8f;
      --blue-700: #1d4ed8;
      --blue-600: #2563eb;
      --blue-500: #3b82f6;
      --blue-400: #60a5fa;
      --blue-300: #93c5fd;
      --blue-200: #bfdbfe;
      --blue-100: #dbeafe;
      --blue-50:  #eff6ff;
      --white:    #ffffff;
      --gray-50:  #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-400: #94a3b8;
      --gray-500: #64748b;
      --gray-600: #475569;
      --gray-700: #334155;
      --gray-900: #0f172a;
      --red-400:  #f87171;
      --red-bg:   rgba(248,113,113,.10);
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.10), 0 2px 6px rgba(15,45,107,.07);
      --sidebar-w: 256px;
    }

    html, body { height: 100%; font-family: 'Plus Jakarta Sans', sans-serif; background: var(--gray-50); color: var(--gray-700); }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--blue-200); border-radius: 4px; }

    /* ══ SIDEBAR ══ */
    .sidebar {
      position: fixed; top: 0; left: 0;
      width: var(--sidebar-w); height: 100vh;
      background: linear-gradient(170deg, var(--blue-900) 0%, var(--blue-800) 55%, var(--blue-700) 100%);
      display: flex; flex-direction: column;
      z-index: 200;
      box-shadow: 4px 0 28px rgba(15,45,107,.22);
      overflow: hidden;
    }

    /* decorative circles */
    .sidebar::before {
      content: ''; position: absolute; top: -50px; right: -50px;
      width: 180px; height: 180px; border-radius: 50%;
      background: rgba(255,255,255,.05); pointer-events: none;
    }
    .sidebar::after {
      content: ''; position: absolute; bottom: -70px; left: -30px;
      width: 200px; height: 200px; border-radius: 50%;
      background: rgba(255,255,255,.03); pointer-events: none;
    }

    /* brand */
    .sidebar-brand {
      padding: 22px 20px 18px;
      border-bottom: 1px solid rgba(255,255,255,.08);
      flex-shrink: 0;
    }
    .brand-icon-wrap {
      width: 40px; height: 40px;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: #fff;
      margin-bottom: 10px;
    }
    .brand-title {
      color: #fff; font-size: .95rem; font-weight: 700; margin-bottom: 2px;
    }
    .brand-sub {
      color: var(--blue-300); font-size: .72rem; font-weight: 500;
    }

    /* nav */
    .sidebar-nav {
      flex: 1; overflow-y: auto; padding: 8px 10px;
    }
    .sidebar-nav::-webkit-scrollbar { width: 3px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); }

    .nav-section-label {
      padding: 14px 12px 5px;
      font-size: .62rem; font-weight: 700;
      letter-spacing: .13em; text-transform: uppercase;
      color: rgba(255,255,255,.30);
    }

    .nav-link {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px 12px; border-radius: 10px;
      color: rgba(255,255,255,.72);
      text-decoration: none; font-size: .81rem; font-weight: 500;
      line-height: 1.4; margin-bottom: 1px;
      transition: background .18s, color .18s;
      cursor: pointer; position: relative; z-index: 1;
    }
    .nav-link .nl-icon {
      width: 28px; height: 28px; min-width: 28px;
      background: rgba(255,255,255,.08);
      border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem;
      transition: background .18s;
      flex-shrink: 0; margin-top: 1px;
    }
    .nav-link:hover, .nav-link.active {
      background: rgba(255,255,255,.10);
      color: #fff;
    }
    .nav-link:hover .nl-icon, .nav-link.active .nl-icon {
      background: var(--blue-500);
    }
    .nav-link.active { background: rgba(255,255,255,.13); }
    .nl-sub { opacity: .58; font-size: .68rem; display: block; margin-top: 1px; }

    /* footer */
    .sidebar-footer {
      padding: 10px; border-top: 1px solid rgba(255,255,255,.08); flex-shrink: 0;
    }
    .logout-link {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: 10px;
      color: var(--red-400); text-decoration: none;
      font-size: .81rem; font-weight: 600;
      transition: background .18s;
    }
    .logout-link:hover { background: var(--red-bg); color: var(--red-400); }
    .logout-link .nl-icon {
      width: 28px; height: 28px;
      background: rgba(248,113,113,.12);
      border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem;
    }

    /* ══ MAIN ══ */
    .main {
      margin-left: var(--sidebar-w);
      min-height: 100vh;
      display: flex; flex-direction: column;
    }

    /* topbar */
    .topbar {
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
      padding: 0 28px;
      height: 58px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50; flex-shrink: 0;
    }
    .topbar-title { font-size: .95rem; font-weight: 700; color: var(--blue-800); }
    .topbar-sub   { font-size: .72rem; color: var(--gray-400); margin-top: 1px; }
    .admin-badge {
      display: flex; align-items: center; gap: 6px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 5px 14px;
      font-size: .71rem; font-weight: 700;
      color: var(--blue-700); text-transform: uppercase; letter-spacing: .06em;
    }

    /* content */
    .content {
      flex: 1; padding: 18px 24px 24px;
      display: flex; flex-direction: column; gap: 14px;
    }

    /* ══ WELCOME STRIP (ultra-compact) ══ */
    .welcome-strip {
      background: linear-gradient(135deg, var(--blue-800) 0%, var(--blue-600) 60%, var(--blue-400) 100%);
      border-radius: 10px;
      padding: 8px 18px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px;
      box-shadow: 0 4px 16px rgba(37,99,235,.22);
      position: relative; overflow: hidden;
      flex-shrink: 0;
      height: 48px;
    }
    .welcome-strip::before {
      content: ''; position: absolute; top: -20px; right: -20px;
      width: 80px; height: 80px; border-radius: 50%;
      background: rgba(255,255,255,.06); pointer-events: none;
    }
    .ws-left { display: flex; align-items: center; gap: 10px; position: relative; z-index: 1; }
    .ws-avatar {
      width: 28px; height: 28px; border-radius: 7px;
      background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.28);
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem; color: #fff; flex-shrink: 0;
    }
    .ws-greeting { color: rgba(255,255,255,.65); font-size: .68rem; font-weight: 500; }
    .ws-name     { color: #fff; font-size: .82rem; font-weight: 700; }
    .ws-right { display: flex; gap: 8px; position: relative; z-index: 1; flex-shrink: 0; }
    .ws-stat {
      display: flex; align-items: center; gap: 5px;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.16);
      border-radius: 6px; padding: 4px 10px;
    }
    .ws-stat-num { color: #fff; font-size: .8rem; font-weight: 700; }
    .ws-stat-lbl { color: rgba(255,255,255,.55); font-size: .65rem; text-transform: uppercase; letter-spacing: .06em; }

    /* ══ IFRAME PANEL ══ */
    .iframe-panel {
      flex: 1;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      display: flex; flex-direction: column;
      min-height: 0;
    }
    .iframe-header {
      padding: 10px 18px;
      background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200);
      display: flex; align-items: center; gap: 8px;
      flex-shrink: 0;
    }
    .win-dot {
      width: 10px; height: 10px; border-radius: 50%;
    }
    .dot-r { background: #fc5c65; }
    .dot-y { background: #fed330; }
    .dot-g { background: #26de81; }
    .iframe-label {
      margin-left: 6px; font-size: .68rem; font-weight: 700;
      color: var(--gray-400); letter-spacing: .08em; text-transform: uppercase;
    }
    .iframe-url-bar {
      flex: 1; margin-left: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 6px; padding: 4px 10px;
      font-size: .7rem; color: var(--gray-500); font-family: monospace;
      max-width: 320px;
    }

    iframe {
      width: 100%; border: none; background: #fff;
      flex: 1; min-height: 0;
      display: block;
    }

    /* placeholder */
    .iframe-placeholder {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 12px; color: var(--gray-400);
      padding: 40px 20px;
    }
    .iframe-placeholder i { font-size: 2.8rem; color: var(--blue-200); }
    .iframe-placeholder p { font-size: .82rem; color: var(--gray-400); }
    .iframe-placeholder .hint {
      font-size: .73rem; color: var(--gray-300);
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 8px; padding: 6px 14px;
    }

    /* fade-up */
    .fade-up { animation: fadeUp .4s ease both; }
    .fade-up:nth-child(2) { animation-delay: .07s; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<nav class="sidebar">

  <div class="sidebar-brand">
    <div class="brand-icon-wrap"><i class="bi bi-hospital-fill"></i></div>
    <div class="brand-title">Angelora Hospital</div>
    <div class="brand-sub">Admin Panel · <?= htmlspecialchars($_SESSION['user']['full_name']) ?></div>
  </div>

  <div class="sidebar-nav">

    <div class="nav-section-label">Services</div>

    <a href="add_services.php" target="mainFrame"
       class="nav-link" onclick="setActive(this,'Add Services')">
      <span class="nl-icon"><i class="bi bi-database-fill-add"></i></span>
      <span>Add Services<span class="nl-sub">Lab · Nursing · Pharmacy</span></span>
    </a>

    <div class="nav-section-label">Medical Records</div>

    <a href="view_doctor_submissions.php" target="mainFrame"
       class="nav-link" onclick="setActive(this,'Doctor Orders')">
      <span class="nl-icon"><i class="bi bi-clipboard2-pulse-fill"></i></span>
      <span>Doctor Orders</span>
    </a>

    <div class="nav-section-label">Users & Patients</div>

    <a href="patients.php" target="mainFrame"
       class="nav-link" onclick="setActive(this,'View Patients')">
      <span class="nl-icon"><i class="bi bi-people-fill"></i></span>
      <span>View Patients</span>
    </a>

    <a href="register.php" target="mainFrame"
       class="nav-link" onclick="setActive(this,'Register Users')">
      <span class="nl-icon"><i class="bi bi-person-plus-fill"></i></span>
      <span>Register Users</span>
    </a>

  </div>

  <div class="sidebar-footer">
    <a href="../logout.php" class="logout-link">
      <span class="nl-icon"><i class="bi bi-box-arrow-right"></i></span>
      Logout
    </a>
  </div>
</nav>

<!-- ══ MAIN ══ -->
<div class="main">

  <!-- Topbar -->
  <header class="topbar">
    <div>
      <div class="topbar-title">Admin Dashboard</div>
      <div class="topbar-sub">Angelora Hospital Management System</div>
    </div>
    <div class="admin-badge">
      <i class="bi bi-shield-fill-check"></i> Administrator
    </div>
  </header>

  <!-- Content -->
  <div class="content">

    <!-- Welcome strip — compact -->
    <div class="welcome-strip fade-up">
      <div class="ws-left">
        <div class="ws-avatar"><i class="bi bi-person-fill"></i></div>
        <div style="display:flex;align-items:center;gap:6px;">
          <div class="ws-greeting">Welcome back,</div>
          <div class="ws-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></div>
        </div>
      </div>
      <div class="ws-right">
        <div class="ws-stat">
          <div class="ws-stat-num"><?= date('H:i') ?></div>
          <div class="ws-stat-lbl">Time</div>
        </div>
        <div class="ws-stat">
          <div class="ws-stat-num"><?= date('d M Y') ?></div>
          <div class="ws-stat-lbl">Date</div>
        </div>
        <div class="ws-stat">
          <div class="ws-stat-num">4</div>
          <div class="ws-stat-lbl">Modules</div>
        </div>
      </div>
    </div>

    <!-- iFrame panel -->
    <div class="iframe-panel fade-up">
      <div class="iframe-header">
        <span class="win-dot dot-r"></span>
        <span class="win-dot dot-y"></span>
        <span class="win-dot dot-g"></span>
        <span class="iframe-label">Content Area</span>
        <span class="iframe-url-bar" id="urlBar">Select a module from the sidebar</span>
      </div>
      <div class="iframe-placeholder" id="placeholder">
        <i class="bi bi-grid-3x3-gap"></i>
        <p>Select a module from the sidebar to load content here.</p>
        <span class="hint">← Click any item on the left to get started</span>
      </div>
      <iframe name="mainFrame" id="mainFrame" style="display:none;height:calc(100vh - 200px);"></iframe>
    </div>

  </div>
</div>

<script>
  function setActive(el, label) {
    document.querySelectorAll('.nav-link').forEach(a => a.classList.remove('active'));
    el.classList.add('active');

    // show iframe, hide placeholder
    const ph     = document.getElementById('placeholder');
    const iframe = document.getElementById('mainFrame');
    const urlBar = document.getElementById('urlBar');
    ph.style.display     = 'none';
    iframe.style.display = 'block';
    urlBar.textContent   = label || 'Loading…';
  }
</script>
</body>
</html>