<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("UPDATE drug_chart SET seen_by_pharmacist = 1 WHERE chart_id = ?");
        $stmt->execute([$chart_id]);
        echo "✅ Marked as seen.";
    } catch (PDOException $e) {
        echo "❌ Failed to mark as seen.";
    }
}
?>
