<?php
include '../db.php';

$id = $_POST['id'];
$source = $_POST['source'];
$name = $_POST['service_name'];
$cost = $_POST['cost'];

try {
    $stmt = $pdo->prepare("UPDATE $source SET service_name=?, cost=? WHERE id=?");
    $stmt->execute([$name, $cost, $id]);
    echo json_encode(["success"=>true]);
} catch(Exception $e){
    echo json_encode(["success"=>false, "message"=>$e->getMessage()]);
}
