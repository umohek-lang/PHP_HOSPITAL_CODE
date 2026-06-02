<?php
include '../db.php';

// Get patient ID
$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) {
    die("No patient selected.");
}

// Fetch patient
$patient_stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients WHERE patient_id=?");
$patient_stmt->execute([$patient_id]);
$patient = $patient_stmt->fetch();
if (!$patient) {
    die("Invalid patient.");
}

// Fetch all bills for patient
$stmt = $pdo->prepare("SELECT * FROM hos_bills WHERE patient_id=? ORDER BY invoice_no DESC, id");
$stmt->execute([$patient_id]);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group bills by invoice_no
$invoices = [];
foreach ($bills as $b) {
    $invoices[$b['invoice_no']][] = $b;
}

// Handle manual marking paid for selected invoices
if (isset($_POST['mark_paid_invoices']) && isset($_POST['selected_invoices'])) {
    $selected_invoices = $_POST['selected_invoices'];
    $placeholders = implode(',', array_fill(0, count($selected_invoices), '?'));
    $stmt = $pdo->prepare("UPDATE hos_bills SET paid=1 WHERE invoice_no IN ($placeholders)");
    $stmt->execute($selected_invoices);
    echo "<script>window.location='view_bill.php?patient_id=$patient_id';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Bills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<h2>Billing Information for <?= htmlspecialchars($patient['full_name']) ?> (ID: <?= $patient['patient_id'] ?>)</h2>

<?php if (isset($_GET['paid']) && $_GET['paid'] == 1): ?>
    <div class="alert alert-success mt-3">
        âœ… Payment successful, invoices marked as paid.
    </div>
<?php endif; ?>

<?php if (empty($invoices)): ?>
    <div class="alert alert-info">No invoices found for this patient.</div>
<?php else: ?>
    <form method="post" action="" id="invoiceForm">
        <?php foreach ($invoices as $inv_no => $items): 
            $total = array_sum(array_column($items,'total'));
            $paid = array_sum(array_column($items,'paid')) > 0;
        ?>
            <div class="card mb-3 invoice-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Invoice: <?= $inv_no ?></strong>
                    <span>Status: <strong><?= $paid ? 'Paid' : 'Unpaid' ?></strong></span>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Source</th>
                                <th>Quantity</th>
                                <th>Unit Cost (â‚¦)</th>
                                <th>Total (â‚¦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['service_name']) ?></td>
                                    <td><?= ucfirst($b['source_table']) ?></td>
                                    <td><?= $b['quantity'] ?></td>
                                    <td class="text-end"><?= number_format($b['cost'],2) ?></td>
                                    <td class="text-end"><?= number_format($b['total'],2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-primary">
                                <td colspan="4" class="text-end"><strong>Invoice Total</strong></td>
                                <td class="text-end"><strong><?= number_format($total,2) ?></strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if (!$paid): ?>
                        <div class="form-check">
                            <input class="form-check-input select-invoice" type="checkbox" name="selected_invoices[]" value="<?= $inv_no ?>" data-total="<?= $total ?>">
                            <label class="form-check-label">
                                Select this invoice for payment
                            </label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="mb-3">
            <button type="submit" name="mark_paid_invoices" class="btn btn-warning" onclick="return confirm('Mark selected invoices as paid?');">âœ… Mark Selected Paid</button>
            <button type="button" id="paystackButton" class="btn btn-success">ðŸ’³ Pay Selected via Paystack</button>
            <button type="button" id="printButton" class="btn btn-primary">ðŸ–¨ Print</button>
            <button type="button" id="downloadButton" class="btn btn-secondary">â¬‡ Download PDF</button>
            <span class="ms-3" id="totalAmountDisplay"></span>
        </div>
    </form>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    function updateTotal() {
        let total = 0;
        $('.select-invoice:checked').each(function(){
            total += parseFloat($(this).data('total'));
        });
        $('#totalAmountDisplay').text('Total Selected: â‚¦' + total.toFixed(2));
        return total;
    }

    $('.select-invoice').on('change', updateTotal);

    $('#paystackButton').on('click', function(){
        let selected = [];
        $('.select-invoice:checked').each(function(){
            selected.push($(this).val());
        });
        if(selected.length == 0){
            alert('Please select at least one unpaid invoice.');
            return;
        }
        let totalAmount = updateTotal();
        let amountKobo = Math.round(totalAmount * 100);

        let patientId = <?= $patient_id ?>;
        let invoicesParam = selected.join(',');
        let form = $('<form action="paystack_payment.php" method="post">' +
            '<input type="hidden" name="patient_id" value="'+patientId+'">' +
            '<input type="hidden" name="invoice_no" value="'+invoicesParam+'">' +
            '<input type="hidden" name="amount" value="'+amountKobo+'">' +
            '</form>');
        $('body').append(form);
        form.submit();
    });

    // Print invoices
    $('#printButton').on('click', function(){
        window.print();
    });

    // Download invoices as PDF via TCPDF
    $('#downloadButton').on('click', function(){
        let selected = [];
        $('.select-invoice:checked').each(function(){
            selected.push($(this).val());
        });

        let patientId = <?= $patient_id ?>;
        let invoicesParam = selected.length > 0 ? selected.join(',') : 'all';

        window.open("download_invoice.php?patient_id=" + patientId + "&invoices=" + invoicesParam, "_blank");
    });
});
</script>
</body>
</html>
