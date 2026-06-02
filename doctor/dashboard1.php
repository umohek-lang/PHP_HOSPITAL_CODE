<?php
// ✅ Start session to track logged-in user
session_start();

// ✅ Include DB connection (adjust path as necessary)
require '../db.php';

// ✅ Check if user is logged in and is a doctor
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header('Location: ../login.php');
    exit;
}

$doctor_id = $_SESSION['user']['user_id']; // Get doctor's ID

// ✅ Fetch unseen appointments for this doctor from database
try {
    $stmt = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time,
               a.status, p.patient_id, p.patient_pin, p.full_name AS patient_name, p.phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = :doctor_id
          AND a.appointment_date >= CURDATE()
          AND a.seen = 0
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 10
    ");
    $stmt->execute(['doctor_id' => $doctor_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $appointments = [];
}

 ?>
<?php
// ✅ Load payment alerts and role check
require '../payment_alerts.php';
require '../includes/auth.php';
checkRole(2); // Ensure user is doctor
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>

    <!-- ✅ Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ✅ Internal CSS for Layout and Styling -->
    <style>
        body { min-height: 100vh; overflow-x: hidden; }
        .sidebar { min-height: 100vh; background-color: #343a40; }
        .sidebar a { color: #fff; padding: 10px 20px; display: block; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; }
        iframe { width: 100%; height: 80vh; border: none; }
        .notification-dropdown { position: absolute; top: 20px; right: 20px; }
        
        
        /*new style */
        #appointmentTable .card {
  margin-top: 20px;
  transition: all 0.3s ease-in-out;
}
#appointmentTable input {
  max-width: 300px;
}
#appointmentTable table {
  font-size: 0.9rem;
}

    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- ✅ Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar pt-3">
            <div class="text-center mb-4">
                <h5 class="text-white">Doctor Panel</h5>

            </div>
            <ul class="nav flex-column">
                
                <a href="#"><i class="bi bi-calendar-check"></i> Appointments</a> -->
    <a href="prescriptions.php"><i class="bi bi-people-fill"></i> Make Prescription</a>
    <a href="doctor_patient_page.php"><i class="bi bi-people-fill"></i> prescribe treatment</a>
    <a href="consultation_list.php"><i class="bi bi-people-fill"></i> view Consultation</a>
    <a href="test.php"><i class="bi bi-people-fill"></i> view Test Result</a>
    <a href="view_logins.php"><i class="bi bi-cash"></i> View Logins</a>
    <a href="view_nurse_reports.php"><i class="bi bi-cash"></i> View Nurse reports</a>
                <li class="nav-item mt-3"><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>

            </ul>
        </nav>

       <!-- ✅ Main Content Area -->
<main class="col-md-9 col-lg-10 ms-sm-auto px-md-4 pt-3">

    <!-- ✅ Notifications Top Right -->
    <div class="d-flex justify-content-end align-items-center gap-4 mb-3">
        <!-- SHIFT -->

<?php
 // This assumes you have a session checker
require '../includes/functions.php'; // Include the new function

$user_id = $_SESSION['user']['user_id'];
$shifts = getUserShifts($pdo, $user_id);
?>

<!-- <h4>Your Assigned Shifts</h4>
<table class="table table-bordered table-sm">
    <thead>
        <tr>
            <th>Shift Name</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Date</th>
            <th>Note</th>
            <th>Action</th>

        </tr>
    </thead>
    <tbody>
        <?php if ($shifts): ?>
            <?php foreach ($shifts as $shift): ?>
                <tr>
                    <td><?= htmlspecialchars($shift['shift_name']) ?></td>
                    <td><?= htmlspecialchars($shift['start_time']) ?></td>
                    <td><?= htmlspecialchars($shift['end_time']) ?></td>
                    <td><?= htmlspecialchars($shift['shift_date']) ?></td>
                    <td><?= htmlspecialchars($shift['note']) ?></td>
                    <td>
    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#shiftModal<?= $shift['shift_name'] . $shift['shift_date'] ?>">View</button>
</td>

                </tr>


            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center">No shifts assigned.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

 -->        <!-- 🔔 Bell Notification -->
        
<!-- 🔔 Bell Notification -->
<div class="text-end me-4 mt-3">
  <div class="position-relative d-inline-block">
    <i id="toggleBell" class="bi bi-bell-fill fs-4 text-danger" style="cursor:pointer;"></i>
    <span id="appointmentCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">0</span>
  </div>
</div>

<!-- ✅ Appointment Table Section -->
<div class="collapse mt-4" id="appointmentTable">
  <div class="card shadow-sm border-0" style="max-width: 95%; margin: auto;">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Upcoming Appointments</h5>
      <input type="text" id="searchInput" class="form-control form-control-sm w-50" placeholder="🔍 Search patient name, PIN, or date...">
    </div>
    <div class="card-body p-3">
      <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover align-middle" id="appointmentsTable">
          <thead class="table-dark text-center">
            <tr>
              <th>#</th>
              <th>Patient ID</th>
              <th>PIN</th>
              <th>Full Name</th>
              <th>Phone</th>
              <th>Date</th>
              <th>Time</th>
              <th>Actions</th>
              <th>Consultation</th>
            </tr>
          </thead>
          <tbody class="text-center"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

        <!-- 💳 Payment Notification Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-currency-exchange"></i> Payments
            </button>
            <ul class="dropdown-menu dropdown-menu-end mt-2 shadow" style="max-height: 400px; overflow-y: auto; width: 300px;">
                <?php if (!empty($alerts)): ?>
                    <?php foreach ($alerts as $alert): ?>
                        <li>
                            <div class="alert alert-success shadow-sm fade-in mb-1 mx-2">
                                <strong><?= htmlspecialchars($alert['full_name']) ?></strong> has paid for 
                                <strong><?= htmlspecialchars($alert['service_name']) ?></strong>.<br>
                                <small><i class="bi bi-clock"></i> <?= htmlspecialchars($alert['paid_at']) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="dropdown-item text-muted text-center">No new notifications</li>
                <?php endif; ?>
            </ul>
        </div>

    </div>

<div class="position-relative">

    <!-- Bell Icon with Alert Count -->
    <i class="bi bi-bell-fill text-warning" style="font-size: 1.5rem;">
        <?php if (!empty($alerts)): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= count($alerts) ?>
            </span>
        <?php endif; ?>
    </i>

    <!-- Alert List -->
    <?php if (!empty($alerts)): ?>
        <div class="alert alert-warning mt-2">
            <strong>Payment Notifications:</strong>
            <ul id="alertList" class="mb-0">
                <?php foreach ($alerts as $alert): ?>
                    <li id="alert-<?= $alert['billing_id'] ?>">
                        <?= htmlspecialchars($alert['full_name']) ?> made a payment for 
                        <?= htmlspecialchars($alert['service_name']) ?> at 
                        <?= date('d M Y h:i A', strtotime($alert['paid_at'])) ?>
                        <button 
                            class="btn btn-sm btn-success markSeenBtn" 
                            data-id="<?= $alert['billing_id'] ?? null ?>">
                            Mark as Seen
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div>




    <!-- ✅ Iframe for loading inner pages -->
    <h3>Doctor Dashboard</h3>
    <p class="text-muted">Use the sidebar to manage patients and appointments.</p>
    <iframe name="mainFrame"></iframe>
</main>



<!-- DOCTORS APPOINTMENT -->


<script>
document.addEventListener('DOMContentLoaded', () => {
    console.log("Script loaded");

    const bell = document.getElementById('toggleBell');
    const tableDiv = document.getElementById('appointmentTable');
    const badge = document.getElementById('appointmentCount');
    const tableBody = document.querySelector('#appointmentsTable tbody');
    const searchInput = document.getElementById('searchInput');
    let collapse = new bootstrap.Collapse(tableDiv, { toggle: false });

function fetchAppointments() {
    fetch('fetch_appointments.php')
        .then(res => res.json())
        .then(data => {
            console.log("Response from server:", data); // <-- Add this
            if (data.success) {
                tableBody.innerHTML = '';
                data.appointments.forEach((a, i) => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-id', a.appointment_id);
                    row.innerHTML = `
                        <td>${i+1}</td>
                        <td>${a.patient_id}</td>
                        <td>${a.patient_pin}</td>
                        <td>${a.patient_name}</td>
                        <td>${a.phone}</td>
                        <td>${a.appointment_date}</td>
                        <td>${a.appointment_time}</td>
                        <td><button class="btn btn-sm btn-warning mark-seen-btn">Mark as Seen</button></td>
                        <td><a href="consultation.php?patient_id=${a.patient_id}" class="btn btn-sm btn-success">Consult</a></td>
                    `;
                    tableBody.appendChild(row);
                });
                attachSeenButtons();
            } else {
                console.warn("Server did not return success:", data.message || data);
            }
        })
        .catch(err => console.error("Error fetching appointments:", err));
}

    function attachSeenButtons() {
        document.querySelectorAll('.mark-seen-btn').forEach(button => {
            button.onclick = function () {
                const row = this.closest('tr');
                const id = row.getAttribute('data-id');
                fetch('mark_seen.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ appointment_id: id })
                }).then(res => res.json())
                  .then(data => {
                      if (data.success) {
                          row.remove();
                          updateCount();
                      }
                  });
            };
        });
    }

    function updateCount() {
        fetch('get_appointment_count.php')
            .then(res => res.json())
            .then(data => {
                badge.textContent = data.count;
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill ' + 
                    (data.count > 0 ? 'bg-danger' : 'bg-secondary');
            });
    }


    bell.addEventListener('click', () => {
    console.log("Bell clicked");
    collapse.toggle();
    console.log("Fetching appointments immediately...");
    fetchAppointments();
});


    // Search filter
    searchInput.addEventListener('keyup', () => {
        const value = searchInput.value.toLowerCase();
        document.querySelectorAll('#appointmentsTable tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });

    // Keep count updated every 5 seconds
    setInterval(updateCount, 3000);
    updateCount();
});
</script>

<!-- PAYMENT NOTIFICATION -->
 <script>
document.addEventListener('DOMContentLoaded', function () {
    const markSeenButtons = document.querySelectorAll('.markSeenBtn');

    markSeenButtons.forEach(button => {
        button.addEventListener('click', function () {
            const billingId = this.getAttribute('data-id');
            const alertElement = document.getElementById('alert-' + billingId);

            fetch('mark_seen.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'billing_id=' + encodeURIComponent(billingId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alertElement.remove(); // ✅ Remove alert from screen
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
</script> 

<!-- Fetching Payment Alerts Automatically -->

<!-- <script>
document.addEventListener('DOMContentLoaded', function () {
    function fetchPaymentNotifications() {
        fetch('fetch_payments.php')
        .then(res => res.json())
        .then(data => {
            const alertList = document.getElementById('alertList');
            const dropdown = document.querySelector('.dropdown-menu');
            
            if (!alertList || !dropdown) return;

            alertList.innerHTML = '';
            dropdown.innerHTML = '';

            if (data.status === 'success' && data.alerts.length > 0) {
                data.alerts.forEach(alert => {
                    const li = document.createElement('li');
                    li.id = 'alert-' + alert.billing_id;
                    li.innerHTML = `
                        ${alert.full_name} made a payment for 
                        ${alert.service_name} at 
                        ${new Date(alert.paid_at).toLocaleString()}
                        <button class="btn btn-sm btn-success markSeenBtn" data-id="${alert.billing_id}">Mark as Seen</button>
                    `;
                    alertList.appendChild(li);

                    const dropdownItem = document.createElement('li');
                    dropdownItem.innerHTML = `
                        <div class="alert alert-success shadow-sm mb-1 mx-2">
                            <strong>${alert.full_name}</strong> has paid for 
                            <strong>${alert.service_name}</strong>.<br>
                            <small><i class="bi bi-clock"></i> ${alert.paid_at}</small>
                        </div>
                    `;
                    dropdown.appendChild(dropdownItem);
                });

                attachMarkSeenButtons();
            } else {
                dropdown.innerHTML = '<li class="dropdown-item text-muted text-center">No new notifications</li>';
            }
        });
    }

    function attachMarkSeenButtons() {
        document.querySelectorAll('.markSeenBtn').forEach(button => {
            button.addEventListener('click', function () {
                const billingId = this.getAttribute('data-id');
                const alertElement = document.getElementById('alert-' + billingId);

                fetch('mark_payment_seen.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'billing_id=' + encodeURIComponent(billingId)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alertElement?.remove();
                    }
                });
            });
        });
    }

    // Run every 5 seconds
    setInterval(fetchPaymentNotifications, 5000);
    fetchPaymentNotifications(); // First run
});
</script>
 -->



</body>
</html>
