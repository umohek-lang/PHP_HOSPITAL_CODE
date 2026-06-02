<?php
require '../db.php';

$patient_id = $_GET['patient_id'];

$stmt = $pdo->prepare("SELECT * FROM pharmacy_orders WHERE patient_id = ?");
$stmt->execute([$patient_id]);

while ($order = $stmt->fetch()) {
    echo "<tr>
        <td>".htmlspecialchars($order['medicine_name'])."</td>
        <td>".htmlspecialchars($order['dosage'])."</td>
        <td>".
            ($order['is_sent_to_cashier']
                ? 'SENT'
                : "<button class='btn btn-sm btn-dark send-to-cashier'
                    data-id='{$order['id']}'
                    data-type='pharmacy'>Send</button>"
            ).
        "</td>
        <td>".($order['is_paid'] ? 'YES' : 'NO')."</td>
    </tr>";
}
