<?php
require '../db.php';
session_start();

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role_id'], [1, 2])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sql = "SELECT user_id, full_name, email, role_id, login_time, logout_time, status, login_state, duration, ip_address, user_agent
        FROM login_activity
        ORDER BY login_time DESC
        LIMIT 10"; // You can increase the limit if needed

$stmt = $pdo->prepare($sql);
$stmt->execute();
$logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($logins);
exit;
