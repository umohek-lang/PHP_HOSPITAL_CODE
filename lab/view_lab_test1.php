<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ablehand'; // Replace with your DB name

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch lab test results
$sql = "SELECT * FROM lab_tests ORDER BY test_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lab Test Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Patient Lab Test Results</h2>
    
    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Lab Test ID</th>
                    <th>Patient ID</th>
                    <th>Test Name</th>
                    <th>Test Date</th>
                    <th>Result</th>
                    <th>Status</th>
                    <th>Report File</th>
                    <th>Requested By</th>
                    <th>Appointment ID</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['lab_test_id']?? '') ?></td>
                    <td><?= htmlspecialchars($row['patient_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['test_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['test_date'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['result'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($row['report_file'] ?? '')): ?>
                            <a href="uploads/<?= urlencode($row['report_file'] ?? '') ?>" target="_blank">View Report</a>
                        <?php else: ?>
                            No File
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['requested_by'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['appointment_id'] ?? '') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No lab test results found.</div>
    <?php endif; ?>

</div>
</body>
</html>

<?php
$conn->close();
?>
