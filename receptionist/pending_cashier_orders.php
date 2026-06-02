<?php
require '../db.php';

session_start();
if (!isset($_SESSION['user'])) { header('Location: ../login.php'); exit(); }

$staff_name    = $_SESSION['user']['full_name'] ?? 'Staff';
$recordsPerPage = 10;
$currentPage    = max(1, (int)($_GET['page'] ?? 1));

$tables = [
    'lab_orders'      => ['field' => 'test_name',      'label' => 'Lab',      'icon' => 'bi-eyedropper',      'color' => 'blue'],
    'nursing_orders'  => ['field' => 'procedure_name', 'label' => 'Nursing',  'icon' => 'bi-clipboard2-heart', 'color' => 'green'],
    'pharmacy_orders' => ['field' => 'medicine_name',  'label' => 'Pharmacy', 'icon' => 'bi-capsule',          'color' => 'violet'],
];

$sections   = [];
$grandTotal = 0;
foreach ($tables as $table => $meta) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $table o JOIN patients p ON o.patient_id = p.patient_id WHERE o.is_sent_to_cashier = 1 AND o.is_paid = 0");
    $countStmt->execute();
    $total      = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($total / $recordsPerPage);
    $offset     = ($currentPage - 1) * $recordsPerPage;

    $stmt = $pdo->prepare("SELECT o.*, p.patient_id, p.full_name FROM $table o JOIN patients p ON o.patient_id = p.patient_id WHERE o.is_sent_to_cashier = 1 AND o.is_paid = 0 LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit',  $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,         PDO::PARAM_INT);
    $stmt->execute();

    $sections[$table] = array_merge($meta, [
        'orders'     => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'      => $total,
        'totalPages' => $totalPages,
    ]);
    $grandTotal += $total;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Orders for Billing — Angelora Hospital</title>

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
      --blue-200:   #bfdbfe;
      --blue-100:   #dbeafe;
      --blue-50:    #eff6ff;
      --white:      #ffffff;
      --gray-50:    #f8fafc;
      --gray-100:   #f1f5f9;
      --gray-200:   #e2e8f0;
      --gray-300:   #cbd5e1;
      --gray-400:   #94a3b8;
      --gray-500:   #64748b;
      --gray-600:   #475569;
      --gray-700:   #334155;
      --gray-900:   #0f172a;
      --green-600:  #059669;
      --green-500:  #10b981;
      --green-100:  #d1fae5;
      --green-50:   #ecfdf5;
      --green-700:  #047857;
      --amber-600:  #d97706;
      --amber-500:  #f59e0b;
      --amber-100:  #fef3c7;
      --amber-50:   #fffbeb;
      --amber-700:  #b45309;
      --violet-600: #7c3aed;
      --violet-500: #8b5cf6;
      --violet-100: #ede9fe;
      --violet-50:  #f5f3ff;
      --violet-700: #6d28d9;
      --shadow-sm:  0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md:  0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg:  0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
      --blue-glow:  rgba(37,99,235,.12);
      --radius:     12px;
    }

    html, body {
      min-height: 100vh;
      font-family: 'Sora', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 10px; }

    /* ── Top Bar ── */
    .topbar {
      position: sticky; top: 0; z-index: 100;
      background: var(--white);
      border-bottom: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
      height: 66px;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 36px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 12px rgba(37,99,235,.28);
    }
    .brand-icon i { font-size: 18px; color: white; }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 18px; color: var(--blue-800); }
    .brand-sep  { color: var(--gray-300); margin: 0 2px; }
    .brand-page { font-size: 13px; color: var(--blue-600); font-weight: 600; }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .topbar-date {
      font-size: 12px; color: var(--gray-400);
      padding: 5px 12px; background: var(--gray-100);
      border-radius: 999px; border: 1px solid var(--gray-200);
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 8px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: 12.5px; font-weight: 600; text-decoration: none;
      transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-100); border-color: var(--blue-200); }

    /* ── Page ── */
    .page { max-width: 1200px; margin: 0 auto; padding: 36px 28px 60px; }

    /* ── Header ── */
    .page-header { margin-bottom: 28px; }
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--gray-400); margin-bottom: 10px;
    }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; font-weight: 500; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; color: var(--gray-300); }
    .header-row {
      display: flex; align-items: flex-end;
      justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .page-title { font-family: 'Instrument Serif', serif; font-size: 2rem; font-weight: 400; color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-400); margin-top: 5px; }

    /* ── Summary chips ── */
    .summary-chips { display: flex; gap: 10px; flex-wrap: wrap; }
    .chip {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 18px;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 12px;
      box-shadow: var(--shadow-sm);
    }
    .chip-icon {
      width: 36px; height: 36px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .chip-icon i { font-size: 16px; }
    .chip-icon.blue   { background: var(--blue-50);   } .chip-icon.blue   i { color: var(--blue-600);   }
    .chip-icon.green  { background: var(--green-50);  } .chip-icon.green  i { color: var(--green-600);  }
    .chip-icon.violet { background: var(--violet-50); } .chip-icon.violet i { color: var(--violet-600); }
    .chip-icon.amber  { background: var(--amber-50);  } .chip-icon.amber  i { color: var(--amber-600);  }
    .chip-num   { font-family: 'Instrument Serif', serif; font-size: 24px; color: var(--gray-900); line-height: 1; }
    .chip-label { font-size: 11px; color: var(--gray-400); font-weight: 500; text-transform: uppercase; letter-spacing: .06em; }

    /* ── Section Panel ── */
    .section-panel {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      overflow: hidden;
      margin-bottom: 22px;
      box-shadow: var(--shadow-sm);
    }

    .panel-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 22px; border-bottom: 1px solid var(--gray-100);
    }
    .panel-blue   .panel-header { background: #fafcff;  border-bottom-color: var(--blue-100); }
    .panel-green  .panel-header { background: #fafffe;  border-bottom-color: var(--green-100); }
    .panel-violet .panel-header { background: #fdfaff;  border-bottom-color: var(--violet-100); }

    .panel-title-wrap { display: flex; align-items: center; gap: 10px; }
    .panel-icon {
      width: 34px; height: 34px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
    }
    .panel-icon i { font-size: 16px; }
    .panel-blue   .panel-icon { background: var(--blue-50);   } .panel-blue   .panel-icon i { color: var(--blue-600);   }
    .panel-green  .panel-icon { background: var(--green-50);  } .panel-green  .panel-icon i { color: var(--green-600);  }
    .panel-violet .panel-icon { background: var(--violet-50); } .panel-violet .panel-icon i { color: var(--violet-600); }

    .panel-name { font-size: 14px; font-weight: 700; color: var(--gray-900); }

    .record-badge {
      font-size: 11px; color: var(--gray-500); font-weight: 600;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 999px; padding: 4px 14px;
    }

    /* ── Table ── */
    .table-scroll { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 520px; }

    thead th {
      padding: 10px 18px; text-align: left;
      font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-400); background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200); white-space: nowrap;
    }

    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .12s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--blue-50); }

    td { padding: 13px 18px; color: var(--gray-700); vertical-align: middle; }
    td.muted { color: var(--gray-400); font-size: 12px; }

    /* Patient cell */
    .patient-cell { display: flex; align-items: center; gap: 11px; }
    .patient-init {
      width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: white;
      box-shadow: 0 2px 8px rgba(37,99,235,.22);
    }
    .patient-name { font-weight: 600; color: var(--gray-900); font-size: 13.5px; }
    .patient-id   { font-size: 11px; color: var(--gray-400); font-family: 'Courier New', monospace; font-weight: 600; }

    /* Service tag */
    .service-tag {
      display: inline-flex; align-items: center;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 7px; padding: 5px 11px;
      font-size: 12.5px; color: var(--gray-700); font-weight: 500;
    }

    /* Action cell */
    .action-cell { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    .pill-pending {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--amber-50); border: 1px solid var(--amber-100);
      color: var(--amber-700); border-radius: 999px;
      font-size: 11px; font-weight: 700; padding: 4px 12px; white-space: nowrap;
    }
    .pill-pending::before {
      content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--amber-500);
    }

    .btn-bill {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 6px 14px; border-radius: 8px;
      background: var(--blue-600);
      border: none;
      color: white; font-family: 'Sora', sans-serif;
      font-size: 12px; font-weight: 700; text-decoration: none;
      transition: all .18s;
      box-shadow: 0 2px 8px rgba(37,99,235,.25);
    }
    .btn-bill:hover {
      background: var(--blue-700);
      color: white;
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(37,99,235,.35);
    }

    /* ── Empty State ── */
    .empty-state {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; padding: 48px 20px; gap: 10px;
      color: var(--gray-400);
    }
    .empty-state i { font-size: 38px; color: var(--gray-300); }
    .empty-state p { font-size: 13px; }

    /* ── Pagination ── */
    .pagination-wrap {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 22px; border-top: 1px solid var(--gray-100);
      flex-wrap: wrap; gap: 10px;
      background: var(--gray-50);
    }
    .pagination-info { font-size: 12px; color: var(--gray-400); font-weight: 500; }
    .pagination { display: flex; gap: 4px; list-style: none; }
    .page-item a, .page-item span {
      display: flex; align-items: center; justify-content: center;
      min-width: 32px; height: 32px; padding: 0 10px;
      border-radius: 7px; font-size: 12.5px; font-weight: 500;
      text-decoration: none; color: var(--gray-500);
      background: var(--white); border: 1px solid var(--gray-200);
      transition: all .15s;
    }
    .page-item a:hover { background: var(--blue-50); color: var(--blue-600); border-color: var(--blue-200); }
    .page-item.active a {
      background: var(--blue-600); border-color: var(--blue-600);
      color: white; font-weight: 700;
      box-shadow: 0 2px 8px rgba(37,99,235,.3);
    }
    .page-item.disabled span { opacity: .35; cursor: not-allowed; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .topbar { padding: 0 16px; }
      .topbar-date { display: none; }
      .page { padding: 20px 14px 48px; }
      .summary-chips { display: none; }
    }
  </style>
</head>
<body>

<!-- ══ TOP BAR ══ -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-icon"><i class="bi bi-hospital-fill"></i></div>
    <span class="brand-name">Angelora</span>
    <span class="brand-sep">·</span>
    <span class="brand-page">Doctor Orders</span>
  </div>
  <div class="topbar-right">
    <span class="topbar-date"><i class="bi bi-calendar3" style="margin-right:5px"></i><?= date('l, d F Y') ?></span>
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>
</header>

<!-- ══ PAGE ══ -->
<div class="page">

  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php"><i class="bi bi-house-fill"></i> Dashboard</a>
      <i class="bi bi-chevron-right"></i>
      <span>Doctor Orders for Billing</span>
    </div>
    <div class="header-row">
      <div>
        <h1 class="page-title">Doctor Orders <em>for Billing</em></h1>
        <p class="page-sub">Unpaid orders sent by doctors — ready to be billed at the cashier desk.</p>
      </div>
      <div class="summary-chips">
        <?php foreach ($sections as $table => $s): ?>
        <div class="chip">
          <div class="chip-icon <?= $s['color'] ?>"><i class="bi <?= $s['icon'] ?>"></i></div>
          <div>
            <div class="chip-num"><?= $s['total'] ?></div>
            <div class="chip-label"><?= $s['label'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="chip">
          <div class="chip-icon amber"><i class="bi bi-receipt"></i></div>
          <div>
            <div class="chip-num"><?= $grandTotal ?></div>
            <div class="chip-label">Total Pending</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Sections -->
  <?php foreach ($sections as $table => $s):
    $orders     = $s['orders'];
    $total      = $s['total'];
    $totalPages = $s['totalPages'];
    $field      = $s['field'];
    $offset     = ($currentPage - 1) * $recordsPerPage;
  ?>
  <div class="section-panel panel-<?= $s['color'] ?>">

    <div class="panel-header">
      <div class="panel-title-wrap">
        <div class="panel-icon"><i class="bi <?= $s['icon'] ?>"></i></div>
        <span class="panel-name"><?= $s['label'] ?> Orders</span>
      </div>
      <span class="record-badge"><?= $total ?> pending</span>
    </div>

    <?php if ($orders): ?>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Patient</th>
              <th>Service / Item</th>
              <th>Status &amp; Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $i => $order):
              $name     = $order['full_name'];
              $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $name)))));
              $initials = substr($initials, 0, 2);
            ?>
            <tr>
              <td class="muted"><?= $offset + $i + 1 ?></td>
              <td>
                <div class="patient-cell">
                  <div class="patient-init"><?= $initials ?></div>
                  <div>
                    <div class="patient-name"><?= htmlspecialchars($name) ?></div>
                    <div class="patient-id">ID: <?= htmlspecialchars($order['patient_id']) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="service-tag"><?= htmlspecialchars($order[$field]) ?></span></td>
              <td>
                <div class="action-cell">
                  <span class="pill-pending">Pending Billing</span>
                  <a href="../cashier/bill_patient2.php?patient_id=<?= htmlspecialchars($order['patient_id']) ?>"
                     class="btn-bill">
                    <i class="bi bi-plus-circle-fill"></i> Bill
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1):
        $start = max(1, $currentPage - 2);
        $end   = min($totalPages, $currentPage + 2);
      ?>
      <div class="pagination-wrap">
        <span class="pagination-info">
          Showing <?= $offset + 1 ?>–<?= min($offset + $recordsPerPage, $total) ?> of <?= $total ?> records
        </span>
        <ul class="pagination">
          <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <?php if ($currentPage > 1): ?>
              <a href="?page=<?= $currentPage - 1 ?>"><i class="bi bi-chevron-left"></i></a>
            <?php else: ?><span><i class="bi bi-chevron-left"></i></span><?php endif; ?>
          </li>
          <?php if ($start > 1): ?>
            <li class="page-item"><a href="?page=1">1</a></li>
            <?php if ($start > 2): ?><li class="page-item disabled"><span>…</span></li><?php endif; ?>
          <?php endif; ?>
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
              <a href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span>…</span></li><?php endif; ?>
            <li class="page-item"><a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
          <?php endif; ?>
          <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <?php if ($currentPage < $totalPages): ?>
              <a href="?page=<?= $currentPage + 1 ?>"><i class="bi bi-chevron-right"></i></a>
            <?php else: ?><span><i class="bi bi-chevron-right"></i></span><?php endif; ?>
          </li>
        </ul>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>No unpaid <?= strtolower($s['label']) ?> orders at this time.</p>
      </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>

</div>
</body>
</html>