<?php
require '../db.php';

$field = $_GET['field'] ?? '';
$q = $_GET['q'] ?? '';
$limit = 10;

$allowed_fields = ['drug_name', 'dosage', 'route', 'frequency', 'duration', 'prescribed_by'];

if (!in_array($field, $allowed_fields)) {
    echo json_encode([]);
    exit;
}

// Sanitize field (use prepared statement, column name directly injected only from trusted list)
$stmt = $pdo->prepare("SELECT DISTINCT `$field` AS value 
                       FROM drug_chart 
                       WHERE `$field` LIKE ? 
                       ORDER BY `$field` ASC 
                       LIMIT $limit");
$stmt->execute(["%$q%"]);
$data = $stmt->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: application/json');
echo json_encode($data);
