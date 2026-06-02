<?php
include '../db.php';

$search = isset($_GET['term']) ? strtolower(trim($_GET['term'])) : '';

$grouped_services = [
    "Consultation Services" => [
        "General Outpatient Consultation",
        "Specialist Consultation (e.g., Cardiologist, Neurologist)",
        "Antenatal Consultation",
        "Postnatal Follow-Up",
        "Family Planning Counseling",
        "Mental Health Assessment",
        "Pre-operative Evaluation",
        "Follow-up Visits",
        "Emergency Room Consultation",
        "Telemedicine Consultation"
    ],
    "Laboratory Services" => [
        "Full Blood Count (FBC / CBC)",
        "Blood Sugar Test (FBS / RBS)",
        "Urinalysis",
        "Malaria Parasite Test",
        "HIV Screening",
        "Hepatitis B and C Test",
        "Widal Test",
        "Blood Group and Cross Matching",
        "Electrolytes & Urea (E/U/Cr)",
        "Liver Function Test (LFT)",
        "Lipid Profile",
        "Pregnancy Test (hCG)",
        "Sputum Microscopy",
        "Stool Analysis",
        "PCR / DNA Test"
    ],
    "Pharmacy Services" => [
        "Dispensing of Prescribed Drugs",
        "Drug Counseling & Education",
        "Inventory & Drug Stock Management",
        "Medication Reconciliation",
        "Over-the-Counter (OTC) Drugs",
        "Intravenous Drug Preparation",
        "Vaccines Administration (by pharmacists where allowed)",
        "Controlled Drugs Dispensing",
        "Adverse Drug Reaction (ADR) Monitoring"
    ],
    "Nursing Services" => [
        "Vital Signs Monitoring",
        "Wound Dressing & Management",
        "Injections and Infusions",
        "Patient Education & Counseling",
        "Pre- and Post-Operative Nursing Care",
        "Catheterization",
        "Bed Bath & Hygiene Assistance",
        "Nutritional Support",
        "Pain Management",
        "Admission & Discharge Nursing Procedures",
        "Immunization",
        "Patient Observation & Documentation",
        "Triage in Emergency Unit"
    ]
];

$results = [];

foreach ($grouped_services as $group => $services) {
    $matched = [];

    // Match entire group if the group name matches the search
    if (stripos($group, $search) !== false && $search !== '') {
        foreach ($services as $service) {
            $matched[] = [
                'id' => $service,
                'text' => $service
            ];
        }
    } else {
        // Otherwise, check each individual service
        foreach ($services as $service) {
            if ($search === '' || stripos($service, $search) !== false) {
                $matched[] = [
                    'id' => $service,
                    'text' => $service
                ];
            }
        }
    }

    if (!empty($matched)) {
        $results[] = [
            'text' => $group,
            'children' => $matched
        ];
    }
}

echo json_encode(['results' => $results]);
