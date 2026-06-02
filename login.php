<?php
require 'db.php';
session_start();

if (!isset($pdo)) { die("PDO not set. Check db.php connection."); }

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $ip         = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $login_time = date('Y-m-d H:i:s');

            $log = $pdo->prepare("INSERT INTO login_activity (user_id, full_name, role_id, email, login_time, status, login_state, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, 'success', 'Online', ?, ?)");
            $log->execute([$user['user_id'], $user['full_name'], $user['role_id'], $user['email'], $login_time, $ip, $user_agent]);

            $stmtAct = $pdo->prepare("INSERT INTO activities (user_id, role_id, action, created_at) VALUES (?, ?, ?, NOW())");
            $stmtAct->execute([$user['user_id'], $user['role_id'], "Logged in"]);

            $_SESSION['user'] = [
                'user_id'   => $user['user_id'],
                'role_id'   => $user['role_id'],
                'full_name' => $user['full_name'],
                'shift'     => $user['shift']
            ];
            $_SESSION['login_success'] = "Welcome, " . htmlspecialchars($user['full_name']);

            switch ($user['role_id']) {
                case 1:  header('Location: admin/dashboard.php');        break;
                case 2:
                    $doctor_id = $user['user_id'];
                    $today     = date('Y-m-d');
                    $pdo->prepare("UPDATE appointments SET status = 'Confirmed' WHERE doctor_id = ? AND appointment_date = ? AND status = 'Pending'")->execute([$doctor_id, $today]);
                    $pdo->prepare("UPDATE appointments SET status = 'Complete'  WHERE doctor_id = ? AND appointment_date < ? AND status != 'Complete'")->execute([$doctor_id, $today]);
                    header('Location: doctor/dashboard.php');             break;
                case 3:  header('Location: nurse/dashboard.php');         break;
                case 4:  header('Location: cashier/dashboard.php');       break;
                case 5:  header('Location: pharmacist/dashboard.php');    break;
                case 6:  header('Location: lab/dashboard.php');           break;
                case 7:  header('Location: patient/dashboard.php');       break;
                case 8:  header('Location: receptionist/dashboard.php');  break;
                case 9:  header('Location: MD/md_dashboard.php');         break;
                case 10: header('Location: radiology/dashboard.php');     break;
                case 11: header('Location: cleaner/dashboard.php');       break;
                default: $error = "Unauthorized role.";                   break;
            }
            exit;

        } else {
            $stmtFail = $pdo->prepare("INSERT INTO activities (user_id, role_id, action, created_at) VALUES (?, ?, ?, NOW())");
            $stmtFail->execute([$user['user_id'] ?? null, $user['role_id'] ?? null, "Failed login attempt with email: {$email}"]);
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Both email and password are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Login — Angelora Hospital</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">

  <style>
    /* ── RESET ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --teal:        #0d9488;
      --teal-dark:   #0f766e;
      --teal-light:  #ccfbf1;
      --teal-glow:   rgba(13,148,136,.12);
      --navy:        #0f172a;
      --slate:       #334155;
      --muted:       #64748b;
      --border:      #e2e8f0;
      --bg:          #f8fafc;
      --white:       #ffffff;
      --red:         #ef4444;
      --red-bg:      #fef2f2;
      --red-border:  #fecaca;
    }

    html { height: 100%; }

    body {
      min-height: 100%;
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--navy);
      -webkit-font-smoothing: antialiased;
    }

    /* ── PAGE GRID ── */
    .page {
      display: grid;
      grid-template-columns: 1fr 480px;
      min-height: 100svh;         /* safe viewport height */
    }

    /* ══════════════════════════════════
       LEFT PANEL — branding
    ══════════════════════════════════ */
    .panel-left {
      position: relative;
      overflow: hidden;
      background: var(--navy);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: clamp(32px, 6vw, 64px);
    }

    /* background image */
    .panel-left::before {
      content: '';
      position: absolute; inset: 0;
      background: url('images/patient.jpg') center / cover no-repeat;
      opacity: .22;
    }

    /* top-right glow */
    .panel-left::after {
      content: '';
      position: absolute;
      top: -120px; right: -120px;
      width: 480px; height: 480px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(13,148,136,.35) 0%, transparent 70%);
      pointer-events: none;
    }

    /* bottom-left glow */
    .deco-circle {
      position: absolute;
      bottom: -80px; left: -80px;
      width: 320px; height: 320px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(13,148,136,.2) 0%, transparent 70%);
      pointer-events: none;
    }

    .panel-content {
      position: relative; z-index: 1;
      animation: slideUp .9s cubic-bezier(.16,1,.3,1) both;
    }

    /* live badge */
    .badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(13,148,136,.25);
      border: 1px solid rgba(13,148,136,.4);
      color: #5eead4;
      font-size: 11px; font-weight: 600;
      letter-spacing: .12em; text-transform: uppercase;
      padding: 6px 14px; border-radius: 100px;
      margin-bottom: 24px;
    }
    .badge-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: #2dd4bf;
      box-shadow: 0 0 0 3px rgba(45,212,191,.3);
      animation: pulse-dot 2s ease-in-out infinite;
      flex-shrink: 0;
    }
    @keyframes pulse-dot {
      0%,100% { box-shadow: 0 0 0 3px rgba(45,212,191,.3); }
      50%      { box-shadow: 0 0 0 6px rgba(45,212,191,.1); }
    }

    .panel-title {
      font-family: 'Instrument Serif', Georgia, serif;
      font-size: clamp(2rem, 4vw, 3.6rem);
      font-weight: 400;
      line-height: 1.15;
      color: var(--white);
      margin-bottom: 18px;
    }
    .panel-title em { font-style: italic; color: #5eead4; }

    .panel-tagline {
      font-size: clamp(13px, 1.6vw, 16px);
      font-weight: 300;
      color: rgba(255,255,255,.6);
      line-height: 1.7;
      max-width: 400px;
      margin-bottom: 40px;
    }

    .stats-row {
      display: flex; gap: clamp(20px, 4vw, 40px);
      padding-top: 28px;
      border-top: 1px solid rgba(255,255,255,.1);
      flex-wrap: wrap;
    }
    .stat { display: flex; flex-direction: column; gap: 4px; }
    .stat-number {
      font-family: 'Instrument Serif', serif;
      font-size: clamp(20px, 2.5vw, 28px);
      color: #5eead4;
    }
    .stat-label {
      font-size: 11px; font-weight: 500;
      color: rgba(255,255,255,.45);
      text-transform: uppercase; letter-spacing: .08em;
    }

    /* ══════════════════════════════════
       RIGHT PANEL — form
    ══════════════════════════════════ */
    .panel-right {
      background: var(--white);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: clamp(28px, 5vw, 56px) clamp(24px, 5vw, 52px);
      border-left: 1px solid var(--border);
      animation: fadeIn .7s ease both .2s;
      overflow-y: auto;             /* scroll if content is taller than viewport */
    }

    /* logo row */
    .form-logo {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 32px;
    }
    .logo-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: var(--teal);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .logo-icon svg { width: 20px; height: 20px; fill: white; }
    .logo-name {
      font-family: 'Instrument Serif', serif;
      font-size: 17px; color: var(--navy); letter-spacing: -.01em;
    }

    /* heading */
    .form-header { margin-bottom: 32px; }
    .form-title {
      font-family: 'Instrument Serif', serif;
      font-size: clamp(1.65rem, 4vw, 2rem);
      font-weight: 400; color: var(--navy);
      margin-bottom: 6px; line-height: 1.2;
    }
    .form-subtitle { font-size: 14px; color: var(--muted); }

    /* ── ERROR ALERT ── */
    .alert-error {
      display: flex; align-items: flex-start; gap: 12px;
      background: var(--red-bg);
      border: 1px solid var(--red-border);
      border-radius: 10px;
      padding: 13px 16px;
      margin-bottom: 22px;
      font-size: 14px; color: #b91c1c;
      animation: shake .4s ease;
    }
    .alert-error svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }

    @keyframes shake {
      0%,100% { transform: translateX(0); }
      20%      { transform: translateX(-5px); }
      40%      { transform: translateX(5px); }
      60%      { transform: translateX(-3px); }
      80%      { transform: translateX(3px); }
    }

    /* ── FIELDS ── */
    .field { margin-bottom: 18px; }
    .field label {
      display: block;
      font-size: 13px; font-weight: 600; color: var(--slate);
      margin-bottom: 7px; letter-spacing: .01em;
    }

    .input-wrap { position: relative; }
    .input-wrap .fi {           /* field icon */
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      width: 17px; height: 17px;
      color: var(--muted); pointer-events: none;
    }
    .input-wrap input {
      width: 100%;
      padding: 13px 14px 13px 42px;
      font-family: 'DM Sans', sans-serif;
      font-size: 15px; color: var(--navy);
      background: var(--bg);
      border: 1.5px solid var(--border);
      border-radius: 10px; outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
      /* prevent iOS zoom: font-size must be ≥16px for the input itself — we use 15px
         but set this to prevent auto-zoom on older iOS */
      -webkit-text-size-adjust: 100%;
    }

    /* On very small screens, bump font to 16px to prevent iOS auto-zoom */
    @media (max-width: 400px) {
      .input-wrap input { font-size: 16px; }
    }

    .input-wrap input:focus {
      border-color: var(--teal);
      background: var(--white);
      box-shadow: 0 0 0 4px var(--teal-glow);
    }
    .input-wrap input::placeholder { color: #94a3b8; }

    /* password toggle */
    .pw-toggle {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--muted); padding: 4px;
      display: flex; align-items: center;
      touch-action: manipulation;    /* better tap on mobile */
    }
    .pw-toggle:hover { color: var(--teal); }
    .pw-toggle svg { width: 17px; height: 17px; }

    /* forgot */
    .forgot-link {
      display: block; text-align: right;
      margin-top: -10px; margin-bottom: 24px;
      font-size: 13px; font-weight: 500;
      color: var(--teal); text-decoration: none;
    }
    .forgot-link:hover { text-decoration: underline; }

    /* ── SUBMIT ── */
    .btn-login {
      width: 100%;
      padding: 14px;
      font-family: 'DM Sans', sans-serif;
      font-size: 15px; font-weight: 600;
      color: var(--white); background: var(--teal);
      border: none; border-radius: 10px;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 10px;
      transition: background .2s, transform .15s, box-shadow .2s;
      position: relative; overflow: hidden;
      touch-action: manipulation;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-login::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.12) 0%, transparent 60%);
      pointer-events: none;
    }
    .btn-login:hover:not(:disabled) {
      background: var(--teal-dark);
      box-shadow: 0 8px 24px rgba(13,148,136,.32);
      transform: translateY(-1px);
    }
    .btn-login:active:not(:disabled) { transform: translateY(0); }
    .btn-login:disabled { opacity: .65; cursor: not-allowed; }

    .spinner {
      width: 17px; height: 17px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: white;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none; flex-shrink: 0;
    }
    .btn-login.loading .spinner { display: block; }
    .btn-login.loading .btn-text { opacity: .8; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* footer */
    .form-footer {
      margin-top: 28px; padding-top: 22px;
      border-top: 1px solid var(--border);
      text-align: center;
      font-size: 12px; color: var(--muted); line-height: 1.6;
    }

    /* ── ANIMATIONS ── */
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(36px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    /* ══════════════════════════════════
       RESPONSIVE BREAKPOINTS
    ══════════════════════════════════ */

    /* ── Tablet landscape / small desktop (≤1024px) ── */
    @media (max-width: 1024px) {
      .page { grid-template-columns: 1fr 420px; }
      .panel-tagline { max-width: 340px; }
    }

    /* ── Tablet portrait (≤768px) ── */
    @media (max-width: 768px) {
      .page {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr;
      }

      /* Left panel becomes a compact hero strip */
      .panel-left {
        padding: 36px clamp(20px, 5vw, 40px) 32px;
        min-height: auto;
        justify-content: flex-end;
      }

      .panel-title { font-size: clamp(1.7rem, 6vw, 2.2rem); margin-bottom: 0; }
      .panel-tagline { display: none; }
      .stats-row    { display: none; }
      .badge        { margin-bottom: 14px; font-size: 10px; padding: 5px 12px; }
      .deco-circle  { display: none; }

      /* Right panel fills remaining height */
      .panel-right {
        border-left: none;
        border-top: 1px solid var(--border);
        justify-content: flex-start;
        padding-top: 36px;
      }
    }

    /* ── Mobile landscape (≤640px) ── */
    @media (max-width: 640px) {
      .panel-left  { padding: 28px 20px 26px; }
      .panel-right { padding: 28px 20px; }
      .form-logo   { margin-bottom: 24px; }
      .form-header { margin-bottom: 24px; }
      .field       { margin-bottom: 16px; }
      .forgot-link { margin-bottom: 20px; }
      .form-footer { margin-top: 22px; padding-top: 18px; }
    }

    /* ── Small mobile (≤390px) ── */
    @media (max-width: 390px) {
      .panel-left  { padding: 24px 16px 22px; }
      .panel-right { padding: 22px 16px; }
      .panel-title { font-size: 1.6rem; }
      .form-title  { font-size: 1.5rem; }
      .logo-name   { font-size: 15px; }
      .input-wrap input { padding: 12px 14px 12px 40px; }
      .btn-login   { padding: 13px; font-size: 15px; }
    }

    /* ── Very tall / desktop large (≥1400px) ── */
    @media (min-width: 1400px) {
      .page { grid-template-columns: 1fr 520px; }
    }

    /* ── Landscape phone: prevent layout overflow ── */
    @media (max-height: 500px) and (orientation: landscape) {
      .panel-left  { display: none; }   /* hide hero, show only form */
      .page        { grid-template-columns: 1fr; }
      .panel-right {
        border: none;
        min-height: 100svh;
        justify-content: center;
        padding: 20px clamp(20px, 8vw, 120px);
      }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- ══ LEFT: BRANDING ══ -->
  <div class="panel-left">
    <div class="deco-circle"></div>
    <div class="panel-content">

      <div class="badge">
        <span class="badge-dot"></span>
        EMR
      </div>

      <h1 class="panel-title">
        Always in<br><em>good hands.</em>
      </h1>

      <p class="panel-tagline">
        A unified platform for every role — from the front desk to the operating theatre. Secure, fast, and built for care.
      </p>

      <div class="stats-row">
        <div class="stat">
          <span class="stat-label">Staff Roles</span>
        </div>
        <div class="stat">
          <span class="stat-number">24/7</span>
          <span class="stat-label">System Access</span>
        </div>
        <div class="stat">
          <span class="stat-number">100%</span>
          <span class="stat-label">Secure Login</span>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ RIGHT: FORM ══ -->
  <div class="panel-right">

    <div class="form-logo">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-7 3a1 1 0 0 1 1 1v3h3a1 1 0 0 1 0 2h-3v3a1 1 0 0 1-2 0v-3H8a1 1 0 0 1 0-2h3V7a1 1 0 0 1 1-1z"/>
        </svg>
      </div>
      <span class="logo-name">Angelora Hospital</span>
    </div>

    <div class="form-header">
      <h2 class="form-title">You are Welcome</h2>
      <p class="form-subtitle">Sign in with your role credentials to continue</p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert-error" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" novalidate>

      <div class="field">
        <label for="email">Email address</label>
        <div class="input-wrap">
          <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
          <input type="email" name="email" id="email"
                 placeholder="you@angelora.ng"
                 autocomplete="email"
                 inputmode="email"
                 required>
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input type="password" name="password" id="password"
                 placeholder="Enter your password"
                 autocomplete="current-password"
                 required>
          <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Show or hide password">
            <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <a href="forgot_password.php" class="forgot-link">Forgot password?</a>

      <button type="submit" id="loginBtn" class="btn-login">
        <span class="btn-text">Sign In</span>
        <div class="spinner" aria-hidden="true"></div>
      </button>

    </form>

    <div class="form-footer">
      &copy; <?= date('Y') ?> Angelora Hospital &mdash; All access is logged and monitored.
    </div>

  </div>
</div>

<script>
  // ── Loading state on submit ──
  document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('loginBtn');
    btn.classList.add('loading');
    btn.disabled = true;
  });

  // ── Password toggle ──
  const eyeOpen   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  const eyeClosed = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;
  let pwVisible = false;

  function togglePw() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    pwVisible   = !pwVisible;
    input.type  = pwVisible ? 'text' : 'password';
    icon.innerHTML = pwVisible ? eyeClosed : eyeOpen;
  }
</script>

</body>
</html>