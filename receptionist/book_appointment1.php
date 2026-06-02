<?php
require '../db.php';
session_start();

// ---------- DEBUG (optional) ----------
// echo '<pre>';
// print_r($_SESSION['user']);
// echo '</pre>';
// exit();

// ---------- Role Access Check ----------
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("Location: ../login.php");
    exit();
}

$userRole = (int)$_SESSION['user']['role_id']; // cast to int for safety

if (!in_array($userRole, [8, 3])) { // 8 = receptionist, 3 = nurse
    $_SESSION['error'] = "You do not have permission to access this page.";
    header("Location: ../login.php");
    exit();
}

// ---------- Initialize ----------
$error = "";
$success = "";

// ---------- Handle Appointment Submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? null;
    $doctor_id = $_POST['doctor_id'] ?? null;
    $appointment_date = $_POST['appointment_date'] ?? null;
    $appointment_time = $_POST['appointment_time'] ?? null;
    $status = $_POST['status'] ?? 'Pending';

    if ($patient_id && $doctor_id && $appointment_date && $appointment_time) {
        $stmt = $pdo->prepare("
            INSERT INTO appointments 
                (patient_id, doctor_id, appointment_date, appointment_time, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        if ($stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $status])) {
            $success = "Appointment created successfully.";
        } else {
            $error = "Failed to create appointment.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// ---------- Fetch Patients ----------
$patients_stmt = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC");
$patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- Fetch Doctors ----------
$doctors_stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role_id = ?");
$doctors_stmt->execute([2]); // role_id=2 for doctors
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-primary">Book New Appointment</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-success text-white">Appointment Form</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= htmlspecialchars($patient['patient_id']) ?>">
                                <?= htmlspecialchars($patient['full_name']) ?> (ID: <?= htmlspecialchars($patient['patient_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">-- Select Doctor --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= htmlspecialchars($doctor['user_id']) ?>">
                                <?= htmlspecialchars($doctor['full_name']) ?> (ID: <?= htmlspecialchars($doctor['user_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Appointment Date</label>
                    <input type="date" name="appointment_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Appointment Time</label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">Create Appointment</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
