<?php
include '../db.php';

$invoice_no = $_GET['invoice_no'] ?? null;
$patient_id = $_GET['patient_id'] ?? null;

if ($invoice_no && $patient_id) {
    $stmt = $pdo->prepare("UPDATE hos_bills SET paid=1 WHERE invoice_no=?");
    $stmt->execute([$invoice_no]);

    // Redirect back to view_bill.php with both parameters
    header("Location: view_bill.php?patient_id=$patient_id&invoice_no=$invoice_no");
    exit();
} else {
    die("Invoice or patient not selected.");
}

