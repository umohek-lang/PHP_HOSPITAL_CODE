<?php
// require '../db.php';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $lab_test_id = $_POST['lab_test_id'];
//     $result = $_POST['result'];

//     // Handle file upload
//     if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === 0) {
//         $fileTmp = $_FILES['report_file']['tmp_name'];
//         $fileName = time() . '_' . basename($_FILES['report_file']['name']);
//         $targetDir = '../uploads/reports/';
//         $targetFile = $targetDir . $fileName;

//         // Make sure uploads directory exists
//         if (!file_exists($targetDir)) {
//             mkdir($targetDir, 0777, true);
//         }

//         if (move_uploaded_file($fileTmp, $targetFile)) {
//             // Update the lab test
//             $stmt = $pdo->prepare("
//                 UPDATE lab_tests 
//                 SET result = ?, report_file = ?, status = 'completed' 
//                 WHERE lab_test_id = ?
//             ");
//             $stmt->execute([$result, $fileName, $lab_test_id]);

//             header("Location: test.php?success=1");
//             exit;
//         } else {
//             echo "❌ Failed to upload file.";
//         }
//     } else {
//         echo "❌ Invalid file or no file uploaded.";
//     }
// }



require '../db.php';
require '../vendor/autoload.php'; // Include Composer autoloader for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab_test_id = $_POST['lab_test_id'];
    $result = $_POST['result'];

    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === 0) {
        $fileTmp = $_FILES['report_file']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['report_file']['name']);
        $targetDir = '../uploads/reports/';
        $targetFile = $targetDir . $fileName;

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($fileTmp, $targetFile)) {
            // Update DB
            $stmt = $pdo->prepare("
                UPDATE lab_tests 
                SET result = ?, report_file = ?, status = 'completed' 
                WHERE lab_test_id = ?
            ");
            $stmt->execute([$result, $fileName, $lab_test_id]);

            // 🔍 Fetch patient details
            $patientStmt = $pdo->prepare("
                SELECT p.email, p.full_name, l.test_name 
                FROM lab_tests l
                JOIN patients p ON l.patient_id = p.patient_id
                WHERE l.lab_test_id = ?
            ");
            $patientStmt->execute([$lab_test_id]);
            $patient = $patientStmt->fetch();

            if ($patient && !empty($patient['email'])) {
                // ✅ Send Email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.yourdomain.com'; // e.g., smtp.gmail.com or smtp.truehost.com.ng
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'your_email@yourdomain.com';
                    $mail->Password   = 'your_email_password';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('your_email@yourdomain.com', 'Hospital Lab');
                    $mail->addAddress($patient['email'], $patient['full_name']);
                    $mail->addAttachment($targetFile);

                    $mail->isHTML(true);
                    $mail->Subject = 'Your Lab Test Result';
                    $mail->Body    = "
                        <p>Dear {$patient['full_name']},</p>
                        <p>Your test <strong>{$patient['test_name']}</strong> has been completed.</p>
                        <p><strong>Result:</strong> {$result}</p>
                        <p>You may also view the attached report file.</p>
                        <p>Regards,<br>Hospital Lab</p>
                    ";

                    $mail->send();
                    // Optionally log email success
                } catch (Exception $e) {
                    error_log("Email Error: {$mail->ErrorInfo}");
                }
            }

            // Redirect
            header("Location: test.php?success=1");
            exit;
        } else {
            echo "❌ Failed to upload file.";
        }
    } else {
        echo "❌ Invalid file or no file uploaded.";
    }
}
