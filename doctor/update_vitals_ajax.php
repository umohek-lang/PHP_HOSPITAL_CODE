<?php
require '../db.php';
session_start();

// Only doctors allowed
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 2) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$vital_id = $_POST['vital_id'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? null;

$allowed = [
    "blood_pressure",
    "pulse_rate",
    "temperature",
    "respiration_rate",
    "oxygen_saturation",
    "pain_level",
    "height_cm",
    "weight_kg",
    "blood_sugar",
    "symptoms_notes"
];

if (!$vital_id || !in_array($field, $allowed)) {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

$stmt = $pdo->prepare("UPDATE vital_signs SET $field = ? WHERE vital_id = ?");
$ok = $stmt->execute([$value, $vital_id]);

echo json_encode(["status" => $ok ? "success" : "error"]);
