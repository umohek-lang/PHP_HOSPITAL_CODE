<?php
require '../includes/auth.php'; // ensures $pdo is connected
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All HMOs</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    table { background: white; }
    .table th { background: #0d6efd; color: white; }
    .search-box { max-width: 350px; }
  </style>
</head>
<body class="p-4">

  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-primary">List of All HMOs</h3>
      <div>
        <a href="generate_hmo_pdf.php" class="btn btn-danger me-2">📄 Download PDF</a>
        <button class="btn btn-success" onclick="window.print()">🖨️ Print</button>
      </div>
    </div>

    <input type="text" id="searchInput" class="form-control mb-3 search-box" placeholder="Search HMO name, code, or country...">

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle" id="hmoTable">
        <thead>
          <tr>
            <th>#</th>
            <th>HMO Name</th>
            <th>HMO Code</th>
            <th>Country</th>
          </tr>
        </thead>
        <tbody>
          <?php
          try {
              $stmt = $pdo->query("SELECT hmo_name, hmo_code, country FROM hmos ORDER BY hmo_name ASC");
              $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

              if ($rows) {
                  $count = 1;
                  foreach ($rows as $row) {
                      echo "<tr>
                              <td>{$count}</td>
                              <td>".htmlspecialchars($row['hmo_name'])."</td>
                              <td>".htmlspecialchars($row['hmo_code'])."</td>
                              <td>".htmlspecialchars($row['country'])."</td>
                            </tr>";
                      $count++;
                  }
              } else {
                  echo "<tr><td colspan='4' class='text-center text-muted'>No HMO records found.</td></tr>";
              }

          } catch (PDOException $e) {
              echo "<tr><td colspan='4' class='text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
  // 🔍 Search filter
  document.getElementById('searchInput').addEventListener('keyup', function() {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll('#hmoTable tbody tr');
      rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(filter) ? '' : 'none';
      });
  });
  </script>

</body>
</html>
