<?php
require '../db.php';
session_start();

$message = "";
$patient = null;
$vitals = null;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "<div class='alert alert-danger small mt-2'>❌ Invalid CSRF token.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // Insert into prescriptions table
            $stmt1 = $pdo->prepare("INSERT INTO prescriptions (
                patient_id, drug_name, dosage, route, frequency, duration, start_date, end_date,
                prescribed_by, notes
            ) VALUES (
                :patient_id, :drug_name, :dosage, :route, :frequency, :duration, :start_date, :end_date,
                :prescribed_by, :notes
            )");

            $stmt1->execute([
                ':patient_id'     => $_POST['patient_id'],
                ':drug_name'      => $_POST['drug_name'],
                ':dosage'         => $_POST['dosage'],
                ':route'          => $_POST['route'],
                ':frequency'      => $_POST['frequency'],
                ':duration'       => $_POST['duration'],
                ':start_date'     => $_POST['start_date'],
                ':end_date'       => $_POST['end_date'],
                ':prescribed_by'  => $_POST['prescribed_by'] ?? ($_SESSION['user']['username'] ?? 'Doctor'),
                ':notes'          => $_POST['notes']
            ]);

            // Insert into drug_chart table
            $stmt2 = $pdo->prepare("INSERT INTO drug_chart (
                patient_id, drug_name, dosage, route, frequency, duration, start_date, end_date,
                prescribed_by, notes
            ) VALUES (
                :patient_id, :drug_name, :dosage, :route, :frequency, :duration, :start_date, :end_date,
                :prescribed_by, :notes
            )");

            $stmt2->execute([
                ':patient_id'     => $_POST['patient_id'],
                ':drug_name'      => $_POST['drug_name'],
                ':dosage'         => $_POST['dosage'],
                ':route'          => $_POST['route'],
                ':frequency'      => $_POST['frequency'],
                ':duration'       => $_POST['duration'],
                ':start_date'     => $_POST['start_date'],
                ':end_date'       => $_POST['end_date'],
                ':prescribed_by'  => $_POST['prescribed_by'] ?? ($_SESSION['user']['username'] ?? 'Doctor'),
                ':notes'          => $_POST['notes']
            ]);

            $pdo->commit();

            $message = "<div class='alert alert-success small mt-2'>✅ Prescription saved successfully to both tables.</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger small mt-2'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Load patient details
if (isset($_GET['patient_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$_GET['patient_id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        // Load latest vital signs
        $vitals_stmt = $pdo->prepare("SELECT * FROM vital_signs WHERE patient_id = ? ORDER BY vitals_time DESC LIMIT 1");
        $vitals_stmt->execute([$patient['patient_id']]);
        $vitals = $vitals_stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Prescription</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.7/dist/css/autoComplete.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <style>
    .form-section { max-width: 850px; margin: auto; }
    label { font-size: 0.9rem; }
    legend { font-size: 0.95rem; font-weight: bold; }
    fieldset { border-color: #ccc !important; }
  </style>
</head>
<body class="bg-light">
<div class="container my-4 form-section">
  <div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2 px-3">
      <h6 class="mb-0">Select Patient and Prescribe Drug</h6>
    </div>
    <div class="card-body p-3">

      <form method="get" class="mb-3">
        <label for="patient_id" class="form-label small">Search or Select Patient by Name or ID</label>
        <select name="patient_id" id="patient_id" class="form-select form-select-sm" required>
          <?php if (isset($_GET['patient_id'])): ?>
            <option value="<?= $_GET['patient_id'] ?>" selected><?= htmlspecialchars($_GET['patient_id']) ?></option>
          <?php endif; ?>
        </select>
      </form>

      <?php if ($patient): ?>
        <div class="card mb-3">
          <div class="card-header bg-secondary text-white py-1 px-2"><strong>Patient Details</strong></div>
          <div class="card-body small">
            <div class="row">
              <div class="col-md-4">
                <img src="<?= htmlspecialchars($patient['photo']) ?>" class="img-thumbnail mb-2" alt="Photo" width="100">
              </div>
              <div class="col-md-8">
                <div class="row row-cols-1 row-cols-md-3 g-2">
                <?php
                  $details = [
                    "ID" => $patient['patient_id'], "Name" => $patient['full_name'], "DOB" => $patient['dob'], "Age" => $patient['age'],
                    "Gender" => $patient['gender'], "Phone" => $patient['phone'], "Email" => $patient['email'],
                    "Address" => $patient['address'], "Language" => $patient['language'],
                    "Type" => $patient['patient_type'], "Status" => $patient['patient_status'],
                    "Registered By" => $patient['registered_by'], "Pin" => $patient['patient_pin'],
                    "Created" => $patient['created_at'], "Registered" => $patient['registration_date'],
                    "HMO" => $patient['hmo_name']
                  ];

                  foreach ($details as $label => $value) {
                    echo "<p><strong>{$label}:</strong> {$value}</p>";
                  }
                ?>
              </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($vitals): ?>
        <fieldset class="border p-3 mb-3 rounded bg-white shadow-sm">
          <legend class="float-none w-auto px-2 text-info">📊 Latest Vital Signs</legend>
          <div class="row">
            <?php
              $vitalData = [
                "Temperature" => $vitals['temperature'] . " °C",
                "Pulse Rate" => $vitals['pulse_rate'] . " bpm",
                "Respiration Rate" => $vitals['respiration_rate'] . " rpm",
                "Blood Pressure" => $vitals['blood_pressure'],
                "Oxygen Saturation" => $vitals['oxygen_saturation'] . " %",
                "Pain Level" => $vitals['pain_level'],
                "Height" => $vitals['height_cm'] . " cm",
                "Weight" => $vitals['weight_kg'] . " kg",
                "BMI" => $vitals['bmi'],
                "Blood Sugar" => $vitals['blood_sugar'],
                "Consciousness" => $vitals['consciousness_level'],
                "Symptoms/Notes" => $vitals['symptoms_notes'],
                "Recorded By" => $vitals['recorded_by'],
                "Vitals Time" => $vitals['vitals_time'],
                "Created At" => $vitals['created_at']
              ];
              foreach ($vitalData as $label => $value) {
                echo "<div class='col-md-6 mb-2'><strong>$label:</strong> $value</div>";
              }
            ?>
          </div>
        </fieldset>
      <?php endif; ?>

      <?php if ($patient): ?>
        <?= $message ?>
        <form method="POST" action="prescriptions.php?patient_id=<?= urlencode($patient['patient_id']) ?>" class="small">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

          <fieldset class="border p-3 mb-3 rounded bg-light">
            <legend class="float-none w-auto px-2 text-primary">🧪 Drug Details</legend>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Drug Name</label>
                <select name="drug_name" id="drug_name" class="form-select form-select-sm" required style="width: 100%;"></select>
                
                <div class="row mt-2">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Stock</label>
                    <input type="text" id="stock_display" class="form-control form-control-sm bg-light" readonly>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Price (₦)</label>
                    <input type="text" id="price_display" class="form-control form-control-sm bg-light" readonly>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Dosage</label>
                <input type="text" name="dosage" id="dosage" class="form-control form-control-sm" required>
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-3 mb-3 rounded bg-light">
            <legend class="float-none w-auto px-2 text-primary">🚚 Administration Details</legend>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Route</label>
                <input type="text" name="route" id="route" class="form-control form-control-sm" placeholder="e.g., Oral">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Frequency</label>
                <input type="text" name="frequency" id="frequency" class="form-control form-control-sm" placeholder="e.g., Twice daily">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Duration</label>
                <input type="text" name="duration" id="duration" class="form-control form-control-sm" placeholder="e.g., 5 days">
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-3 mb-3 rounded bg-light">
            <legend class="float-none w-auto px-2 text-primary">🗓️ Prescription Period</legend>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm">
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-3 mb-3 rounded bg-light">
            <legend class="float-none w-auto px-2 text-primary">📝 Notes</legend>
            <div class="mb-2">
              <label class="form-label fw-semibold">Additional Notes</label>
              <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Add any special instructions..."></textarea>
            </div>
          </fieldset>

          <div class="d-flex justify-content-between mt-3">
            <button type="submit" class="btn btn-success btn-sm">💊 Submit</button>
            <a href="consultation.php?patient_id=<?= urlencode($patient['patient_id']) ?>" class="btn btn-secondary btn-sm">⬅️ Back</a>
          </div>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(document).ready(function() {
    $('#patient_id').select2({
      placeholder: "Search by name or ID...",
      allowClear: true,
      ajax: {
        url: 'search_patients_ajax.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return data;
        },
        cache: true
      },
      minimumInputLength: 1,
      width: '100%'
    });

    $('#patient_id').on('select2:select', function (e) {
      const selectedPatientId = e.params.data.id;
      const url = new URL(window.location.href);
      url.searchParams.set('patient_id', selectedPatientId);
      window.location.href = url.toString();
    });

    // Drug Name Select2 Ajax
    $('#drug_name').select2({
      placeholder: "Select drug...",
      allowClear: true,
      ajax: {
        url: 'search_medicines_ajax.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return { results: data.results };
        },
        cache: true
      },
      minimumInputLength: 1,
      width: '100%',
      templateResult: function (data) {
        if (!data.id) return data.text;
        return `${data.text} (Stock: ${data.stock}, ₦${data.price})`;
      },
      templateSelection: function (data) {
        return data.text || data.id;
      }
    }).on('select2:select', function (e) {
      const selected = e.params.data;
      $('#stock_display').val(selected.stock);
      $('#price_display').val(selected.price);
    });
  });
</script>
</body>
</html>
