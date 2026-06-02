<?php
require '../includes/auth.php';
require '../db.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM pharmacy_inventory WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: pharmacy_inventory.php"); // redirect back to inventory list
    exit;
} else {
    echo "Invalid item ID.";
}
?>
