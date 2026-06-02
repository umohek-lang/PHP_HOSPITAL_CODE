<?php
require '../db.php';

$id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? '';

if ($id && $type) {
    switch ($type) {
        case 'lab':
            $stmt = $pdo->prepare("DELETE FROM lab_tests_catalog WHERE id = :id");
            break;
        case 'procedure':
            $stmt = $pdo->prepare("DELETE FROM nursing_procedures_catalog WHERE id = :id");
            break;
        case 'pharmacy':
            $stmt = $pdo->prepare("DELETE FROM pharmacy_medicines WHERE id = :id");
            break;
        default:
            die("Invalid type.");
    }

    $stmt->execute([':id' => $id]);
    header("Location: manage_catalogs.php");
    exit;
}

die("Invalid delete request.");
