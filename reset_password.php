<?php
require 'db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row && strtotime($row['expires_at']) > time()) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")
            ->execute([$hashedPassword, $row['email']]);

        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$row['email']]);

        $success = "Password has been reset. You can now <a href='login.php' class='text-success'>login</a>.";
    } else {
        $error = "Invalid or expired reset link.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .fade-in {
            animation: fadeIn 1.2s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        #strengthBar {
            height: 5px;
        }
    </style>
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card shadow p-4 fade-in" style="min-width: 400px;">
    <h4 class="mb-3 text-primary text-center">Reset Your Password</h4>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($token): ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Enter new password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">Show</button>
                </div>
                <div class="invalid-feedback">Please enter a new password.</div>
                <div class="mt-2">
                    <div class="progress">
                        <div id="strengthBar" class="progress-bar" role="progressbar"></div>
                    </div>
                    <small id="strengthText" class="form-text text-muted"></small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
        </form>

        <script>
            // Toggle show/hide password
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');

            togglePassword.addEventListener('click', function () {
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                this.textContent = type === 'password' ? 'Show' : 'Hide';
            });

            // Bootstrap validation
            (function () {
                'use strict';
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();

            // Password strength checker
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');

            passwordField.addEventListener('input', function () {
                const value = passwordField.value;
                let strength = 0;

                if (value.length >= 8) strength += 1;
                if (/[A-Z]/.test(value)) strength += 1;
                if (/[a-z]/.test(value)) strength += 1;
                if (/\d/.test(value)) strength += 1;
                if (/[\W]/.test(value)) strength += 1;

                const width = strength * 20;
                strengthBar.style.width = width + '%';

                let barClass = '';
                let text = '';
                switch (strength) {
                    case 0:
                    case 1:
                        barClass = 'bg-danger';
                        text = 'Very Weak';
                        break;
                    case 2:
                        barClass = 'bg-warning';
                        text = 'Weak';
                        break;
                    case 3:
                        barClass = 'bg-info';
                        text = 'Moderate';
                        break;
                    case 4:
                        barClass = 'bg-primary';
                        text = 'Strong';
                        break;
                    case 5:
                        barClass = 'bg-success';
                        text = 'Very Strong';
                        break;
                }

                strengthBar.className = `progress-bar ${barClass}`;
                strengthText.textContent = text;
            });
        </script>

    <?php else: ?>
        <div class="alert alert-danger">Invalid or expired reset link.</div>
    <?php endif; ?>
</div>

</body>
</html>
