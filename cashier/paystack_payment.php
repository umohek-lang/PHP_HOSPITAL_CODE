<?php
session_start();
include '../db.php';

// Paystack Secret Key
$paystack_secret_key = "sk_test_xxxxxxxxxxxxxxxxx"; // Replace with your secret key

// Get POST data
$invoice_no_param = $_POST['invoice_no'] ?? null; // comma-separated invoice numbers
$patient_id = $_POST['patient_id'] ?? null;
$amount = $_POST['amount'] ?? null; // amount in kobo

if (!$invoice_no_param || !$patient_id || !$amount) {
    die("Invalid payment request.");
}

// Split invoices
$invoice_numbers = explode(',', $invoice_no_param);

// Fetch patient details
$stmt = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id=?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();
if (!$patient) {
    die("Invalid patient.");
}

// Use real patient email if available
$email = strtolower(str_replace(' ', '', $patient['full_name'])) . "@example.com";

// Create a unique reference for this batch transaction
$reference = 'INV_BATCH_' . implode('_', $invoice_numbers) . '_' . time();

// Optional: Store the invoice numbers in metadata
$metadata = [
    'patient_id' => $patient_id,
    'invoices' => $invoice_numbers
];

// Initialize Paystack transaction
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode([
        'email' => $email,
        'amount' => $amount,
        'currency' => 'NGN',
        'reference' => $reference,
        'metadata' => $metadata,
        'callback_url' => "https://yourdomain.com/paystack_callback.php"
    ]),
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

if (isset($result['status']) && $result['status'] == true) {
    $authorization_url = $result['data']['authorization_url'];
    // Redirect to Paystack payment page
    header("Location: $authorization_url");
    exit();
} else {
    echo "Payment initialization failed: " . ($result['message'] ?? 'Unknown error');
}
