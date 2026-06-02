<?php
require '../db.php';
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 9) exit('Unauthorized');

$perPage = 10;
$pages = [
    'patients'      => max(1, (int)($_GET['patientsPage']      ?? 1)),
    'consultations' => max(1, (int)($_GET['consultationsPage'] ?? 1)),
    'tests'         => max(1, (int)($_GET['testsPage']         ?? 1)),
    'billings'      => max(1, (int)($_GET['billingsPage']      ?? 1)),
    'doctors'       => max(1, (int)($_GET['doctorsPage']       ?? 1)),
    'nurses'        => max(1, (int)($_GET['nursesPage']        ?? 1)),
    'pharmacists'   => max(1, (int)($_GET['pharmacistsPage']   ?? 1)),
    'labtechs'      => max(1, (int)($_GET['labtechsPage']      ?? 1)),
];

function fetchData($pdo, $table, $page, $perPage, $where = '', $orderBy = 'id') {
    $offset = ($page - 1) * $perPage;
    return $pdo->query("SELECT * FROM $table $where ORDER BY $orderBy DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
}

/* ── INLINE CSS injected once (first call only) ── */
function dashboardCSS() {
    return '
<style id="md-content-css">
/* ── Reset inside injected sections ── */
.details-section *{box-sizing:border-box}

/* ── Section wrapper ── */
.details-section{
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:14px;
  overflow:hidden;
  box-shadow:0 4px 16px rgba(15,45,107,.09),0 2px 6px rgba(15,45,107,.06);
  margin-top:20px;
  font-family:"Sora",sans-serif;
}

/* ── Section header ── */
.ds-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:13px 20px;
  background:#fafcff;
  border-bottom:1px solid #dbeafe;
}
.ds-head-left{display:flex;align-items:center;gap:9px}
.ds-icon{
  width:32px;height:32px;border-radius:8px;
  background:#eff6ff;color:#2563eb;
  display:flex;align-items:center;justify-content:center;font-size:.88rem;
}
.ds-title{font-size:.9rem;font-weight:800;color:#0f172a}
.ds-count{
  font-size:.68rem;font-weight:700;padding:3px 11px;border-radius:999px;
  background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8;
}

/* ── Table ── */
.ds-table-wrap{overflow-x:auto}
.ds-table{
  width:100%;border-collapse:collapse;
  font-size:.76rem;min-width:520px;
}
.ds-table thead th{
  padding:8px 14px;text-align:left;
  font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  color:#94a3b8;background:#f8fafc;
  border-bottom:1px solid #e2e8f0;white-space:nowrap;
}
.ds-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s}
.ds-table tbody tr:last-child{border-bottom:none}
.ds-table tbody tr:hover{background:#eff6ff}
.ds-table td{
  padding:9px 14px;color:#334155;vertical-align:middle;
  max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.ds-table td:first-child{
  font-family:monospace;font-size:.7rem;color:#94a3b8;font-weight:600;
}

/* Staff name cell */
.staff-cell{display:flex;align-items:center;gap:8px}
.staff-av{
  width:26px;height:26px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,#2563eb,#60a5fa);
  display:flex;align-items:center;justify-content:center;
  font-size:.58rem;font-weight:700;color:#fff;
}
.staff-name{font-weight:600;color:#0f172a;font-size:.78rem}

/* Status chips */
.chip{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:999px;font-size:.65rem;font-weight:700;white-space:nowrap}
.chip-green{background:#ecfdf5;border:1px solid #d1fae5;color:#047857}
.chip-amber{background:#fffbeb;border:1px solid #fef3c7;color:#b45309}
.chip-red  {background:#fef2f2;border:1px solid #fee2e2;color:#b91c1c}
.chip-blue {background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8}
.chip-gray {background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b}

/* ── Empty state ── */
.ds-empty{
  padding:40px 20px;text-align:center;color:#94a3b8;font-size:.8rem;
}
.ds-empty i{display:block;font-size:2rem;color:#bfdbfe;margin-bottom:10px}

/* ── Pagination ── */
.ds-pagination{
  display:flex;align-items:center;justify-content:space-between;
  padding:11px 20px;border-top:1px solid #f1f5f9;background:#f8fafc;
  flex-wrap:wrap;gap:8px;
}
.ds-pag-info{font-size:.7rem;color:#94a3b8;font-weight:500}
.ds-pag-btns{display:flex;gap:3px;list-style:none;margin:0;padding:0}
.ds-pag-btns .page-item .page-link{
  display:flex;align-items:center;justify-content:center;
  min-width:30px;height:30px;padding:0 8px;
  border-radius:7px;font-size:.74rem;font-weight:600;
  text-decoration:none;color:#64748b;
  background:#fff;border:1px solid #e2e8f0;
  transition:all .15s;cursor:pointer;
}
.ds-pag-btns .page-item .page-link:hover{
  background:#eff6ff;color:#2563eb;border-color:#bfdbfe;
}
.ds-pag-btns .page-item.active .page-link{
  background:#2563eb;border-color:#2563eb;color:#fff;font-weight:700;
  box-shadow:0 2px 8px rgba(37,99,235,.28);
}
.ds-pag-btns .page-item.disabled .page-link{opacity:.35;pointer-events:none}

/* ── Activity log ── */
.activity-item{
  display:flex;align-items:flex-start;gap:12px;
  padding:10px 18px;border-bottom:1px solid #f1f5f9;
  font-size:.76rem;transition:background .1s;
}
.activity-item:last-child{border-bottom:none}
.activity-item:hover{background:#eff6ff}
.act-dot{
  width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px;
  background:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15);
}
.act-action{font-weight:600;color:#0f172a;margin-bottom:2px}
.act-meta{color:#94a3b8;font-size:.7rem}
.act-role{
  display:inline-flex;align-items:center;padding:1px 7px;
  border-radius:999px;font-size:.62rem;font-weight:700;
  background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8;
  margin-left:5px;
}
.act-time{
  margin-left:auto;flex-shrink:0;font-size:.68rem;
  color:#94a3b8;white-space:nowrap;
}
</style>';
}

/* ── Pagination HTML ── */
function paginate($totalCount, $currentPage, $perPage, $param, $offset) {
    $totalPages = (int)ceil($totalCount / $perPage);
    if ($totalPages <= 1) return '';
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    $info = 'Showing ' . ($offset + 1) . '–' . min($offset + $perPage, $totalCount) . ' of ' . number_format($totalCount);

    $html = "<div class='ds-pagination'><span class='ds-pag-info'>$info</span><ul class='ds-pag-btns'>";

    // Prev
    $prevDis = $currentPage <= 1 ? 'disabled' : '';
    $html .= "<li class='page-item $prevDis'><a class='page-link' href='#' data-page='" . ($currentPage - 1) . "' data-param='$param'>&#8249;</a></li>";

    if ($start > 1) {
        $html .= "<li class='page-item'><a class='page-link' href='#' data-page='1' data-param='$param'>1</a></li>";
        if ($start > 2) $html .= "<li class='page-item disabled'><a class='page-link' href='#'>…</a></li>";
    }
    for ($i = $start; $i <= $end; $i++) {
        $act = $i === $currentPage ? 'active' : '';
        $html .= "<li class='page-item $act'><a class='page-link' href='#' data-page='$i' data-param='$param'>$i</a></li>";
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= "<li class='page-item disabled'><a class='page-link' href='#'>…</a></li>";
        $html .= "<li class='page-item'><a class='page-link' href='#' data-page='$totalPages' data-param='$param'>$totalPages</a></li>";
    }

    // Next
    $nextDis = $currentPage >= $totalPages ? 'disabled' : '';
    $html .= "<li class='page-item $nextDis'><a class='page-link' href='#' data-page='" . ($currentPage + 1) . "' data-param='$param'>&#8250;</a></li>";

    $html .= "</ul></div>";
    return $html;
}

/* ── Icon map ── */
$icons = [
    'patients'      => 'bi-people-fill',
    'consultations' => 'bi-clipboard2-pulse-fill',
    'tests'         => 'bi-eyedropper-fill',
    'billings'      => 'bi-receipt-cutoff',
    'doctors'       => 'bi-person-badge-fill',
    'nurses'        => 'bi-clipboard2-heart-fill',
    'pharmacists'   => 'bi-capsule-pill',
    'labtechs'      => 'bi-flask-fill',
    'activities'    => 'bi-activity',
];

/* ── renderTable: produces styled HTML for a section ── */
function renderTable($title, $id, $icon, $data, $headers, $totalCount, $currentPage, $pageParam, $renderRow) {
    global $perPage;
    $offset = ($currentPage - 1) * $perPage;
    $paginationHTML = paginate($totalCount, $currentPage, $perPage, $pageParam, $offset);

    ob_start(); ?>
    <div id="<?= $id ?>" class="details-section">
      <div class="ds-head">
        <div class="ds-head-left">
          <div class="ds-icon"><i class="bi <?= $icon ?>"></i></div>
          <div class="ds-title"><?= $title ?></div>
        </div>
        <span class="ds-count"><?= number_format($totalCount) ?> record<?= $totalCount !== 1 ? 's' : '' ?></span>
      </div>
      <div class="ds-table-wrap">
        <table class="ds-table">
          <thead>
            <tr>
              <?php foreach ($headers as $h): ?><th><?= $h ?></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($data)): ?>
              <tr><td colspan="<?= count($headers) ?>">
                <div class="ds-empty"><i class="bi bi-inbox"></i>No records found.</div>
              </td></tr>
            <?php else: ?>
              <?php foreach ($data as $i => $row): ?>
                <?php echo $renderRow($row, $offset + $i + 1); ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?= $paginationHTML ?>
    </div>
    <?php return ob_get_clean();
}

/* ── Helper: initials avatar ── */
function av($name) {
    $parts = array_filter(explode(' ', $name));
    $ini   = strtoupper(implode('', array_map(fn($w) => $w[0], $parts)));
    return substr($ini, 0, 2) ?: '—';
}

/* ── Helper: status chip ── */
function chip($val, $map = []) {
    $v   = strtolower((string)$val);
    $cls = 'chip-gray';
    foreach ($map as $key => $c) { if (str_contains($v, $key)) { $cls = $c; break; } }
    return "<span class='chip $cls'>" . htmlspecialchars($val ?: '—') . "</span>";
}

/* ── Counts ── */
$counts = [
    'patients'      => $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
    'consultations' => $pdo->query("SELECT COUNT(*) FROM consultations")->fetchColumn(),
    'tests'         => $pdo->query("SELECT COUNT(*) FROM lab_tests")->fetchColumn(),
    'billings'      => $pdo->query("SELECT COUNT(*) FROM billings")->fetchColumn(),
    'doctors'       => $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=2")->fetchColumn(),
    'nurses'        => $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=3")->fetchColumn(),
    'pharmacists'   => $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=5")->fetchColumn(),
    'labtechs'      => $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=6")->fetchColumn(),
];

/* ── Fetch data ── */
$patients      = fetchData($pdo, 'patients',      $pages['patients'],      $perPage, '',               'patient_id');
$consultations = fetchData($pdo, 'consultations', $pages['consultations'], $perPage, '',               'consultation_id');
$tests         = fetchData($pdo, 'lab_tests',     $pages['tests'],         $perPage, '',               'lab_test_id');
$billings      = fetchData($pdo, 'billings',      $pages['billings'],      $perPage, '',               'billing_id');
$doctors       = fetchData($pdo, 'users',         $pages['doctors'],       $perPage, 'WHERE role_id=2','user_id');
$nurses        = fetchData($pdo, 'users',         $pages['nurses'],        $perPage, 'WHERE role_id=3','user_id');
$pharmacists   = fetchData($pdo, 'users',         $pages['pharmacists'],   $perPage, 'WHERE role_id=5','user_id');
$labtechs      = fetchData($pdo, 'users',         $pages['labtechs'],      $perPage, 'WHERE role_id=6','user_id');

/* ── Activities ── */
$activities = $pdo->query("
    SELECT a.id, a.action, a.created_at, u.full_name, u.email, r.role_name
    FROM activities a
    JOIN users u ON a.user_id = u.user_id
    JOIN (
        SELECT 1 AS role_id,'Admin' AS role_name UNION SELECT 2,'Doctor' UNION SELECT 3,'Nurse'
        UNION SELECT 4,'Cashier' UNION SELECT 5,'Pharmacist' UNION SELECT 6,'Lab Technician'
        UNION SELECT 7,'Patient' UNION SELECT 8,'Receptionist' UNION SELECT 9,'MD'
    ) r ON a.role_id = r.role_id
    ORDER BY a.created_at DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

/* ════════════════════════════════════════
   BUILD SECTIONS
════════════════════════════════════════ */
$css = dashboardCSS();

/* ── PATIENTS ── */
$patientsSection = $css . renderTable('Patients', 'patientsSection', $icons['patients'],
    $patients,
    ['#', 'Patient', 'Gender', 'Date of Birth', 'Age', 'Phone', 'Created'],
    $counts['patients'], $pages['patients'], 'patientsPage',
    function($r, $n) {
        $name = htmlspecialchars($r['full_name'] ?? '—');
        return "<tr>
            <td>" . str_pad($n, 3, '0', STR_PAD_LEFT) . "</td>
            <td><div class='staff-cell'><div class='staff-av'>" . av($r['full_name'] ?? '') . "</div><div class='staff-name'>$name</div></div></td>
            <td>" . htmlspecialchars($r['gender'] ?? '—') . "</td>
            <td>" . htmlspecialchars($r['dob'] ?? '—') . "</td>
            <td>" . htmlspecialchars($r['age'] ?? '—') . "</td>
            <td>" . htmlspecialchars($r['phone'] ?? '—') . "</td>
            <td style='color:#94a3b8;font-size:.72rem'>" . htmlspecialchars(substr($r['created_at'] ?? '', 0, 10)) . "</td>
        </tr>";
    }
);

/* ── CONSULTATIONS ── */
$consultationsSection = renderTable('Consultations', 'consultationsSection', $icons['consultations'],
    $consultations,
    ['#', 'Patient ID', 'Doctor', 'Diagnosis', 'Date', 'Created'],
    $counts['consultations'], $pages['consultations'], 'consultationsPage',
    function($r, $n) {
        $diag = htmlspecialchars(substr($r['diagnosis'] ?? '—', 0, 60)) . (strlen($r['diagnosis'] ?? '') > 60 ? '…' : '');
        return "<tr>
            <td>" . str_pad($n, 3, '0', STR_PAD_LEFT) . "</td>
            <td><span class='chip chip-blue'>" . htmlspecialchars($r['patient_id'] ?? '—') . "</span></td>
            <td style='font-weight:600;color:#0f172a'>" . htmlspecialchars($r['doctor_id'] ?? '—') . "</td>
            <td style='max-width:180px'>$diag</td>
            <td style='color:#94a3b8;font-size:.72rem'>" . htmlspecialchars($r['consultation_date'] ?? '—') . "</td>
            <td style='color:#94a3b8;font-size:.72rem'>" . htmlspecialchars(substr($r['created_at'] ?? '', 0, 10)) . "</td>
        </tr>";
    }
);

/* ── LAB TESTS ── */
$testsSection = renderTable('Lab Tests', 'testsSection', $icons['tests'],
    $tests,
    ['#', 'Patient', 'Test Name', 'Date', 'Status', 'Result', 'Requested By'],
    $counts['tests'], $pages['tests'], 'testsPage',
    function($r, $n) {
        $status = chip($r['status'] ?? '', ['complet' => 'chip-green', 'pending' => 'chip-amber', 'cancel' => 'chip-red']);
        return "<tr>
            <td>" . str_pad($n, 3, '0', STR_PAD_LEFT) . "</td>
            <td><span class='chip chip-blue'>" . htmlspecialchars($r['patient_id'] ?? '—') . "</span></td>
            <td style='font-weight:600;color:#0f172a'>" . htmlspecialchars($r['test_name'] ?? '—') . "</td>
            <td style='color:#94a3b8;font-size:.72rem'>" . htmlspecialchars($r['test_date'] ?? '—') . "</td>
            <td>$status</td>
            <td>" . htmlspecialchars(substr($r['result'] ?? '—', 0, 40)) . "</td>
            <td>" . htmlspecialchars($r['requested_by'] ?? '—') . "</td>
        </tr>";
    }
);

/* ── BILLINGS ── */
$billingsSection = renderTable('Billing Records', 'billingsSection', $icons['billings'],
    $billings,
    ['#', 'Patient ID', 'Service', 'Amount', 'Status', 'Paid At'],
    $counts['billings'], $pages['billings'], 'billingsPage',
    function($r, $n) {
        $status = chip($r['status'] ?? '', ['paid' => 'chip-green', 'pending' => 'chip-amber', 'cancel' => 'chip-red', 'unpaid' => 'chip-red']);
        $amount = !empty($r['amount']) ? '₦' . number_format((float)$r['amount'], 2) : '—';
        return "<tr>
            <td>" . str_pad($n, 3, '0', STR_PAD_LEFT) . "</td>
            <td><span class='chip chip-blue'>" . htmlspecialchars($r['patient_id'] ?? '—') . "</span></td>
            <td>" . htmlspecialchars($r['service_id'] ?? '—') . "</td>
            <td style='font-weight:700;color:#0f172a'>$amount</td>
            <td>$status</td>
            <td style='color:#94a3b8;font-size:.72rem'>" . htmlspecialchars($r['paid_at'] ?? '—') . "</td>
        </tr>";
    }
);

/* ── STAFF RENDERER (reused for doctors/nurses/pharmacists/labtechs) ── */
function staffSection($title, $id, $icon, $data, $count, $page, $param) {
    return renderTable($title, $id, $icon, $data,
        ['#', 'Name', 'Email', 'Phone'],
        $count, $page, $param,
        function($r, $n) {
            $name  = htmlspecialchars($r['full_name'] ?? '—');
            $email = htmlspecialchars($r['email'] ?? '—');
            $phone = htmlspecialchars($r['phone'] ?? '—');
            return "<tr>
                <td>" . str_pad($n, 3, '0', STR_PAD_LEFT) . "</td>
                <td><div class='staff-cell'><div class='staff-av'>" . av($r['full_name'] ?? '') . "</div><div class='staff-name'>$name</div></div></td>
                <td style='color:#64748b'>$email</td>
                <td style='color:#64748b'>$phone</td>
            </tr>";
        }
    );
}

$doctorsSection     = staffSection('Doctors',          'doctorsSection',     $icons['doctors'],     $doctors,     $counts['doctors'],     $pages['doctors'],     'doctorsPage');
$nursesSection      = staffSection('Nurses',           'nursesSection',      $icons['nurses'],      $nurses,      $counts['nurses'],      $pages['nurses'],      'nursesPage');
$pharmacistsSection = staffSection('Pharmacists',      'pharmacistsSection', $icons['pharmacists'], $pharmacists, $counts['pharmacists'], $pages['pharmacists'], 'pharmacistsPage');
$labtechsSection    = staffSection('Lab Technicians',  'labtechsSection',    $icons['labtechs'],    $labtechs,    $counts['labtechs'],    $pages['labtechs'],    'labtechsPage');

/* ── ACTIVITIES SECTION ── */
ob_start(); ?>
<div id="activitiesSection" class="details-section" style="margin-top:20px">
  <div class="ds-head">
    <div class="ds-head-left">
      <div class="ds-icon"><i class="bi bi-activity"></i></div>
      <div class="ds-title">Recent Activity Log</div>
    </div>
    <span class="ds-count"><?= count($activities) ?> entries</span>
  </div>
  <?php if (empty($activities)): ?>
    <div class="ds-empty"><i class="bi bi-inbox"></i>No activity records found.</div>
  <?php else: ?>
    <?php foreach ($activities as $a): ?>
    <div class="activity-item">
      <div class="act-dot"></div>
      <div style="flex:1;min-width:0">
        <div class="act-action"><?= htmlspecialchars($a['action'] ?? '—') ?></div>
        <div class="act-meta">
          <?= htmlspecialchars($a['full_name'] ?? '—') ?>
          <span class="act-role"><?= htmlspecialchars($a['role_name'] ?? '—') ?></span>
          &nbsp;·&nbsp; <?= htmlspecialchars($a['email'] ?? '—') ?>
        </div>
      </div>
      <div class="act-time"><?= htmlspecialchars(date('d M, H:i', strtotime($a['created_at'] ?? 'now'))) ?></div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php $activitiesSection = ob_get_clean();

/* ── RETURN JSON ── */
echo json_encode([
    'counts' => $counts,
    'sections' => [
        'patients'      => $patientsSection,
        'consultations' => $consultationsSection,
        'tests'         => $testsSection,
        'billings'      => $billingsSection,
        'doctors'       => $doctorsSection,
        'nurses'        => $nursesSection,
        'pharmacists'   => $pharmacistsSection,
        'labtechs'      => $labtechsSection,
        'activities'    => $activitiesSection,
    ],
    'activities' => $activities
]);