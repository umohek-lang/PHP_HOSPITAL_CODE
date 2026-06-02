<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

require '../includes/auth.php';
require '../db.php';
require '../payment_alerts.php';

checkRole(6);

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM lab_orders WHERE status = 'pending'");
$stmtCount->execute();
$newOrders = $stmtCount->fetchColumn();

$stmt = $pdo->prepare("
    SELECT o.*, p.full_name 
    FROM patient_orders o
    JOIN patients p ON o.patient_id = p.patient_id
    WHERE o.status = 'pending' AND o.service_type = 'lab'
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

$userName = htmlspecialchars($_SESSION['user']['full_name']);
$shift    = htmlspecialchars($_SESSION['user']['shift'] ?? 'N/A');
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $_SESSION['user']['full_name']), 0, 2)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lab Dashboard — Angelora</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --black:    #0a0a0a;
      --ink:      #111318;
      --ink-2:    #1c2029;
      --ink-3:    #262d3a;
      --gray-1:   #3d4452;
      --gray-2:   #6b7385;
      --gray-3:   #9aa0ad;
      --gray-4:   #c5c9d1;
      --gray-5:   #e2e4e9;
      --gray-6:   #f0f2f5;
      --gray-7:   #f7f8fa;
      --white:    #ffffff;

      --accent:   #0f172a;   /* near-black accent for buttons */
      --line:     #e5e7eb;

      --green:    #15803d; --green-bg:  #f0fdf4; --green-rim: #bbf7d0;
      --amber:    #b45309; --amber-bg:  #fffbeb; --amber-rim: #fde68a;
      --red:      #dc2626; --red-bg:    #fef2f2; --red-rim:   #fecaca;

      --radius:   10px;
      --sidebar-w: 230px;
      --topbar-h:  60px;
      --sh-sm: 0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.04);
      --sh:    0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.05);
      --sh-lg: 0 12px 40px rgba(0,0,0,.13), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body {
      height: 100%; font-family: 'DM Sans', sans-serif;
      background: var(--gray-7); color: var(--ink);
    }
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--gray-4); border-radius: 8px; }

    /* ══════════════════════════════════
       LAYOUT
    ══════════════════════════════════ */
    .shell {
      display: flex; height: 100vh; overflow: hidden;
    }

    /* ══════════════════════════════════
       SIDEBAR
    ══════════════════════════════════ */
    .sidebar {
      width: var(--sidebar-w); flex-shrink: 0;
      background: var(--ink);
      display: flex; flex-direction: column;
      overflow-y: auto; overflow-x: hidden;
      position: relative; z-index: 20;
    }

    /* Fine dot texture */
    .sidebar::before {
      content: '';
      position: absolute; inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,.04) 1px, transparent 1px);
      background-size: 18px 18px;
      pointer-events: none;
    }

    .sidebar-top {
      padding: 24px 18px 20px;
      border-bottom: 1px solid rgba(255,255,255,.07);
      position: relative; z-index: 1;
    }

    .brand-row {
      display: flex; align-items: center; gap: 10px; margin-bottom: 18px;
    }
    .brand-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: white;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .brand-icon i { font-size: 15px; color: var(--ink); }
    .brand-name { font-family: 'DM Serif Display', serif; font-size: 16px; color: white; line-height: 1; }
    .brand-sub  { font-size: 9.5px; color: var(--gray-3); letter-spacing: .12em; text-transform: uppercase; margin-top: 2px; }

    .user-card {
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.09);
      border-radius: var(--radius);
      padding: 12px 14px;
      display: flex; align-items: center; gap: 10px;
    }
    .user-avatar {
      width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
      background: white;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700; color: var(--ink); letter-spacing: .04em;
    }
    .user-name  { font-size: 12.5px; font-weight: 600; color: white; line-height: 1.2; }
    .user-shift {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 10px; color: var(--gray-3); margin-top: 3px;
    }
    .shift-dot {
      width: 5px; height: 5px; border-radius: 50%; background: #4ade80; flex-shrink: 0;
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }

    /* Nav */
    .nav-section { padding: 12px 12px 0; position: relative; z-index: 1; }
    .nav-label {
      font-size: 9px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
      color: var(--gray-1); padding: 0 8px; margin-bottom: 6px;
    }
    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 10px; border-radius: 8px;
      color: var(--gray-3); text-decoration: none;
      font-size: 13px; font-weight: 500;
      transition: background .15s, color .15s;
      margin-bottom: 2px;
    }
    .nav-item i { font-size: 15px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(255,255,255,.07); color: white; }
    .nav-item.active { background: rgba(255,255,255,.11); color: white; }
    .nav-item.logout {
      color: #f87171; margin-top: auto;
    }
    .nav-item.logout:hover { background: rgba(248,113,113,.1); color: #fca5a5; }

    .sidebar-footer { margin-top: auto; padding: 14px 12px; position: relative; z-index: 1; border-top: 1px solid rgba(255,255,255,.07); }

    /* ══════════════════════════════════
       MAIN AREA
    ══════════════════════════════════ */
    .main {
      flex: 1; display: flex; flex-direction: column; overflow: hidden;
    }

    /* Topbar */
    .topbar {
      height: var(--topbar-h); flex-shrink: 0;
      background: var(--white); border-bottom: 1px solid var(--line);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 28px; box-shadow: var(--sh-sm);
    }
    .topbar-left { display: flex; align-items: center; gap: 10px; }
    .page-title  {
      font-family: 'DM Serif Display', serif; font-size: 18px; color: var(--ink);
    }
    .topbar-right { display: flex; align-items: center; gap: 10px; }

    .date-chip {
      display: flex; align-items: center; gap: 6px;
      padding: 5px 12px; border-radius: 20px;
      background: var(--gray-7); border: 1px solid var(--gray-5);
      font-size: 11.5px; color: var(--gray-2); font-weight: 500;
    }
    .date-chip i { font-size: 12px; }



    /* Body area */
    .body-area {
      flex: 1; display: flex; overflow: hidden; gap: 0;
    }

    /* iframe panel */
    .iframe-panel {
      flex: 1; display: flex; flex-direction: column; overflow: hidden;
      background: var(--gray-7);
    }
    .iframe-panel iframe {
      flex: 1; border: none; width: 100%; height: 100%;
    }

    /* ══════════════════════════════════
       RIGHT PANEL
    ══════════════════════════════════ */
    .right-panel {
      width: 320px; flex-shrink: 0;
      background: var(--white); border-left: 1px solid var(--line);
      display: flex; flex-direction: column; overflow: hidden;
    }

    .rp-tabs {
      display: flex; border-bottom: 1px solid var(--line);
    }
    .rp-tab {
      flex: 1; padding: 13px 8px; text-align: center;
      font-size: 12px; font-weight: 600; color: var(--gray-2);
      cursor: pointer; border-bottom: 2px solid transparent;
      transition: color .15s, border-color .15s;
      display: flex; align-items: center; justify-content: center; gap: 5px;
    }
    .rp-tab:hover { color: var(--ink); }
    .rp-tab.active { color: var(--ink); border-bottom-color: var(--ink); }
    .rp-tab .badge {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 18px; height: 18px; border-radius: 9px; padding: 0 5px;
      font-size: 10px; font-weight: 800; background: var(--ink); color: white;
    }
    .rp-tab .badge.zero { background: var(--gray-5); color: var(--gray-2); }

    .rp-body {
      flex: 1; overflow-y: auto; padding: 16px;
    }

    /* Tab content panels */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* Payments tab */
    .pmt-card {
      background: var(--green-bg); border: 1px solid var(--green-rim);
      border-radius: 8px; padding: 12px; margin-bottom: 10px;
    }
    .pmt-name   { font-size: 13px; font-weight: 700; color: var(--ink); }
    .pmt-svc    { font-size: 12px; color: var(--gray-1); margin-top: 2px; }
    .pmt-time   { font-size: 11px; color: var(--gray-2); margin-top: 4px; display: flex; align-items: center; gap: 4px; }
    .btn-seen {
      margin-top: 8px; padding: 4px 12px; border-radius: 6px;
      background: var(--ink); color: white;
      font-family: 'DM Sans', sans-serif; font-size: 11px; font-weight: 700;
      border: none; cursor: pointer; transition: opacity .15s;
    }
    .btn-seen:hover { opacity: .8; }

    /* Lab orders tab */
    .order-card {
      background: var(--white); border: 1.5px solid var(--gray-5);
      border-radius: 8px; padding: 13px; margin-bottom: 10px;
      transition: border-color .15s, box-shadow .15s;
    }
    .order-card:hover { border-color: var(--gray-4); box-shadow: var(--sh-sm); }
    .order-test  { font-size: 13px; font-weight: 700; color: var(--ink); }
    .order-meta  { font-size: 11.5px; color: var(--gray-2); margin-top: 3px; display: flex; flex-direction: column; gap: 2px; }
    .order-meta span { display: flex; align-items: center; gap: 5px; }
    .order-notes {
      margin-top: 6px; padding: 6px 10px; border-radius: 6px;
      background: var(--gray-7); font-size: 11px; color: var(--gray-1);
      border-left: 3px solid var(--gray-4);
    }
    .order-actions { display: flex; gap: 7px; margin-top: 10px; }
    .btn-complete {
      flex: 1; padding: 6px 0; border-radius: 7px;
      background: var(--ink); color: white;
      font-family: 'DM Sans', sans-serif; font-size: 11.5px; font-weight: 700;
      border: none; cursor: pointer; transition: opacity .15s;
      display: flex; align-items: center; justify-content: center; gap: 5px;
    }
    .btn-complete:hover { opacity: .8; }
    .btn-gotest {
      flex: 1; padding: 6px 0; border-radius: 7px;
      background: var(--gray-6); color: var(--gray-1);
      font-family: 'DM Sans', sans-serif; font-size: 11.5px; font-weight: 700;
      border: 1.5px solid var(--gray-5); cursor: pointer; transition: all .15s;
      text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;
    }
    .btn-gotest:hover { background: var(--gray-5); color: var(--ink); }

    /* Empty state */
    .empty-state {
      display: flex; flex-direction: column; align-items: center;
      gap: 8px; padding: 40px 16px; color: var(--gray-3); text-align: center;
    }
    .empty-state i { font-size: 28px; opacity: .3; }
    .empty-state p { font-size: 12.5px; }

    /* Section header row inside rp-body */
    .rp-section-head {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 12px;
    }
    .rp-section-title { font-size: 12px; font-weight: 700; color: var(--gray-2); text-transform: uppercase; letter-spacing: .1em; }
    .count-chip {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 10px; border-radius: 20px;
      background: var(--gray-6); border: 1px solid var(--gray-5);
      font-size: 11px; font-weight: 700; color: var(--ink);
    }

    /* Spinner */
    .spinner {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      padding: 30px; color: var(--gray-3); font-size: 13px;
    }
    .spin-icon { animation: spin 1s linear infinite; font-size: 18px; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Responsive */
    @media (max-width: 900px) {
      .sidebar { display: none; }
      .right-panel { width: 280px; }
    }
  </style>
</head>
<body>

<div class="shell">

  <!-- ── SIDEBAR ───────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-top">
      <div class="brand-row">
        <div class="brand-icon"><i class="bi bi-hospital"></i></div>
        <div>
          <div class="brand-name">Angelora</div>
          <div class="brand-sub">Laboratory</div>
        </div>
      </div>
      <div class="user-card">
        <div class="user-avatar"><?= $initials ?></div>
        <div>
          <div class="user-name"><?= $userName ?></div>
          <div class="user-shift">
            <span class="shift-dot"></span>
            <?= $shift ?> shift
          </div>
        </div>
      </div>
    </div>

    <div class="nav-section" style="flex:1;">
      <div class="nav-label">Navigation</div>
      <a href="lab_welcome.php" target="mainFrame" class="nav-item active">
        <i class="bi bi-grid-1x2"></i> Dashboard Overview
      </a>
      <a href="view_lab_test.php" target="mainFrame" class="nav-item">
        <i class="bi bi-check2-square"></i> Completed Tests
      </a>
      <!--<a href="view_lab_test1.php" target="mainFrame" class="nav-item">-->
      <!--  <i class="bi bi-printer"></i> Print Completed-->
      <!--</a>-->
      <a href="lab_tests.php" target="mainFrame" class="nav-item">
        <i class="bi bi-eyedropper"></i> Conduct Test
      </a>
    </div>

    <div class="sidebar-footer">
      <a href="../logout.php" class="nav-item logout">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </aside>

  <!-- ── MAIN ──────────────────────────── -->
  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <i class="bi bi-eyedropper" style="font-size:18px;color:var(--gray-3);"></i>
        <span class="page-title">Lab Dashboard</span>
      </div>
      <div class="topbar-right">
        <div class="date-chip">
          <i class="bi bi-calendar3"></i>
          <?= date('D, d M Y') ?>
        </div>
      </div>
    </div>


    <!-- Body: iframe + right panel -->
    <div class="body-area">

      <!-- iframe -->
      <div class="iframe-panel">
        <iframe name="mainFrame" src="lab_welcome.php"></iframe>
      </div>

      <!-- Right panel -->
      <div class="right-panel">

        <!-- Tabs -->
        <div class="rp-tabs">
          <div class="rp-tab active" data-tab="orders">
            <i class="bi bi-flask"></i> Orders
            <span class="badge <?= $newOrders == 0 ? 'zero' : '' ?>" id="ordersBadge"><?= $newOrders ?></span>
          </div>
          <div class="rp-tab" data-tab="payments">
            <i class="bi bi-bell"></i> Payments
            <span class="badge <?= empty($alerts) ? 'zero' : '' ?>"><?= empty($alerts) ? 0 : count($alerts) ?></span>
          </div>
        </div>

        <!-- Panel body -->
        <div class="rp-body">

          <!-- Lab Orders panel -->
          <div class="tab-panel active" id="tab-orders">
            <div class="rp-section-head">
              <span class="rp-section-title">Pending Lab Orders</span>
              <span class="count-chip" id="ordersCount">
                <i class="bi bi-hourglass-split" style="font-size:10px;"></i>
                <span id="ordersCountNum"><?= $newOrders ?></span>
              </span>
            </div>
            <div id="labOrdersBox">
              <div class="spinner"><i class="bi bi-arrow-repeat spin-icon"></i> Loading…</div>
            </div>
          </div>

          <!-- Payments panel -->
          <div class="tab-panel" id="tab-payments">
            <div class="rp-section-head">
              <span class="rp-section-title">Payment Alerts</span>
              <span class="count-chip"><?= empty($alerts) ? 0 : count($alerts) ?></span>
            </div>
            <?php if (!empty($alerts)): ?>
              <?php foreach ($alerts as $alert): ?>
              <div class="pmt-card" id="alert-<?= $alert['billing_id'] ?>">
                <div class="pmt-name"><?= htmlspecialchars($alert['full_name']) ?></div>
                <div class="pmt-svc">
                  <i class="bi bi-check-circle-fill" style="color:var(--green);font-size:11px;"></i>
                  Paid for <strong><?= htmlspecialchars($alert['service_name']) ?></strong>
                </div>
                <div class="pmt-time">
                  <i class="bi bi-clock"></i> <?= htmlspecialchars($alert['paid_at']) ?>
                </div>
                <button class="btn-seen markSeenBtn" data-id="<?= $alert['billing_id'] ?>">
                  <i class="bi bi-check2"></i> Mark as Seen
                </button>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <i class="bi bi-bell-slash"></i>
                <p>No new payment alerts</p>
              </div>
            <?php endif; ?>
          </div>

        </div><!-- /rp-body -->
      </div><!-- /right-panel -->
    </div><!-- /body-area -->
  </div><!-- /main -->
</div><!-- /shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Tabs ──────────────────────────────────────────── */
document.querySelectorAll('.rp-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.rp-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
  });
});

/* ── Render lab orders ─────────────────────────────── */
function renderOrders(orders) {
  const box = document.getElementById('labOrdersBox');
  const countEl = document.getElementById('ordersCountNum');
  const badgeEl = document.getElementById('ordersBadge');

  if (countEl) countEl.textContent = orders.length;
  if (badgeEl) {
    badgeEl.textContent = orders.length;
    badgeEl.className = 'badge' + (orders.length === 0 ? ' zero' : '');
  }

  if (!orders.length) {
    box.innerHTML = `
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>No pending lab orders</p>
      </div>`;
    return;
  }

  box.innerHTML = orders.map(o => `
    <div class="order-card">
      <div class="order-test">${o.test_name}</div>
      <div class="order-meta">
        <span><i class="bi bi-person"></i> ${o.full_name}</span>
        <span><i class="bi bi-clock"></i> ${o.ordered_at ?? o.created_at ?? '—'}</span>
      </div>
      ${o.lab_notes ? `<div class="order-notes"><i class="bi bi-chat-left-text"></i> ${o.lab_notes.replace(/\n/g,'<br>')}</div>` : ''}
      <div class="order-actions">
        <button class="btn-complete mark-complete-btn" data-id="${o.id}">
          <i class="bi bi-check2-circle"></i> Mark Complete
        </button>
        <a href="lab_tests.php" target="mainFrame" class="btn-gotest">
          <i class="bi bi-arrow-right-circle"></i> Go to Test
        </a>
      </div>
    </div>
  `).join('');
}

/* ── Load lab orders ───────────────────────────────── */
function loadLabOrders() {
  fetch('get_lab_orders.php')
    .then(r => r.json())
    .then(data => renderOrders(data))
    .catch(() => {});
}

/* ── Mark complete (delegated) ─────────────────────── */
document.getElementById('labOrdersBox').addEventListener('click', e => {
  const btn = e.target.closest('.mark-complete-btn');
  if (!btn) return;
  const id = btn.dataset.id;
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';

  fetch('mark_order_complete.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'order_id=' + encodeURIComponent(id)
  })
  .then(() => loadLabOrders())
  .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle"></i> Mark Complete'; });
});

/* ── Mark payment as seen ──────────────────────────── */
document.addEventListener('click', e => {
  const btn = e.target.closest('.markSeenBtn');
  if (!btn) return;
  const billingId = btn.dataset.id;

  fetch('mark_alert_seen.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'billing_id=' + encodeURIComponent(billingId)
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      document.getElementById('alert-' + billingId)?.remove();
    }
  })
  .catch(() => {});
});


/* ── Init ──────────────────────────────────────────── */
loadLabOrders();
setInterval(loadLabOrders,  10000);

</script>
</body>
</html>