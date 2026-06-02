<?php
require '../includes/auth.php';
require '../payment_alerts.php';
checkRole(5); // Pharmacist

// Count pending pharmacy orders
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM patient_orders WHERE service_type = 'pharmacy' AND status = 'pending'");
$stmtCount->execute();
$newOrders = $stmtCount->fetchColumn();

// Fetch pending pharmacy orders
$stmt = $pdo->prepare("
    SELECT o.*, p.full_name 
    FROM patient_orders o
    JOIN patients p ON o.patient_id = p.patient_id
    WHERE o.service_type = 'pharmacy' AND o.status = 'pending'
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacist Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        body {
            min-height: 100vh;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: #fff;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
        }
        .sidebar a:hover, .sidebar .active {
            background-color: #495057;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <h4 class="text-white mb-4">Pharmacist</h4>
            <a href="#" class="active"><i class="bi bi-house-door"></i> Dashboard</a>
            <a href="medicines.php"><i class="bi bi-clipboard-check"></i> Add Medicine</a>
            <a href="patients.php"><i class="bi bi-clipboard-check"></i> View Patients</a>
            <a href="../nurse/medical_history.php" target="mainFrame" class="btn btn-primary">View Medical History</a>
            <a href="#"><i class="bi bi-bell"></i> Notifications</a>
            <a href="dispensed_medicines.php"><i class="bi bi-box-seam me-2"></i>Dispense Medicines</a>
        <a href="pharmacy_inventory.php"><i class="bi bi-boxes me-2"></i>Pharmacy Inventory</a>
        <a href="pharmacy_reports.php"><i class="bi bi-bar-chart-line me-2"></i>Pharmacy Reports</a>
            <a href="../logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>

        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <h3>Welcome PHARM, <?= htmlspecialchars($_SESSION['user']['full_name']) ?>!</h3>
            <!-- <p>Your shift: <?= htmlspecialchars($_SESSION['user']['shift'] ?? 'N/A') ?></p> -->

            <!-- PRESCRIPTION ALERT -->
            <!-- Notification Bell -->
<button id="loadPrescriptionsBtn" type="button" class="btn btn-outline-secondary position-relative me-2 text-center" data-bs-toggle="modal" data-bs-target="#prescriptionModal">
  🔔
  <span id="prescriptionCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-center">
    PRESCRIPTION ALERT 0
  </span>
</button>

<div class="modal fade" id="prescriptionModal" tabindex="-1" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="prescriptionModalLabel">💊 New Drug Prescriptions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="prescriptionBody">
        <div class="text-center text-muted small">Loading prescriptions...</div>
      </div>
    </div>
  </div>
</div>


            <!-- Payment Notifications -->
            <div class="dropdown mb-3">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-bell-fill"></i> Payments
                    <?php if (!empty($alerts)): ?>
                        <span class="badge bg-danger"><?= count($alerts) ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu p-2 shadow" style="max-height: 300px; overflow-y: auto;">
                    <?php if (!empty($alerts)): ?>
                        <?php foreach ($alerts as $alert): ?>
                            <li id="alert-<?= $alert['billing_id'] ?>" class="mb-2">
                                <div class="alert alert-success p-2 mb-0">
                                    <strong><?= htmlspecialchars($alert['full_name']) ?></strong> paid for 
                                    <strong><?= htmlspecialchars($alert['service_name']) ?></strong><br>
                                    <small><i class="bi bi-clock"></i> <?= htmlspecialchars($alert['paid_at']) ?></small><br>
                                    <button class="btn btn-sm btn-success markSeenBtn mt-1" data-id="<?= $alert['billing_id'] ?>">Mark as Seen</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-center text-muted">No new notifications</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Pharmacy Orders -->
            <div class="bg-light border rounded shadow p-3 mb-3" style="max-height: 400px; overflow-y: auto;">
                <h5 class="fw-bold text-success">💊 Pharmacy Orders (<span id="pharmacyOrderCount"><?= $newOrders ?></span>)</h5>
                <div id="pharmacyOrdersContainer">
                    <?php foreach ($orders as $order): ?>
                        <div class="border-bottom pb-2 mb-2 small">
                            <strong><?= htmlspecialchars($order['details']) ?></strong><br>
                            <span class="text-muted">Patient: <?= htmlspecialchars($order['full_name']) ?></span><br>
                            <span class="text-muted">Date: <?= $order['created_at'] ?></span><br>
                            <form method="POST" action="mark_order_complete.php" class="mt-1">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <button class="btn btn-sm btn-outline-success">Mark as Completed</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- JS Section -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.markSeenBtn').forEach(button => {
        button.addEventListener('click', function () {
            const billingId = this.getAttribute('data-id');
            const alertElement = document.getElementById('alert-' + billingId);

            fetch('mark_alert_seen.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'billing_id=' + encodeURIComponent(billingId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alertElement.remove();
                } else {
                    alert('Failed to mark as seen.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
        });
    });
});

// Live refresh pharmacy orders
setInterval(() => {
    fetch('fetch_pharmacy_orders1.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('pharmacyOrdersContainer').innerHTML = html;
        });
}, 5000);
</script>

<!-- SELECT 2 AJAX -->
<script>
$(document).ready(function () {
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search patient...',
        ajax: {
            url: 'ajax_patients.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return data;
            }
        }
    });

    $('#medicine_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search medicine...',
        ajax: {
            url: 'ajax_medicines.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return data;
            }
        }
    });
});
</script>



<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->

<script>
$(document).ready(function() {
  // Load new prescriptions
  function loadPrescriptions() {
    $('#prescriptionBody').html('<div class="text-center text-muted">Loading...</div>');
    $.get('fetch_prescriptions.php', function(data) {
      $('#prescriptionBody').html(data);
    });
  }

  // Update bell count
  function updateCount() {
    $.get('fetch_prescriptions.php', function(data) {
      const rowCount = $('<div>').html(data).find('tbody tr').length;
      $('#prescriptionCount').text(rowCount);
    });
  }

  // Load prescriptions when bell is clicked
  $('#loadPrescriptionsBtn').on('click', function() {
    loadPrescriptions();
  });

  // Mark as Seen (using delegation)
  $('#prescriptionBody').on('click', '.markSeenBtn', function() {
    const id = $(this).data('id');
    const row = $('#row-' + id);

    $.post('mark_seen.php', { id: id }, function(response) {
      row.fadeOut('slow', function() {
        $(this).remove();
        updateCount();
      });
    });
  });

  // Initial load and auto-refresh every 30s
  updateCount();
  setInterval(updateCount, 30000);
});
</script>
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

<!-- Then Bootstrap -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
