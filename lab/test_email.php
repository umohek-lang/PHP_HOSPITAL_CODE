<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Show detailed debug output
    $mail->isSMTP();
    $mail->Host = 'mail.excellentgrade.ng';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@excellentgrade.ng';
    $mail->Password = 'ExcellentGradeInternationalSchool@12';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('noreply@excellentgrade.ng', 'Test Mail');
    $mail->addAddress('youremail@example.com', 'Your Name'); // Change to your real email

    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test Email';
    $mail->Body = '<b>This is a test email.</b>';
    $mail->AltBody = 'This is a test email.';

    $mail->send();
    echo '✅ Email sent successfully!';
} catch (Exception $e) {
    echo "❌ Email failed. Error: {$mail->ErrorInfo}";
}
