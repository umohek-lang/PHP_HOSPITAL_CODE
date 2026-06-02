<?php
require 'db.php';

$feedback = '';
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = $_POST['role_id'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['email'] === $email) {
            $feedback .= '<div class="alert alert-danger">Email is already registered.</div>';
        }
        if ($existing['phone'] === $phone) {
            $feedback .= '<div class="alert alert-danger">Phone number is already registered.</div>';
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$role_id, $full_name, $email, $phone, $password])) {
            $redirect = true;
        } else {
            $feedback = '<div class="alert alert-danger">Registration failed. Please try again.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .animated-card {
            opacity: 0;
            transform: translateY(-30px);
            animation: slideFadeIn 0.8s ease-out forwards;
        }

        @keyframes slideFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-control, .form-select {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            transform: scale(1.02);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .btn-success {
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.5);
        }

        .btn-success:active {
            transform: scale(0.95);
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
        }

        .btn-loading .spinner-border {
            display: inline-block;
        }

        .btn-loading .btn-text {
            display: none;
        }
    </style>
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

    <div class="col-md-5">
        <div class="card shadow-sm rounded-4 animated-card">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">User Registration</h3>

                <form method="post" class="fade-in" id="registerForm">
                    <div class="mb-3">
                        <input name="full_name" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="mb-3">
                        <input name="email" type="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input name="phone" class="form-control" placeholder="Phone" required>
                    </div>
                    <div class="mb-3">
                        <input name="password" type="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="mb-3">
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="1">Admin</option>
                            <option value="2">Doctor</option>
                            <option value="3">Nurse</option>
                            <option value="4">Cashier</option>
                            <option value="5">Pharmacist</option>
                            <option value="6">Lab Technician</option>
                            <option value="7">Patient</option>
                            <option value="8">Receptionist</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100" id="submitBtn">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Register</span>
                    </button>

<a href="#" class="btn btn-danger w-100 mt-3 btn-google">
    <i class="bi bi-google"></i> Register with Google
</a>

                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Error -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Registration Error</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


<!-- selecting users based on role  -->


<script>
    document.querySelector('.btn-google').addEventListener('click', function (e) {
        e.preventDefault();
        const role = document.querySelector('select[name="role_id"]').value;

        if (!role) {
            alert("Please select a role before registering with Google.");
            return;
        }

        fetch('save_role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'role_id=' + encodeURIComponent(role)
        }).then(() => {
            window.location.href = 'google_login.php';
        });
    });
</script>




    <!-- Redirect Script on Success -->
    <?php if ($redirect): ?>
        <script>
            setTimeout(() => {
                window.location.href = "registration_success.php";
            }, 1000);
        </script>
    <?php endif; ?>

    <!-- Error Modal Trigger Script -->
    <?php if ($feedback): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const errorMessage = <?= json_encode($feedback) ?>;
                document.getElementById('modalBody').innerHTML = errorMessage;
                const myModal = new bootstrap.Modal(document.getElementById('errorModal'));
                myModal.show();
            });
        </script>
    <?php endif; ?>

    <!-- Bootstrap JS and Spinner -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('btn-loading');
            btn.querySelector('.spinner-border').classList.remove('d-none');
        });
    </script>
</body>
</html>
