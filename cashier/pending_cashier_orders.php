<?php
require '../db.php';

$recordsPerPage = 5;
$currentPage    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$tables = [
    'lab_orders'      => ['field' => 'test_name',      'label' => 'Lab',      'icon' => 'bi-eyedropper',            'color' => 'blue'],
    'nursing_orders'  => ['field' => 'procedure_name', 'label' => 'Nursing',  'icon' => 'bi-clipboard2-heart-fill', 'color' => 'green'],
    'pharmacy_orders' => ['field' => 'medicine_name',  'label' => 'Pharmacy', 'icon' => 'bi-capsule',               'color' => 'violet'],
];

$sections = [];
foreach ($tables as $table => $cfg) {
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) FROM $table o
        JOIN patients p ON o.patient_id = p.patient_id
        WHERE o.is_sent_to_cashier = 1 AND o.is_paid = 0
    ");
    $stmtCount->execute();
    $total      = (int)$stmtCount->fetchColumn();
    $totalPages = $total > 0 ? (int)ceil($total / $recordsPerPage) : 1;
    $offset     = ($currentPage - 1) * $recordsPerPage;

    $stmt = $pdo->prepare("
        SELECT o.*, p.patient_id, p.full_name
        FROM $table o
        JOIN patients p ON o.patient_id = p.patient_id
        WHERE o.is_sent_to_cashier = 1 AND o.is_paid = 0
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit',  $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,         PDO::PARAM_INT);
    $stmt->execute();

    $sections[$table] = [
        'cfg'        => $cfg,
        'orders'     => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total'      => $total,
        'totalPages' => $totalPages,
        'from'       => $total > 0 ? $offset + 1 : 0,
        'to'         => min($offset + $recordsPerPage, $total),
    ];
}

$grand_total = array_sum(array_column($sections, 'total'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders to Bill — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-800: #1e40af; --blue-700: #1d4ed8; --blue-600: #2563eb;
      --blue-500: #3b82f6; --blue-400: #60a5fa; --blue-300: #93c5fd;
      --blue-200: #bfdbfe; --blue-100: #dbeafe; --blue-50:  #eff6ff;

      --white:    #ffffff;
      --gray-50:  #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
      --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
      --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;

      --green:    #16a34a; --green-bg:  #dcfce7; --green-100:  #bbf7d0;
      --amber:    #d97706; --amber-bg:  #fef3c7; --amber-100:  #fde68a;
      --violet:   #7c3aed; --violet-bg: #ede9fe; --violet-100: #c4b5fd;

      --radius:    12px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.04);
      --shadow:    0 4px 16px rgba(37,99,235,.09), 0 1px 4px rgba(0,0,0,.05);
      --shadow-lg: 0 12px 36px rgba(37,99,235,.14), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body { min-height: 100vh; font-family: 'Sora', sans-serif; background: var(--gray-50); color: var(--gray-800); }
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

    /* ── TOP BAR ─────────────────────── */
    .topbar {
      position: sticky; top: 0; z-index: 100; height: 64px;
      background: var(--white); border-bottom: 1px solid var(--gray-200);
      box-shadow: 0 1px 8px rgba(0,0,0,.06);
      display: flex; align-items: center; justify-content: space-between; padding: 0 36px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .brand-mark i { font-size: 17px; color: white; }
    .brand-text  { display: flex; flex-direction: column; gap: 1px; }
    .brand-name  { font-family: 'Instrument Serif', serif; font-size: 17px; color: var(--gray-900); line-height: 1; }
    .brand-sub   { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue-600); line-height: 1; }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .date-pill {
      display: flex; align-items: center; gap: 7px; padding: 6px 14px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100); font-size: 12px; color: var(--blue-700); font-weight: 500;
    }
    .back-btn {
      display: flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200); color: var(--gray-600);
      font-family: 'Sora', sans-serif; font-size: 12.5px; font-weight: 500; text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-600); }

    /* ── PAGE ────────────────────────── */
    .page { max-width: 1100px; margin: 0 auto; padding: 36px 24px 72px; }

    .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--gray-400); margin-bottom: 12px; }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }

    .page-title { font-family: 'Instrument Serif', serif; font-size: clamp(1.5rem, 3vw, 2.1rem); color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-top: 5px; margin-bottom: 26px; }

    /* ── SUMMARY CHIPS ───────────────── */
    .chips { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }
    .chip {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 16px; border-radius: 14px;
      background: var(--white); border: 1.5px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
    }
    .chip-icon { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .chip-icon i { font-size: 14px; }
    .chip-val  { font-size: 18px; font-weight: 800; line-height: 1; }
    .chip-lbl  { font-size: 11px; color: var(--gray-500); margin-top: 1px; }

    .chip.c-blue   { border-color: var(--blue-100); }  .chip.c-blue   .chip-icon { background: var(--blue-50);   } .chip.c-blue   .chip-icon i { color: var(--blue-600);  } .chip.c-blue   .chip-val { color: var(--blue-700);  }
    .chip.c-green  { border-color: var(--green-100); } .chip.c-green  .chip-icon { background: var(--green-bg);  } .chip.c-green  .chip-icon i { color: var(--green);    } .chip.c-green  .chip-val { color: var(--green);     }
    .chip.c-violet { border-color: var(--violet-100);} .chip.c-violet .chip-icon { background: var(--violet-bg); } .chip.c-violet .chip-icon i { color: var(--violet);   } .chip.c-violet .chip-val { color: var(--violet);    }
    .chip.c-total  { border-color: var(--gray-200);  } .chip.c-total  .chip-icon { background: var(--gray-100);  } .chip.c-total  .chip-icon i { color: var(--gray-600);  } .chip.c-total  .chip-val { color: var(--gray-800);  }

    /* ── SECTION PANEL ───────────────── */
    .panel {
      background: var(--white); border: 1.5px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden;
      box-shadow: var(--shadow-sm); margin-bottom: 22px;
    }
    .panel-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px; gap: 12px;
    }
    .panel-title { display: flex; align-items: center; gap: 10px; }
    .panel-icon  { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .panel-icon i { font-size: 16px; }
    .panel-name  { font-size: 14px; font-weight: 700; }
    .count-pill  { font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px; }

    .p-blue   .panel-head { background: var(--blue-50);   border-bottom: 1px solid var(--blue-100);   }
    .p-blue   .panel-icon { background: var(--blue-100);  } .p-blue   .panel-icon i { color: var(--blue-700); }
    .p-blue   .panel-name { color: var(--blue-800); }
    .p-blue   .count-pill { background: var(--blue-100); color: var(--blue-700); border: 1px solid var(--blue-200); }

    .p-green  .panel-head { background: var(--green-bg);  border-bottom: 1px solid var(--green-100);  }
    .p-green  .panel-icon { background: var(--green-100); } .p-green  .panel-icon i { color: var(--green);   }
    .p-green  .panel-name { color: #166534; }
    .p-green  .count-pill { background: var(--green-100); color: var(--green);   border: 1px solid #86efac; }

    .p-violet .panel-head { background: var(--violet-bg); border-bottom: 1px solid var(--violet-100); }
    .p-violet .panel-icon { background: var(--violet-100);} .p-violet .panel-icon i { color: var(--violet); }
    .p-violet .panel-name { color: #4c1d95; }
    .p-violet .count-pill { background: var(--violet-100);color: var(--violet); border: 1px solid var(--violet-100); }

    /* ── TABLE ───────────────────────── */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 520px; }
    thead th {
      padding: 9px 16px; text-align: left;
      font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      color: var(--gray-500); background: var(--gray-50); border-bottom: 1px solid var(--gray-200);
      white-space: nowrap;
    }
    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .1s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--blue-50); }
    td { padding: 11px 16px; color: var(--gray-700); vertical-align: middle; }

    .patient-cell { display: flex; align-items: center; gap: 10px; }
    .p-avatar {
      width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; color: white; letter-spacing: .04em;
    }
    .p-name { font-weight: 600; color: var(--gray-800); font-size: 13px; }
    .p-id   { font-size: 11px; color: var(--gray-400); margin-top: 1px; }

    .svc-name { font-weight: 500; color: var(--gray-800); }

    .action-cell { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .badge-pending {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
      background: var(--amber-bg); color: var(--amber); border: 1px solid var(--amber-100);
    }
    .btn-bill {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 14px; border-radius: 7px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      color: white; font-family: 'Sora', sans-serif;
      font-size: 11.5px; font-weight: 700; text-decoration: none;
      box-shadow: 0 2px 8px rgba(37,99,235,.25); transition: opacity .15s, transform .15s;
    }
    .btn-bill:hover { opacity: .9; transform: translateY(-1px); color: white; }

    /* ── EMPTY STATE ─────────────────── */
    .empty {
      display: flex; flex-direction: column; align-items: center;
      gap: 8px; padding: 38px 20px; color: var(--gray-400); text-align: center;
    }
    .empty i { font-size: 30px; opacity: .25; }
    .empty p { font-size: 13px; }

    /* ── PAGINATION ──────────────────── */
    .pag-wrap {
      display: flex; align-items: center; justify-content: space-between;
      padding: 12px 20px; border-top: 1px solid var(--gray-100);
      background: var(--gray-50); flex-wrap: wrap; gap: 10px;
    }
    .pag-info  { font-size: 12px; color: var(--gray-500); }
    .pag-links { display: flex; align-items: center; gap: 4px; }
    .pag-btn {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 32px; height: 32px; border-radius: 7px; padding: 0 8px;
      font-family: 'Sora', sans-serif; font-size: 12.5px; font-weight: 600;
      text-decoration: none; border: 1.5px solid var(--gray-200);
      background: var(--white); color: var(--gray-600); transition: all .15s;
    }
    .pag-btn:hover   { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-700); }
    .pag-btn.active  { background: var(--blue-600); border-color: var(--blue-600); color: white; pointer-events: none; }
    .pag-btn.disabled{ opacity: .4; pointer-events: none; }
    .pag-dots { font-size: 13px; color: var(--gray-400); padding: 0 4px; line-height: 32px; }

    @media (max-width: 768px) {
      .topbar { padding: 0 16px; } .date-pill { display: none; }
      .page { padding: 20px 14px 52px; }
    }
  </style>
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-mark"><i class="bi bi-hospital"></i></div>
    <div class="brand-text">
      <span class="brand-name">Angelora</span>
      <span class="brand-sub">Billing System</span>
    </div>
  </div>
  <div class="topbar-right">
    <div class="date-pill"><i class="bi bi-calendar3"></i><?= date('D, d M Y') ?></div>
    <a href="bill_dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</header>

<div class="page">

  <div class="breadcrumb">
    <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="bill_dashboard.php">Billing</a>
    <i class="bi bi-chevron-right"></i>
    <span>Orders to Bill</span>
  </div>

  <h1 class="page-title">Doctor <em>Orders to Bill</em></h1>
  <p class="page-sub">Unpaid orders sent to the cashier by doctors — ready for billing.</p>

  <!-- Summary chips -->
  <div class="chips">
    <?php
    $chip_map = [
      'lab_orders'      => ['c-blue',   'bi-eyedropper',            'Lab'],
      'nursing_orders'  => ['c-green',  'bi-clipboard2-heart-fill', 'Nursing'],
      'pharmacy_orders' => ['c-violet', 'bi-capsule',               'Pharmacy'],
    ];
    foreach ($sections as $tbl => $sec):
      [$ccls, $icon, $lbl] = $chip_map[$tbl];
    ?>
    <div class="chip <?= $ccls ?>">
      <div class="chip-icon"><i class="bi <?= $icon ?>"></i></div>
      <div>
        <div class="chip-val"><?= $sec['total'] ?></div>
        <div class="chip-lbl"><?= $lbl ?> Orders</div>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="chip c-total">
      <div class="chip-icon"><i class="bi bi-receipt-cutoff"></i></div>
      <div>
        <div class="chip-val"><?= $grand_total ?></div>
        <div class="chip-lbl">Total Pending</div>
      </div>
    </div>
  </div>

  <!-- Panels -->
  <?php
  $panel_map = [
    'lab_orders'      => 'p-blue',
    'nursing_orders'  => 'p-green',
    'pharmacy_orders' => 'p-violet',
  ];
  foreach ($sections as $tbl => $sec):
    $cfg   = $sec['cfg'];
    $pclass = $panel_map[$tbl];
  ?>
  <div class="panel <?= $pclass ?>">

    <div class="panel-head">
      <div class="panel-title">
        <div class="panel-icon"><i class="bi <?= $cfg['icon'] ?>"></i></div>
        <span class="panel-name"><?= $cfg['label'] ?> Orders</span>
      </div>
      <span class="count-pill"><?= $sec['total'] ?> pending</span>
    </div>

    <?php if ($sec['orders']): ?>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th><?= $cfg['label'] ?> Service</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sec['orders'] as $row):
            $name    = htmlspecialchars($row['full_name']);
            $pid     = htmlspecialchars($row['patient_id']);
            $svc     = htmlspecialchars($row[$cfg['field']]);
            $words   = explode(' ', trim($name));
            $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));
          ?>
          <tr>
            <td>
              <div class="patient-cell">
                <div class="p-avatar"><?= $initials ?></div>
                <div>
                  <div class="p-name"><?= $name ?></div>
                  <div class="p-id">ID: <?= $pid ?></div>
                </div>
              </div>
            </td>
            <td><span class="svc-name"><?= $svc ?></span></td>
            <td>
              <div class="action-cell">
                <span class="badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                <a href="../cashier/bill_patient2.php?patient_id=<?= $pid ?>" class="btn-bill">
                  <i class="bi bi-plus-circle-fill"></i> Bill Patient
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($sec['totalPages'] > 1): ?>
    <div class="pag-wrap">
      <div class="pag-info">
        Showing <?= $sec['from'] ?>–<?= $sec['to'] ?> of <?= $sec['total'] ?> orders
      </div>
      <div class="pag-links">
        <a href="?page=<?= max(1, $currentPage-1) ?>" class="pag-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-left"></i>
        </a>
        <?php
        $prev = null;
        for ($i = 1; $i <= $sec['totalPages']; $i++):
          $show = ($i === 1 || $i === $sec['totalPages'] || abs($i - $currentPage) <= 1);
          if (!$show) { if ($prev !== null && $prev !== '…') { echo '<span class="pag-dots">…</span>'; $prev = '…'; } continue; }
        ?>
        <a href="?page=<?= $i ?>" class="pag-btn <?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
        <?php $prev = $i; endfor; ?>
        <a href="?page=<?= min($sec['totalPages'], $currentPage+1) ?>" class="pag-btn <?= $currentPage >= $sec['totalPages'] ? 'disabled' : '' ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty">
      <i class="bi bi-inbox"></i>
      <p>No unpaid <?= strtolower($cfg['label']) ?> orders at the moment.</p>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>

</div>
</body>
</html>