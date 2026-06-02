<?php
require '../db.php';

$recordsPerPage = 5;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$tables = [
    'lab_orders' => 'test_name',
    'nursing_orders' => 'procedure_name',
    'pharmacy_orders' => 'medicine_name',
];

foreach ($tables as $table => $field) {
    $title = ucfirst(str_replace('_orders', '', $table));

    // Count total unpaid orders
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM $table o 
        JOIN patients p ON o.patient_id = p.patient_id 
        WHERE o.is_sent_to_cashier = 1 AND o.is_paid = 0
    ");
    $stmtCount->execute();
    $totalRecords = $stmtCount->fetchColumn();

    $totalPages = ceil($totalRecords / $recordsPerPage);
    $offset = ($currentPage - 1) * $recordsPerPage;

    echo "<div class='card shadow-sm mb-4'>";
    echo "<div class='card-header bg-primary text-white fw-bold'>{$title} Orders</div>";
    echo "<div class='card-body p-0'>";

    // Fetch paginated orders
    $stmt = $pdo->prepare("
        SELECT o.*, p.patient_id, p.full_name 
        FROM $table o 
        JOIN patients p ON o.patient_id = p.patient_id 
        WHERE o.is_sent_to_cashier = 1 AND o.is_paid = 0
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $orders = $stmt->fetchAll();

    if ($orders) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped table-hover table-sm m-0'>";
        echo "<thead class='table-light'>
                <tr>
                    <th>Patient ID</th>
                    <th>Patient Name</th>
                    <th>Service</th>
                    <th>Status</th>
                </tr>
              </thead>
              <tbody>";

        foreach ($orders as $order) {
            echo "<tr>
                    <td>{$order['patient_id']}</td>
                    <td>{$order['full_name']}</td>
                    <td>{$order[$field]}</td>
                    <td>
                        <span class='badge bg-warning text-dark'>Pending Billing</span><br>
                        <a href='../cashier/bill_patient2.php?patient_id={$order['patient_id']}' class='btn btn-sm btn-outline-primary mt-1'>💰 Bill</a>
                        <a href='../cashier/view_bill.php?patient_id={$order['patient_id']}' class='btn btn-sm btn-outline-success mt-1'>🧾 View Bill</a>
                    </td>
                  </tr>";
        }

        echo "</tbody></table></div>";

        if ($totalPages > 1) {
            echo "<nav><ul class='pagination justify-content-center p-3'>";
            for ($i = 1; $i <= $totalPages; $i++) {
                $active = ($i === $currentPage) ? 'active' : '';
                echo "<li class='page-item $active'>
                        <a class='page-link' href='#' onclick='loadOrders($i)'>$i</a>
                      </li>";
            }
            echo "</ul></nav>";
        }
    } else {
        echo "<p class='text-center text-muted m-3'>No unpaid {$title} orders.</p>";
    }

    echo "</div></div>";
}
?>
