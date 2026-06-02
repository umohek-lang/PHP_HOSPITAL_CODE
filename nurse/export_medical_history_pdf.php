<?php
require_once('../db.php');
require_once('../tcpdf/tcpdf.php');

/* ════════════════════════════════════════════════════
   HELPER: render JSON field as clean HTML for TCPDF
════════════════════════════════════════════════════ */
function extract_json_html($json) {
    $output = '';
    $data   = json_decode($json, true);
    if (!empty($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $output .= '<table border="0" cellpadding="3" width="100%">';
                foreach ($value as $sk => $sv) {
                    $label  = ucfirst(str_replace('_', ' ', $sk));
                    $output .= '<tr>
                        <td width="35%" style="color:#475569;font-size:8px">' . $label . '</td>
                        <td style="color:#0f172a;font-size:8px">' . htmlspecialchars((string)$sv) . '</td>
                    </tr>';
                }
                $output .= '</table>';
            } else {
                $label   = ucfirst(str_replace('_', ' ', $key));
                $output .= '<table border="0" cellpadding="2" width="100%">
                    <tr>
                        <td width="35%" style="color:#475569;font-size:8px">' . $label . '</td>
                        <td style="color:#0f172a;font-size:8px">' . htmlspecialchars((string)$value) . '</td>
                    </tr>
                </table>';
            }
        }
    } else {
        $output = '<span style="color:#94a3b8;font-style:italic;font-size:8px">None recorded</span>';
    }
    return $output;
}

/* ════════════════════════════════════════════════════
   FETCH RECORDS
════════════════════════════════════════════════════ */
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = $_GET['search'] ?? '';
$date   = $_GET['date']   ?? '';
$params = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM medical_historys WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
} else {
    $sql = "SELECT * FROM medical_historys WHERE 1=1";
    if (!empty($search)) {
        $sql .= " AND (full_name LIKE :search OR patient_id LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if (!empty($date)) {
        $sql .= " AND DATE(created_at) = :date";
        $params[':date'] = $date;
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}
$records = $stmt->fetchAll();

/* ════════════════════════════════════════════════════
   CUSTOM TCPDF CLASS  (header + footer)
════════════════════════════════════════════════════ */
class HospitalPDF extends TCPDF {

    public function Header() {
        // Blue top bar
        $this->SetFillColor(37, 99, 235);
        $this->Rect(0, 0, $this->getPageWidth(), 18, 'F');

        // Hospital name
        $this->SetFont('dejavusans', 'B', 13);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(12, 4);
        $this->Cell(0, 8, 'ANGELORA HOSPITAL', 0, 0, 'L');

        // Right side: document label
        $this->SetFont('dejavusans', '', 8);
        $this->SetTextColor(191, 219, 254);
        $this->SetXY(0, 4);
        $this->Cell($this->getPageWidth() - 12, 8, 'CONFIDENTIAL  —  MEDICAL HISTORY RECORD', 0, 0, 'R');

        // Light blue sub-bar
        $this->SetFillColor(219, 234, 254);
        $this->Rect(0, 18, $this->getPageWidth(), 6, 'F');
        $this->SetFont('dejavusans', '', 7);
        $this->SetTextColor(30, 64, 175);
        $this->SetXY(12, 19.5);
        $this->Cell(0, 4, 'Generated: ' . date('d F Y, H:i'), 0, 0, 'L');
        $this->SetXY(0, 19.5);
        $this->Cell($this->getPageWidth() - 12, 4, 'Angelora Hospital Management System', 0, 0, 'R');

        $this->SetTextColor(0, 0, 0);
    }

    public function Footer() {
        $this->SetY(-12);
        $this->SetFillColor(37, 99, 235);
        $this->Rect(0, $this->getPageHeight() - 12, $this->getPageWidth(), 12, 'F');

        $this->SetFont('dejavusans', '', 7);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(12, $this->getPageHeight() - 9);
        $this->Cell(0, 6, 'CONFIDENTIAL — FOR AUTHORISED PERSONNEL ONLY', 0, 0, 'L');
        $this->SetXY(0, $this->getPageHeight() - 9);
        $this->Cell($this->getPageWidth() - 12, 6, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

/* ════════════════════════════════════════════════════
   PDF SETUP
════════════════════════════════════════════════════ */
$pdf = new HospitalPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Angelora Hospital System');
$pdf->SetAuthor('Angelora Hospital');
$pdf->SetTitle('Medical History Record');
$pdf->SetSubject('Patient Medical History');
$pdf->SetMargins(12, 28, 12);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(12);
$pdf->SetAutoPageBreak(true, 18);
$pdf->SetFont('dejavusans', '', 9);

/* ════════════════════════════════════════════════════
   HELPER: section heading bar
════════════════════════════════════════════════════ */
function sectionHead($pdf, $title, $r=30, $g=64, $b=175) {
    $html = '<table border="0" cellpadding="0" width="100%">
        <tr>
            <td style="background-color:rgb(' . $r . ',' . $g . ',' . $b . ');
                       color:#ffffff;
                       font-size:8px;
                       font-weight:bold;
                       padding:4px 8px;
                       text-transform:uppercase;
                       letter-spacing:0.08em;">
                ' . $title . '
            </td>
        </tr>
    </table>';
    $pdf->writeHTML($html, true, false, false, false, '');
}

/* ════════════════════════════════════════════════════
   HELPER: two-cell info row
════════════════════════════════════════════════════ */
function infoRow($label, $value, $w1 = '30%', $w2 = '70%') {
    $val = htmlspecialchars((string)($value ?? '—'));
    return '<tr>
        <td width="' . $w1 . '" style="color:#475569;font-size:8px;padding:3px 6px;">' . $label . '</td>
        <td width="' . $w2 . '" style="color:#0f172a;font-size:8.5px;font-weight:bold;padding:3px 6px;">' . ($val ?: '—') . '</td>
    </tr>';
}

/* ════════════════════════════════════════════════════
   RECORD LOOP
════════════════════════════════════════════════════ */
if (!$records) {
    $pdf->AddPage();
    $pdf->writeHTML('<p style="color:#dc2626;font-size:11px;text-align:center;margin-top:30px;"><b>No records found.</b></p>', true, false, true, false, '');
} else {

    foreach ($records as $ridx => $row) {

        $pdf->AddPage();

        /* ── PATIENT NAME BANNER ── */
        $name  = htmlspecialchars($row['full_name'] ?? 'Unknown Patient');
        $pid   = htmlspecialchars($row['patient_id'] ?? '—');
        $banner = '
        <table border="0" cellpadding="0" width="100%">
            <tr>
                <td style="background-color:rgb(239,246,255);
                           border-left:4px solid rgb(37,99,235);
                           padding:7px 12px;">
                    <span style="font-size:13px;font-weight:bold;color:rgb(30,58,138);">' . $name . '</span>
                    <span style="font-size:8px;color:rgb(100,116,139);margin-left:10px;">Patient ID: ' . $pid . '</span>
                </td>
                <td width="30%" style="background-color:rgb(239,246,255);
                                        padding:7px 12px;
                                        text-align:right;">
                    <span style="font-size:7.5px;color:rgb(71,85,105);">Record #' . ($ridx + 1) . '  |  Created: ' . htmlspecialchars($row['created_at'] ?? '') . '</span>
                </td>
            </tr>
        </table>';
        $pdf->writeHTML($banner, true, false, false, false, '');
        $pdf->Ln(3);

        /* ══ SECTION 1: DEMOGRAPHICS + PHOTO ══ */
        sectionHead($pdf, '1.  Patient Demographics');

        // Photo + demographics side by side
        $hasPhoto  = !empty($row['photo']) && file_exists('../uploads/' . $row['photo']);
        $photoPath = '../uploads/' . ($row['photo'] ?? '');

        $demoHTML = '
        <table border="0" cellpadding="0" width="100%">
        <tr>
            <td width="22%" style="vertical-align:top;padding:6px 8px 6px 0;">';

        if ($hasPhoto) {
            // We'll render the photo after writeHTML via Image()
            $demoHTML .= '<span style="font-size:7.5px;color:#64748b;">[Photo attached]</span>';
        } else {
            $demoHTML .= '
            <table border="1" cellpadding="0" width="80%" style="border-color:#e2e8f0;">
                <tr><td style="height:50px;text-align:center;color:#94a3b8;font-size:7.5px;padding:8px;">
                    No Photo
                </td></tr>
            </table>';
        }

        $demoHTML .= '</td>
            <td width="78%" style="vertical-align:top;">
            <table border="0" cellpadding="0" width="100%">';

        $demoHTML .= infoRow('Date of Birth',    $row['dob']);
        $demoHTML .= infoRow('Age',              $row['age']);
        $demoHTML .= infoRow('Gender',           $row['gender']);
        $demoHTML .= infoRow('Marital Status',   $row['marital_status'] ?? '');
        $demoHTML .= infoRow('Occupation',       $row['occupation'] ?? '');
        $demoHTML .= infoRow('Phone',            $row['phone']);
        $demoHTML .= infoRow('Address',          $row['address']);
        $demoHTML .= infoRow('Visit Date',       $row['visit_date']);

        $demoHTML .= '</table></td>
        </tr></table>';

        $pdf->writeHTML($demoHTML, true, false, false, false, '');

        // Place actual photo if present
        if ($hasPhoto) {
            $pdf->Image(
                $photoPath,
                $pdf->GetX() + 13,
                $pdf->GetY() - 28,   // move up to sit beside demographics
                28, 34,
                '', '', '', false, 150, '', false, false, 1
            );
        }

        $pdf->Ln(3);

        /* ══ SECTION 2: CLINICAL NOTES (3-col grid) ══ */
        sectionHead($pdf, '2.  Clinical Notes');
        $pdf->Ln(1);

        $clinHtml = '<table border="0" cellpadding="4" width="100%">';

        // Row 1: Complaint + HPI
        $clinHtml .= '<tr>
            <td width="50%" style="vertical-align:top;border:1px solid #e2e8f0;border-radius:4px;padding:6px;">
                <span style="font-size:7px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;">Chief Complaint</span><br>
                <span style="font-size:8.5px;color:#0f172a;">' . htmlspecialchars($row['chief_complaint'] ?? '—') . '</span>
            </td>
            <td width="50%" style="vertical-align:top;border:1px solid #e2e8f0;border-radius:4px;padding:6px;">
                <span style="font-size:7px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;">History of Present Illness (HPI)</span><br>
                <span style="font-size:8.5px;color:#0f172a;">' . htmlspecialchars($row['hpi'] ?? '—') . '</span>
            </td>
        </tr>';

        // Row 2: Allergies + Family History
        $clinHtml .= '<tr>
            <td width="50%" style="vertical-align:top;border:1px solid #e2e8f0;border-radius:4px;padding:6px;">
                <span style="font-size:7px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;">Allergies</span><br>
                <span style="font-size:8.5px;color:#0f172a;">' . htmlspecialchars($row['allergies'] ?? '—') . '</span>
            </td>
            <td width="50%" style="vertical-align:top;border:1px solid #e2e8f0;border-radius:4px;padding:6px;">
                <span style="font-size:7px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;">Family History</span><br>
                <span style="font-size:8.5px;color:#0f172a;">' . htmlspecialchars($row['family_history'] ?? '—') . '</span>
            </td>
        </tr>';

        // Row 3: Assessment & Plan (full width)
        $clinHtml .= '<tr>
            <td colspan="2" style="vertical-align:top;border:1px solid #e2e8f0;border-radius:4px;padding:6px;background-color:rgb(239,246,255);">
                <span style="font-size:7px;text-transform:uppercase;letter-spacing:0.08em;color:#1d4ed8;">Assessment &amp; Plan</span><br>
                <span style="font-size:8.5px;color:#0f172a;">' . htmlspecialchars($row['assessment_plan'] ?? '—') . '</span>
            </td>
        </tr>';

        $clinHtml .= '</table>';
        $pdf->writeHTML($clinHtml, true, false, false, false, '');
        $pdf->Ln(3);

        /* ══ SECTION 3: CLINICIAN SIGN-OFF ══ */
        sectionHead($pdf, '3.  Clinician Sign-off', 71, 85, 105);

        $signHtml = '
        <table border="0" cellpadding="4" width="100%">
            <tr style="background-color:rgb(248,250,252);">
                <td width="25%">
                    <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Clinician</span><br>
                    <span style="font-size:9px;font-weight:bold;color:#0f172a;">' . htmlspecialchars($row['clinician_name'] ?? '—') . '</span>
                </td>
                <td width="25%">
                    <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Designation</span><br>
                    <span style="font-size:9px;color:#0f172a;">' . htmlspecialchars($row['clinician_designation'] ?? '—') . '</span>
                </td>
                <td width="25%">
                    <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Date Signed</span><br>
                    <span style="font-size:9px;color:#0f172a;">' . htmlspecialchars($row['clinician_date'] ?? '—') . '</span>
                </td>
                <td width="25%">
                    <span style="font-size:7px;color:#64748b;text-transform:uppercase;">Signature</span><br>
                    <span style="font-size:9px;color:#0f172a;">' . htmlspecialchars($row['clinician_signature'] ?? '—') . '</span>
                </td>
            </tr>
        </table>';
        $pdf->writeHTML($signHtml, true, false, false, false, '');
        $pdf->Ln(4);

        /* ══ SECTION 4: DETAILED MEDICAL DATA (3-col grid) ══ */
        sectionHead($pdf, '4.  Detailed Medical Data');
        $pdf->Ln(1);

        $blocks = [
            ['Surgical History',  $row['surgical_history'],  'rgb(239,246,255)', 'rgb(30,64,175)'],
            ['Current Medications', $row['medications'],     'rgb(240,253,244)', 'rgb(4,120,87)'],
            ['Social History',    $row['social_history'],    'rgb(245,243,255)', 'rgb(109,40,217)'],
            ['Immunization',      $row['immunization'],      'rgb(255,251,235)', 'rgb(180,83,9)'],
            ['Review of Systems', $row['ros'],               'rgb(240,249,255)', 'rgb(2,132,199)'],
            ['Obstetric History', $row['obstetric'],         'rgb(255,241,242)', 'rgb(190,18,60)'],
        ];

        // Render in 3 columns
        $colW = '33.33%';
        $blockHtml = '<table border="0" cellpadding="3" width="100%"><tr>';
        foreach ($blocks as $bidx => $block) {
            if ($bidx > 0 && $bidx % 3 === 0) {
                $blockHtml .= '</tr><tr>';
            }
            $blockHtml .= '<td width="' . $colW . '" style="vertical-align:top;border:1px solid #e2e8f0;padding:0;">
                <table border="0" cellpadding="0" width="100%">
                    <tr>
                        <td style="background-color:' . $block[2] . ';
                                   border-bottom:1px solid #e2e8f0;
                                   padding:4px 7px;
                                   font-size:7px;
                                   font-weight:bold;
                                   text-transform:uppercase;
                                   letter-spacing:0.07em;
                                   color:' . $block[3] . ';">' . $block[0] . '</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 7px;">' . extract_json_html($block[1]) . '</td>
                    </tr>
                </table>
            </td>';
        }
        $blockHtml .= '</tr></table>';
        $pdf->writeHTML($blockHtml, true, false, false, false, '');

        // Physical Examination (full width)
        $pdf->Ln(3);
        $physHtml = '<table border="1" cellpadding="0" width="100%" style="border-color:#e2e8f0;">
            <tr>
                <td style="background-color:rgb(239,246,255);
                           border-bottom:1px solid #dbeafe;
                           padding:4px 8px;
                           font-size:7px;
                           font-weight:bold;
                           text-transform:uppercase;
                           letter-spacing:0.07em;
                           color:rgb(30,58,138);">Physical Examination</td>
            </tr>
            <tr>
                <td style="padding:8px 10px;">' . extract_json_html($row['physical_exam']) . '</td>
            </tr>
        </table>';
        $pdf->writeHTML($physHtml, true, false, false, false, '');

        // Separator between records
        if ($ridx < count($records) - 1) {
            $pdf->Ln(6);
        }
    }
}

/* ════════════════════════════════════════════════════
   OUTPUT
════════════════════════════════════════════════════ */
$pdf->Output('medical_history_' . date('Y-m-d') . '.pdf', 'I');