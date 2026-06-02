<?php
require 'db.php';
session_start();

// ✅ Get user ID before clearing session
$user_id = $_SESSION['user']['user_id'] ?? null;

if ($user_id) {
    // Get latest login record with NULL logout
    $stmt = $pdo->prepare("SELECT id, login_time FROM login_activity 
                           WHERE user_id = ? AND logout_time IS NULL 
                           ORDER BY login_time DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $record = $stmt->fetch();

    if ($record) {
        $logout_time = date('Y-m-d H:i:s');
        $login_time = $record['login_time'];

        $login = new DateTime($login_time);
        $logout = new DateTime($logout_time);
        $interval = $login->diff($logout);
        $duration = $interval->format('%h hrs %i mins');

        // ✅ Corrected parameter order
        $update = $pdo->prepare("UPDATE login_activity 
                                 SET logout_time = NOW(), login_state = 'Offline', duration = ? 
                                 WHERE id = ?");
        $update->execute([$duration, $record['id']]);
    }
}

// ✅ Clear session and redirect
session_unset();
session_destroy();
header("Location: login.php");
exit;
