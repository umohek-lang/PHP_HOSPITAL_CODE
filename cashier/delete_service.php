<?php
require '../db.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid request.");

/* Get service name before delete */
$stmt = $pdo->prepare("SELECT service_name FROM service_roles WHERE id=?");
$stmt->execute([$id]);
$service = $stmt->fetch();
if (!$service) die("Service not found.");

$service_name = $service['service_name'];

/* Delete from service_roles */
$pdo->prepare("DELETE FROM service_roles WHERE id=?")->execute([$id]);

/* Optional: delete from medicines */
$pdo->prepare("DELETE FROM medicines WHERE medicine_name=?")
    ->execute([$service_name]);

header("Location: manage_service.php");
exit;
