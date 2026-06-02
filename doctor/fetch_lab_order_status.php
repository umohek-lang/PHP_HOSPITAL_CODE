<?php
require '../db.php';
$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) exit;

$stmt = $pdo->prepare("SELECT * FROM lab_orders WHERE patient_id = ?");
$stmt->execute([$patient_id]);

foreach ($stmt as $order) {
    $sendBtn = !empty($order['is_sent_to_cashier'])
        ? '✅'
        : "<button 
                type='button' 
                class='btn btn-sm btn-dark send-to-cashier' 
                data-id='{$order['id']}' 
                data-type='lab' 
                data-patient='{$patient_id}'>
                Send
           </button>";

    $seenBtn = !empty($order['is_seen_by_doctor'])
        ? '👀'
        : "<a href='mark_seenn.php?type=lab&id={$order['id']}&patient_id=$patient_id' class='btn btn-sm btn-info'></a>";

    $paid = !empty($order['is_paid']) ? '💵' : '❌';

    echo "<tr>
        <td>{$order['test_name']}</td>
        <td>{$sendBtn}</td>
        <td>{$paid}</td>
        <td>{$seenBtn}</td>
    </tr>";
}
?>
