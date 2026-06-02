<?php  
require 'db.php';
session_start();

if (!isset($pdo)) {
    die("PDO not set. Check db.php connection.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            // ✅ Log successful login
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $login_time = date('Y-m-d H:i:s');

            $log = $pdo->prepare("
                INSERT INTO login_activity (user_id, full_name, role_id, email, login_time, status, login_state, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, 'success', 'Online', ?, ?)
            ");
            $log->execute([
                $user['user_id'],
                $user['full_name'],
                $user['role_id'],
                $user['email'],
                $login_time,
                $ip,
                $user_agent
            ]);

            // ✅ Also record in activities table
            $stmtAct = $pdo->prepare("INSERT INTO activities (user_id, role_id, action, created_at) 
                                      VALUES (?, ?, ?, NOW())");
            $stmtAct->execute([$user['user_id'], $user['role_id'], "Logged in"]);

            // Start session
            $_SESSION['user'] = [
                'user_id'   => $user['user_id'],
                'role_id'   => $user['role_id'],
                'full_name' => $user['full_name'],
                'shift'     => $user['shift']
            ];

            $_SESSION['login_success'] = "Welcome, " . htmlspecialchars($user['full_name']);

            // Role-based redirect
            switch ($user['role_id']) {
                case 1: header('Location: admin/dashboard.php'); break;
                case 2:
                    $doctor_id = $user['user_id'];
                    $today = date('Y-m-d');

                    // Auto-confirm and complete appointments
                    $stmtConfirm = $pdo->prepare("UPDATE appointments 
                        SET status = 'Confirmed' 
                        WHERE doctor_id = ? AND appointment_date = ? AND status = 'Pending'");
                    $stmtConfirm->execute([$doctor_id, $today]);

                    $stmtComplete = $pdo->prepare("UPDATE appointments 
                        SET status = 'Complete' 
                        WHERE doctor_id = ? AND appointment_date < ? AND status != 'Complete'");
                    $stmtComplete->execute([$doctor_id, $today]);

                    header('Location: doctor/dashboard.php');
                    break;
                case 3: header('Location: nurse/dashboard.php'); break;
                case 4: header('Location: cashier/dashboard.php'); break;
                case 5: header('Location: pharmacist/dashboard.php'); break;
                case 6: header('Location: lab/dashboard.php'); break;
                case 7: header('Location: patient/dashboard.php'); break;
                case 8: header('Location: receptionist/dashboard.php'); break;
                case 9: header('Location: MD/md_dashboard.php'); break;
                case 10: header('Location: radiology/dashboard.php'); break;
                case 11: header('Location: cleaner/dashboard.php'); break;
                default:
                    $error = "Unauthorized role.";
                    break;
            }
            exit;
        } else {
            // ✅ Log failed login
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            $stmtFail = $pdo->prepare("INSERT INTO activities (user_id, role_id, action, created_at) 
                                       VALUES (?, ?, ?, NOW())");
            $stmtFail->execute([
                $user['user_id'] ?? null,
                $user['role_id'] ?? null,
                "Failed login attempt with email: {$email}"
            ]);

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
    <title>Login | Hospital Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5; /* Light background for the full page */
        }

        .bg-hospital {
            background: url('ABLEHANDS/assets/images/bg-hospital.jpg') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 40px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.6);
        }

        .logo {
            max-width: 180px;
            margin-bottom: 20px;
        }

        .card-animate {
            opacity: 0;
            transform: translateY(50px);
            animation: fade-slide-in 1s ease forwards;
        }

        @keyframes fade-slide-in {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-text {
            opacity: 0;
            transform: translateY(-20px);
            animation: fadeInUp 1s ease-out forwards 0.3s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-success {
            transition: transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .btn-success:hover {
            transform: scale(1.02);
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.4);
        }

        .btn-success:active {
            transform: scale(0.98);
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
            margin-left: 8px;
            display: none;
        }

        .btn-loading .spinner-border {
            display: inline-block;
        }

        .bg-login {
            background-color: #ffffff;
            display: flex;
            justify-content: center;  /* horizontal center */
            align-items: center;      /* vertical center */
            padding: 20px;
            min-height: 100vh;        /* full viewport height */
        }

        .login-card {
            margin-top: 40px;
            margin-bottom: 40px;
            max-width: 400px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .bg-hospital {
                text-align: center;
                padding: 20px;
            }

            .bg-login {
                padding: 20px;
                min-height: auto;
            }

            .login-card {
                margin-top: 20px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid min-vh-100">
    <div class="row h-100">
        <!-- Left: Hospital Logo & Background -->
        <div class="col-md-7 d-flex flex-column justify-content-center align-items-center bg-hospital text-center">
            <img src="images/patient.jpg" alt="Hospital Logo" class="logo img-fluid w-1200">

            <h1 class="display-5 fw-bold text-primary">Angelora  HOSPITAL</h1>
            <p class="fs-4 text-primary">Always in good hands.</p>
        </div>

        <!-- Right: Login Form -->
        <div class="col-md-5 bg-login">
            <div class="card shadow-sm card-animate login-card">
                <div class="card-body p-4">
                    <h3 class="text-center text-primary fw-bold fade-in-text">Login Based on Your Role</h3>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" id="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" id="password" required>
                        </div>

                        <button id="loginBtn" type="submit" class="btn btn-success w-100">
                            <span>Login</span>
                            <div class="spinner-border spinner-border-sm text-light" role="status"></div>
                        </button>

                        <div class="mt-3 text-center">
                            <!-- <h5 class="text-primary fade-in-text">Don't have an account?</h5>
                            <a href="register.php" class="btn btn-sm btn-primary mt-2">Register Now</a> -->
                        </div>

                        <div class="mt-2 text-center">
                            <h5 class="text-primary fade-in-text"></h5>
                            <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Spinner script -->
<script>
    document.getElementById("loginForm").addEventListener("submit", function () {
        const btn = document.getElementById("loginBtn");
        btn.classList.add("btn-loading");
        btn.disabled = true;
    });
</script>

</body>
</html>

