<?php
require '../db.php';
$type = $_POST['type'] ?? '';
$name = trim($_POST['item_name'] ?? '');

if ($type && $name) {
    switch ($type) {
        case 'lab':
            $stmt = $pdo->prepare("INSERT INTO lab_tests_catalog (test_name) VALUES (:name)");
            break;
        case 'procedure':
            $stmt = $pdo->prepare("INSERT INTO nursing_procedures_catalog (procedure_name) VALUES (:name)");
            break;
        case 'pharmacy':
            $stmt = $pdo->prepare("INSERT INTO pharmacy_medicines (medicine_name) VALUES (:name)");
            break;
        default:
            die("Invalid type");
    }

    $stmt->execute([':name' => $name]);
    header("Location: manage_catalogs.php");
    exit;
}

die("Invalid request.");
