<?php
require '../db.php'; // adjust path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? null;
    $procedures = $_POST['procedure_order'] ?? [];
    $notes = $_POST['nursing_notes'] ?? '';

    if (!$patient_id) {
        http_response_code(400);
        echo "Invalid patient ID.";
        exit;
    }

    // Insert each procedure (if any)
    foreach ($procedures as $procedure_name) {
        $stmt = $pdo->prepare("INSERT INTO nursing_orders (patient_id, procedure_name, notes) VALUES (?, ?, ?)");
        $stmt->execute([$patient_id, $procedure_name, $notes]);
    }

    // If no procedure selected but only notes, save the notes entry alone
    if (empty($procedures) && !empty(trim($notes))) {
        $stmt = $pdo->prepare("INSERT INTO nursing_orders (patient_id, procedure_name, notes) VALUES (?, ?, ?)");
        $stmt->execute([$patient_id, 'NOTE ONLY', $notes]); // Or use NULL or '' instead of 'NOTE ONLY'
    }

    echo "success";
    exit;
}
?>
