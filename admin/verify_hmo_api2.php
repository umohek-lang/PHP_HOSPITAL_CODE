<?php
require '../db.php';

header('Content-Type: application/json');

// Read and decode the JSON input
$data = json_decode(file_get_contents("php://input"), true);
$hmoCode = trim($data['hmo_code'] ?? '');
$hmoName = trim($data['hmo_name'] ?? '');

if (empty($hmoCode) && empty($hmoName)) {
    echo json_encode(['status' => 'error', 'message' => 'HMO code or name must be provided.']);
    exit;
}

// If both code and name are provided, check if they match together
if (!empty($hmoCode) && !empty($hmoName)) {
    $stmt = $pdo->prepare("SELECT * FROM hmos WHERE hmo_code = ? AND LOWER(hmo_name) = LOWER(?)");
    $stmt->execute([$hmoCode, $hmoName]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($match) {
        echo json_encode([
            'status' => 'success',
            'message' => 'HMO code and name match.',
            'hmo_name' => $match['hmo_name'],
            'hmo_code' => $match['hmo_code'],
            'country' => $match['country'] ?? 'N/A'
        ]);
        exit;
    }
}

// Try code verification only
if (!empty($hmoCode)) {
    $stmt = $pdo->prepare("SELECT * FROM hmos WHERE hmo_code = ?");
    $stmt->execute([$hmoCode]);
    $hmo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hmo) {
        echo json_encode([
            'status' => 'success',
            'message' => 'HMO code verified.',
            'hmo_name' => $hmo['hmo_name'],
            'hmo_code' => $hmo['hmo_code'],
            'country' => $hmo['country'] ?? 'N/A'
        ]);
        exit;
    }
}

// Try name verification only
if (!empty($hmoName)) {
    $stmt = $pdo->prepare("SELECT * FROM hmos WHERE LOWER(hmo_name) = LOWER(?)");
    $stmt->execute([$hmoName]);
    $hmoByName = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hmoByName) {
        echo json_encode([
            'status' => 'partial',
            'message' => 'HMO name found, but code is invalid or mismatched.',
            'hmo_name' => $hmoByName['hmo_name'],
            'hmo_code' => $hmoByName['hmo_code'],
            'country' => $hmoByName['country'] ?? 'N/A'
        ]);
        exit;
    }
}

// If no match found
echo json_encode(['status' => 'error', 'message' => 'HMO code and name not recognized.']);
?>
