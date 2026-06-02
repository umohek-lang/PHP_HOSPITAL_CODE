<?php
header('Content-Type: application/json');

// Read and decode the JSON input
$data = json_decode(file_get_contents("php://input"), true);
$hmoCode = trim($data['hmo_code'] ?? '');

if (empty($hmoCode)) {
    echo json_encode(['status' => 'error', 'message' => 'No HMO code provided']);
    exit;
}

// 🔧 Replace this mock logic with real API integration
$validCodes = [
    'ABC123' => 'ABC Health',
    'XYZ456' => 'XYZ HMO Limited',
    'MED789' => 'Medicare Services'
];


// actual HMO PROVIDER

// $response = file_get_contents("https://api.hmo-provider.com/verify?code=" . urlencode($hmoCode));
// $data = json_decode($response, true);
// Process the real response accordingly


if (array_key_exists($hmoCode, $validCodes)) {
    echo json_encode(['status' => 'success', 'hmo_name' => $validCodes[$hmoCode]]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid HMO code']);
}
