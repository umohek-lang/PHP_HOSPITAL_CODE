<?php
require '../db.php';
session_start();

// Only allow Admin and Doctor
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 2])) {
    header("Location: ../login.php");
    exit;
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $login_time = $_POST['login_time'] ?? null;

    if ($user_id && $login_time) {
        $stmt = $pdo->prepare("DELETE FROM login_activity WHERE user_id = ? AND login_time = ?");
        $stmt->execute([$user_id, $login_time]);
    }
}

// Redirect back
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
