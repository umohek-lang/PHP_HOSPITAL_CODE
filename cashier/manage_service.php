<?php
require '../db.php';

/* =========================
   HANDLE BULK ACTIONS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_POST['service_ids'])) {
        $ids = $_POST['service_ids'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // BULK DELETE
        if (isset($_POST['bulk_delete'])) {
            $stmt = $pdo->prepare("DELETE FROM service_roles WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        }

        // BULK UPDATE COST
        if (isset($_POST['bulk_update_cost']) && $_POST['new_cost'] !== '') {
            $newCost = floatval($_POST['new_cost']);
            $stmt = $pdo->prepare("UPDATE service_roles SET cost=? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$newCost], $ids));
        }

        // BULK UPDATE DEPARTMENT
        if (isset($_POST['bulk_update_role']) && $_POST['new_role'] !== '') {
            $newRole = intval($_POST['new_role']);
            $stmt = $pdo->prepare("UPDATE service_roles SET role_id=? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$newRole], $ids));
        }

        header("Location: manage_service.php");
        exit;
    }
}

/* =========================
   FETCH SERVICES
========================= */
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

<form method="post">

<!-- 🔧 BULK ACTION BAR -->
<div class="row mb-3">
    <div class="col-md-3">
        <input type="number" step="0.01" name="new_cost" class="form-control" placeholder="New Cost ₦">
    </div>

    <div class="col-md-3">
        <select name="new_role" class="form-select">
            <option value="">Change Department</option>
            <option value="2">Doctor</option>
            <option value="3">Nurse</option>
            <option value="5">Pharmacy</option>
            <option value="6">Lab Technician</option>
        </select>
    </div>

    <div class="col-md-6">
        <button name="bulk_update_cost" class="btn btn-warning">Update Cost</button>
        <button name="bulk_update_role" class="btn btn-info text-white">Update Dept</button>
        <button name="bulk_delete" class="btn btn-danger"
            onclick="return confirm('Delete selected services?');">
            🗑 Delete Selected
        </button>
    </div>
</div>

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>
                <input type="checkbox" id="checkAll">
            </th>
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
            <td>
                <input type="checkbox" name="service_ids[]" value="<?= $s['id'] ?>">
            </td>
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
</form>

<script>
document.getElementById('checkAll').addEventListener('change', function () {
    document.querySelectorAll('input[name="service_ids[]"]').forEach(cb => {
        cb.checked = this.checked;
    });
});
</script>

</body>
</html>
