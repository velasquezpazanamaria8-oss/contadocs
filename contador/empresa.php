<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id=?", [$user['estudio_id']]);
$emp_id  = trim($_GET['id'] ?? '');
if (!$emp_id) redirect('/contador/clientes.php');

$empresa = Database::fetch("SELECT * FROM empresas_cliente WHERE id=? AND estudio_id=?", [$emp_id,$user['estudio_id']]);
if (!$empresa) redirect('/contador/clientes.php');

$mensaje = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'eliminar_doc') {
    $doc_id = $_POST['doc_id'] ?? '';
    $doc = Database::fetch("SELECT * FROM documentos d JOIN empresas_cliente ec ON d.empresa_id=ec.id WHERE d.id=? AND ec.estudio_id=?", [$doc_id,$user['estudio_id']]);
    if ($doc) {
        // Borrar archivo físico
        $filepath = __DIR__ . '/../../uploads/' . $doc['storage_path'];
        if (file_exists($filepath)) @unlink($filepath);
        Database::query("DELETE FROM descargas_log WHERE documento_id=?", [$doc_id]);
        Database::query("DELETE FROM documentos WHERE id=?", [$doc_id]);
        $mensaje = 'Documento eliminado.';
    }
}

$periodo = trim($_GET['periodo'] ?? '');
$where = "d.empresa_id=?"; $params = [$emp_id];
if ($periodo) { $where .= " AND d.periodo=?"; $params[] = $periodo; }

$docs = Database::fetchAll(
    "SELECT d.*, c.nombre as cat_nombre, c.color, c.color_texto,
            (SELECT COUNT(*) FROM descargas_log WHERE documento_id=d.id) as total_descargas,
            (SELECT MAX(descargado_at) FROM descargas_log WHERE documento_id=d.id) as ultima_descarga
     FROM documentos d LEFT JOIN categorias c ON d.categoria_id=c.id
     WHERE $where ORDER BY d.created_at DESC", $params
);

$periodos = Database::fetchAll("SELECT DISTINCT periodo FROM documentos WHERE empresa_id=? ORDER BY periodo DESC", [$emp_id]);
$stats_emp = Database::fetch(
    "SELECT COUNT(*) as total_docs, SUM(tamanio) as total_size,
            (SELECT COUNT(*) FROM descargas_log WHERE empresa_id=?) as total_descargas
     FROM documentos WHERE empresa_id=?", [$emp_id,$emp_id]
);

$nav_active='clientes'; $user_rol='contador'; $user_nombre=$estudio['nombre']??''; $user_plan=$estudio['plan_id']??'';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($empresa['razon_social']) ?> — ContaDocs</title>
<link rel="stylesheet" href="/assets/css/app.css?v=2">
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:100;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.2);width:100%;max-width:420px;padding:28px;transform:scale(.97);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1)}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div class="topbar-left">
        <div style="display:flex;align-items:center;gap:8px">
          <a href="/contador/clientes.php" style="color:var(--gris-400);text-decoration:none;font-size:13px;display:flex;align-items:center;gap:4px">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Clientes
          </a>
          <span style="color:var(--gris-300)">/</span>
          <h1 style="font-size:16px"><?= e($empresa['razon_social']) ?></h1>
        </div>
        <p>RUC <?= e($empresa['ruc']) ?> · <?= e($empresa['email_acceso']) ?></p>
      </div>
      <div class="topbar-actions">
        <a href="/contador/subir.php?empresa_id=<?= e($emp_id) ?>" class="btn btn-primary">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
          Subir documentos
        </a>
      </div>
    </div>

    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <!-- Stats empresa -->
      <div class="metrics-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
        <div class="metric-card blue">
          <div class="metric-top">
            <div><div class="metric-label">Documentos subidos</div><div class="metric-value"><?= $stats_emp['total_docs'] ?></div></div>
            <div class="metric-icon blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
          </div>
        </div>
        <div class="metric-card green">
          <div class="metric-top">
            <div><div class="metric-label">Total descargas</div><div class="metric-value"><?= $stats_emp['total_descargas'] ?></div></div>
            <div class="metric-icon green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></div>
          </div>
        </div>
        <div class="metric-card purple">
          <div class="metric-top">
            <div><div class="metric-label">Espacio usado</div><div class="metric-value" style="font-size:20px"><?= $stats_emp['total_size'] ? formatBytes((int)$stats_emp['total_size']) : '0 KB' ?></div></div>
            <div class="metric-icon purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg></div>
          </div>
        </div>
      </div>

      <!-- Filtro período -->
      <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;flex-wrap:wrap">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <input type="hidden" name="id" value="<?= e($emp_id) ?>">
          <select name="periodo" class="form-select" style="width:auto" onchange="this.form.submit()">
            <option value="">Todos los períodos</option>
            <?php foreach ($periodos as $p): ?>
            <option value="<?= e($p['periodo']) ?>" <?= $periodo===$p['periodo']?'selected':'' ?>><?= e($p['periodo']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($periodo): ?><a href="/contador/empresa.php?id=<?= e($emp_id) ?>" class="btn btn-secondary btn-sm">Limpiar</a><?php endif; ?>
        </form>
        <span class="text-muted" style="margin-left:auto"><?= count($docs) ?> documentos</span>
      </div>

      <!-- Lista de documentos -->
      <div class="card">
        <?php if (empty($docs)): ?>
        <div class="empty-state">
          <div class="empty-icon">📄</div>
          <div class="empty-title">Sin documentos<?= $periodo?" en $periodo":'' ?></div>
          <div class="empty-sub">Sube el primer documento usando el botón de arriba</div>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Documento</th><th>Categoría</th><th>Período</th><th>Tamaño</th><th>Subido</th><th>Descargas</th><th>Última desc.</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($docs as $doc): ?>
              <tr>
                <td>
                  <div class="fw" style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:20px">📄</span>
                    <?= e($doc['nombre']) ?>
                  </div>
                </td>
                <td>
                  <?php if ($doc['cat_nombre']): ?>
                  <span style="background:<?= e($doc['color']??'#f1f5f9') ?>;color:<?= e($doc['color_texto']??'#475569') ?>;padding:3px 8px;border-radius:5px;font-size:11px;font-weight:600">
                    <?= e($doc['cat_nombre']) ?>
                  </span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="badge badge-blue badge-none"><?= e($doc['periodo']) ?></span></td>
                <td style="font-size:12px;color:var(--gris-400)"><?= $doc['tamanio']?formatBytes((int)$doc['tamanio']):'—' ?></td>
                <td style="font-size:12px;color:var(--gris-500)"><?= tiempoRelativo($doc['created_at']) ?></td>
                <td>
                  <span class="badge <?= $doc['total_descargas']>0?'badge-green':'badge-gray' ?> badge-none">
                    <?= $doc['total_descargas'] ?> <?= $doc['total_descargas']==1?'vez':'veces' ?>
                  </span>
                </td>
                <td style="font-size:12px;color:var(--gris-400)">
                  <?= $doc['ultima_descarga'] ? tiempoRelativo($doc['ultima_descarga']) : 'Nunca' ?>
                </td>
                <td>
                  <button class="btn btn-ghost btn-sm" style="color:var(--rojo)" onclick='eliminarDoc("<?= e($doc['id']) ?>","<?= e($doc['nombre']) ?>")'>🗑️</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal eliminar doc -->
<div class="modal-overlay" id="modalEliminarDoc" onclick="if(event.target===this)cerrarModal()">
  <div class="modal">
    <div style="text-align:center;margin-bottom:16px"><div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:26px">🗑️</div></div>
    <div class="modal-title" style="text-align:center">¿Eliminar documento?</div>
    <div id="eliminarDocNombre" style="text-align:center;font-size:13px;color:var(--gris-500);margin:6px 0 16px"></div>
    <div class="alert alert-error">El documento se eliminará permanentemente. Los clientes ya no podrán descargarlo.</div>
    <form method="POST">
      <input type="hidden" name="action" value="eliminar_doc">
      <input type="hidden" name="doc_id" id="eliminarDocId">
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-danger">Sí, eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
function cerrarModal() { document.getElementById('modalEliminarDoc').classList.remove('open'); }
function eliminarDoc(id, nombre) {
  document.getElementById('eliminarDocId').value  = id;
  document.getElementById('eliminarDocNombre').textContent = nombre;
  document.getElementById('modalEliminarDoc').classList.add('open');
}
</script>
</body>
</html>
