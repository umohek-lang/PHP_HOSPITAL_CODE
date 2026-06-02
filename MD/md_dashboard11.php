<?php
require '../db.php';
session_start();

// Ensure only MD can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 9) {
    header("Location: ../login.php");
    exit();
}

// Pagination settings
$perPage = 10;

// Get current pages for each section
$pages = [
    'patients' => $_GET['patientsPage'] ?? 1,
    'consultations' => $_GET['consultationsPage'] ?? 1,
    'tests' => $_GET['testsPage'] ?? 1,
    'billings' => $_GET['billingsPage'] ?? 1,
    'doctors' => $_GET['doctorsPage'] ?? 1,
    'nurses' => $_GET['nursesPage'] ?? 1,
    'pharmacists' => $_GET['pharmacistsPage'] ?? 1,
    'labtechs' => $_GET['labtechsPage'] ?? 1,
];

// Summary counts
$patients_count = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$consultations_count = $pdo->query("SELECT COUNT(*) FROM consultations")->fetchColumn();
$tests_count = $pdo->query("SELECT COUNT(*) FROM lab_tests")->fetchColumn();
$billing_count = $pdo->query("SELECT COUNT(*) FROM billings")->fetchColumn();
$doctors_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=2")->fetchColumn();
$nurses_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=3")->fetchColumn();
$pharmacists_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=5")->fetchColumn();
$labtechs_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=6")->fetchColumn();

// Function to fetch paginated data
function fetchData($pdo, $table, $page, $perPage, $where='', $orderBy='id') {
    $offset = ($page - 1) * $perPage;
    $sql = "SELECT * FROM $table $where ORDER BY $orderBy DESC LIMIT $perPage OFFSET $offset";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Example usage:
$patients = fetchData($pdo, 'patients', $pages['patients'], $perPage, '', 'patient_id');
$consultations = fetchData($pdo, 'consultations', $pages['consultations'], $perPage, '', 'consultation_id');
$tests = fetchData($pdo, 'lab_tests', $pages['tests'], $perPage, '', 'lab_test_id');
$billings = fetchData($pdo, 'billings', $pages['billings'], $perPage, '', 'billing_id');
$doctors = fetchData($pdo, 'users', $pages['doctors'], $perPage, "WHERE role_id=2", 'user_id');
$nurses = fetchData($pdo, 'users', $pages['nurses'], $perPage, "WHERE role_id=3", 'user_id');
$pharmacists = fetchData($pdo, 'users', $pages['pharmacists'], $perPage, "WHERE role_id=5", 'user_id');
$labtechs = fetchData($pdo, 'users', $pages['labtechs'], $perPage, "WHERE role_id=6", 'user_id');

// Activities
$activities = $pdo->query("
    SELECT a.id, a.action, a.created_at, u.full_name, u.email, r.role_name
    FROM activities a
    JOIN users u ON a.user_id = u.user_id
    JOIN (
        SELECT 1 AS role_id, 'Admin' AS role_name UNION
        SELECT 2, 'Doctor' UNION
        SELECT 3, 'Nurse' UNION
        SELECT 4, 'Cashier' UNION
        SELECT 5, 'Pharmacist' UNION
        SELECT 6, 'Lab Technician' UNION
        SELECT 7, 'Patient' UNION
        SELECT 8, 'Receptionist' UNION
        SELECT 9, 'MD'
    ) r ON a.role_id = r.role_id
    ORDER BY a.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Function to generate pagination links
function paginate($totalCount, $currentPage, $perPage, $param) {
    $totalPages = ceil($totalCount / $perPage);
    $html = '<nav><ul class="pagination">';
    for ($i=1; $i<=$totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= "<li class='page-item $active'><a class='page-link' href='?{$param}={$i}'>$i</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}

// Array to store card colors for active highlight
$cardColors = [
    'patientsSection' => 'bg-primary',
    'consultationsSection' => 'bg-success',
    'testsSection' => 'bg-warning',
    'billingsSection' => 'bg-danger',
    'doctorsSection' => 'bg-info',
    'nursesSection' => 'bg-secondary',
    'pharmacistsSection' => 'bg-dark',
    'labtechsSection' => 'bg-primary'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>MD Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .stat-card { border-radius: 12px; color: white; cursor: pointer; transition: 0.3s; }
        .stat-card.active { box-shadow: 0 0 15px rgba(0,0,0,0.3); }
        .details-section { display:none; margin-top:20px; }
        .pagination { justify-content: center; }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="text-center mb-4">MD Dashboard</h2>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3"><div id="patientsCard" class="card stat-card bg-primary p-3" onclick="showSection('patientsSection')"><h4><?= $patients_count ?></h4><p>Registered Patients</p></div></div>
        <div class="col-md-3"><div id="consultationsCard" class="card stat-card bg-success p-3" onclick="showSection('consultationsSection')"><h4><?= $consultations_count ?></h4><p>Consultations</p></div></div>
        <div class="col-md-3"><div id="testsCard" class="card stat-card bg-warning p-3" onclick="showSection('testsSection')"><h4><?= $tests_count ?></h4><p>Lab Tests</p></div></div>
        <div class="col-md-3"><div id="billingsCard" class="card stat-card bg-danger p-3" onclick="showSection('billingsSection')"><h4><?= $billing_count ?></h4><p>Billing Records</p></div></div>
    </div>

    <!-- Staff Summary -->
    <div class="row mb-4">
        <div class="col-md-3"><div id="doctorsCard" class="card stat-card bg-info p-3" onclick="showSection('doctorsSection')"><h4><?= $doctors_count ?></h4><p>Doctors</p></div></div>
        <div class="col-md-3"><div id="nursesCard" class="card stat-card bg-secondary p-3" onclick="showSection('nursesSection')"><h4><?= $nurses_count ?></h4><p>Nurses</p></div></div>
        <div class="col-md-3"><div id="pharmacistsCard" class="card stat-card bg-dark p-3" onclick="showSection('pharmacistsSection')"><h4><?= $pharmacists_count ?></h4><p>Pharmacists</p></div></div>
        <div class="col-md-3"><div id="labtechsCard" class="card stat-card bg-primary p-3" onclick="showSection('labtechsSection')"><h4><?= $labtechs_count ?></h4><p>Lab Techs</p></div></div>
    </div>

    <!-- Sections -->
    <?php
    // Function to render a table with pagination
    function renderTable($title, $id, $data, $headers, $count, $pageParam, $currentPage) {
        echo "<div id='$id' class='details-section'>";
        echo "<h4>$title</h4>";
        echo "<table class='table table-striped table-hover'><thead><tr>";
        foreach ($headers as $h) echo "<th>$h</th>";
        echo "</tr></thead><tbody>";
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $v) echo "<td>".htmlspecialchars($v ?? '')."</td>";
            echo "</tr>";
        }
        if (empty($data)) echo "<tr><td colspan='".count($headers)."' class='text-center text-muted'>No records found.</td></tr>";
        echo "</tbody></table>";
        echo paginate($count, $currentPage, 10, $pageParam);
        echo "</div>";
    }

    // Render all sections
    renderTable('Patients Details', 'patientsSection', $patients, ['ID','Name','Gender','DOB','Created'], $patients_count, 'patientsPage', $pages['patients']);
    renderTable('Consultations Details', 'consultationsSection', $consultations, ['ID','Patient ID','Name','Doctor','Diagnosis','Date','Created'], $consultations_count, 'consultationsPage', $pages['consultations']);
    renderTable('Lab Tests Details', 'testsSection', $tests, ['ID','Patient','Test','Date','Status','Result','Requested By'], $tests_count, 'testsPage', $pages['tests']);
    renderTable('Billing Records', 'billingsSection', $billings, ['ID','Patient ID','Service ID','Status','Paid At','Alert Seen'], $billing_count, 'billingsPage', $pages['billings']);
    renderTable('Doctors', 'doctorsSection', $doctors, ['ID','Full Name','Email'], $doctors_count, 'doctorsPage', $pages['doctors']);
    renderTable('Nurses', 'nursesSection', $nurses, ['ID','Full Name','Email'], $nurses_count, 'nursesPage', $pages['nurses']);
    renderTable('Pharmacists', 'pharmacistsSection', $pharmacists, ['ID','Full Name','Email'], $pharmacists_count, 'pharmacistsPage', $pages['pharmacists']);
    renderTable('Lab Technicians', 'labtechsSection', $labtechs, ['ID','Full Name','Email'], $labtechs_count, 'labtechsPage', $pages['labtechs']);
    ?>

    <!-- Activities Log -->
    <div class="card p-3 mt-4">
        <h5>Recent Activities</h5>
        <table class="table table-striped">
            <thead><tr><th>#</th><th>User</th><th>Email</th><th>Role</th><th>Action</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($activities as $i => $a): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><?= htmlspecialchars($a['role_name']) ?></td>
                    <td><?= htmlspecialchars($a['action']) ?></td>
                    <td><?= $a['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?><tr><td colspan="6" class="text-center text-muted">No activities logged yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let cardColors = <?= json_encode($cardColors) ?>;

function showSection(sectionId) {
    document.querySelectorAll('.details-section').forEach(s => s.style.display = "none");
    document.getElementById(sectionId).style.display = "block";

    // Reset all cards
    document.querySelectorAll('.stat-card').forEach(c => {
        c.classList.remove('active');
        for (const id in cardColors) {
            if(c.id === id.replace('Section','Card')) {
                c.className = 'card stat-card ' + cardColors[id] + ' p-3';
            }
        }
    });

    // Highlight the clicked card
    const cardId = sectionId.replace('Section','Card');
    const card = document.getElementById(cardId);
    if(card) card.classList.add('active');

    window.scrollTo({ top: document.getElementById(sectionId).offsetTop - 50, behavior: 'smooth' });
}

// Open last active section if paginated
<?php
foreach ($pages as $section=>$page) {
    if($page > 1) {
        echo "showSection('{$section}Section');\n";
    }
}
?>
</script>
</body>
</html>
