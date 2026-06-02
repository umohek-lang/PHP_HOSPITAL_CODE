<?php
require '../db.php';

$table = $_GET['table'] ?? '';
$order_id = $_GET['order_id'] ?? '';

if ($table && $order_id) {
    $stmt = $pdo->prepare("UPDATE $table SET is_paid = 1 WHERE id = ?");
    $stmt->execute([$order_id]);

    header("Location: view_bill.php?success=1"); // Redirect back
    exit;
} else {
    echo "Invalid data";
}
?>
