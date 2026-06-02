<?php
// require '../includes/db.php';
require '../db.php';

$id = $_POST['id'] ?? '';
$type = $_POST['type'] ?? '';
$name = trim($_POST['item_name'] ?? '');

if ($id && $type && $name) {
    switch ($type) {
        case 'lab':
            $stmt = $pdo->prepare("UPDATE lab_tests_catalog SET test_name = :name WHERE id = :id");
            break;
        case 'procedure':
            $stmt = $pdo->prepare("UPDATE nursing_procedures_catalog SET procedure_name = :name WHERE id = :id");
            break;
        case 'pharmacy':
            $stmt = $pdo->prepare("UPDATE pharmacy_medicines SET medicine_name = :name WHERE id = :id");
            break;
        default:
            die("Invalid type.");
    }

    $stmt->execute([':name' => $name, ':id' => $id]);
    header("Location: manage_catalogs.php");
    exit;
}

die("Invalid request.");
