<?php
// dashboard.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../includes/auth.php';
checkRole(8); // Receptionist role
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receptionist Dashboard</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background-color: #f8f9fa;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Marquee */
    .marquee-wrapper {
      overflow: hidden;
      background: linear-gradient(to right, #0d6efd, #6610f2);
      padding: 12px 0;
    }

    .marquee-content {
      display: inline-block;
      white-space: nowrap;
      color: #fff;
      font-weight: 700;
      font-size: 1.6rem;
      animation: marquee 20s linear infinite;
    }

    @keyframes marquee {
      0%   { transform: translateX(100%); }
      100% { transform: translateX(-100%); }
    }

    /* Dashboard */
    .dashboard-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }

    .dashboard-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      width: 100%;
      max-width: 1200px;
    }

    .dashboard-card {
      background: #fff;
      border-radius: 15px;
      padding: 30px 20px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .dashboard-card:hover {
      transform: translateY(-8px) scale(1.05);
      box-shadow: 0 20px 30px rgba(0,0,0,0.15);
    }

    .card-icon {
      font-size: 3.2rem;
      color: #0d6efd;
      margin-bottom: 15px;
    }

    .card-title {
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 1.2rem;
      color: #343a40;
    }

    .card-text {
      font-size: 0.9rem;
      color: #6c757d;
    }

    a.text-decoration-none {
      color: inherit;
    }

    @media (max-width: 576px) {
      .marquee-content {
        font-size: 1.2rem;
      }
      .dashboard-card {
        padding: 25px 15px;
      }
    }
  </style>
</head>
<body>

  <!-- Marquee -->
  <div class="marquee-wrapper">
    <div class="marquee-content">🚑 WELCOME TO RECEPTIONIST DASHBOARD 🚑</div>
  </div>

  <!-- Dashboard Cards -->
  <div class="dashboard-container">
    <div class="dashboard-cards">

      <!-- Register New Patients -->
      <a href="patients_register.php" class="text-decoration-none">
        <div class="dashboard-card">
          <i class="bi bi-person-plus-fill card-icon"></i>
          <h5 class="card-title">Register New Patient</h5>
          <p class="card-text">Add new patients to the hospital system.</p>
        </div>
      </a>

      <!-- Book Appointment -->
      <a href="book_appointment.php" class="text-decoration-none">
        <div class="dashboard-card">
          <i class="bi bi-calendar-check-fill card-icon"></i>
          <h5 class="card-title">Book Appointment</h5>
          <p class="card-text">Schedule appointments for patients.</p>
        </div>
      </a>

      <!-- Pending Doctor Orders -->
      <a href="pending_cashier_orders.php" class="text-decoration-none">
        <div class="dashboard-card">
          <i class="bi bi-bag-fill card-icon"></i>
          <h5 class="card-title">View Doctor Orders for billing</h5>
          <p class="card-text">View orders sent by doctors for processing.</p>
        </div>
      </a>

      <!-- View HMOs -->
      <!--<a href="view_hmos.php" class="text-decoration-none">-->
      <!--  <div class="dashboard-card">-->
      <!--    <i class="bi bi-file-medical-fill card-icon"></i>-->
      <!--    <h5 class="card-title">View HMOs</h5>-->
      <!--    <p class="card-text">Check and confirm your hospital HMOs.</p>-->
      <!--  </div>-->
      <!--</a>-->

      <!-- Doctor Submissions -->
      <a href="view_doctor_submissions.php" class="text-decoration-none">
        <div class="dashboard-card">
          <i class="bi bi-activity card-icon"></i>
          <h5 class="card-title">Doctor Activities</h5>
          <p class="card-text">Monitor submissions and activities by doctors.</p>
        </div>
      </a>

    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>