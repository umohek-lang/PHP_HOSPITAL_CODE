<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../db.php';
require '../tcpdf/tcpdf.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_GET['id'])) {
    die("❌ Lab Test ID not provided.");
}

$lab_test_id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT l.*, p.full_name, p.email
    FROM lab_tests l
    JOIN patients p ON l.patient_id = p.patient_id
    WHERE l.lab_test_id = ?
");
$stmt->execute([$lab_test_id]);
$test = $stmt->fetch();

if (!$test) die("❌ Lab test not found.");
if (empty($test['email']) || !filter_var($test['email'], FILTER_VALIDATE_EMAIL)) die("❌ Invalid or missing patient email.");
if (empty($test['report_file'])) die("❌ Report file is missing in the database.");

$relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($test['report_file']));
$absolutePath = __DIR__ . DIRECTORY_SEPARATOR . $relativePath;
if (!file_exists($absolutePath)) die("❌ Report file not found at: $absolutePath");

// ─────────────────────────────────────────────────────────────
// SMTP CONFIGURATION
// ─────────────────────────────────────────────────────────────
// OPTION A: Gmail (recommended — most reliable)
//   Step 1: Go to myaccount.google.com → Security → 2-Step Verification → turn ON
//   Step 2: Go to myaccount.google.com → Security → App Passwords
//   Step 3: Create an app password for "Mail" and paste it below (16-char code, no spaces)
//
// OPTION B: Your cPanel/hosting SMTP
//   Use the mail server that matches the domain you're hosting the hospital system on.
//   e.g. if hosted on hostinger.com → smtp.hostinger.com, port 587
//   Ask your hosting provider for the correct SMTP settings.
// ─────────────────────────────────────────────────────────────

// ── CHANGE THESE 5 LINES ONLY ────────────────────────────────
define('SMTP_HOST',     'smtp.gmail.com');              // or smtp.hostinger.com, smtp.office365.com, etc.
define('SMTP_PORT',     587);                           // 587 for TLS (recommended), 465 for SSL
define('SMTP_USER',     'your_email@gmail.com');        // your full email address
define('SMTP_PASS',     'xxxx xxxx xxxx xxxx');         // Gmail App Password (16 chars) OR your email password
define('SMTP_FROM',     'your_email@gmail.com');        // must match SMTP_USER for Gmail
define('SMTP_FROMNAME', 'Angelora Hospital Lab');       // display name shown to patient
define('SMTP_REPLYTO',  'your_email@gmail.com');        // reply-to address
// ─────────────────────────────────────────────────────────────

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // use ENCRYPTION_SMTPS for port 465
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM, SMTP_FROMNAME);
    $mail->addAddress($test['email'], $test['full_name']);
    $mail->addReplyTo(SMTP_REPLYTO, SMTP_FROMNAME);

    // Embed logo if it exists
    $logoPath = __DIR__ . '/logo.png';
    $hasLogo  = file_exists($logoPath);
    if ($hasLogo) $mail->addEmbeddedImage($logoPath, 'logo_cid');

    $mail->isHTML(true);
    $mail->Subject = 'Your Lab Test Result — Angelora Hospital';

    $logoTag = $hasLogo
        ? "<img src='cid:logo_cid' alt='Angelora Hospital' style='width:110px;margin-bottom:8px;'><br>"
        : "<strong style='font-size:18px;color:#1d4ed8;'>Angelora Hospital</strong><br>";

    $mail->Body = "
    <html>
    <head>
      <meta charset='UTF-8'>
      <style>
        body { margin:0; padding:0; background:#f1f5f9; font-family:Arial,sans-serif; }
        .wrap { max-width:580px; margin:32px auto; background:#ffffff;
                border-radius:12px; overflow:hidden;
                box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .head { background:linear-gradient(135deg,#1d4ed8,#3b82f6);
                padding:28px 32px; text-align:center; }
        .head-sub { color:rgba(255,255,255,.75); font-size:12px;
                    margin-top:6px; letter-spacing:.05em; text-transform:uppercase; }
        .body { padding:32px; color:#334155; line-height:1.7; }
        .body h2 { font-size:20px; color:#1e293b; margin:0 0 16px; }
        .info-box { background:#eff6ff; border:1px solid #dbeafe;
                    border-radius:8px; padding:16px 20px; margin:20px 0; }
        .info-row { display:flex; justify-content:space-between;
                    font-size:13.5px; padding:5px 0;
                    border-bottom:1px solid #dbeafe; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#64748b; font-weight:600; }
        .info-value { color:#1e293b; font-weight:700; }
        .note { font-size:12px; color:#94a3b8; margin-top:24px;
                padding-top:16px; border-top:1px solid #e2e8f0; text-align:center; }
        .footer { background:#f8fafc; padding:18px 32px; text-align:center;
                  font-size:11.5px; color:#94a3b8; border-top:1px solid #e2e8f0; }
      </style>
    </head>
    <body>
      <div class='wrap'>
        <div class='head'>
          {$logoTag}
          <div style='color:white;font-size:22px;font-weight:700;'>Lab Test Result</div>
          <div class='head-sub'>Angelora Hospital · Laboratory Department</div>
        </div>
        <div class='body'>
          <h2>Hello, {$test['full_name']}</h2>
          <p>Your lab test result is ready. Please find the report attached to this email.</p>

          <div class='info-box'>
            <div class='info-row'>
              <span class='info-label'>Test Name</span>
              <span class='info-value'>{$test['test_name']}</span>
            </div>
            <div class='info-row'>
              <span class='info-label'>Test Date</span>
              <span class='info-value'>{$test['test_date']}</span>
            </div>
            <div class='info-row'>
              <span class='info-label'>Status</span>
              <span class='info-value' style='color:#16a34a;'>{$test['status']}</span>
            </div>
            <div class='info-row'>
              <span class='info-label'>Patient ID</span>
              <span class='info-value'>{$test['patient_id']}</span>
            </div>
          </div>

          <p>If you have any questions about your results, please contact us or visit the hospital.</p>
          <p class='note'>This is an automated message from Angelora Hospital Laboratory. Please do not reply directly to this email.</p>
        </div>
        <div class='footer'>
          &copy; " . date('Y') . " Angelora Hospital &nbsp;&middot;&nbsp; Laboratory Department<br>
          Sent on " . date('D, d M Y \a\t H:i') . "
        </div>
      </div>
    </body>
    </html>";

    $mail->AltBody = "Dear {$test['full_name']},\n\nYour lab result for {$test['test_name']} (Date: {$test['test_date']}) is ready. Please find the report attached.\n\nAngelora Hospital Laboratory.";

    $mail->addAttachment($absolutePath);
    $mail->send();

    header("Location: view_lab_test.php?success=1&msg=" . urlencode("Email sent successfully to {$test['email']}"));
    exit;

} catch (Exception $e) {
    // Friendly error page instead of raw message
    http_response_code(500);
    echo "<!DOCTYPE html><html><head>
        <meta charset='UTF-8'>
        <title>Email Error</title>
        <link href='https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&display=swap' rel='stylesheet'>
        <style>
          body{font-family:'Sora',sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
          .box{background:#fff;border:1.5px solid #fee2e2;border-radius:14px;padding:36px 40px;max-width:520px;box-shadow:0 8px 30px rgba(0,0,0,.08);}
          .icon{font-size:36px;margin-bottom:12px;}
          h2{color:#dc2626;font-size:18px;margin:0 0 10px;}
          p{color:#475569;font-size:13.5px;line-height:1.7;margin:0 0 10px;}
          code{display:block;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;font-size:12px;color:#b91c1c;margin:14px 0;word-break:break-all;}
          .tip{background:#eff6ff;border:1px solid #dbeafe;border-radius:8px;padding:12px 16px;font-size:12.5px;color:#1d4ed8;margin-top:16px;}
          a{display:inline-flex;align-items:center;gap:6px;margin-top:20px;padding:9px 20px;border-radius:8px;background:#2563eb;color:white;text-decoration:none;font-size:13px;font-weight:700;}
        </style>
      </head><body>
        <div class='box'>
          <div class='icon'>❌</div>
          <h2>Email Could Not Be Sent</h2>
          <p>The system failed to connect to the mail server. This is usually a configuration issue.</p>
          <code>" . htmlspecialchars($mail->ErrorInfo) . "</code>
          <div class='tip'>
            <strong>Common fixes:</strong><br>
            1. Check SMTP host, port and credentials in <code>send_lab_email.php</code><br>
            2. For Gmail: use an <strong>App Password</strong> (not your regular password)<br>
            3. Make sure your server allows outbound connections on port 587<br>
            4. Ask your hosting provider if SMTP is blocked
          </div>
          <a href='view_lab_test.php'>← Back to Lab Tests</a>
        </div>
      </body></html>";
}