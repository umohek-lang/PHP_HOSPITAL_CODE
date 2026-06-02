<?php
require '../includes/auth.php';
require '../db.php';

$patient_id = $_POST['patient_id'] ?? null;
$type = $_POST['type'] ?? '';

if (!$patient_id) exit;

if ($type === 'treatments') {
    $stmt = $pdo->prepare("SELECT t.*, m.medicine_name FROM treatments t LEFT JOIN medicines m ON t.medicine_id = m.medicine_id WHERE t.patient_id = ? ORDER BY t.created_at DESC");
    $stmt->execute([$patient_id]);
    $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo '<table class="table table-bordered">
            <thead><tr><th>Treatment</th><th>Medicine</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
    foreach ($treatments as $t) {
        echo '<tr>
            <td>'.htmlspecialchars($t['treatment_name']).'</td>
            <td>'.htmlspecialchars($t['medicine_name'] ?? '-').'</td>
            <td>'.htmlspecialchars($t['notes']).'</td>
            <td>'.date('d M Y', strtotime($t['treatment_date'])).'</td>
        </tr>';
    }
    echo '</tbody></table>';
}

if ($type === 'medicines') {
    $stmt = $pdo->prepare("SELECT d.*, m.medicine_name FROM dispensed_medicines d LEFT JOIN medicines m ON d.medicine_id = m.medicine_id WHERE d.patient_id = ? ORDER BY d.dispensed_at DESC");
    $stmt->execute([$patient_id]);
    $meds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo '<table class="table table-bordered">
            <thead><tr><th>Medicine</th><th>Quantity</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
    foreach ($meds as $d) {
        echo '<tr>
            <td>'.htmlspecialchars($d['medicine_name']).'</td>
            <td>'.htmlspecialchars($d['quantity']).'</td>
            <td>'.htmlspecialchars($d['notes']).'</td>
            <td>'.date('d M Y', strtotime($d['dispensed_at'])).'</td>
        </tr>';
    }
    echo '</tbody></table>';
}
