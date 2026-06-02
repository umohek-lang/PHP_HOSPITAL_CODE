<?php
require '../includes/auth.php';
require '../db.php';

$patient_id = $_GET['patient_id'] ?? null;
$last_id = $_GET['last_id'] ?? 0;

if (!$patient_id) {
    echo json_encode(['html' => '', 'latest_id' => $last_id]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT dm.*, m.medicine_name 
    FROM dispensed_medicines dm 
    JOIN medicines m ON dm.medicine_id = m.medicine_id 
    WHERE dm.patient_id = ? AND dm.id > ?
    ORDER BY dm.id ASC
");
$stmt->execute([$patient_id, $last_id]);
$rows = $stmt->fetchAll();

$html = '';
$new_last_id = $last_id;

foreach ($rows as $row) {
    $html .= "<tr>
        <td>" . htmlspecialchars($row['medicine_name']) . "</td>
        <td>" . htmlspecialchars($row['quantity']) . "</td>
        <td>" . htmlspecialchars($row['prescribed_by']) . "</td>
        <td>" . htmlspecialchars($row['dispensed_by']) . "</td>
        <td>" . htmlspecialchars($row['notes']) . "</td>
    </tr>";
    $new_last_id = $row['id'];
}

echo json_encode([
    'html' => $html,
    'latest_id' => $new_last_id,
    'new_count' => count($rows)
]);
