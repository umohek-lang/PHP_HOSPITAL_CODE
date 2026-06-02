<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Paid Bills — Angelora Hospital</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-800:  #1e40af;
      --blue-700:  #1d4ed8;
      --blue-600:  #2563eb;
      --blue-500:  #3b82f6;
      --blue-400:  #60a5fa;
      --blue-300:  #93c5fd;
      --blue-200:  #bfdbfe;
      --blue-100:  #dbeafe;
      --blue-50:   #eff6ff;
      --blue-glow: rgba(37,99,235,.12);

      --white:   #ffffff;
      --gray-50: #f8fafc; --gray-100:#f1f5f9; --gray-200:#e2e8f0;
      --gray-300:#cbd5e1; --gray-400:#94a3b8; --gray-500:#64748b;
      --gray-600:#475569; --gray-700:#334155; --gray-800:#1e293b; --gray-900:#0f172a;

      --green:   #16a34a; --green-bg:#dcfce7; --green-100:#bbf7d0;

      --radius:    12px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 2px 8px rgba(0,0,0,.04);
      --shadow:    0 4px 16px rgba(37,99,235,.09), 0 1px 4px rgba(0,0,0,.05);
      --shadow-lg: 0 12px 36px rgba(37,99,235,.14), 0 2px 8px rgba(0,0,0,.06);
    }

    html, body { min-height: 100vh; font-family: 'Sora', sans-serif; background: var(--gray-50); color: var(--gray-800); }
    ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: var(--gray-100); } ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 10px; }

    /* ════ TOP BAR ═══════════════════ */
    .topbar {
      position: sticky; top: 0; z-index: 100; height: 64px;
      background: var(--white); border-bottom: 1px solid var(--gray-200);
      box-shadow: 0 1px 8px rgba(0,0,0,.06);
      display: flex; align-items: center; justify-content: space-between; padding: 0 36px;
    }
    .topbar-brand { display: flex; align-items: center; gap: 12px; }
    .brand-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-400));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 10px rgba(37,99,235,.3);
    }
    .brand-mark i { font-size: 17px; color: white; }
    .brand-text { display: flex; flex-direction: column; gap: 1px; }
    .brand-name { font-family: 'Instrument Serif', serif; font-size: 17px; color: var(--gray-900); line-height: 1; }
    .brand-sub  { font-size: 10px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--blue-600); line-height: 1; }

    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .date-pill {
      display: flex; align-items: center; gap: 7px;
      padding: 6px 14px; border-radius: 20px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      font-size: 12px; color: var(--blue-700); font-weight: 500;
    }
    .date-pill i { color: var(--blue-500); }
    .back-btn {
      display: flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 8px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-600); font-family: 'Sora', sans-serif;
      font-size: 12.5px; font-weight: 500; text-decoration: none; transition: all .18s;
    }
    .back-btn:hover { background: var(--blue-50); border-color: var(--blue-200); color: var(--blue-600); }

    /* ════ PAGE ══════════════════════ */
    .page { max-width: 760px; margin: 0 auto; padding: 40px 24px 72px; }

    /* Breadcrumb */
    .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--gray-400); margin-bottom: 10px; }
    .breadcrumb a { color: var(--blue-600); text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: 10px; }

    .page-title { font-family: 'Instrument Serif', serif; font-size: clamp(1.6rem,3vw,2.1rem); font-weight: 400; color: var(--gray-900); }
    .page-title em { font-style: italic; color: var(--blue-600); }
    .page-sub { font-size: 13px; color: var(--gray-500); margin-top: 5px; margin-bottom: 28px; }

    /* ════ INFO CHIPS ════════════════ */
    .filter-chips { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .chip {
      display: flex; align-items: center; gap: 8px;
      padding: 8px 14px; border-radius: 20px;
      background: var(--white); border: 1.5px solid var(--gray-200);
      font-size: 12px; font-weight: 600; color: var(--gray-600);
      box-shadow: var(--shadow-sm);
    }
    .chip i { font-size: 13px; }
    .chip.c-blue  { background: var(--blue-50); border-color: var(--blue-100); color: var(--blue-700); }
    .chip.c-green { background: var(--green-bg); border-color: var(--green-100); color: var(--green); }
    .chip.c-gray  { background: var(--gray-100); border-color: var(--gray-200); color: var(--gray-500); }

    /* ════ FORM CARD ═════════════════ */
    .form-card {
      background: var(--white); border: 1.5px solid var(--gray-200);
      border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);
    }
    .form-card-header {
      padding: 16px 28px; border-bottom: 1px solid var(--gray-100);
      background: linear-gradient(135deg, var(--blue-800), var(--blue-600));
      display: flex; align-items: center; gap: 10px;
    }
    .form-card-header i { font-size: 18px; color: rgba(255,255,255,.85); }
    .form-card-title { font-size: 14.5px; font-weight: 700; color: white; }
    .form-card-sub   { font-size: 12px; color: rgba(255,255,255,.65); margin-top: 1px; }

    .form-body { padding: 28px; display: flex; flex-direction: column; gap: 22px; }

    /* ════ FIELD ═════════════════════ */
    .field { display: flex; flex-direction: column; gap: 7px; }
    .field label { font-size: 11.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--gray-500); display: flex; align-items: center; gap: 5px; }
    .field label .req { color: #dc2626; }

    .fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

    /* Inputs */
    input[type="date"] {
      width: 100%; padding: 12px 14px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; color: var(--gray-800);
      font-family: 'Sora', sans-serif; font-size: 13.5px;
      outline: none; transition: border-color .18s, box-shadow .18s, background .18s;
    }
    input[type="date"]:focus { border-color: var(--blue-400); background: var(--white); box-shadow: 0 0 0 3px var(--blue-glow); }

    /* Custom select (filter type) */
    .styled-select {
      width: 100%; padding: 12px 36px 12px 14px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 9px; color: var(--gray-800);
      font-family: 'Sora', sans-serif; font-size: 13.5px;
      outline: none; appearance: none; cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 13px center;
      transition: border-color .18s, box-shadow .18s, background .18s;
    }
    .styled-select:focus { border-color: var(--blue-400); background-color: var(--white); box-shadow: 0 0 0 3px var(--blue-glow); }

    /* Date field slide-in */
    .date-field-wrap {
      overflow: hidden; max-height: 0;
      transition: max-height .3s ease, opacity .3s ease;
      opacity: 0; pointer-events: none;
    }
    .date-field-wrap.visible { max-height: 120px; opacity: 1; pointer-events: auto; }

    /* ════ SELECT2 WHITE THEME ════════ */
    .select2-container--default .select2-selection--single {
      background: var(--gray-50) !important; border: 1.5px solid var(--gray-200) !important;
      border-radius: 9px !important; height: 46px !important;
      display: flex !important; align-items: center !important;
    }
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default .select2-selection--single:focus {
      border-color: var(--blue-400) !important; box-shadow: 0 0 0 3px var(--blue-glow) !important; background: var(--white) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--gray-800) !important; font-family: 'Sora', sans-serif !important;
      font-size: 13.5px !important; font-weight: 500 !important;
      line-height: 46px !important; padding-left: 14px !important;
    }
    .select2-container--default .select2-selection__placeholder { color: var(--gray-400) !important; font-weight: 400 !important; }
    .select2-container--default .select2-selection__arrow { height: 46px !important; right: 12px !important; }
    .select2-dropdown {
      background: var(--white) !important; border: 1.5px solid var(--gray-200) !important;
      border-radius: 10px !important; box-shadow: var(--shadow-lg) !important; font-family: 'Sora', sans-serif !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
      background: var(--gray-50) !important; border: 1px solid var(--gray-200) !important;
      border-radius: 7px !important; color: var(--gray-800) !important;
      font-family: 'Sora', sans-serif !important; font-size: 13px !important; padding: 8px 12px !important;
    }
    .select2-results__option { color: var(--gray-700) !important; font-size: 13.5px !important; padding: 10px 14px !important; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--blue-50) !important; color: var(--blue-700) !important; }
    .select2-container--default .select2-results__option[aria-selected=true] { background: var(--blue-100) !important; color: var(--blue-800) !important; }

    /* ════ FORM FOOTER ═══════════════ */
    .form-footer {
      padding: 18px 28px; border-top: 1px solid var(--gray-100); background: var(--gray-50);
      display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .form-footer-note { font-size: 12px; color: var(--gray-400); display: flex; align-items: center; gap: 6px; }
    .form-footer-note i { color: var(--blue-400); }

    .btn-submit {
      display: flex; align-items: center; gap: 8px;
      padding: 12px 28px; border-radius: 9px; border: none;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      color: white; font-family: 'Sora', sans-serif;
      font-size: 13.5px; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(37,99,235,.3);
      transition: opacity .18s, transform .15s, box-shadow .18s;
      position: relative; overflow: hidden;
    }
    .btn-submit::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,.1) 0%,transparent 60%); }
    .btn-submit:hover { opacity:.95; transform:translateY(-1px); box-shadow:0 8px 24px rgba(37,99,235,.4); }
    .btn-submit:active { transform:translateY(0); }

    /* ════ RESPONSIVE ══════════════════ */
    @media (max-width: 600px) {
      .topbar { padding: 0 16px; } .date-pill { display: none; }
      .page { padding: 20px 14px 52px; }
      .fields-row { grid-template-columns: 1fr; }
      .form-body { padding: 20px; }
      .form-footer { flex-direction: column; align-items: stretch; }
      .btn-submit { justify-content: center; }
      .filter-chips { gap: 7px; }
    }
  </style>
</head>
<body>

<!-- ════ TOP BAR ════════════════════════ -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-mark"><i class="bi bi-hospital"></i></div>
    <div class="brand-text">
      <span class="brand-name">Angelora</span>
      <span class="brand-sub">Billing System</span>
    </div>
  </div>
  <div class="topbar-right">
    <div class="date-pill"><i class="bi bi-calendar3"></i>
      <script>document.write(new Date().toLocaleDateString('en-NG',{weekday:'short',day:'numeric',month:'short',year:'numeric'}))</script>
    </div>
    <a href="bill_dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
</header>

<!-- ════ PAGE ════════════════════════════ -->
<div class="page">

  <div class="breadcrumb">
    <a href="dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="bill_dashboard.php">Billing</a>
    <i class="bi bi-chevron-right"></i>
    <span>View Paid Bills</span>
  </div>
  <h1 class="page-title">View <em>Paid Bills</em></h1>
  <p class="page-sub">Search for a patient and filter by date to view their paid billing records.</p>

  <!-- Info chips -->
  <div class="filter-chips">
    <div class="chip c-blue"><i class="bi bi-calendar-check"></i> Filter by specific date</div>
    <div class="chip c-green"><i class="bi bi-calendar-week"></i> This week</div>
    <div class="chip c-gray"><i class="bi bi-calendar-year"></i> This year</div>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <div class="form-card-header">
      <i class="bi bi-check2-circle"></i>
      <div>
        <div class="form-card-title">Bill Lookup</div>
        <div class="form-card-sub">Select a patient and a time filter, then click View Paid Bills</div>
      </div>
    </div>

    <form method="get" action="view_paid_bills.php" id="billForm">
      <div class="form-body">

        <!-- Patient -->
        <div class="field">
          <label><i class="bi bi-person-fill"></i> Patient <span class="req">*</span></label>
          <select name="patient_id" id="patient_id" required></select>
        </div>

        <!-- Filter row -->
        <div class="fields-row">
          <div class="field">
            <label><i class="bi bi-funnel-fill"></i> Filter By <span class="req">*</span></label>
            <select name="filter_type" id="filter_type" class="styled-select" required>
              <option value="">— Select filter —</option>
              <option value="date">Specific Date</option>
              <option value="week">This Week</option>
              <option value="year">This Year</option>
            </select>
          </div>

          <!-- Date picker (shown only when needed) -->
          <div class="field">
            <label><i class="bi bi-calendar3"></i> Select Date</label>
            <div class="date-field-wrap" id="dateBox">
              <input type="date" name="bill_date" id="bill_date">
            </div>
            <div id="datePlaceholder" style="font-size:13px;color:var(--gray-400);padding-top:13px;">
              Choose "Specific Date" to enable this field.
            </div>
          </div>
        </div>

      </div>

      <div class="form-footer">
        <span class="form-footer-note">
          <i class="bi bi-shield-check"></i>
          Only paid bills are shown in results.
        </span>
        <button type="submit" class="btn-submit">
          <i class="bi bi-search"></i> View Paid Bills
        </button>
      </div>
    </form>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {

  // Patient Select2 with AJAX search
  $('#patient_id').select2({
    placeholder: 'Search patient name…',
    allowClear: true,
    width: '100%',
    ajax: {
      url: 'ajax_search_patients.php',
      dataType: 'json',
      delay: 250,
      data: function (params) { return { term: params.term }; },
      processResults: function (data) { return data; },
      cache: true
    }
  });

  // Show/hide date field with animation
  $('#filter_type').on('change', function () {
    const isDate = $(this).val() === 'date';
    $('#dateBox').toggleClass('visible', isDate);
    $('#datePlaceholder').toggle(!isDate);
    if (!isDate) $('#bill_date').val('');
  });

  // Require date if filter = date
  $('#billForm').on('submit', function (e) {
    if ($('#filter_type').val() === 'date' && !$('#bill_date').val()) {
      e.preventDefault();
      $('#bill_date').focus();
      $('#dateBox').css('box-shadow', '0 0 0 3px rgba(220,38,38,.15)');
      setTimeout(() => $('#dateBox').css('box-shadow', ''), 1500);
    }
  });

});
</script>
</body>
</html>