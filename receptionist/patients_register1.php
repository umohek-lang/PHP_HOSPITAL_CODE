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
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        exit;
    }

    if (!preg_match('/^(\+234|0)[789][01]\d{8}$/', $phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format.']); exit;
    }

    // ✅ Email optional check
    $query = "SELECT email, phone, full_name FROM patients WHERE (phone = :phone OR full_name = :full_name)";
    $params = [':phone' => $phone, ':full_name' => $full_name];

    if (!empty($email)) {
        $query .= " OR email = :email";
        $params[':email'] = $email;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $takenFields = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['email'] === $email) $takenFields[] = "Email";
        if ($row['phone'] === $phone) $takenFields[] = "Phone number";
        if ($row['full_name'] === $full_name) $takenFields[] = "Full name";
    }
    if (!empty($takenFields)) {
        echo json_encode(['status' => 'error', 'message' => implode(", ", $takenFields) . " already taken."]); exit;
    }

    // ❌ COMMENTED OUT HMO VERIFICATION BLOCK
    /*
    if ($patient_type === 'HMO' && ($_POST['hmo_verified'] ?? '') !== '1') {
        echo json_encode(['status' => 'error', 'message' => 'Please verify HMO code before submitting.']); exit;
    }
    */

    // ✅ Photo upload
    $photoPath = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/";
        $filename = time() . "_" . basename($_FILES["photo"]["name"]);
        $photoPath = $targetDir . $filename;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        $validMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $validMimes)) {
            echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, or GIF files are allowed.']); exit;
        }

        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $photoPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']); exit;
        }
    }

    try {
        $pdo->beginTransaction();

        $role_id = 7;
        $password = password_hash($pin, PASSWORD_BCRYPT);

        $stmt_user = $pdo->prepare("INSERT INTO users (role_id, full_name, email, password, phone, created_at, profile_image) 
                                    VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt_user->execute([$role_id, $full_name, $email ?? null, $password, $phone, $photoPath]);
        $user_id = $pdo->lastInsertId();

        $stmt_patient = $pdo->prepare("INSERT INTO patients 
            (user_id, full_name, gender, dob, age, email, phone, address, patient_pin, photo, patient_type, patient_status, hmo_name, language, registered_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_patient->execute([
            $user_id, $full_name, $gender, $dob, $age, $email, $phone, $address,
            $pin, $photoPath, $patient_type, $patient_status, $hmo_name,
            $language, $registered_by
        ]);

        $pdo->commit();

        echo json_encode(['status' => 'success', 'pin' => $pin, 'patient_id' => $user_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
    exit;
}

$stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role_id = 8");
$userRegister = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Patient</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <div class="card p-4 shadow">
    <h4 class="text-center mb-4">Patient Registration Form</h4>
    <form id="patientForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <input type="hidden" name="hmo_verified" id="hmo_verified" value="0">

      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label">Photo</label>
          <input type="file" name="photo" class="form-control">
        </div>
        <div class="col-md-8">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control" required>
            <option value="">Select</option>
            <option>Male</option>
            <option>Female</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="dob" id="dob" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Age</label>
          <input type="number" name="age" id="age" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Email <small class="text-muted">(optional)</small></label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Language</label>
          <input type="text" name="language" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control" required>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label">Patient Type</label>
          <select name="patient_type" id="patient_type" class="form-control" onchange="toggleHmoField(this.value)" required>
            <option value="">Select</option>
            <option value="Regular">Private</option>
            <option value="HMO">HMO</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Patient Status</label>
          <select name="patient_status" class="form-control" required>
            <option value="">Select</option>
            <option value="Outpatient">Outpatient</option>
            <option value="Inpatient">Inpatient</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Registered By</label>
          <select name="registered_by" class="form-control" required>
            <option value="">Select</option>
            <?php foreach ($userRegister as $user): ?>
              <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4" id="hmo_field" style="display:none;">
          <label class="form-label">HMO Name</label>
          <input type="text" name="hmo_name" class="form-control">
        </div>
      </div>

      <div class="mb-3" id="hmo_code_group" style="display:none;">
        <label class="form-label">HMO Code</label>
        <div class="input-group">
          <input type="text" id="hmo_code" class="form-control">
          <button type="button" class="btn btn-outline-secondary" onclick="verifyHmoCode()">Verify</button>
        </div>
        <div id="hmo_verification_status" class="mt-1"></div>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-primary px-4">Register</button>
      </div>
    </form>
    <div id="response_message" class="mt-3"></div>
  </div>
</div>

<div class="modal fade" id="pinModal" tabindex="-1" aria-labelledby="pinModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="pinModalLabel">Registration Successful</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p><strong>Patient ID:</strong> <span id="modalPatientId"></span></p>
        <p><strong>Registered! PIN:</strong></p>
        <h3 class="text-primary" id="modalPin"></h3>
        <p>Please copy or save this PIN for login and future reference.</p>
      </div>
      <div class="modal-footer">
        <!--<a href="../index.php" class="btn btn-secondary">Return to Home</a>-->
        <a href="patients_register.php" class="btn btn-secondary">Return to Home</a>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('patientForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  fetch('patients_register.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    const msgDiv = document.getElementById('response_message');

    if (data.status === 'success') {
      msgDiv.innerHTML = `<div class='alert alert-success'>Registered! PIN: <strong>${data.pin}</strong></div>`;
      document.getElementById('modalPatientId').textContent = data.patient_id;
      document.getElementById('modalPin').textContent = data.pin;
      const modal = new bootstrap.Modal(document.getElementById('pinModal'));
      modal.show();
      form.reset();
      setTimeout(() => { window.location.href = "../index.php"; }, 300000);
    } else {
      msgDiv.innerHTML = `<div class='alert alert-danger'>${data.message}</div>`;
    }
  })
  .catch(() => {
    document.getElementById('response_message').innerHTML = '<div class="alert alert-danger">Something went wrong.</div>';
  });
});

function toggleHmoField(value) {
  document.getElementById('hmo_field').style.display = value === 'HMO' ? 'block' : 'none';
  document.getElementById('hmo_code_group').style.display = value === 'HMO' ? 'block' : 'none';
}

function verifyHmoCode() {
  const code = document.getElementById('hmo_code').value;
  const status = document.getElementById('hmo_verification_status');
  if (!code) {
    status.innerHTML = '<span class="text-danger">Enter HMO code.</span>';
    return;
  }
  status.innerHTML = 'Verifying...';
  fetch('verify_hmo_api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ hmo_code: code })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      status.innerHTML = `<span class='text-success'>${data.hmo_name} Verified</span>`;
      document.getElementById('hmo_verified').value = '1';
    } else {
      status.innerHTML = `<span class='text-danger'>${data.message}</span>`;
      document.getElementById('hmo_verified').value = '0';
    }
  });
}

document.getElementById('dob').addEventListener('change', function () {
  const dob = new Date(this.value);
  const today = new Date();
  let age = today.getFullYear() - dob.getFullYear();
  const m = today.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
  document.getElementById('age').value = isNaN(age) ? '' : age;
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
