<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../db.php';
require '../includes/auth.php';
require '../vendor/autoload.php';

$user_id = $_SESSION['user']['user_id'] ?? null;
$role_id = $_SESSION['user']['role_id'] ?? null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']); exit;
    }

    $patient_type   = $_POST['patient_type'];
    $patient_status = $_POST['patient_status'];
    $full_name      = $_POST['full_name'];
    $gender         = $_POST['gender'];
    $dob            = $_POST['dob'];
    $age            = $_POST['age'];
    $email          = !empty($_POST['email']) ? $_POST['email'] : null;
    $phone          = $_POST['phone'];
    $address        = $_POST['address'];
    $hmo_name       = ($patient_type === 'HMO') ? $_POST['hmo_name'] : null;
    $language       = $_POST['language'];
    $registered_by  = $_POST['registered_by'];

    $pin = strtolower(str_replace(' ', '', explode(' ', $full_name)[0])) . rand(100000, 999999);

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']); exit;
    }

    if (!preg_match('/^(\+234|0)[789][01]\d{8}$/', $phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format.']); exit;
    }

    $query  = "SELECT email, phone, full_name FROM patients WHERE (phone = :phone OR full_name = :full_name)";
    $params = [':phone' => $phone, ':full_name' => $full_name];
    if (!empty($email)) { $query .= " OR email = :email"; $params[':email'] = $email; }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $takenFields = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['email']     === $email)     $takenFields[] = "Email";
        if ($row['phone']     === $phone)     $takenFields[] = "Phone number";
        if ($row['full_name'] === $full_name) $takenFields[] = "Full name";
    }
    if (!empty($takenFields)) {
        echo json_encode(['status' => 'error', 'message' => implode(", ", $takenFields) . " already taken."]); exit;
    }

    $photoPath = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/";
        $filename  = time() . "_" . basename($_FILES["photo"]["name"]);
        $photoPath = $targetDir . $filename;
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mime      = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg','image/png','image/gif'])) {
            echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, or GIF files are allowed.']); exit;
        }
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $photoPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']); exit;
        }
    }

    try {
        $pdo->beginTransaction();
        $role_id  = 7;
        $password = password_hash($pin, PASSWORD_BCRYPT);

        $pdo->prepare("INSERT INTO users (role_id, full_name, email, password, phone, created_at, profile_image) VALUES (?, ?, ?, ?, ?, NOW(), ?)")
            ->execute([$role_id, $full_name, $email ?? null, $password, $phone, $photoPath]);
        $user_id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO patients (user_id, full_name, gender, dob, age, email, phone, address, patient_pin, photo, patient_type, patient_status, hmo_name, language, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$user_id, $full_name, $gender, $dob, $age, $email, $phone, $address, $pin, $photoPath, $patient_type, $patient_status, $hmo_name, $language, $registered_by]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'pin' => $pin, 'patient_id' => $user_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
    exit;
}

$receptionists = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 8")->fetchAll();
$staff_name    = $_SESSION['user']['full_name'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Patient — Angelora Hospital</title>

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
      --green-500:  #10b981;
      --green-50:   #ecfdf5;
      --green-200:  #a7f3d0;
      --amber-500:  #f59e0b;
      --red-500:    #ef4444;
      --red-50:     #fef2f2;
      --red-200:    #fecaca;
      --shadow-sm:  0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md:  0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg:  0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
      --radius:     12px;
      --blue-glow:  rgba(37,99,235,.12);
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
    .topbar-date  { font-size: 12px; color: var(--gray-400); padding: 5px 12px; background: var(--gray-100); border-radius: 999px; border: 1px solid var(--gray-200); }
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
    .page { max-width: 960px; margin: 0 auto; padding: 36px 24px 60px; }

    /* ── Page Header ── */
    .page-header { margin-bottom: 28px; }
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--gray-400); margin-bottom: 10px;
    }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; font-weight: 500; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; color: var(--gray-300); }
    .page-title { font-family: 'Instrument Serif', serif; font-size: 2rem; font-weight: 400; color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-400); margin-top: 5px; }

    /* ── Steps ── */
    .steps {
      display: flex; align-items: center;
      margin-bottom: 24px;
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 16px 24px;
      box-shadow: var(--shadow-sm);
      overflow-x: auto;
      gap: 0;
    }
    .step { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .step-num {
      width: 30px; height: 30px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700;
      background: var(--gray-100);
      border: 2px solid var(--gray-200);
      color: var(--gray-400);
      transition: all .2s;
    }
    .step.active .step-num {
      background: var(--blue-600);
      border-color: var(--blue-600);
      color: white;
      box-shadow: 0 0 0 4px var(--blue-glow);
    }
    .step.done .step-num {
      background: var(--green-500);
      border-color: var(--green-500);
      color: white;
    }
    .step-label { font-size: 12.5px; color: var(--gray-400); font-weight: 500; }
    .step.active .step-label { color: var(--blue-600); font-weight: 700; }
    .step.done   .step-label { color: var(--green-500); }
    .step-connector { flex: 1; height: 2px; background: var(--gray-200); margin: 0 12px; min-width: 24px; }
    .step.done + .step-connector { background: var(--green-200); }

    /* ── Form Card ── */
    .form-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow-md);
    }

    /* ── Section ── */
    .form-section {
      padding: 28px 32px;
      border-bottom: 1px solid var(--gray-100);
    }
    .form-section:last-child { border-bottom: none; }

    .section-heading {
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 22px;
    }
    .section-num {
      width: 28px; height: 28px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; color: white;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(37,99,235,.25);
    }
    .section-name {
      font-size: 12px; font-weight: 700;
      letter-spacing: .1em; text-transform: uppercase;
      color: var(--blue-700);
    }
    .section-line { flex: 1; height: 1px; background: var(--gray-100); }

    /* ── Grid ── */
    .field-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 16px; }
    .col-3  { grid-column: span 3; }
    .col-4  { grid-column: span 4; }
    .col-6  { grid-column: span 6; }
    .col-8  { grid-column: span 8; }
    .col-12 { grid-column: span 12; }

    @media (max-width: 768px) {
      .col-3, .col-4, .col-6, .col-8 { grid-column: span 12; }
    }
    @media (min-width: 769px) and (max-width: 960px) {
      .col-3 { grid-column: span 6; }
      .col-4 { grid-column: span 6; }
    }

    /* ── Field ── */
    .field { display: flex; flex-direction: column; gap: 7px; }

    .field label {
      font-size: 11px; font-weight: 700;
      letter-spacing: .06em; text-transform: uppercase;
      color: var(--gray-500);
      display: flex; align-items: center; gap: 5px;
    }
    .field label .req { color: var(--red-500); }
    .field label .opt {
      font-size: 10px; font-weight: 500; text-transform: none;
      letter-spacing: 0; color: var(--gray-400);
      background: var(--gray-100); border: 1px solid var(--gray-200);
      border-radius: 4px; padding: 1px 7px;
    }

    .input-wrap { position: relative; }
    .input-wrap .input-icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: var(--gray-400); font-size: 15px; pointer-events: none;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="number"],
    input[type="date"],
    select, textarea {
      width: 100%;
      padding: 11px 14px;
      background: var(--gray-50);
      border: 1.5px solid var(--gray-200);
      border-radius: 9px;
      color: var(--gray-700);
      font-family: 'Sora', sans-serif;
      font-size: 13.5px;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
      appearance: none;
    }
    input:hover, select:hover { border-color: var(--blue-300); }
    input:focus, select:focus, textarea:focus {
      border-color: var(--blue-500);
      background: var(--white);
      box-shadow: 0 0 0 3px var(--blue-glow);
    }
    input::placeholder { color: var(--gray-300); }
    select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 13px center; padding-right: 36px; background-color: var(--gray-50); }

    .has-icon input, .has-icon select { padding-left: 38px; }

    /* ── Photo upload ── */
    .photo-upload-wrap {
      display: flex; align-items: center; gap: 20px;
      padding: 20px 24px;
      background: var(--blue-50);
      border: 1.5px dashed var(--blue-200);
      border-radius: 12px;
      margin-bottom: 22px;
    }
    .photo-preview {
      width: 76px; height: 76px; border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-600), var(--blue-400));
      border: 3px solid var(--white);
      box-shadow: 0 4px 14px rgba(37,99,235,.25);
      display: flex; align-items: center; justify-content: center;
      overflow: hidden; flex-shrink: 0;
    }
    .photo-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }
    .photo-preview i { font-size: 28px; color: rgba(255,255,255,.85); }
    .photo-upload-info { flex: 1; }
    .photo-upload-btn {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 9px 18px; border-radius: 8px;
      background: var(--white);
      border: 1.5px solid var(--blue-200);
      color: var(--blue-600); font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; cursor: pointer;
      transition: all .18s;
      box-shadow: var(--shadow-sm);
    }
    .photo-upload-btn:hover { background: var(--blue-50); border-color: var(--blue-400); box-shadow: var(--shadow-md); }
    .photo-upload-hint { font-size: 11.5px; color: var(--gray-400); margin-top: 6px; }
    #photoInput { display: none; }

    /* ── HMO section ── */
    .hmo-section { display: none; }
    .hmo-section.visible { display: block; }

    .hmo-inner {
      background: var(--blue-50);
      border: 1px solid var(--blue-100);
      border-radius: 10px;
      padding: 18px 20px;
      margin-top: 16px;
    }
    .hmo-inner-label {
      font-size: 11px; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; color: var(--blue-600);
      margin-bottom: 14px; display: flex; align-items: center; gap: 6px;
    }

    .hmo-verify-wrap { display: flex; gap: 10px; align-items: flex-end; }
    .hmo-verify-wrap .field { flex: 1; }
    .btn-verify {
      display: flex; align-items: center; gap: 6px;
      padding: 11px 18px; border-radius: 9px;
      background: var(--blue-600);
      border: none;
      color: white; font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 600; cursor: pointer;
      transition: all .18s; white-space: nowrap; flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(37,99,235,.25);
    }
    .btn-verify:hover { background: var(--blue-700); box-shadow: 0 6px 18px rgba(37,99,235,.35); }

    .verify-status {
      margin-top: 8px; font-size: 12.5px;
      display: flex; align-items: center; gap: 6px;
      min-height: 20px;
    }
    .verify-status.ok   { color: var(--green-500); }
    .verify-status.fail { color: var(--red-500); }
    .verify-status.wait { color: var(--gray-400); }

    /* ── Form Footer ── */
    .form-footer {
      padding: 20px 32px;
      background: var(--gray-50);
      border-top: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: space-between;
      gap: 16px; flex-wrap: wrap;
    }
    .form-footer-note {
      font-size: 12px; color: var(--gray-400);
      display: flex; align-items: center; gap: 7px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 8px; padding: 8px 14px;
    }
    .form-footer-note i { color: var(--blue-500); font-size: 14px; }

    .btn-submit {
      display: flex; align-items: center; gap: 9px;
      padding: 13px 32px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; border-radius: 10px;
      color: white; font-family: 'Sora', sans-serif;
      font-size: 14px; font-weight: 700;
      cursor: pointer; position: relative; overflow: hidden;
      transition: all .2s;
      box-shadow: 0 6px 20px rgba(37,99,235,.35);
    }
    .btn-submit::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,.15) 0%, transparent 60%);
    }
    .btn-submit:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(37,99,235,.45);
    }
    .btn-submit:active:not(:disabled) { transform: translateY(0); }
    .btn-submit:disabled { opacity: .55; cursor: not-allowed; }
    .btn-spinner {
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: white;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none;
    }
    .btn-submit.loading .btn-spinner { display: block; }
    .btn-submit.loading .btn-text    { opacity: .8; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Alert ── */
    .alert-box {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px 18px; border-radius: 10px;
      font-size: 13.5px; line-height: 1.5;
    }
    .alert-box i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .alert-error  { background: var(--red-50); border: 1px solid var(--red-200); color: #b91c1c; }
    .alert-error i { color: var(--red-500); }
    #responseWrap { padding: 0 32px 20px; }
    #responseWrap:empty { display: none; }

    /* ── Success Modal ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 500;
      background: rgba(15,45,107,.45);
      backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; animation: fadeIn .2s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .modal-box {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 20px;
      width: 100%; max-width: 440px; margin: 20px;
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      animation: slideUp .3s cubic-bezier(.16,1,.3,1);
    }
    @keyframes slideUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:none; } }

    .modal-top {
      background: linear-gradient(135deg, var(--blue-800), var(--blue-500));
      padding: 32px 28px 28px;
      text-align: center;
      position: relative; overflow: hidden;
    }
    .modal-top::before {
      content: ''; position: absolute;
      top: -40px; right: -40px;
      width: 160px; height: 160px; border-radius: 50%;
      background: rgba(255,255,255,.06); pointer-events: none;
    }
    .modal-check {
      width: 64px; height: 64px; border-radius: 50%;
      background: rgba(255,255,255,.2);
      border: 2px solid rgba(255,255,255,.35);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }
    .modal-check i { font-size: 28px; color: white; }
    .modal-top h3  { font-family: 'Instrument Serif', serif; font-size: 1.55rem; font-weight: 400; color: white; }
    .modal-top p   { font-size: 13px; color: rgba(255,255,255,.7); margin-top: 5px; }

    .modal-body {
      padding: 24px 28px;
      display: flex; flex-direction: column; gap: 16px;
    }
    .modal-row {
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
      padding-bottom: 14px; border-bottom: 1px solid var(--gray-100);
    }
    .modal-row:last-child { border-bottom: none; padding-bottom: 0; }
    .modal-key { font-size: 11px; color: var(--gray-400); text-transform: uppercase; letter-spacing: .08em; }
    .modal-val { font-size: 14px; font-weight: 600; color: var(--gray-900); }

    .pin-display {
      background: linear-gradient(135deg, var(--blue-50), #f0f7ff);
      border: 1.5px solid var(--blue-100);
      border-radius: 12px;
      padding: 18px 22px;
      text-align: center;
    }
    .pin-label  { font-size: 10.5px; text-transform: uppercase; letter-spacing: .12em; color: var(--gray-400); margin-bottom: 8px; font-weight: 600; }
    .pin-value  { font-family: 'Courier New', monospace; font-size: 28px; font-weight: 700; color: var(--blue-700); letter-spacing: .05em; }
    .pin-hint   { font-size: 11.5px; color: var(--gray-400); margin-top: 8px; }

    .modal-footer-btns {
      display: flex; gap: 10px; padding: 0 28px 24px;
    }
    .btn-modal-back {
      flex: 1; padding: 11px;
      background: var(--gray-100);
      border: 1px solid var(--gray-200);
      border-radius: 9px; color: var(--gray-500);
      font-family: 'Sora', sans-serif; font-size: 13px;
      font-weight: 600; text-decoration: none; text-align: center;
      transition: all .18s; cursor: pointer;
    }
    .btn-modal-back:hover { background: var(--gray-200); color: var(--gray-700); }
    .btn-modal-new {
      flex: 1; padding: 11px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; border-radius: 9px;
      color: white; font-family: 'Sora', sans-serif;
      font-size: 13px; font-weight: 700; cursor: pointer;
      transition: all .18s;
      box-shadow: 0 4px 12px rgba(37,99,235,.25);
    }
    .btn-modal-new:hover { opacity: .92; box-shadow: 0 6px 18px rgba(37,99,235,.35); }

    @media (max-width: 600px) {
      .topbar { padding: 0 16px; }
      .topbar-date { display: none; }
      .page { padding: 20px 14px 48px; }
      .form-section { padding: 18px 16px; }
      .form-footer { padding: 16px; }
      .steps { padding: 12px 16px; }
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
    <span class="brand-page">Patient Registration</span>
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
      <span>Register New Patient</span>
    </div>
    <h1 class="page-title">Register <em>New Patient</em></h1>
    <p class="page-sub">Fill in all required fields to create a new patient record and generate their PIN.</p>
  </div>

  <!-- Steps -->
  <div class="steps">
    <div class="step active">
      <div class="step-num">1</div>
      <div class="step-label">Personal Info</div>
    </div>
    <div class="step-connector"></div>
    <div class="step active">
      <div class="step-num">2</div>
      <div class="step-label">Contact Details</div>
    </div>
    <div class="step-connector"></div>
    <div class="step active">
      <div class="step-num">3</div>
      <div class="step-label">Patient Type</div>
    </div>
    <div class="step-connector"></div>
    <div class="step">
      <div class="step-num">4</div>
      <div class="step-label">PIN Generated</div>
    </div>
  </div>

  <!-- Form -->
  <form id="patientForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="hmo_verified" id="hmo_verified" value="0">

    <div class="form-card">

      <!-- Section 1: Personal Info -->
      <div class="form-section">
        <div class="section-heading">
          <div class="section-num">1</div>
          <div class="section-name">Personal Information</div>
          <div class="section-line"></div>
        </div>

        <div class="photo-upload-wrap">
          <div class="photo-preview" id="photoPreview">
            <img id="photoImg" src="" alt="">
            <i class="bi bi-person-fill" id="photoIcon"></i>
          </div>
          <div class="photo-upload-info">
            <label class="photo-upload-btn" for="photoInput">
              <i class="bi bi-cloud-upload-fill"></i> Upload Patient Photo
            </label>
            <div class="photo-upload-hint">JPG, PNG or GIF &nbsp;·&nbsp; Max 5MB &nbsp;·&nbsp; Passport photograph preferred</div>
          </div>
          <input type="file" name="photo" id="photoInput" accept="image/*">
        </div>

        <div class="field-grid">
          <div class="col-8 field">
            <label>Full Name <span class="req">*</span></label>
            <div class="input-wrap has-icon">
              <i class="bi bi-person input-icon"></i>
              <input type="text" name="full_name" placeholder="e.g. Amara Chisom Okafor" required>
            </div>
          </div>
          <div class="col-4 field">
            <label>Gender <span class="req">*</span></label>
            <select name="gender" required>
              <option value="">Select gender</option>
              <option>Male</option>
              <option>Female</option>
            </select>
          </div>
          <div class="col-3 field">
            <label>Date of Birth <span class="req">*</span></label>
            <input type="date" name="dob" id="dob" required>
          </div>
          <div class="col-3 field">
            <label>Age <span class="req">*</span></label>
            <div class="input-wrap has-icon">
              <i class="bi bi-calendar3 input-icon"></i>
              <input type="number" name="age" id="age" placeholder="Auto-filled" required>
            </div>
          </div>
          <div class="col-6 field">
            <label>Language <span class="req">*</span></label>
            <div class="input-wrap has-icon">
              <i class="bi bi-translate input-icon"></i>
              <input type="text" name="language" placeholder="e.g. English, Igbo, Hausa" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Contact -->
      <div class="form-section">
        <div class="section-heading">
          <div class="section-num">2</div>
          <div class="section-name">Contact Details</div>
          <div class="section-line"></div>
        </div>
        <div class="field-grid">
          <div class="col-4 field">
            <label>Phone <span class="req">*</span></label>
            <div class="input-wrap has-icon">
              <i class="bi bi-telephone input-icon"></i>
              <input type="tel" name="phone" placeholder="08012345678" required>
            </div>
          </div>
          <div class="col-4 field">
            <label>Email <span class="opt">optional</span></label>
            <div class="input-wrap has-icon">
              <i class="bi bi-envelope input-icon"></i>
              <input type="email" name="email" placeholder="patient@email.com">
            </div>
          </div>
          <div class="col-4"></div>
          <div class="col-12 field">
            <label>Address <span class="req">*</span></label>
            <div class="input-wrap has-icon">
              <i class="bi bi-geo-alt input-icon"></i>
              <input type="text" name="address" placeholder="Full residential address" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Classification -->
      <div class="form-section">
        <div class="section-heading">
          <div class="section-num">3</div>
          <div class="section-name">Classification & Assignment</div>
          <div class="section-line"></div>
        </div>
        <div class="field-grid">
          <div class="col-4 field">
            <label>Patient Type <span class="req">*</span></label>
            <select name="patient_type" id="patient_type" required onchange="toggleHmo(this.value)">
              <option value="">Select type</option>
              <option value="Regular">Private</option>
              <option value="HMO">HMO</option>
            </select>
          </div>
          <div class="col-4 field">
            <label>Patient Status <span class="req">*</span></label>
            <select name="patient_status" required>
              <option value="">Select status</option>
              <option value="Outpatient">Outpatient</option>
              <option value="Inpatient">Inpatient</option>
            </select>
          </div>
          <div class="col-4 field">
            <label>Registered By <span class="req">*</span></label>
            <select name="registered_by" required>
              <option value="">Select staff</option>
              <?php foreach ($receptionists as $u): ?>
                <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- HMO fields -->
        <div class="hmo-section" id="hmoSection">
          <div class="hmo-inner">
            <div class="hmo-inner-label"><i class="bi bi-shield-check-fill"></i> HMO Details</div>
            <div class="field-grid">
              <div class="col-6 field">
                <label>HMO Name <span class="req">*</span></label>
                <div class="input-wrap has-icon">
                  <i class="bi bi-building input-icon"></i>
                  <input type="text" name="hmo_name" id="hmo_name" placeholder="e.g. Hygeia HMO, NHIS">
                </div>
              </div>
              <div class="col-6">
                <div class="hmo-verify-wrap">
                  <div class="field" style="flex:1;">
                    <label>HMO Code</label>
                    <div class="input-wrap has-icon">
                      <i class="bi bi-qr-code input-icon"></i>
                      <input type="text" id="hmo_code" placeholder="Enter code to verify">
                    </div>
                  </div>
                  <button type="button" class="btn-verify" onclick="verifyHmoCode()">
                    <i class="bi bi-patch-check-fill"></i> Verify
                  </button>
                </div>
                <div class="verify-status" id="verifyStatus"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alert area -->
      <div id="responseWrap"></div>

      <!-- Footer -->
      <div class="form-footer">
        <div class="form-footer-note">
          <i class="bi bi-key-fill"></i>
          A unique PIN will be auto-generated after registration.
        </div>
        <button type="submit" id="submitBtn" class="btn-submit">
          <span class="btn-text"><i class="bi bi-person-plus-fill"></i> Register Patient</span>
          <div class="btn-spinner"></div>
        </button>
      </div>

    </div>
  </form>
</div>

<!-- ══ SUCCESS MODAL ══ -->
<div class="modal-overlay" id="successModal">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-check"><i class="bi bi-check-lg"></i></div>
      <h3>Registration Successful</h3>
      <p>Patient has been added to the system.</p>
    </div>
    <div class="modal-body">
      <div class="modal-row">
        <span class="modal-key">Patient ID</span>
        <span class="modal-val" id="modalPatientId">—</span>
      </div>
      <div class="pin-display">
        <div class="pin-label">Patient PIN / Login Password</div>
        <div class="pin-value" id="modalPin">—</div>
        <div class="pin-hint">Copy and hand this PIN to the patient for future logins.</div>
      </div>
    </div>
    <div class="modal-footer-btns">
      <a href="dashboard.php" class="btn-modal-back"><i class="bi bi-arrow-left"></i> Dashboard</a>
      <button class="btn-modal-new" onclick="registerAnother()">
        <i class="bi bi-person-plus"></i> Register Another
      </button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('patientForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  btn.classList.add('loading'); btn.disabled = true;

  fetch('patients_register.php', { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        document.getElementById('modalPatientId').textContent = data.patient_id;
        document.getElementById('modalPin').textContent       = data.pin;
        document.getElementById('successModal').classList.add('open');
        document.getElementById('responseWrap').innerHTML = '';
        this.reset(); resetPhotoPreview();
      } else {
        showError(data.message || 'Registration failed.');
      }
    })
    .catch(() => showError('Network error. Please try again.'))
    .finally(() => { btn.classList.remove('loading'); btn.disabled = false; });
});

function showError(msg) {
  document.getElementById('responseWrap').innerHTML =
    `<div class="alert-box alert-error" style="margin:0 32px 20px;">
      <i class="bi bi-exclamation-circle-fill"></i><span>${msg}</span>
    </div>`;
  window.scrollTo({ top: document.getElementById('responseWrap').offsetTop - 20, behavior: 'smooth' });
}

function toggleHmo(val) {
  const sec = document.getElementById('hmoSection');
  sec.classList.toggle('visible', val === 'HMO');
  if (val !== 'HMO') {
    document.getElementById('hmo_verified').value = '0';
    document.getElementById('verifyStatus').innerHTML = '';
    document.getElementById('verifyStatus').className = 'verify-status';
  }
}

function verifyHmoCode() {
  const code = document.getElementById('hmo_code').value.trim();
  const statusEl = document.getElementById('verifyStatus');
  if (!code) {
    statusEl.className = 'verify-status fail';
    statusEl.innerHTML = '<i class="bi bi-x-circle-fill"></i> Enter an HMO code first.'; return;
  }
  statusEl.className = 'verify-status wait';
  statusEl.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation:spin .7s linear infinite;display:inline-block"></i> Verifying…';
  fetch('verify_hmo_api.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ hmo_code: code })
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      statusEl.className = 'verify-status ok';
      statusEl.innerHTML = `<i class="bi bi-patch-check-fill"></i> ${data.hmo_name} — Verified`;
      document.getElementById('hmo_verified').value = '1';
    } else {
      statusEl.className = 'verify-status fail';
      statusEl.innerHTML = `<i class="bi bi-x-circle-fill"></i> ${data.message || 'Verification failed.'}`;
      document.getElementById('hmo_verified').value = '0';
    }
  })
  .catch(() => {
    statusEl.className = 'verify-status fail';
    statusEl.innerHTML = '<i class="bi bi-x-circle-fill"></i> Could not verify. Try again.';
  });
}

document.getElementById('dob').addEventListener('change', function () {
  const dob = new Date(this.value);
  const now  = new Date();
  let age = now.getFullYear() - dob.getFullYear();
  const m = now.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--;
  document.getElementById('age').value = isNaN(age) ? '' : Math.max(age, 0);
});

document.getElementById('photoInput').addEventListener('change', function () {
  const file = this.files[0]; if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('photoImg');
    const icon = document.getElementById('photoIcon');
    img.src = e.target.result;
    img.style.display = 'block'; icon.style.display = 'none';
  };
  reader.readAsDataURL(file);
});

function resetPhotoPreview() {
  const img = document.getElementById('photoImg');
  const icon = document.getElementById('photoIcon');
  img.src = ''; img.style.display = 'none'; icon.style.display = '';
}

function registerAnother() {
  document.getElementById('successModal').classList.remove('open');
  document.getElementById('patientForm').reset();
  resetPhotoPreview();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('successModal').addEventListener('click', function (e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
</body>
</html>