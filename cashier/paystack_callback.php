<?php
session_start();
include '../db.php';

// Paystack Secret Key
$paystack_secret_key = "sk_test_xxxxxxxxxxxxxxxxx"; // Replace with your secret key

// Get transaction reference from Paystack
$reference = $_GET['reference'] ?? null;
if (!$reference) {
    die("No transaction reference supplied.");
}

// Verify the transaction with Paystack
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $paystack_secret_key",
        "Content-Type: application/json"
    ],
]);
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die("cURL Error: " . $err);
}

$result = json_decode($response, true);

if (!$result['status']) {
    die("Verification failed: " . ($result['message'] ?? 'Unknown error'));
}

// If transaction successful
if ($result['data']['status'] === 'success') {
    $metadata = $result['data']['metadata'] ?? null;

    if ($metadata && isset($metadata['patient_id'], $metadata['invoices'])) {
        $patient_id = $metadata['patient_id'];
        $invoice_numbers = $metadata['invoices'];

        if (is_array($invoice_numbers)) {
            // Mark all invoices as paid
            $placeholders = rtrim(str_repeat('?,', count($invoice_numbers)), ',');
            $stmt = $pdo->prepare("UPDATE hos_bills SET paid=1 WHERE invoice_no IN ($placeholders) AND patient_id=?");
            $params = array_merge($invoice_numbers, [$patient_id]);
            $stmt->execute($params);
        }

        // Redirect back to view_bill for patient
        // header("Location: view_bill.php?patient_id=" . urlencode($patient_id));
        // exit();
        // Redirect back to view_bill for patient with success alert
header("Location: view_bill.php?patient_id=" . urlencode($patient_id) . "&paid=1");
exit();

    } else {
        die("No invoice metadata found.");
    }
} else {
    die("Payment not successful. Status: " . $result['data']['status']);
}
