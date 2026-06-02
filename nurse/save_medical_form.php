<?php
require '../includes/auth.php'; // Assumes $pdo is defined here as your PDO connection

require '../db.php'; 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Collect surgical history
        $surgical_history = [];
        if (!empty($_POST['surgery_name'])) {
            foreach ($_POST['surgery_name'] as $i => $name) {
                if (!empty($name)) {
                    $surgical_history[] = [
                        'name' => $name,
                        'date' => $_POST['surgery_date'][$i] ?? '',
                        'complications' => $_POST['surgery_complications'][$i] ?? ''
                    ];
                }
            }
        }

        // Collect medication history
        $medications = [];
        if (!empty($_POST['med_name'])) {
            foreach ($_POST['med_name'] as $i => $med) {
                if (!empty($med)) {
                    $medications[] = [
                        'name' => $med,
                        'dosage' => $_POST['med_dosage'][$i] ?? '',
                        'frequency' => $_POST['med_frequency'][$i] ?? '',
                        'duration' => $_POST['med_duration'][$i] ?? '',
                        'indication' => $_POST['med_indication'][$i] ?? ''
                    ];
                }
            }
        }

        // Encode as JSON
        $surgical_json = json_encode($surgical_history);
        $medications_json = json_encode($medications);

        // Checkbox group handling
        $allergies = isset($_POST['nkda']) ? 'No Known Drug Allergies' : ($_POST['allergies'] ?? '');
        $family_history = isset($_POST['fh']) ? implode(", ", $_POST['fh']) : '';

        // Structured sections
        $social_history = json_encode([
            'smoking' => $_POST['smoking'] ?? '',
            'smoking_packs' => $_POST['smoking_packs'] ?? '',
            'alcohol' => $_POST['alcohol'] ?? '',
            'alcohol_details' => $_POST['alcohol_details'] ?? '',
            'drugs' => $_POST['drugs'] ?? '',
            'drugs_specify' => $_POST['drugs_specify'] ?? '',
            'sexual_activity' => $_POST['sexual_activity'] ?? '',
            'partners' => $_POST['partners'] ?? '',
            'protection' => $_POST['protection'] ?? '',
            'work_hazards' => $_POST['work_hazards'] ?? ''
        ]);

        $immunization = json_encode([
            'childhood' => $_POST['childhood_vaccines'] ?? '',
            'tetanus' => $_POST['tetanus'] ?? '',
            'tetanus_date' => $_POST['tetanus_date'] ?? '',
            'hepatitis_b' => $_POST['hepatitis_b'] ?? '',
            'covid19' => $_POST['covid19'] ?? '',
            'others' => $_POST['other_vaccines'] ?? ''
        ]);

        $ros = json_encode([
            'general' => $_POST['ros_general'] ?? '',
            'general_desc' => $_POST['ros_general_desc'] ?? '',
            'cardio' => $_POST['ros_cardio'] ?? '',
            'cardio_desc' => $_POST['ros_cardio_desc'] ?? '',
            'resp' => $_POST['ros_resp'] ?? '',
            'resp_desc' => $_POST['ros_resp_desc'] ?? '',
            'gi' => $_POST['ros_gi'] ?? '',
            'gi_desc' => $_POST['ros_gi_desc'] ?? '',
            'gu' => $_POST['ros_gu'] ?? '',
            'gu_desc' => $_POST['ros_gu_desc'] ?? '',
            'ns' => $_POST['ros_ns'] ?? '',
            'ns_desc' => $_POST['ros_ns_desc'] ?? '',
            'msk' => $_POST['ros_msk'] ?? '',
            'msk_desc' => $_POST['ros_msk_desc'] ?? '',
            'derm' => $_POST['ros_derm'] ?? '',
            'derm_desc' => $_POST['ros_derm_desc'] ?? '',
            'psych' => $_POST['ros_psych'] ?? '',
            'psych_desc' => $_POST['ros_psych_desc'] ?? ''
        ]);

        $obstetric = json_encode([
            'gravida' => $_POST['gravida'] ?? '',
            'para' => $_POST['para'] ?? '',
            'abortions' => $_POST['abortions'] ?? '',
            'lmp' => $_POST['lmp'] ?? '',
            'cycle' => $_POST['menstrual_cycle'] ?? '',
            'contraceptive_use' => $_POST['contraceptive_use'] ?? '',
            'type' => $_POST['contraceptive_type'] ?? ''
        ]);

        $physical_exam = json_encode([
            'temp' => $_POST['temp'] ?? '',
            'bp' => $_POST['bp'] ?? '',
            'hr' => $_POST['hr'] ?? '',
            'rr' => $_POST['rr'] ?? '',
            'spo2' => $_POST['spo2'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'height' => $_POST['height'] ?? '',
            'bmi' => $_POST['bmi'] ?? ''
        ]);

        // SQL INSERT with new fields
        $sql = "INSERT INTO medical_historys (
            full_name, patient_id, dob, age, gender, marital_status, occupation,
            address, phone, visit_date, chief_complaint, hpi,
            surgical_history, medications,
            allergies, family_history, social_history,
            immunization, ros, obstetric, physical_exam,
            assessment_plan, clinician_name, clinician_designation, clinician_date, clinician_signature, photo
        ) VALUES (
            :full_name, :patient_id, :dob, :age, :gender, :marital_status, :occupation,
            :address, :phone, :visit_date, :chief_complaint, :hpi,
            :surgical_history, :medications,
            :allergies, :family_history, :social_history,
            :immunization, :ros, :obstetric, :physical_exam,
            :assessment_plan, :clinician_name, :clinician_designation, :clinician_date, :clinician_signature, :photo
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $_POST['full_name'],
            ':patient_id' => $_POST['patient_id'],
            ':dob' => $_POST['dob'],
            ':age' => $_POST['age'],
            ':gender' => $_POST['gender']?? '',
            ':marital_status' => $_POST['marital_status'],
            ':occupation' => $_POST['occupation'],
            ':address' => $_POST['address'],
            ':phone' => $_POST['phone'],
            ':visit_date' => $_POST['visit_date'],
            ':chief_complaint' => $_POST['chief_complaint'],
            ':hpi' => $_POST['hpi'],
            ':surgical_history' => $surgical_json,
            ':medications' => $medications_json,
            ':allergies' => $allergies,
            ':family_history' => $family_history,
            ':social_history' => $social_history,
            ':immunization' => $immunization,
            ':ros' => $ros,
            ':obstetric' => $obstetric,
            ':physical_exam' => $physical_exam,
            ':assessment_plan' => $_POST['clinical_assessment'],
            ':clinician_name' => $_POST['clinician_name'],
            ':clinician_designation' => $_POST['clinician_designation'],
            ':clinician_date' => $_POST['clinician_date'],
            ':clinician_signature' => $_POST['clinician_signature'],
            ':photo' => $_POST['photo'] ?? ''

        ]);

        echo "<div style='padding: 15px; background: #e9ffe9; color: #27632a;'>✔️ Full medical history saved successfully.</div>";
    } catch (Exception $e) {
        echo "<div style='padding: 15px; background: #ffe9e9; color: #8b0000;'>❌ Error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "Invalid request method.";
}
?>
