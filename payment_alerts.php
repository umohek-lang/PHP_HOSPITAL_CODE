<?php
if (!isset($_SESSION)) {
    session_start();
}
require 'db.php';

$alerts = [];
$role_id = $_SESSION['user']['role_id'] ?? null;

if (in_array($role_id, [2, 3, 5, 6])) {
    $search = $_GET['search'] ?? '';

    try {
        $sql = "
            SELECT b.billing_id, b.patient_id, p.full_name, p.patient_pin, p.phone, bs.service_name, b.paid_at
            FROM billings b
            JOIN patients p ON b.patient_id = p.patient_id
            JOIN bill_services bs ON b.service_id = bs.id
            WHERE bs.role_id = :role_id AND b.alert_seen = 0
        ";

        if (!empty($search)) {
            $sql .= " AND (
                p.full_name LIKE :search 
                OR p.patient_id LIKE :search 
                OR p.patient_pin LIKE :search 
                OR p.phone LIKE :search
            )";
        }

        $sql .= " ORDER BY b.paid_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':role_id', $role_id, PDO::PARAM_INT);

        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }

        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage()); // show error
    }
}
?>
