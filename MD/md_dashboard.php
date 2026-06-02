<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 9) {
    header('Location: ../login.php');
    exit;
}
$staff_name = $_SESSION['user']['full_name'] ?? 'MD';
$initial    = strtoupper(substr($staff_name, 0, 1));
$hour       = (int)date('H');
$greeting   = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MD Dashboard — Angelora Hospital</title>
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
      --green-600:#059669; --green-50:#ecfdf5; --green-100:#d1fae5; --green-700:#047857;
      --amber-600:#d97706; --amber-50:#fffbeb; --amber-100:#fef3c7;
      --red-600:  #dc2626; --red-50:#fef2f2; --red-100:#fee2e2;
      --violet-600:#7c3aed; --violet-50:#f5f3ff; --violet-100:#ede9fe;
      --sky-600:  #0284c7; --sky-50:#f0f9ff; --sky-100:#e0f2fe;
      --rose-600: #e11d48; --rose-50:#fff1f2; --rose-100:#ffe4e6;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
    }

    html, body { min-height:100vh; font-family:'Sora',sans-serif; background:var(--gray-50); color:var(--gray-700); }
    body::before { content:''; position:fixed; inset:0; z-index:0; pointer-events:none;
      background: radial-gradient(ellipse 700px 500px at 5% 10%,rgba(37,99,235,.05) 0%,transparent 70%),
                  radial-gradient(ellipse 600px 400px at 95% 90%,rgba(96,165,250,.04) 0%,transparent 70%); }
    ::-webkit-scrollbar { width:5px; } ::-webkit-scrollbar-track { background:var(--gray-100); }
    ::-webkit-scrollbar-thumb { background:var(--blue-300); border-radius:4px; }

    /* ── TOPBAR ── */
    .topbar {
      position:sticky; top:0; z-index:200; background:var(--white);
      border-bottom:1px solid var(--gray-200); box-shadow:var(--shadow-sm); height:64px;
      display:flex; align-items:center; justify-content:space-between; padding:0 32px;
    }
    .tb-brand { display:flex; align-items:center; gap:12px; }
    .tb-icon {
      width:38px; height:38px; border-radius:10px;
      background:linear-gradient(135deg,var(--blue-600),var(--blue-400));
      display:flex; align-items:center; justify-content:center; font-size:1.1rem; color:#fff;
      box-shadow:0 4px 12px rgba(37,99,235,.28);
    }
    .tb-name { font-family:'Instrument Serif',serif; font-size:1.1rem; color:var(--blue-800); }
    .tb-sep  { color:var(--gray-300); margin:0 2px; }
    .tb-page { font-size:.78rem; color:var(--blue-600); font-weight:600; }
    .tb-right { display:flex; align-items:center; gap:12px; }
    .tb-date  { font-size:.72rem; color:var(--gray-400); padding:4px 12px;
      background:var(--gray-100); border-radius:999px; border:1px solid var(--gray-200); }
    .tb-avatar {
      width:36px; height:36px; border-radius:50%;
      background:linear-gradient(135deg,var(--blue-600),var(--blue-400));
      display:flex; align-items:center; justify-content:center;
      font-size:.8rem; font-weight:700; color:#fff;
      box-shadow:0 2px 8px rgba(37,99,235,.25);
    }
    .md-badge {
      display:flex; align-items:center; gap:5px; padding:5px 13px;
      background:var(--blue-50); border:1px solid var(--blue-100);
      border-radius:999px; font-size:.68rem; font-weight:700;
      color:var(--blue-700); text-transform:uppercase; letter-spacing:.06em;
    }
    .logout-btn {
      display:flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px;
      background:var(--gray-100); border:1px solid var(--gray-200); color:var(--gray-500);
      font-size:.74rem; font-weight:600; text-decoration:none; transition:all .18s;
    }
    .logout-btn:hover { background:var(--red-50); border-color:var(--red-100); color:var(--red-600); }

    /* ── PAGE ── */
    .page { position:relative; z-index:1; max-width:1260px; margin:0 auto; padding:32px 24px 60px; }

    /* ── WELCOME STRIP ── */
    .welcome-strip {
      background:linear-gradient(135deg,var(--blue-800) 0%,var(--blue-600) 60%,var(--blue-400) 100%);
      border-radius:16px; padding:22px 28px;
      display:flex; align-items:center; justify-content:space-between; gap:16px;
      margin-bottom:28px; position:relative; overflow:hidden;
      box-shadow:0 8px 28px rgba(37,99,235,.28);
    }
    .welcome-strip::before { content:''; position:absolute; top:-40px; right:-40px;
      width:160px; height:160px; border-radius:50%;
      background:rgba(255,255,255,.06); pointer-events:none; }
    .ws-left { display:flex; align-items:center; gap:16px; position:relative; z-index:1; }
    .ws-icon {
      width:48px; height:48px; border-radius:12px;
      background:rgba(255,255,255,.18); border:1.5px solid rgba(255,255,255,.3);
      display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff;
    }
    .ws-greeting { color:rgba(255,255,255,.7); font-size:.78rem; }
    .ws-name     { color:#fff; font-size:1.1rem; font-weight:800; margin-top:2px; letter-spacing:-.01em; }
    .ws-role     { color:rgba(255,255,255,.6); font-size:.7rem; margin-top:1px; }
    .ws-right { display:flex; gap:10px; position:relative; z-index:1; flex-shrink:0; }
    .ws-stat {
      text-align:center; background:rgba(255,255,255,.13);
      border:1px solid rgba(255,255,255,.2); border-radius:10px; padding:10px 18px;
    }
    .ws-stat-num { color:#fff; font-family:'Instrument Serif',serif; font-size:1.5rem; line-height:1; }
    .ws-stat-lbl { color:rgba(255,255,255,.55); font-size:.62rem; text-transform:uppercase; letter-spacing:.07em; margin-top:3px; }

    /* ── SECTION LABEL ── */
    .section-label {
      font-size:.65rem; font-weight:700; letter-spacing:.12em;
      text-transform:uppercase; color:var(--gray-400); margin-bottom:16px;
      display:flex; align-items:center; gap:10px;
    }
    .section-label::after { content:''; flex:1; height:1px; background:var(--gray-200); }

    /* ── STAT CARDS GRID ── */
    .stats-grid {
      display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px;
    }

    /* ── STAT CARD ── */
    .stat-card {
      background:var(--white); border:1.5px solid var(--gray-200);
      border-radius:14px; padding:20px 18px; cursor:pointer;
      box-shadow:var(--shadow-sm);
      transition:transform .22s cubic-bezier(.16,1,.3,1), box-shadow .22s, border-color .22s;
      position:relative; overflow:hidden;
    }
    .stat-card::after {
      content:''; position:absolute; bottom:0; left:0; right:0; height:3px;
      background:var(--c-bar, var(--blue-600));
      transform:scaleX(0); transform-origin:left; border-radius:0 0 2px 2px;
      transition:transform .26s cubic-bezier(.16,1,.3,1);
    }
    .stat-card:hover::after, .stat-card.active::after { transform:scaleX(1); }
    .stat-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-lg); border-color:var(--c-border, var(--blue-200)); }
    .stat-card.active { border-color:var(--c-border, var(--blue-200)); box-shadow:var(--shadow-md), 0 0 0 3px var(--c-glow, rgba(37,99,235,.1)); }

    .sc-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; }
    .sc-icon {
      width:40px; height:40px; border-radius:10px;
      display:flex; align-items:center; justify-content:center; font-size:1.1rem;
      background:var(--c-icon-bg, var(--blue-50)); color:var(--c-icon, var(--blue-600));
    }
    .sc-trend {
      font-size:.65rem; font-weight:700; padding:3px 8px; border-radius:999px;
      background:var(--green-50); border:1px solid var(--green-100); color:var(--green-700);
    }
    .sc-num {
      font-family:'Instrument Serif',serif; font-size:2rem; font-weight:400;
      color:var(--gray-900); line-height:1; margin-bottom:4px;
    }
    .sc-label { font-size:.74rem; color:var(--gray-400); font-weight:500; }

    /* card color variants */
    .sc-blue   { --c-bar:var(--blue-600); --c-border:var(--blue-200); --c-glow:rgba(37,99,235,.1);
                 --c-icon-bg:var(--blue-50); --c-icon:var(--blue-600); }
    .sc-green  { --c-bar:var(--green-600); --c-border:#a7f3d0; --c-glow:rgba(5,150,105,.1);
                 --c-icon-bg:var(--green-50); --c-icon:var(--green-600); }
    .sc-amber  { --c-bar:var(--amber-600); --c-border:#fde68a; --c-glow:rgba(217,119,6,.1);
                 --c-icon-bg:var(--amber-50); --c-icon:var(--amber-600); }
    .sc-red    { --c-bar:var(--red-600);   --c-border:#fca5a5; --c-glow:rgba(220,38,38,.1);
                 --c-icon-bg:var(--red-50); --c-icon:var(--red-600); }
    .sc-sky    { --c-bar:var(--sky-600);   --c-border:#bae6fd; --c-glow:rgba(2,132,199,.1);
                 --c-icon-bg:var(--sky-50); --c-icon:var(--sky-600); }
    .sc-violet { --c-bar:var(--violet-600); --c-border:#c4b5fd; --c-glow:rgba(124,58,237,.1);
                 --c-icon-bg:var(--violet-50); --c-icon:var(--violet-600); }
    .sc-gray   { --c-bar:var(--gray-700);  --c-border:var(--gray-300); --c-glow:rgba(51,65,85,.08);
                 --c-icon-bg:var(--gray-100); --c-icon:var(--gray-700); }
    .sc-rose   { --c-bar:var(--rose-600);  --c-border:#fda4af; --c-glow:rgba(225,29,72,.1);
                 --c-icon-bg:var(--rose-50); --c-icon:var(--rose-600); }

    /* ── NURSE REPORTS CARD ── */
    .nurse-card {
      background:var(--white); border:1.5px solid var(--gray-200);
      border-radius:16px; overflow:hidden; box-shadow:var(--shadow-md); margin-bottom:28px;
    }
    .nc-banner {
      background:linear-gradient(135deg,var(--blue-800),var(--blue-600),var(--blue-400));
      padding:22px 28px; display:flex; align-items:center; gap:18px;
      position:relative; overflow:hidden;
    }
    .nc-banner::before { content:''; position:absolute; top:-30px; right:-30px;
      width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,.07); }
    .nc-banner-icon {
      width:52px; height:52px; border-radius:13px; flex-shrink:0;
      background:rgba(255,255,255,.18); border:1.5px solid rgba(255,255,255,.3);
      display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:#fff;
      position:relative; z-index:1;
    }
    .nc-banner-title { color:#fff; font-size:1.05rem; font-weight:800; position:relative; z-index:1; }
    .nc-banner-sub   { color:rgba(255,255,255,.65); font-size:.78rem; margin-top:3px; position:relative; z-index:1; }
    .nc-body { padding:22px 28px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .nc-desc { font-size:.85rem; color:var(--gray-500); line-height:1.6; max-width:420px; }
    .btn-reports {
      display:flex; align-items:center; gap:8px; padding:11px 26px; border-radius:10px;
      background:linear-gradient(135deg,var(--blue-700),var(--blue-500));
      color:#fff; font-family:'Sora',sans-serif; font-size:.85rem; font-weight:700;
      text-decoration:none; box-shadow:0 5px 16px rgba(37,99,235,.3); transition:all .2s;
      flex-shrink:0;
    }
    .btn-reports:hover { color:#fff; transform:translateY(-2px); box-shadow:0 8px 24px rgba(37,99,235,.4); }

    /* ── DYNAMIC SECTIONS ── */
    .details-section { display:none; margin-top:4px; }

    /* table inside sections */
    .sec-card {
      background:var(--white); border:1px solid var(--gray-200);
      border-radius:14px; overflow:hidden; box-shadow:var(--shadow-sm);
    }
    .sec-head {
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 20px; border-bottom:1px solid var(--gray-100);
      background:#fafcff;
    }
    .sh-left { display:flex; align-items:center; gap:9px; }
    .sh-icon { width:30px; height:30px; border-radius:7px; background:var(--blue-50);
      color:var(--blue-600); display:flex; align-items:center; justify-content:center; font-size:.85rem; }
    .sh-title { font-size:.88rem; font-weight:700; color:var(--gray-900); }
    .sh-count { font-size:.68rem; font-weight:700; padding:3px 10px; border-radius:999px;
      background:var(--blue-50); border:1px solid var(--blue-100); color:var(--blue-700); }

    /* ── AUTO-REFRESH DOT ── */
    .refresh-dot {
      display:flex; align-items:center; gap:6px;
      font-size:.7rem; color:var(--gray-400);
    }
    .rdot { width:7px; height:7px; border-radius:50%; background:var(--green-600);
      animation:rdpulse 2s ease-in-out infinite;
      box-shadow:0 0 0 3px rgba(5,150,105,.2); }
    @keyframes rdpulse {
      0%,100% { box-shadow:0 0 0 3px rgba(5,150,105,.2); }
      50%      { box-shadow:0 0 0 6px rgba(5,150,105,.06); }
    }

    /* ── RESPONSIVE ── */
    @media (max-width:1100px) { .stats-grid { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:800px)  { .stats-grid { grid-template-columns:repeat(2,1fr); } .ws-right { display:none; } }
    @media (max-width:520px)  { .topbar { padding:0 14px; } .tb-date,.md-badge { display:none; } .page { padding:16px 12px 48px; } }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="tb-brand">
    <div class="tb-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="tb-name">Angelora</span>
    <span class="tb-sep">·</span>
    <span class="tb-page">MD Dashboard</span>
  </div>
  <div class="tb-right">
    <span class="tb-date"><i class="bi bi-calendar3" style="margin-right:4px"></i><?= date('l, d M Y') ?></span>
    <div class="md-badge"><i class="bi bi-shield-fill-check"></i> Medical Director</div>
    <div class="refresh-dot"><span class="rdot"></span> Live</div>
    <div class="tb-avatar"><?= htmlspecialchars($initial) ?></div>
    <a href="../logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</header>

<div class="page">

  <!-- WELCOME STRIP -->
  <div class="welcome-strip">
    <div class="ws-left">
      <div class="ws-icon"><i class="bi bi-person-badge-fill"></i></div>
      <div>
        <div class="ws-greeting"><?= $greeting ?>,</div>
        <div class="ws-name">Dr. <?= htmlspecialchars($staff_name) ?></div>
        <div class="ws-role">Medical Director · Angelora Hospital</div>
      </div>
    </div>
    <div class="ws-right">
      <div class="ws-stat">
        <div class="ws-stat-num" id="patientsCount">—</div>
        <div class="ws-stat-lbl">Patients</div>
      </div>
      <div class="ws-stat">
        <div class="ws-stat-num" id="consultationsCount">—</div>
        <div class="ws-stat-lbl">Consults</div>
      </div>
      <div class="ws-stat">
        <div class="ws-stat-num"><?= date('H:i') ?></div>
        <div class="ws-stat-lbl">Time</div>
      </div>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="section-label">Hospital Overview</div>
  <div class="stats-grid" id="summaryCards">

    <div class="stat-card sc-blue" id="patientsCard" onclick="showSection('patientsSection')">
      <div class="sc-top">
        <div class="sc-icon"><i class="bi bi-people-fill"></i></div>
        <span class="sc-trend">Active</span>
      </div>
      <div class="sc-num" id="patientsCountCard">0</div>
      <div class="sc-label">Registered Patients</div>
    </div>

    <div class="stat-card sc-green" id="consultationsCard" onclick="showSection('consultationsSection')">
      <div class="sc-top"><div class="sc-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div></div>
      <div class="sc-num" id="consultationsCountCard">0</div>
      <div class="sc-label">Consultations</div>
    </div>

    <div class="stat-card sc-amber" id="testsCard" onclick="showSection('testsSection')">
      <div class="sc-top"><div class="sc-icon"><i class="bi bi-eyedropper-fill"></i></div></div>
      <div class="sc-num" id="testsCountCard">0</div>
      <div class="sc-label">Lab Tests</div>
    </div>

    <div class="stat-card sc-red" id="billingsCard" onclick="showSection('billingsSection')">
      <div class="sc-top"><div class="sc-icon"><i class="bi bi-receipt-cutoff"></i></div></div>
      <div class="sc-num" id="billingsCountCard">0</div>
      <div class="sc-label">Billing Records</div>
    </div>

    <div class="stat-card sc-sky" id="doctorsCard" onclick="showSection('doctorsSection')">
      <div class="sc-top"><div class="sc-icon"><i class="bi bi-person-badge-fill"></i></div></div>
      <div class="sc-num" id="doctorsCountCard">0</div>
      <div class="sc-label">Doctors</div>
    </div>

    <div class="stat-card sc-violet" id="nursesCard" onclick="showSection('nursesSection')">
      <div class="sc-top"><div class="sc-icon"><i class="bi bi-clipboard2-heart-fill"></i></div></div>
      <div class="sc-num" id="nursesCountCard">0</div>
      <div class="sc-label">Nurses</div>
    </div>

    <div class="stat-card sc-gray" id="pharmacistsCard" onclick="showSection('pharmacistsSection')">
      <div class="sc-top"><div class="sc-icon"><i class="bi bi-capsule-pill"></i></div></div>
      <div class="sc-num" id="pharmacistsCountCard">0</div>
      <div class="sc-label">Pharmacists</div>
    </div>

    <div class="stat-card sc-rose" id="labtechsCard" onclick="showSection('labtechsSection')">
      <div class="sc-top"><div class="sc-icon"><i class="bi bi-flask-fill"></i></div></div>
      <div class="sc-num" id="labtechsCountCard">0</div>
      <div class="sc-label">Lab Technicians</div>
    </div>

  </div>

  <!-- NURSE REPORTS CARD -->
  <div class="section-label">Quick Access</div>
  <div class="nurse-card">
    <div class="nc-banner">
      <div class="nc-banner-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
      <div>
        <div class="nc-banner-title">Nurse Reports</div>
        <div class="nc-banner-sub">Clinical reports submitted by nursing staff</div>
      </div>
    </div>
    <div class="nc-body">
      <div class="nc-desc">
        View and review all nurse reports submitted for patients under your care.
        Reports include vitals trends, observations, and nursing interventions.
      </div>
      <a href="view_nurse_reports.php" class="btn-reports">
        <i class="bi bi-clipboard2-check-fill"></i> View Nurse Reports
      </a>
    </div>
  </div>

  <!-- DYNAMIC SECTIONS (populated by AJAX) -->
  <div id="sectionsContainer"></div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let activeSection = null;

// Track current page number for each section independently
const currentPages = {
  patientsPage:      1,
  consultationsPage: 1,
  testsPage:         1,
  billingsPage:      1,
  doctorsPage:       1,
  nursesPage:        1,
  pharmacistsPage:   1,
  labtechsPage:      1,
};

function showSection(sectionId) {
  $('.details-section').hide();
  $('#' + sectionId).show();
  $('.stat-card').removeClass('active');
  const cardId = sectionId.replace('Section', 'Card');
  $('#' + cardId).addClass('active');
  activeSection = sectionId;
  document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function loadDashboard(pages = {}) {
  // Merge tracked pages with any override pages passed in
  const requestParams = Object.assign({}, currentPages, pages);

  $.getJSON('md_dashboard_content.php', requestParams, function(data) {
    // Update count badges
    for (const key in data.counts) {
      $('#' + key + 'Count').text(data.counts[key]);
      $('#' + key + 'CountCard').text(data.counts[key]);
    }
    // Inject / replace sections
    for (const key in data.sections) {
      if ($('#' + key + 'Section').length) {
        $('#' + key + 'Section').replaceWith(data.sections[key]);
      } else {
        $('#sectionsContainer').append(data.sections[key]);
      }
    }
    // Re-show active section after reload
    if (activeSection) $('#' + activeSection).show();
  });
}

// ── PAGINATION CLICK HANDLER (delegated — works on dynamically injected HTML) ──
$(document).on('click', '.ds-pag-btns .page-link', function(e) {
  e.preventDefault();

  const $btn  = $(this);
  const page  = parseInt($btn.data('page'));
  const param = $btn.data('param'); // e.g. "patientsPage"

  // Ignore disabled buttons or missing data
  if (!param || isNaN(page) || $btn.closest('.page-item').hasClass('disabled')) return;

  // Update tracked page for this section
  currentPages[param] = page;

  // Reload with updated page — pass only this section's page change
  loadDashboard({ [param]: page });
});

loadDashboard();

// Auto-refresh every 3 seconds — uses currentPages so pagination state is preserved
setInterval(() => loadDashboard(), 3000);
</script>
</body>
</html>