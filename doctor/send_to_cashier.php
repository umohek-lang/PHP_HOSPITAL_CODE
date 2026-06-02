<?php
require '../db.php';
require '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

$id   = $_POST['id']   ?? null;
$type = $_POST['type'] ?? null;

$tableMap = [
    'lab'      => 'lab_orders',
    'nursing'  => 'nursing_orders',
    'pharmacy' => 'pharmacy_orders'
];

if (!$id || !isset($tableMap[$type])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid parameters'
    ]);
    exit;
}

$table = $tableMap[$type];

$stmt = $pdo->prepare(
    "UPDATE {$table} SET is_sent_to_cashier = 1 WHERE id = ?"
);

if ($stmt->execute([$id])) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Sent to cashier'
    ]);
    exit;
}

echo json_encode([
    'status' => 'error',
    'message' => 'Database update failed'
]);
