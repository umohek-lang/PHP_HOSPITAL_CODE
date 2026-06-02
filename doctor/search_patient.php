<?php
require '../db.php';

$q = $_GET['q'] ?? '';

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients 
                      WHERE full_name LIKE ? OR patient_id LIKE ? 
                      ORDER BY full_name ASC LIMIT 10");
$stmt->execute(["%$q%", "%$q%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
