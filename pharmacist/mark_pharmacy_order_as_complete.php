<?php
require '../includes/auth.php';
require '../db.php';

// Ensure only pharmacist can access
checkRole(5); // Role 5 = Pharmacist

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    $stmt = $pdo->prepare("UPDATE pharmacy_orders SET status = 'completed' WHERE id = ?");
    $stmt->execute([$order_id]);

    // Redirect back to the orders page
    header('Location: dashboard.php'); // adjust path if needed
    exit();
}
?>
