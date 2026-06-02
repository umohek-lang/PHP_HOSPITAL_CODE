<?php 
include "db.php"; 

// Show all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/auth.php';
require '../db.php';

// Ensure receptionist is logged in
if (!isset($_SESSION['user']['role_id']) || $_SESSION['user']['role_id'] != 8) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <title>EMR Orders Viewer</title>
    <style>
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            padding: 8px;
            background: #0d6efd;
            color: white;
            border-radius: 5px;
        }
        /* Optional: highlight selected patient */
        .select2-selection__rendered {
            color: #0d6efd;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <h2 class="text-center mb-4">Patient Orders Viewer</h2>

    <!-- Patient Dropdown -->
    <div class="mb-4">
        <label class="form-label fw-bold">Select Patient</label>
        <select id="patientSelect" class="form-select">
            <option value=""></option> <!-- placeholder option -->
            <?php
            $patients = $pdo->query("SELECT patient_id, full_name FROM patients ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach($patients as $p){
                echo "<option value='".$p['patient_id']."'>".$p['full_name']."</option>";
            }
            ?>
        </select>
    </div>

    <!-- Display Sections -->
    <div class="section-title">Lab Orders</div>
    <div id="labContent">No data</div>

    <div class="section-title">Nursing Orders</div>
    <div id="nursingContent">No data</div>

    <div class="section-title">Pharmacy Orders</div>
    <div id="pharmacyContent">No data</div>

    <div class="section-title">Consultations</div>
    <div id="consultationContent">No data</div>
    
    <div class="section-title">Drug Chart / Prescription</div>
    <div id="drugChartContent">No data</div>


</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let selectedPatientId = "";

// Initialize Select2
$(document).ready(function() {
    $('#patientSelect').select2({
        placeholder: "Search and select a patient...",
        allowClear: true, // allow clearing selection
        width: '100%',
        templateSelection: function (data) {
            // highlight selected patient in blue
            return $('<span style="color:#0d6efd;font-weight:bold;">'+data.text+'</span>');
        }
    });
});

// When patient is selected
$('#patientSelect').on('change', function() {
    selectedPatientId = $(this).val();
    loadOrders(selectedPatientId);
});

// Fetch and display all orders and consultations
function loadOrders(patientId) {
    if (patientId === "" || patientId === null) {
        document.getElementById("labContent").innerHTML = "No data";
        document.getElementById("nursingContent").innerHTML = "No data";
        document.getElementById("pharmacyContent").innerHTML = "No data";
        document.getElementById("consultationContent").innerHTML = "No data";
        return;
    }

    // Orders
    fetch("fetch_patient_orders.php?patient_id=" + patientId)
        .then(response => response.json())
        .then(data => {
            displayLabOrders(data.lab_orders);
            displayNursingOrders(data.nursing_orders);
            displayPharmacyOrders(data.pharmacy_orders);
            displayDrugChart(data.drug_chart); 
        });

    // Consultations
    fetch("fetch_patient_consultations.php?patient_id=" + patientId)
        .then(response => response.json())
        .then(data => {
            displayConsultations(data.consultations);
        });
}

// Lab Orders Table
function displayLabOrders(data) {
    if (data.length === 0) {
        document.getElementById("labContent").innerHTML = "<p>No lab orders found.</p>";
        return;
    }
    let html = `<div class="table-responsive"><table class="table table-bordered"><thead>
        <tr><th>ID</th><th>Test Name</th><th>Notes</th></tr>
        </thead><tbody>`;
    data.forEach(row => {
        html += `<tr>
            <td>${row.id}</td>
            <td>${row.test_name}</td>
            <td>${row.lab_notes}</td>
        </tr>`;
    });
    html += "</tbody></table></div>";
    document.getElementById("labContent").innerHTML = html;
}

// Nursing Orders Table
function displayNursingOrders(data) {
    if (data.length === 0) {
        document.getElementById("nursingContent").innerHTML = "<p>No nursing orders found.</p>";
        return;
    }
    let html = `<div class="table-responsive"><table class="table table-bordered"><thead>
        <tr><th>ID</th><th>Procedure</th><th>Notes</th></tr>
        </thead><tbody>`;
    data.forEach(row => {
        html += `<tr>
            <td>${row.id}</td>
            <td>${row.procedure_name}</td>
            <td>${row.notes}</td>
        </tr>`;
    });
    html += "</tbody></table></div>";
    document.getElementById("nursingContent").innerHTML = html;
}

// Pharmacy Orders Table
function displayPharmacyOrders(data) {
    if (data.length === 0) {
        document.getElementById("pharmacyContent").innerHTML = "<p>No pharmacy orders found.</p>";
        return;
    }
    let html = `<div class="table-responsive"><table class="table table-bordered"><thead>
        <tr><th>ID</th><th>Medicine</th><th>Dosage</th><th>Notes</th><th>Status</th></tr>
        </thead><tbody>`;
    data.forEach(row => {
        html += `<tr>
            <td>${row.id}</td>
            <td>${row.medicine_name}</td>
            <td>${row.dosage}</td>
            <td>${row.notes}</td>
            <td>${row.status}</td>
        </tr>`;
    });
    html += "</tbody></table></div>";
    document.getElementById("pharmacyContent").innerHTML = html;
}

// Consultations Table
function displayConsultations(data) {
    if (data.length === 0) {
        document.getElementById("consultationContent").innerHTML = "<p>No consultations found.</p>";
        return;
    }
    let html = `<div class="table-responsive"><table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th><th>Date</th>
                <th>O2 Sat</th><th>Pain</th><th>BMI</th><th>Chief Complaint</th><th>Diagnosis</th><th>Treatment</th>
            </tr>
        </thead><tbody>`;
    data.forEach(row => {
        html += `<tr>
            <td>${row.consultation_id}</td>
            <td>${row.consultation_date}</td>
            <td>${row.oxygen_saturation}</td>
            <td>${row.pain_level}</td>
            <td>${row.bmi}</td>
            <td>${row.chief_complaint}</td>
            <td>${row.diagnosis}</td>
            <td>${row.treatment_plan}</td>
        </tr>`;
    });
    html += "</tbody></table></div>";
    document.getElementById("consultationContent").innerHTML = html;
}

// prescription
function displayDrugChart(data) {
    if (!data || data.length === 0) {
        document.getElementById("drugChartContent").innerHTML = "<p>No drug chart found.</p>";
        return;
    }

    let html = `<div class="table-responsive"><table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Drug Name</th>
                <th>Dosage</th>
                <th>Route</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Start</th>
                <th>End</th>
                <th>Prescribed By</th>
                <th>Notes</th>
            </tr>
        </thead><tbody>`;

    data.forEach(row => {
        html += `<tr>
            <td>${row.id}</td>
            <td>${row.drug_name}</td>
            <td>${row.dosage}</td>
            <td>${row.route}</td>
            <td>${row.frequency}</td>
            <td>${row.duration}</td>
            <td>${row.start_date}</td>
            <td>${row.end_date}</td>
            <td>${row.prescribed_by}</td>
            <td>${row.notes}</td>
        </tr>`;
    });

    html += "</tbody></table></div>";
    document.getElementById("drugChartContent").innerHTML = html;
}


// Auto-refresh every 5 seconds
setInterval(() => {
    if (selectedPatientId !== "" && selectedPatientId !== null) {
        loadOrders(selectedPatientId);
    }
}, 5000);

</script>

</body>
</html>
