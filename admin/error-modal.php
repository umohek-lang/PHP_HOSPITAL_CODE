<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Validation Error</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      box-shadow: 0 0 20px rgba(220, 53, 69, 0.45);
      border-radius: 1rem;
      animation: fadeIn 0.5s ease-in-out;
    }

    .modal-header {
      border-bottom: none;
    }

    .modal-body {
      font-size: 1.1rem;
      color: #dc3545;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .modal-footer {
      border-top: none;
      justify-content: center;
    }

    .back-btn {
      background: linear-gradient(45deg, #dc3545, #a71d2a);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 25px;
      font-weight: bold;
      text-transform: uppercase;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .back-btn:hover {
      background: linear-gradient(45deg, #a71d2a, #dc3545);
      transform: scale(1.05);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }
  </style>
</head>
<body>

  <!-- Modal HTML -->
  <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger">
        <div class="modal-header bg-danger text-white rounded-top">
          <h5 class="modal-title" id="errorModalLabel">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Validation Error
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <i class="bi bi-x-circle-fill text-danger"></i>
          <?= htmlspecialchars($errorMessage) ?>
        </div>
        <div class="modal-footer">
          <a href="patients_register.php" class="back-btn">
            <i class="bi bi-arrow-left-circle me-1"></i> Back to Registration
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
      errorModal.show();

      // Automatically close the modal after 5 seconds and redirect
      setTimeout(() => {
        errorModal.hide();
        window.location.href = "patients_register.php";
      }, 50000);
    });
  </script>
</body>
</html>
