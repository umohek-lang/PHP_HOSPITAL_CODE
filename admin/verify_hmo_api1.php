<?php
require '../db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$hmoCode = trim($data['hmo_code'] ?? '');
$hmoName = trim($data['hmo_name'] ?? '');

if (empty($hmoCode) && empty($hmoName)) {
    echo json_encode(['status' => 'error', 'message' => 'HMO code or name must be provided.']);
    exit;
}

$verifiedViaApi = false;

$realApis = [
    'Hygeia' => 'https://online.hygeiahmo.com/hygeiaapiservice/api/Provider/ProvidersForWeb',
    'AXA Mansard' => 'https://api.axamansard.com/hmo/providers',
    'Reliance HMO' => 'https://reliancehmo.com/api/v1/providers',
    'Avon HMO' => 'https://www.avonhealthcare.com/api/providers',
    'MetroHealth' => 'https://metrohealthhmo.com/api/providers',
    'Total Health Trust' => 'https://www.totalhealthtrust.com/api/providers',
    'Greenbay HMO' => 'https://greenbayhmo.com/api/providers',
    'Integrated Healthcare Limited' => 'https://ihl.com.ng/api/providers',
    'Clearline HMO' => 'https://clearlinehmo.net/api/providers',
    'Leadway Health' => 'https://leadwayhealth.com/api/providers'
];

// Limit to 10 APIs
$realApis = array_slice($realApis, 0, 10);

$multiHandle = curl_multi_init();
$curlHandles = [];

foreach ($realApis as $source => $apiUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[] = ['handle' => $ch, 'source' => $source];
}

do {
    $status = curl_multi_exec($multiHandle, $active);
    curl_multi_select($multiHandle, 0.5);
} while ($active && $status == CURLM_OK);

foreach ($curlHandles as $item) {
    $ch = $item['handle'];
    $source = $item['source'];
    $apiResponse = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);

    if ($curlError) {
        error_log("[$source API] cURL Error: $curlError");
        continue;
    }

    if ($httpCode === 200 && $apiResponse !== false) {
        $dataDecoded = json_decode($apiResponse, true);

        if (!$dataDecoded) {
            $xml = simplexml_load_string($apiResponse);
            $json = json_encode($xml);
            $dataDecoded = json_decode($json, true);
        }

        if (is_array($dataDecoded)) {
            foreach ($dataDecoded as $provider) {
                $providerCode = $provider['ProviderCode'] ?? $provider['providercode'] ?? '';
                $providerName = $provider['ProviderName'] ?? $provider['providername'] ?? '';

                if (strcasecmp($providerCode, $hmoCode) === 0) {
                    $verifiedViaApi = true;
                    curl_multi_close($multiHandle);

                    if (!empty($hmoName) && strcasecmp($providerName, $hmoName) !== 0) {
                        echo json_encode([
                            'status' => 'partial',
                            'message' => "HMO code valid via $source API, but name does not match.",
                            'hmo_name' => $providerName,
                            'hmo_code' => $providerCode,
                            'country' => 'N/A'
                        ]);
                        exit;
                    }
                    echo json_encode([
                        'status' => 'success',
                        'message' => "Verified via $source API.",
                        'hmo_name' => $providerName,
                        'hmo_code' => $providerCode,
                        'country' => 'N/A'
                    ]);
                    exit;
                }
            }
        }
    }
}
curl_multi_close($multiHandle);

// If no API verification succeeded, fall back to local DB check
if (!$verifiedViaApi) {
    if (!empty($hmoCode) && !empty($hmoName)) {
        $stmt = $pdo->prepare("SELECT * FROM hmos WHERE hmo_code = ? AND LOWER(hmo_name) = LOWER(?)");
        $stmt->execute([$hmoCode, $hmoName]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($match) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Local DB: HMO code and name match.',
                'hmo_name' => $match['hmo_name'],
                'hmo_code' => $match['hmo_code'],
                'country' => $match['country'] ?? 'N/A'
            ]);
            exit;
        }
    }

    if (!empty($hmoCode)) {
        $stmt = $pdo->prepare("SELECT * FROM hmos WHERE hmo_code = ?");
        $stmt->execute([$hmoCode]);
        $hmo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($hmo) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Local DB: HMO code verified.',
                'hmo_name' => $hmo['hmo_name'],
                'hmo_code' => $hmo['hmo_code'],
                'country' => $hmo['country'] ?? 'N/A'
            ]);
            exit;
        }
    }

    if (!empty($hmoName)) {
        $stmt = $pdo->prepare("SELECT * FROM hmos WHERE LOWER(hmo_name) = LOWER(?)");
        $stmt->execute([$hmoName]);
        $hmoByName = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($hmoByName) {
            echo json_encode([
                'status' => 'partial',
                'message' => 'Local DB: HMO name found, but code is invalid or mismatched.',
                'hmo_name' => $hmoByName['hmo_name'],
                'hmo_code' => $hmoByName['hmo_code'],
                'country' => $hmoByName['country'] ?? 'N/A'
            ]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'HMO not recognized via API or local DB.']);
?>
