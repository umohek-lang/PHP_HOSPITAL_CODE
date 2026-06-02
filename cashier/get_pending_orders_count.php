<?php
require 'db.php'; // adjust path as needed

// Total unpaid orders from all order tables
$labStmt = $pdo->query("SELECT COUNT(*) FROM lab_order WHERE is_paid = 0");
$nursingStmt = $pdo->query("SELECT COUNT(*) FROM nursing_orders WHERE is_paid = 0");
$pharmacyStmt = $pdo->query("SELECT COUNT(*) FROM pharmacy_orders WHERE is_paid = 0");

$labCount = $labStmt->fetchColumn();
$nursingCount = $nursingStmt->fetchColumn();
$pharmacyCount = $pharmacyStmt->fetchColumn();

$totalUnpaid = $labCount + $nursingCount + $pharmacyCount;

echo $totalUnpaid;
