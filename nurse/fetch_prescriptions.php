<?php
require '../db.php';

// Fetch all prescriptions
$stmt = $pdo->prepare("
    SELECT d.chart_id, d.patient_id, d.drug_name, d.dosage, d.route, 
           d.frequency, d.duration, d.start_date, d.end_date, d.status, d.notes,
           d.created_at,
           p.full_name
    FROM drug_chart d
    JOIN patients p ON d.patient_id = p.patient_id
    ORDER BY d.start_date DESC
");
$stmt->execute();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If called via AJAX, return only the list content
if (isset($_GET['ajax'])) {
    if ($prescriptions && count($prescriptions) > 0) {
        echo '<ul class="list-group">';
        foreach ($prescriptions as $prescription) {
            $status = strtolower(trim($prescription['status'])); // normalize
            echo '<li class="list-group-item" id="pres-' . $prescription['chart_id'] . '">';
            echo '<strong>' . htmlspecialchars($prescription['full_name']) . '</strong><br>';
            echo htmlspecialchars($prescription['drug_name']) . ' - ' . 
                 htmlspecialchars($prescription['dosage']) . ' (' . htmlspecialchars($prescription['route']) . ')<br>';
            echo '<small>Frequency: ' . htmlspecialchars($prescription['frequency']) . ' | Duration: ' . htmlspecialchars($prescription['duration']) . '</small><br>';
            echo '<small>Start: ' . htmlspecialchars($prescription['start_date']) . ' | End: ' . htmlspecialchars($prescription['end_date']) . '</small><br>';

            // Status badge
            echo '<span class="badge ';
            if ($status === 'pending') echo 'bg-warning text-dark';
            elseif ($status === 'prescribed') echo 'bg-primary text-white';
            elseif ($status === 'completed') echo 'bg-success';
            elseif ($status === 'cancelled') echo 'bg-danger';
            echo '">' . htmlspecialchars($prescription['status']) . '</span><br>';

            // Action buttons
            if ($status === 'pending') {
                echo '<button class="btn btn-sm btn-primary mt-2 mark-prescribed-btn" data-id="' . $prescription['chart_id'] . '">✅ Mark as Prescribed</button>';
            } elseif ($status === 'prescribed') {
                echo '<a href="administer_medications.php?chart_id=' . $prescription['chart_id'] . '" class="btn btn-sm btn-success mt-2">💉 Administer</a>';
            } elseif ($status === 'completed') {
                echo '<span class="badge bg-success mt-2">✅ Completed</span>';
            }

            if (!empty($prescription['notes'])) {
                echo '<div class="text-muted mt-1"><strong>Notes:</strong> ' . nl2br(htmlspecialchars($prescription['notes'])) . '</div>';
            }

            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No prescriptions found.</p>';
    }
    exit; // stop here for AJAX
}
?>

<!-- Full page -->
<div class="container mt-4">
    <h4>💊 Prescriptions</h4>
    <div id="prescriptions-list">
        <?php foreach ($prescriptions as $prescription): ?>
            <?php 
            $status = strtolower(trim($prescription['status'])); 
            ?>
            <li class="list-group-item" id="pres-<?= $prescription['chart_id'] ?>">
                <strong><?= htmlspecialchars($prescription['full_name']) ?></strong><br>
                <?= htmlspecialchars($prescription['drug_name']) ?> - <?= htmlspecialchars($prescription['dosage']) ?> (<?= htmlspecialchars($prescription['route']) ?>)<br>
                <small>Frequency: <?= htmlspecialchars($prescription['frequency']) ?> | Duration: <?= htmlspecialchars($prescription['duration']) ?></small><br>
                <small>Start: <?= htmlspecialchars($prescription['start_date']) ?> | End: <?= htmlspecialchars($prescription['end_date']) ?></small><br>

                <span class="badge 
                    <?php 
                        if ($status === 'pending') echo 'bg-warning text-dark';
                        elseif ($status === 'prescribed') echo 'bg-primary text-white';
                        elseif ($status === 'completed') echo 'bg-success';
                        elseif ($status === 'cancelled') echo 'bg-danger';
                    ?>
                "><?= htmlspecialchars($prescription['status']) ?></span><br>

                <?php if ($status === 'pending'): ?>
                    <button class="btn btn-sm btn-primary mt-2 mark-prescribed-btn" data-id="<?= $prescription['chart_id'] ?>">✅ Mark as Prescribed</button>
                <?php elseif ($status === 'prescribed'): ?>
                    <a href="administer_medications.php?chart_id=<?= $prescription['chart_id'] ?>" class="btn btn-sm btn-success mt-2">💉 Administer</a>
                <?php elseif ($status === 'completed'): ?>
                    <span class="badge bg-success mt-2">✅ Completed</span>
                <?php endif; ?>

                <?php if (!empty($prescription['notes'])): ?>
                    <div class="text-muted mt-1"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($prescription['notes'])) ?></div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Auto-refresh prescriptions every 5 seconds
function loadPrescriptions() {
    fetch('fetch_prescriptions.php?ajax=1')
        .then(res => res.text())
        .then(data => {
            document.getElementById('prescriptions-list').innerHTML = data;
            bindMarkPrescribedButtons();
        })
        .catch(err => console.error(err));
}
setInterval(loadPrescriptions, 5000);
loadPrescriptions(); // initial load

// Mark as Prescribed button
function bindMarkPrescribedButtons() {
    document.querySelectorAll('.mark-prescribed-btn').forEach(button => {
        button.onclick = function () {
            const chartId = this.dataset.id;
            fetch('mark_prescribed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'chart_id=' + encodeURIComponent(chartId)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadPrescriptions(); // refresh list
                } else {
                    alert('Failed to mark as prescribed.');
                }
            });
        }
    });
}
</script>
