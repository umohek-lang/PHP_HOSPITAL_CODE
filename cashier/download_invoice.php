<?php
require_once('../tcpdf/tcpdf.php');
require '../db.php';

$patient_id     = $_GET['patient_id']  ?? null;
$invoices_param = $_GET['invoices']    ?? null;

if (!$patient_id)     die("No patient selected.");
if (!$invoices_param) die("No invoice numbers provided.");

// Fetch patient
$stmt = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id=?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();
if (!$patient) die("Invalid patient.");

// Fetch bills
if ($invoices_param === 'all') {
    $stmt = $pdo->prepare("SELECT * FROM hos_bills WHERE patient_id=? ORDER BY invoice_no DESC, id");
    $stmt->execute([$patient_id]);
} else {
    $invoice_numbers = explode(',', $invoices_param);
    $placeholders    = implode(',', array_fill(0, count($invoice_numbers), '?'));
    $stmt = $pdo->prepare("SELECT * FROM hos_bills WHERE patient_id=? AND invoice_no IN ($placeholders) ORDER BY invoice_no DESC, id");
    $stmt->execute(array_merge([$patient_id], $invoice_numbers));
}

$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$bills) die("No invoices found.");

// Group by invoice_no
$invoices = [];
foreach ($bills as $b) {
    $invoices[$b['invoice_no']][] = $b;
}

// ── Colour palette (hex strings for TCPDF) ──────────────────────────
define('C_BLUE_DARK',   '#1d4ed8');   // headings, totals
define('C_BLUE_MID',    '#2563eb');   // table header bg
define('C_BLUE_LIGHT',  '#dbeafe');   // total row bg
define('C_BLUE_XLIGHT', '#eff6ff');   // invoice section bg
define('C_GRAY_LINE',   '#e2e8f0');   // divider lines
define('C_GRAY_ALT',    '#f8fafc');   // alternating row bg
define('C_WHITE',       '#ffffff');
define('C_TEXT',        '#1e293b');   // body text
define('C_MUTED',       '#64748b');   // subtitles / notes

// Helper: hex → [r,g,b]
function hexRGB(string $hex): array {
    $hex = ltrim($hex, '#');
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

// ── Extended TCPDF class ────────────────────────────────────────────
class MYPDF extends TCPDF {

    public function Header() {
        // Blue top accent bar
        [$r,$g,$b] = hexRGB(C_BLUE_DARK);
        $this->SetFillColor($r,$g,$b);
        $this->Rect(0, 0, 210, 6, 'F');

        // Logo
        $logo = K_PATH_IMAGES . 'hospital_logo.png';
        if (file_exists($logo)) {
            $this->Image($logo, 15, 11, 22, 0, '', '', '', false, 300);
        }

        // Hospital name
        $this->SetXY(42, 10);
        [$r,$g,$b] = hexRGB(C_BLUE_DARK);
        $this->SetTextColor($r,$g,$b);
        $this->SetFont('dejavusans', 'B', 15);
        $this->Cell(0, 7, 'Angelora Hospital', 0, 1, 'L');

        // Address
        $this->SetX(42);
        [$r,$g,$b] = hexRGB(C_MUTED);
        $this->SetTextColor($r,$g,$b);
        $this->SetFont('dejavusans', '', 8.5);
        $this->MultiCell(0, 4.5, "Plot 73B Cornershop Area, First Avenue Gwarinpa\nContact: 07048221888", 0, 'L');

        // Divider line
        [$r,$g,$b] = hexRGB(C_BLUE_MID);
        $this->SetDrawColor($r,$g,$b);
        $this->SetLineWidth(0.5);
        $this->Line(15, 34, 195, 34);

        $this->SetTextColor(0,0,0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(0.2);
        $this->Ln(10);
    }

    public function Footer() {
        // Blue bottom accent bar
        [$r,$g,$b] = hexRGB(C_BLUE_DARK);
        $this->SetFillColor($r,$g,$b);
        $this->Rect(0, $this->getPageHeight()-6, 210, 6, 'F');

        $this->SetY(-22);
        [$r,$g,$b] = hexRGB(C_MUTED);
        $this->SetTextColor($r,$g,$b);
        $this->SetFont('dejavusans', 'I', 8);
        $this->Cell(0, 5, 'Thank you for choosing Angelora Hospital.  All rights reserved.', 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
        $this->SetTextColor(0,0,0);
    }
}

// ── Create PDF ──────────────────────────────────────────────────────
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Angelora Hospital Billing System');
$pdf->SetAuthor('Angelora Hospital');
$pdf->SetTitle('Invoices — ' . $patient['full_name']);
$pdf->SetMargins(15, 44, 15);
$pdf->SetAutoPageBreak(true, 28);
$pdf->SetPrintHeader(true);
$pdf->SetPrintFooter(true);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// ── Document title ──────────────────────────────────────────────────
[$r,$g,$b] = hexRGB(C_BLUE_DARK);
$pdf->SetTextColor($r,$g,$b);
$pdf->SetFont('dejavusans', 'B', 14);
$pdf->Cell(0, 8, 'Billing Statement', 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 10);
[$r,$g,$b] = hexRGB(C_MUTED);
$pdf->SetTextColor($r,$g,$b);
$pdf->Cell(0, 5, 'Generated: ' . date('D, d M Y  H:i'), 0, 1, 'L');
$pdf->SetTextColor(0,0,0);
$pdf->Ln(4);

// ── Patient info box ────────────────────────────────────────────────
[$r,$g,$b] = hexRGB(C_BLUE_XLIGHT);
$pdf->SetFillColor($r,$g,$b);
[$r,$g,$b] = hexRGB(C_BLUE_MID);
$pdf->SetDrawColor($r,$g,$b);
$pdf->RoundedRect(15, $pdf->GetY(), 180, 18, 3, '1111', 'DF');

[$r,$g,$b] = hexRGB(C_BLUE_DARK);
$pdf->SetTextColor($r,$g,$b);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->SetX(19);
$pdf->Cell(0, 9, 'Patient: ' . htmlspecialchars($patient['full_name']) . '  
|   ID: ' . $patient_id, 0, 1, 'L');
[$r,$g,$b] = hexRGB(C_MUTED);
$pdf->SetTextColor($r,$g,$b);
$pdf->SetFont('dejavusans', '', 9);
$pdf->SetX(19);
$pdf->Cell(0, 7, 'Total Invoices: ' . count($invoices) . '   |   Requested: ' . ($invoices_param === 'all' ? 'All invoices' : count($invoices) . ' invoice(s)'), 0, 1, 'L');

$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(255,255,255);
$pdf->Ln(6);

// ── Invoice loop ────────────────────────────────────────────────────
$colW = [60, 28, 18, 37, 37]; // Service | Source | Qty | Unit Cost | Total
$inv_index = 0;

foreach ($invoices as $inv_no => $items) {

    $grand_total = array_sum(array_column($items, 'total'));
    $is_paid     = array_sum(array_column($items, 'paid')) > 0;
    $inv_index++;

    // ── Invoice header bar ──────────────────────────────────────
    [$r,$g,$b] = hexRGB(C_BLUE_MID);
    $pdf->SetFillColor($r,$g,$b);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 9, 2, '1001', 'F');
    $pdf->SetX(19);
    $pdf->Cell(120, 9, 'Invoice: ' . $inv_no, 0, 0, 'L');
    $status_label = $is_paid ? 'PAID' : 'UNPAID';
    $pdf->Cell(0, 9, 'Status: ' . $status_label, 0, 1, 'R');

    // Status colour line under header
    [$r,$g,$b] = $is_paid ? hexRGB('#16a34a') : hexRGB('#d97706');
    $pdf->SetFillColor($r,$g,$b);
    $pdf->Rect(15, $pdf->GetY(), 180, 1.5, 'F');
    $pdf->Ln(3);

    // ── Table column headers ────────────────────────────────────
    [$r,$g,$b] = hexRGB('#e0effe');
    $pdf->SetFillColor($r,$g,$b);
    [$r,$g,$b] = hexRGB(C_BLUE_DARK);
    $pdf->SetTextColor($r,$g,$b);
    $pdf->SetDrawColor(200,215,240);
    $pdf->SetFont('dejavusans', 'B', 8.5);
    $pdf->SetLineWidth(0.15);

    $headers = ['Service', 'Source', 'Qty', 'Unit Cost (₦)', 'Total (₦)'];
    $aligns  = ['L', 'L', 'C', 'R', 'R'];
    $x = 15;
    foreach ($headers as $i => $h) {
        $pdf->SetXY($x, $pdf->GetY());
        $pdf->Cell($colW[$i], 7, $h, 1, 0, $aligns[$i], true);
        $x += $colW[$i];
    }
    $pdf->Ln(7);

    // ── Table rows ──────────────────────────────────────────────
    $pdf->SetFont('dejavusans', '', 9);
    [$r,$g,$b] = hexRGB(C_TEXT);
    $pdf->SetTextColor($r,$g,$b);

    foreach ($items as $ri => $b_row) {
        // Alternating row background
        if ($ri % 2 === 0) {
            [$r,$g,$b] = hexRGB(C_WHITE);
        } else {
            [$r,$g,$b] = hexRGB(C_GRAY_ALT);
        }
        $pdf->SetFillColor($r,$g,$b);

        $row_data = [
            htmlspecialchars($b_row['service_name']),
            ucfirst(htmlspecialchars($b_row['source_table'])),
            (int)$b_row['quantity'],
            '₦' . number_format((float)$b_row['cost'], 2),
            '₦' . number_format((float)$b_row['total'], 2),
        ];
        $x = 15;
        foreach ($row_data as $ci => $cell) {
            $pdf->SetXY($x, $pdf->GetY());
            $pdf->Cell($colW[$ci], 6.5, $cell, 'LR', 0, $aligns[$ci], true);
            $x += $colW[$ci];
        }
        $pdf->Ln(6.5);
    }

    // ── Invoice total row ────────────────────────────────────────
    [$r,$g,$b] = hexRGB(C_BLUE_LIGHT);
    $pdf->SetFillColor($r,$g,$b);
    [$r,$g,$b] = hexRGB(C_BLUE_DARK);
    $pdf->SetTextColor($r,$g,$b);
    $pdf->SetFont('dejavusans', 'B', 9.5);

    $x = 15;
    $label_w = $colW[0]+$colW[1]+$colW[2]+$colW[3];
    $pdf->SetXY($x, $pdf->GetY());
    $pdf->Cell($label_w, 7.5, 'Invoice Total', 'LBR', 0, 'R', true);
    $pdf->Cell($colW[4], 7.5, '₦' . number_format((float)$grand_total, 2), 'LBR', 1, 'R', true);

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->Ln(7);
}

// ── Grand summary box ────────────────────────────────────────────────
$all_totals = array_sum(array_column($bills, 'total'));
[$r,$g,$b] = hexRGB(C_BLUE_DARK);
$pdf->SetFillColor($r,$g,$b);
$pdf->SetTextColor(255,255,255);
$pdf->SetFont('dejavusans', 'B', 10.5);
$pdf->RoundedRect(15, $pdf->GetY(), 180, 10, 3, '1111', 'F');
$pdf->SetX(19);
$pdf->Cell(120, 10, 'Grand Total for All Invoices', 0, 0, 'L');
$pdf->Cell(0, 10, '₦' . number_format((float)$all_totals, 2), 0, 1, 'R');

$pdf->SetTextColor(0,0,0);
$pdf->Ln(4);

// ── Disclaimer ───────────────────────────────────────────────────────
[$r,$g,$b] = hexRGB(C_MUTED);
$pdf->SetTextColor($r,$g,$b);
$pdf->SetFont('dejavusans', 'I', 7.5);
$pdf->MultiCell(0, 4.5,
    "This document is a computer-generated billing statement from Angelora Hospital. " .
    "Please retain this invoice for your records. For enquiries, contact our billing department.",
    0, 'C');
$pdf->SetTextColor(0,0,0);

// ── Output ───────────────────────────────────────────────────────────
$pdf->Output('Angelora_Invoice_' . $patient_id . '_' . date('Ymd') . '.pdf', 'I');