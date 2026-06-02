<?php
include '../db.php';

$services = $pdo->query("
    SELECT id, service_name, cost, 'service_roles' AS source FROM service_roles
    UNION
    SELECT id, service_name, cost, 'bill_services' AS source FROM bill_services
")->fetchAll(PDO::FETCH_ASSOC);

echo "<option value=''>Select Service</option>";
foreach($services as $s){
    echo "<option value='{$s['id']}|{$s['source']}' data-cost='{$s['cost']}'>
        {$s['service_name']} - ₦".number_format($s['cost'],2)." ({$s['source']})
    </option>";
}
