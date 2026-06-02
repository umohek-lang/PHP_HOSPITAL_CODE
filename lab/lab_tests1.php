<?php
require '../includes/auth.php'; // For authentication
require '../db.php';   // For database connection

try {
    if (!isset($pdo)) {
        $host = "localhost";
        $dbname = "ablehand";
        $username = "root";
        $password = "";
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

$stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients");

    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_POST['submit'])) {
        $patient_id = $_POST['patient_id'];
        $test_name = $_POST['test_name'];
        $test_date = $_POST['test_date'];
        $result = $_POST['result'];
        $status = $_POST['status'];
        $requested_by = $_POST['requested_by'];

        $report_file = '';
        if (!empty($_FILES['report_file']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $report_file = $targetDir . basename($_FILES['report_file']['name']);
            move_uploaded_file($_FILES['report_file']['tmp_name'], $report_file);
        }

        $stmt = $pdo->prepare("INSERT INTO lab_tests (patient_id, test_name, test_date, result, status, report_file, requested_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $test_name, $test_date, $result, $status, $report_file, $requested_by]);

        echo "<div class='alert alert-success m-3'>Lab Test Added Successfully!</div>";
        header("Location: view_lab_test.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Lab Test Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <style>
    body {
      background: linear-gradient(to right, #add8e6, #e0f7fa); /* Light blue gradient */
      min-height: 100vh;
      padding-top: 50px;
    }
    .card-custom {
        max-width: 900px;
        margin: auto;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease-in-out;
    }
    .card-custom:hover {
        transform: scale(1.02);
    }
    .card-body-custom {
        padding: 20px;
    }
    .form-control:focus {
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        border-color: #0056b3;
    }


    /*SELECT2 STYLING */
    .select2-container--default .select2-selection--single {
    height: 38px;
    padding: 6px 12px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

  </style>
</head>
<body>
  <div class="container">
    <div class="card card-custom">
      <div class="card-header text-center">
        <h3>Add Lab Test</h3>
      </div>
      <div class="card-body card-body-custom">
        <form action="lab_tests.php" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Patient ID</label>
         <!--        <select class="form-select" name="patient_id" required>
  <option value="">Select Patient</option>
  <?php foreach ($patients as $patient): ?>
    <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
      <?php echo htmlspecialchars($patient['patient_id'] . ' - ' . $patient['full_name']); ?>
    </option>
  <?php endforeach; ?>
</select> -->

<select class="form-select" id="patient-select" name="patient_id" required></select>


              </div>
              <div class="mb-3">
                <label class="form-label">Test Name</label>
                <input type="text" class="form-control" name="test_name" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Test Date</label>
                <input type="date" class="form-control" name="test_date" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Requested By</label>
                <input type="text" class="form-control" name="requested_by" required />
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Result</label>


                <!-- Lab Test Selector -->
<div class="mb-3">
  <label class="form-label">Select Lab Test</label>
  <select class="form-select" id="lab-test-selector">
  <option value="">-- Select a Test --</option>
  <option value="fbc">Full Blood Count</option>
  <option value="widal">Widal Test</option>
  <option value="kft">Kidney Function Test</option>
  <option value="lft">Liver Function Test</option>
  <option value="lipid">Lipid Profile</option>
  <option value="serology">Serology</option>
  <option value="urinalysis">Urinalysis / MCS</option>
  <option value="stool">Stool MCS / Microscopy</option>
  <option value="hvs">HVS</option>
  <option value="hsv">Herpes Simplex Virus</option>
  <option value="hba1c">HbA1c</option>
  <option value="hbv_profile">HBV Profile</option>
  <option value="hiv_test">HIV Test</option>
  <option value="hormonal">Hormonal Test</option>
  <option value="psa">PSA</option>
  <option value="rbs_fbs">RBS / FBS</option>
  <option value="esr">ESR</option>
  <option value="h_pylori">H. Pylori</option>
  <option value="blood_group">Blood Group</option>
  <option value="blood_sugar">Blood Sugar</option>

  <option value="genotype">Genotype</option>
  <option value="sputum">Sputum MCS</option>
  <option value="malaria">Malaria Parasite</option>
  <option value="reticulocyte">Reticulocyte Count</option>
<option value="hb_electrophoresis">Hemoglobin Electrophoresis</option>
<option value="pbs">Peripheral Blood Smear</option>
<option value="blood_culture">Blood Culture</option>
<option value="wound_swab">Wound Swab</option>
<option value="afb">AFB (Acid-Fast Bacilli)</option>
<option value="asotitre">ASO Titre</option>
<option value="rheumatoid_factor">Rheumatoid Factor (RF)</option>
<option value="microfilaria">Microfilaria</option>
<option value="pt_hcg">Pregnancy Test (PT - HCG)</option>
<option value="papsmear">Pap Smear</option>
<option value="coombs">Coombs Test (Direct/Indirect)</option>
<option value="sfa">Seminal Fluid Analysis (SFA)</option>
<option value="csf">CSF (Cerebrospinal Fluid)</option>
<option value="fob">Fecal Occult Blood (FOB)</option>

</select>

</div>

<!-- Dynamic Test Fields Will Appear Here -->
<div id="test-components-container"></div>

<!-- Hidden field to hold all inputted results -->
<input type="hidden" name="result" id="result-field">


                


                <!-- <textarea class="form-control" name="result" rows="3"></textarea> -->
              </div>
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" required>
                  <option value="">Select Status</option>
                  <option value="Pending">Pending</option>
                  <option value="Completed">Completed</option>
                  <option value="In Progress">In Progress</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Upload Report (PDF/Image)</label>
                <input type="file" class="form-control" name="report_file" />
              </div>
            </div>
          </div>
          <div class="text-start">
            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- SELECT2 AND AJAX FOR DROP DOWN -->
  <!-- jQuery & Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
  $(document).ready(function () {
    $('#patient-select').select2({
      placeholder: 'Search Patient by Name or ID',
      ajax: {
        url: 'ajax_search_patients.php',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return { results: data.results };
        },
        cache: true
      },
      minimumInputLength: 1
    });
  });
</script>



<!-- ALL LAB TEST -->
 <script>
  // Define all tests and their fields
  const testFields = {
    hiv_test: {
  title: "HIV Test",
  fields: ["HIV I", "HIV II", "Interpretation"]
},

  fbc: {
    title: "Full Blood Count",
    fields: ["WBC", "LYM", "MID", "GRA", "LYM%", "MID%", "GRA%", "RBC", "HGB", "HCT", "MCV", "MCH", "MCHC", "RDWc", "PLT", "PCT", "MPV", "PDWc", "P-LCC", "P-LCR", "DIAGNOSTIC FLAGS", "WARNING", "LYSE", "PRVW", "PRVR"]
  },
  widal: {
    title: "Widal Test",
    fields: ["Salmonella typhi O", "Salmonella typhi H", "Salmonella paratyphi A", "Salmonella paratyphi B"]
  },
  kft: {
    title: "Kidney Function Test",
    fields: ["Urea", "Creatinine", "Sodium", "Potassium", "Chloride", "Bicarbonate", "Calcium"]
  },
  lft: {
    title: "Liver Function Test",
    fields: ["T. Bilirubin (0-1.0 mg/dL)", "Direct Bilirubin (0-0.3 mg/dL)", "SGOT (0-31 U/L)", "SGPT (0-31 U/L)", "Alkaline Phosphatase (64-306 U/L)", "Total Protein (6.6-8.7 g/dL)", "Albumin (3.6-5.5 g/dL)"]
  },
  lipid: {
    title: "Lipid Profile",
    fields: ["Cholesterol (<200 mg/dL)", "Triglycerides (35-135 mg/dL)", "HDL (>35 mg/dL)", "LDL (<130 mg/dL)"]
  },
  serology: {
    title: "Serology",
    fields: ["HBsAg", "HCV", "VDRL", "HIV"]
  },
  urinalysis: {
    title: "Urinalysis / M/C/S",
    fields: ["Color", "Appearance", "pH", "Protein", "Glucose", "Ketones", "Nitrite", "Leukocyte", "Bacteria", "Epithelial cells", "Casts", "Crystals"]
  },
  stool: {
    title: "Stool M/C/S / Microscopy",
    fields: ["Color", "Consistency", "Ova", "Cyst", "Parasites", "Pus Cells", "RBC"]
  },
  hvs: {
    title: "High Vaginal Swab (HVS)",
    fields: ["Gram Reaction", "Yeast", "Trichomonas", "Bacteria", "Culture Result", "Sensitivity"]
  },
  hsv: {
    title: "Herpes Simplex Virus",
    fields: ["HSV IgG 1", "HSV IgG 2", "HSV IgM 1", "HSV IgM 2"]
  },
  hba1c: {
    title: "HbA1c",
    fields: ["HbA1c (%)", "Interpretation (Normal <6.0%, Good 6.0-6.8%, Fair 6.8-7.65%, Poor >7.65)"]
  },
  hbv_profile: {
    title: "HBV Profile",
    fields: ["HBsAg", "HBsAb", "HBeAg", "HBeAb", "HBcAb"]
  },
  hormonal: {
    title: "Hormonal Test",
    fields: ["Prolactin", "LH", "FSH", "Estrogen", "Progesterone"]
  },
  psa: {
    title: "Prostate Specific Antigen (PSA)",
    fields: ["Total PSA", "Free PSA", "PSA Ratio"]
  },
  rbs_fbs: {
    title: "RBS / FBS",
    fields: ["Random Blood Sugar (mg/dL)", "Fasting Blood Sugar (mg/dL)"]
  },
  esr: {
    title: "ESR",
    fields: ["Erythrocyte Sedimentation Rate (mm/hr)"]
  },
  h_pylori: {
    title: "H. Pylori",
    fields: ["H. Pylori Antigen", "H. Pylori Antibody"]
  },
  blood_group: {
    title: "Blood Group",
    fields: ["Blood Group", "Rh Factor"]
  },
  genotype: {
    title: "Genotype",
    fields: ["Genotype"]
  },
  sputum: {
    title: "Sputum M/C/S",
    fields: ["Appearance", "Color", "Culture", "Sensitivity", "Organism Isolated"]
  },
  malaria: {
    title: "Malaria Parasite",
    fields: ["MP Test Result", "Parasite Species", "Parasitemia Level"]
  },
  blood_sugar: {
  title: "Blood Sugar",
  fields: [
    "Fasting Blood Sugar (mg/dL)",
    "2 Hours Postprandial (mg/dL)",
    "Random Blood Sugar (mg/dL)"
  ]
},
reticulocyte: {
  title: "Reticulocyte Count",
  fields: ["Reticulocyte %", "Reticulocyte Absolute Count"]
},
hb_electrophoresis: {
  title: "Hemoglobin Electrophoresis",
  fields: ["HbA", "HbF", "HbS", "HbC", "Other Variants"]
},
pbs: {
  title: "Peripheral Blood Smear",
  fields: ["RBC Morphology", "WBC Morphology", "Platelet Morphology", "Parasite Seen"]
},
blood_culture: {
  title: "Blood Culture",
  fields: ["Culture Result", "Organism Isolated", "Sensitivity"]
},
wound_swab: {
  title: "Wound Swab",
  fields: ["Culture Result", "Organism Isolated", "Sensitivity"]
},
afb: {
  title: "Acid-Fast Bacilli (AFB)",
  fields: ["AFB Result", "Number of Bacilli", "Grade"]
},
asotitre: {
  title: "ASO Titre",
  fields: ["ASO Titre (IU/mL)"]
},
rheumatoid_factor: {
  title: "Rheumatoid Factor (RF)",
  fields: ["RF Level", "Interpretation"]
},
microfilaria: {
  title: "Microfilaria",
  fields: ["Test Result", "Species Detected", "Parasite Load"]
},
pt_hcg: {
  title: "Pregnancy Test (PT - HCG)",
  fields: ["Urine HCG Result", "Serum HCG Result"]
},
papsmear: {
  title: "Pap Smear",
  fields: ["Cellular Result", "Inflammation", "Dysplasia", "HPV Status"]
},
coombs: {
  title: "Coombs Test (Direct/Indirect)",
  fields: ["Direct Coombs Result", "Indirect Coombs Result", "Interpretation"]
},
sfa: {
  title: "Seminal Fluid Analysis (SFA)",
  fields: ["Volume", "pH", "Motility", "Morphology", "Count", "WBCs", "RBCs"]
},
csf: {
  title: "Cerebrospinal Fluid (CSF)",
  fields: ["Appearance", "Protein", "Glucose", "WBCs", "RBCs", "Culture Result"]
},
fob: {
  title: "Fecal Occult Blood (FOB)",
  fields: ["FOB Result", "Method Used", "Interpretation"]
}

};


  // When a test is selected
  document.getElementById("lab-test-selector").addEventListener("change", function () {
    const selected = this.value;
    const container = document.getElementById("test-components-container");
    container.innerHTML = ""; // Clear previous

    if (testFields[selected]) {
      const { title, fields } = testFields[selected];
      const section = document.createElement("div");
      section.className = "border p-3 mb-3 bg-light rounded";

      const heading = document.createElement("h5");
      heading.textContent = title;
      section.appendChild(heading);

      fields.forEach(field => {
        const group = document.createElement("div");
        group.className = "mb-2";

        const label = document.createElement("label");
        label.className = "form-label";
        label.textContent = field;

        const input = document.createElement("input");
        input.className = "form-control";
        input.name = `result_values[${field}]`;
        input.setAttribute("placeholder", `Enter ${field}`);

        group.appendChild(label);
        group.appendChild(input);
        section.appendChild(group);
      });

      container.appendChild(section);
    }
  });

  // When submitting the form, gather result inputs into one hidden field
  document.querySelector("form").addEventListener("submit", function () {
    const resultInputs = document.querySelectorAll("#test-components-container input");
    const resultData = {};

    resultInputs.forEach(input => {
      const key = input.name.replace("result_values[", "").replace("]", "");
      resultData[key] = input.value;
    });

    document.getElementById("result-field").value = JSON.stringify(resultData);
  });
</script>


</body>
</html>
