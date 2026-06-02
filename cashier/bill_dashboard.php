<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billing Dashboard — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900:  #1e3a5f;
      --blue-800:  #1e40af;
      --blue-700:  #1d4ed8;
      --blue-600:  #2563eb;
      --blue-500:  #3b82f6;
      --blue-400:  #60a5fa;
      --blue-300:  #93c5fd;
      --blue-200:  #bfdbfe;
      --blue-100:  #dbeafe;
      --blue-50:   #eff6ff;
      --blue-glow: rgba(37,99,235,.12);

      --white:     #ffffff;
      --gray-50:   #f8fafc;
      --gray-100:  #f1f5f9;
      --gray-200:  #e2e8f0;
      --gray-300:  #cbd5e1;
      --gray-400:  #94a3b8;
      --gray-500:  #64748b;
      --gray-600:  #475569;
      --gray-700:  #334155;
      --gray-800:  #1e293b;
      --gray-900:  #0f172a;

      --green:     #16a34a;
      --green-bg:  #dcfce7;
      --green-100: #bbf7d0;
      --amber:     #d97706;
      --amber-bg:  #fef3c7;
      --red:       #dc2626;
      --red-bg:    #fee2e2;
      --violet:    #7c3aed;
      --violet-bg: #ede9fe;
      --teal:      #0d9488;
      --teal-bg:   #ccfbf1;

      --radius:    14px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.05);
      --shadow:    0 4px 16px rgba(37,99,235,.09), 0 1px 4px rgba(0,0,0,.06);
      --shadow-lg: 0 12px 40px rgba(37,99,235,.15), 0 2px 8px rgba(0,0,0,.07);
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
    .brand-sub {
      font-size: 10px; font-weight: 700; letter-spacing: .14em;
      text-transform: uppercase; color: var(--blue-600); line-height: 1;
    }

    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .date-pill {
      display: flex; align-items: center; gap: 7px;
      padding: 6px 14px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      font-size: 12px; color: var(--blue-700); font-weight: 500;
    }
    .date-pill i { color: var(--blue-500); font-size: 12px; }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 12.5px; font-weight: 500; text-decoration: none;
      transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-600); }

    /* ════ PAGE ══════════════════════ */
    .page { max-width: 1100px; margin: 0 auto; padding: 44px 28px 72px; }

    /* ════ HERO ══════════════════════ */
    .hero {
      position: relative; overflow: hidden;
      background: linear-gradient(135deg, var(--blue-800) 0%, var(--blue-600) 55%, var(--blue-400) 100%);
      border-radius: 20px; padding: 44px 48px;
      margin-bottom: 40px;
      display: flex; align-items: center;
      justify-content: space-between; gap: 24px; flex-wrap: wrap;
      box-shadow: var(--shadow-lg);
    }
    .hero::before {
      content: ''; position: absolute; top: -80px; right: -60px;
      width: 300px; height: 300px; border-radius: 50%;
      background: rgba(255,255,255,.07); pointer-events: none;
    }
    .hero::after {
      content: ''; position: absolute; bottom: -90px; right: 140px;
      width: 220px; height: 220px; border-radius: 50%;
      background: rgba(255,255,255,.05); pointer-events: none;
    }
    .hero-dots {
      position: absolute; inset: 0; pointer-events: none; z-index: 0; opacity: .07;
      background-image: radial-gradient(circle, white 1px, transparent 1px);
      background-size: 22px 22px;
    }
    .hero-left { position: relative; z-index: 1; }
    .hero-right { position: relative; z-index: 1; display: flex; gap: 14px; flex-shrink: 0; }

    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.28);
      border-radius: 20px; padding: 5px 14px;
      font-size: 10.5px; font-weight: 700; letter-spacing: .12em;
      text-transform: uppercase; color: rgba(255,255,255,.9);
      margin-bottom: 16px;
    }
    .live-dot {
      width: 7px; height: 7px; border-radius: 50%; background: #4ade80;
      animation: ping 2s ease-in-out infinite;
    }
    @keyframes ping {
      0%,100% { box-shadow: 0 0 0 0 rgba(74,222,128,.5); }
      50%      { box-shadow: 0 0 0 6px rgba(74,222,128,.0); }
    }

    .hero-title {
      font-family: 'Instrument Serif', serif;
      font-size: clamp(1.8rem, 3.5vw, 2.7rem);
      font-weight: 400; color: white; line-height: 1.15; margin-bottom: 10px;
    }
    .hero-title em { font-style: italic; color: var(--blue-200); }
    .hero-sub { font-size: 14px; color: rgba(255,255,255,.72); line-height: 1.65; max-width: 400px; }

    .stat-box {
      min-width: 100px; text-align: center;
      padding: 16px 20px;
      background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.22);
      border-radius: 13px; backdrop-filter: blur(6px);
      position: relative; overflow: hidden;
      transition: background .2s;
    }
    .stat-box:hover { background: rgba(255,255,255,.22); }
    .stat-box::after {
      content: ''; position: absolute;
      bottom: 0; left: 0; right: 0; height: 2px;
      background: rgba(255,255,255,.35);
    }
    .stat-num { font-family: 'Instrument Serif', serif; font-size: 28px; color: white; line-height: 1; }
    .stat-lbl { font-size: 10px; color: rgba(255,255,255,.62); margin-top: 4px; letter-spacing: .07em; text-transform: uppercase; }

    /* ════ SECTION LABEL ══════════════ */
    .section-label {
      display: flex; align-items: center; gap: 12px;
      font-size: 10.5px; font-weight: 700; letter-spacing: .16em;
      text-transform: uppercase; color: var(--gray-500); margin-bottom: 18px;
    }
    .section-pip {
      width: 20px; height: 3px; border-radius: 2px;
      background: linear-gradient(90deg, var(--blue-700), var(--blue-400)); flex-shrink: 0;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: var(--gray-200); }

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
      padding: 28px 26px;
      text-decoration: none;
      display: flex; flex-direction: column; gap: 16px;
      box-shadow: var(--shadow-sm);
      transition:
        transform .22s cubic-bezier(.16,1,.3,1),
        border-color .22s, box-shadow .22s;
    }

    /* Left colour strip on hover */
    .nav-card::before {
      content: '';
      position: absolute; top: 0; left: 0; bottom: 0; width: 4px;
      background: var(--c-strip);
      border-radius: var(--radius) 0 0 var(--radius);
      transform: scaleY(0); transform-origin: bottom;
      transition: transform .28s cubic-bezier(.16,1,.3,1);
    }
    .nav-card:hover {
      transform: translateY(-4px);
      border-color: var(--c-border);
      box-shadow: var(--shadow-lg);
    }
    .nav-card:hover::before { transform: scaleY(1); }

    /* ── Colour variants ── */
    .c-blue   { --c-strip: linear-gradient(180deg,var(--blue-700),var(--blue-400));   --c-border:var(--blue-300);    --c-icon-bg:var(--blue-50);   --c-icon-bd:var(--blue-100);   --c-icon:var(--blue-600);  }
    .c-green  { --c-strip: linear-gradient(180deg,#166534,var(--green));               --c-border:var(--green-100);   --c-icon-bg:var(--green-bg);  --c-icon-bd:var(--green-100);  --c-icon:var(--green);     }
    .c-teal   { --c-strip: linear-gradient(180deg,#134e4a,var(--teal));                --c-border:#99f6e4;             --c-icon-bg:var(--teal-bg);   --c-icon-bd:#99f6e4;           --c-icon:var(--teal);      }
    .c-gray   { --c-strip: linear-gradient(180deg,var(--gray-700),var(--gray-400));    --c-border:var(--gray-300);    --c-icon-bg:var(--gray-100);  --c-icon-bd:var(--gray-200);   --c-icon:var(--gray-600);  }
    .c-red    { --c-strip: linear-gradient(180deg,#991b1b,var(--red));                 --c-border:#fca5a5;             --c-icon-bg:var(--red-bg);    --c-icon-bd:#fecaca;           --c-icon:var(--red);       }
    .c-violet { --c-strip: linear-gradient(180deg,#4c1d95,var(--violet));              --c-border:#c4b5fd;             --c-icon-bg:var(--violet-bg); --c-icon-bd:#c4b5fd;           --c-icon:var(--violet);    }

    .card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }

    .card-icon {
      width: 50px; height: 50px; border-radius: 14px; flex-shrink: 0;
      background: var(--c-icon-bg); border: 1px solid var(--c-icon-bd);
      display: flex; align-items: center; justify-content: center;
      transition: transform .2s;
    }
    .nav-card:hover .card-icon { transform: scale(1.08) rotate(-3deg); }
    .card-icon i { font-size: 22px; color: var(--c-icon); }

    .card-arrow {
      width: 32px; height: 32px; border-radius: 9px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: center;
      color: var(--gray-400); font-size: 14px;
      transition: background .2s, border-color .2s, color .2s, transform .2s;
    }
    .nav-card:hover .card-arrow {
      background: var(--c-icon-bg); border-color: var(--c-icon-bd);
      color: var(--c-icon); transform: translate(2px,-2px);
    }

    .card-body-inner { display: flex; flex-direction: column; gap: 5px; }
    .card-name { font-size: 15px; font-weight: 700; color: var(--gray-900); line-height: 1.3; }
    .card-desc { font-size: 13px; color: var(--gray-500); line-height: 1.6; }

    .card-cta {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11.5px; font-weight: 700;
      color: var(--c-icon); letter-spacing: .04em;
      opacity: 0; transform: translateY(5px);
      transition: opacity .2s, transform .2s;
    }
    .nav-card:hover .card-cta { opacity: 1; transform: translateY(0); }

    /* Badge on Orders card */
    .order-badge {
      display: none;
      min-width: 20px; height: 20px; border-radius: 20px;
      background: var(--red); color: white;
      font-size: 10px; font-weight: 700;
      align-items: center; justify-content: center;
      padding: 0 6px;
      position: absolute; top: 20px; right: 20px;
      box-shadow: 0 2px 6px rgba(220,38,38,.4);
      animation: pop .3s cubic-bezier(.16,1,.3,1);
    }
    @keyframes pop { from { transform: scale(0); } to { transform: scale(1); } }
    .order-badge.visible { display: flex; }

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
      .topbar { padding: 0 16px; }
      .date-pill { display: none; }
      .page { padding: 24px 14px 52px; }
      .hero { padding: 28px 22px; }
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
      <span class="brand-sub">Billing System</span>
    </div>
  </div>
  <div class="topbar-right">
    <div class="date-pill">
      <i class="bi bi-calendar3"></i>
      <script>document.write(new Date().toLocaleDateString('en-NG',{weekday:'short',day:'numeric',month:'short',year:'numeric'}))</script>
    </div>
    <a href="dashboard.php" class="back-btn">
      <i class="bi bi-arrow-left"></i> Dashboard
    </a>
  </div>
</header>

<!-- ════ PAGE ════════════════════════════ -->
<div class="page">

  <!-- Hero -->
  <div class="hero">
    <div class="hero-dots"></div>
    <div class="hero-left">
      <div class="hero-badge">
        <span class="live-dot"></span>
        Hospital Billing System
      </div>
      <h1 class="hero-title">
        Billing <em>Dashboard</em>
      </h1>
      <p class="hero-sub">Manage services, patient bills, doctor orders, and payment records from one place.</p>
    </div>
    <div class="hero-right">
      <div class="stat-box">
        <div class="stat-num">5</div>
        <div class="stat-lbl">Modules</div>
      </div>
      <div class="stat-box">
        <div class="stat-num" id="live-time">--:--</div>
        <div class="stat-lbl">Current Time</div>
      </div>
    </div>
  </div>

  <!-- Section -->
  <div class="section-label">
    <span class="section-pip"></span>
    Billing Modules
  </div>

  <div class="card-grid">

    <!-- Doctor Activities -->
    <a href="view_doctors_submissions.php" class="nav-card c-blue">
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-person-badge-fill"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Doctor Activities</div>
        <div class="card-desc">View all submissions and activities logged by doctors.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> View activities</span>
    </a>

    <!-- Add Service -->
    <a href="add_service3.php" class="nav-card c-green">
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-plus-circle-fill"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Add Service</div>
        <div class="card-desc">Add a new medical service and set its cost.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> Add service</span>
    </a>

    <!-- Bill Patient -->
    <a href="bill_patient2.php" class="nav-card c-teal">
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Bill Patient</div>
        <div class="card-desc">Assign services to a patient and generate a bill.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> Create bill</span>
    </a>

    <!-- View Paid Bills -->
    <a href="select_patient_paid_bills.php" class="nav-card c-gray">
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-check2-circle"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body-inner">
        <div class="card-name">View Paid Bills</div>
        <div class="card-desc">Check and review a patient's paid billing records.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> View records</span>
    </a>

    <!-- Orders to Bill -->
    <a href="pending_cashier_orders.php" class="nav-card c-red" id="ordersCard">
      <span class="order-badge" id="orderBadge"></span>
      <div class="card-head">
        <div class="card-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div>
        <div class="card-arrow"><i class="bi bi-arrow-up-right"></i></div>
      </div>
      <div class="card-body-inner">
        <div class="card-name">Orders to Bill</div>
        <div class="card-desc">Review and process pending orders sent by doctors.</div>
      </div>
      <span class="card-cta"><i class="bi bi-arrow-right"></i> Review orders</span>
    </a>

  </div>

  <!-- Footer -->
  <div class="page-footer">
    <span>&copy; <script>document.write(new Date().getFullYear())</script> Angelora Hospital</span>
    <span class="footer-sep"></span>
    <span>Billing System</span>
    <span class="footer-sep"></span>
    <span>All transactions are logged and monitored</span>
  </div>

</div>

<script>
  // Live clock
  (function tick() {
    const el = document.getElementById('live-time');
    if (el) {
      const n = new Date();
      el.textContent =
        String(n.getHours()).padStart(2,'0') + ':' +
        String(n.getMinutes()).padStart(2,'0');
    }
    setTimeout(tick, 1000);
  })();

  // Fetch pending orders badge
  document.addEventListener('DOMContentLoaded', function () {
    fetch('get_pending_orders_count.php')
      .then(r => r.text())
      .then(count => {
        const n = parseInt(count);
        if (!isNaN(n) && n > 0) {
          const badge = document.getElementById('orderBadge');
          if (badge) {
            badge.textContent = n > 99 ? '99+' : n;
            badge.classList.add('visible');
          }
        }
      })
      .catch(err => console.error('Badge fetch error:', err));
  });
</script>
</body>
</html>