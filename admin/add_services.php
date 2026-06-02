<?php
require '../includes/auth.php';
require '../db.php';
checkRole(1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catalog Management — Angelora Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue-900: #0f2d6b;
      --blue-800: #1a3f8f;
      --blue-700: #1d4ed8;
      --blue-600: #2563eb;
      --blue-500: #3b82f6;
      --blue-400: #60a5fa;
      --blue-300: #93c5fd;
      --blue-200: #bfdbfe;
      --blue-100: #dbeafe;
      --blue-50:  #eff6ff;
      --white:    #ffffff;
      --gray-50:  #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-400: #94a3b8;
      --gray-500: #64748b;
      --gray-700: #334155;
      --gray-900: #0f172a;
      --green-600: #059669;
      --green-50:  #ecfdf5;
      --green-100: #d1fae5;
      --red-600:   #dc2626;
      --red-50:    #fef2f2;
      --red-100:   #fee2e2;
      --violet-600: #7c3aed;
      --violet-50:  #f5f3ff;
      --violet-100: #ede9fe;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 20px rgba(15,45,107,.10), 0 2px 8px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 14px rgba(15,45,107,.07);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }

    /* subtle background */
    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 600px 400px at 15% 15%, rgba(37,99,235,.06) 0%, transparent 70%),
        radial-gradient(ellipse 500px 350px at 85% 85%, rgba(96,165,250,.05) 0%, transparent 70%);
    }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 4px; }

    /* ── PAGE ── */
    .page {
      position: relative; z-index: 1;
      max-width: 920px; margin: 0 auto;
      padding: 48px 28px 72px;
    }

    /* ── TOP BADGE ── */
    .top-badge {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 5px 14px;
      font-size: .71rem; font-weight: 700; color: var(--blue-700);
      text-transform: uppercase; letter-spacing: .08em;
      margin-bottom: 20px;
    }

    /* ── PAGE HEADER ── */
    .page-header { text-align: center; margin-bottom: 52px; }
    .page-header h1 {
      font-size: 2rem; font-weight: 800; color: var(--gray-900);
      letter-spacing: -.03em; line-height: 1.2; margin-bottom: 10px;
    }
    .page-header h1 em { font-style: italic; color: var(--blue-600); }
    .page-header p {
      font-size: .9rem; color: var(--gray-400); max-width: 440px; margin: 0 auto;
      line-height: 1.6;
    }

    /* ── CARDS GRID ── */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 22px;
    }

    /* ── CATALOG CARD ── */
    .catalog-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 18px;
      padding: 32px 26px 28px;
      text-decoration: none;
      display: flex; flex-direction: column; align-items: center;
      text-align: center; gap: 0;
      position: relative; overflow: hidden;
      transition: transform .25s cubic-bezier(.16,1,.3,1),
                  border-color .22s, box-shadow .22s;
      box-shadow: var(--shadow-sm);
    }

    /* top accent bar */
    .catalog-card::after {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 4px;
      background: var(--c-bar, var(--blue-600));
      transform: scaleX(0); transform-origin: left;
      border-radius: 4px 4px 0 0;
      transition: transform .28s cubic-bezier(.16,1,.3,1);
    }
    .catalog-card:hover::after { transform: scaleX(1); }

    /* bg tint on hover */
    .catalog-card::before {
      content: ''; position: absolute; inset: 0;
      background: var(--c-tint, rgba(37,99,235,.03));
      opacity: 0; transition: opacity .22s;
    }
    .catalog-card:hover::before { opacity: 1; }

    .catalog-card:hover {
      transform: translateY(-6px);
      border-color: var(--c-border, var(--blue-200));
      box-shadow: var(--shadow-lg);
    }

    /* colour variants */
    .card-lab {
      --c-bar:    var(--blue-600);
      --c-border: var(--blue-200);
      --c-tint:   rgba(37,99,235,.03);
      --c-icon-bg: var(--blue-50);
      --c-icon:    var(--blue-600);
    }
    .card-procedure {
      --c-bar:    var(--green-600);
      --c-border: #a7f3d0;
      --c-tint:   rgba(5,150,105,.03);
      --c-icon-bg: var(--green-50);
      --c-icon:    var(--green-600);
    }
    .card-pharmacy {
      --c-bar:    var(--violet-600);
      --c-border: #c4b5fd;
      --c-tint:   rgba(124,58,237,.03);
      --c-icon-bg: var(--violet-50);
      --c-icon:    var(--violet-600);
    }

    /* icon */
    .card-icon-wrap {
      width: 64px; height: 64px; border-radius: 18px;
      background: var(--c-icon-bg, var(--blue-50));
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 18px; position: relative; z-index: 1;
      transition: background .22s, transform .22s;
      border: 1px solid rgba(0,0,0,.04);
    }
    .catalog-card:hover .card-icon-wrap { transform: scale(1.08); }
    .card-icon-wrap i { font-size: 1.75rem; color: var(--c-icon, var(--blue-600)); }

    /* text */
    .card-title {
      font-size: 1rem; font-weight: 800; color: var(--gray-900);
      margin-bottom: 7px; position: relative; z-index: 1;
    }
    .card-desc {
      font-size: .8rem; color: var(--gray-400); line-height: 1.55;
      position: relative; z-index: 1; margin-bottom: 20px;
    }

    /* manage link pill */
    .card-action {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 18px; border-radius: 999px;
      background: var(--c-icon-bg, var(--blue-50));
      border: 1px solid var(--c-border, var(--blue-100));
      font-size: .76rem; font-weight: 700; color: var(--c-icon, var(--blue-700));
      position: relative; z-index: 1;
      transition: background .2s, box-shadow .2s;
    }
    .catalog-card:hover .card-action {
      background: var(--c-icon, var(--blue-600));
      color: #fff; border-color: transparent;
      box-shadow: 0 4px 14px rgba(0,0,0,.15);
    }
    .catalog-card:hover .card-action i { color: #fff; }

    /* ── BACK LINK ── */
    .back-link {
      display: inline-flex; align-items: center; gap: 7px;
      margin-top: 44px; font-size: .8rem; font-weight: 600;
      color: var(--gray-400); text-decoration: none;
      transition: color .18s;
    }
    .back-link:hover { color: var(--blue-600); }

    /* ── RESPONSIVE ── */
    @media (max-width: 700px) {
      .cards-grid { grid-template-columns: 1fr; gap: 16px; }
      .page { padding: 36px 18px 56px; }
      .page-header h1 { font-size: 1.55rem; }
    }
    @media (min-width: 701px) and (max-width: 900px) {
      .cards-grid { grid-template-columns: 1fr 1fr; }
      .cards-grid > :last-child { grid-column: 1 / -1; max-width: 320px; margin: 0 auto; width: 100%; }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- badge -->
  <div style="text-align:center">
    <div class="top-badge">
      <i class="bi bi-shield-fill-check"></i> Admin · Catalog Management
    </div>
  </div>

  <!-- header -->
  <div class="page-header">
    <h1>Manage Medical <em>Catalogs</em></h1>
    <p>Select a category below to add, update, or remove items from the hospital service catalog.</p>
  </div>

  <!-- cards -->
  <div class="cards-grid">

    <a href="manage_catalogs.php?type=lab" class="catalog-card card-lab">
      <div class="card-icon-wrap">
        <i class="bi bi-eyedropper-fill"></i>
      </div>
      <div class="card-title">Lab Investigations</div>
      <div class="card-desc">Add, update or remove lab tests and diagnostic investigations.</div>
      <div class="card-action">
        <i class="bi bi-arrow-right-circle-fill"></i> Manage Lab
      </div>
    </a>

    <a href="manage_catalogs.php?type=procedure" class="catalog-card card-procedure">
      <div class="card-icon-wrap">
        <i class="bi bi-clipboard2-heart-fill"></i>
      </div>
      <div class="card-title">Nursing Procedures</div>
      <div class="card-desc">Manage available nursing procedures and clinical activities.</div>
      <div class="card-action">
        <i class="bi bi-arrow-right-circle-fill"></i> Manage Procedures
      </div>
    </a>

    <a href="manage_catalogs.php?type=pharmacy" class="catalog-card card-pharmacy">
      <div class="card-icon-wrap">
        <i class="bi bi-capsule-pill"></i>
      </div>
      <div class="card-title">Pharmacy Medicines</div>
      <div class="card-desc">Manage the list of available medicines and pharmacy items.</div>
      <div class="card-action">
        <i class="bi bi-arrow-right-circle-fill"></i> Manage Pharmacy
      </div>
    </a>

  </div>

  <!-- back -->
  <div style="text-align:center">
    <a href="dashboard.php" class="back-link">
      <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
  </div>

</div>

</body>
</html>