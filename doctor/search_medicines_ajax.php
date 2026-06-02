<?php
require '../db.php';

header('Content-Type: application/json');

$results = [];

if (isset($_GET['q'])) {
    $search = '%' . $_GET['q'] . '%';

    $stmt = $pdo->prepare("SELECT medicine_id, medicine_name, stock, price FROM medicines WHERE medicine_name LIKE ? LIMIT 20");
    $stmt->execute([$search]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['medicine_name'], // still using name as ID
            'text' => $row['medicine_name'],
            'stock' => $row['stock'],
            'price' => $row['price']
        ];
    }
}

echo json_encode(['results' => $results]);
