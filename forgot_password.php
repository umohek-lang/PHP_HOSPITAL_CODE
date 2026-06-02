<?php
require 'db.php'; // Your PDO connection

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$email, $token, $expires_at]);

        $resetLink = "http://angelora.com.ng/ANGELORA/reset_password.php?token=$token";

        $success = "Reset link sent successfully!<br><a href='$resetLink' target='_blank'>$resetLink</a>";
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .forgot-container {
            background: white;
            padding: 2rem 2.5rem;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            animation: fadeIn 0.7s ease;
            width: 100%;
            max-width: 450px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .feedback {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h4 class="mb-4 text-center">🔐 Forgot Password</h4>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" id="forgotForm" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Registered Email Address</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="Enter email">
                <div class="invalid-feedback">Please enter a valid email.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
    </div>

    <script>
        // Client-side validation
        const form = document.getElementById('forgotForm');
        const emailInput = document.getElementById('email');

        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                emailInput.classList.add('is-invalid');
            } else {
                emailInput.classList.remove('is-invalid');
            }
        });

        emailInput.addEventListener('input', () => {
            emailInput.classList.remove('is-invalid');
        });
    </script>
</body>
</html>
