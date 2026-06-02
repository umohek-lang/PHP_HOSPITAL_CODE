<?php
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chart_id'])) {
    $chart_id = $_POST['chart_id'];
    $stmt = $pdo->prepare("UPDATE drug_chart SET status = 'prescribed' WHERE chart_id = ?");
    $success = $stmt->execute([$chart_id]);
    echo json_encode(['success' => $success]);
}
?>
