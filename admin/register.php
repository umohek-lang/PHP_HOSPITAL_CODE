<?php
require '../db.php';

$feedback = '';
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name        = $_POST['full_name'];
    $email            = $_POST['email'];
    $phone            = $_POST['phone'];
    $role_id          = $_POST['role_id'];
    $plain_password   = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($plain_password !== $confirm_password) {
        $feedback = 'Passwords do not match.';
    } else {
        $password = password_hash($plain_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);
        $existing = $stmt->fetch();

        if ($existing) {
            $msgs = [];
            if ($existing['email'] === $email) $msgs[] = 'Email is already registered.';
            if ($existing['phone'] === $phone)  $msgs[] = 'Phone number is already registered.';
            $feedback = implode(' ', $msgs);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$role_id, $full_name, $email, $phone, $password])) {
                $redirect = true;
                $generatedEmail = $email;
            } else {
                $feedback = 'Registration failed. Please try again.';
            }
        }
    }
}

$roles = [
    1 => 'Admin', 2 => 'Doctor', 3 => 'Nurse', 4 => 'Cashier',
    5 => 'Pharmacist', 6 => 'Lab Technician', 7 => 'Patient',
    8 => 'Receptionist', 9 => 'MD'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register User — Angelora Hospital</title>
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
      --gray-700: #334155;
      --gray-900: #0f172a;
      --green-50:  #ecfdf5;
      --green-100: #d1fae5;
      --green-600: #059669;
      --green-700: #047857;
      --red-50:   #fef2f2;
      --red-100:  #fee2e2;
      --red-600:  #dc2626;
      --red-700:  #b91c1c;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 20px rgba(15,45,107,.11), 0 2px 8px rgba(15,45,107,.07);
      --shadow-lg: 0 12px 48px rgba(15,45,107,.15), 0 4px 14px rgba(15,45,107,.08);
      --blue-glow: rgba(37,99,235,.12);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }

    /* ── BACKGROUND PATTERN ── */
    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 700px 500px at 10% 20%, rgba(37,99,235,.07) 0%, transparent 70%),
        radial-gradient(ellipse 600px 400px at 90% 80%, rgba(96,165,250,.06) 0%, transparent 70%);
    }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 4px; }

    /* ── PAGE LAYOUT ── */
    .page {
      min-height: 100vh;
      display: flex;
      position: relative; z-index: 1;
    }

    /* ── LEFT PANEL ── */
    .left-panel {
      width: 380px; flex-shrink: 0;
      background: linear-gradient(160deg, var(--blue-900) 0%, var(--blue-800) 50%, var(--blue-700) 100%);
      display: flex; flex-direction: column; justify-content: space-between;
      padding: 48px 40px;
      position: relative; overflow: hidden;
    }
    .left-panel::before {
      content: ''; position: absolute; top: -80px; right: -80px;
      width: 280px; height: 280px; border-radius: 50%;
      background: rgba(255,255,255,.05); pointer-events: none;
    }
    .left-panel::after {
      content: ''; position: absolute; bottom: -60px; left: -40px;
      width: 240px; height: 240px; border-radius: 50%;
      background: rgba(255,255,255,.04); pointer-events: none;
    }
    .lp-top { position: relative; z-index: 1; }
    .lp-logo {
      display: flex; align-items: center; gap: 12px; margin-bottom: 48px;
    }
    .lp-logo-icon {
      width: 44px; height: 44px; border-radius: 12px;
      background: rgba(255,255,255,.14);
      border: 1.5px solid rgba(255,255,255,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.25rem; color: #fff;
    }
    .lp-logo-text { color: #fff; font-size: 1rem; font-weight: 700; }
    .lp-logo-sub  { color: var(--blue-300); font-size: .72rem; font-weight: 500; margin-top: 2px; }

    .lp-headline {
      font-size: 1.75rem; font-weight: 800; color: #fff;
      line-height: 1.25; margin-bottom: 14px; letter-spacing: -.02em;
    }
    .lp-headline em { font-style: italic; color: var(--blue-300); }
    .lp-desc { font-size: .85rem; color: rgba(255,255,255,.6); line-height: 1.65; }

    /* role chips */
    .lp-roles {
      display: flex; flex-wrap: wrap; gap: 7px; margin-top: 28px;
    }
    .role-chip {
      display: flex; align-items: center; gap: 5px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.14);
      border-radius: 999px; padding: 5px 12px;
      font-size: .72rem; font-weight: 600; color: rgba(255,255,255,.8);
    }
    .role-chip i { font-size: .75rem; color: var(--blue-300); }

    .lp-bottom {
      position: relative; z-index: 1;
      font-size: .72rem; color: rgba(255,255,255,.35);
    }

    /* ── RIGHT PANEL (form) ── */
    .right-panel {
      flex: 1;
      display: flex; align-items: center; justify-content: center;
      padding: 40px 32px;
    }

    .form-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      width: 100%; max-width: 480px;
      overflow: hidden;
      animation: slideUp .45s cubic-bezier(.16,1,.3,1) both;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* card header */
    .card-header {
      padding: 26px 32px 20px;
      border-bottom: 1px solid var(--gray-100);
      background: var(--gray-50);
    }
    .card-header-top { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
    .card-header-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; color: var(--blue-600);
    }
    .card-title { font-size: 1.05rem; font-weight: 800; color: var(--gray-900); }
    .card-sub   { font-size: .76rem; color: var(--gray-400); margin-top: 3px; }

    /* card body */
    .card-body { padding: 26px 32px 28px; }

    /* field */
    .field { margin-bottom: 16px; }
    .field label {
      display: block; font-size: .71rem; font-weight: 700;
      letter-spacing: .07em; text-transform: uppercase;
      color: var(--gray-500); margin-bottom: 7px;
    }
    .field label .req { color: var(--blue-500); }

    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: 15px; pointer-events: none; z-index: 1;
    }

    input, select {
      width: 100%; height: 44px;
      padding: 0 14px 0 40px;
      background: var(--gray-50);
      border: 1.5px solid var(--gray-200);
      border-radius: 10px;
      color: var(--gray-700);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .875rem;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
      appearance: none;
    }
    input:hover, select:hover { border-color: var(--blue-300); }
    input:focus, select:focus {
      border-color: var(--blue-500);
      background: var(--white);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }
    input::placeholder { color: var(--gray-300); }

    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 13px center;
      padding-right: 36px; background-color: var(--gray-50);
      cursor: pointer;
    }

    /* 2 col grid */
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    /* divider */
    .field-divider {
      display: flex; align-items: center; gap: 12px;
      margin: 18px 0 16px;
    }
    .field-divider span {
      font-size: .68rem; font-weight: 700; letter-spacing: .1em;
      text-transform: uppercase; color: var(--gray-400); white-space: nowrap;
    }
    .field-divider::before, .field-divider::after {
      content: ''; flex: 1; height: 1px; background: var(--gray-100);
    }

    /* strength indicator */
    .strength-bar {
      display: flex; gap: 4px; margin-top: 8px;
    }
    .strength-seg {
      flex: 1; height: 3px; border-radius: 2px; background: var(--gray-200);
      transition: background .3s;
    }
    .strength-label {
      font-size: .68rem; color: var(--gray-400); margin-top: 5px; min-height: 14px;
    }

    /* submit */
    .btn-submit {
      width: 100%; height: 48px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; border-radius: 12px;
      color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .9rem; font-weight: 700;
      cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
      position: relative; overflow: hidden;
      transition: all .2s;
      box-shadow: 0 6px 20px rgba(37,99,235,.32);
      margin-top: 22px;
    }
    .btn-submit::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.14) 0%, transparent 60%);
    }
    .btn-submit:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(37,99,235,.42);
    }
    .btn-submit:active:not(:disabled) { transform: translateY(0); }
    .btn-submit:disabled { opacity: .55; cursor: not-allowed; }
    .btn-spinner {
      width: 17px; height: 17px;
      border: 2px solid rgba(255,255,255,.35);
      border-top-color: #fff; border-radius: 50%;
      animation: spin .7s linear infinite; display: none;
    }
    .btn-submit.loading .btn-spinner { display: block; }
    .btn-submit.loading .btn-label   { opacity: .7; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── MODAL ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 500;
      background: rgba(15,45,107,.35);
      backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; animation: fadeIn .2s ease; }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }

    .modal-box {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 20px;
      width: 100%; max-width: 400px; margin: 20px;
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      animation: slideUp .3s cubic-bezier(.16,1,.3,1);
    }
    .modal-head {
      padding: 20px 24px 18px;
      display: flex; align-items: center; gap: 12px;
      border-bottom: 1px solid var(--gray-100);
    }
    .modal-head-icon {
      width: 40px; height: 40px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
    }
    .modal-head-icon.error   { background: var(--red-50);   color: var(--red-600); }
    .modal-head-icon.success { background: var(--green-50); color: var(--green-600); }
    .modal-head-title { font-size: 1rem; font-weight: 700; color: var(--gray-900); }
    .modal-head-sub   { font-size: .76rem; color: var(--gray-400); margin-top: 2px; }
    .modal-body-inner { padding: 18px 24px; font-size: .875rem; color: var(--gray-600); line-height: 1.6; }

    /* success detail box */
    .success-detail {
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 10px; padding: 14px 16px; margin-top: 12px;
    }
    .success-detail .sd-row {
      display: flex; align-items: center; gap: 8px;
      font-size: .82rem; color: var(--blue-800); font-weight: 600;
    }
    .success-detail .sd-row i { color: var(--blue-500); }
    .success-notice {
      background: var(--green-50); border: 1px solid var(--green-100);
      border-radius: 8px; padding: 10px 14px; margin-top: 10px;
      font-size: .78rem; color: var(--green-700); font-weight: 500;
    }

    .modal-footer-btns {
      display: flex; gap: 10px; padding: 0 24px 22px;
    }
    .btn-modal-sec {
      flex: 1; padding: 10px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 9px; color: var(--gray-500);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .82rem; font-weight: 600; cursor: pointer; text-align: center;
      transition: all .18s; text-decoration: none; display: flex; align-items: center; justify-content: center;
    }
    .btn-modal-sec:hover { background: var(--gray-200); color: var(--gray-700); }
    .btn-modal-pri {
      flex: 1; padding: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; border-radius: 9px; color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .82rem; font-weight: 700; cursor: pointer; text-align: center;
      transition: all .18s; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
      box-shadow: 0 4px 12px rgba(37,99,235,.25);
    }
    .btn-modal-pri:hover { opacity: .92; box-shadow: 0 6px 18px rgba(37,99,235,.35); color: #fff; }

    /* ── RESPONSIVE ── */
    @media (max-width: 820px) {
      .left-panel { display: none; }
      .right-panel { padding: 24px 16px; }
    }
    @media (max-width: 480px) {
      .card-header, .card-body { padding: 20px; }
      .field-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- ── LEFT PANEL ── -->
  <div class="left-panel">
    <div class="lp-top">
      <div class="lp-logo">
        <div class="lp-logo-icon"><i class="bi bi-hospital-fill"></i></div>
        <div>
          <div class="lp-logo-text">Angelora Hospital</div>
          <div class="lp-logo-sub">Management System</div>
        </div>
      </div>
      <div class="lp-headline">Register a <em>New User</em> to the System</div>
      <div class="lp-desc">Create accounts for doctors, nurses, receptionists, lab technicians, pharmacists and more. Each role gets customised access to the right modules.</div>
      <div class="lp-roles">
        <div class="role-chip"><i class="bi bi-person-badge-fill"></i> Doctor</div>
        <div class="role-chip"><i class="bi bi-clipboard2-heart-fill"></i> Nurse</div>
        <div class="role-chip"><i class="bi bi-cash-stack"></i> Cashier</div>
        <div class="role-chip"><i class="bi bi-capsule-pill"></i> Pharmacist</div>
        <div class="role-chip"><i class="bi bi-eyedropper-fill"></i> Lab Tech</div>
        <div class="role-chip"><i class="bi bi-telephone-fill"></i> Receptionist</div>
        <div class="role-chip"><i class="bi bi-shield-fill-check"></i> Admin</div>
        <div class="role-chip"><i class="bi bi-person-heart"></i> Patient</div>
      </div>
    </div>
    <div class="lp-bottom">&copy; <?= date('Y') ?> Angelora Hospital &mdash; Admin Panel</div>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="right-panel">
    <div class="form-card">

      <div class="card-header">
        <div class="card-header-top">
          <div class="card-header-icon"><i class="bi bi-person-plus-fill"></i></div>
          <div>
            <div class="card-title">New User Registration</div>
          </div>
        </div>
        <div class="card-sub">Fill in all required fields to create a new system account.</div>
      </div>

      <div class="card-body">
        <form id="registerForm" method="POST">

          <!-- Personal Info -->
          <div class="field">
            <label>Full Name <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-person input-icon"></i>
              <input name="full_name" type="text" placeholder="e.g. Dr. Amara Okafor" required>
            </div>
          </div>

          <div class="field-row">
            <div class="field">
              <label>Email <span class="req">*</span></label>
              <div class="input-wrap">
                <i class="bi bi-envelope input-icon"></i>
                <input name="email" type="email" placeholder="user@angelora.com" required>
              </div>
            </div>
            <div class="field">
              <label>Phone <span class="req">*</span></label>
              <div class="input-wrap">
                <i class="bi bi-telephone input-icon"></i>
                <input name="phone" type="tel" placeholder="08012345678" required>
              </div>
            </div>
          </div>

          <div class="field">
            <label>Role / Department <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-briefcase input-icon"></i>
              <select name="role_id" required>
                <option value="">Select a role…</option>
                <?php foreach ($roles as $id => $name): ?>
                  <option value="<?= $id ?>"><?= $name ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Password divider -->
          <div class="field-divider">
            <span><i class="bi bi-lock-fill" style="margin-right:5px;color:var(--blue-400)"></i>Set Password</span>
          </div>

          <div class="field">
            <label>Password <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-key input-icon"></i>
              <input name="password" id="pwdInput" type="password" placeholder="Minimum 8 characters" required oninput="checkStrength(this.value)">
            </div>
            <div class="strength-bar">
              <div class="strength-seg" id="s1"></div>
              <div class="strength-seg" id="s2"></div>
              <div class="strength-seg" id="s3"></div>
              <div class="strength-seg" id="s4"></div>
            </div>
            <div class="strength-label" id="strengthLabel"></div>
          </div>

          <div class="field">
            <label>Confirm Password <span class="req">*</span></label>
            <div class="input-wrap">
              <i class="bi bi-key-fill input-icon"></i>
              <input name="confirm_password" id="cpwdInput" type="password" placeholder="Re-enter password" required>
            </div>
          </div>

          <button type="submit" class="btn-submit" id="submitBtn">
            <div class="btn-spinner"></div>
            <span class="btn-label"><i class="bi bi-person-plus-fill"></i> Register User</span>
          </button>

        </form>
      </div>
    </div>
  </div>

</div>

<!-- ── ERROR MODAL ── -->
<?php if ($feedback && !$redirect): ?>
<div class="modal-overlay open" id="errorModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-icon error"><i class="bi bi-exclamation-circle-fill"></i></div>
      <div>
        <div class="modal-head-title">Registration Error</div>
        <div class="modal-head-sub">Please fix the issue and try again.</div>
      </div>
    </div>
    <div class="modal-body-inner"><?= htmlspecialchars($feedback) ?></div>
    <div class="modal-footer-btns">
      <button class="btn-modal-sec" onclick="document.getElementById('errorModal').classList.remove('open')">
        <i class="bi bi-arrow-left"></i> Go Back
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── SUCCESS MODAL ── -->
<?php if ($redirect): ?>
<div class="modal-overlay open" id="successModal">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-head-icon success"><i class="bi bi-check-circle-fill"></i></div>
      <div>
        <div class="modal-head-title">Registration Successful</div>
        <div class="modal-head-sub">User account created successfully.</div>
      </div>
    </div>
    <div class="modal-body-inner">
      The new user has been added to the system.
      <div class="success-detail">
        <div class="sd-row"><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($generatedEmail ?? '') ?></div>
      </div>
      <div class="success-notice">
        <i class="bi bi-info-circle-fill"></i>
        The user can now log in with their email and the password you set.
      </div>
    </div>
    <div class="modal-footer-btns">
      <button class="btn-modal-sec"
        onclick="document.getElementById('successModal').classList.remove('open');document.getElementById('registerForm').reset();">
        <i class="bi bi-person-plus"></i> Register Another
      </button>
      <a href="../login.php" class="btn-modal-pri">
        <i class="bi bi-box-arrow-in-right"></i> Go to Login
      </a>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
  // submit loading state
  document.getElementById('registerForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.disabled = true;
  });

  // close modals on backdrop click
  document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', function(e) {
      if (e.target === this) this.classList.remove('open');
    });
  });

  // password strength meter
  function checkStrength(val) {
    const segs   = [document.getElementById('s1'),document.getElementById('s2'),document.getElementById('s3'),document.getElementById('s4')];
    const label  = document.getElementById('strengthLabel');
    const colors = { 0:'#e2e8f0', 1:'#ef4444', 2:'#f59e0b', 3:'#3b82f6', 4:'#10b981' };
    const labels = { 0:'', 1:'Weak', 2:'Fair', 3:'Good', 4:'Strong' };

    let score = 0;
    if (val.length >= 8)             score++;
    if (/[A-Z]/.test(val))           score++;
    if (/[0-9]/.test(val))           score++;
    if (/[^A-Za-z0-9]/.test(val))   score++;

    segs.forEach((s, i) => {
      s.style.background = i < score ? colors[score] : '#e2e8f0';
    });
    label.textContent = val.length ? labels[score] : '';
    label.style.color = colors[score];
  }
</script>
</body>
</html>