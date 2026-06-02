<?php
require_once "../db.php"; // Your database connection file
session_start();
file_put_contents('debug_lab_post.txt', print_r($_POST, true));


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? null;
    $lab_orders = $_POST['lab_order'] ?? [];
    $lab_notes = trim($_POST['lab_notes'] ?? '');
    $requested_by = $_SESSION['username'] ?? 'Doctor';

    if (!$patient_id) {
        echo "❌ Patient ID is missing.";
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO lab_orders (patient_id, test_name, lab_notes, requested_by) VALUES (?, ?, ?, ?)");

        $somethingInserted = false;

        // Insert each selected test
        foreach ($lab_orders as $test_name) {
            $stmt->execute([$patient_id, $test_name, $lab_notes, $requested_by]);
            $somethingInserted = true;
        }

        // If no tests selected, but lab_notes entered (note only)
        if (empty($lab_orders) && !empty($lab_notes)) {
            $stmt->execute([$patient_id, 'Note Only', $lab_notes, $requested_by]);
            $somethingInserted = true;
        }

        $pdo->commit();

        if ($somethingInserted) {
            echo "✅ Lab order(s) saved.";
        } else {
            echo "⚠️ No lab test or note was submitted.";
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "❌ Error inserting lab orders: " . $e->getMessage();
    }
}
