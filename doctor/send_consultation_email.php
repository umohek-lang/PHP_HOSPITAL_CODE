<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../includes/auth.php';
require '../db.php';
require '../tcpdf/tcpdf.php';
require_once __DIR__ . '/../vendor/autoload.php'; // PHPMailer

// Ensure doctor is logged in
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit();
}

// ✅ Accept GET request with patient_id
if (!isset($_GET['patient_id'])) {
    die("❌ Patient ID not provided.");
}

$patient_id = $_GET['patient_id'];

// ✅ Get patient details
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient || empty($patient['email']) || !filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {
    die("❌ Valid patient record with email not found.");
}

// ✅ Get latest consultation for the patient
$stmt = $pdo->prepare("SELECT * FROM consultations WHERE patient_id = ? ORDER BY consultation_date DESC LIMIT 1");
$stmt->execute([$patient_id]);
$consult = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consult) {
    die("❌ No consultation record found.");
}

$doctor_name = $consult['doctor_signature'] ?? 'Doctor';
$date = $consult['consultation_date'] ?? date('Y-m-d');

// ✅ Generate PDF with TCPDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);
$pdf->Write(0, "🧑‍⚕️ Consultation Report\n\n");

$fields = [
    'Temperature (°C)' => $consult['temperature'],
    'Pulse Rate' => $consult['pulse'],
    'Respiratory Rate' => $consult['respiratory_rate'],
    'Blood Pressure' => $consult['bp'],
    'Oxygen Saturation' => $consult['oxygen_saturation'],
    'Pain Level' => $consult['pain_level'],
    'Height (cm)' => $consult['height_cm'],
    'Weight (kg)' => $consult['weight_kg'],
    'BMI' => $consult['bmi'],
    'Blood Sugar' => $consult['blood_sugar'],
    'Consciousness Level' => $consult['consciousness_level'],
    'Time Vitals Taken' => $consult['vitals_time'],
    'Symptoms / Notes' => $consult['symptoms_notes'],
    'Chief Complaint & History' => $consult['chief_complaint'],
    'Physical Examination' => $consult['physical_exam'],
    'Diagnosis' => $consult['diagnosis'],
    'Investigations' => $consult['investigations'],
    'Treatment Plan / Prescription' => $consult['treatment_plan'],
    "Doctor's Name / Signature" => $doctor_name,
    'Consultation Date' => $date
];

foreach ($fields as $label => $value) {
    $pdf->Write(0, "$label: $value\n\n");
}

$pdfPath = __DIR__ . '/../uploads/consultation_report_' . time() . '.pdf';
$pdf->Output($pdfPath, 'F');

// ✅ Send Email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'mail.excellentgrade.ng';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@excellentgrade.ng';
    $mail->Password = 'ExcellentGradeInternationalSchool@12';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('noreply@excellentgrade.ng', 'Excellent Grade Medical Lab');
    $mail->addAddress($patient['email'], $patient['full_name']);
    $mail->addReplyTo('info@excellentgrade.ng', 'Lab Info');

    // Embed logo if exists
    $logoPath = __DIR__ . '/logo.png';
    if (file_exists($logoPath)) {
        $mail->addEmbeddedImage($logoPath, 'logo_cid');
    }

    $mail->isHTML(true);
    $mail->Subject = 'Your Consultation Report';
    $mail->Body = "
        <html><body>
        <img src='cid:logo_cid' width='120'><br>
        <p>Dear {$patient['full_name']},<br>
        Your consultation report from Dr. {$doctor_name} dated {$date} is attached.<br><br>
        Regards,<br>Excellent Grade Medical</p>
        </body></html>";
    $mail->AltBody = "Dear {$patient['full_name']}, your consultation report is attached.";
    $mail->addAttachment($pdfPath);

    $mail->send();
    unlink($pdfPath);

    echo "<div style='text-align:center;margin-top:100px'>
            ✅ Email sent to <strong>{$patient['email']}</strong><br><br>
            <a href='consultation_list.php' class='btn btn-success'>Return to Consultation List</a>
          </div>";
} catch (Exception $e) {
    echo "❌ Failed to send email: " . $mail->ErrorInfo;
}
?>
