<?php
require '../db.php';

$labTests          = $pdo->query("SELECT * FROM lab_tests_catalog")->fetchAll();
$nursingProcedures = $pdo->query("SELECT * FROM nursing_procedures_catalog")->fetchAll();
$pharmacyDrugs     = $pdo->query("SELECT * FROM pharmacy_medicines")->fetchAll();

$sections = [
    ['label'=>'Lab Test',    'type'=>'lab',       'data'=>$labTests,          'field'=>'test_name',      'color'=>'blue',   'icon'=>'bi-eyedropper-fill',    'desc'=>'Lab investigations & diagnostics'],
    ['label'=>'Procedure',   'type'=>'procedure', 'data'=>$nursingProcedures, 'field'=>'procedure_name', 'color'=>'green',  'icon'=>'bi-clipboard2-heart-fill','desc'=>'Nursing procedures & clinical activities'],
    ['label'=>'Medicine',    'type'=>'pharmacy',  'data'=>$pharmacyDrugs,     'field'=>'medicine_name',  'color'=>'violet', 'icon'=>'bi-capsule-pill',         'desc'=>'Pharmacy medicines & items'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Medical Catalogs — Angelora Hospital</title>
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
      --green-600: #059669; --green-700: #047857;
      --green-50:  #ecfdf5; --green-100: #d1fae5;
      --green-200: #a7f3d0;
      --violet-600: #7c3aed; --violet-700: #6d28d9;
      --violet-50:  #f5f3ff; --violet-100: #ede9fe;
      --violet-200: #ddd6fe;
      --red-600: #dc2626; --red-700: #b91c1c;
      --red-50:  #fef2f2; --red-100: #fee2e2;
      --shadow-sm: 0 1px 3px rgba(15,45,107,.07), 0 1px 2px rgba(15,45,107,.05);
      --shadow-md: 0 4px 16px rgba(15,45,107,.09), 0 2px 6px rgba(15,45,107,.06);
      --shadow-lg: 0 12px 40px rgba(15,45,107,.13), 0 4px 12px rgba(15,45,107,.07);
    }

    html, body {
      min-height: 100vh;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
    }

    body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 600px 400px at 5% 10%, rgba(37,99,235,.05) 0%, transparent 70%),
        radial-gradient(ellipse 500px 350px at 95% 90%, rgba(96,165,250,.04) 0%, transparent 70%);
    }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--gray-100); }
    ::-webkit-scrollbar-thumb { background: var(--blue-300); border-radius: 4px; }

    /* ── PAGE ── */
    .page {
      position: relative; z-index: 1;
      max-width: 1160px; margin: 0 auto;
      padding: 36px 24px 60px;
    }

    /* ── PAGE HEADER ── */
    .page-header {
      margin-bottom: 32px;
      display: flex; align-items: flex-end; justify-content: space-between;
      gap: 16px; flex-wrap: wrap;
    }
    .ph-left {}
    .ph-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      border-radius: 999px; padding: 4px 12px;
      font-size: .68rem; font-weight: 700; color: var(--blue-700);
      text-transform: uppercase; letter-spacing: .08em;
      margin-bottom: 10px;
    }
    .ph-title {
      font-size: 1.6rem; font-weight: 800; color: var(--gray-900);
      letter-spacing: -.03em; line-height: 1.2;
    }
    .ph-title em { font-style: italic; color: var(--blue-600); }
    .ph-sub { font-size: .82rem; color: var(--gray-400); margin-top: 5px; }
    .ph-counts {
      display: flex; gap: 10px; flex-shrink: 0;
    }
    .count-pill {
      display: flex; align-items: center; gap: 7px;
      padding: 8px 14px; border-radius: 10px;
      background: var(--white); border: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
    }
    .count-pill-dot { width: 8px; height: 8px; border-radius: 50%; }
    .count-pill-num { font-size: .9rem; font-weight: 800; color: var(--gray-900); }
    .count-pill-lbl { font-size: .7rem; color: var(--gray-400); font-weight: 500; }

    /* ── GRID ── */
    .catalogs-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      align-items: start;
    }

    /* ── CATALOG PANEL ── */
    .catalog-panel {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      display: flex; flex-direction: column;
    }

    /* panel header */
    .panel-head {
      padding: 16px 20px;
      border-bottom: 1px solid var(--gray-100);
      display: flex; align-items: center; justify-content: space-between;
    }
    .panel-head.blue   { background: #fafcff; border-bottom-color: var(--blue-100); }
    .panel-head.green  { background: #fafffe; border-bottom-color: var(--green-100); }
    .panel-head.violet { background: #fdfaff; border-bottom-color: var(--violet-100); }

    .panel-head-left { display: flex; align-items: center; gap: 10px; }
    .panel-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; flex-shrink: 0;
    }
    .panel-icon.blue   { background: var(--blue-50);   color: var(--blue-600); }
    .panel-icon.green  { background: var(--green-50);  color: var(--green-600); }
    .panel-icon.violet { background: var(--violet-50); color: var(--violet-600); }

    .panel-title { font-size: .9rem; font-weight: 800; color: var(--gray-900); }
    .panel-sub   { font-size: .7rem; color: var(--gray-400); margin-top: 1px; }

    .item-badge {
      font-size: .68rem; font-weight: 700;
      padding: 3px 10px; border-radius: 999px;
    }
    .item-badge.blue   { background: var(--blue-50);   color: var(--blue-700);   border: 1px solid var(--blue-100); }
    .item-badge.green  { background: var(--green-50);  color: var(--green-700);  border: 1px solid var(--green-100); }
    .item-badge.violet { background: var(--violet-50); color: var(--violet-700); border: 1px solid var(--violet-100); }

    /* add form */
    .add-form-wrap {
      padding: 16px 18px;
      border-bottom: 1px solid var(--gray-100);
      background: var(--gray-50);
    }
    .add-form-label {
      font-size: .65rem; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; color: var(--gray-500); margin-bottom: 8px;
    }
    .add-form-row { display: flex; gap: 8px; }

    .add-input {
      flex: 1; height: 38px;
      padding: 0 13px;
      background: var(--white);
      border: 1.5px solid var(--gray-200);
      border-radius: 9px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .82rem; color: var(--gray-700);
      outline: none;
      transition: border-color .18s, box-shadow .18s;
    }
    .add-input::placeholder { color: var(--gray-300); }
    .add-input:focus { border-color: var(--blue-400); box-shadow: 0 0 0 3px rgba(37,99,235,.09); }

    .btn-add {
      height: 38px; padding: 0 16px;
      border: none; border-radius: 9px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .8rem; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; gap: 5px;
      white-space: nowrap; flex-shrink: 0;
      transition: all .18s;
    }
    .btn-add.blue   { background: var(--blue-600);   color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.25); }
    .btn-add.green  { background: var(--green-600);  color: #fff; box-shadow: 0 3px 10px rgba(5,150,105,.22); }
    .btn-add.violet { background: var(--violet-600); color: #fff; box-shadow: 0 3px 10px rgba(124,58,237,.22); }
    .btn-add:hover  { opacity: .9; transform: translateY(-1px); }
    .btn-add:active { transform: translateY(0); }

    /* items list */
    .items-list {
      flex: 1; overflow-y: auto; max-height: 380px;
    }
    .items-list::-webkit-scrollbar { width: 4px; }
    .items-list::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 4px; }

    .item-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 18px; border-bottom: 1px solid var(--gray-100);
      gap: 10px; transition: background .12s;
    }
    .item-row:last-child { border-bottom: none; }
    .item-row:hover { background: var(--blue-50); }

    .item-name {
      font-size: .82rem; font-weight: 600; color: var(--gray-700);
      flex: 1; line-height: 1.4;
    }
    .item-index {
      font-size: .68rem; color: var(--gray-300);
      font-family: monospace; font-weight: 700;
      margin-right: 6px; flex-shrink: 0;
    }

    .item-actions { display: flex; gap: 5px; flex-shrink: 0; }

    .btn-edit, .btn-del {
      width: 30px; height: 30px; border-radius: 7px;
      display: flex; align-items: center; justify-content: center;
      font-size: .82rem; cursor: pointer; border: 1px solid;
      transition: all .16s;
    }
    .btn-edit {
      background: var(--blue-50); border-color: var(--blue-100); color: var(--blue-600);
    }
    .btn-edit:hover { background: var(--blue-600); border-color: var(--blue-600); color: #fff; }

    .btn-del {
      background: var(--red-50); border-color: var(--red-100); color: var(--red-600);
      text-decoration: none;
    }
    .btn-del:hover { background: var(--red-600); border-color: var(--red-600); color: #fff; }

    /* empty state */
    .empty-state {
      padding: 32px 20px; text-align: center;
      color: var(--gray-400); font-size: .8rem;
    }
    .empty-state i { display: block; font-size: 1.8rem; color: var(--gray-200); margin-bottom: 8px; }

    /* ── EDIT MODAL ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 500;
      background: rgba(15,45,107,.3); backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; animation: fadeIn .2s ease; }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }

    .modal-box {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: 18px;
      width: 100%; max-width: 420px; margin: 20px;
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      animation: slideUp .3s cubic-bezier(.16,1,.3,1);
    }
    @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }

    .modal-head {
      padding: 20px 24px 16px;
      border-bottom: 1px solid var(--gray-100);
      background: var(--gray-50);
      display: flex; align-items: center; justify-content: space-between;
    }
    .modal-head-left { display: flex; align-items: center; gap: 10px; }
    .modal-head-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: var(--blue-50); border: 1px solid var(--blue-100);
      display: flex; align-items: center; justify-content: center;
      color: var(--blue-600); font-size: .95rem;
    }
    .modal-title { font-size: .95rem; font-weight: 800; color: var(--gray-900); }
    .modal-sub   { font-size: .72rem; color: var(--gray-400); margin-top: 2px; }
    .modal-close {
      width: 28px; height: 28px; border-radius: 7px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--gray-500); font-size: .85rem;
      transition: all .15s;
    }
    .modal-close:hover { background: var(--gray-200); color: var(--gray-700); }

    .modal-body-inner { padding: 22px 24px; }
    .modal-label {
      font-size: .68rem; font-weight: 700; letter-spacing: .08em;
      text-transform: uppercase; color: var(--gray-500); margin-bottom: 8px;
    }
    .modal-input {
      width: 100%; height: 44px; padding: 0 14px;
      background: var(--gray-50); border: 1.5px solid var(--gray-200);
      border-radius: 10px; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .875rem; color: var(--gray-700); outline: none;
      transition: border-color .18s, box-shadow .18s;
    }
    .modal-input:focus { border-color: var(--blue-500); box-shadow: 0 0 0 3px rgba(37,99,235,.1); background: var(--white); }

    .modal-footer-btns { display: flex; gap: 10px; padding: 0 24px 22px; }
    .btn-modal-cancel {
      flex: 1; height: 42px; border-radius: 9px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-500); font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .82rem; font-weight: 600; cursor: pointer;
      transition: all .16s;
    }
    .btn-modal-cancel:hover { background: var(--gray-200); color: var(--gray-700); }
    .btn-modal-save {
      flex: 2; height: 42px; border-radius: 9px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border: none; color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .85rem; font-weight: 700; cursor: pointer;
      box-shadow: 0 4px 14px rgba(37,99,235,.28);
      transition: all .18s;
    }
    .btn-modal-save:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(37,99,235,.35); }

    /* ── DELETE CONFIRM ── */
    .del-confirm-overlay {
      display: none; position: fixed; inset: 0; z-index: 600;
      background: rgba(15,45,107,.3); backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .del-confirm-overlay.open { display: flex; animation: fadeIn .2s ease; }
    .del-confirm-box {
      background: var(--white); border: 1px solid var(--gray-200);
      border-radius: 18px; width: 100%; max-width: 380px; margin: 20px;
      overflow: hidden; box-shadow: var(--shadow-lg);
      animation: slideUp .3s cubic-bezier(.16,1,.3,1);
      text-align: center;
    }
    .del-confirm-top { padding: 28px 24px 16px; }
    .del-confirm-icon {
      width: 52px; height: 52px; border-radius: 14px;
      background: var(--red-50); border: 1px solid var(--red-100);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; color: var(--red-600);
      margin: 0 auto 14px;
    }
    .del-confirm-title { font-size: 1rem; font-weight: 800; color: var(--gray-900); margin-bottom: 6px; }
    .del-confirm-msg   { font-size: .82rem; color: var(--gray-400); line-height: 1.55; }
    .del-confirm-item  { font-weight: 700; color: var(--gray-700); }
    .del-confirm-btns  { display: flex; gap: 10px; padding: 16px 24px 24px; }
    .btn-del-cancel {
      flex: 1; height: 42px; border-radius: 9px;
      background: var(--gray-100); border: 1px solid var(--gray-200);
      color: var(--gray-500); font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .16s;
    }
    .btn-del-cancel:hover { background: var(--gray-200); }
    .btn-del-confirm {
      flex: 2; height: 42px; border-radius: 9px;
      background: var(--red-600); border: none; color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: .82rem; font-weight: 700; cursor: pointer;
      text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
      transition: all .16s; box-shadow: 0 4px 14px rgba(220,38,38,.22);
    }
    .btn-del-confirm:hover { background: var(--red-700); color: #fff; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .catalogs-grid { grid-template-columns: 1fr; }
      .ph-counts { display: none; }
    }
    @media (min-width: 601px) and (max-width: 900px) {
      .catalogs-grid { grid-template-columns: 1fr 1fr; }
      .catalogs-grid > :last-child { grid-column: 1 / -1; }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="ph-left">
      <div class="ph-eyebrow"><i class="bi bi-database-fill"></i> Admin · Catalog Management</div>
      <div class="ph-title">Medical <em>Catalogs</em></div>
      <div class="ph-sub">Add, edit or remove lab tests, nursing procedures and pharmacy medicines.</div>
    </div>
    <div class="ph-counts">
      <div class="count-pill">
        <div class="count-pill-dot" style="background:var(--blue-500)"></div>
        <div class="count-pill-num"><?= count($labTests) ?></div>
        <div class="count-pill-lbl">Lab Tests</div>
      </div>
      <div class="count-pill">
        <div class="count-pill-dot" style="background:var(--green-600)"></div>
        <div class="count-pill-num"><?= count($nursingProcedures) ?></div>
        <div class="count-pill-lbl">Procedures</div>
      </div>
      <div class="count-pill">
        <div class="count-pill-dot" style="background:var(--violet-600)"></div>
        <div class="count-pill-num"><?= count($pharmacyDrugs) ?></div>
        <div class="count-pill-lbl">Medicines</div>
      </div>
    </div>
  </div>

  <!-- CATALOGS GRID -->
  <div class="catalogs-grid">
    <?php foreach ($sections as $idx => $sec):
      $c = $sec['color'];
    ?>
    <div class="catalog-panel">

      <!-- panel header -->
      <div class="panel-head <?= $c ?>">
        <div class="panel-head-left">
          <div class="panel-icon <?= $c ?>"><i class="bi <?= $sec['icon'] ?>"></i></div>
          <div>
            <div class="panel-title"><?= $sec['label'] ?>s</div>
            <div class="panel-sub"><?= $sec['desc'] ?></div>
          </div>
        </div>
        <span class="item-badge <?= $c ?>"><?= count($sec['data']) ?> items</span>
      </div>

      <!-- add form -->
      <div class="add-form-wrap">
        <div class="add-form-label"><i class="bi bi-plus-circle" style="margin-right:4px"></i>Add New <?= $sec['label'] ?></div>
        <form action="insert_catalog_item.php" method="POST">
          <input type="hidden" name="type" value="<?= $sec['type'] ?>">
          <div class="add-form-row">
            <input type="text" name="item_name" class="add-input"
              placeholder="Enter <?= strtolower($sec['label']) ?> name…" required>
            <button type="submit" class="btn-add <?= $c ?>">
              <i class="bi bi-plus-lg"></i> Add
            </button>
          </div>
        </form>
      </div>

      <!-- items list -->
      <div class="items-list">
        <?php if ($sec['data']): ?>
          <?php foreach ($sec['data'] as $i => $item): ?>
          <div class="item-row">
            <span class="item-index"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></span>
            <span class="item-name"><?= htmlspecialchars($item[$sec['field']]) ?></span>
            <div class="item-actions">
              <button class="btn-edit"
                onclick="openEdit('<?= $item['id'] ?>','<?= htmlspecialchars(addslashes($item[$sec['field']]), ENT_QUOTES) ?>','<?= $sec['type'] ?>')"
                title="Edit">
                <i class="bi bi-pencil-fill"></i>
              </button>
              <a href="#" class="btn-del"
                onclick="openDel('delete_catalog_item.php?type=<?= $sec['type'] ?>&id=<?= $item['id'] ?>','<?= htmlspecialchars(addslashes($item[$sec['field']]), ENT_QUOTES) ?>');return false;"
                title="Delete">
                <i class="bi bi-trash3-fill"></i>
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            No <?= strtolower($sec['label']) ?>s added yet.
          </div>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <form method="POST" action="update_catalog_item.php">
      <div class="modal-head">
        <div class="modal-head-left">
          <div class="modal-head-icon"><i class="bi bi-pencil-fill"></i></div>
          <div>
            <div class="modal-title">Edit Item</div>
            <div class="modal-sub">Update the name of this catalog entry.</div>
          </div>
        </div>
        <button type="button" class="modal-close" onclick="closeEdit()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="modal-body-inner">
        <input type="hidden" name="id"   id="edit-id">
        <input type="hidden" name="type" id="edit-type">
        <div class="modal-label">New Name</div>
        <input type="text" name="item_name" id="edit-name"
          class="modal-input" placeholder="Enter updated name…" required>
      </div>
      <div class="modal-footer-btns">
        <button type="button" class="btn-modal-cancel" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn-modal-save">
          <i class="bi bi-check-lg"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="del-confirm-overlay" id="delModal">
  <div class="del-confirm-box">
    <div class="del-confirm-top">
      <div class="del-confirm-icon"><i class="bi bi-trash3-fill"></i></div>
      <div class="del-confirm-title">Delete Item?</div>
      <div class="del-confirm-msg">
        You are about to delete <span class="del-confirm-item" id="delItemName">this item</span>.<br>
        This action cannot be undone.
      </div>
    </div>
    <div class="del-confirm-btns">
      <button class="btn-del-cancel" onclick="closeDel()">Cancel</button>
      <a href="#" id="delConfirmLink" class="btn-del-confirm">
        <i class="bi bi-trash3-fill"></i> Yes, Delete
      </a>
    </div>
  </div>
</div>

<script>
  // ── EDIT MODAL ──
  function openEdit(id, name, type) {
    document.getElementById('edit-id').value   = id;
    document.getElementById('edit-type').value = type;
    document.getElementById('edit-name').value = name;
    document.getElementById('editModal').classList.add('open');
    setTimeout(() => document.getElementById('edit-name').focus(), 200);
  }
  function closeEdit() {
    document.getElementById('editModal').classList.remove('open');
  }
  document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
  });

  // ── DELETE MODAL ──
  function openDel(href, name) {
    document.getElementById('delConfirmLink').href = href;
    document.getElementById('delItemName').textContent = '"' + name + '"';
    document.getElementById('delModal').classList.add('open');
  }
  function closeDel() {
    document.getElementById('delModal').classList.remove('open');
  }
  document.getElementById('delModal').addEventListener('click', function(e) {
    if (e.target === this) closeDel();
  });
</script>
</body>
</html>