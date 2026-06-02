<?php
include '../db.php';
$message = '';

// Handle cost update
if(isset($_POST['update_cost'])){
    $table = $_POST['table']; // service_roles or bill_services
    $id = $_POST['id'];
    $new_cost = floatval($_POST['cost']);

    $stmt = $pdo->prepare("UPDATE $table SET cost=? WHERE id=?");
    if($stmt->execute([$new_cost, $id])){
        $message = "<div class='alert alert-success'>Cost updated successfully.</div>";
    }
}

// Fetch all services
$services = $pdo->query("
    SELECT id, service_name, cost, 'service_roles' AS source FROM service_roles
    UNION
    SELECT id, service_name, cost, 'bill_services' AS source FROM bill_services
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
<h2>Manage Services</h2>
<?= $message ?>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Service</th>
            <th>Source Table</th>
            <th>Cost (₦)</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($services as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['service_name']) ?></td>
            <td><?= $s['source'] ?></td>
            <td>
                <form method="post" style="display:flex; gap:5px;">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="table" value="<?= $s['source'] ?>">
                    <input type="number" step="0.01" name="cost" value="<?= $s['cost'] ?>" class="form-control" required>
                    <button type="submit" name="update_cost" class="btn btn-primary btn-sm">Save</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
