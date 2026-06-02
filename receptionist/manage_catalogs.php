<?php
// require '../includes/db.php';
require '../db.php';

// Fetch data
$labTests = $pdo->query("SELECT * FROM lab_tests_catalog")->fetchAll();
$nursingProcedures = $pdo->query("SELECT * FROM nursing_procedures_catalog")->fetchAll();
$pharmacyDrugs = $pdo->query("SELECT * FROM pharmacy_medicines")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Medical Catalogs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4">🩺 Manage Lab Tests, Procedures & Medicines</h2>

  <div class="row g-4">
    <?php
    $sections = [
        ['label' => 'Lab Test', 'type' => 'lab', 'data' => $labTests, 'field' => 'test_name', 'color' => 'primary'],
        ['label' => 'Procedure', 'type' => 'procedure', 'data' => $nursingProcedures, 'field' => 'procedure_name', 'color' => 'success'],
        ['label' => 'Medicine', 'type' => 'pharmacy', 'data' => $pharmacyDrugs, 'field' => 'medicine_name', 'color' => 'warning'],
    ];

    foreach ($sections as $sec): ?>
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header bg-<?= $sec['color'] ?> text-white">➕ Add <?= $sec['label'] ?></div>
        <div class="card-body">
          <form action="insert_catalog_item.php" method="POST" class="mb-3">
            <input type="hidden" name="type" value="<?= $sec['type'] ?>">
            <input type="text" name="item_name" class="form-control mb-2" required placeholder="Enter <?= $sec['label'] ?> Name">
            <button class="btn btn-<?= $sec['color'] ?> w-100" type="submit">Add</button>
          </form>
          <hr>
          <ul class="list-group">
            <?php foreach ($sec['data'] as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($item[$sec['field']]) ?>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" 
                    data-bs-toggle="modal" 
                    data-bs-target="#editModal"
                    data-id="<?= $item['id'] ?>"
                    data-name="<?= htmlspecialchars($item[$sec['field']]) ?>"
                    data-type="<?= $sec['type'] ?>"
                  >Edit</button>
                  <a href="delete_catalog_item.php?type=<?= $sec['type'] ?>&id=<?= $item['id'] ?>" 
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Delete this item?')">Del</a>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ✏️ Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="update_catalog_item.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit-id">
        <input type="hidden" name="type" id="edit-type">
        <label class="form-label">New Name</label>
        <input type="text" name="item_name" id="edit-name" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// Populate modal with data
document.querySelectorAll('[data-bs-target="#editModal"]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('edit-id').value = btn.dataset.id;
    document.getElementById('edit-type').value = btn.dataset.type;
    document.getElementById('edit-name').value = btn.dataset.name;
  });
});
</script>

</body>
</html>
