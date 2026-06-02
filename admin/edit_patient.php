<?php  
require '../includes/auth.php';
require '../db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid patient ID.");
}

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient not found.");
}

$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $age = $_POST['age'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $medical_history = $_POST['medical_history'] ?? '';
    $doctor_id = $_POST['doctor_id'] ?? '';

    $updateStmt = $pdo->prepare("UPDATE patients SET full_name=?, gender=?, age=?, phone=?, email=?, medical_history=?, doctor_id=? WHERE patient_id=?");
    $updateStmt->execute([$full_name, $gender, $age, $phone, $email, $medical_history, $doctor_id, $id]);

    $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Patient</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease-in-out forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1055;
        }
    </style>
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="card shadow-lg fade-in mx-auto" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Edit Patient</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($patient['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" <?= ($patient['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($patient['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Age</label>
                        <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($patient['age'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($patient['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medical History</label>
                        <textarea name="medical_history" class="form-control"><?= htmlspecialchars($patient['medical_history'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Doctor ID</label>
                        <input type="text" name="doctor_id" class="form-control" value="<?= htmlspecialchars($patient['doctor_id'] ?? '') ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success">Update Patient</button>
                         <a href="patients.php" class="btn btn-secondary">Back to Patients</a> <!-- Updated link -->
                        <a href="patients.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    Patient updated successfully!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($success): ?>
    <script>
        const toast = new bootstrap.Toast(document.getElementById('successToast'));
        toast.show();
    </script>
    <?php endif; ?>
</body>
</html>
