<?php
require '../db.php';

$services = $pdo->query("
    SELECT id, service_name, role_id, cost 
    FROM service_roles 
    ORDER BY service_name
")->fetchAll(PDO::FETCH_ASSOC);

function roleName($role_id) {
    return match($role_id) {
        2 => 'Doctor',
        3 => 'Nurse',
        5 => 'Pharmacy',
        6 => 'Lab Technician',
        default => 'Other',
    };
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h3 class="mb-4">Manage Services</h3>

<a href="add_service3.php" class="btn btn-primary mb-3">➕ Add New Service</a>

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Service Name</th>
            <th>Department</th>
            <th>Cost (₦)</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($services as $i => $s): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($s['service_name']) ?></td>
            <td><?= roleName($s['role_id']) ?></td>
            <td class="text-end"><?= number_format($s['cost'], 2) ?></td>
            <td>
                <a href="edit_service.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">✏ Edit</a>
                <a href="delete_service.php?id=<?= $s['id'] ?>" 
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete this service?');">
                   🗑 Delete
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
