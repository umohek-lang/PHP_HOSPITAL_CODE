<?php
require '../includes/auth.php';
require_once('../tcpdf/tcpdf.php'); // adjust path to your TCPDF folder

// Create new PDF document
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your System');
$pdf->SetTitle('List of All HMOs');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Title
$html = '<h2 style="text-align:center;color:#0d6efd;">List of All HMOs</h2>';

// Table Header
$html .= '<table border="1" cellspacing="0" cellpadding="6">
            <tr style="background-color:#0d6efd;color:#fff;">
                <th width="5%">#</th>
                <th width="45%">HMO Name</th>
                <th width="25%">HMO Code</th>
                <th width="25%">Country</th>
            </tr>';

// Fetch data
$stmt = $pdo->query("SELECT hmo_name, hmo_code, country FROM hmos ORDER BY hmo_name ASC");
$count = 1;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $html .= "<tr>
                <td>{$count}</td>
                <td>{$row['hmo_name']}</td>
                <td>{$row['hmo_code']}</td>
                <td>{$row['country']}</td>
              </tr>";
    $count++;
}
$html .= '</table>';

// Output HTML content
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('all_hmos.pdf', 'I'); // 'I' = view in browser, 'D' = download
?>
