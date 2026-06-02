<?php
require '../db.php';

$message     = '';
$msg_type    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name'] ?? '');
    $role_id      = trim($_POST['role_id']      ?? '');
    $cost         = floatval($_POST['cost']      ?? 0);

    if ($service_name !== '' && $role_id !== '') {
        $stmt = $pdo->prepare("SELECT * FROM service_roles WHERE service_name = ?");
        $stmt->execute([$service_name]);
        $existingService = $stmt->fetch();

        if ($existingService) {
            $pdo->prepare("UPDATE service_roles SET cost = ?, role_id = ? WHERE service_name = ?")
                ->execute([$cost, $role_id, $service_name]);
            $message  = "Service updated successfully.";
            $msg_type = "success";
        } else {
            $pdo->prepare("INSERT INTO service_roles (service_name, role_id, cost) VALUES (?, ?, ?)")
                ->execute([$service_name, $role_id, $cost]);
            $message  = "New service added successfully.";
            $msg_type = "success";
        }

        if (in_array($role_id, [2, 3, 5, 6])) {
            $checkMed = $pdo->prepare("SELECT * FROM medicines WHERE medicine_name = ?");
            $checkMed->execute([$service_name]);
            if ($checkMed->fetch()) {
                $pdo->prepare("UPDATE medicines SET price = ? WHERE medicine_name = ?")
                    ->execute([$cost, $service_name]);
            } else {
                $pdo->prepare("INSERT INTO medicines (medicine_name, description, stock, expiry_date, price) VALUES (?, '', 0, NULL, ?)")
                    ->execute([$service_name, $cost]);
            }
        }
    } else {
        $message  = "Please fill in all required fields.";
        $msg_type = "error";
    }
}

$dept_map = [
    2 => ['label' => 'Doctor',        'icon' => 'bi-person-badge-fill',   'color' => 'blue'],
    3 => ['label' => 'Nurse',         'icon' => 'bi-clipboard2-heart-fill','color' => 'green'],
    5 => ['label' => 'Pharmacy',      'icon' => 'bi-capsule',              'color' => 'violet'],
    6 => ['label' => 'Lab Technician','icon' => 'bi-eyedropper',           'color' => 'amber'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Service — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
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

      --green:     #16a34a; --green-bg: #dcfce7; --green-100:#bbf7d0;
      --amber:     #d97706; --amber-bg: #fef3c7; --amber-100:#fde68a;
      --red:       #dc2626; --red-bg:   #fee2e2; --red-100:  #fecaca;
      --violet:    #7c3aed; --violet-bg:#ede9fe; --violet-100:#c4b5fd;

      --radius: 12px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.04);
      --shadow:    0 4px 16px rgba(37,99,235,.09), 0 1px 4px rgba(0,0,0,.05);
      --shadow-lg: 0 12px 36px rgba(37,99,235,.14), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body {
      min-height: 100vh; font-family: 'Sora', sans-serif;
      background: var(--gray-50); color: var(--gray-800);
    }
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

    /* ════ TOP BAR ═══════════════════ */
    .topbar {
      position: sticky; top: 0; z-index: 100;
      height: 64px; background: var(--white);
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
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 17px; color: var(--gray-900); line-height: 1; }
    .brand-sub  { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue-600); line-height: 1; }

    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .date-pill {
      display: flex; align-items: center; gap: 7px;
      padding: 6px 14px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      font-size: 12px; color: var(--blue-700); font-weight: 500;
    }
    .date-pill i { color: var(--blue-500); }
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
    .page { max-width: 760px; margin: 0 auto; padding: 40px 24px 72px; }

    /* ════ PAGE HEADER ═══════════════ */
    .page-header { margin-bottom: 28px; }
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--gray-400); margin-bottom: 10px;
    }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }
    .header-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .page-title { font-family: 'Instrument Serif', serif; font-size: clamp(1.6rem,3vw,2.1rem); font-weight: 400; color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-top: 5px; }

    .manage-btn {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 18px; border-radius: 9px;
      background: var(--white); border: 1.5px solid var(--gray-200);
      color: var(--gray-700); font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; text-decoration: none;
      box-shadow: var(--shadow-sm); transition: all .18s; white-space: nowrap;
    }
    .manage-btn:hover { background: var(--blue-50); border-color: var(--blue-300); color: var(--blue-700); box-shadow: var(--shadow); }

    /* ════ ALERT ══════════════════════ */
    .alert {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px 18px; border-radius: 10px; margin-bottom: 22px;
      font-size: 13.5px; line-height: 1.5;
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    .alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .alert-success { background: var(--green-bg); border: 1px solid var(--green-100); color: var(--green); }
    .alert-error   { background: var(--red-bg);   border: 1px solid var(--red-100);   color: var(--red);   }

    /* ════ DEPT GUIDE ════════════════ */
    .dept-guide {
      display: grid; grid-template-columns: repeat(4,1fr); gap: 10px;
      margin-bottom: 24px;
    }
    .dept-chip {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 12px; border-radius: 10px;
      background: var(--white); border: 1.5px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
    }
    .dept-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .dept-icon i { font-size: 14px; }
    .dept-icon.blue   { background: var(--blue-50);    } .dept-icon.blue   i { color: var(--blue-600);  }
    .dept-icon.green  { background: var(--green-bg);   } .dept-icon.green  i { color: var(--green);     }
    .dept-icon.violet { background: var(--violet-bg);  } .dept-icon.violet i { color: var(--violet);    }
    .dept-icon.amber  { background: var(--amber-bg);   } .dept-icon.amber  i { color: var(--amber);     }
    .dept-num   { font-size: 18px; font-weight: 800; color: var(--gray-800); line-height: 1; }
    .dept-label { font-size: 10.5px; color: var(--gray-500); margin-top: 1px; }

    /* ════ FORM CARD ═════════════════ */
    .form-card {
      background: var(--white); border: 1.5px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden;
      box-shadow: var(--shadow);
    }
    .form-card-header {
      padding: 16px 28px; border-bottom: 1px solid var(--gray-100);
      background: linear-gradient(135deg, var(--blue-800), var(--blue-600));
      display: flex; align-items: center; gap: 10px;
    }
    .form-card-header i { font-size: 18px; color: rgba(255,255,255,.85); }
    .form-card-title { font-size: 14.5px; font-weight: 700; color: white; }
    .form-card-sub   { font-size: 12px; color: rgba(255,255,255,.65); margin-top: 1px; }

    .form-body { padding: 28px; display: flex; flex-direction: column; gap: 20px; }

    /* ════ FIELDS ════════════════════ */
    .field { display: flex; flex-direction: column; gap: 7px; }
    .field label {
      font-size: 11.5px; font-weight: 700; letter-spacing: .05em;
      text-transform: uppercase; color: var(--gray-500);
      display: flex; align-items: center; gap: 5px;
    }
    .field label .req { color: var(--red); }

    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: 15px; pointer-events: none;
    }

    input[type="text"],
    input[type="number"],
    select {
      width: 100%; padding: 12px 14px 12px 40px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; color: var(--gray-800);
      font-family: 'Sora', sans-serif; font-size: 13.5px;
      outline: none; appearance: none;
      transition: border-color .18s, box-shadow .18s, background .18s;
    }
    input:focus, select:focus {
      border-color: var(--blue-400); background: var(--white);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }
    input::placeholder { color: var(--gray-400); }

    /* Dept select with visual options */
    .dept-select-wrap { position: relative; }
    .dept-select-wrap select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 13px center;
      padding-right: 36px; cursor: pointer;
    }

    /* Cost input prefix */
    .cost-wrap { position: relative; }
    .cost-prefix {
      position: absolute; left: 0; top: 0; bottom: 0; width: 44px;
      background: var(--blue-50); border: 1.5px solid var(--gray-200);
      border-right: none; border-radius: 9px 0 0 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700; color: var(--blue-600);
      pointer-events: none;
    }
    .cost-wrap input { padding-left: 52px; border-radius: 0 9px 9px 0; }
    .cost-wrap input:focus ~ .cost-prefix,
    .cost-wrap input:focus { border-color: var(--blue-400); }

    /* Field hint */
    .field-hint {
      font-size: 11.5px; color: var(--gray-400);
      display: flex; align-items: center; gap: 5px;
    }
    .field-hint i { font-size: 12px; color: var(--amber); }

    /* ════ FORM FOOTER ═══════════════ */
    .form-footer {
      padding: 18px 28px; border-top: 1px solid var(--gray-100);
      background: var(--gray-50);
      display: flex; align-items: center; justify-content: space-between;
      gap: 16px; flex-wrap: wrap;
    }
    .form-footer-note {
      font-size: 12px; color: var(--gray-400);
      display: flex; align-items: center; gap: 6px;
    }
    .form-footer-note i { color: var(--blue-400); }

    .btn-submit {
      display: flex; align-items: center; gap: 8px;
      padding: 12px 28px; border-radius: 9px; border: none;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      color: white; font-family: 'Sora', sans-serif;
      font-size: 13.5px; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(37,99,235,.3);
      transition: opacity .18s, transform .15s, box-shadow .18s;
      position: relative; overflow: hidden;
    }
    .btn-submit::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.1) 0%, transparent 60%);
    }
    .btn-submit:hover { opacity: .95; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(37,99,235,.4); }
    .btn-submit:active { transform: translateY(0); }

    /* ════ RESPONSIVE ══════════════════ */
    @media (max-width: 640px) {
      .topbar { padding: 0 16px; }
      .date-pill { display: none; }
      .page { padding: 20px 14px 52px; }
      .dept-guide { grid-template-columns: 1fr 1fr; }
      .form-body { padding: 20px; }
      .form-footer { padding: 16px 20px; flex-direction: column; align-items: stretch; }
      .btn-submit { justify-content: center; }
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
      <?= date('D, d M Y') ?>
    </div>
    <a href="dashboard.php" class="back-btn">
      <i class="bi bi-arrow-left"></i> Dashboard
    </a>
  </div>
</header>

<!-- ════ PAGE ════════════════════════════ -->
<div class="page">

  <!-- Header -->
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
      <i class="bi bi-chevron-right"></i>
      <a href="bill_dashboard.php">Billing</a>
      <i class="bi bi-chevron-right"></i>
      <span>Add Service</span>
    </div>
    <div class="header-row">
      <div>
        <h1 class="page-title">Add or <em>Update Service</em></h1>
        <p class="page-sub">Create a new billable service or update an existing one by name.</p>
      </div>
      <a href="manage_service.php" class="manage-btn">
        <i class="bi bi-grid-3x3-gap-fill"></i> Manage Services
      </a>
    </div>
  </div>

  <!-- Alert -->
  <?php if ($message): ?>
    <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-error' ?>">
      <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Department Guide -->
  <div class="dept-guide">
    <?php foreach ($dept_map as $id => $dept): ?>
    <div class="dept-chip">
      <div class="dept-icon <?= $dept['color'] ?>"><i class="bi <?= $dept['icon'] ?>"></i></div>
      <div>
        <div class="dept-num"><?= $id ?></div>
        <div class="dept-label"><?= $dept['label'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <div class="form-card-header">
      <i class="bi bi-plus-circle-fill"></i>
      <div>
        <div class="form-card-title">Service Details</div>
        <div class="form-card-sub">If the service name already exists, its cost and department will be updated.</div>
      </div>
    </div>

    <form method="post" action="">
      <div class="form-body">

        <!-- Service Name -->
        <div class="field">
          <label>Service Name <span class="req">*</span></label>
          <div class="input-wrap">
            <i class="bi bi-tag-fill input-icon"></i>
            <input type="text" name="service_name" placeholder="e.g. Full Blood Count, IV Cannulation…" required>
          </div>
          <span class="field-hint">
            <i class="bi bi-info-circle"></i>
            Existing services will be updated; new ones will be inserted.
          </span>
        </div>

        <!-- Department -->
        <div class="field">
          <label>Department (Role ID) <span class="req">*</span></label>
          <div class="dept-select-wrap">
            <select name="role_id" required style="padding-left:14px;">
              <option value="">— Select department —</option>
              <option value="2">2 — Doctor</option>
              <option value="3">3 — Nurse</option>
              <option value="5">5 — Pharmacy</option>
              <option value="6">6 — Lab Technician</option>
            </select>
          </div>
          <span class="field-hint">
            <i class="bi bi-info-circle"></i>
            Roles 2, 3, 5, and 6 will also sync to the medicines table.
          </span>
        </div>

        <!-- Cost -->
        <div class="field">
          <label>Cost <span class="req">*</span></label>
          <div class="cost-wrap">
            <span class="cost-prefix">₦</span>
            <input type="number" name="cost" step="0.01" min="0" placeholder="0.00" required>
          </div>
        </div>

      </div>

      <div class="form-footer">
        <span class="form-footer-note">
          <i class="bi bi-shield-check"></i>
          All changes are saved to the database immediately.
        </span>
        <button type="submit" class="btn-submit">
          <i class="bi bi-floppy-fill"></i> Save Service
        </button>
      </div>
    </form>
  </div>

</div>
</body>
</html>