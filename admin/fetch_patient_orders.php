<?php
include "db.php";
require '../db.php';

if (!isset($_GET['patient_id'])) {
    echo json_encode(["error" => "Patient ID missing"]);
    exit;
}

$patient_id = $_GET['patient_id'];

$lab = $pdo->prepare("SELECT * FROM lab_orders WHERE patient_id = ?");
$lab->execute([$patient_id]);
$labOrders = $lab->fetchAll(PDO::FETCH_ASSOC);

$nursing = $pdo->prepare("SELECT * FROM nursing_orders WHERE patient_id = ?");
$nursing->execute([$patient_id]);
$nursingOrders = $nursing->fetchAll(PDO::FETCH_ASSOC);

$pharmacy = $pdo->prepare("SELECT * FROM pharmacy_orders WHERE patient_id = ?");
$pharmacy->execute([$patient_id]);
$pharmacyOrders = $pharmacy->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "lab_orders" => $labOrders,
    "nursing_orders" => $nursingOrders,
    "pharmacy_orders" => $pharmacyOrders
]);
?>