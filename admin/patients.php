<?php 
require '../includes/auth.php';
require '../db.php';

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$searchQuery = '';
$searchValue = '';
$statusValue = '';
$whereClauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $searchValue = trim($_GET['search']);
    $whereClauses[] = "(full_name LIKE :search OR patient_pin LIKE :search OR email LIKE :search OR phone LIKE :search OR patient_status LIKE :search OR created_at LIKE :search)";
    $params[':search'] = '%' . $searchValue . '%';
}

if (!empty($_GET['status'])) {
    $statusValue = $_GET['status'];
    $whereClauses[] = "patient_status = :status";
    $params[':status'] = $statusValue;
}

if (!empty($whereClauses)) {
    $searchQuery = ' WHERE ' . implode(' AND ', $whereClauses);
}

$stmt = $pdo->prepare("SELECT * FROM patients" . $searchQuery . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM patients" . $searchQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$countStmt->execute();
$totalPatients = $countStmt->fetchColumn();
$totalPages = ceil($totalPatients / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sky-600: #0284c7;
            --sky-500: #0ea5e9;
            --sky-400: #38bdf8;
            --sky-300: #7dd3fc;
            --sky-100: #e0f2fe;
            --sky-50:  #f0f9ff;
            --white:   #ffffff;
            --gray-50: #f8fafc;
            --gray-100:#f1f5f9;
            --gray-200:#e2e8f0;
            --gray-400:#94a3b8;
            --gray-600:#475569;
            --gray-800:#1e293b;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--sky-50);
            min-height: 100vh;
            color: var(--gray-800);
        }

        /* ── TOPBAR ── */
        .topbar {
            background: linear-gradient(135deg, var(--sky-600) 0%, var(--sky-500) 100%);
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(2,132,199,0.25);
        }
        .topbar-title {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }
        .topbar-title .icon-wrap {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.18);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .topbar-title h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .topbar-title p {
            margin: 0;
            font-size: 0.72rem;
            opacity: 0.75;
        }
        .btn-back {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }

        /* ── STATS ROW ── */
        .stats-row {
            display: flex;
            gap: 16px;
            padding: 24px 32px 0;
            flex-wrap: wrap;
        }
        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 16px 22px;
            border: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 160px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }
        .stat-icon.blue  { background: var(--sky-100); color: var(--sky-600); }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }
        .stat-icon.amber { background: #fef9c3; color: #ca8a04; }
        .stat-info .val  { font-size: 1.35rem; font-weight: 700; color: var(--gray-800); line-height: 1; }
        .stat-info .lbl  { font-size: 0.72rem; color: var(--gray-400); font-weight: 500; margin-top: 2px; }

        /* ── SEARCH BAR ── */
        .search-section {
            padding: 20px 32px;
        }
        .search-card {
            background: var(--white);
            border-radius: 14px;
            padding: 20px 24px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        }
        .search-card .form-control,
        .search-card .form-select {
            border: 1.5px solid var(--gray-200);
            border-radius: 9px;
            font-size: 0.85rem;
            padding: 9px 14px;
            color: var(--gray-800);
            background: var(--gray-50);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-card .form-control:focus,
        .search-card .form-select:focus {
            border-color: var(--sky-400);
            box-shadow: 0 0 0 3px rgba(56,189,248,0.15);
            outline: none;
            background: #fff;
        }
        .btn-search {
            background: linear-gradient(135deg, var(--sky-600), var(--sky-500));
            border: none;
            color: #fff;
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 9px 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.2s;
        }
        .btn-search:hover { opacity: 0.9; color: #fff; }
        .btn-clear {
            background: var(--gray-100);
            border: 1.5px solid var(--gray-200);
            color: var(--gray-600);
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 9px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-clear:hover { background: var(--gray-200); color: var(--gray-800); }

        /* ── PATIENT CARDS ── */
        .cards-section {
            padding: 0 32px 32px;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .section-header h6 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--gray-400);
            margin: 0;
        }
        .result-count {
            font-size: 0.75rem;
            color: var(--sky-600);
            font-weight: 600;
            background: var(--sky-100);
            border-radius: 20px;
            padding: 3px 12px;
        }

        .patient-card {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .patient-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(2,132,199,0.13);
        }

        .card-photo-wrap {
            position: relative;
            background: var(--sky-50);
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .card-photo-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .card-photo-wrap .photo-placeholder {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: var(--sky-100);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            color: var(--sky-400);
        }
        .status-badge {
            position: absolute;
            top: 10px; right: 10px;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .status-badge.inpatient  { background: #fef9c3; color: #a16207; }
        .status-badge.outpatient { background: #dcfce7; color: #15803d; }
        .status-badge.default    { background: var(--sky-100); color: var(--sky-600); }

        .card-body-inner {
            padding: 16px 18px;
            flex: 1;
        }
        .patient-name {
            font-size: 0.97rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 4px;
        }
        .patient-pin {
            font-size: 0.72rem;
            color: var(--sky-600);
            font-weight: 600;
            background: var(--sky-50);
            border: 1px solid var(--sky-100);
            border-radius: 6px;
            padding: 2px 8px;
            display: inline-block;
            margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            color: var(--gray-600);
            margin-bottom: 6px;
        }
        .info-row i {
            color: var(--sky-400);
            font-size: 0.8rem;
            width: 14px;
            flex-shrink: 0;
        }

        .card-actions {
            padding: 12px 18px;
            border-top: 1px solid var(--gray-100);
            display: flex;
            gap: 8px;
        }
        .btn-edit {
            flex: 1;
            background: var(--sky-50);
            border: 1.5px solid var(--sky-200, #bae6fd);
            color: var(--sky-600);
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 7px;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background 0.2s;
        }
        .btn-edit:hover {
            background: var(--sky-100);
            color: var(--sky-600);
        }
        .btn-del {
            flex: 1;
            background: #fff5f5;
            border: 1.5px solid #fecaca;
            color: #dc2626;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 7px;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: background 0.2s;
        }
        .btn-del:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-400);
        }
        .empty-state i { font-size: 3rem; color: var(--sky-200, #bae6fd); margin-bottom: 12px; }
        .empty-state p { font-size: 0.85rem; margin: 0; }

        /* ── PAGINATION ── */
        .pagination-wrap {
            padding: 0 32px 40px;
            display: flex;
            justify-content: center;
        }
        .pagination .page-link {
            border-radius: 8px !important;
            margin: 0 3px;
            border: 1.5px solid var(--gray-200);
            color: var(--sky-600);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 7px 13px;
            transition: all 0.2s;
        }
        .pagination .page-link:hover {
            background: var(--sky-50);
            border-color: var(--sky-300);
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--sky-600), var(--sky-500));
            border-color: var(--sky-500);
            color: #fff;
            box-shadow: 0 3px 10px rgba(2,132,199,0.3);
        }
        .pagination .page-item.disabled .page-link {
            color: var(--gray-400);
            background: var(--gray-50);
        }

        /* entry animation */
        .fade-in {
            animation: fadeIn 0.4s ease both;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">
        <div class="icon-wrap"><i class="bi bi-people-fill"></i></div>
        <div>
            <h4>Patient Records</h4>
            <p>Hospital Management System</p>
        </div>
    </div>
    <a href="../index.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<!-- STATS ROW -->
<div class="stats-row fade-in">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
            <div class="val"><?= number_format($totalPatients) ?></div>
            <div class="lbl">Total Patients</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
        <div class="stat-info">
            <div class="val">
                <?php
                    $outCount = $pdo->query("SELECT COUNT(*) FROM patients WHERE patient_status='Outpatient'")->fetchColumn();
                    echo number_format($outCount);
                ?>
            </div>
            <div class="lbl">Outpatients</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="bi bi-hospital-fill"></i></div>
        <div class="stat-info">
            <div class="val">
                <?php
                    $inCount = $pdo->query("SELECT COUNT(*) FROM patients WHERE patient_status='Inpatient'")->fetchColumn();
                    echo number_format($inCount);
                ?>
            </div>
            <div class="lbl">Inpatients</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-journal-text"></i></div>
        <div class="stat-info">
            <div class="val"><?= $totalPages ?></div>
            <div class="lbl">Pages</div>
        </div>
    </div>
</div>

<!-- SEARCH SECTION -->
<div class="search-section fade-in">
    <div class="search-card">
        <form method="get">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border:1.5px solid #e2e8f0;border-right:none;border-radius:9px 0 0 9px;">
                            <i class="bi bi-search" style="color:#94a3b8;font-size:.85rem;"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="search"
                               style="border-left:none;border-radius:0 9px 9px 0;"
                               value="<?= htmlspecialchars($searchValue) ?>"
                               placeholder="Search name, PIN, email, phone…">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Inpatient"  <?= ($statusValue === 'Inpatient')  ? 'selected' : '' ?>>🏥 Inpatient</option>
                        <option value="Outpatient" <?= ($statusValue === 'Outpatient') ? 'selected' : '' ?>>✅ Outpatient</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn-search w-100 justify-content-center" type="submit">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="patients.php" class="btn-clear w-100 justify-content-center">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- PATIENT CARDS -->
<div class="cards-section">
    <div class="section-header">
        <h6><i class="bi bi-grid-3x3-gap-fill me-1"></i> Patient List</h6>
        <span class="result-count"><?= $totalPatients ?> record<?= $totalPatients != 1 ? 's' : '' ?> found</span>
    </div>

    <?php if (count($patients) > 0): ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
        <?php foreach ($patients as $i => $patient): ?>
        <div class="col fade-in" style="animation-delay: <?= $i * 0.04 ?>s">
            <div class="patient-card">

                <!-- Photo -->
                <div class="card-photo-wrap">
                    <?php if (!empty($patient['photo'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($patient['photo']) ?>" alt="Photo">
                    <?php else: ?>
                        <div class="photo-placeholder"><i class="bi bi-person-fill"></i></div>
                    <?php endif; ?>

                    <?php
                        $s = strtolower($patient['patient_status'] ?? '');
                        $cls = $s === 'inpatient' ? 'inpatient' : ($s === 'outpatient' ? 'outpatient' : 'default');
                    ?>
                    <span class="status-badge <?= $cls ?>"><?= htmlspecialchars($patient['patient_status'] ?? 'Unknown') ?></span>
                </div>

                <!-- Info -->
                <div class="card-body-inner">
                    <div class="patient-name"><?= htmlspecialchars($patient['full_name'] ?? '') ?></div>
                    <div class="patient-pin"><i class="bi bi-upc me-1"></i><?= htmlspecialchars($patient['patient_pin'] ?? '') ?></div>

                    <div class="info-row">
                        <i class="bi bi-gender-ambiguous"></i>
                        <span><?= htmlspecialchars($patient['gender'] ?? '—') ?>, <?= htmlspecialchars($patient['age'] ?? '—') ?> yrs</span>
                    </div>
                    <div class="info-row">
                        <i class="bi bi-telephone-fill"></i>
                        <span><?= htmlspecialchars($patient['phone'] ?? '—') ?></span>
                    </div>
                    <div class="info-row">
                        <i class="bi bi-envelope-fill"></i>
                        <span style="word-break:break-all;"><?= htmlspecialchars($patient['email'] ?? '—') ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card-actions">
                    <a href="edit_patient.php?id=<?= $patient['patient_id'] ?>" class="btn-edit">
                        <i class="bi bi-pencil-fill"></i> Edit
                    </a>
                    <a href="delete_patient.php?id=<?= $patient['patient_id'] ?>"
                       class="btn-del"
                       onclick="return confirm('Delete this patient? This cannot be undone.')">
                        <i class="bi bi-trash-fill"></i> Delete
                    </a>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <i class="bi bi-person-x-fill"></i>
        <p>No patient records found.<br>
           <?php if ($searchValue || $statusValue): ?>
               Try adjusting your search or <a href="patients.php">clear filters</a>.
           <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="pagination-wrap">
    <ul class="pagination">
        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($searchValue) ?>&status=<?= urlencode($statusValue) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
